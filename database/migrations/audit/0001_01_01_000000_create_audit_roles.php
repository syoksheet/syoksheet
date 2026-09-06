<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The audit log is append-only because these roles are missing the permission to
     * update or delete, not because application code refuses to. A bug or a stolen
     * credential therefore still cannot rewrite history.
     */
    private const string APPLICATION_ROLE = 'syoksheet_audit_app';

    private const string ERASURE_ROLE = 'syoksheet_audit_erasure';

    public function up(): void
    {
        foreach ([self::APPLICATION_ROLE, self::ERASURE_ROLE] as $role) {
            // Postgres has no CREATE ROLE IF NOT EXISTS, and roles are cluster-wide.
            DB::statement(sprintf(<<<'SQL'
                DO $$
                BEGIN
                    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '%s') THEN
                        CREATE ROLE %s LOGIN;
                    END IF;
                END
                $$;
            SQL, $role, $role));
        }

        // This is a default privilege rather than a GRANT because the audit tables do
        // not exist yet. Postgres applies a default privilege at CREATE TABLE time, so
        // every audit table added later arrives with the right access list already set.
        //
        // Keep FOR ROLE CURRENT_USER as it is. Whoever creates the tables differs
        // between environments, so a hardcoded role name would be right in one place
        // and would silently grant nothing in the other.
        DB::statement(sprintf(
            'ALTER DEFAULT PRIVILEGES FOR ROLE CURRENT_USER IN SCHEMA public GRANT INSERT, SELECT ON TABLES TO %s',
            self::APPLICATION_ROLE
        ));
    }

    /**
     * This destroys no data. The roles own nothing, because the audit tables belong to
     * whoever ran the migration.
     *
     * Roles are cluster-wide, though, so dropping them here removes them for every
     * database on the server rather than only this one.
     */
    public function down(): void
    {
        DB::statement(sprintf(
            'ALTER DEFAULT PRIVILEGES FOR ROLE CURRENT_USER IN SCHEMA public REVOKE INSERT, SELECT ON TABLES FROM %s',
            self::APPLICATION_ROLE
        ));

        foreach ([self::APPLICATION_ROLE, self::ERASURE_ROLE] as $role) {
            DB::statement(sprintf('DROP OWNED BY %s', $role));
            DB::statement(sprintf('DROP ROLE IF EXISTS %s', $role));
        }
    }
};

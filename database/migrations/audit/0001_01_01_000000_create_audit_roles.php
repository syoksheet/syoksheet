<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Append-only is a missing permission, not a rule in application code, so a bug or
     * a stolen credential still cannot rewrite history.
     *
     * The grant is expressed as a default privilege rather than a `GRANT` because the
     * audit tables do not exist yet: Phase 6 creates them. A default privilege is a
     * standing instruction applied at CREATE TABLE time, so every audit table any later
     * phase adds arrives with the right access list and nobody has to remember.
     *
     * `FOR ROLE CURRENT_USER` is deliberate and must not become a literal. The
     * instruction is keyed to whoever creates the table, which is `db` locally and
     * `forge` in production. A hardcoded name would be correct here and silently do
     * nothing there, leaving the audit tables ungranted.
     *
     * The erasure role gets no default privilege. Its `UPDATE` is scoped to the
     * anonymisable columns, which `ALTER DEFAULT PRIVILEGES` cannot express, so Phase 6
     * grants it beside each table. A table-wide `UPDATE` here would look symmetrical
     * and dissolve the guarantee.
     */
    private const string APPLICATION_ROLE = 'syoksheet_audit_app';

    private const string ERASURE_ROLE = 'syoksheet_audit_erasure';

    public function up(): void
    {
        foreach ([self::APPLICATION_ROLE, self::ERASURE_ROLE] as $role) {
            // Postgres has no CREATE ROLE IF NOT EXISTS, and roles are cluster-wide, so
            // this must survive a second environment on the same cluster.
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

        DB::statement(sprintf(
            'ALTER DEFAULT PRIVILEGES FOR ROLE CURRENT_USER IN SCHEMA public GRANT INSERT, SELECT ON TABLES TO %s',
            self::APPLICATION_ROLE
        ));
    }

    /**
     * Destroys no data: the roles own nothing, since the audit tables are owned by
     * whoever ran the migration. But roles are cluster-wide, so this removes them for
     * every database on the server, not only this one.
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

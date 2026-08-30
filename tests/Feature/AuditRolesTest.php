<?php

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->artisan('migrate:all')->assertExitCode(0);
});

/**
 * Default privileges are stored per database, so asserting them proves the migration
 * ran against the audit test database. Role existence proves less: roles are
 * cluster-wide, so one created by a development run already exists here.
 *
 * @return list<string>
 */
function defaultTablePrivilegesFor(string $role): array
{
    /** @var list<object{privilege_type: string}> $rows */
    $rows = DB::connection('audit')->select(<<<'SQL'
        SELECT privilege_type
        FROM pg_default_acl, aclexplode(defaclacl)
        WHERE defaclnamespace = 'public'::regnamespace
          AND defaclobjtype = 'r'
          AND grantee::regrole::text = ?
    SQL, [$role]);

    return array_map(static fn (object $row): string => $row->privilege_type, $rows);
}

it('creates the application and erasure roles', function () {
    $roles = DB::connection('audit')
        ->table('pg_roles')
        ->whereIn('rolname', ['syoksheet_audit_app', 'syoksheet_audit_erasure'])
        ->pluck('rolname')
        ->all();

    expect($roles)->toEqualCanonicalizing(['syoksheet_audit_app', 'syoksheet_audit_erasure']);
});

it('lets the application role only insert and read future audit tables', function () {
    expect(defaultTablePrivilegesFor('syoksheet_audit_app'))
        ->toEqualCanonicalizing(['INSERT', 'SELECT']);
});

it('gives the erasure role no blanket privilege on future audit tables', function () {
    expect(defaultTablePrivilegesFor('syoksheet_audit_erasure'))->toBeEmpty();
});

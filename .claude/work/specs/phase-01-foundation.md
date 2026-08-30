# Phase 1 Foundation: resolved design questions

Decisions taken before planning Phase 1, in the order they were raised. Each existed because the specs did not decide it, not because they were ambiguous. Dated 2026-08-30.

## 1. Audit database grants land in two phases

Phase 1 owns "the audit database's two-user grants as code"; Phase 6 creates the audit tables. You cannot `GRANT` on a table that does not exist.

**Resolution:** Phase 1 creates both roles and uses `ALTER DEFAULT PRIVILEGES` on the schema, so the application user's `INSERT`/`SELECT` applies automatically to every table Phase 6 creates. The erasure user's `UPDATE` is column-scoped, which default privileges cannot express, so that half is explicitly deferred to Phase 6 and must be added alongside each table's migration.

**Why:** append-only becomes true by construction from the moment the tables exist, rather than depending on someone remembering to add a grant in a later phase.

## 2. `migrate:all` is not a scheduled command

The build-step artifact checklist says "new Artisan command → `docs/scheduled-jobs.md`", but that file is the canonical Artisan **schedule** and `migrate:all` runs at deploy time.

**Resolution:** no entry in `scheduled-jobs.md`. It is already documented where it is used, in `docs/infrastructure/deployment.md` and `docs/infrastructure/local-development.md`. The checklist rule in `.claude/skills/build-step/SKILL.md` is narrowed to *scheduled* commands, with operational commands documented alongside their runbook.

## 3. Horizon supervisors land in Phase 1

**Resolution:** Phase 1 defines all three supervisors (`audit`, `notifications`, `default`) and adds a `staging` key to `environments`.

**Why:** Horizon aborts at boot on any environment absent from `environments`, and Phase 2 runs it as a staging daemon. The queue names are already fixed in `docs/architecture.md`, so mirroring them costs nothing even though nothing dispatches to `audit` until Phase 6 or `notifications` until Phase 7.

## 4. `bruno/` is scaffolded in Phase 1, not deferred

Phase 1 has no API routes, so a Bruno collection can only exercise `/up`.

**Resolution:** scaffold in Phase 1. Nine tag folders, three environments (`local`, `ci`, `staging`), gitignored `bruno/.env`, a `GET /up` request with assertions, and `BrunoSeeder`. CI step 8 runs from the first workflow.

**Why:** its Phase 1 value is not testing the API, which does not exist. It is proving the pipeline (seeder runs, `artisan serve` comes up, `bru run` executes, the JUnit reporter annotates failures) before Phase 2's first real deploy depends on all of it. Deferring to Phase 4 was considered and rejected: it would move that discovery into the deploy.

## 5. The suite runs on PostgreSQL

`phpunit.xml` inherited Laravel's `sqlite` + `:memory:` default, which blocked Task 2 (two connections, two databases) and Task 3 outright (SQLite has no roles or grants), and silently permitted schemas Postgres would reject.

**Resolution:** two test databases created by the `post-start` hook, `phpunit.xml` pointed at them, and a guard in `Tests\TestCase` that refuses any connection whose database does not end in `_testing`. Separate databases rather than a dedicated test instance, because CI provisions one PostgreSQL service and local must not test a shape CI does not have.

## 6. Redis is deliberately not isolated for tests

Nothing in the suite writes to Redis: cache and session are `array`, the queue is `sync`, and `RedisConnectionTest` only reads which database a connection selected.

**Resolution:** no test Redis databases for now. **The trigger to revisit is the first test that writes to Redis**, which is Phase 6's `AuditLogJob` or Phase 7's notifications. A key prefix is not sufficient isolation: Laravel's `RedisStore::flush()` calls `flushdb()`, which ignores the prefix and would wipe development data. Separate database numbers are the only real isolation.

When that happens, `RedisConnectionTest` needs attention in the same change: it asserts the real database numbers, so overriding them for tests would make it assert the overrides and stop verifying anything.

## 7. Audit role names, and no passwords in the migration

`database/audit.md` describes an "Application" and an "Erasure" user without naming them.

**Resolution:** `syoksheet_audit_app` and `syoksheet_audit_erasure`. Roles are cluster-wide, so the prefix earns its place the same way the database prefix does.

They are created with `LOGIN` and **no password**. A password is a credential, which is environment config rather than schema, and putting one in a migration commits a secret to git. `operations.md` already treats recreating these users as a provisioning step, so staging sets its own in Phase 2 and production in Phase 19. Nothing connects as them before Phase 6.

## 8. The audit database needs three identities, not two

Task 3 surfaced a gap the specs do not cover. `database/audit.md` names two runtime users, but a third is implied and unnamed:

| Identity | Does what | Specified |
|---|---|---|
| Owner | Runs `migrate --database=audit`, creates and **owns** the tables | No |
| Application | `INSERT`, `SELECT`, for `AuditLogJob` and reads | Yes |
| Erasure | `UPDATE` on anonymisable columns | Yes |

A table's owner can always do anything to it, including `DROP`, and that cannot be revoked. So if the application connects as the role that ran the migrations, the append-only guarantee is worth nothing. One Laravel connection cannot be both, because a role with only `INSERT`/`SELECT` cannot `CREATE TABLE`.

**Not resolved here.** Task 3 only creates roles and default privileges, which is coherent either way. **Phase 6 must decide** how many audit connections exist and which role each uses, before `AuditLogJob` writes anything. Deciding it later means retrofitting every audit write path.

## Related corrections made while resolving these

| Correction | Where |
|---|---|
| CI never uploaded the build artifact the deploy script requires | `docs/infrastructure/deployment.md` step 7 |
| Three secret groups were undocumented (R2 upload, Cloudflare Access service token, `SENTRY_AUTH_TOKEN`) | Both `deployment.md` files, now a table with the phase each is first needed |
| Phase 1's CI workflow needs **no** GitHub secrets | Same table. Bruno's seeder credentials are plain CI variables by design |
| Primary database table count said 49, breakdown says 61 | `docs/architecture.md` |
| Database placement contradicted across five docs | Resolved by decision: both environments run two managed clusters |

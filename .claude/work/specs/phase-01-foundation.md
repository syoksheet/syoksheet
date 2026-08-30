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

## Related corrections made while resolving these

| Correction | Where |
|---|---|
| CI never uploaded the build artifact the deploy script requires | `docs/infrastructure/deployment.md` step 7 |
| Three secret groups were undocumented (R2 upload, Cloudflare Access service token, `SENTRY_AUTH_TOKEN`) | Both `deployment.md` files, now a table with the phase each is first needed |
| Phase 1's CI workflow needs **no** GitHub secrets | Same table. Bruno's seeder credentials are plain CI variables by design |
| Primary database table count said 49, breakdown says 61 | `docs/architecture.md` |
| Database placement contradicted across five docs | Resolved by decision: both environments run two managed clusters |

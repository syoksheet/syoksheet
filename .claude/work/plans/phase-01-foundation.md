# Phase 1: Foundation

**Goal:** make the application use the environment Phase 0 built, and put every convention later phases depend on in place before any feature exists.

**Specs:** `docs/architecture.md` (domains, guards, connections, Redis split, queues), `docs/validation.md` (error codes), `docs/localization.md`, `docs/ai.md`, `docs/api/README.md` (Bruno rules), `docs/database/README.md` + `docs/database/audit.md` (conventions, audit users), `docs/infrastructure/deployment.md` (CI pipeline, secrets), `docs/infrastructure/local-development.md`, `.claude/work/specs/phase-01-foundation.md` (the four resolved holes).

**Audit events:** none. This phase writes no user or org data.

**Working agreement:** learning mode. Default is the user writes. Claude takes three tasks only: pure configuration whose values are already fixed by spec (Horizon), bulk transcription from an existing source (SCSS tokens, the Bruno `.bru` files), and YAML that matches an existing file in the repo (CI). Everything else is the user's, with escalation level 1 as the default.

## Constraints

Values copied verbatim from the specs. Do not paraphrase.

| Setting | Value | Source |
|---|---|---|
| Redis DB split | default 0, cache 1, session 2, queue 3 | architecture.md § Redis |
| Queues and priority | `audit` (highest, retries forever), `notifications` (medium), `default` (normal) | architecture.md § Events, Queues & Jobs |
| Domains | `app.`, `admin.`, apex, `api.` | architecture.md § Domains |
| Audit application user | `INSERT`, `SELECT` only. No `UPDATE`, no `DELETE`, ever | database/audit.md:16 |
| Audit erasure user | `UPDATE` on anonymisable columns only | database/audit.md:17 |
| Audit connection name | `audit` | architecture.md § Databases |
| Audit migrations path | `database/migrations/audit/`, own history table on `audit` | local-development.md:55 |
| Filesystem disks | `r2_public`, `r2_private`; `FILESYSTEM_DISK=r2_private` | environment-variables.md § Cloudflare R2 |
| R2 region / path style | `auto` / `true`, hardcoded constants, never env | environment-variables.md § Cloudflare R2 |
| Error-code shape | 422 with a stable `code` beside the message | validation.md § Business-Rule Error Codes |
| AI config keys | `AI_PROVIDER`, `ANTHROPIC_API_KEY`, `AI_MODEL_JUDGMENT`, `AI_MODEL_BULK` | ai.md § Configuration |
| Bruno environments | `local`, `ci`, `staging`. **Production is never a Bruno environment** | api/README.md § Bruno Collection |
| CI secrets needed this phase | None | deployment.md § Secrets and CI variables |
| Test databases | `syoksheet_primary_testing`, `syoksheet_audit_testing`, created by the `post-start` hook | local-development.md § Databases |
| Test engine | PostgreSQL, never SQLite. `Tests\TestCase` refuses a database not ending in `_testing` | decisions.md, 2026-08-30 |

## Observability review

Answered before implementing, per the build-step gate.

1. **New exception types:** one, `BusinessRuleException` (Task 10). It is an **expected condition**, a 422 the application raises deliberately, so it joins `ignore_exceptions` in `config/sentry.php` in the same task that creates it.
2. **New routes worth tracing:** none. The four domain groups carry no real routes yet, and `/up` is already in `ignore_transactions`.
3. **Personal data into exceptions or breadcrumbs:** none. No user data exists yet. `send_default_pii` stays `false`.
4. **New background jobs:** none. Horizon supervisors are defined but nothing dispatches to `audit` until Phase 6 or `notifications` until Phase 7.

## Tasks

### Task 1: Redis connections and queue priorities

**Files:** create `app/Enums/QueueName.php`, modify `config/database.php` (add `session` and `queue` Redis connections), modify `config/queue.php` (the three queues).

**Behaviour:** `cache` resolves to DB 1, `session` to DB 2, `queue` to DB 3, `default` to DB 0. Two of the three commented-out keys in `.env` become live here, `SESSION_CONNECTION` and `REDIS_QUEUE_CONNECTION`; the third, `FILESYSTEM_DISK=r2_private`, waits for Task 5.

**Who writes:** user. First time touching connection definitions, the eviction reasoning behind the split is worth internalising once, and `QueueName` is the first enum in the codebase, so it sets the convention every later enum copies.

**Read first:** `docs/architecture.md` § Redis and § Events, Queues & Jobs. `docs/infrastructure/local-development.md` § Redis for why `volatile-lru` makes the split safe rather than sufficient on its own.

- [x] Implement, `QueueName` first so the config can reference it
- [x] Contract test: `RedisConnectionTest` asserts each connection reports its expected database number at runtime, and that `QueueName` carries exactly the three names `architecture.md` fixes
- [x] Green: `ddev php artisan test --compact --filter=RedisConnection`
- [x] Uncomment the two `.env` keys, `ddev restart`, confirm requests still serve

### Task 2: The `audit` connection, audit migrations path, and `migrate:all`

**Files:** modify `config/database.php` (`audit` connection), create `database/migrations/audit/.gitkeep`, create `app/Console/Commands/MigrateAll.php`, test `tests/Feature/MigrateAllTest.php`.

**Behaviour:** `DB::connection('audit')` resolves against database `syoksheet_audit` on the same host as `pgsql`. `migrate:all` runs the default path on `pgsql` and `database/migrations/audit/` on `log`, each with its own history table, and passes `--force` through.

**Who writes:** user. The connection is configuration; the command is the first real Artisan class in the codebase and the deploy script depends on it.

**Read first:** `docs/database/README.md` § Conventions, `docs/architecture.md` § Databases, `docs/infrastructure/deployment.md:37`. Boost `search-docs` for "migrate path connection" and "artisan command signature".

- [x] Failing test: `migrate:all runs both connections` — fails because the command does not exist
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=MigrateAll`
- [x] `ddev exec psql -d syoksheet_audit -c '\dt'` shows the audit history table

### Task 3: Audit database roles and default privileges

**Files:** create `database/migrations/audit/0001_01_01_000000_create_audit_roles.php`.

**Behaviour:** creates the application and erasure roles and sets `ALTER DEFAULT PRIVILEGES` so every table later created in the audit schema grants `INSERT`, `SELECT` to the application role automatically. Idempotent: safe to run against a database where the roles already exist.

**Who writes:** user. Raw SQL in a migration is new ground, and this migration is what makes "the app cannot rewrite history" structurally true.

**Read first:** `docs/database/audit.md:12-20` for both users and the reasoning. `.claude/work/specs/phase-01-foundation.md` § 1 for why the erasure user's grant is deferred. PostgreSQL docs for `ALTER DEFAULT PRIVILEGES` and `CREATE ROLE ... IF NOT EXISTS` behaviour, which differs from `CREATE DATABASE`.

> [!WARNING]
> The erasure user's `UPDATE` is column-scoped and `ALTER DEFAULT PRIVILEGES` cannot express a column list. That half is Phase 6's, added beside each table's migration. Do not fake it with a table-wide `UPDATE`: that dissolves the append-only guarantee this migration exists to create.

- [x] Failing test: `AuditRolesTest` asserts both roles exist and the application role has no `UPDATE` default privilege
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=AuditRoles`

### Task 4: Horizon supervisors and the staging environment

**Files:** modify `config/horizon.php`.

**Behaviour:** `environments` gains `staging`; each environment defines three supervisors mirroring the queue table, with `audit` retrying forever.

**Who writes:** claude. Pure configuration whose every value is already fixed by `docs/architecture.md`, and Horizon aborts at boot without a `staging` key, which Phase 2 needs.

- [x] Implement
- [x] Contract test: `HorizonConfigTest` asserts all three environments exist and `audit` has unlimited tries
- [x] Green: `ddev php artisan test --compact --filter=HorizonConfig`

### Task 5: Filesystem disks and the local public bucket policy

**Files:** modify `config/filesystems.php` (add `r2_public`, `r2_private`; remove the unused `s3` disk), modify `.ddev/config.yaml` (bucket policy in the existing `post-start` hook).

**Behaviour:** both disks resolve against MinIO locally. `Storage::disk('r2_private')->temporaryUrl()` returns a signed URL. `syoksheet-public-local` is anonymously readable.

**Who writes:** user. First disk definitions, and the region/path-style decision is a teachable one.

**Read first:** `docs/infrastructure/environment-variables.md` § Cloudflare R2, including the note on why region and path style are hardcoded rather than env-driven. `docs/infrastructure/local-development.md` § Object Storage for the `:10101` endpoint trap.

- [x] Failing test: `FilesystemDisksTest` asserts both disks resolve and `temporaryUrl()` works on `r2_private`
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=FilesystemDisks`
- [x] `mc anonymous set download` added to the hook; `curl` the public bucket URL and get 200, not 403

### Task 6: Per-domain host configuration

**Files:** create `config/domains.php` and `app/Enums/Domain.php`, modify `.env` and `.env.example` (four host keys).

**Behaviour:** one config source naming the four domain hosts per environment, consumed by both `Route::domain()` in Task 7 and by absolute-URL building in later phases. Named `domains` to match the Laravel method that reads it, so `Route::domain(config('domains.admin'))` needs no explanation.

**Who writes:** user.

**Read first:** `docs/infrastructure/environment-variables.md` § "APP_URL is a fallback, not an origin". The rule this exists to enforce: no notification relies on the ambient root.

- [x] Implement
- [x] Contract test: `DomainConfigTest` asserts all four hosts resolve and none is empty
- [x] Green: `ddev php artisan test --compact --filter=DomainConfig`

### Task 7: `Route::domain()` skeleton

**Files:** modify `bootstrap/app.php` (register the route files), create `routes/app.php`, `routes/admin.php`, `routes/api.php`, `routes/public.php`, modify `routes/web.php`.

**Behaviour:** each domain answers only on its own host. A request for an `app.` route sent to the apex 404s.

> [!NOTE]
> Internal routes carry **no `/api` prefix**: `app.`, `admin.` and the apex are all Inertia, and there is no internal API to prefix. Only `api.` carries versioned JSON, at `/v1/*` and `/admin/v1/*`, with no `/api` prefix because the host already says it. Laravel's `withRouting(api:)` adds `/api` by default, so the api group needs its prefix set explicitly rather than inherited.

**Who writes:** user. This is the structural decision the whole application hangs off.

**Read first:** `docs/architecture.md` § Domains and § Auth & Guards. Boost `search-docs` for "route domain group" and "withRouting then".

- [x] Failing test: `DomainRoutingTest`, one case per domain, each asserting the route answers on its own host and 404s on the other three
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=DomainRouting`
- [x] `curl` all four local hostnames and confirm each serves its own domain

### Task 8: Inertia bootstrap

**Files:** modify `bootstrap/app.php` (Inertia middleware), create `app/Http/Middleware/HandleInertiaRequests.php`, create `resources/views/app.blade.php`, modify `resources/ts/app.ts` (page resolution), create `resources/ts/pages/Welcome.svelte`.

> **Superseded by `phase-01-apex-ssr.md`.** That plan splits `HandleInertiaRequests` into one middleware class per domain and moves the pages under `resources/ts/pages/<domain>/`. The files named above no longer exist under these names. This task record is kept as written, since it describes what was built at the time.

**Behaviour:** an Inertia page renders on `app.` through Svelte 5, with shared props flowing from the middleware.

**Who writes:** user.

**Read first:** the `inertia-svelte-development` skill, which is the authority for client-side patterns here. Boost `search-docs` for Inertia v3 server-side setup. `docs/architecture.md` § Frontend Layer.

- [x] Implement
- [x] Contract test: `InertiaBootstrapTest` asserts the `app.` root returns an Inertia response with the expected component
- [x] Green: `ddev php artisan test --compact --filter=InertiaBootstrap`
- [x] `ddev exec npm run check` and `ddev exec npm run lint` clean

### Task 9: Design tokens and Geist fonts

**Files:** modify `resources/scss/app.scss`, create the token partials under `resources/scss/`.

**Behaviour:** every colour, spacing, radius and type token from the design system exists as a CSS custom property, with Geist and Geist Mono self-hosted.

**Who writes:** claude. Bulk transcription from `design/docs/DS Colour.html` and its siblings, with no design decisions to make.

**Read first (for review):** `design/docs/DS Colour.html`. The verification mark's forest green is never the primary teal.

- [ ] Implement
- [ ] `ddev exec npm run build` succeeds and the tokens appear in the built CSS
- [ ] User reviews the token names against the design system before the task closes

### Task 10: Error-code and validation scaffolding

**Files:** create `app/Enums/ErrorCode.php`, create `app/Exceptions/BusinessRuleException.php`, modify `bootstrap/app.php` (render it as 422 with `code`), modify `config/sentry.php` (`ignore_exceptions`), modify `docs/api/openapi.json` (`BusinessRuleError` component).

**Behaviour:** throwing `BusinessRuleException` with an `ErrorCode` case produces `{ "message": ..., "code": ... }` at 422 and is not reported to Sentry.

**Who writes:** claude, handed over by the user mid-task. The enum convention is already set by `QueueName` in Task 1; this one adds the fourteen catalogued codes and the exception that carries them.

> The catalogue said fifteen. `sso_required` was moved out to `docs/validation.md` § Authorization codes before implementation: it is a 403 raised in middleware, not a 422 business rule, and it only sat in that table because `BusinessRuleError` was the one error shape carrying a `code`.

**Read first:** `docs/validation.md` § Business-Rule Error Codes for all fifteen codes. `CLAUDE.md` § PHP on enum conventions. `docs/api/README.md` § Conventions on the error shape.

- [ ] Failing test: `BusinessRuleExceptionTest` asserts the 422 body carries a stable `code`
- [ ] Implement
- [ ] Green: `ddev php artisan test --compact --filter=BusinessRuleException`

### Task 11: AI SDK scaffold

**Files:** install `laravel/ai`, publish and configure `config/ai.php`, modify `.env` and `.env.example`.

**Behaviour:** the SDK is installed and configured for Anthropic only, with no key set locally.

**Who writes:** claude, handed over by the user.

> **Reworked mid-task.** This first shipped as a hand-rolled `AiService` contract plus an
> `AnthropicDriver`, per the original plan. The user asked whether we were following the
> Laravel AI SDK; we were not, and had not checked. All of it was deleted and replaced
> with `laravel/ai`. The SDK gives provider swapping, queued calls and structured output
> as maintained first-party code, and both our use cases want structured output rather
> than the raw string the hand-rolled contract returned. See the 2026-08-31 row in
> `syoksheet-docs/product/decisions.md`.

**Read first:** `docs/ai.md`, especially the two hard rules: human review gate, no personal data. https://laravel.com/framework/docs/ai-sdk for agents, `#[Model]` attributes and structured output.

> [!NOTE]
> Scaffold only. No agent is created and no provider call is made in this phase; the two
> use cases arrive in Phase 10.

- [x] Failing test: `AiServiceTest` asserts the SDK registers, defaults to Anthropic, configures no other provider, and publishes no conversation tables
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=AiService`

### Task 12: `bruno/` scaffold and `BrunoSeeder`

**Files (12a):** create `bruno/` with nine tag folders, `bruno.json`, three environment files, `bruno/.env.example`, one `GET /up` request with assertions; modify `.gitignore` for `bruno/.env`.
**Files (12b):** create `database/seeders/BrunoSeeder.php`.

**Behaviour:** `bru run --env local`, from inside `bruno/`, passes against DDEV. The seeder creates deterministic fixtures; in Phase 1 that is an empty but callable seeder, since no models exist beyond `User`.

**Who writes:** 12a claude (bulk `.bru` transcription against a documented format), 12b claude, handed over by the user.

> **Deviations.** Three, all found by running it:
> 1. Bruno CLI 4 only starts at a collection root, so the documented `bru run bruno --env local` fails. Corrected everywhere to `cd bruno && bru run --env local`.
> 2. `/up` content-negotiates. Bruno sends `Accept: application/json` and gets `{"status":"up"}`, so the request asserts that rather than the framework-rendered HTML a browser sees.
> 3. `BrunoSeeder` gained a production guard, which was not in the plan. It exists to create accounts with known credentials, and CI runs it with `--force`, the same flag that skips Laravel's own production prompt.

**Read first:** `docs/api/README.md` § Bruno Collection for the four rules, especially that production is never an environment and secrets are read via `{{process.env.X}}`.

- [x] Implement
- [x] `ddev exec sh -c 'cd bruno && npx @usebruno/cli run --env local'` passes
- [x] `ddev php artisan db:seed --class=BrunoSeeder` runs clean

### Task 13: GitHub Actions CI workflow

**Files:** create `.github/workflows/ci.yml`.

**Behaviour:** the nine documented steps run on push and PR to `main` and `develop`, and on `v*` tags. Phase 1 implements steps 1 to 8; the artifact upload and deploy hooks are stubbed with a comment naming Phase 2, since neither has anywhere to go yet.

**Who writes:** claude. YAML configuration matching the existing `.github/workflows/security.yml`, including its SHA-pinned action convention.

**Read first (for review):** `docs/infrastructure/deployment.md` § CI Pipeline and § Secrets and CI variables. `.github/workflows/security.yml` for the pinning convention: third-party actions pinned to commit SHAs with the version in a trailing comment, never to tags.

- [ ] Implement
- [ ] Green: the workflow passes on a pushed branch, with all eight steps visible

## Artifacts

| Checklist item | This phase |
|---|---|
| Route change → `openapi.json` + `bruno/` | Yes: the `BusinessRuleError` component (Task 10) and the Bruno scaffold (Task 12) |
| New business rule → error code in `docs/validation.md` | No new rules. Task 10 implements the fifteen already catalogued |
| New Artisan command → `docs/scheduled-jobs.md` | No. `migrate:all` is a deploy command, documented in `deployment.md`. See the resolved holes spec |
| New personal-data field | None |
| New audit event | None |
| New consent type | None |
| New durable convention | Record with `record-rule` if one emerges, likely around the domain-routing pattern |

## Out of scope

Audit **tables** and the activitylog config (Phase 6, along with the erasure user's column-scoped grant). Design-system **components** (Phase 3; this phase ships tokens only). Any auth, guard or user model work (Phase 4). Tier limits (first appearance Phase 4, wired Phase 13). Real API endpoints, and therefore real Bruno requests beyond `/up` (Phase 4 onward). The artifact upload and deploy hooks in CI (Phase 2, when staging exists to receive them). Meilisearch (Phase 10) and Reverb (Phase 7).

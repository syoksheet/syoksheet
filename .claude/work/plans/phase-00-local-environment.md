# Phase 0: Local Environment

**Goal:** bring DDEV to the shape `docs/infrastructure/local-development.md` specifies, before any code runs against it.

**Specs:** `docs/infrastructure/local-development.md` (authoritative for this phase), `docs/architecture.md` (connections, Redis split, queue priorities).

**Audit events:** none. This phase writes no user or org data.

**Working agreement:** learning mode. **The user writes every change in this phase.** Claude names the doc page and the exact keys, warns about the traps recorded below, and reviews each diff. Escalation level 1 is the default: ask for the source, not the answer.

## Constraints

Values copied verbatim from the specs. Do not paraphrase these into something that looks equivalent.

| Setting | Value | Source |
|---|---|---|
| PostgreSQL, both instances | `18` | local-development.md § Services |
| Primary connection | `pgsql`, host `db`, database `syoksheet`, `db`/`db` | local-development.md § Databases |
| Audit connection | `log`, host `postgres-audit`, database `syoksheet_audit`, `db`/`db` | local-development.md § Databases |
| `maxmemory-policy` | `volatile-lru` | local-development.md § Redis |
| `appendonly` | `yes` | local-development.md § Redis |
| `appendfsync` | `everysec` | local-development.md § Redis |
| Redis DB split | cache 1, session 2, queue 3 | implementation-order.md Phase 1, wired in Phase 1 |
| Buggregator SMTP | `MAIL_HOST=buggregator`, `MAIL_PORT=1025` | local-development.md § Buggregator |
| Buggregator URL | `https://buggregator.syoksheet.ddev.site` | local-development.md § Buggregator |
| Node | 24 | already correct in `.ddev/config.yaml` |

## Measured delta

Taken from the running environment on 2026-08-29, not from the docs.

| Item | On disk now | Target | File |
|---|---|---|---|
| Primary DB | `postgres:16` | `18` | `.ddev/config.yaml` |
| Audit DB | `image: postgres:16` | `postgres:18` | `.ddev/docker-compose.postgres-audit.yaml` |
| Eviction policy | `allkeys-lfu` | `volatile-lru` | `.ddev/redis/redis.conf` |
| Persistence | `appendonly` commented out | `appendonly yes`, `appendfsync everysec` | `.ddev/redis/redis.conf` |
| MinIO | Absent | `ddev/ddev-minio` add-on | new |
| Buggregator | Absent, Mailpit on `web:8025` | Own compose file | `.ddev/docker-compose.buggregator.yaml` (new) |
| HTTPS | DDEV default certificate | mkcert-signed | host-level |
| Horizon | Not installed | `require` | `composer.json` |
| Sentry | Not installed | `sentry/sentry-laravel` + `@sentry/svelte` | `composer.json`, `package.json` |

## Tasks

### Task 1: PostgreSQL 16 to 18, both instances

**Files:** modify `.ddev/config.yaml` (`database.version`), modify `.ddev/docker-compose.postgres-audit.yaml` (`image:`).

**Behaviour:** both Postgres instances run 18. `ddev describe` reports `postgres:18` on `db`, and `postgres-audit` starts healthy.

**Who writes:** user. Two-key YAML change, but the volume handling around it is the actual lesson and it recurs at every future version bump.

**Read first:** ddev.readthedocs.io, search "postgres version change" and "ddev delete".

> [!WARNING]
> DDEV refuses to start against a data volume built by a different major version, and the failure message is not always obvious about why. Both volumes must be destroyed deliberately: DDEV owns the primary one (`ddev delete -O`, the `-O` skips the snapshot), and it does **not** own the audit one, which was declared in our own compose file and must be removed with `docker volume rm`. There is no local data to preserve, so this costs nothing, but it will not happen by itself.

- [x] Bump both versions
- [x] Remove both volumes deliberately
- [x] `ddev start`, then `ddev describe` shows 18 on both

### Task 2: `redis.conf` to production parity

**Files:** modify `.ddev/redis/redis.conf`.

**Behaviour:** `maxmemory-policy volatile-lru`, `appendonly yes`, `appendfsync everysec`. Redis reports these values at runtime, not just in the file.

**Who writes:** user.

**Read first:** `docs/infrastructure/local-development.md` § Redis. It carries the reasoning, not just the values.

> [!WARNING]
> Line 2 of that file is `# #ddev-generated`. While that marker is present the add-on owns the file and overwrites it on the next add-on operation, silently reverting the edit. Remove the marker line as part of this change.

Worth internalising before editing: eviction policy is per **instance**, not per database, so the cache/session/queue split across DBs 1/2/3 does not on its own stop Redis from evicting a queued job under memory pressure. `volatile-lru` does, because it only ever evicts keys that carry an expiry, and queued jobs do not. This is also why `Cache::forever()` is banned project-wide.

- [x] Edit the three directives, remove the `#ddev-generated` marker
- [x] `ddev restart`
- [x] Confirm at runtime, not in the file: `ddev php artisan tinker --execute 'dd(Redis::connection()->config("GET", "maxmemory-policy"), Redis::connection()->config("GET", "appendonly"));'`

### Task 3: MinIO

**Files:** created by the add-on.

**Behaviour:** an S3-compatible endpoint reachable from the web container, with a bucket for the app.

**Who writes:** user runs `ddev add-on get ddev/ddev-minio`. Official add-on, nothing to decide.

**Read first:** the add-on's own README, reachable from `ddev add-on list --all`.

Why this exists rather than the `local` disk: `Storage::temporaryUrl()` is unsupported on `local`, and signed URLs are required by PDF export (24-hour expiry) and GDPR data export (48-hour expiry). MinIO proves the code path. It does not replicate R2's quirks (no ACLs, `AWS_DEFAULT_REGION=auto`, its own CORS behaviour), which is why staging points at a real bucket in Phase 2.

- [x] Add-on installed, `ddev restart`
- [x] Console loads, bucket exists

### Task 4: Buggregator via our own compose file

**Files:** create `.ddev/docker-compose.buggregator.yaml`. Possibly modify `.ddev/config.yaml` (`additional_hostnames`).

**Behaviour:** one container replacing several tools, reached at `https://buggregator.syoksheet.ddev.site`. It receives outgoing mail over SMTP, `dump()` output, and Sentry-protocol events.

**Who writes:** user. The third-party add-on was rejected deliberately, and this same hand-written pattern is what Meilisearch needs in Phase 10, so writing it once here pays twice.

**Read first:**
1. `.ddev/docker-compose.postgres-audit.yaml` in this repo. It is the reference implementation: `container_name`, the two `com.ddev.*` labels, `restart: "no"`, a healthcheck. Read it completely before writing, and match it rather than inventing a second style.
2. ddev.readthedocs.io, "Additional services" / "custom compose files", specifically how a service with a web UI gets a hostname. The `VIRTUAL_HOST` plus `HTTP_EXPOSE`/`HTTPS_EXPOSE` environment variables are the mechanism, and the docs spell out the port-mapping syntax. The spec asks for a `buggregator.` subdomain, which is the part worth getting right rather than settling for a port on the main hostname.
3. Buggregator's own docs for the image name and its ports. Confirm them rather than trusting a remembered list: the web UI and the Sentry endpoint share the HTTP port, while SMTP and the var-dump socket are separate.

- [x] Compose file written, matching the postgres-audit pattern
- [x] `ddev restart`, UI loads at the intended hostname
- [x] Left until Task 8: mail actually landing in it

### Task 5: mkcert

**Files:** none. Host-level, operator only.

**Behaviour:** all four hostnames answer over HTTPS with a locally trusted certificate.

**Who writes:** user, at the keyboard. `brew install mkcert nss && mkcert -install && ddev restart`.

Why it matters enough to be a phase task: cookie scoping across subdomains behaves differently over plain HTTP, and the app serves four of them. Without this, a cookie bug would first appear in Phase 4 auth, would not reproduce anywhere else, and would cost far more to diagnose than this costs to set up.

- [x] `syoksheet.ddev.site`, `api.`, `app.` and `admin.syoksheet.ddev.site` all answer over HTTPS, certificate trusted

### Task 6: Horizon into `require`

**Files:** modify `composer.json`, `composer.lock`; adds `config/horizon.php`.

**Behaviour:** Horizon installed as a production dependency and its config published. Supervisor configuration itself is Phase 1 work; this task only puts it in place.

**Who writes:** user.

**Read first:** Boost `search-docs` for Horizon configuration and supervisor options. Boost is version-matched to what is installed, so it beats the website here.

`require`, not `require-dev`, deliberately: the `audit` queue's retry-forever behaviour is a Horizon supervisor setting, and every deploy runs `composer install --no-dev`. Hand-typed `queue:work` flags would exercise something different from what ships.

- [x] Installed under `require`
- [x] Config published
- [x] `ddev php artisan horizon` starts without error

### Task 7: Sentry SDK

**Files:** modify `composer.json`, `composer.lock`, `package.json`; adds `config/sentry.php`.

**Behaviour:** the PHP SDK installed and configured, DSN pointing at Buggregator locally. Same SDK and same code in staging and production, only the DSN differs.

**Who writes:** user.

**Read first:** Boost `search-docs` for the Sentry Laravel package. For the browser package, Context7 with `/getsentry/sentry-javascript` passed directly.

Scope boundary: install and configure the PHP side here, and add `@sentry/svelte` to `package.json`. Wiring the browser SDK into the app entry point belongs with the Inertia bootstrap in Phase 1, because no entry point exists yet.

The `build-step` observability review applies from the first exception type, so two config values are a deliberate decision now rather than a default inherited silently:

- `ignore_exceptions`: expected-and-handled conditions are not errors. Validation failures, 404s, auth challenges, CSRF mismatches, rate limits and model-not-found all belong here.
- `traces_sample_rate`: locally this can be 1.0; the per-route `traces_sampler` closure that staging and production need arrives with real routes.

- [x] Both packages installed, `--save-exact` respected on the npm side
- [x] `config/sentry.php` published, both values set consciously
- [x] A deliberately thrown exception appears in Buggregator

### Task 8: `.env` and `.env.example`

**Files:** modify `.env` and `.env.example`.

**Behaviour:** mail goes to Buggregator, Sentry reports to Buggregator, the S3 disk points at MinIO, and the Redis database split is declared.

**Who writes:** user, exclusively. Claude never touches `.env` or `.env.example`; that is a standing project rule, not a learning-mode choice.

Keys to set: `MAIL_MAILER=smtp`, `MAIL_HOST=buggregator`, `MAIL_PORT=1025`, `SENTRY_LARAVEL_DSN`, the MinIO endpoint plus key, secret and bucket, and the Redis database numbers (cache 1, session 2, queue 3). `.env.example` gets the same keys with placeholder values and no secrets.

Never use Resend locally. Beyond the 3,000/month quota with its 100/day cap, development traffic genuinely delivers, and bounces from fixture data damage the production sending domain's reputation.

- [x] Both files updated, `.env.example` free of real credentials
- [x] A test mail lands in Buggregator and not in Mailpit

## Verification

Contract tests arrive in Phase 1. Phase 0 is proved by observation, and per the verification gate every claim below needs fresh output in this session, read in full including the exit code.

| Claim | Command or check |
|---|---|
| Both databases on 18 and healthy | `ddev describe` |
| Four hostnames answer over trusted HTTPS | Browser or `curl` against `syoksheet.ddev.site`, `api.`, `app.` and `admin.syoksheet.ddev.site` |
| Both connections resolve | `ddev php artisan tinker --execute 'DB::connection("pgsql")->getPdo(); DB::connection("log")->getPdo();'` |
| Redis matches the spec at runtime | `ddev php artisan tinker --execute 'dd(Redis::connection()->config("GET", "maxmemory-policy"), Redis::connection()->config("GET", "appendonly"));'` |
| Buggregator receives mail | Test mail sent, visible in the UI, absent from Mailpit |
| MinIO usable | Console loads, bucket exists |
| Static analysis clean | `ddev php vendor/bin/phpstan analyse`, exit 0 |
| Formatting clean | `ddev php vendor/bin/pint --format agent <changed paths>` |
| Tests green | `ddev php artisan test --compact` |

The Larastan run carries extra weight here: it is the gate that went unrun in the previous session because Docker was down. Level 9 on a near-bare skeleton should be clean. If it is not, that is a finding to resolve in this phase rather than carry into Phase 1.

The `log` connection does not exist in `config/database.php` yet, so its `getPdo()` check only becomes meaningful once Phase 1 defines it. Phase 0 proves the container answers; `ddev exec psql -h postgres-audit -U db syoksheet_audit` is the check that works today.

## Artifacts

From the `build-step` sync checklist, this phase touches none of the API artifacts: no routes, no error codes, no commands, no personal-data fields, no audit events, no consent types.

`docs/infrastructure/local-development.md` is already correct and is what this phase implements. If reality ends up diverging from it, the doc is the spec and the divergence is a finding, not a doc update, unless the user decides otherwise.

If a durable convention emerges (for instance, the shape every hand-written `.ddev/docker-compose.*.yaml` in this repo follows), record it with the Boost `record-rule` tool, glob `.ddev/**`.

## Out of scope

Phase 0 provides the containers. **Phase 1** makes the application use them: the `log` connection definition in `config/database.php`, the audit migrations path and the audit database's two-user grants as code, the Redis queue priorities in `config/queue.php`, the `Route::domain()` skeleton, the Inertia bootstrap, design tokens as SCSS, CI, and the `AiService` and `bruno/` scaffolds.

Also out of scope: Meilisearch (Phase 10) and Reverb (Phase 7), both noted as "not yet local" in the spec.

## Noticed, deferred

The `php: ^8.3` versus 8.4 mismatch is resolved: commit `9d23847` moved `composer.json` to `php ^8.4`, matching `phpstan.neon.dist` and the docs.

Four items are knowingly carried into Phase 1, each surfaced by the Phase 0 spec review:

| Item | Why it waits | Where it lands |
|---|---|---|
| `config/horizon.php` has no `staging` environment | Horizon aborts at boot on any environment absent from `environments`, and staging is provisioned in Phase 2 | Add `staging` when Phase 1 writes the real supervisors |
| Supervisors are the published stub: one `supervisor-1`, `tries => 1` | Queue priorities live in `config/queue.php`, already Phase 1 scope | The `audit` queue's retry-forever setting is the stated justification for Horizon being in `require`, so the two must not stay divorced |
| Sentry `before_send` scrubbing | Nothing personal exists to scrub with no routes and no users | Tracked on the launch checklist in syoksheet-docs, infrastructure/operations.md |
| Per-surface host configuration for out-of-request links | Three surfaces originate links from queued jobs and mail (`app.`, `admin.`, apex), and `APP_URL` is a single fallback that can only be right for one | Phase 1, alongside the `Route::domain()` groups: one config source for both routing and absolute-URL building, so no notification relies on the ambient root. Rule documented in `docs/infrastructure/environment-variables.md` |
| The local `syoksheet-public-local` bucket is not anonymously readable | MinIO buckets are private by default and no policy was set | `mc anonymous set download` alongside the `r2_public` disk definition |

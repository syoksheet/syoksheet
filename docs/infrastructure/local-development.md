# Local Development

Every project service runs inside DDEV. Never interact with PHP, PostgreSQL, or Redis directly from the host.

## ⚙️ Project Setup

```bash
ddev config --project-type=laravel --docroot=public --php-version=8.4 \
  --database=postgres:18 \
  --project-name=syoksheet \
  --additional-hostnames=api.syoksheet,app.syoksheet,admin.syoksheet,www.syoksheet \
  --nodejs-version=24
ddev add-on get ddev/ddev-redis
ddev add-on get ddev/ddev-minio
ddev start
```

HTTPS is used locally so cookie behaviour across the four subdomains matches production. One-time host setup:

```bash
brew install mkcert nss && mkcert -install && ddev restart
```

## 📦 Services

| Service | Provides | Notes |
|---------|----------|-------|
| web | PHP 8.4, nginx, Node 24 | Serves all four subdomains |
| db | PostgreSQL 18 | Databases `syoksheet` and `syoksheet_audit` |
| postgres-audit | PostgreSQL 18 | Separate instance for the `log` connection, mirroring production's eventual split |
| redis | Redis 7 | Sessions, cache, queue |
| buggregator | Mail, dumps, logs, HTTP inspection | Replaces Mailpit. Speaks the Sentry protocol |
| minio | S3-compatible object storage | Stands in for Cloudflare R2 |
| xhgui | Profiling | Started on demand with `ddev xhgui` |

Base URL: `https://app.syoksheet.ddev.site`. The additional hostnames give all four surfaces locally, so `Route::domain()` groups behave exactly as in production.

## 🗄️ Databases

Two **databases**, never two schemas. Postgres cannot join or foreign-key across databases, which is what enforces the audit log's "raw IDs, no cross-database FK constraints".

| Connection | Host | Database | Credentials |
|------------|------|----------|-------------|
| `pgsql` (default) | `db` | `syoksheet` | `db` / `db` |
| `log` (audit) | `postgres-audit` | `syoksheet_audit` | `db` / `db` |

```bash
ddev php artisan migrate:all              # runs both connections
ddev exec psql -h postgres-audit -U db syoksheet_audit
```

Audit migrations live in `database/migrations/audit/` with their own history table on the `log` connection.

## 🧠 Redis

Configure `.ddev/redis/redis.conf` to match production exactly:

| Setting | Value | Why |
|---------|-------|-----|
| `maxmemory-policy` | `volatile-lru` | Evicts only keys with an expiry, so queued jobs can never be dropped |
| `appendonly` | `yes` | Survives restarts |
| `appendfsync` | `everysec` | At most one second of loss |

Eviction policy is per-instance, not per-database, so the 0/1/2/3 split does not by itself protect the queue. Never use `Cache::forever()`; always set a TTL.

## 🐛 Buggregator

One container replacing several tools. Reached at `https://buggregator.syoksheet.ddev.site`.

| Use | How |
|-----|-----|
| Outgoing mail | `MAIL_MAILER=smtp`, `MAIL_HOST=buggregator`, `MAIL_PORT=1025` |
| Errors | `SENTRY_LARAVEL_DSN` points here locally, at Sentry in staging and production. Same SDK, same code |
| Dumps | `dump()` lands here instead of corrupting an Inertia response |
| Webhook payloads | An HTTP endpoint to point outbound webhooks at, from Phase 16 |

Never use Resend locally. Beyond the 3,000/month quota with its 100/day cap, development traffic genuinely delivers, and bounces from fixture data damage the production sending domain's reputation.

## 🗃️ Object Storage

MinIO stands in for R2 using the same S3 driver, so only the endpoint and credentials differ. This matters because the `local` disk does **not** support `Storage::temporaryUrl()`, and signed URLs are required by PDF export (24-hour expiry) and GDPR data exports (48-hour expiry).

MinIO proves the code path; it does not replicate R2's quirks (no ACLs, `AWS_DEFAULT_REGION=auto`, its own CORS behaviour). Staging points at a real R2 bucket for that reason.

## ⌨️ Commands

| Task | Command |
|------|---------|
| Artisan | `ddev php artisan ...` |
| Tests | `ddev php artisan test --compact` |
| Filtered tests | `ddev php artisan test --compact --filter=<Name>` |
| Pint | `ddev php vendor/bin/pint <paths>` |
| Larastan | `ddev php vendor/bin/phpstan analyse` |
| Svelte types | `ddev exec npm run check` |
| JS lint | `ddev exec npm run lint` |
| Install JS deps | `ddev exec npm ci` |
| Tinker | `ddev php artisan tinker` |
| PostgreSQL | `ddev psql` |
| Redis | `ddev php artisan tinker --execute 'Redis::ping();'` |
| Logs | `ddev php artisan pail` |
| Queue, day to day | `ddev php artisan queue:listen` |
| Queue, verifying behaviour | `ddev php artisan horizon` |
| Profiling UI | `ddev xhgui` |
| Anything else | `ddev exec ...` |

> [!NOTE]
> `pint --dirty` fails locally because `.git` is not mounted into the container. Pass the changed paths explicitly. `redis-cli` is likewise not installed in the web container.

Horizon is a **production dependency** (`require`, not `require-dev`) so that local and production share one queue configuration. The `audit` queue's retry-forever behaviour is a Horizon supervisor setting, and hand-typed `queue:work` flags would exercise something different from what ships.

Workers are started manually, never as DDEV daemons: a background worker keeps running old code after every edit. `queue:listen` reloads per job, which suits iteration; `horizon` exercises the real supervisor configuration. The scheduler runs no daemon either. Invoke scheduled commands directly, and use `schedule:work` only when testing the schedule itself.

## 🧭 Conveniences

- Laravel Boost (MCP) provides schema inspection, read-only DB queries, and documentation search.
- Telescope is enabled locally for request, query and job debugging. It is local-only: a `require-dev` package, and every deploy runs `composer install --no-dev`.
- Never create or modify `.env` files directly.

## ⏳ Not Yet Local

| Service | Arrives | Note |
|---------|---------|------|
| Meilisearch | Phase 10 | No maintained DDEV add-on. Add a `.ddev/docker-compose.meilisearch.yaml`, the same pattern as `postgres-audit` |
| Reverb | Phase 7 | HTTPS means the browser needs `wss://`, so the port must be exposed with the mkcert certificate and `REVERB_*` plus `VITE_REVERB_*` set to match |

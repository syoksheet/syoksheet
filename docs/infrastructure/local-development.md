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

DDEV always provisions its Postgres instance with a database named `db`, and that name cannot be configured. The application expects `syoksheet`, the same name used in staging and production, so a `post-start` hook in `.ddev/config.yaml` creates it on every start. Nothing is done by hand: a manually created database would not survive `ddev delete`, and a fresh clone would come up broken. The hook guards on `pg_database` because Postgres has no `CREATE DATABASE IF NOT EXISTS`, and needs no `GRANT`, since the `db` role is a superuser and owns what it creates.

## 📦 Services

| Service | Provides | Notes |
|---------|----------|-------|
| web | PHP 8.4, nginx, Node 24 | Serves all four subdomains |
| db | PostgreSQL 18 | Database `syoksheet`, created by the `post-start` hook. DDEV's own `db` database is left unused |
| postgres-audit | PostgreSQL 18 | Separate instance for the `log` connection, mirroring production's eventual split |
| redis | Redis 7 | Sessions, cache, queue |
| buggregator | Mail, dumps, logs, HTTP inspection | Replaces Mailpit. Speaks the Sentry protocol |
| minio | S3-compatible object storage | Stands in for Cloudflare R2. Buckets created by the `post-start` hook. S3 API on 10101, console on 9090 |
| xhgui | Profiling | Started on demand with `ddev xhgui` |
| mailpit | DDEV built-in mail catcher | Unused. Mail goes to Buggregator, but DDEV always provides this and it cannot be removed |

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

> [!WARNING]
> Upgrading the Postgres major version is not a one-line image bump. From 18 onward the image stores data in a version-named directory (`/var/lib/postgresql/18/docker`, where 16 used a flat `/var/lib/postgresql/data`), so `.ddev/docker-compose.postgres-audit.yaml` mounts its volume one level up at `/var/lib/postgresql` and sets no `PGDATA` of its own. Overriding `PGDATA` back to a fixed path appears to work and silently recreates the pre-18 layout, which breaks the next upgrade: version-named directories are what let `pg_upgrade --link` run inside a single mount. The container refuses to start on mismatched data rather than corrupting it, which is a safety feature, not a bug.
>
> `ddev delete` removes only DDEV's own `db` volume. The audit volume is declared in our compose file, so it must be removed separately with `docker volume rm ddev-syoksheet_postgres-audit`.

## 🧠 Redis

Configure `.ddev/redis/redis.conf` to match production exactly:

| Setting | Value | Why |
|---------|-------|-----|
| `maxmemory-policy` | `volatile-lru` | Evicts only keys with an expiry, so queued jobs can never be dropped |
| `appendonly` | `yes` | Survives restarts |
| `appendfsync` | `everysec` | At most one second of loss |

Eviction policy is per-instance, not per-database, so the 0/1/2/3 split does not by itself protect the queue. Never use `Cache::forever()`; always set a TTL.

> [!WARNING]
> The add-on writes this file with a `#ddev-generated` marker on line 2 and overwrites the file on any add-on operation while that marker is present. It has been removed deliberately. Do not restore it, and check it is still absent after running `ddev add-on get ddev/ddev-redis` again.

Verify at runtime rather than by reading the file:

```bash
docker exec ddev-syoksheet-redis redis-cli CONFIG GET maxmemory-policy
```

## 🐛 Buggregator

One container replacing several tools. Reached at `https://buggregator.syoksheet.ddev.site`.

| Use | How |
|-----|-----|
| Outgoing mail | `MAIL_MAILER=smtp`, `MAIL_HOST=buggregator`, `MAIL_PORT=1025` |
| Errors | `SENTRY_LARAVEL_DSN` points here locally, at Sentry in staging and production. Same SDK, same code |
| Dumps | `dump()` lands here instead of corrupting an Inertia response |
| Webhook payloads | An HTTP endpoint to point outbound webhooks at, from Phase 16 |

Declared in `.ddev/docker-compose.buggregator.yaml`, ours rather than a third-party add-on. Image `ghcr.io/buggregator/server:latest`, no volume: captured events are disposable.

| Port | Serves | Reached from |
|------|--------|--------------|
| 8000 | Web UI, Sentry protocol, Ray, XHProf, HTTP dumps | The router, as `https://buggregator.syoksheet.ddev.site` |
| 1025 | SMTP | The web container only, as `buggregator:1025` |
| 9912 | Symfony VarDumper socket | Published to `127.0.0.1`, for the PhpStorm plugin |
| 9913 | Monolog `SocketHandler` | Published to `127.0.0.1`, for the PhpStorm plugin |

The two socket ports are published to the host because the IDE plugin connects over raw TCP from outside Docker rather than through the router. SMTP is deliberately not published: nothing outside the Docker network sends mail.

> [!WARNING]
> `VIRTUAL_HOST` is written out as `buggregator.${DDEV_SITENAME}.ddev.site`, not as `buggregator.$DDEV_HOSTNAME`. `DDEV_HOSTNAME` expands to a comma-separated list of all five project hostnames, so the DDEV docs' subdomain pattern would attach the prefix to the first entry only and leave four bare hostnames claimed alongside it. The hostname needs no entry in `additional_hostnames`: `VIRTUAL_HOST` alone routes it, and the mkcert certificate already covers it.

Never use Resend locally. Beyond the 3,000/month quota with its 100/day cap, development traffic genuinely delivers, and bounces from fixture data damage the production sending domain's reputation.

## 🗃️ Object Storage

MinIO stands in for R2 using the same S3 driver, so only the endpoint and credentials differ. This matters because the `local` disk does **not** support `Storage::temporaryUrl()`, and signed URLs are required by PDF export (24-hour expiry) and GDPR data exports (48-hour expiry).

MinIO proves the code path; it does not replicate R2's quirks (no ACLs, `AWS_DEFAULT_REGION=auto`, its own CORS behaviour). Staging points at real R2 buckets for that reason.

| Setting | Value |
|---------|-------|
| Console | `https://syoksheet.ddev.site:9090` |
| S3 endpoint, from the web container | `http://minio:10101` |
| S3 endpoint, from the host browser | `https://syoksheet.ddev.site:10101` |
| Access key / secret | `ddevminio` / `ddevminio` |
| Public bucket | `syoksheet-public-local` |
| Private bucket | `syoksheet-private-local` |

Local buckets carry `-local` for the same reason production carries `-production`: the name is always `syoksheet-<purpose>-<environment>` with no default case, so a misconfigured environment fails on a bucket that does not exist rather than quietly reaching one that does. The `backups`, `audit-archive` and `artifacts` buckets have no local counterpart, since nothing local runs those jobs.

> [!WARNING]
> The S3 API listens on **10101**, not the conventional 9000. The add-on's compose file passes `--address :10101` and reserves 9090 for the console. `ddev describe` still lists `minio:9000` because the image exposes it, but nothing serves there, so an endpoint left at 9000 hangs instead of erroring.

Both buckets are created by a `post-start` hook in `.ddev/config.yaml`, never by hand in the console. The `minio` volume is declared in the add-on's compose file, so a hand-made bucket does not survive `ddev delete`, and a fresh clone of the repo would come up with storage that fails on first write. Same reasoning as the `syoksheet` database hook above.

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
| Redis | `docker exec ddev-syoksheet-redis redis-cli PING` |
| Logs | `ddev php artisan pail` |
| Queue, day to day | `ddev php artisan queue:listen` |
| Queue, verifying behaviour | `ddev php artisan horizon` |
| Profiling UI | `ddev xhgui` |
| Anything else | `ddev exec ...` |

> [!NOTE]
> `pint --dirty` fails locally because `.git` is not mounted into the container. Pass the changed paths explicitly. `redis-cli` is not installed in the web container, so Redis is inspected with `docker exec` against the `redis` container instead. In tinker the bare `Redis` name resolves to the phpredis extension's global class, not the facade: use the fully qualified `Illuminate\Support\Facades\Redis` or the container will report an undefined method.

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

# Local Development

All project services run inside DDEV — never interact with PHP, PostgreSQL, or Redis directly from the host.

## ⚙️ Project Setup

```bash
ddev config --project-type=laravel --docroot=public --php-version=8.4 \
  --database=postgres:16 \
  --project-name=syoksheet \
  --additional-hostnames=api.syoksheet,app.syoksheet,admin.syoksheet,www.syoksheet \
  --nodejs-version=22
ddev add-on get ddev/ddev-redis
ddev start
```

- The additional hostnames give all four subdomain surfaces locally: `api.` / `app.` / `admin.` / `www.syoksheet.ddev.site` — `Route::domain()` groups work in development exactly as in production.
- The audit database runs as a **separate PostgreSQL instance**, mirroring production: `.ddev/docker-compose.postgres-audit.yaml` defines the `postgres-audit` service (postgres:16, its own volume). The `log` connection's local values: host `postgres-audit`, port `5432`, database `syoksheet_audit`, user/password `db`/`db`. Access it with `ddev exec psql -h postgres-audit -U db syoksheet_audit`.
- Vite dev server (`ddev npm run dev`): expose container port 5173 via `web_extra_exposed_ports` in `.ddev/config.yaml`.
- Meilisearch (taxonomy search): `ddev add-on get ddev/ddev-meilisearch` — needed from the taxonomy phase onward.

## 📦 Services

DDEV provides PHP 8.4, PostgreSQL 16 (app + audit databases), Redis, Node 22 (Vite), and Mailpit (catches all outgoing mail — `ddev launch -m`). Base URL: `https://app.syoksheet.ddev.site`.

## ⌨️ Commands

| Task | Command |
|------|---------|
| Artisan | `ddev php artisan ...` |
| Tests | `ddev php artisan test --compact` |
| Pint | `ddev php vendor/bin/pint --dirty` |
| Larastan | `ddev php vendor/bin/phpstan analyse` |
| Tinker | `ddev php artisan tinker` |
| PostgreSQL | `ddev psql` |
| Redis | `ddev exec redis-cli` |
| Anything else in the container | `ddev exec ...` |

## 🧭 Conveniences

- Laravel Boost (MCP) is available for schema inspection, read-only DB queries, and docs search.
- Telescope is enabled locally for request/query/job debugging.
- Never create or modify `.env` files directly — DDEV manages the environment.

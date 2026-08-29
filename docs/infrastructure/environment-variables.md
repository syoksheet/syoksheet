# Environment Variables

All environment variables for the Laravel API, set in Forge per environment. Frontend env vars live in their own repos.

## ⚙️ Application

| Variable | Example | Notes |
|----------|---------|-------|
| APP_NAME | syoksheet | Standard |
| APP_ENV | production | `staging` for staging |
| APP_KEY | base64:xxx… | `php artisan key:generate` |
| APP_DEBUG | false | `true` for staging |
| APP_URL | https://app.syoksheet.com | Primary origin; the app also serves api./admin./www. via domain routing (staging: staging.* equivalents) |

## 🗄️ Databases

Primary (`DB_*`) and audit (`LOG_DB_*`), two separate Forge managed PostgreSQL clusters, reached over the Forge private network with public access disabled.

| Variable | Example | Notes |
|----------|---------|-------|
| DB_CONNECTION | pgsql | Standard |
| DB_HOST / DB_PORT | [cluster private host] / [cluster port] | From the cluster's credentials panel |
| DB_DATABASE / DB_USERNAME / DB_PASSWORD | syoksheet / syoksheet / [secure] | Per environment |
| DB_SSLMODE | require | Managed DB requirement |
| LOG_DB_HOST / LOG_DB_PORT | [audit cluster private host] / [cluster port] | Separate cluster |
| LOG_DB_DATABASE / LOG_DB_USERNAME / LOG_DB_PASSWORD | syoksheet_audit / … / [secure] | `log` connection |
| LOG_DB_SSLMODE | require | Standard |

## 🧰 Redis

| Variable | Example | Notes |
|----------|---------|-------|
| REDIS_HOST / REDIS_PORT / REDIS_PASSWORD | `127.0.0.1` / 6379 / [secure] | Redis runs on the app server, not a managed cluster. Locally: `redis` on 6379, no password |
| REDIS_DB / REDIS_CACHE_DB / REDIS_SESSION_DB / REDIS_QUEUE_DB | 0 / 1 / 2 / 3 | Standard |
| CACHE_STORE / SESSION_DRIVER / QUEUE_CONNECTION | redis | Standard |
| SESSION_CONNECTION | session | Standard |

## 🔐 Sessions

| Variable | Example | Notes |
|----------|---------|-------|
| SESSION_DOMAIN | .syoksheet.com | Leading dot, both environments: sessions work across the app's subdomains; guard separation (user vs admin) is enforced by guards, not cookies |

No `SANCTUM_STATEFUL_DOMAINS` or CORS origin list. The UIs are same-origin Inertia pages, and `api.*` is bearer-token/webhook traffic. Public `api.*` GET endpoints may enable permissive CORS if third parties embed them.

## 📦 Cloudflare R2

| Variable | Example |
|----------|---------|
| FILESYSTEM_DISK | r2_private |
| R2_ACCESS_KEY_ID / R2_SECRET_ACCESS_KEY | [key] / [secret] |
| R2_PUBLIC_BUCKET | syoksheet-public-production (staging: syoksheet-public-staging) |
| R2_PRIVATE_BUCKET | syoksheet-private-production (staging: syoksheet-private-staging) |
| R2_ENDPOINT | https://[account].r2.cloudflarestorage.com |
| R2_PUBLIC_URL | https://cdn.syoksheet.com (staging: https://staging.cdn.syoksheet.com) |

Two disks, `r2_public` and `r2_private`, over one credential pair. `FILESYSTEM_DISK` is `r2_private` so an unqualified `Storage::put()` cannot accidentally publish; avatars, org logos and verification marks are written with an explicit `Storage::disk('r2_public')`.

The backup, audit-archive and build-artifact buckets deliberately have no variables here. None is reached through Laravel's filesystem layer: the first two belong to shell commands run by the scheduler, the third to CI and the deploy script, and each carries its own credential scoped to its own bucket.

## 📧 Resend

| Variable | Example |
|----------|---------|
| MAIL_MAILER | resend |
| RESEND_API_KEY | re_xxx… |
| MAIL_FROM_ADDRESS / MAIL_FROM_NAME | noreply@syoksheet.com / syoksheet |

## 🔑 Google OAuth

| Variable | Notes |
|----------|-------|
| GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET | From the Google Cloud OAuth 2.0 client. Separate credentials per environment |
| GOOGLE_REDIRECT_URI | https://api.syoksheet.com/auth/google/callback (staging URI on staging) |

## 💳 DodoPayments

| Variable |
|----------|
| DODO_API_KEY |
| DODO_WEBHOOK_SECRET |
| DODO_PRO_PRODUCT_ID |
| DODO_BUSINESS_PRODUCT_ID |

## 🔎 Search (Meilisearch)

| Variable | Example |
|----------|---------|
| SCOUT_DRIVER | meilisearch |
| MEILISEARCH_HOST | http://127.0.0.1:7700 |
| MEILISEARCH_KEY | [master key] |

## 🤖 AI

| Variable | Example | Notes |
|----------|---------|-------|
| AI_PROVIDER | anthropic | `AiService` driver |
| ANTHROPIC_API_KEY | sk-ant-… | Empty locally. AI jobs no-op |
| AI_MODEL_JUDGMENT | claude-sonnet-5 | Similarity judgment |
| AI_MODEL_BULK | claude-haiku-4-5 | Bulk scoring |

## 📈 Observability

| Variable | Example | Notes |
|----------|---------|-------|
| SENTRY_LARAVEL_DSN | https://…@…ingest.sentry.io/… | Locally this points at **Buggregator**, which speaks the same protocol |
| SENTRY_ENVIRONMENT | production / staging / local | One Sentry project, separated by environment |
| SENTRY_RELEASE | [commit SHA] | Set at deploy; matches the build artifact key and the uploaded source maps |
| SENTRY_TRACES_SAMPLE_RATE | 0.1 staging | Production tunes per route via `traces_sampler` |
| SENTRY_SEND_DEFAULT_PII | false | Never `true`. Scrubbing happens in `before_send` |
| VITE_SENTRY_DSN | (same DSN) | Browser SDK in the Svelte apps |
| LOG_CHANNEL | stack | Local: daily only |

# Environment Variables

All environment variables for the Laravel API, set in Forge per environment. Frontend env vars live in their own repos.

## ⚙️ Application

| Variable | Example | Notes |
|----------|---------|-------|
| APP_NAME | syoksheet | |
| APP_ENV | production | `staging` for staging |
| APP_KEY | base64:xxx… | `php artisan key:generate` |
| APP_DEBUG | false | `true` for staging |
| APP_URL | https://app.syoksheet.com | Primary origin; the app also serves api./admin./www. via domain routing (staging: staging.* equivalents) |

## 🗄️ Databases

Primary (`DB_*`) and audit (`LOG_DB_*`) — both DO Managed PostgreSQL over VPC private hostnames, public endpoints disabled.

| Variable | Example | Notes |
|----------|---------|-------|
| DB_CONNECTION | pgsql | |
| DB_HOST / DB_PORT | [private hostname] / 25060 | |
| DB_DATABASE / DB_USERNAME / DB_PASSWORD | syoksheet / syoksheet / [secure] | Per environment |
| DB_SSLMODE | require | Managed DB requirement |
| LOG_DB_HOST / LOG_DB_PORT | [audit instance private hostname] / 25060 | Separate instance |
| LOG_DB_DATABASE / LOG_DB_USERNAME / LOG_DB_PASSWORD | syoksheet_audit / … / [secure] | `log` connection |
| LOG_DB_SSLMODE | require | |

## 🧰 Redis

| Variable | Example | Notes |
|----------|---------|-------|
| REDIS_HOST / REDIS_PORT / REDIS_PASSWORD | [private hostname] / 25061 / [secure] | |
| REDIS_DB / REDIS_CACHE_DB / REDIS_SESSION_DB / REDIS_QUEUE_DB | 0 / 1 / 2 / 3 | |
| CACHE_STORE / SESSION_DRIVER / QUEUE_CONNECTION | redis | |
| SESSION_CONNECTION | session | |

## 🔐 Sessions

| Variable | Example | Notes |
|----------|---------|-------|
| SESSION_DOMAIN | .syoksheet.com | Leading dot, both environments — sessions work across the app's subdomains; guard separation (user vs admin) is enforced by guards, not cookies |

No `SANCTUM_STATEFUL_DOMAINS` or CORS origin list — the UIs are same-origin Inertia pages, and `api.*` is bearer-token/webhook traffic. Public `api.*` GET endpoints may enable permissive CORS if third parties embed them.

## 📦 Cloudflare R2

| Variable | Example |
|----------|---------|
| FILESYSTEM_DISK | r2 |
| R2_ACCESS_KEY_ID / R2_SECRET_ACCESS_KEY | [key] / [secret] |
| R2_BUCKET | syoksheet-uploads (staging: syoksheet-uploads-staging) |
| R2_ENDPOINT | https://[account].r2.cloudflarestorage.com |
| R2_URL | https://uploads.syoksheet.com |

## 📧 Resend

| Variable | Example |
|----------|---------|
| MAIL_MAILER | resend |
| RESEND_API_KEY | re_xxx… |
| MAIL_FROM_ADDRESS / MAIL_FROM_NAME | noreply@syoksheet.com / syoksheet |

## 🔑 Google OAuth

| Variable | Notes |
|----------|-------|
| GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET | |
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
| ANTHROPIC_API_KEY | sk-ant-… | Empty locally — AI jobs no-op |
| AI_MODEL_JUDGMENT | claude-sonnet-5 | Similarity judgment |
| AI_MODEL_BULK | claude-haiku-4-5 | Bulk scoring |

## 📈 Observability

| Variable | Example | Notes |
|----------|---------|-------|
| OTEL_EXPORTER_OTLP_ENDPOINT | http://[observability private IP]:4318 | VPC private address — traces, logs, metrics to SigNoz. Empty locally |
| OTEL_SERVICE_NAME | syoksheet-api | |
| SENTRY_LARAVEL_DSN | https://…@errors.syoksheet.com/1 | GlitchTip DSN. Empty locally |
| LOG_CHANNEL | stack | Local: daily only; staging/prod include the OTLP handler |

# Environment Variables

All environment variables for the Laravel API, set in Forge per environment. Frontend env vars live in their own repos.

## ⚙️ Application

| Variable | Example | Notes |
|----------|---------|-------|
| APP_NAME | syoksheet | Standard |
| APP_ENV | production | `staging` for staging |
| APP_KEY | base64:xxx… | `php artisan key:generate` |
| APP_DEBUG | false | `true` for staging |
| APP_URL | https://syoksheet.com | Fallback root for URL generation outside a request, never a per-domain setting. Staging: https://staging.syoksheet.com. Local: https://syoksheet.ddev.site |

### APP_URL is a fallback, not an origin

Laravel reads `APP_URL` only when there is no request to take a host from: queued jobs, console commands, mail. Inside a request, `url()` and `route()` follow the actual `Host` header and `Route::domain()` matches on it, so `APP_URL` never decides which domain works.

Three domains originate out-of-request links, so no single fallback can be correct for all of them:

| Domain | Links it originates |
|---------|---------------------|
| `app.` | Email verification, password reset, account-deletion confirmation and cancellation |
| `admin.` | Admin authentication mail, account provisioning |
| apex | Collaborator and verifier invitations |

The rule is therefore that **no notification relies on the fallback**: each builds its URL against its own domain's configured host. `APP_URL` points at the apex so that anything which does fall through fails loudly and identically for every audience, rather than working for users and silently breaking for admins, which is the failure that survives testing.

Signed URLs make this strict rather than cosmetic. The signature covers the full URL and `hasValidSignature()` re-derives it from the incoming request, so a host mismatch is rejected outright, not merely served as a 404.

> [!NOTE]
> The per-domain host configuration that this rule depends on arrives with the `Route::domain()` groups in Phase 1. Until then no routes exist and the value has no observable effect.

## 🗄️ Databases

Primary (`DB_*`) and audit (`AUDIT_DB_*`): two databases on one Forge managed PostgreSQL cluster per environment, reached over the Forge private network with public access disabled. The two connections share a host and differ in database name and credentials. Production and staging differ only in cluster size and credentials.

| Variable | Example | Notes |
|----------|---------|-------|
| DB_CONNECTION | pgsql | Standard |
| DB_HOST / DB_PORT | [cluster private host] / [cluster port] | From the cluster's credentials panel |
| DB_DATABASE / DB_USERNAME / DB_PASSWORD | syoksheet_primary / syoksheet / [secure] | Per environment |
| DB_SSLMODE | require | Managed DB requirement |
| AUDIT_DB_HOST / AUDIT_DB_PORT | Same values as `DB_HOST` / `DB_PORT` | Same cluster, different database |
| AUDIT_DB_DATABASE / AUDIT_DB_USERNAME / AUDIT_DB_PASSWORD | syoksheet_audit / … / [secure] | `audit` connection |
| AUDIT_DB_SSLMODE | require | Standard |

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
| SESSION_DOMAIN | null | Host-only, both environments. Only `app.` and `admin.` hold a session and they run separate guards, so neither needs the other's cookie. A leading-dot parent domain would also send it to `api.`, which is bearer-token only, and to the apex, which is public, stateless and cached. Guard separation is enforced by guards, not cookies |

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

No region or path-style variable exists, deliberately. The S3 driver needs both, but neither varies by environment: R2 always wants `region => 'auto'`, and MinIO needs `use_path_style_endpoint => true`, which R2 also accepts. Both belong hardcoded in the disk definitions rather than in `.env`.

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
| AI_PROVIDER | anthropic | Sets `ai.default` for the Laravel AI SDK. The only provider we configure |
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
| SENTRY_SEND_DEFAULT_PII | false | Never `true`. `before_send` scrubbing is required before launch and is not implemented yet |
| VITE_SENTRY_DSN | (same DSN) | Browser SDK in the Svelte apps |
| LOG_CHANNEL | stack | Local: daily only |

## 🖥️ Server-Side Rendering

Only the apex is server-side rendered. `app.` and `admin.` opt out in their middleware, not through configuration.

| Variable | Example | Notes |
|----------|---------|-------|
| INERTIA_SSR_ENABLED | true | Locally the renderer is served by `npm run dev`. With Vite not running, apex pages fall back to client-side rendering and a refused connection is deliberately not reported |
| INERTIA_SSR_URL | http://127.0.0.1:13714 | Loopback only, never reachable off the machine. The port must match `vite.config.ts`, which pins the same value: changing one alone disables SSR silently, with only a Sentry message to show for it |

## 🗂️ Apex Response Cache

`Cache-Control` on apex GET responses, read by Cloudflare. The apex is the only cacheable domain, because it is the only one that shares no user data.

| Variable | Example | Notes |
|----------|---------|-------|
| PUBLIC_CACHE_MAX_AGE | 60 | Seconds a response is fresh |
| PUBLIC_CACHE_STALE_WHILE_REVALIDATE | 300 | Serve stale and refresh in the background, which keeps the renderer off the critical path |
| PUBLIC_CACHE_STALE_IF_ERROR | 86400 | Keep serving the last good copy through an origin or SSR outage |

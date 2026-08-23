# Application Architecture

How the Laravel application is structured: one app serving four subdomains, guards, databases, queues, events, and integrations. Platform-level architecture (droplets, VPC, Cloudflare) lives in syoksheet-docs → infrastructure/architecture.md.

## 🌐 Subdomain Surfaces

One application, routed by `Route::domain()` groups:

| Subdomain | Surface | Rendering |
|-----------|---------|-----------|
| `app.syoksheet.com` | User app | Inertia + Svelte pages, `web` session auth (user guard) |
| `admin.syoksheet.com` | Admin panel | Inertia + Svelte pages, admin session guard |
| `www.syoksheet.com` | Marketing, public walls, jobs directory, policy pages | Server-rendered Blade — SEO-first, no auth |
| `api.syoksheet.com` | **The sold API surface**: Pro user tokens, Jobs Push API, Dodo webhooks, verifier/collaborator/invitation token pages' endpoints | JSON via Sanctum bearer tokens / signed or tokened routes |

## 🔐 Auth & Guards

| Surface | Auth | Notes |
|---------|------|-------|
| `app.*` Inertia routes | Session (`web` guard, user provider) | Same-origin — no CORS, no Sanctum-SPA layer |
| `admin.*` Inertia routes | Session (`admin` guard) | Separate guard + provider; bidirectional isolation as before |
| `api.*` `/v1/*` | `auth:sanctum` → `EnsureUser` | `user:api` bearer tokens (Pro) |
| `api.*` `/admin/v1/*` | `auth:sanctum` → `EnsureAdmin` | `admin:api` bearer tokens (scripts) |
| Auth flows, `/verify/*`, `/collaborate/*`, `/invitations/*`, public endpoints, webhooks | Unauthenticated | Rate limited; token- or signature-scoped where applicable |

User↔Admin isolation is bidirectional and middleware-enforced: the wrong principal type on either surface gets 403. Users resolve through `UserEmailProvider` (`user_emails.type = primary`) — there is no `users.email` column.

> [!NOTE]
> The feature specs under `docs/features/` define each domain's **operations, validation, events, and rules**. Internal UI consumes them as Inertia web routes; the same operations are exposed on `api.*` only where they are part of the sold surface (public endpoints, Push API, user/admin token APIs). `openapi.json` and the Bruno collection document the sold surface.

## 🖥️ Frontend Layer

- Inertia + **Svelte 5** + TypeScript, built by Vite (Laravel-native). The frontend is a self-contained TS project under `resources/ts/` — own `tsconfig` (strict), ESLint, `svelte-check`.
- Components: headless primitives (Bits UI, TanStack Table) styled by the design system tokens — specs and mirror in `design/` at the repo root.
- PHP enums/DTOs generate TS types (`spatie/typescript-transformer`) — no hand-duplicated types.

## 🗄️ Databases

| Connection | Instance | Holds |
|------------|----------|-------|
| `pgsql` (default) | DO Managed PostgreSQL | All application data — 49 tables |
| `log` | Separate DO Managed PostgreSQL | `audit_logs`, `security_incidents`, `security_incident_affected_records` — append-only, forever retention |

Schema conventions and per-domain docs: [database/README.md](database/README.md). Writes to the `log` connection go through `AuditLogJob` only.

## 🧰 Redis

| DB | Use |
|----|-----|
| 0 | Default — general, locks |
| 1 | Cache |
| 2 | Sessions |
| 3 | Queues |

## ⚡ Events, Queues & Jobs

Hybrid event architecture: one domain event → independent queued listeners.

| Queue | Priority | Work |
|-------|----------|------|
| `audit` | Highest — retries forever | `AuditLogJob` → audit DB |
| `notifications` | Medium | `NotificationJob` → `notifications` table + Reverb broadcast |
| `default` | Normal | Everything else (exports, DNS checks, Dodo webhook processing, outbound webhook deliveries, match recomputes, AI jobs, emails) |

Workers run under Forge daemons; Horizon monitors production queues. Scheduled commands: [scheduled-jobs.md](scheduled-jobs.md).

## 🔎 Search

Laravel Scout + Meilisearch (self-hosted on the API droplet) for taxonomy search — see [features/taxonomy/endpoints.md](features/taxonomy/endpoints.md).

## 📦 Storage, Mail, Payments

| Concern | Service | Notes |
|---------|---------|-------|
| Files | Cloudflare R2 (`FILESYSTEM_DISK=r2`) | Avatars, logos, attachments, export ZIPs; signed URLs for downloads |
| Mail | Resend | From-addresses: noreply@, team@, billing@ (support@ is inbound-only) — catalog in syoksheet-docs → features/notifications.md |
| Payments | DodoPayments | Webhook-driven sync — [features/billing/webhooks.md](features/billing/webhooks.md) |
| Realtime | Laravel Reverb | Notification broadcasts + org activity channels ([features/audit/implementation.md](features/audit/implementation.md)) |
| AI | Claude API via `AiService` | Two batch, review-gated uses; no personal data — [ai.md](ai.md) |
| SSO gate | `EnsureOrgSsoSession` | Org-scoped routes when the org enables SSO — [features/auth/sso.md](features/auth/sso.md) |

## 📈 Observability

- OpenTelemetry → SigNoz over the VPC private network (traces, logs, metrics).
- Exceptions → GlitchTip via `sentry/sentry-laravel`.
- Local/staging debugging → Telescope; production queues → Horizon.

## 🧱 Code Conventions

- PHP 8.4, Laravel 13. Pint + Larastan + Pest gate CI.
- PHP enums for every fixed value set (statuses, types, visibility, consent types); stored as varchar.
- Eloquent API Resources for all responses; versioned routes (`/api/v1/`).
- Authorization via policies + `Gate::authorize()` in controllers.

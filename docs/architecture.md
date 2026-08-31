# Application Architecture

How the Laravel application is structured: one app serving four domains, guards, databases, queues, events, and integrations. Platform-level architecture (servers, managed resources, Cloudflare) lives in syoksheet-docs → infrastructure/architecture.md.

## 🌐 Domains

One application, routed by `Route::domain()` groups:

| Host | Serves | Rendering |
|-----------|---------|-----------|
| `app.syoksheet.com` | User app | Inertia + Svelte pages, `web` session auth (user guard) |
| `admin.syoksheet.com` | Admin panel | Inertia + Svelte pages, admin session guard |
| `syoksheet.com` (apex) | Marketing, public walls, jobs directory, policy pages | Inertia + Svelte with server-side rendering. SEO-first, no auth. `www.syoksheet.com` redirects here |
| `api.syoksheet.com` | **The sold API**: Pro user tokens, Jobs Push API, Dodo webhooks, verifier/collaborator/invitation token pages' endpoints | JSON via Sanctum bearer tokens / signed or tokened routes |

Paths differ by host, and the difference is load-bearing. All three HTML hosts are
Inertia: a route returns a page, a write returns a redirect, and there is **no `/api`
prefix**, because there is no internal API to prefix. Only `api.` carries versioned
JSON paths, `/v1/*` and `/admin/v1/*`, and it needs no `/api` prefix either since the
host already says so.

Server-side rendering runs on the apex alone, because it is the only host whose pages
are crawled. `app.` and `admin.` sit behind auth and disable it. The full reasoning,
along with the process isolation, caching and client-side rules that follow from it,
is in `.claude/work/specs/ssr-and-domain-rendering.md`.

## 🔐 Auth & Guards

| Host | Auth | Notes |
|---------|------|-------|
| `app.*` Inertia routes | Session (`web` guard, user provider) | Same-origin, no CORS, no Sanctum-SPA layer |
| `admin.*` Inertia routes | Session (`admin` guard) | Separate guard + provider; bidirectional isolation as before |
| `api.*` `/v1/*` | `auth:sanctum` → `EnsureUser` | `user:api` bearer tokens (Pro) |
| `api.*` `/admin/v1/*` | `auth:sanctum` → `EnsureAdmin` | `admin:api` bearer tokens (scripts) |
| Auth flows, `/verify/*`, `/collaborate/*`, `/invitations/*`, public endpoints, webhooks | Unauthenticated | Rate limited; token- or signature-scoped where applicable |

User↔Admin isolation is bidirectional and middleware-enforced: the wrong principal type on either host gets 403. Users resolve through `UserEmailProvider` (`user_emails.type = primary`): there is no `users.email` column.

> [!NOTE]
> The feature specs under `docs/features/` are **operation catalogues**, not URL specifications. Each defines an operation's validation, events, rules and error codes, and which host serves it. Whether an operation is also sold on `api.*` is decided per operation as it is built, and recorded in `openapi.json` and the Bruno collection, which are the only statement of the external contract.
>
> Tables still carrying `/api/v1/...` paths predate the move to Inertia and are being reshaped phase by phase, as each domain is built.

## 🖥️ Frontend Layer

- Inertia + **Svelte 5** + TypeScript, built by Vite (Laravel-native). The frontend is a self-contained TS project under `resources/ts/`: own `tsconfig` (strict), ESLint, `svelte-check`.
- **One bundle per domain**, not one shared bundle: `app.ts`, `admin.ts` and `public.ts`, each declaring its own page directory under `resources/ts/pages/`. The apex must never ship admin code, and a marketing visitor must not download the product UI.
- **Server-side rendering runs on the apex only**, from a fourth entry, `ssr.ts`. `app.` and `admin.` are authenticated and never crawled, so they disable it. Rendering, isolation, caching and the client-side rules that follow are in `.claude/work/specs/ssr-and-domain-rendering.md`.
- Components: headless primitives (Bits UI) styled by the design system tokens. Specs and the mirror live in `design/` at the repo root. The data table is built in-house (no TanStack Table).
- PHP enums/DTOs generate TS types (`spatie/typescript-transformer`), no hand-duplicated types.

## 🗄️ Databases

| Connection | Instance | Holds |
|------------|----------|-------|
| `pgsql` (default) | Database `syoksheet_primary` on the environment's managed PostgreSQL cluster | All application data: 61 tables, listed in [database/README.md](database/README.md) |
| `audit` | Database `syoksheet_audit` on the **same** cluster. A separate database, never a schema: Postgres cannot join or foreign-key across databases, which is what enforces the audit log's raw-ID rule | `audit_logs`, `security_incidents`, `security_incident_affected_records`: append-only, forever retention |

Schema conventions and per-domain docs: [database/README.md](database/README.md). Writes to the `audit` connection go through `AuditLogJob` only.

## 🧰 Redis

| DB | Use |
|----|-----|
| 0 | Default: general, locks |
| 1 | Cache |
| 2 | Sessions |
| 3 | Queues |

## ⚡ Events, Queues & Jobs

Hybrid event architecture: one domain event → independent queued listeners.

| Queue | Priority | Work |
|-------|----------|------|
| `audit` | Highest: retries forever | `AuditLogJob` → audit DB |
| `notifications` | Medium | `NotificationJob` → `notifications` table + Reverb broadcast |
| `default` | Normal | Everything else (exports, DNS checks, Dodo webhook processing, outbound webhook deliveries, match recomputes, AI jobs, emails) |

Workers run under Forge daemons; Horizon monitors production queues. Scheduled commands: [scheduled-jobs.md](scheduled-jobs.md).

## 🔎 Search

Laravel Scout + Meilisearch (a process on the app VPS) for taxonomy search. See [features/taxonomy/endpoints.md](features/taxonomy/endpoints.md).

## 📦 Storage, Mail, Payments

| Concern | Service | Notes |
|---------|---------|-------|
| Files | Cloudflare R2, two disks (`r2_public`, `r2_private`), default `FILESYSTEM_DISK=r2_private` | Avatars, logos and verification marks in `syoksheet-public-{env}`; attachments, PDF exports and data-export ZIPs in `syoksheet-private-{env}`, reached by signed URL |
| Mail | Resend | From-addresses: noreply@, team@, billing@ (support@ is inbound-only): catalog in syoksheet-docs → features/notifications.md |
| Payments | DodoPayments | Webhook-driven sync: [features/billing/webhooks.md](features/billing/webhooks.md) |
| Realtime | Laravel Reverb | Notification broadcasts + org activity channels ([features/audit/implementation.md](features/audit/implementation.md)) |
| AI | Claude API via `laravel/ai` | Two batch, review-gated uses; no personal data: [ai.md](ai.md) |
| SSO gate | `EnsureOrgSsoSession` | Org-scoped routes when the org enables SSO: [features/auth/sso.md](features/auth/sso.md) |

## 📈 Observability

- Sentry for errors, tracing and logs: backend (`sentry/sentry-laravel`) and browser (Sentry's JS SDK in the Svelte apps). One platform, one issue stream.
- **Buggregator speaks the Sentry protocol**, so the same SDK and DSN configuration points at Buggregator locally and Sentry in staging and production. Identical code, three destinations.
- Source maps are uploaded to Sentry from CI at build time and excluded from the deployed artifact. Without them a Svelte stack trace is unreadable; served publicly they expose source.
- Releases are keyed to commit SHA, matching the build artifact key.
- Local debugging → Telescope (local only, it is a `require-dev` package and every deploy runs `--no-dev`); queues → Horizon in local and production.
- No browser OpenTelemetry: frontend errors go to Sentry, frontend performance to Cloudflare Analytics.
- Every phase reviews what it sends: see the Observability review gate in `.claude/skills/build-step/SKILL.md`. Expected conditions (validation failures, 404s, auth challenges, invalid webhook signatures) are never reported as errors.

## 🧱 Code Conventions

- PHP 8.4, Laravel 13. Pint + Larastan + Pest gate CI.
- PHP enums for every fixed value set (statuses, types, visibility, consent types); stored as varchar.
- Eloquent API Resources for all responses; versioned routes (`/api/v1/`).
- Authorization via policies + `Gate::authorize()` in controllers.

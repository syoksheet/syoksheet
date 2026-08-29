# V1 Implementation Order

The ground-up build sequence for syoksheet, one Laravel application serving `api.*`, `app.*`, `admin.*`, and `www.*`, with Inertia + Svelte UIs built alongside each backend domain. Dependency-driven: audit and notifications come early so every later feature fires events from day one; the design-system components come before the screens that use them; billing precedes the features its tiers gate; SSO is last. Each phase ends with its tests green, its audit events firing, and its screens working.

## 🏗️ Sequence

| # | Phase | Scope | Key specs |
|---|-------|-------|-----------|
| 1 | Foundation | **Already in place pre-build:** Laravel 13 in DDEV (dual Postgres incl. `postgres-audit`, Redis, Mailpit), the full lint/type/test toolchain (Pint, Larastan, Pest, ESLint, Prettier, svelte-check), Vite + Svelte plugin, and the typed-props pipeline (laravel-data + typescript-transformer → `resources/ts/types/generated.d.ts`). **This phase builds:** the `log` DB connection + audit migrations path, Redis DB split (cache 1 / session 2 / queue 3) + queue priorities, subdomain routing skeleton (`Route::domain()` × 4), the Inertia bootstrap (middleware, root views, Svelte page resolution in `resources/ts/`), design tokens as global SCSS (`resources/scss/`, Geist fonts), the GitHub Actions CI workflow, error-code + validation conventions scaffolding, `AiService` scaffold, `bruno/` scaffold + `BrunoSeeder` | architecture.md, validation.md, localization.md, ai.md, api/README.md |
| 2 | Design system | The Svelte component library from `design/docs/` specs: headless primitives (Bits UI) styled with the tokens: buttons, forms, selection, modal, toast, tooltip, tabs, tags/badges, avatars, skeleton, progress, empty state, verification mark, breadcrumb, app shell + sidebar per the layout contract | design/docs/, design/previews/ |
| 3 | Auth & user core | users + user_emails schema, `UserEmailProvider`, register/login/logout, email verification, password reset, rate limits, Google OAuth, 2FA, email management, profile + avatar: **with their Inertia screens** (Sign In/Up, Forgot Password, Email Verification, Profile, Security from the studio designs) | features/auth/, features/users/ |
| 4 | Admin core | Admin model, bidirectional guard isolation, Spatie RBAC + team seeding, admin auth (rate-limited), provisioning, tokens, impersonation: admin shell + auth screens (designs to be generated) | features/admin/ |
| 5 | Audit log | `log` connection migrations, activitylog config, event fan-out (`AuditLogJob`), retrofit auth + admin events, changed-fields policy, display contract | features/audit/ |
| 6 | Notifications | Morph table, `NotificationJob`, Reverb broadcasting, per-category preferences, notification centre UI (user + admin) | features/users/notifications.md |
| 7 | Privacy phase 1 | `ConsentType` enum + consent_records + endpoints, MarketingEmails at registration, policy versions, cookie banner (www + app), deletion request / cooling-off / suspension | features/privacy/consent.md, account-deletion.md (phases 1–2) |
| 8 | Location | GeoNames import command + tables, profile location fields + selectors | database/location.md |
| 9 | Taxonomy | ESCO/O*NET import, crosswalk dedup + AI review queue (`AiService`), Scout/Meilisearch, aliases, occupation_skills, taxonomy_translations + Weblate export/import commands, search UI, admin taxonomy screens | features/taxonomy/, localization.md |
| 10 | Organizations | Orgs, teams/permissions, invitations + join requests + departures, transfers, DNS pipeline + place auto-link, org audit view + live activity stream, branding config: **with the org screens** (Overview, Members, Settings, Verification, Moderation, Membership from the designs). Personal-anchor email rule activates here | features/organizations/ (excl. webhooks) |
| 11 | Brags & verification | Brag CRUD + children, field locking, collaborators, personal + org verification, expiry job: **with** My Brags, Brag Detail, Brag Editor screens and the external verifier/collaborator pages (www, server-rendered) | features/brags/ |
| 12 | Billing | DodoPayments checkout + webhooks, subscriptions, seat billing, **tier-limit wiring across all prior domains**, lifecycle/dunning, downgrade hiding + selection, with Billing + Org Billing screens | features/billing/ |
| 13 | Public pages & analytics | `www.*` Blade: homepage, user walls, org walls + pins, org directory (from the marketing designs; missing pages designed first), view tracking + analytics tables + dashboards, custom wall URL, PDF export | features/public/, features/users/pdf-export.md |
| 14 | Jobs & matching | Postings CRUD + Push API + normalization review, public jobs directory (www), match_scores pipeline + reconcile, open-to-work + consents, candidate lists, talent search, express interest, match alerts: app + admin + www surfaces | features/jobs/ |
| 15 | Org webhooks | Endpoints CRUD, signed delivery + retries + auto-disable, deliveries log + cleanup, settings UI | features/organizations/webhooks.md |
| 16 | Privacy phase 2 | `GenerateDataExportJob` covering every domain, Tier 1/2/3 erasure jobs, R2 lifecycle | features/privacy/data-export.md, account-deletion.md (phases 3–5) |
| 17 | SSO | OIDC flow, `EnsureOrgSsoSession` gate, subject binding, owner escape hatch, config UI | features/auth/sso.md |
| 18 | Hardening & launch | `audit:archive`, full scheduled-jobs verification, OpenAPI completeness check for the external surface, security review, launch checklist | scheduled-jobs.md, syoksheet-docs → infrastructure/operations.md |

## 🧾 Phase Prerequisites

Things that must exist or be decided before a phase starts, discovered during design and easy to forget at implementation time.

| Phase | Prerequisite |
|-------|--------------|
| 5. Audit log | **Consider a transactional outbox** for audit events. Today a domain event fans out to `AuditLogJob` on the queue; if Redis is lost, that job is gone and the audit record is silently missing: nothing reconciles it, and "retry forever" only helps if the job still exists. An outbox writes the event to a durable table in the **primary** database inside the same transaction as the business change; the worker drains it into the audit database and marks rows done, and a reconcile command sweeps anything unprocessed. It makes queue durability irrelevant, including the managed-cache eviction and persistence questions, at the cost of one table and one extra write per event. Decide before the write path is built; retrofitting means touching every event |
| 10. Organizations | **A second domain for testing DNS verification.** The flow needs both a TXT record and a verified email on the same domain. `syoksheet.com` covers the first org; a throwaway domain at Cloudflare Registrar (~$11/yr, DNS + Email Routing included) is needed to test a second org, the already-claimed conflict path, and `dns:reverify` failure when a record is removed |
| 12. Billing | DodoPayments **test mode** keys on staging, never live. Staging's Cloudflare Access bypass for `/api/v1/webhooks/*` is added here, not before, since nothing needs that path reachable until billing |
| 15. Org webhooks | The **Security** section of `docs/features/organizations/webhooks.md` is an implementation requirement, not guidance. SSRF (resolve-then-validate, re-validated per delivery, no redirects), blocked address space, delivery hardening, scanning abuse, signing with a timestamp, and payload visibility boundaries each need their own test. Seeded webhook endpoints use `.test` hostnames so nothing leaves the machine |

## 📏 Rules of Engagement

- Every phase: feature tests written and passing, audit events added to the catalog **before** implementing, OpenAPI **and** the Bruno collection updated in the same commit as any external-API route.
- Screens are implemented from their Claude Design files, using only design-system components, a screen without a design gets designed first (studio project, against the published design system).
- Tier limits are config-driven from their first appearance (phases 3–11) and wired to real subscriptions in phase 12: features never hardcode limits.
- All UI strings through translation files from the first screen, no retrofitting i18n.
- Phases 13–15 are independent of each other and can reorder freely; everything else is dependency-ordered.
- Privacy phase 2 (16) comes after all data-owning domains so export and erasure are complete on first build.

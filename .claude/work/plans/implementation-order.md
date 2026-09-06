# V1 Implementation Order

The ground-up build sequence for syoksheet, one Laravel application serving `api.*`, `app.*`, `admin.*`, and the apex (marketing), with Inertia + Svelte UIs built alongside each backend domain. Dependency-driven: the environments come before the code that runs in them; audit and notifications come early so every later feature fires events from day one; the design-system components come before the screens that use them; billing precedes the features its tiers gate; SSO is last. Each phase ends with its tests green, its audit events firing, and its screens working.

## 🏗️ Sequence

| # | Phase | Scope | Key specs |
|---|-------|-------|-----------|
| 0 | Local environment | Bring DDEV to the decided shape before any code runs against it: PostgreSQL 18 holding `syoksheet_primary` and `syoksheet_audit`, HTTPS via mkcert so cookie behavior across four subdomains matches production, Buggregator replacing Mailpit, RustFS as the R2 stand-in (a committed `.ddev/docker-compose.rustfs.yaml`, not an add-on), `.ddev/redis/redis.conf` (`volatile-lru`, `appendonly yes`, `appendfsync everysec`), Horizon moved to `require`, `sentry/sentry-laravel` plus the browser SDK pointed at Buggregator. Verify both databases resolve and all four hostnames answer | infrastructure/local-development.md |
| 1 | Foundation | **Already in place pre-build:** Laravel 13 in DDEV, the full lint/type/test toolchain (Pint, Larastan, Pest, ESLint, Prettier, svelte-check), Vite + Svelte plugin, and the typed-props pipeline (laravel-data + typescript-transformer → `resources/ts/types/generated.d.ts`). **This phase builds:** the `audit` DB connection + audit migrations path + the audit database's two-user grants as code, Redis DB split (cache 1 / session 2 / queue 3) + queue priorities, subdomain routing skeleton (`Route::domain()` × 4), the Inertia bootstrap (middleware, root views, Svelte page resolution in `resources/ts/`), design tokens as global SCSS (`resources/scss/`, Geist fonts), the GitHub Actions CI workflow, error-code + validation conventions scaffolding, AI SDK scaffold, `bruno/` scaffold + `BrunoSeeder` | architecture.md, validation.md, localization.md, ai.md, api/README.md |
| 2 | Frontend foundations | Bits UI installed and pinned, `resources/ts/components/` grouped to mirror the spec sections with a `$components` alias and no barrel file, and the app shell plus logo from the layout contract. The rest of the component library is **not** built here: each feature phase builds the components its own screens need, because building twenty-eight up front means validating specs no screen has exercised and roughly a third would never be used | design/docs/ (`DS App Shell`, `DS Logo`, `DS Bits UI`), .ai/rules/ts.md |
| 2b | Tables | Table and Data Table built in house against the `DS Table` / `DS Data Table` specs, with sort, filter, paginate and select in a Svelte 5 runes composable. No TanStack Table and no TanStack package for anything. Split from Phase 2 because it is close to a phase in its own right and nothing in Phases 3 or 4 needs it: it is independent and may slide to just before Phase 5, which is the first consumer | design/docs/ (`DS Table`, `DS Data Table`) |
| 3 | Auth & user core | users + user_emails schema, `UserEmailProvider`, register/login/logout, email verification, password reset, rate limits, Google OAuth, passkeys, email management, profile + avatar: **with their Inertia screens** (Sign In/Up, Forgot Password, Email Verification, Profile, Security from the studio designs) | features/auth/, features/users/ |
| 4 | Staging | Provision and prove the deployment path, now that Phase 3 has produced something a person can actually sign into and look at. Cloudflare DNS for `staging.*` across all four subdomains, Cloudflare Access with Google SSO plus a CI service token, Origin CA certificate with SSL mode Full (strict), `X-Robots-Tag: noindex`. Forge App Server (1 vCPU / 2 GB / 50 GB) carrying the app and Redis, with `redis.conf` tuned and the upload ceiling raised in three places, `upload_max_filesize` and `post_max_size` in PHP and `client_max_body_size` in nginx, all above the 10 MB attachment limit, since nginx defaults to 1 MB and rejects with a bare 413. Both databases live on a Forge **managed** PostgreSQL 18 cluster, not on the box, with `shared_buffers` tuned and the audit database's application and erasure users created. Staging R2 bucket, staging Resend key with `Mail::alwaysTo()`, Sentry `staging` environment. Then the deploy script (sparse-checkout, `migrate:all`, SHA-keyed artifact fetched from R2, abort when missing), the first real deploy of Phase 1, and Bruno smoke tests reaching the API through Access | syoksheet-docs → infrastructure/setup.md, environments.md, deployment.md; infrastructure/deployment.md |
| 5 | Admin core | Admin model, bidirectional guard isolation, Spatie RBAC + team seeding, admin auth (rate-limited), provisioning, tokens, impersonation: admin shell + auth screens (designs to be generated) | features/admin/ |
| 6 | Audit log | `audit` connection migrations, activitylog config, event fan-out (`AuditLogJob`), retrofit auth + admin events, changed-fields policy, display contract | features/audit/ |
| 7 | Notifications | Morph table, `NotificationJob`, Reverb broadcasting, per-category preferences, notification center UI (user + admin) | features/users/notifications.md |
| 8 | Privacy phase 1 | `ConsentType` enum + consent_records + endpoints, MarketingEmails at registration, policy versions, cookie banner (marketing + app), deletion request / cooling-off / suspension | features/privacy/consent.md, account-deletion.md (phases 1–2) |
| 9 | Location | GeoNames import command + tables, profile location fields + selectors | database/location.md |
| 10 | Taxonomy | ESCO/O*NET import, crosswalk dedup + AI review queue (`laravel/ai` agents), Scout/Meilisearch, aliases, occupation_skills, taxonomy_translations + Weblate export/import commands, search UI, admin taxonomy screens | features/taxonomy/, localization.md |
| 11 | Organizations | Orgs, teams/permissions, guests (invitation only, expiry, no `org.manage`/`members.manage`/`billing.manage`), invitations + join requests + departures, transfers, **the `EnsureOrgSsoSession` seam** (every org-scoped route behind it, implemented as its pass-through step only so Phase 18 adds OIDC without touching a route), DNS pipeline + place auto-link, org audit view + live activity stream, branding config: **with the org screens** (Overview, Members, Settings, Verification, Moderation, Membership from the designs). Personal-anchor email rule activates here | features/organizations/ (excl. webhooks) |
| 12 | Brags & verification | Brag CRUD + children, field locking, collaborators, personal + org verification, expiry job: **with** My Brags, Brag Detail, Brag Editor screens and the external verifier/collaborator pages (apex, server-rendered) | features/brags/ |
| 13 | Billing | DodoPayments checkout + webhooks, subscriptions, flat organization plans, **tier-limit wiring across all prior domains**, lifecycle/dunning, downgrade hiding + selection, with Billing + Org Billing screens | features/billing/ |
| 14 | Public pages & analytics | Apex Inertia with SSR: homepage, user walls, org walls + pins, org directory (from the marketing designs; missing pages designed first), view tracking + analytics tables + dashboards, custom wall URL, PDF export | features/public/, features/users/pdf-export.md |
| 15 | Jobs & matching | Postings CRUD + Push API + normalization review, public jobs directory (apex), match_scores pipeline + reconcile, open-to-work + consents, candidate lists, talent search, express interest, match alerts: app + admin + apex domains | features/jobs/ |
| 16 | Org webhooks | Endpoints CRUD, signed delivery + retries + auto-disable, deliveries log + cleanup, settings UI | features/organizations/webhooks.md |
| 17 | Privacy phase 2 | `GenerateDataExportJob` covering every domain, Tier 1/2/3 erasure jobs, R2 lifecycle | features/privacy/data-export.md, account-deletion.md (phases 3–5) |
| 18 | SSO | OIDC flow, `EnsureOrgSsoSession` gate, subject binding, owner escape hatch, config UI | features/auth/sso.md |
| 19 | Production & launch | Provision production from the same runbook staging already proved: Forge App Server (2 vCPU / 4 GB / 80 GB) with Redis on the box and both databases on a managed PostgreSQL cluster, Origin CA, the two audit database users, daily `pg_dump -Fc` to R2 with a 30-day lifecycle rule, UptimeRobot, Forge spend alerts. Then `audit:archive`, full scheduled-jobs verification, OpenAPI completeness check for the external API, security review, launch checklist | syoksheet-docs → infrastructure/setup.md, operations.md; scheduled-jobs.md |

## 🧾 Phase Prerequisites

Things that must exist or be decided before a phase starts, discovered during design and easy to forget at implementation time.

| Phase | Prerequisite |
|-------|--------------|
| 0. Local environment | Two steps need the operator at the keyboard, not an agent: `brew install mkcert nss && mkcert -install`, and the DDEV volume recreation that the PostgreSQL 16 to 18 move requires. There is no local data to preserve, so recreation is free, but it must be deliberate |
| 4. Staging | **Decide whether the Cloudflare configuration is defined in OpenTofu from the start.** This phase creates the first meaningful amount of clicked state: DNS across four staging subdomains, Access applications and policies, a service token, R2 buckets and lifecycle rules, cache rules, Bot Fight and the WAF. Defining it as code from the start is cleaner than clicking it and adopting it later with `import` blocks, but it means learning OpenTofu while standing up staging, which is two unknowns at once. Either is defensible; deciding after the fact is not |
| 4. Staging | Staging costs $42/month from this phase until launch: $12 Forge Hobby, $13 app server, $17 managed PostgreSQL. That is deliberate: deploying a nearly-empty application is the easiest possible first deploy, and every later phase then ships to a real environment instead of accumulating risk for one big-bang deploy near launch |
| 6. Audit log | **Consider a transactional outbox** for audit events. Today a domain event fans out to `AuditLogJob` on the queue; if Redis is lost, that job is gone and the audit record is silently missing: nothing reconciles it, and "retry forever" only helps if the job still exists. An outbox writes the event to a durable table in the **primary** database inside the same transaction as the business change; the worker drains it into the audit database and marks rows done, and a reconcile command sweeps anything unprocessed. It makes queue durability irrelevant, including the managed-cache eviction and persistence questions, at the cost of one table and one extra write per event. Decide before the write path is built; retrofitting means touching every event |
| 11. Organizations | **A second domain for testing DNS verification.** The flow needs both a TXT record and a verified email on the same domain. `syoksheet.com` covers the first org; a throwaway domain at Cloudflare Registrar (~$11/yr, DNS + Email Routing included) is needed to test a second org, the already-claimed conflict path, and `dns:reverify` failure when a record is removed |
| 13. Billing | DodoPayments **test mode** keys on staging, never live. Staging's Cloudflare Access bypass for `/api/v1/webhooks/*` is added here, not before, since nothing needs that path reachable until billing |
| 16. Org webhooks | The **Security** section of `docs/features/organizations/webhooks.md` is an implementation requirement, not guidance. SSRF (resolve-then-validate, re-validated per delivery, no redirects), blocked address space, delivery hardening, scanning abuse, signing with a timestamp, and payload visibility boundaries each need their own test. Seeded webhook endpoints use `.test` hostnames so nothing leaves the machine |

## 📏 Rules of Engagement

- Every phase: feature tests written and passing, audit events added to the catalog **before** implementing, OpenAPI **and** the Bruno collection updated in the same commit as any external-API route.
- **Reshape the phase's endpoint docs before implementing them.** The tables under `docs/features/` predate Inertia and still carry `/api/v1/...` paths from the old JSON-API design. Each becomes an operation catalog (operation, host that serves it, behavior) as its phase starts. Internal routes get no `/api` prefix; whether an operation is sold on `api.*` is decided then and recorded in `openapi.json`.
- Screens are implemented from their Claude Design files, using only design-system components, a screen without a design gets designed first (studio project, against the published design system).
- Tier limits are config-driven from their first appearance (phases 4–12) and wired to real subscriptions in phase 13: features never hardcode limits.
- All UI strings through translation files from the first screen, no retrofitting i18n.
- Phases 14–16 are independent of each other and can reorder freely; everything else is dependency-ordered.
- Privacy phase 2 (17) comes after all data-owning domains so export and erasure are complete on first build.
- Shared components are built by the phase whose screens need them, not up front. Phase 2 ships only the foundations and the app shell; Phase 3's auth screens bring Button, Field, Alert and Card, and so on. Table and Data Table stay in Phase 2b.
- Staging is deliberately the fourth phase, not the second. It costs $42/month from the moment it exists, and until Phase 3 there is nothing on it a person could use, so the earlier placement would have paid for an idle environment through the whole build. Phase 3 is the first point where a real account can be created and a real screen looked at, which is when staging starts earning.
- From Phase 4 onward every phase deploys to staging. A phase is not done because it passes locally.

## 📤 Carried Forward from Closed Phases

Recorded here because the phase plans that produced these were deleted once their phases
closed. Everything below is a commitment made during a closed phase that a later one owes.
Items already carried by a code comment or another doc are noted as such rather than
repeated.

| Owed by | Item | Where it already lives |
|---|---|---|
| Phase 4 | Artifact upload to R2 and the Forge deploy hooks | Stubbed with a comment in `.github/workflows/assets.yml` |
| Phase 4 | A deploy workflow, and the repository secrets it needs | `docs/infrastructure/deployment.md` § Secrets |
| Phase 4 | SSR daemon: supervisor unit, its own unprivileged user, loopback-only egress | `syoksheet-docs → infrastructure/setup.md`, Forge daemon list |
| Phase 4 | Revisit GitHub Team. Free gives a private repo no branch protection, so CI reports but cannot block | Trigger: a second committer, or a red build able to deploy |
| Phase 4 | Source map upload to Sentry at build time | `docs/infrastructure/deployment.md` § CI Pipeline |
| Phase 2 | Design-system components. Phase 1 shipped tokens and fonts only | `design/docs/` |
| Phase 3 | `App\Models\User` and the users schema. The skeleton model and factory were deleted: no table existed behind them | `config/auth.php` names the class as a string until then |
| Phase 3 | `javascript:` URL validation on user-supplied links, enforced in a Form Request | `.claude/work/specs/ssr-and-domain-rendering.md` § Client-side safety |
| Phase 6 | The erasure role's column-scoped `UPDATE` grant, beside each table's migration | `docs/database/audit.md` |
| Phase 6 | Audit tables and the activitylog configuration | `docs/features/audit/events.md` |
| Phase 10 | The two AI use cases. Phase 1 installed and configured the SDK only | `docs/ai.md` |
| Phase 14 | Apex SEO and Open Graph meta, GTM with Consent Mode v2. Placeholders ship today | Comments in `resources/views/domains/public.blade.php` and `pages/public/Home.svelte` |
| Phase 14 | Cloudflare cache rules for the apex, including the `X-Inertia` bypass | `docs/infrastructure/deployment.md` § Cloudflare Rules |
| Phase 14 | `Route::pattern` constraints on apex wildcards before any is added | `.claude/work/specs/ssr-and-domain-rendering.md` |

### Known gaps, owned by nobody yet

| Gap | Note |
|---|---|
| Sentry `before_send` PII scrubbing | Required before launch, tracked on the launch checklist in `syoksheet-docs → infrastructure/operations.md` |
| No `traces_sampler`; the apex inherits the global sample rate | `config/sentry.php` is a single global rate. Costs money rather than correctness |
| The Inertia SSR gateway sets no HTTP timeout | Laravel's 30s default applies, so a hung renderer can hold a PHP-FPM worker. Accepted deliberately; `stale-if-error` limits it to cold pages |
| Whoever deletes `design/` | Its pages are not mockups: they carry behavior and accessibility rules Figma does not usually hold, such as validate-on-blur, focus return, the truncation order and the whole App Shell contract. Those need a home first, `.ai/rules` for what constrains code and this repo's `docs/` for the rest. CLAUDE.md's Frontend section and the DesignSync instruction both describe `design/` as it stands and would need rewriting with it | Raised in Phase 2 |

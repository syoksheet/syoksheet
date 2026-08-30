# Phase 1 (insert): Apex to Inertia with SSR

**Goal:** convert the apex from Blade to Inertia with server-side rendering, split the frontend into three per-domain bundles, and leave nothing behind that still assumes the old shape.

**Specs:** `.claude/work/specs/ssr-and-domain-rendering.md` (the decisions of record), `docs/architecture.md` § Domains and § Frontend Layer, `syoksheet-docs → product/decisions.md` (2026-08-30 row).

**Audit events:** none. This phase writes no user or org data.

## Constraints

Copied from the spec, verbatim, so no task re-decides them.

- SSR runs on the **apex only**. `app.` and `admin.` disable it with `$withoutSsr = ['*']`.
- **Three middleware classes**, one per Inertia domain. The apex class shares **no user data**: no auth, nothing session-derived. This is the security boundary that makes apex output identical for every visitor and therefore safe to cache.
- **Apex GET routes start no session.** A `Set-Cookie` header makes the response uncacheable.
- **One bundle per domain.** Component names stay flat within a domain; `pages.paths` lists all three directories.
- `Cache-Control: public, max-age=60, stale-while-revalidate=300, stale-if-error=86400` on apex GET responses, values config-driven.
- **The SSR HTTP timeout is not touched.** Subclassing the gateway was considered and rejected.

### Verified before planning

| Fact | Evidence |
|---|---|
| A stateless apex is safe | Every session touchpoint in `Inertia\Middleware` is guarded: `resolveValidationErrors` by `hasSession()` (line 241), `onVersionChange` by `hasSession()` (line 223), and `reflash()` is a no-op because `getFlashed()` returns `[]` without a session (`ResponseFactory.php:490`) |
| `$withoutSsr = ['*']` works | `ExcludesPaths::inExceptArray` calls `$request->is($except)`, and `Str::is('*', ...)` matches every path including `/` |
| Pages are scoped per entry file | `@inertiajs/vite` transforms `pages: './pages/app'` inside each `createInertiaApp` call, so each bundle globs only its own directory |
| There is exactly one SSR entry | `InertiaPluginOptions.ssr` is `string | false | InertiaSSROptions`, not a list |

## Tasks

### Task 1: Page paths

**Files:** modify `config/inertia.php`.

**Behaviour:** `pages.paths` lists `ts/pages/app`, `ts/pages/admin`, `ts/pages/public`. `ensure_pages_exist` stays `true`. The existing comment explaining the repeated `pages` block stays accurate and gains a line on why the check is now per-domain-set rather than exact.

**Who writes:** claude (pure config, explicit handover).

- [x] Implement
- [x] Green: `ddev php artisan config:show inertia.pages.paths`

### Task 2: Three middleware classes

**Files:** delete `app/Http/Middleware/HandleInertiaRequests.php`; create `HandleAppInertiaRequests.php`, `HandleAdminInertiaRequests.php`, `HandlePublicInertiaRequests.php`.

**Behaviour:**

| Class | `$rootView` | `$withoutSsr` | Shares |
|---|---|---|---|
| `HandleAppInertiaRequests` | `domains.app` | `['*']` | `locale`, plus parent's `errors` |
| `HandleAdminInertiaRequests` | `domains.admin` | `['*']` | `locale`, plus parent's `errors` |
| `HandlePublicInertiaRequests` | `domains.public` | `[]` | `locale` only |

The public class must **not** call `parent::share()`, because that shares session-derived validation errors. It returns `['locale' => ...]` and nothing else, with a comment stating that adding a user-derived prop here would make every cached apex page a data leak.

**Who writes:** claude.

- [x] Failing test: `ApexRendersWithoutUserDataTest` asserts the apex Inertia response carries no `errors` and no auth prop
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=Inertia`

### Task 3: Three root views

**Files:** modify `resources/views/domains/app.blade.php`; create `domains/admin.blade.php`, `domains/public.blade.php`.

**Behaviour:** three standalone files, no shared partial.

- `app` and `admin`: `noindex, nofollow`, CSRF token meta, their own Vite entry, `@inertiaHead`.
- `public`: no CSRF meta (the group is stateless), `<x-inertia::head>` with fallback title and description for when SSR is inactive, and placeholders for SEO/OG meta and GTM with Consent Mode v2. The placeholders are marked as Phase 14 work rather than left as silent gaps.

**Who writes:** claude.

- [x] Implement
- [x] Green: covered by Task 9's root-view assertions

### Task 4: Pages and entries

**Files:** move `resources/ts/pages/Welcome.svelte` to `resources/ts/pages/app/Welcome.svelte`; create `pages/admin/Welcome.svelte`, `pages/public/Home.svelte`; rewrite `resources/ts/app.ts`; create `admin.ts`, `public.ts`, `ssr.ts`; modify `routes/admin.php` and `routes/public.php` to render Inertia pages.

**Behaviour:** each client entry calls `createInertiaApp({ pages: './pages/<domain>' })` using the plugin shorthand, replacing the hand-written glob and its now-false comment. `ssr.ts` points at `./pages/public`, since SSR is apex-only. `routes/public.php` renders `Home` and loses its "Server-rendered Blade" comment; `routes/admin.php` renders `Welcome` instead of a plain string.

**Who writes:** claude.

- [x] Implement
- [x] Green: `ddev exec npm run check` and `ddev exec npm run lint`

### Task 5: Vite configuration

**Files:** modify `vite.config.ts`.

**Behaviour:** add the `inertia({ ssr: 'resources/ts/ssr.ts' })` plugin, list all four client inputs on the `laravel()` plugin, and set `ssr: { noExternal: true }` so the SSR bundle is self-contained and the server needs no `node_modules`.

**Who writes:** claude.

- [x] Implement
- [x] Green: `ddev exec npm run build` produces `public/build/` and `bootstrap/ssr/`

### Task 6: Route group wiring

**Files:** modify `bootstrap/app.php`.

**Behaviour:** attach each Inertia middleware to its own domain group. The apex group is **stateless**: it does not use `web`. It carries `SubstituteBindings`, the apex cache-header middleware from Task 7, and `HandlePublicInertiaRequests`, and nothing that starts a session or queues a cookie. The existing comment block is rewritten, since its claim that every HTML group needs `web` is what this task disproves.

**Who writes:** claude.

- [x] Failing test: `ApexIsStatelessTest` asserts an apex response carries no `Set-Cookie` header
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=Apex`

### Task 7: Apex cache headers

**Files:** create `app/Http/Middleware/SetApexCacheHeaders.php`; modify `config/domains.php` or add cache values to it.

**Behaviour:** sets `Cache-Control: public, max-age=..., stale-while-revalidate=..., stale-if-error=...` on successful apex GET responses only. Never on a non-GET, never on a non-2xx, so an error page cannot be cached as if it were content. The three values are config-driven, not literals.

**Who writes:** claude.

- [x] Failing test: `ApexCacheHeadersTest` asserts the directives are present on a GET 200 and absent on a 404
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=ApexCacheHeaders`

### Task 8: SSR failure reporting

**Files:** create `app/Listeners/ReportSsrRenderFailure.php`; register it.

**Behaviour:** listens for `Inertia\Ssr\SsrRenderFailed` and reports to Sentry with the component name and error type. Required because the gateway swallows SSR failure and falls back to client rendering, so without this a broken SSR server is invisible while every crawler silently receives an empty shell.

**Who writes:** claude.

- [x] Failing test: `SsrFailureIsReportedTest` asserts the listener reports when the event fires
- [x] Implement
- [x] Green: `ddev php artisan test --compact --filter=SsrFailure`

### Task 9: Test suite

**Files:** modify `phpunit.xml`; rewrite `tests/Feature/InertiaBootstrapTest.php`; add the tests named in Tasks 2, 6, 7, 8.

**Behaviour:** `INERTIA_SSR_ENABLED=false` in `phpunit.xml` so the adapter never dispatches SSR during tests. `InertiaBootstrapTest`'s "leaves the apex as plain blade" test and its comment are removed, replaced by assertions that the apex renders Inertia through `domains.public`.

One test enables SSR deliberately and uses `Http::fake()` to prove the split: a request to the apex dispatches to the SSR server, and a request to `app.` does not. This is the only assertion that actually proves `$withoutSsr` works, rather than merely asserting the property is set.

**Who writes:** claude.

- [x] Implement
- [x] Green: `ddev php artisan test --compact` (full suite, 45 tests currently passing, none may regress)

### Task 10: Stale sweep

**Files:** `docs/architecture.md`, plus anything the sweep finds.

**Behaviour:** the doc reversal is already applied across ten files. This task catches what remains:

- `docs/architecture.md` § Frontend Layer says nothing about per-domain bundles or SSR. Add it.
- `docs/architecture.md:52` still names the database `syoksheet`; it was renamed to `syoksheet_primary`. Fix.
- Re-run the sweeps: `grep -rn -i 'blade' docs/ ../syoksheet-docs/`, and a grep for single-entry assumptions, confirming only intentional historical references remain.
- Update `.claude/work/specs/ssr-and-domain-rendering.md` to mark the open items closed.

**Who writes:** claude.

- [x] Implement
- [x] Green: sweep output shows no unintended matches

## Deviations from this plan

- The four planned test classes (`ApexRendersWithoutUserDataTest`, `ApexIsStatelessTest`, `ApexCacheHeadersTest`, `SsrFailureIsReportedTest`) were consolidated into two, `ApexResponseTest` and `SsrDispatchTest`, grouped by the surface under test rather than one class per assertion.
- Task 3 originally left the apex head to the root view's fallback. Review found that a successful SSR render discards that fallback entirely, so every page under `pages/public/` now declares its own head block, with a test that fails if one does not.
- Task 7's middleware shipped as `SetPublicCacheHeaders`, not `SetApexCacheHeaders`. The domain is `public` everywhere else in the code, and `Domain::Public` is the enum case.
- Task 5 used the object form of the plugin's `ssr` option rather than the string, so the loopback `host` and `port` are pinned explicitly instead of inherited from defaults.
- Task 7 gained a rule that was not in the plan: Inertia JSON partials are excluded from caching. They are served from the same URL as the HTML page and `Vary` is not honoured by Cloudflare outside `Accept-Encoding`.

## Artifacts

| Item | Applies |
|---|---|
| Route change → `openapi.json` + `bruno/` | No. The apex is not part of the sold API, and no `api.` route changes |
| New business rule → `docs/validation.md` | No |
| New Artisan command → `docs/scheduled-jobs.md` | No. `inertia:start-ssr` is a daemon, covered in `deployment.md`, not a scheduled job |
| New personal-data field | No |
| New audit event | No |
| New consent type | No |
| New durable convention → `record-rule` | Already done: the module-scope-state rule is in `.ai/rules/ts.md` |

## Observability review

1. **New exception types:** none. `SsrException` is only thrown when `inertia.ssr.throw_on_error` is set, which stays off outside E2E.
2. **High-volume routes:** the apex is the highest-volume, lowest-value surface to trace. **Not yet implemented:** `config/sentry.php:41` is a single global `traces_sample_rate` and there is no `traces_sampler` anywhere, so the apex inherits the global rate. A per-host sampler is deferred; the CDN absorbs most apex traffic before it reaches the origin, which reduces but does not remove the need.
3. **Personal data in payloads:** the apex shares no user data by construction, which is the strongest possible answer here.
4. **Invisible background failure:** yes, and it is the reason Task 8 exists.

## Out of scope

- Actual marketing, wall and profile pages. Phase 14.
- SEO/OG meta content and the GTM container. Placeholders only; Phase 14.
- Cloudflare cache rules and rate limiting. Configuration applied at launch, not code.
- The supervisor unit, the unprivileged SSR user and its iptables egress rules. Deployment, Phase 2 onward.
- Apex form posts and the session-enabled subgroup they will need. No apex forms exist yet.

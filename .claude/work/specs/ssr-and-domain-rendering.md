# SSR and Domain Rendering

How each of the four domains is rendered, why server-side rendering runs on the apex alone, and the isolation, caching and safety rules that follow from that. Decisions of record: the code implementing them is listed as pending where it does not exist yet.

## Rendering per domain

| Host | Rendering | SSR | Reason |
|------|-----------|-----|--------|
| `syoksheet.com` (apex) | Inertia + Svelte | Yes | Public walls and profiles are complex enough to need components, and they are the only SEO-critical pages |
| `app.syoksheet.com` | Inertia + Svelte | No | Behind auth, never crawled, so SSR would add a Node round trip for nothing |
| `admin.syoksheet.com` | Inertia + Svelte | No | Same as `app.`, and it must never reach a Node process at all |
| `api.syoksheet.com` | JSON | Not applicable | No HTML is rendered |

The apex was originally specified as server-rendered Blade. That was reversed because public walls and profiles are component-heavy, and maintaining them twice (Blade for the apex, Svelte for the app) would guarantee drift. Inertia everywhere with SSR on the one domain that needs it is the smaller cost.

## Bundles and entry points

- **One bundle per domain**, not a single shared bundle. The apex must not ship admin code, and a marketing visitor should not download the product UI.
- **`@inertiajs/vite` 3.7.0** is installed and handles page resolution and SSR configuration. It replaces the manual `createInertiaApp` glob wiring.
- **`ssr.noExternal: true`** so the SSR bundle is self-contained. Without it the server would need `node_modules` present, which is the single largest supply-chain surface we are avoiding.

## The SSR process

The Laravel adapter posts the page object to a Node daemon on `127.0.0.1:13714` and uses the rendered HTML.

| Concern | Decision |
|---------|----------|
| Process management | A supervisor restarts it. This is the ecosystem convention |
| User | A separate unprivileged user, not the web user |
| Network | Loopback only, enforced by two iptables owner rules on that user |
| Failure reporting | A listener on `SsrRenderFailed` reports to Sentry. Required because the fallback is silent |
| Request timeout | Left at the Laravel default. See below |

**The timeout is deliberately not changed.** `HttpGateway::dispatch()` sets none, so Laravel's defaults apply: 10s connect, 30s request. A hung Node process therefore holds a PHP-FPM worker for up to 30 seconds, and that pool is shared with `app.` and `admin.`. Capping it would mean subclassing the gateway and carrying a copy of vendor code, which is not standard practice and is not what the ecosystem does. Instead `stale-if-error` keeps the CDN serving the last good render through an origin problem, which reduces the exposure to cold pages only. Revisit if it ever bites.

Failure itself is safe: the gateway returns `null` on a refused connection or a non-2xx, and Inertia falls back to client-side rendering. SSR being down costs SEO, not availability.

## Data isolation

**Three middleware classes**, one per Inertia domain, rather than one shared class. The apex class shares no user data at all: no auth props, no flash scoped to a user, nothing session-derived.

This is a security boundary, not tidiness. It also produces the property the caching design depends on: apex HTML is identical for every visitor, so a cached page cannot leak one user's data to another. A single shared middleware would make that impossible to guarantee.

`app.` and `admin.` disable SSR through `$withoutSsr = ['*']` on their own middleware classes (`Inertia\Middleware:40`).

## Caching and rate limiting

Standard practice, applied without customization.

```
Cache-Control: public, max-age=60, stale-while-revalidate=300, stale-if-error=86400
```

`stale-while-revalidate` keeps SSR out of the critical path: a hot page is served stale and refreshed in the background, so no visitor waits on a render. `stale-if-error` covers origin and SSR failure.

| Cache key part | Included | Reason |
|---|---|---|
| Path | Yes | Identifies the page |
| Locale | Yes | English at launch, but a key without it poisons the day a second language ships |
| Route-declared query params | Yes | They change the content |
| Any other query param | No, stripped | Otherwise `?x=1`, `?x=2` and so on each cost a render, and the cache can be busted indefinitely |
| Cookies and auth state | No | Nothing user-specific is ever in the output |

**Inertia JSON partials are never cached.** An Inertia visit returns JSON from the same URL as the HTML page, separated only by `Vary: X-Inertia`. Cloudflare honors `Vary` for `Accept-Encoding` and effectively ignores it otherwise, so relying on it would let a JSON body be cached against the page URL and served to the next visitor or crawler. `SetPublicCacheHeaders` therefore skips any request carrying the `X-Inertia` header outright, and the Cloudflare configuration needs the matching bypass rule.

### The URL is in the cached HTML

One piece of visitor-supplied data does reach a cached apex page: the request URL. `Inertia\Response` puts `url` (from `fullUrl()`) into the page object and the `@inertia` directive writes it into the HTML as `{!! json_encode($page) !!}` inside a `<script type="application/json">` block.

With the cache key stripping unknown query params, that means the first visitor's query string is baked into the copy everyone else gets. Harmless today: `/` is the only apex route and Symfony encodes the query string. It stops being harmless once the apex has wildcard routes, because `Request::url()` comes from the raw path and the directive uses no `JSON_HEX_TAG`.

Two things prevent it, both before Phase 14:

- Cloudflare strips or normalises the query string for apex cache entries, or bypasses cache for requests carrying unknown params.
- Every apex wildcard segment gets a `Route::pattern` constraint, so a `<` can never reach the path on a 200.

Rate limiting is a Cloudflare rule, not Laravel middleware. The param allowlist is the real defense; the limiter only backstops it. Cache hits never reach the origin, so the limiter sees misses only, and the ceiling must stay high enough never to throttle a search crawler.

**The session cookie is host-only** (`SESSION_DOMAIN=null`). The apex sets no cookie because it runs no session, but a leading-dot parent domain would still have the browser *send* a logged-in user's session cookie to it on every request, to a host with no use for it. A test asserts the cookie carries no domain attribute on `app.` and `admin.`, and none at all on the apex.

**Consequence for routing:** apex GET routes must not start a session. Laravel's `web` group sets `laravel_session`, and a response carrying `Set-Cookie` will not be cached. The apex therefore gets a stateless middleware group, and any apex form post goes to a small session-enabled subgroup.

## Client-side safety

Module scope behaves differently under SSR: the process imports a module once and keeps it alive across every render, so a mutable binding at module scope is shared by every visitor.

| Hazard | Control |
|--------|---------|
| Mutable module-scope state in `.ts` | ESLint `no-restricted-syntax` on `Program > VariableDeclaration[kind="let"]` in `eslint.config.ts` |
| Mutable state in `<script module>` | Review only. `svelte-eslint-parser` does not expose those as top-level declarations. Recorded in `.ai/rules/ts.md` |
| `{@html}` | Already enforced by `svelte/no-at-html-tags`, active through `svelte.configs.recommended` |
| `javascript:` URLs in user-supplied `href` | Not covered by any lint rule. Needs the http(s) validation enforced in a Form Request, in Phase 4 |

The last row is a real residual and applies to `users.website_url`, `social_links` and brag links.

### Every public page declares its own head

When SSR succeeds, Inertia renders the head the page produced and **skips the root view's fallback entirely**, so a page under `pages/public/` that declares no head block ships to search engines with no title and no description at all. The fallback in `domains/public.blade.php` only ever covers the case where SSR is inactive.

Since this is the one domain SSR exists for, it is enforced by a test that walks `resources/ts/pages/public/` and fails on any page without a head block, rather than left to reviewer attention.

## Testing

`INERTIA_SSR_ENABLED=false` is set in `phpunit.xml` so the adapter never dispatches SSR requests during tests. `SsrDispatchTest` re-enables it for its whole file in a `beforeEach`, and one test in `ApexResponseTest` does the same. Both also set `inertia.ssr.ensure_bundle_exists` to `false` so the gateway does not bail out before the HTTP call merely because no production bundle exists in the test environment.

Those two tests are deliberately separate rather than one test with two assertions. `$withoutSsr` calls `except()` on the SSR gateway, which is a singleton that merges and retains the exclusion, so exercising `app.` first would disable SSR for the apex too and the apex assertion would pass for the wrong reason.

## Resolved

| Question | Status |
|----------|--------|
| The three root views | Decided: three standalone files, no shared partial. `domains/app.blade.php`, `domains/admin.blade.php`, `domains/public.blade.php`. Only the apex carries `<x-inertia::head>`, SEO/OG meta and GTM; it omits CSRF meta so its GET responses stay cacheable |
| Three Vite entries plus the SSR entry | Decided and implemented. Three client entries declare `pages: './pages/<domain>'`; one SSR entry points at `./pages/public`. `setup` is omitted everywhere: the adapter's default already hydrates when `data-server-rendered` is present and builds the Svelte context a hand-written `setup` would drop |

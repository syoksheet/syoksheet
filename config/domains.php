<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain Hosts
    |--------------------------------------------------------------------------
    |
    | One application serves four hosts through `Route::domain()` groups. This is
    | the single source for them, read by the routing skeleton and by anything
    | building an absolute URL outside a request, where `APP_URL` alone cannot say
    | which host a link belongs to.
    |
    | No fallback defaults on purpose. Route::domain(null) is not an error, Laravel
    | reads it as "any host", so an unset value would quietly serve the admin panel on
    | every hostname instead of failing. Domain::host() throws on a missing value
    | instead. Use App\Enums\Domain rather than config('domains.*') at call sites.
    |
    */

    'app' => env('DOMAIN_APP'),

    'admin' => env('DOMAIN_ADMIN'),

    'api' => env('DOMAIN_API'),

    'public' => env('DOMAIN_PUBLIC'),

    /*
    |--------------------------------------------------------------------------
    | Apex Response Cache
    |--------------------------------------------------------------------------
    |
    | The apex is the only domain we can cache, because it is the only one that shares
    | no user data: every visitor gets the same HTML. These become the Cache-Control
    | directives on its GET responses and Cloudflare reads them.
    |
    | stale_while_revalidate is what keeps the renderer off the critical path. A page
    | someone visited recently is served stale and refreshed in the background, so
    | nobody waits on a render. stale_if_error covers the origin or the SSR server
    | falling over. That is also why we can live with the SSR gateway not setting a
    | timeout of its own, so Laravel's 30s default applies: an outage only affects
    | pages that are not cached yet.
    |
    | The key is `cache` rather than a domain name so it cannot clash with a case of
    | App\Enums\Domain, which is what Domain::host() looks up in this file.
    |
    */

    'cache' => [

        'max_age' => (int) env('PUBLIC_CACHE_MAX_AGE', 60),

        'stale_while_revalidate' => (int) env('PUBLIC_CACHE_STALE_WHILE_REVALIDATE', 300),

        'stale_if_error' => (int) env('PUBLIC_CACHE_STALE_IF_ERROR', 86400),

    ],

];

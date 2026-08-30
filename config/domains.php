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
    | No fallback defaults, deliberately. `Route::domain(null)` is not an error:
    | Laravel reads it as "no host constraint", so an unset value would serve the
    | admin panel on every hostname rather than failing. `Domain::host()` turns a
    | missing value into an exception instead. `App\Enums\Domain` is the typed way
    | to reach them; prefer it over `config('domains.*')` at call sites.
    |
    */

    'app' => env('DOMAIN_APP'),

    'admin' => env('DOMAIN_ADMIN'),

    'api' => env('DOMAIN_API'),

    'public' => env('DOMAIN_PUBLIC'),

];

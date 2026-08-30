<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Components
    |--------------------------------------------------------------------------
    |
    | Inertia defaults to `resources/js/pages`. We keep the frontend in `resources/ts`
    | and only write Svelte, so both the path and the extensions are narrowed. The whole
    | `pages` block is repeated because Laravel merges package config one level deep:
    | overriding `paths` on its own would drop `extensions` with it.
    |
    | One path per domain, since each domain builds its own bundle and globs only its
    | own folder. Page names stay flat inside a domain, so `Inertia::render('Welcome')`
    | on `app.` finds `pages/app/Welcome`.
    |
    | The trade-off: `ensure_pages_exist` can only tell you a name exists in one of the
    | three folders, not that it exists in the one serving the request. Prefixing names
    | instead would give an exact check, but then every bundle would have to glob all of
    | `pages/`, which is the split we want. A name that only exists in another domain
    | passes here and fails in the browser on first load.
    |
    | `ensure_pages_exist` is on for a reason. With it off, rendering a page that does
    | not exist gives you a blank screen and no error anywhere, which is miserable to
    | debug. On, it throws ComponentNotFoundException.
    |
    */

    'pages' => [

        'ensure_pages_exist' => true,

        'paths' => [

            resource_path('ts/pages/app'),
            resource_path('ts/pages/admin'),
            resource_path('ts/pages/public'),

        ],

        'extensions' => [

            'svelte',

        ],

    ],

];

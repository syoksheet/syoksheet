<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abuse Rate Limits
    |--------------------------------------------------------------------------
    |
    | These apply to every account on every plan. They exist to stop automation,
    | not to sell an upgrade, so none of them ever appears in the pricing table.
    | A limit that differs by plan is a tier limit and belongs elsewhere.
    |
    | Exceeding one returns 429 with a Retry-After header and a code from
    | App\Enums\RateLimitCode.
    |
    */

    'brags' => [
        'per_hour' => (int) env('ABUSE_BRAGS_PER_HOUR', 30),
        'per_day' => (int) env('ABUSE_BRAGS_PER_DAY', 100),
    ],

    'verification_requests' => [
        'per_day' => (int) env('ABUSE_VERIFICATION_REQUESTS_PER_DAY', 20),
    ],

];

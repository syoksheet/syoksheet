<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleAppInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'domains.app';

    /**
     * Server-side rendering is switched off here. This app sits behind a login, so
     * nothing crawls it, and rendering on the server would add a round trip to Node for
     * no gain. The '*' matches every path, including '/'.
     *
     * @var array<int, string>
     */
    protected $withoutSsr = ['*'];

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
        ];
    }
}

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
     * SSR off. The app sits behind a login so nothing crawls it. SSR would add a Node round trip for no gain.
     * '*' matches every path, including '/'.
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

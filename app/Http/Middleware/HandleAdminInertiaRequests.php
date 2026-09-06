<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleAdminInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'domains.admin';

    /**
     * Server-side rendering is switched off here. The admin panel is behind a login as
     * well, and it should never touch the Node process at all. The '*' matches every
     * path, including '/'.
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

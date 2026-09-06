<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandlePublicInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'domains.public';

    /**
     * Locale and nothing else.
     *
     * We skip parent::share() on purpose. It shares validation errors from the
     * session, and everything shared here ends up in HTML that gets cached and served
     * to every visitor. Anything tied to one person would leak to everyone else, so
     * do not add per-visitor props to this method.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            'locale' => app()->getLocale(),
        ];
    }
}

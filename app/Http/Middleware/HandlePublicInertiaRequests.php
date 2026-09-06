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
     * Share the locale and nothing else.
     *
     * The apex HTML is cached and handed to every visitor, so anything shared here is
     * seen by everyone. Never add a prop that belongs to one person.
     *
     * We skip parent::share() for the same reason. It shares validation errors from the
     * session, and those belong to whoever submitted the form.
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

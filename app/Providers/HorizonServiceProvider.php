<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Register the Horizon gate.
     *
     * This gate only decides access outside local development. Horizon's own auth
     * callback lets the local environment through before the gate is consulted.
     */
    protected function gate(): void
    {
        // Closed to everyone until admin authentication is built. The parameter is
        // untyped because there is no user model yet.
        Gate::define('viewHorizon', fn (?object $user = null): bool => false);
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     * Local access does not pass through here: Horizon's own auth callback
     * admits the local environment before the gate is consulted.
     */
    protected function gate(): void
    {
        // Closed to everyone until admin authentication exists in Phase 5. Untyped
        // because there is no user model yet: the users schema arrives in Phase 4.
        Gate::define('viewHorizon', fn (?object $user = null): bool => false);
    }
}

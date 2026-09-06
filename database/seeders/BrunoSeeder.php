<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fixtures for the Bruno collection, which asserts against known ids and credentials.
 * Everything here is deterministic: a random factory value would make the run flap.
 * Credentials come from CI environment variables rather than being written here.
 */
class BrunoSeeder extends Seeder
{
    public function run(): void
    {
        $this->refuseToRunInProduction();

        // Nothing is seeded yet. Once users, organizations and brags exist, create them
        // here with fixed ids that the collection can assert against.
    }

    /**
     * CI runs this with `--force`, the same flag that skips Laravel's production
     * prompt, so the environment check belongs here rather than in the framework.
     */
    private function refuseToRunInProduction(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'BrunoSeeder creates fixtures with known credentials and must never run in production.'
            );
        }
    }
}

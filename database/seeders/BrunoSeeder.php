<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fixtures for the Bruno collection.
 *
 * CI migrates a throwaway database, runs this, serves the app and points `bru run` at
 * it. So everything here must be deterministic: the collection asserts against known
 * ids and known credentials, and a random factory value would make it flap.
 *
 * Nothing is created yet. Phase 1 has no models beyond `User` and no API routes, so the
 * collection only exercises `/up`. What exists here now is the shape: the guard, and
 * the rule that fixtures are fixed rather than faked.
 *
 * When fixtures do arrive, credentials come from CI environment variables rather than
 * being written here, and they stay plain CI variables rather than secrets: they protect
 * nothing, since the database they live in exists for one job and is thrown away.
 */
class BrunoSeeder extends Seeder
{
    public function run(): void
    {
        $this->refuseToRunInProduction();

        // Phase 4 onward: deterministic users, orgs and brags the collection can assert
        // against by id.
    }

    /**
     * This seeder exists to create accounts with known credentials. `db:seed --force` is
     * how CI runs it, and `--force` is also what skips Laravel's own production prompt,
     * so the environment check has to be here rather than left to the framework.
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

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every root view calls @vite, which throws when public/build/manifest.json is
     * missing. That file is gitignored, so on a clean checkout, and in CI where tests
     * run before the build, 20 tests fail on a missing asset none of them care about.
     * Nothing here asserts on script tags. A test that does needs to opt back in with
     * $this->withVite().
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->refuseNonTestingDatabases();
        $this->withoutVite();
    }

    private function refuseNonTestingDatabases(): void
    {
        foreach (['pgsql', 'audit'] as $connection) {
            $database = config("database.connections.{$connection}.database");

            if (is_string($database) && ! str_ends_with($database, '_testing')) {
                throw new RuntimeException(
                    "Connection [{$connection}] points at [{$database}], which is not a testing database. "
                    .'Check the DB_DATABASE and AUDIT_DB_DATABASE overrides in phpunit.xml.'
                );
            }
        }
    }
}

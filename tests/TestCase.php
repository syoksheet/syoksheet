<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->refuseNonTestingDatabases();
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

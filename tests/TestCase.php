<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\HtmlString;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every root view calls @vite, which throws when there is no built manifest. That
     * file is gitignored, so a clean checkout and CI both hit it, and no test here
     * asserts on script tags anyway. A test that needs them calls $this->withVite().
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->refuseNonTestingDatabases();
        $this->withoutVite();
        $this->pinViteNotRunningHot();
    }

    /**
     * Stop a running dev server from changing what these tests exercise.
     *
     * Inertia's SSR gateway posts to the Vite dev server instead of the SSR bundle
     * whenever a hot file exists. So whether someone has `npm run dev` running decides
     * which code path the suite covers, which is not something a test should depend on.
     *
     * We cannot pin this with useHotFile(). The stub that withoutVite() installs above
     * overrides that method to do nothing, so the call succeeds and changes nothing.
     * Replacing the binding is the only way to reach the decision.
     *
     * This has to run after withoutVite(). withVite() restores the instance that
     * withoutVite() saved, so reversing the order stops withVite() working.
     */
    private function pinViteNotRunningHot(): void
    {
        // The facade remembers the first instance it resolved. Without clearing that,
        // swapping the binding would leave the facade handing back the old stub.
        ViteFacade::clearResolvedInstance();

        $this->swap(Vite::class, new class extends Vite
        {
            public function isRunningHot(): bool
            {
                return false;
            }

            public function __invoke($entrypoints, $buildDirectory = null): HtmlString
            {
                return new HtmlString('');
            }

            /**
             * @param  array<int, mixed>  $parameters
             */
            public function __call($method, $parameters): string
            {
                return '';
            }

            public function __toString(): string
            {
                return '';
            }

            public function asset($asset, $buildDirectory = null): string
            {
                return '';
            }

            public function content($asset, $buildDirectory = null): string
            {
                return '';
            }

            /**
             * @return array<string, string>
             */
            public function preloadedAssets(): array
            {
                return [];
            }
        });
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

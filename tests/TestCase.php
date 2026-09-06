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
        $this->pinViteNotRunningHot();
    }

    /**
     * Inertia's SSR gateway posts to the Vite dev server rather than the SSR bundle
     * whenever `Vite::isRunningHot()` is true, and that is `is_file(public_path('hot'))`.
     * A developer with `npm run dev` running, or one whose dev server was killed instead
     * of stopped and left the file behind, gets different results from the same suite.
     *
     * `useHotFile()` cannot pin it: withoutVite() above installs a stub that overrides
     * that method to do nothing and return itself, so the call succeeds and changes
     * nothing. Replacing the binding is the only way to reach the decision. Running
     * after withoutVite() keeps $this->withVite() working, since that restores the
     * instance withoutVite() saved.
     */
    private function pinViteNotRunningHot(): void
    {
        // The facade caches whatever it resolved first, so swapping the binding on its
        // own leaves it handing back the stub installed a moment ago.
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

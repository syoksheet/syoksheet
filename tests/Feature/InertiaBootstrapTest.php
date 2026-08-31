<?php

use App\Enums\Domain;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia;

dataset('inertia domains', [
    'app' => [Domain::App, 'Welcome', 'domains.app'],
    'admin' => [Domain::Admin, 'Welcome', 'domains.admin'],
    'public' => [Domain::Public, 'Home', 'domains.public'],
]);

it('renders an inertia page on every html domain', function (Domain $domain, string $component) {
    $this->get('https://'.$domain->host().'/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with('inertia domains');

/**
 * Each domain builds its own bundle and the root view is what loads it. Share one root
 * view and marketing visitors start downloading the admin bundle.
 */
it('renders each domain through its own root view', function (Domain $domain, string $component, string $rootView) {
    $this->get('https://'.$domain->host().'/')
        ->assertOk()
        ->assertViewIs($rootView);
})->with('inertia domains');

/**
 * Sets a locale the app does not default to, because that is the only way this test can
 * fail. Asserting against app()->getLocale() would compare the answer to itself, and
 * would have passed just as happily against the earlier bug where the prop came from
 * $request->getLocale(): Symfony's request locale is its own default, 'en', and never
 * follows App::setLocale().
 */
it('shares the application locale with every page', function (Domain $domain) {
    App::setLocale('fr');

    $this->get('https://'.$domain->host().'/')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'fr'));
})->with([
    'app' => Domain::App,
    'admin' => Domain::Admin,
    'public' => Domain::Public,
]);

/**
 * Each root view has to load its own entry. Nothing else catches a copy-paste here: the
 * page renders, the props are right and every other test stays green while marketing
 * visitors quietly download the admin bundle.
 *
 * Reads the file rather than the rendered HTML so it works without a build.
 */
it('loads its own bundle in each root view', function (Domain $domain) {
    $view = file_get_contents(resource_path("views/domains/{$domain->value}.blade.php"));

    expect($view)->toContain("resources/ts/{$domain->value}.ts");

    foreach (['app', 'admin', 'public'] as $other) {
        if ($other !== $domain->value) {
            expect($view)->not->toContain("resources/ts/{$other}.ts");
        }
    }
})->with([
    'app' => Domain::App,
    'admin' => Domain::Admin,
    'public' => Domain::Public,
]);

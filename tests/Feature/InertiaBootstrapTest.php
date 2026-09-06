<?php

use App\Enums\Domain;
use Illuminate\Support\Facades\App;
use Inertia\ComponentNotFoundException;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia;

dataset('inertia domains', [
    'app' => [Domain::App, 'welcome/Index', 'domains.app'],
    'admin' => [Domain::Admin, 'welcome/Index', 'domains.admin'],
    'public' => [Domain::Public, 'home/Index', 'domains.public'],
]);

it('renders an inertia page on every html domain', function (Domain $domain, string $component) {
    $this->get('https://'.$domain->host().'/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with('inertia domains');

/**
 * Only `.page.svelte` files are pages. Widening `inertia.pages.extensions` back to
 * `svelte` would make every component sitting beside a page routable, and would put each
 * one in the Vite glob as a chunk of its own. Nothing else fails when that happens: the
 * real pages keep rendering, so this is the only thing standing between the narrowing
 * and a silent revert.
 */
it('does not resolve a component beside a page as a page', function () {
    $pages = sys_get_temp_dir().'/'.uniqid('inertia-pages-', true).'/home';
    mkdir($pages, recursive: true);
    file_put_contents($pages.'/Index.page.svelte', "<p>a page</p>\n");
    file_put_contents($pages.'/SiteHeader.svelte', "<p>not a page</p>\n");

    config()->set('inertia.pages.paths', [dirname($pages)]);
    App::forgetInstance('inertia.view-finder');

    expect(fn () => Inertia::render('home/Index')->toResponse(request()))->not->toThrow(ComponentNotFoundException::class);
    expect(fn () => Inertia::render('home/SiteHeader')->toResponse(request()))->toThrow(ComponentNotFoundException::class);
});

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

    expect($view)->toContain("resources/ts/domains/{$domain->value}/entry.ts");

    foreach (['app', 'admin', 'public'] as $other) {
        if ($other !== $domain->value) {
            expect($view)->not->toContain("resources/ts/domains/{$other}/entry.ts");
        }
    }
})->with([
    'app' => Domain::App,
    'admin' => Domain::Admin,
    'public' => Domain::Public,
]);

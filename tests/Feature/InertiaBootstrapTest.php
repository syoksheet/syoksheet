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
 * Only .page.svelte files are pages. If someone widens the extension back to .svelte,
 * every component sitting beside a page becomes routable and gets its own chunk.
 *
 * Nothing else fails when that happens. The real pages keep rendering, so this test is
 * the only thing standing between the narrowing and a silent revert.
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
 * Each domain builds its own bundle, and the root view is what loads it. If the domains
 * shared one root view, marketing visitors would start downloading the admin bundle.
 */
it('renders each domain through its own root view', function (Domain $domain, string $component, string $rootView) {
    $this->get('https://'.$domain->host().'/')
        ->assertOk()
        ->assertViewIs($rootView);
})->with('inertia domains');

/**
 * This sets a locale the app does not default to, because that is the only way the test
 * can fail. Asserting against app()->getLocale() would compare the answer to itself.
 *
 * It would also pass against a version that read the locale from the request instead.
 * Symfony's request locale defaults to 'en' on its own and never follows
 * App::setLocale().
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
 * Each root view has to load its own entry file. Nothing else catches a copy-paste
 * here: the page still renders, the props are still right, and every other test stays
 * green while marketing visitors quietly download the admin bundle.
 *
 * This reads the Blade file rather than the rendered HTML, so it works without a build.
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

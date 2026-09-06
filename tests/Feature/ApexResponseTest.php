<?php

use App\Enums\Domain;
use App\Http\Middleware\HandlePublicInertiaRequests;
use App\Http\Middleware\SetPublicCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The apex must not start a session. A Set-Cookie header stops the response being
 * cacheable, and being cacheable is the whole point of this domain.
 */
it('sets no cookie on the apex', function () {
    $this->get('https://'.Domain::Public->host().'/')
        ->assertOk()
        ->assertHeaderMissing('Set-Cookie');
});

/**
 * The apex HTML is cached and handed to every visitor. A prop built from one person
 * would therefore show up for everyone else too.
 */
it('shares no user-derived data on the apex', function () {
    $this->get('https://'.Domain::Public->host().'/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('locale')
            ->missing('errors')
            ->missing('auth'));
});

/**
 * Check one directive at a time. Symfony reorders Cache-Control alphabetically, so
 * matching the whole string would test Symfony's formatting instead of our policy, and
 * would break on an unrelated framework change.
 */
it('marks a successful apex page cacheable', function () {
    $header = $this->get('https://'.Domain::Public->host().'/')
        ->assertOk()
        ->headers->get('Cache-Control');

    // These values are written out rather than read from config on purpose. Reading
    // them back would only prove the header was built from config, and the test would
    // pass just as happily if someone set max-age to a day.
    expect($header)
        ->toContain('public')
        ->toContain('max-age=60')
        ->toContain('stale-while-revalidate=300')
        ->toContain('stale-if-error=86400');
});

/**
 * An error cached at the edge for a day would still be served long after the fault was
 * fixed.
 */
it('never marks an error response cacheable', function () {
    $middleware = new SetPublicCacheHeaders;

    $response = $middleware->handle(
        Request::create('https://'.Domain::Public->host().'/', 'GET'),
        fn () => new Response('failed', Response::HTTP_INTERNAL_SERVER_ERROR),
    );

    expect($response->headers->get('Cache-Control'))->toBe('no-cache, private');
});

it('never marks a write response cacheable', function () {
    $middleware = new SetPublicCacheHeaders;

    $response = $middleware->handle(
        Request::create('https://'.Domain::Public->host().'/', 'POST'),
        fn () => new Response('created', Response::HTTP_OK),
    );

    expect($response->headers->get('Cache-Control'))->toBe('no-cache, private');
});

/**
 * When SSR succeeds, Inertia uses the head the page rendered and skips the fallback in
 * the root view. So whatever the page sets is the only head the apex gets.
 *
 * The faked title is deliberately not the app name. If it matched the fallback, this
 * test would pass off the Blade slot with SSR switched off entirely, proving nothing.
 */
it('renders the head produced by server-side rendering', function () {
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);

    Http::fake([
        '127.0.0.1:13714/*' => Http::response([
            'head' => ['<title>rendered-by-ssr</title>'],
            'body' => '<div id="app" data-server-rendered="true"></div>',
        ]),
    ]);

    $this->get('https://'.Domain::Public->host().'/')
        ->assertOk()
        ->assertSee('<title>rendered-by-ssr</title>', false)
        ->assertDontSee('<title>syoksheet</title>', false);
});

/**
 * This guards the test above. Since a successful render drops the fallback title, a
 * public page with no head of its own would ship with no title and no description, and
 * nothing else here would notice.
 *
 * The search is recursive, because page names are paths. It looks at .page.svelte
 * files only: a component sitting beside a page has no head of its own, and should not
 * be held to this.
 */
it('gives every public page its own head', function () {
    $pages = collect(File::allFiles(resource_path('ts/domains/public/pages')))
        ->filter(fn (SplFileInfo $file): bool => str_ends_with($file->getFilename(), '.page.svelte'))
        ->all();

    expect($pages)->not->toBeEmpty();

    $missingHead = array_values(array_filter(
        $pages,
        fn (SplFileInfo $page): bool => ! str_contains($page->getContents(), '<svelte:head>'),
    ));

    expect($missingHead)->toBeEmpty();
});

/**
 * An Inertia visit returns JSON from the same URL as the page. The only thing telling
 * them apart is a Vary header that Cloudflare ignores, so caching this response would
 * hand the JSON to the next person asking for the page.
 *
 * We ask the middleware for the version rather than hashing the manifest ourselves, so
 * this works with or without a build. Getting the version wrong makes the request 409,
 * the middleware skips it for not being a 200, and the test passes covering nothing.
 */
it('never marks an inertia partial cacheable', function () {
    $response = $this->get('https://'.Domain::Public->host().'/', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (new HandlePublicInertiaRequests)->version(Request::create('/')) ?? '',
    ]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('application/json')
        ->and($response->headers->get('Cache-Control'))->toBe('no-cache, private');
});

it('never marks a missing apex page cacheable', function () {
    $header = $this->get('https://'.Domain::Public->host().'/no-such-page')
        ->assertNotFound()
        ->headers->get('Cache-Control');

    expect($header)->not->toContain('public');
});

/**
 * The guard for the assumption the whole caching design rests on. If a response ever
 * carries a cookie, we want it uncached rather than shared between visitors.
 */
it('never marks a response carrying a cookie cacheable', function () {
    $middleware = new SetPublicCacheHeaders;

    $response = $middleware->handle(
        Request::create('https://'.Domain::Public->host().'/', 'GET'),
        function (): Response {
            $response = new Response('hello', Response::HTTP_OK);
            $response->headers->setCookie(Cookie::create('session_like', 'value'));

            return $response;
        },
    );

    expect($response->headers->get('Cache-Control'))->not->toContain('public');
});

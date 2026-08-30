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
 * No session on the apex. A Set-Cookie header stops the response being cacheable, and
 * the whole point of this domain is that it can be cached.
 */
it('sets no cookie on the apex', function () {
    $this->get('https://'.Domain::Public->host().'/')
        ->assertOk()
        ->assertHeaderMissing('Set-Cookie');
});

/**
 * The apex HTML is cached and handed to every visitor, so a prop built from one person
 * would show up for everyone. That is why the public middleware skips parent::share(),
 * which adds validation errors from the session.
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
 * Checked one directive at a time. Symfony reorders Cache-Control alphabetically, so
 * matching the whole string would be testing its formatting instead of ours and would
 * break on an unrelated framework change.
 *
 * stale-while-revalidate is what keeps the renderer out of the request path and
 * stale-if-error is what covers it going down, so both are named on purpose.
 */
it('marks a successful apex page cacheable', function () {
    $header = $this->get('https://'.Domain::Public->host().'/')
        ->assertOk()
        ->headers->get('Cache-Control');

    expect($header)
        ->toContain('public')
        ->toContain('max-age='.config('domains.cache.max_age'))
        ->toContain('stale-while-revalidate='.config('domains.cache.stale_while_revalidate'))
        ->toContain('stale-if-error='.config('domains.cache.stale_if_error'));
});

/**
 * An error cached at the edge for a day would stick around long after the fix.
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
 * When SSR works, Inertia uses the head it rendered and skips the fallback in the root
 * view (see Head::render()). So whatever the page set has to make it into the HTML,
 * because it is the only head the apex gets.
 *
 * The faked title is deliberately not the app name. Match the fallback and the test
 * passes off the Blade slot with SSR switched off entirely, proving nothing. The
 * assertDontSee is there for the same reason: it pins that the fallback was dropped.
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
 * The guard for the test above. Since a successful render drops the fallback title, a
 * public page without its own head goes out with no title and no description and
 * nothing else here would catch it.
 *
 * Recursive on purpose. Page names are paths, so Inertia::render('jobs/Index') lives at
 * pages/public/jobs/Index.svelte, and Phase 14 nests plenty of them.
 */
it('gives every public page its own head', function () {
    $pages = collect(File::allFiles(resource_path('ts/pages/public')))
        ->filter(fn (SplFileInfo $file): bool => $file->getExtension() === 'svelte')
        ->all();

    expect($pages)->not->toBeEmpty();

    $missingHead = array_values(array_filter(
        $pages,
        fn (SplFileInfo $page): bool => ! str_contains($page->getContents(), '<svelte:head>'),
    ));

    expect($missingHead)->toBeEmpty();
});

/**
 * An Inertia visit returns JSON from the same URL as the page, and Cloudflare ignores
 * Vary unless it is Accept-Encoding. Cache this and the next person asking for the
 * page could get the JSON.
 *
 * We ask the middleware for the version instead of hashing the manifest ourselves, so
 * this works with or without a build. version() returns the manifest hash when there is
 * one and null when there is not, and Inertia turns null into ''. Hashing the file
 * directly blows up on a clean checkout, and CI runs tests before the build.
 *
 * Get the version wrong and the request 409s, the middleware skips it for not being a
 * 200, and the test passes without touching the code it is meant to cover.
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

<?php

use App\Enums\Domain;
use App\Listeners\ReportSsrRenderFailure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Ssr\SsrErrorType;
use Inertia\Ssr\SsrRenderFailed;
use Mockery\MockInterface;
use Sentry\ClientInterface;
use Sentry\SentrySdk;

/**
 * phpunit.xml turns SSR off for the suite, so these tests turn it back on.
 * ensure_bundle_exists goes off too, otherwise the gateway gives up before making the
 * HTTP call just because nobody has run a production build here.
 */
beforeEach(function () {
    config([
        'inertia.ssr.enabled' => true,
        'inertia.ssr.ensure_bundle_exists' => false,
    ]);
});

it('server-side renders the apex', function () {
    Http::fake([
        '127.0.0.1:13714/*' => Http::response(['head' => [], 'body' => '<div id="app"></div>']),
    ]);

    $this->get('https://'.Domain::Public->host().'/')->assertOk();

    // Pins the target, not just the count. A request to the wrong host would still
    // satisfy assertSentCount.
    Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:13714/render');
});

/**
 * Its own test, not a second assertion in the one above. $withoutSsr calls except() on
 * the SSR gateway, which is a singleton and keeps what you give it. Hit `app.` first
 * and SSR is off for the apex too, so the apex test would pass for the wrong reason.
 */
it('does not server-side render the user app', function () {
    Http::fake();

    $this->get('https://'.Domain::App->host().'/')->assertOk();

    Http::assertNothingSent();
});

it('does not server-side render the admin panel', function () {
    Http::fake();

    $this->get('https://'.Domain::Admin->host().'/')->assertOk();

    Http::assertNothingSent();
});

/**
 * Inertia hides SSR failures and falls back to rendering in the browser, so without a
 * listener nobody finds out and crawlers just get an empty shell. Laravel finds
 * listeners in app/Listeners, so this breaks if the class moves or handle() stops
 * type-hinting the event.
 */
it('reports server-side rendering failures', function () {
    Event::fake();

    Event::assertListening(SsrRenderFailed::class, ReportSsrRenderFailure::class);
});

/**
 * Without this the tray fills up locally: every apex page load with no `npm run dev`
 * running produces a refused connection, and an alert that fires constantly is one
 * nobody reads.
 */
it('stays quiet when the local renderer is simply not running', function () {
    app()->detectEnvironment(fn () => 'local');

    /** @var ClientInterface&MockInterface $client */
    $client = Mockery::mock(ClientInterface::class);
    $client->shouldNotReceive('captureMessage');
    SentrySdk::getCurrentHub()->bindClient($client);

    (new ReportSsrRenderFailure)->handle(new SsrRenderFailed(
        page: ['component' => 'home/Index'],
        error: 'Connection refused',
        type: SsrErrorType::Connection,
    ));
});

/**
 * A component that throws while rendering is a real bug in any environment, so the
 * local suppression above must not swallow it.
 */
it('reports a local render error even though it suppresses a local connection error', function () {
    app()->detectEnvironment(fn () => 'local');

    /** @var ClientInterface&MockInterface $client */
    $client = Mockery::mock(ClientInterface::class);
    $client->shouldReceive('captureMessage')->once();
    SentrySdk::getCurrentHub()->bindClient($client);

    (new ReportSsrRenderFailure)->handle(new SsrRenderFailed(
        page: ['component' => 'home/Index'],
        error: 'Cannot read properties of undefined',
        type: SsrErrorType::Render,
    ));
});

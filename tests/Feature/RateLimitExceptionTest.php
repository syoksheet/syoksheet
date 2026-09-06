<?php

use App\Enums\RateLimitCode;
use App\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Route::middleware('api')->get('/_test/rate-limited', function () {
        throw new RateLimitException(
            RateLimitCode::BragCreationRateLimited,
            retryAfterSeconds: 900,
            message: 'Too many achievements created. Try again shortly.',
        );
    });
});

/**
 * A rate limit tells the caller to slow down, so it is a 429 rather than the 422 a
 * business rule returns. Frontends branch on the code, never the message.
 */
it('renders as a 429 carrying the code', function () {
    $this->getJson('/_test/rate-limited')
        ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
        ->assertJson([
            'message' => 'Too many achievements created. Try again shortly.',
            'code' => 'brag_creation_rate_limited',
        ]);
});

/**
 * An HTTP client looks for the wait in the header, not the body, so it has to be there.
 */
it('sends Retry-After as a header', function () {
    $this->getJson('/_test/rate-limited')->assertHeader('Retry-After', '900');
});

/**
 * The code reaches the log context, so a logged trip names the limit that was hit.
 */
it('puts the code and the wait in the log context', function () {
    $exception = new RateLimitException(RateLimitCode::VerificationRateLimited, 3600, 'Slow down.');

    expect($exception->context())->toBe([
        'code' => 'verification_rate_limited',
        'retry_after' => 3600,
    ]);
});

/**
 * Tripping an abuse limit is user behavior, not a defect. Reporting these would bury
 * real bugs under noise from people clicking too fast.
 */
it('is not reported to sentry', function () {
    expect(config('sentry.ignore_exceptions'))->toContain(RateLimitException::class);
});

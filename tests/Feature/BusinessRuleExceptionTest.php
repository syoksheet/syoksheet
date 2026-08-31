<?php

use App\Enums\ErrorCode;
use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Facades\Route;

/**
 * Frontends branch on the code, never the message, so the code is the part of this
 * response that cannot change once it ships.
 */
it('renders a business rule violation as 422 with a stable code', function () {
    Route::get('/__test/business-rule', function (): never {
        throw new BusinessRuleException(
            ErrorCode::BragLimitReached,
            'You have reached the Free tier brag limit.',
        );
    });

    $this->getJson('/__test/business-rule')
        ->assertStatus(422)
        ->assertExactJson([
            'message' => 'You have reached the Free tier brag limit.',
            'code' => 'brag_limit_reached',
        ]);
});

/**
 * These strings are a public contract. Anything but lowercase snake_case will read as a
 * typo to whoever consumes it, and a duplicate would make two rules indistinguishable.
 */
it('gives every code a distinct snake_case value', function () {
    $values = array_map(fn (ErrorCode $case): string => $case->value, ErrorCode::cases());

    expect($values)->not->toBeEmpty()
        ->and($values)->toEqual(array_unique($values));

    foreach ($values as $value) {
        expect($value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
    }
});

/**
 * A rule violation is an expected outcome, not a fault. Reporting these would spend the
 * error quota on users hitting their own tier limits.
 */
it('is not reported to sentry', function () {
    expect(config('sentry.ignore_exceptions'))->toContain(BusinessRuleException::class);
});

/**
 * The code reaches the log context, so a logged violation names the rule that refused
 * the request instead of leaving you to match on the message.
 */
it('puts the code in the log context', function () {
    $exception = new BusinessRuleException(ErrorCode::ExportInProgress, 'An export is already running.');

    expect($exception->context())->toBe(['code' => 'export_in_progress']);
});

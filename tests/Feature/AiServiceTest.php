<?php

use Laravel\Ai\AiServiceProvider;

/**
 * Scaffold-level checks only. No agent exists yet and no provider call is made until
 * the taxonomy and job-mapping work lands, so what is worth pinning here is the wiring:
 * the SDK is installed, it points at Anthropic, and nothing else is configured.
 */
it('registers the ai sdk', function () {
    expect(app()->getProviders(AiServiceProvider::class))->not->toBeEmpty();
});

it('defaults to anthropic', function () {
    expect(config('ai.default'))->toBe('anthropic');
});

/**
 * The SDK ships drivers for a dozen providers. Configuring a key for any of them would
 * make it reachable, and only Anthropic is a documented processor for our data.
 */
it('configures no provider other than anthropic', function () {
    $configured = collect(config('ai.providers'))
        ->filter(fn (array $provider): bool => filled($provider['key'] ?? null))
        ->keys()
        ->all();

    expect($configured)->toBeIn([[], ['anthropic']]);
});

/**
 * Conversation persistence is the one part of the SDK that needs tables. Both our use
 * cases are one-shot batch prompts, so those migrations stay unpublished and the schema
 * stays ours.
 */
it('publishes no conversation tables', function () {
    $migrations = glob(database_path('migrations/*agent_conversation*'));

    expect($migrations)->toBeEmpty();
});

<?php

use Laravel\Ai\AiServiceProvider;

/**
 * These tests only check the wiring. The SDK is installed, it points at Anthropic, and
 * no other provider is configured. There is no agent yet, and nothing calls out.
 */
it('registers the ai sdk', function () {
    expect(app()->getProviders(AiServiceProvider::class))->not->toBeEmpty();
});

it('defaults to anthropic', function () {
    expect(config('ai.default'))->toBe('anthropic');
});

/**
 * The SDK ships drivers for a dozen providers. Setting a key for any of them would make
 * that provider reachable. Anthropic is the only one we have approved to handle our
 * data, so the others must stay unconfigured.
 */
it('configures no provider other than anthropic', function () {
    $configured = collect(config('ai.providers'))
        ->filter(fn (array $provider): bool => filled($provider['key'] ?? null))
        ->keys()
        ->all();

    expect($configured)->toBeIn([[], ['anthropic']]);
});

/**
 * Conversation persistence is the only part of the SDK that needs database tables. We
 * only ever send one-shot prompts, so we never publish those migrations.
 */
it('publishes no conversation tables', function () {
    $migrations = glob(database_path('migrations/*agent_conversation*'));

    expect($migrations)->toBeEmpty();
});

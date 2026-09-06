<?php

use Database\Seeders\BrunoSeeder;

it('runs clean', function () {
    $this->seed(BrunoSeeder::class);
})->throwsNoExceptions();

/**
 * This seeder exists to create accounts with known credentials, and CI runs it with
 * --force. That is also the flag that skips Laravel's own production prompt, so without
 * this guard the only thing between a fixture user and production is whoever typed the
 * command.
 */
it('refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // Call the seeder directly. Going through $this->seed() runs the Artisan command,
    // which swallows the exception and turns it into a failed exit code.
    expect(fn () => (new BrunoSeeder)->run())
        ->toThrow(RuntimeException::class, 'must never run in production');
});

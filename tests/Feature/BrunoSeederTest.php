<?php

use Database\Seeders\BrunoSeeder;

it('runs clean', function () {
    $this->seed(BrunoSeeder::class);
})->throwsNoExceptions();

/**
 * The seeder's whole job is creating accounts with known credentials, and CI calls it
 * with `--force`, which is also the flag that skips Laravel's own production prompt.
 * Without this guard the only thing standing between a fixture user and production is
 * whoever typed the command.
 */
it('refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // Called directly rather than through $this->seed(), which goes via the Artisan
    // command and swallows the exception into a failed exit code.
    expect(fn () => (new BrunoSeeder)->run())
        ->toThrow(RuntimeException::class, 'must never run in production');
});

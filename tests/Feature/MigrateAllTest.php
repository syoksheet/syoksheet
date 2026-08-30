<?php

use Illuminate\Support\Facades\Schema;

it('migrates the primary and audit databases without crossing their paths', function () {
    Schema::connection('pgsql')->dropAllTables();
    Schema::connection('audit')->dropAllTables();

    $this->artisan('migrate:all')->assertExitCode(0);

    expect(Schema::connection('pgsql')->hasTable('migrations'))->toBeTrue()
        ->and(Schema::connection('pgsql')->hasTable('users'))->toBeTrue()
        ->and(Schema::connection('audit')->hasTable('migrations'))->toBeTrue()
        ->and(Schema::connection('audit')->hasTable('users'))->toBeFalse();
});

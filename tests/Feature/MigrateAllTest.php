<?php

use Illuminate\Support\Facades\Schema;

it('migrates the primary and audit databases without crossing their paths', function () {
    Schema::connection('pgsql')->dropAllTables();
    Schema::connection('audit')->dropAllTables();

    $this->artisan('migrate:all')->assertExitCode(0);

    expect(Schema::connection('pgsql')->hasTable('migrations'))->toBeTrue()
        ->and(Schema::connection('pgsql')->hasTable('failed_jobs'))->toBeTrue()
        ->and(Schema::connection('audit')->hasTable('migrations'))->toBeTrue()
        ->and(Schema::connection('audit')->hasTable('failed_jobs'))->toBeFalse();
});

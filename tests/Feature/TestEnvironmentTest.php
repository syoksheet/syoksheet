<?php

use Illuminate\Support\Facades\DB;

it('runs against postgresql rather than sqlite', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');
});

it('reaches a database it can query', function () {
    expect(DB::select('select 1 as answer'))->toHaveCount(1);
});

it('uses a database named for testing', function () {
    expect(DB::connection()->getDatabaseName())->toEndWith('_testing');
});

<?php

use App\Enums\QueueName;
use Illuminate\Support\Facades\Redis;

it('resolves each redis connection to its own database', function (string $connection, int $database) {
    $client = Redis::connection($connection)->client();

    assert($client instanceof \Redis);

    expect($client->getDbNum())->toBe($database);
})->with([
    'default' => ['default', 0],
    'cache' => ['cache', 1],
    'session' => ['session', 2],
    'queue' => ['queue', 3],
]);

it('declares exactly the audit, notifications and default queues', function () {
    $names = array_map(fn (QueueName $case): string => $case->value, QueueName::cases());

    expect($names)->toEqualCanonicalizing(['audit', 'notifications', 'default']);
});

<?php

use App\Enums\QueueName;

/**
 * @return list<string>
 */
function queueNames(): array
{
    return array_map(static fn (QueueName $queue): string => $queue->value, QueueName::cases());
}

it('defines every environment horizon runs in', function () {
    $environments = config('horizon.environments');

    assert(is_array($environments));

    expect(array_keys($environments))->toEqualCanonicalizing(['production', 'staging', 'local']);
});

it('gives every environment one supervisor per queue', function (string $environment) {
    $supervisors = config("horizon.environments.{$environment}");

    assert(is_array($supervisors));

    expect(array_keys($supervisors))->toEqualCanonicalizing(queueNames());
})->with(['production', 'staging', 'local']);

it('never gives up on the audit queue', function () {
    expect(config('horizon.defaults.'.QueueName::Audit->value.'.tries'))->toBe(0);
});

it('times each supervisor out before the queue would hand its job to another worker', function () {
    $retryAfter = config('queue.connections.redis.retry_after');
    $defaults = config('horizon.defaults');

    assert(is_int($retryAfter));
    assert(is_array($defaults));

    foreach ($defaults as $supervisor) {
        assert(is_array($supervisor));

        expect($supervisor['timeout'])->toBeLessThan($retryAfter);
    }
});

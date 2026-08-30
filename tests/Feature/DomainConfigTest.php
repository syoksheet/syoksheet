<?php

use App\Enums\Domain;

it('configures a host for every domain', function (Domain $domain) {
    expect($domain->host())->not->toBeEmpty();
})->with(Domain::cases());

it('gives each domain a host of its own', function () {
    $hosts = array_map(static fn (Domain $domain): string => $domain->host(), Domain::cases());

    expect(array_unique($hosts))->toHaveCount(count($hosts));
});

it('refuses to guess a missing host rather than matching every hostname', function () {
    config(['domains.admin' => null]);

    expect(fn () => Domain::Admin->host())
        ->toThrow(RuntimeException::class, 'Set DOMAIN_ADMIN');
});

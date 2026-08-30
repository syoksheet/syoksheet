<?php

use App\Enums\Domain;

dataset('domains', [
    'public' => [Domain::Public, '/', 'public'],
    'app' => [Domain::App, '/', 'app'],
    'admin' => [Domain::Admin, '/', 'admin'],
    'api' => [Domain::Api, '/v1', 'api'],
]);

it('answers on its own host', function (Domain $domain, string $path, string $body) {
    $this->get("https://{$domain->host()}{$path}")
        ->assertOk()
        ->assertContent($body);
})->with('domains');

/**
 * Three domains all serve `/`, so a 404 check would prove nothing: the response would
 * be 200 from a different handler. What matters is that no host ever runs another
 * domain's route, which is what a group registered without `Route::domain()` would do.
 */
it('never runs one domain route from another host', function (Domain $domain, string $path, string $body) {
    foreach (Domain::cases() as $other) {
        if ($other === $domain) {
            continue;
        }

        expect($this->get("https://{$other->host()}{$path}")->getContent())
            ->not->toBe($body);
    }
})->with('domains');

it('serves the health check from every host', function (Domain $domain) {
    $this->get("https://{$domain->host()}/up")->assertOk();
})->with(Domain::cases());

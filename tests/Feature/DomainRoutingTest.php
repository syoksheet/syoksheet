<?php

use App\Enums\Domain;
use Illuminate\Support\Facades\Route;

dataset('domains', [
    'public' => [Domain::Public, '/', 'public.home'],
    'app' => [Domain::App, '/', 'app.home'],
    'admin' => [Domain::Admin, '/', 'admin.home'],
    'api' => [Domain::Api, '/v1', 'api.v1.index'],
]);

it('answers on its own host', function (Domain $domain, string $path, string $route) {
    $this->get("https://{$domain->host()}{$path}")->assertOk();

    expect(Route::currentRouteName())->toBe($route);
})->with('domains');

/**
 * Three domains all serve `/`, so checking for a 404 proves nothing: the wrong host
 * still returns 200, just from a different handler. What matters is which route ran,
 * which is the thing a group registered without Route::domain() gets wrong.
 */
it('never runs one domain route from another host', function (Domain $domain, string $path, string $route) {
    foreach (Domain::cases() as $other) {
        if ($other === $domain) {
            continue;
        }

        $this->get("https://{$other->host()}{$path}");

        expect(Route::currentRouteName())->not->toBe($route);
    }
})->with('domains');

it('serves the health check from every host', function (Domain $domain) {
    $this->get("https://{$domain->host()}/up")->assertOk();
})->with(Domain::cases());

<?php

use App\Enums\Domain;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * @param  TestResponse<Response>  $response
 */
function apexSessionCookieFrom(TestResponse $response): ?Cookie
{
    foreach ($response->headers->getCookies() as $cookie) {
        if ($cookie->getName() === config('session.cookie')) {
            return $cookie;
        }
    }

    return null;
}

/**
 * The session cookie is host-only.
 *
 * With a leading-dot parent domain the browser would send it to every subdomain. Two
 * of them should never see it: `api.` uses bearer tokens and has no session, and the
 * apex is public and cached. Handing a session id to a cached page often stops the CDN
 * caching it at all.
 *
 * `app.` and `admin.` do not need to share a cookie. They run separate guards, so being
 * signed in to one never means being signed in to the other.
 */
it('scopes the session cookie to the host that set it', function (Domain $domain) {
    $cookie = apexSessionCookieFrom($this->get('https://'.$domain->host().'/'));

    // Both assertions matter. The first one stops the second from passing when no
    // session cookie was set at all.
    expect($cookie)->not->toBeNull()
        ->and($cookie?->getDomain())->toBeNull();
})->with([
    'app' => Domain::App,
    'admin' => Domain::Admin,
]);

it('sets no session cookie on the apex', function () {
    expect(apexSessionCookieFrom($this->get('https://'.Domain::Public->host().'/')))->toBeNull();
});

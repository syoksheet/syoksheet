<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicCacheHeaders
{
    /**
     * Let the CDN cache apex pages.
     *
     * GET requests that returned 200 only. Caching a 404 or a 500 would keep it
     * around long after the fault was fixed.
     *
     * Inertia visits are skipped. They hit the same URL but return JSON, and the only
     * thing separating them is `Vary: X-Inertia`, which Cloudflare ignores unless it
     * is `Accept-Encoding`. Cache the JSON and someone asking for the page gets it.
     *
     * All of this only works because the apex has no session and shares no user data,
     * so every visitor gets identical HTML. Change either and this becomes a leak.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() !== Response::HTTP_OK) {
            return $response;
        }

        if ($request->hasHeader('X-Inertia')) {
            return $response;
        }

        // Belt and braces. Everything above assumes the apex has no session and sets no
        // cookies, which is true of its route group today. If a future route ends up in
        // a session-enabled group, we want it uncached rather than shared between
        // visitors along with someone's session id.
        if ($request->hasSession() || $response->headers->getCookies() !== []) {
            return $response;
        }

        $response->headers->set('Cache-Control', sprintf(
            'public, max-age=%d, stale-while-revalidate=%d, stale-if-error=%d',
            (int) config('domains.cache.max_age'),
            (int) config('domains.cache.stale_while_revalidate'),
            (int) config('domains.cache.stale_if_error'),
        ));

        return $response;
    }
}

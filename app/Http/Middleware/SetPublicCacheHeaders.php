<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicCacheHeaders
{
    /**
     * Let the CDN cache apex pages. Only GET requests that returned 200 qualify.
     *
     * Inertia visits are skipped. They hit the same URL as the page but return JSON,
     * and the only thing telling them apart is the Vary header, which Cloudflare
     * ignores. If we cached the JSON, the next visitor asking for the page would get it.
     *
     * All of this is only safe because the apex has no session and shares no user data,
     * so every visitor receives identical HTML.
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

        // Never cache a response that sets a cookie. The apex has no session today, but
        // if a future route ends up in a session-enabled group, we want that response
        // left uncached rather than shared between visitors along with a session id.
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

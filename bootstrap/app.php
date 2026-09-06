<?php

use App\Enums\Domain;
use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\HandleAdminInertiaRequests;
use App\Http\Middleware\HandleAppInertiaRequests;
use App\Http\Middleware\HandlePublicInertiaRequests;
use App\Http\Middleware\SetPublicCacheHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        /*
        | Every group is tied to a domain, so no route file answers on a host it does
        | not belong to. There is no `web:` argument on purpose: it registers without a
        | domain and anything in it would answer on all four hosts. `api:` is unused for
        | the same reason, plus it prefixes `/api` and the API host does not need that.
        |
        | Middleware is listed here because groups registered this way inherit nothing.
        | Each Inertia domain gets its own class so the apex can never pick up a prop
        | built from a logged-in user.
        |
        | The apex does not use `web`, and that is the important bit. `web` starts a
        | session, a session sets a cookie, and a response with Set-Cookie is not
        | cacheable. The apex is public, identical for everyone and server-rendered, so
        | staying stateless is what lets the CDN hold it. Nothing here needs a session:
        | Inertia's middleware guards every session call with hasSession(). Its Response
        | class calls session() without that guard, but the call returns a default
        | rather than throwing, so the only cost is building the session manager.
        |
        | When the apex needs a form, that POST gets its own small session-enabled
        | group rather than putting a session on every GET.
        */
        then: function (): void {
            Route::domain(Domain::Public->host())
                ->middleware([
                    SubstituteBindings::class,
                    SetPublicCacheHeaders::class,
                    HandlePublicInertiaRequests::class,
                ])
                ->group(base_path('routes/public.php'));

            Route::domain(Domain::App->host())
                ->middleware(['web', HandleAppInertiaRequests::class])
                ->group(base_path('routes/app.php'));

            Route::domain(Domain::Admin->host())
                ->middleware(['web', HandleAdminInertiaRequests::class])
                ->group(base_path('routes/admin.php'));

            Route::domain(Domain::Api->host())
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A business rule refusing the request is an expected outcome, so it renders
        // itself rather than reaching the default handler. Frontends read `code`; the
        // message is copy and may change.
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => $e->errorCode->value,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        // Keyed on the host, not the path. The API lives at `/v1/*` rather than
        // `/api/*`, so a path check would silently stop matching and return HTML
        // errors to a JSON client that did not send an Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->getHost() === Domain::Api->host()
                || $request->expectsJson(),
        );
    })->create();

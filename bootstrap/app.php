<?php

use App\Enums\Domain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        /*
        | Every group is domain-scoped, so no route file is reachable from a host it
        | does not belong to. There is deliberately no `web:` argument: it registers
        | without a domain, and anything in it would answer on all four hosts.
        |
        | `api:` is unused for the same reason plus one more: it prefixes `/api`, and
        | the API host needs no such prefix.
        |
        | Middleware is attached explicitly here because a group registered this way
        | inherits nothing. Inertia needs the session, cookies and CSRF that `web`
        | brings; the API wants `api` instead, which throttles and holds no session.
        */
        then: function (): void {
            Route::domain(Domain::Public->host())
                ->middleware('web')
                ->group(base_path('routes/public.php'));

            Route::domain(Domain::App->host())
                ->middleware('web')
                ->group(base_path('routes/app.php'));

            Route::domain(Domain::Admin->host())
                ->middleware('web')
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
        // Keyed on the host, not the path. The API lives at `/v1/*` rather than
        // `/api/*`, so a path check would silently stop matching and return HTML
        // errors to a JSON client that did not send an Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->getHost() === Domain::Api->host()
                || $request->expectsJson(),
        );
    })->create();

<?php

namespace App\Listeners;

use Inertia\Ssr\SsrErrorType;
use Inertia\Ssr\SsrRenderFailed;
use Sentry\State\Scope;

use function Sentry\captureMessage;
use function Sentry\withScope;

class ReportSsrRenderFailure
{
    /**
     * Send an SSR failure to Sentry.
     *
     * Inertia hides these. When the render fails it falls back to rendering in the
     * browser, so the visitor still gets a working page and nothing is logged. That is
     * fine for people and bad for crawlers, which get an empty shell. Without this we
     * would never find out the SSR server was down.
     */
    public function handle(SsrRenderFailed $event): void
    {
        if ($this->isLocalServerNotRunning($event)) {
            return;
        }

        // withScope, not configureScope. configureScope would leave this context on
        // the hub for the rest of the request and tag unrelated errors as SSR ones.
        withScope(function (Scope $scope) use ($event): void {
            $scope->setContext('inertia_ssr', [
                'component' => $event->page['component'] ?? 'unknown',
                'type' => $event->type->value,
                'hint' => $event->hint,
            ]);

            captureMessage(sprintf('Inertia SSR render failed: %s', $event->error));
        });
    }

    /**
     * Locally a refused connection just means `npm run dev` is not running. The page
     * still works, and reporting it would fire on every apex page load until everyone
     * ignored the alert. Render errors are real bugs and still get reported.
     */
    private function isLocalServerNotRunning(SsrRenderFailed $event): bool
    {
        return $event->type === SsrErrorType::Connection
            && app()->environment('local');
    }
}

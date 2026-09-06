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
     * When the render fails, Inertia quietly falls back to rendering in the browser and
     * logs nothing. A visitor still sees a working page, so nobody notices. A crawler
     * does not run JavaScript, so it gets an empty shell instead.
     */
    public function handle(SsrRenderFailed $event): void
    {
        if ($this->isLocalServerNotRunning($event)) {
            return;
        }

        // Use withScope rather than configureScope. configureScope would leave this
        // context on the hub for the rest of the request, and later errors that have
        // nothing to do with SSR would be tagged as SSR failures.
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
     * A refused connection in local development just means the dev server is not
     * running. Reporting that would raise an alert on every page load, and an alert
     * that fires constantly is one nobody reads. Real render errors are still sent.
     */
    private function isLocalServerNotRunning(SsrRenderFailed $event): bool
    {
        return $event->type === SsrErrorType::Connection
            && app()->environment('local');
    }
}

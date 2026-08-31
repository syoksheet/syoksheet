<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

/**
 * A rule the application refuses to break: a tier limit, a locked field, an export
 * already running.
 *
 * Always a 422. The request was well formed and the caller was allowed to make it; the
 * system's state is what says no. Anything the caller is simply not permitted to do is
 * a 403 and does not belong here.
 *
 * Rendered by the handler in `bootstrap/app.php`.
 */
class BusinessRuleException extends Exception
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * Laravel merges this into the log context. Without it a logged violation says only
     * that something was refused, and you have to match the message to work out which
     * rule did the refusing.
     *
     * Sentry never sees these: the class is in `ignore_exceptions`. That is deliberate
     * and different from implementing `ShouldntReport`, which would drop the local log
     * entry as well. A refused request is worth a log line and not worth an alert.
     *
     * @return array<string, string>
     */
    public function context(): array
    {
        return ['code' => $this->errorCode->value];
    }
}

<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;

/**
 * Thrown when the application refuses a request that would break one of its own rules,
 * such as a tier limit, a locked field, or an export that is already running.
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
     * Laravel adds this to the log context. Without it, the log line tells you that
     * something was refused but not which rule did the refusing.
     *
     * @return array<string, string>
     */
    public function context(): array
    {
        return ['code' => $this->errorCode->value];
    }
}

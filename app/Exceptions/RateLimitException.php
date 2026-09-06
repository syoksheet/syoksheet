<?php

namespace App\Exceptions;

use App\Enums\RateLimitCode;
use Exception;

/**
 * Thrown when a caller trips an abuse rate limit and should try again later.
 *
 * This is not a BusinessRuleException. That one means the request was valid and the
 * system's state refused it, such as a tier limit. This one means the caller is going
 * too fast, so it carries the seconds to wait and renders as a 429.
 */
class RateLimitException extends Exception
{
    public function __construct(
        public readonly RateLimitCode $rateLimitCode,
        public readonly int $retryAfterSeconds,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * Laravel adds this to the log context, so a logged trip names the limit that was
     * hit rather than leaving you to work it out from the message.
     *
     * @return array<string, int|string>
     */
    public function context(): array
    {
        return [
            'code' => $this->rateLimitCode->value,
            'retry_after' => $this->retryAfterSeconds,
        ];
    }
}

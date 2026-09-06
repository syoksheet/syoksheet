<?php

namespace App\Enums;

/**
 * Stable codes for abuse rate limits. Every one of these is returned as a 429 alongside
 * a Retry-After header.
 *
 * These are separate from ErrorCode because they answer a different question. A 422
 * means the request was valid and the system's state refused it, such as hitting a tier
 * limit. A 429 means the caller is going too fast and should try again later.
 *
 * Abuse limits apply to everyone on every plan. A limit that differs by plan is a tier
 * limit, and belongs in ErrorCode instead.
 */
enum RateLimitCode: string
{
    case BragCreationRateLimited = 'brag_creation_rate_limited';
    case VerificationRateLimited = 'verification_rate_limited';
}

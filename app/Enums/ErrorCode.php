<?php

namespace App\Enums;

/**
 * Stable codes for business-rule violations, all returned as 422.
 *
 * These strings are a public contract: frontends branch on the code and never on the
 * message, so a value here can be added but not renamed once it has shipped. The
 * message is free to change with the copy.
 *
 * The set is pinned to the `BusinessRuleError` component in docs/api/openapi.json by
 * ErrorCodeTest, so the enum and the published contract cannot drift apart.
 */
enum ErrorCode: string
{
    case BragLimitReached = 'brag_limit_reached';
    case VerificationRateLimited = 'verification_rate_limited';
    case AttachmentRequiresPro = 'attachment_requires_pro';
    case JobPostingLimitReached = 'job_posting_limit_reached';
    case FieldsLocked = 'fields_locked';
    case OwnedOrgsExist = 'owned_orgs_exist';
    case ExportCooldown = 'export_cooldown';
    case ExportInProgress = 'export_in_progress';
    case ConsentsRequired = 'consents_required';
    case ReservedName = 'reserved_name';
}

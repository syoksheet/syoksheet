<?php

namespace App\Enums;

/**
 * Stable codes for business-rule violations. Every one of these is returned as a 422.
 *
 * Frontends branch on the code and never on the message. That makes each string a
 * public contract, so you can add a case here, but you cannot rename one once it has
 * shipped. The message is free to change with the copy.
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

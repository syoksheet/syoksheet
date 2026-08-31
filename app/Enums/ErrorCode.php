<?php

namespace App\Enums;

/**
 * Stable codes for business-rule violations, all returned as 422.
 *
 * These strings are a public contract: frontends branch on the code and never on the
 * message, so a value here can be added but not renamed once it has shipped. The
 * message is free to change with the copy.
 *
 * Authorization outcomes do not belong here. A 422 means the request was allowed and
 * the system's state refused it; a 403 means the caller may not do this at all.
 */
enum ErrorCode: string
{
    case BragLimitReached = 'brag_limit_reached';
    case PendingVerificationLimitReached = 'pending_verification_limit_reached';
    case AttachmentLimitReached = 'attachment_limit_reached';
    case QueueCapacityReached = 'queue_capacity_reached';
    case JobPostingLimitReached = 'job_posting_limit_reached';
    case MemberLimitReached = 'member_limit_reached';
    case TeamLimitReached = 'team_limit_reached';
    case FieldsLocked = 'fields_locked';
    case OwnedOrgsExist = 'owned_orgs_exist';
    case ExportCooldown = 'export_cooldown';
    case ExportInProgress = 'export_in_progress';
    case ConsentsRequired = 'consents_required';
    case ReservedName = 'reserved_name';
    case WorkDomainPrimary = 'work_domain_primary';
}

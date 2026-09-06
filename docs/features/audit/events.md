# Audit Log: Events Catalog

All events written to the audit log, organised by domain (`log_name`). Every user-initiated event includes `ip_address` and `user_agent`; System events carry neither. New features must add their events here before implementation.

## `auth`: Authentication & Account Security

All `internal` visibility.

| Event | Causer | Subject | Notes |
|-------|--------|---------|-------|
| `user.registered` | System | User | IP captured at registration |
| `user.login` | User | User | No extra properties |
| `user.logout` | User | User | No extra properties |
| `user.login_failed` | System | None | `email_attempted` in `properties` |
| `user.password_changed` | User | User | No extra properties |
| `user.password_reset_requested` | System | User | No extra properties |
| `user.email_changed` | User | User | `old_email`, `new_email` in `properties` |
| `user.passkey_registered` | User | User | `passkey_name` in `properties` |
| `user.passkey_deleted` | User | User | `passkey_name` in `properties` |
| `user.oauth_connected` | User | User | `provider` in `properties` |
| `user.oauth_disconnected` | User | User | No extra properties |
| `user.impersonated` | Admin | User | `admin_id` in `properties` |
| `admin.login` | Admin | Admin | No extra properties |
| `admin.logout` | Admin | Admin | No extra properties |
| `admin.login_failed` | System | None | `email_attempted` in `properties` |

## `brags`: Brag Lifecycle

`internal` unless noted; verification events gain `management` when an org is involved.

| Event | Causer | Subject | Notes |
|-------|--------|---------|-------|
| `brag.created` | User | Brag | No extra properties |
| `brag.updated` | User | Brag | Changed fields only |
| `brag.deleted` | User | Brag | Full before state |
| `brag.visibility_changed` | User | Brag | `old_visibility`, `new_visibility` |
| `brag.verification_requested` | User | Brag | `organization_id` set for org type |
| `brag.verification_approved` | User | Brag | `organization_id` set for org type; also `management`. Never recorded for a self-approval, which is rejected |
| `brag.verification_rejected` | User | Brag | `organization_id` set for org type; also `management` |
| `brag.unlocked` | User | Brag | Removes all verifications, always logged |
| `brag.collaborator_invited` | User | Brag | No extra properties |
| `brag.collaborator_accepted` | User | Brag | No extra properties |
| `brag.collaborator_declined` | User | Brag | No extra properties |
| `brag.collaborator_removed` | User | Brag | Initiator (owner or collaborator) in `properties` |
| `brag.admin_removed` | Admin | Brag | Full before state; reason in `properties` |

## `organizations`: Org Settings & Membership

| Event | Causer | Subject | Visibility |
|-------|--------|---------|-----------|
| `org.created` | User | Organization | internal |
| `org.updated` | User | Organization | internal, management |
| `org.dns_verified` | System | Organization | internal, management |
| `org.dns_verification_failed` | System | Organization | internal |
| `org.dns_revoked` | System | Organization | internal, management |
| `org.member_invited` | User | OrgInvitation | management: `member_type` in `properties` |
| `org.guest_expired` | System | OrgMember | management |
| `org.member_joined` | User | OrgMember | management |
| `org.member_removed` | User | OrgMember | management: `removed_by` in `display` |
| `org.ownership_transferred` | User | Organization | internal, management |
| `org.sso_authenticated` | User | Organization | internal |
| `org.webhook_created` | User | OrgWebhook | management |
| `org.webhook_updated` | User | OrgWebhook | management |
| `org.webhook_deleted` | User | OrgWebhook | management |

## `teams`: Team & Permission Management

All `management` visibility.

| Event | Causer | Subject | Notes |
|-------|--------|---------|-------|
| `team.created` | User | OrgTeam | No extra properties |
| `team.updated` | User | OrgTeam | No extra properties |
| `team.deleted` | User | OrgTeam | No extra properties |
| `team.member_added` | User | OrgTeam | No extra properties |
| `team.member_removed` | User | OrgTeam | No extra properties |
| `team.permissions_changed` | User | OrgTeam | Most sensitive: old and new permission sets in `properties` |

## `jobs`: Job Postings & Matching

Posting events are `internal` + `management` (the org's own history); `job.interest_expressed` is `management`.

| Event | Causer | Subject | Notes |
|-------|--------|---------|-------|
| `job.created` | User/System | JobPosting | System causer for Push API creates; `source` in `properties` |
| `job.updated` | User/System | JobPosting | Changed fields only |
| `job.published` | User | JobPosting | Review confirmation for API-sourced postings |
| `job.closed` | User | JobPosting | No extra properties |
| `job.deleted` | User | JobPosting | Full before state |
| `job.interest_expressed` | User | JobPosting | No extra properties |
| `admin.job_removed` | Admin | JobPosting | internal only: reason + full before state |

## `billing`: Subscriptions & Payments

`internal` + `management` so orgs can see their own subscription history.

| Event | Causer | Subject |
|-------|--------|---------|
| `billing.subscription_created` | User/System | User or Organization |
| `billing.subscription_upgraded` | User/System | User or Organization |
| `billing.subscription_downgraded` | User/System | User or Organization |
| `billing.subscription_canceled` | User/System | User or Organization |
| `billing.payment_succeeded` | System | User or Organization |
| `billing.payment_failed` | System | User or Organization |

## `gdpr`: Consent & Data Subject Rights

All `internal`, never visible to orgs.

| Event | Causer | Subject | Notes |
|-------|--------|---------|-------|
| `consent.given` | User | User | `consent_type`, `policy_version` in `properties` |
| `consent.withdrawn` | User | User | `consent_type` in `properties` |
| `data_export.requested` | User | User | No extra properties |
| `data_export.completed` | System | User | No extra properties |
| `account_deletion.requested` | User | User | No extra properties |
| `account_deletion.canceled` | User | User | No extra properties |
| `account_deletion.tier1_applied` | System | User | No extra properties |
| `account_deletion.tier2_applied` | System | User | No extra properties |
| `account_deletion.completed` | System | User | No extra properties |

## `security`: Incident Register

All `internal`.

| Event | Causer | Subject |
|-------|--------|---------|
| `security_incident.created` | Admin | SecurityIncident |
| `security_incident.updated` | Admin | SecurityIncident |
| `security_incident.resolved` | Admin | SecurityIncident |
| `security_incident.notifications_sent` | System | SecurityIncident |

## `admin`: Admin Panel Actions

All `internal`.

| Event | Causer | Subject | Notes |
|-------|--------|---------|-------|
| `admin.user_suspended` | Admin | User | No extra properties |
| `admin.user_restored` | Admin | User | No extra properties |
| `admin.org_suspended` | Admin | Organization | No extra properties |
| `admin.org_restored` | Admin | Organization | No extra properties |
| `admin.brag_removed` | Admin | Brag | Reason in `properties` |
| `admin.user_data_viewed` | Admin | User | Always logged: even with no changes |
| `admin.admin_created` | Admin | Admin | No extra properties |
| `admin.admin_permissions_changed` | Admin | Admin | No extra properties |

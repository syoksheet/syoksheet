# Organizations: Database Schema

Tables for organisations, membership, teams and permissions, join requests, departures, ownership transfers, DNS verification, and SSO configuration.

## 🏢 organizations

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | Public identifier |
| name | varchar(255) | Required |
| slug | varchar(255) | Unique |
| domain | varchar(255) | Unique |
| contact_email | varchar(255) | Required |
| logo_url | varchar(500) | Nullable, `syoksheet-public-{env}` |
| description | text | Nullable |
| industry_id | bigint FK → industries | Required. See [taxonomy.md](taxonomy.md) |
| company_size | varchar(50) | Nullable |
| website_url | varchar(500) | Nullable |
| country_id | bigint FK → countries | Nullable. See [location.md](location.md) |
| state_id | bigint FK → states | Nullable |
| city_id | bigint FK → cities | Nullable |
| founded_year | smallint | Nullable |
| social_links | jsonb | Nullable |
| branding | jsonb | Nullable: `{ accent_color, cover_image_url }` (Business); rendered on the wall + org verifier pages |
| is_dns_verified | boolean | Default false |
| dns_verified_at | timestamptz | Nullable |
| plan | varchar(20) | Default `free` (`free`, `business`) |
| suspended_at | timestamptz | Nullable: admin suspension; suspended orgs are 404 on public pages |
| created_at, updated_at | timestamptz | Managed by Eloquent |
| deleted_at | timestamptz | Soft delete |

## 👥 org_members

One row per user per organisation. `is_owner` is the special system designation: all other access comes from team membership.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| user_id | uuid FK → users | CASCADE |
| user_email_id | bigint FK → user_emails | Work email used to join |
| is_owner | boolean | Default false. Exactly one true per org. Full control, can transfer. |
| sso_subject | varchar(255) | Nullable, the IdP's stable subject ID, bound on first successful SSO; matched by subject thereafter |
| joined_at | timestamptz | Required |
| created_at, updated_at | timestamptz | Managed by Eloquent |

**Unique:** `(organization_id, user_id)`

## 🏷️ org_teams

Named permission groups. A member's access is the union of all their team permissions.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| name | varchar(100) | Display name |
| slug | varchar(100) | Standard |
| permissions | jsonb | Array of permission strings, e.g. `["verification.approve", "billing.manage"]` |
| is_default | boolean | Default false. Default teams cannot be deleted, only modified. |
| created_at, updated_at | timestamptz | Managed by Eloquent |

**Unique:** `(organization_id, slug)`

Default teams seeded per org on creation: Admin, People & Culture, Marketing, Finance, Hiring.

## 👤 org_team_members

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| org_team_id | bigint FK → org_teams | CASCADE |
| org_member_id | bigint FK → org_members | CASCADE |
| created_at | timestamptz | Set on insert |

**Unique:** `(org_team_id, org_member_id)`

## 🌐 org_domains

Additional whitelisted domains (subsidiaries, regional) usable for joining.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| domain | varchar(255) | Standard |
| created_at | timestamptz | Set on insert |

**Unique:** `(organization_id, domain)`

## ✉️ org_invitations

Invites sent by the org to an email address; the inverse of join requests.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| email | varchar(255) | Invitee: need not be a user yet |
| invited_by | uuid FK → users | Required |
| token | varchar(255) | Unique: accept link |
| status | varchar(20) | `pending`, `accepted`, `declined`, `expired` |
| expires_at | timestamptz | 30 days from creation |
| responded_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

**Unique:** `(organization_id, email)` while `pending`

## 📥 org_join_requests

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| user_id | uuid FK → users | CASCADE |
| user_email_id | bigint FK → user_emails | Matching work email |
| status | varchar(20) | `pending`, `approved`, `rejected` |
| reviewed_by | uuid FK → users | Nullable |
| reviewed_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🚪 org_departures

Scheduled or completed member departures (7-day notice).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| user_id | uuid FK → users | CASCADE |
| initiated_at | timestamptz | Required |
| effective_at | timestamptz | `initiated_at` + 7 days |
| cancelled_at | timestamptz | Nullable |
| status | varchar(20) | `pending`, `completed`, `cancelled` |
| created_at | timestamptz | Set on insert |

## 🔄 ownership_transfers

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| from_user_id | uuid FK → users | Required |
| to_user_id | uuid FK → users | Any current member |
| status | varchar(20) | `pending`, `accepted`, `expired`, `cancelled` |
| expires_at | timestamptz | 7 days from creation |
| responded_at | timestamptz | Nullable |
| created_at | timestamptz | Set on insert |

One `pending` transfer per org at a time.

## 📌 org_wall_pins

Business orgs pin brags to the top of their public wall (limit in syoksheet-docs → product/pricing.md).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| brag_id | uuid FK → brags | CASCADE |
| position | smallint | Display order |
| created_at | timestamptz | Set on insert |

**Unique:** `(organization_id, brag_id)`

## 🌐 dns_verifications

DNS TXT verification attempts and the re-verification state machine.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| initiated_by | uuid FK → users | Required |
| txt_record | varchar(255) | `syoksheet-verify={org_code}-{user_code}` |
| status | varchar(20) | `pending`, `verified`, `failed` |
| verified_at | timestamptz | Nullable |
| last_checked_at | timestamptz | Nullable |
| next_check_at | timestamptz | Nullable |
| grace_period_ends_at | timestamptz | Nullable: 14-day grace on re-verification failure |
| failure_count | int | Default 0, max 18 initial attempts |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 📡 org_webhooks

Outbound webhook endpoints (Business). See [features/organizations/webhooks.md](../features/organizations/webhooks.md).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE |
| url | varchar(500) | https only |
| secret | text | Encrypted. HMAC signing key, shown once at creation |
| events | jsonb | Subscribed event names |
| is_active | boolean | Default true; auto-disabled after 20 consecutive failures |
| consecutive_failures | int | Default 0; reset on success |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 📬 org_webhook_deliveries

One row per delivery attempt cycle; pruned after 30 days.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| org_webhook_id | bigint FK → org_webhooks | CASCADE |
| event | varchar(100) | Required |
| payload | jsonb | Required |
| status | varchar(20) | `pending`, `delivered`, `failed` |
| attempts | smallint | Default 0, max 5 |
| response_code | smallint | Nullable: last HTTP status |
| last_attempted_at | timestamptz | Nullable |
| delivered_at | timestamptz | Nullable |
| created_at | timestamptz | Set on insert |

## 🔐 sso_configs

Per-org OIDC identity provider configuration (Business tier).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| organization_id | uuid FK → organizations | CASCADE, unique, one config per org |
| oidc_client_id | varchar(255) | Required |
| oidc_client_secret | text | Encrypted |
| oidc_discovery_url | varchar(500) | Required |
| claim_mapping | jsonb | Nullable: nonstandard claim names per IdP |
| is_enabled | boolean | Default false |
| created_at, updated_at | timestamptz | Managed by Eloquent |

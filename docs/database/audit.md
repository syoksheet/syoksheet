# Audit — Database Schema

Tables on the **audit database** (`log` connection — separate DigitalOcean Managed PostgreSQL instance). Append-only, forever retention, no FK constraints, no soft deletes, no `updated_at`.

> [!NOTE]
> This is a separate instance from the primary database. Migrations run against the `log` connection. References to users, orgs, and other models are stored as raw IDs — no cross-database FK constraints.

## 📋 audit_logs

Every significant platform event, written via `spatie/laravel-activitylog` configured for the `log` connection. See [features/audit/implementation.md](../features/audit/implementation.md) for the write path and [features/audit/events.md](../features/audit/events.md) for the full catalog.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| log_name | varchar(100) | Domain: `auth`, `brags`, `organizations`, `teams`, `billing`, `gdpr`, `security`, `admin` |
| event | varchar(100) | Dot notation: `brag.verification_approved`, `team.permissions_changed` |
| causer_type | varchar(255) | Nullable — `App\Models\User`, `App\Models\Admin`, `System` |
| causer_id | uuid | Nullable — null for System events; set null on account erasure |
| subject_type | varchar(255) | Nullable — model class of the entity acted on |
| subject_id | varchar(36) | Nullable — uuid or bigint as string |
| organization_id | uuid | Nullable — org context, enables org-scoped audit views |
| visibility | varchar(20) | `internal` (admin only) or `management` (org Management team) |
| properties | jsonb | Event data — changed fields, before/after state, metadata |
| display | jsonb | Structured rendering context: `{ actor, subject, context }` |
| ip_address | varchar(45) | Nullable — null for System events |
| user_agent | text | Nullable — null for System events |
| created_at | timestamptz | |

**Indexes:** `(log_name, event)`, `(causer_type, causer_id)`, `(subject_type, subject_id)`, organization_id, created_at

**On account erasure:** `causer_id` set null, personal fields in `properties` and `display` anonymised. Records are never deleted.

## 🚨 security_incidents

GDPR breach register — minimal compliance record only; operational incident management lives in external tooling, linked via `incident_url`.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| title | varchar(255) | |
| description | text | |
| severity | varchar(20) | `low`, `medium`, `high`, `critical` |
| status | varchar(20) | `open`, `investigating`, `resolved` |
| incident_type | varchar(100) | e.g. `data_breach`, `unauthorised_access` |
| discovered_at | timestamptz | Starts the 72-hour GDPR notification clock |
| resolved_at | timestamptz | Nullable |
| notification_sent_at | timestamptz | Nullable — when affected users/orgs were notified |
| incident_url | varchar(500) | Nullable — link to the operational incident |
| affected_data | text | Nullable — narrative of what data was involved |
| created_at | timestamptz | No `updated_at` |

## 🔗 security_incident_affected_records

Links incidents to affected users and organisations; drives notification targeting. Affected counts are always derived at query time, never stored.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| security_incident_id | bigint FK → security_incidents | Same database, FK allowed |
| affected_type | varchar(50) | `User` or `Organization` |
| affected_id | uuid | Raw ID — no cross-database FK |
| created_at | timestamptz | |

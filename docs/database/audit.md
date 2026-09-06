# Audit: Database Schema

Tables on the **audit database** (`audit` connection). Append-only, forever retention, no FK constraints, no soft deletes, no `updated_at`.

> [!NOTE]
> This is a **separate database** from the primary, not a schema within it. Postgres cannot join or foreign-key across databases, which is what structurally enforces "references are stored as raw IDs". A schema would permit an accidental FK to `users` that quietly makes the eventual split to its own cluster impossible. Migrations run against the `audit` connection with their own path and history.

The audit database sits on the same managed cluster as the primary, as a separate database rather than a schema. That is what the raw-ID rule needs: Postgres cannot foreign-key across databases within a cluster any more than across clusters. A separate cluster would additionally give it its own failure domain, which the daily dump and the monthly `audit:archive` already largely cover; moving it there later is a `pg_dump syoksheet_audit` and a restore, with no application change.

## 🔐 Database Users

Append-only is enforced by Postgres permissions, not by application convention, so a bug, or a compromised app server, still cannot rewrite history.

| User | Grants | Used by |
|------|--------|---------|
| Application | `INSERT`, `SELECT` | `AuditLogJob` and every read path. **No `UPDATE`, no `DELETE`, ever** |
| Erasure | `UPDATE` on the anonymisable columns only | `gdpr:anonymize-accounts`, and nothing else |

> [!WARNING]
> The second user exists because append-only and GDPR erasure genuinely conflict: erasure must modify existing rows (null the `causer_id`, strip personal fields from `properties` and `display`), which the application user cannot do. Granting `UPDATE` to the application user instead would dissolve the guarantee entirely. Splitting the identity keeps "the app cannot rewrite history" true while permitting the one modification the law requires, and makes the erasure path auditable in its own right, since it acts as a distinct database identity.

## 📋 audit_logs

Every significant platform event, written via `spatie/laravel-activitylog` configured for the `audit` connection. See [features/audit/implementation.md](../features/audit/implementation.md) for the write path and [features/audit/events.md](../features/audit/events.md) for the full catalog.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| log_name | varchar(100) | Domain: `auth`, `brags`, `organizations`, `teams`, `billing`, `gdpr`, `security`, `admin` |
| event | varchar(100) | Dot notation: `brag.verification_approved`, `team.permissions_changed` |
| causer_type | varchar(255) | Nullable: `App\Models\User`, `App\Models\Admin`, `System` |
| causer_id | uuid | Nullable: null for System events; set null on account erasure |
| subject_type | varchar(255) | Nullable: model class of the entity acted on |
| subject_id | varchar(36) | Nullable: uuid or bigint as string |
| organization_id | uuid | Nullable: org context, enables org-scoped audit views |
| visibility | varchar(20) | `internal` (admin only) or `management` (org Management team) |
| properties | jsonb | Event data: changed fields, before/after state, metadata |
| display | jsonb | Structured rendering context: `{ actor, subject, context }` |
| ip_address | varchar(45) | Nullable: null for System events |
| user_agent | text | Nullable: null for System events |
| created_at | timestamptz | Set on insert |

**Indexes:** `(log_name, event)`, `(causer_type, causer_id)`, `(subject_type, subject_id)`, organization_id, created_at

**On account erasure:** `causer_id` set null, personal fields in `properties` and `display` anonymized: performed by the **erasure user**, never the application user. Records are never deleted.

## 🚨 security_incidents

GDPR breach register: minimal compliance record only; operational incident management lives in external tooling, linked via `incident_url`.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| title | varchar(255) | Required |
| description | text | Required |
| severity | varchar(20) | `low`, `medium`, `high`, `critical` |
| status | varchar(20) | `open`, `investigating`, `resolved` |
| incident_type | varchar(100) | e.g. `data_breach`, `unauthorized_access` |
| discovered_at | timestamptz | Starts the 72-hour GDPR notification clock |
| resolved_at | timestamptz | Nullable |
| notification_sent_at | timestamptz | Nullable: when affected users/orgs were notified |
| incident_url | varchar(500) | Nullable: link to the operational incident |
| affected_data | text | Nullable: narrative of what data was involved |
| created_at | timestamptz | No `updated_at` |

## 🔗 security_incident_affected_records

Links incidents to affected users and organizations; drives notification targeting. Affected counts are always derived at query time, never stored.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| security_incident_id | bigint FK → security_incidents | Same database, FK allowed |
| affected_type | varchar(50) | `User` or `Organization` |
| affected_id | uuid | Raw ID, no cross-database FK |
| created_at | timestamptz | Set on insert |

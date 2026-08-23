# Security Incidents — Implementation

The GDPR breach register on the audit database. Product behaviour (what counts as an incident, severity, lifecycle) in syoksheet-docs → features/privacy.md.

## 🔌 Endpoints

All require `security_incidents.manage` (Compliance / Super Admin).

| Route | Behaviour |
|-------|-----------|
| `GET /api/admin/v1/security-incidents` | List with live 72-hour countdown data for open High/Critical |
| `POST /api/admin/v1/security-incidents` | Create — title, description, severity, incident_type, discovered_at (starts the clock), incident_url? |
| `GET /api/admin/v1/security-incidents/{incident}` | Detail + affected records |
| `PATCH /api/admin/v1/security-incidents/{incident}` | Status changes, resolution (`resolved_at`), `notification_sent_at` |
| `POST /api/admin/v1/security-incidents/{incident}/affected-records` | Attach `{ affected_type: User\|Organization, affected_id }` entries |
| `POST /api/admin/v1/security-incidents/{incident}/notify` | Send notifications to affected users (direct) and orgs (owner + Admin team); sets `notification_sent_at`, fires `security_incident.notifications_sent` |

Affected counts are computed at query time from the pivot — never stored or manually entered.

## ⏱️ 72-Hour Reminder Job

`security:check-incidents` (hourly) — for `high`/`critical` incidents with `status != resolved`:

- 48 h remaining from `discovered_at` → internal email reminder to Compliance.
- 24 h remaining → second reminder.

See [scheduled-jobs.md](../../scheduled-jobs.md).

## 📏 Rules

- Records live on the audit DB — append-only, no `updated_at`, no soft deletes. Status transitions append audit events rather than mutating history where possible.
- All incident data is `internal` — never exposed to org users beyond the notifications themselves.
- Operational detail belongs in the external tool behind `incident_url`; specific affected data records are described in the `affected_data` narrative field, not the pivot.

## 📋 Audit Events

`security_incident.created/updated/resolved/notifications_sent` — see [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/audit.md](../../database/audit.md).

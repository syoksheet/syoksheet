# Audit Log — Implementation

The write path, record contract, and rendering model for the audit log. What it means for users and orgs is product-level — see syoksheet-docs → features/audit-log.md.

## 🏗️ Architecture

- **Separate database** — `log` connection; a separate DO Managed PostgreSQL instance. Migrations run against `log`.
- **Package** — `spatie/laravel-activitylog` configured to write on the `log` connection.
- **Append-only** — no `updated_at`, no soft deletes, no FK constraints; records are never modified.
- **Forever retention** — no pruning, ever. `audit:archive` (monthly) dumps the database to a dedicated R2 archival bucket, since 7-day managed backups are insufficient disaster recovery for a forever-retention store.

## ⚡ Event Fan-Out

One domain event fires → independent queued listeners route to their destinations:

| Listener | Queue priority | Writes to |
|----------|---------------|-----------|
| `AuditLogJob` | Highest — retries forever | Audit DB (`log` connection) |
| `NotificationJob` | Medium | Primary DB (`notifications`) |

Controllers and models never write to the audit DB directly — always through the event → `AuditLogJob` path.

## 📐 Record Contract

Every record: `log_name` (domain), `event` (dot notation), causer (`User`/`Admin`/`System`), subject, optional `organization_id` (enables org-scoped views), `visibility`, `properties`, `display`, `ip_address`, `user_agent`. Column details in [database/audit.md](../../database/audit.md).

**Changed-fields policy**

- Standard events → `properties` holds only changed fields (before + after).
- Destructive events (delete, anonymise) → full before state; there is no after.
- Auth events → IP + user agent always captured.
- System events → causer null, IP/user agent null.

**Visibility** — one level per record: `internal` (admin teams) or `management` (org owner + Admin team). Events relevant to both are either written twice with different visibility or filtered at query time via `organization_id`.

## 🖥️ Display Rendering

`display` stores structured data, never prose:

```json
{
  "actor":   { "id": "uuid", "type": "User", "name": "Tenesh Raj" },
  "subject": { "id": "uuid", "type": "Brag", "label": "Led migration to Kubernetes" },
  "context": { "key": "value" }
}
```

The frontend maps `log_name + event` to a display template (`brag.verification_approved` → "{{actor.name}} approved verification of {{subject.label}}"). Templates live in the frontend — text changes need no data changes. Multilingual-ready.

## 🔎 Contextual Activity Views & the Org Activity Stream

The audit log is the single source of truth for activity. Contextual views (brag history, org audit view) query it filtered by subject or `organization_id` — no separate activity tables.

The **org activity stream** is the live surface over the same data: `GET /api/v1/organizations/{org}/activity` (Management team) serves the cursor-paginated backlog, and after `AuditLogJob` persists a `management`-visibility event with an `organization_id`, it broadcasts the event's `display` payload on the org's private Reverb channel (`org.{id}.activity`, Management-authorized). The broadcast happens only after the durable write succeeds — the stream can never show an event the audit log doesn't hold.

## 🔒 Erasure Handling

On account deletion (Tier 2): `causer_id` → null; personal fields in `properties` and `display` → `[Deleted User]`. Records are never deleted. See [../privacy/account-deletion.md](../privacy/account-deletion.md).

## 📋 Events

Full catalog: [events.md](events.md). When planning a new feature, add its events to the catalog **before** implementation.

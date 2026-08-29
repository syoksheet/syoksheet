# Notifications: Endpoints

In-app notifications for users and admins, stored in the single morph `notifications` table. Trigger catalog lives in syoksheet-docs → features/notifications.md.

## 🔌 User Endpoints

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/me/notifications` | Paginated list |
| `GET /api/v1/me/notifications/unread-count` | `{ count }` for the bell badge |
| `POST /api/v1/me/notifications/read-all` | Mark all read |
| `PATCH /api/v1/me/notifications/{notification}` | Mark one read/unread |
| `DELETE /api/v1/me/notifications/{notification}` | Delete one |

## 🔌 Admin Endpoints

Identical shape under `/api/admin/v1/me/notifications` (+ `unread-count`, `read-all`, `{notification}`) for the `Admin` notifiable.

## ⚙️ Preferences

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/me/notification-preferences` | Current per-category email toggles (defaults: all on) |
| `PUT /api/v1/me/notification-preferences` | `{ verification?: {email: bool}, collaboration?: {...}, organization?: {...}, jobs?: {...} }` |

Stored in `users.notification_preferences` (jsonb, null = all on). Only the four listed categories are accepted: auth/security, billing, and GDPR notifications are not preference-gated, and marketing email is a consent (see [../privacy/consent.md](../privacy/consent.md)).

## ⚙️ Implementation

- Written by `NotificationJob` (medium-priority queue) from domain events. See [../audit/implementation.md](../audit/implementation.md) for the event fan-out. Before dispatching the **email** channel, the job checks the recipient's category preference; in-app always delivers.
- `data` jsonb carries `title`, `body`, `action_url`, plus event-specific fields.
- Real-time delivery over Laravel Reverb channels; unread counts update live.
- `notifications:cleanup` prunes rows older than 90 days. See [scheduled-jobs.md](../../scheduled-jobs.md).

## 🗄️ Tables

See [database/users.md](../../database/users.md).

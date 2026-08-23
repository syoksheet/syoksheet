# Admin — Database Schema

Tables for internal admin accounts and Spatie Laravel Permission RBAC.

## 🛡️ admins

Completely separate from `users` — different table, guard, and tokens. No soft deletes, no profile fields.

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| name | varchar(255) | |
| email | varchar(255) | Unique, login identifier |
| password | varchar(255) | Always required — no social login |
| remember_token | varchar(100) | |
| created_at, updated_at | timestamptz | |

## 🔑 Spatie Permission Tables

Installed via `spatie/laravel-permission`, all scoped to the `admin` guard. Used exclusively for admin RBAC — **not** for org team permissions.

| Table | Columns |
|-------|---------|
| roles | id, name, guard_name, timestamps |
| permissions | id, name, guard_name, timestamps |
| model_has_roles | role_id, model_type, model_id |
| model_has_permissions | permission_id, model_type, model_id |
| role_has_permissions | permission_id, role_id |

> [!NOTE]
> Org teams use a separate custom model (`org_teams`, `org_team_members`) with a `permissions` jsonb column — see [organizations.md](organizations.md). The two systems never interact.

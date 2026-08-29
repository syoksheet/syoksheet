# Admin: Endpoints & Implementation

All admin routes, provisioning, and RBAC wiring. The team/permission matrix is product-level. See syoksheet-docs → features/admin-panel.md.

## 🏗️ Architecture

Separate `Admin` model on the `admins` table. Two auth paths: the admin session guard for the Inertia UI on `admin.*`, and Sanctum bearer tokens (`admin:api` ability) for scripts. Spatie Laravel Permission manages roles/permissions on the `admin` guard.

**Guard isolation**: enforced at the middleware layer, bidirectionally:

- `/api/admin/v1/*`: `auth:sanctum` → `EnsureAdmin`. A `User` token → 403.
- `/api/v1/*`: `auth:sanctum` → `EnsureUser`. An `Admin` token → 403.

## 🛠️ Provisioning

- First super-admin: `ddev php artisan admin:create` (interactive prompts).
- Subsequent admins: `POST /api/admin/v1/admins` by a Super Admin.

## 🔌 Endpoints

### Auth & Profile

| Route | Behaviour |
|-------|-----------|
| `POST /api/admin/auth/login` | Admin session login (Inertia web route on `admin.*`) |
| `POST /api/admin/auth/logout` | Clears session (cookie) or revokes token (bearer) → 204 |
| `GET /api/admin/v1/me` | Authenticated admin profile |
| `POST /api/admin/v1/me/confirm-password` | Required before token create/revoke → 201 |
| `GET/POST /api/admin/v1/me/tokens`, `DELETE .../tokens/{token}` | Own bearer tokens: plain text shown once, `admin:api` ability |

### Admin Accounts (Super Admin)

| Route | Behaviour |
|-------|-----------|
| `GET /api/admin/v1/admins` | List |
| `POST /api/admin/v1/admins` | Create `{ name, email, password, role }` |
| `DELETE /api/admin/v1/admins/{admin}` | Hard delete → 204 |

### Users

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/admin/v1/users` | `users.view` | Search/list (`?search=`), paginated |
| `GET /api/admin/v1/users/{user}` | `users.view` | Profile: logged as `admin.user_data_viewed` |
| `PATCH /api/admin/v1/users/{user}` | `users.edit` | Update; `{ suspended: bool }` (sets/clears `suspended_at`) requires `users.suspend`: suspended users are 404 on public surfaces, 403 on login |
| `POST /api/admin/v1/users/{user}/impersonate` | `users.impersonate` | Impersonation token. See [impersonation.md](impersonation.md) |

### Content & Verification

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/admin/v1/brags` | `brags.view` | List/search |
| `DELETE /api/admin/v1/brags/{brag}` | `brags.delete` | Force delete (`brag.admin_removed`, reason + full before state) → 204 |
| `GET /api/admin/v1/verifications` | `verifications.view` | List requests |
| `PATCH /api/admin/v1/verifications/{id}` | `verifications.manage` | `{ action: approve\|reject, reason? }` |
| `GET /api/admin/v1/jobs` | `jobs.moderate` | List/search job postings |
| `DELETE /api/admin/v1/jobs/{job}` | `jobs.moderate` | Force remove (`admin.job_removed`, reason + before state) → 204 |

### Organisations & Billing

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/admin/v1/organizations` | `organizations.view` | List |
| `PATCH /api/admin/v1/organizations/{org}` | `organizations.manage` / `organizations.suspend` | `{ is_dns_verified?, suspended? }`: suspension sets `suspended_at`, org 404s publicly |
| `GET /api/admin/v1/billing/users/{user}` | `billing.view` | User subscription details |
| `GET /api/admin/v1/billing/organizations/{org}` | `billing.view` | Org subscription details |

### Compliance

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/admin/v1/audit-log` | `audit_log.view` | All events, filterable by domain, event, causer, subject, date |
| `GET /api/admin/v1/audit-log/export` | `audit_log.export` | CSV |
| `GET /api/admin/v1/gdpr/exports`, `GET /api/admin/v1/gdpr/deletions` | `gdpr.manage` | Monitor request pipelines |
| `GET/POST /api/admin/v1/security-incidents`, `PATCH .../{incident}` | `security_incidents.manage` | Breach register. See [../privacy/security-incidents.md](../privacy/security-incidents.md) |

### Taxonomy

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/admin/v1/taxonomy/review-queue` | `taxonomy.manage` | Pending dedup merges |
| `PATCH /api/admin/v1/taxonomy/review-queue/{item}` | `taxonomy.manage` | `{ action: approve\|reject\|skip }` |
| `POST /api/admin/v1/taxonomy/{occupations\|skills\|industries}` | `taxonomy.manage` | Manual additions |

## 📋 Audit Events

`admin` domain, all `internal`. See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/admin.md](../../database/admin.md).

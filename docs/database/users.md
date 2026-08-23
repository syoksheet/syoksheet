# Users — Database Schema

Tables for user accounts, email addresses, social logins, password resets, and in-app notifications.

## 👤 users

There is **no `email` column** — all emails live in `user_emails`.

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| name | varchar(255) | Required |
| username | varchar(100) | Unique, nullable (set during profile setup) |
| custom_slug | varchar(30) | Unique, nullable — Pro custom wall URL; cleared on downgrade fallback |
| avatar_url | varchar(500) | Nullable, R2 |
| bio | text | Nullable |
| current_role | varchar(255) | Nullable, freeform display headline |
| current_company | varchar(255) | Nullable, freeform display |
| country_id | bigint FK → countries | Nullable — see [location.md](location.md) |
| state_id | bigint FK → states | Nullable |
| city_id | bigint FK → cities | Nullable |
| website_url | varchar(500) | Nullable |
| social_links | jsonb | Nullable |
| password | varchar(255) | Nullable (null for Google-only accounts) |
| email_verified_at | timestamptz | Nullable |
| two_factor_secret | text | Nullable, encrypted |
| two_factor_recovery_codes | text | Nullable, encrypted |
| two_factor_confirmed_at | timestamptz | Nullable |
| locale | varchar(15) | Default `en` — BCP 47; see [localization.md](../localization.md) |
| plan | varchar(20) | Default `free` (`free`, `pro`) |
| is_open_to_work | boolean | Default false — requires JobMatching + AiProcessing consents to enable |
| notification_preferences | jsonb | Nullable — per-category email toggles, e.g. `{"verification": {"email": false}}`; null = all on. Categories: `verification`, `collaboration`, `organization`, `jobs` |
| suspended_at | timestamptz | Nullable — set by admin suspension and deletion cooling-off; suspended users are 404 on public surfaces, 403 on login |
| remember_token | varchar(100) | |
| created_at, updated_at | timestamptz | |
| deleted_at | timestamptz | Soft delete |

**Indexes:** unique username, unique custom_slug, index plan, index is_open_to_work, index deleted_at, index country_id

## 📧 user_emails

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | uuid FK → users | CASCADE |
| email | varchar(255) | Unique across the platform |
| type | varchar(20) | `primary`, `backup`, `work` |
| is_verified | boolean | Default false |
| verified_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | |

One `primary` and at most one `backup` per user; unlimited `work` emails.

## 🔗 social_accounts

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | uuid FK → users | CASCADE |
| provider | varchar(50) | `google` |
| provider_id | varchar(255) | Unique per provider |
| provider_data | jsonb | Nullable |
| created_at, updated_at | timestamptz | |

## 🔑 password_reset_tokens

Standard Laravel: email (PK), token, created_at.

## 🔔 notifications

Single table covering all notifiable types via morph columns.

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| notifiable_type | varchar(255) | `App\Models\User`, `App\Models\Admin`, `App\Models\Organization` |
| notifiable_id | uuid | Matches the UUID PKs of all notifiable models |
| type | varchar(255) | Fully-qualified notification class name |
| data | jsonb | `title`, `body`, `action_url`, plus event-specific fields |
| read_at | timestamptz | Null = unread |
| created_at | timestamptz | No `updated_at` |

**Index:** composite on `(notifiable_type, notifiable_id, read_at)`

Rows older than 90 days are pruned by `notifications:cleanup` — see [scheduled-jobs.md](../scheduled-jobs.md).

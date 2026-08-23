# Account Deletion — Implementation

The GDPR Article 17 erasure pipeline: suspension, cooling off, and three-tier erasure. Product behaviour in syoksheet-docs → features/privacy.md.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/account/delete` | Request deletion — 422 while the user owns any org. On success: all sessions revoked, tokens deleted, account suspended, request row created (`status: cooling_off`, `cooling_off_ends_at = now + 30 days`), confirmation email sent |
| `POST /api/v1/me/account/delete/cancel` | During cooling off only — restores the account fully (`status: cancelled`) |

While suspended: login rejected except for the cancellation flow; profile excluded from walls, search, and public endpoints.

## ⚙️ Tier 1 — Hard Deletes

`gdpr:process-deletions` (daily) picks up requests past `cooling_off_ends_at`:

- `personal_access_tokens` — all deleted
- `social_accounts` — deleted
- `password`, `two_factor_secret`, `two_factor_recovery_codes` — nulled
- Avatar — R2 object deleted, `avatar_url` nulled
- `notifications` — deleted
- `org_members` rows — removed
- `job_interests` and `match_scores` — deleted; `is_open_to_work` forced false
- `custom_slug` and `notification_preferences` — cleared

Status → `tier1_complete`. **Irreversible from here.**

## ⚙️ Tier 2 — Anonymisation

`gdpr:anonymise-accounts` (daily) processes `tier1_complete` records within 30 days:

| Data | Action |
|------|--------|
| `users.name` | → `[Deleted User]` |
| `users` bio, current_role, current_company, website_url, social_links, country/state/city | → null |
| `user_emails` | Anonymised or removed (structure retained if referenced by verification records) |
| Published brags | title → `[Deleted]`, description → `[Content removed]`, place_text → `[Location removed]`; tags, links, attachments deleted |
| `consent_records.user_id` | → null (records retained) |
| `audit_logs` (audit DB) | `causer_id` → null; personal fields in `properties`/`display` → `[Deleted User]` |

Status → `tier2_complete` → `completed`; completion email sent. **Every new field containing personal data must be handled here.**

## ⚙️ Tier 3 — Retained Anonymised (permanent)

| Record | Retained as |
|--------|-------------|
| `verifications` | "[Deleted User] verified…" — the org's verification history |
| `verification_requests` | Anonymous reference |
| Audit log structure | Event records, identity anonymised |

## 📧 Emails

Requested (confirmation + deadline + cancel link), cancelled (restoration), completed (final confirmation) — all noreply@.

## 📋 Audit Events

`account_deletion.requested/cancelled/tier1_applied/tier2_applied/completed` — see [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/privacy.md](../../database/privacy.md).

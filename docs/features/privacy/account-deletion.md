# Account Deletion: Implementation

The GDPR Article 17 erasure pipeline: suspension, cooling off, and three-tier erasure. Product behaviour in syoksheet-docs → features/privacy.md.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/account/delete` | Request deletion: 422 while the user owns any org. On success: all sessions revoked, tokens deleted, account suspended, request row created (`status: cooling_off`, `cooling_off_ends_at = now + 30 days`), confirmation email sent |
| `POST /api/v1/me/account/delete/cancel` | During cooling off only: restores the account fully (`status: cancelled`) |

While suspended: login rejected except for the cancellation flow; profile excluded from walls, search, and public endpoints.

## ⚙️ Tier 1: Hard Deletes

`gdpr:process-deletions` (daily) picks up requests past `cooling_off_ends_at`:

- `personal_access_tokens`: all deleted
- `social_accounts`: deleted
- `password`: nulled. Passkey credentials: deleted
- Avatar. Object deleted from `syoksheet-public-{env}`, `avatar_url` nulled
- `notifications`: deleted
- `org_members` rows: removed
- `job_interests` and `match_scores`: deleted; `is_open_to_work` forced false
- `custom_slug` and `notification_preferences`: cleared

Status → `tier1_complete`. **Irreversible from here.**

## ⚙️ Tier 2: Anonymisation

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

## ⚙️ Tier 3: Retained Anonymised (permanent)

| Record | Retained as |
|--------|-------------|
| `verifications` | "[Deleted User] verified…", the org's verification history |
| `verification_requests` | Anonymous reference |
| Audit log structure | Event records, identity anonymised |

## 🔑 Audit Database Permissions

Tier 2 anonymisation modifies existing audit rows, which the audit database's application user cannot do: it holds `INSERT` and `SELECT` only, so that neither a bug nor a compromised app server can rewrite history.

`gdpr:anonymise-accounts` therefore connects as a **separate erasure user** holding `UPDATE` on the anonymisable columns and nothing else. Configure it as its own connection; do not widen the application user's grants.

See [../../database/audit.md](../../database/audit.md) for the full grant table.

## 💾 Erasure and Backups

An erasure request cannot reach a backup. A dump is an immutable snapshot, and rewriting it would destroy the integrity that makes it a backup. The accepted position, which this platform adopts:

- Erasure is applied to **live systems immediately**, on the schedule above.
- Database dumps in `syoksheet-backups-{env}` are retained **30 days** and pruned automatically by lifecycle rule, so any snapshot containing erased data ages out within that window.
- If a dump is ever restored, **pending and completed erasures are re-applied to the restored data before it serves traffic**. A restore must never resurrect a deleted account.
- Managed-cluster point-in-time recovery has the same property and the same 7-day bound.

This must be stated in the privacy policy: erasure completes on live systems immediately, and residual copies in backups are removed within 30 days.

> [!WARNING]
> Dumps are complete copies of personal data and are **never anonymised**, an anonymised backup restores nothing. They are encrypted before upload, written by a dedicated credential held only by the backup job, and never downloaded to a personal machine except deliberately and briefly.

## 📧 Emails

Requested (confirmation + deadline + cancel link), cancelled (restoration), completed (final confirmation): all noreply@.

## 📋 Audit Events

`account_deletion.requested/cancelled/tier1_applied/tier2_applied/completed`. See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/privacy.md](../../database/privacy.md).

# Email Management: Endpoints

Managing the user's primary, backup, and work emails. The rules (types, when a backup becomes required) are product behaviour. See syoksheet-docs → features/user-accounts.md; this file is the API contract.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/me/emails` | List the user's emails with type and verification state |
| `POST /api/v1/me/emails` | Add an email `{ email, type }` → sends verification link. 422 if the address exists anywhere on the platform |
| `DELETE /api/v1/me/emails/{email}` | Remove: allowed for work emails not attached to an org membership; primary/backup can only be replaced |
| `POST /api/v1/me/emails/{email}/make-primary` | Start the primary-change flow (verified backup required) |
| `POST /api/v1/me/emails/{email}/resend-verification` | Resend the verification link (rate limited 3/hour) |
| `GET /api/v1/me/emails/{email}/verify/{hash}` | Signed verification link → marks verified, redirects to the frontend |
| `GET /api/v1/me/connected-accounts` | List linked social accounts |
| `DELETE /api/v1/me/connected-accounts/{account}` | Unlink Google (rejected if it would leave the account without a login method) → `user.oauth_disconnected` |

## 📏 Enforcement Rules

- Every email unique across the platform (`user_emails.email` unique).
- Exactly one `primary`; at most one `backup`; backup must differ from primary.
- **Primary must be personal.** A conflict is detected when the primary's domain matches a DNS-verified org, either at org verification or when the user joins an org with that address. The user must then add and verify a personal email and make it primary. The old address converts to type `work` in place, keeping its `user_email_id` so org memberships are unaffected, and org-space access is restricted until resolved. A primary change onto a verified-org domain is rejected (`code: work_domain_primary`).
- Primary change: requires a verified backup; the new address must be verified before the swap; the old primary is removed on completion (or converted to `work` in the flow above). Fires `user.email_changed` with old and new values in `properties`.
- Work email removal blocked while it is the `user_email_id` on any `org_members` row.

## 🗄️ Tables

See [database/users.md](../../database/users.md).

# Email Management: Endpoints

Managing the user's primary, recovery, and work emails. The rules (types, when a recovery address becomes required) are product behavior. See syoksheet-docs → features/user-accounts.md; this file is the API contract.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/me/emails` | List the user's emails with type and verification state |
| `POST /api/v1/me/emails` | Add an email `{ email, type }` → sends verification link. 422 if the address exists anywhere on the platform |
| `DELETE /api/v1/me/emails/{email}` | Remove: allowed for work emails not attached to an org membership; primary and recovery can only be replaced |
| `POST /api/v1/me/emails/{email}/make-primary` | Start the primary-change flow (verified recovery address required) |
| `POST /api/v1/me/emails/{email}/resend-verification` | Resend the verification link (rate limited 3/hour) |
| `GET /api/v1/me/emails/{email}/verify/{hash}` | Signed verification link → marks verified, redirects to the frontend |
| `GET /api/v1/me/connected-accounts` | List linked social accounts |
| `DELETE /api/v1/me/connected-accounts/{account}` | Unlink Google (rejected if it would leave the account without a login method) → `user.oauth_disconnected` |

## 📏 Enforcement Rules

- Every email unique across the platform (`user_emails.email` unique).
- Exactly one `primary`; at most one `recovery`; recovery must differ from primary.
- **The primary address may sit on a company domain.** This is never blocked. Whoever controls that mailbox can request a password reset and so reach the account, which is the user's decision to make. Where the primary's domain matches a DNS-verified org, the emails endpoint flags it in the response so the UI can recommend moving it and prompt for a recovery address. It is a recommendation and never an error.
- Primary change: requires a verified recovery address; the new address must be verified before the swap; the old primary is removed on completion (or converted to `work` in the flow above). Fires `user.email_changed` with old and new values in `properties`.
- Work email removal blocked while it is the `user_email_id` on any `org_members` row.

## 🗄️ Tables

See [database/users.md](../../database/users.md).

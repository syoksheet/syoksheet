# Impersonation

Read-only impersonation lets Support and Engineering observe a user's experience while debugging. Changes on a user's behalf are made through explicit admin operations, never impersonation.

## 🔑 Token Spec

`POST /api/admin/v1/users/{user}/impersonate` (requires `users.impersonate`) issues a short-lived Sanctum token scoped to the target user:

| Property | Value |
|----------|-------|
| `tokenable_type` | `User::class`: acts as the user, not the admin |
| Ability | `support:impersonation` |
| Expiry | 2 hours |
| Token name | `impersonation|admin:{admin-id}`: auditable in `personal_access_tokens` |

The token works on regular `/api/v1/` endpoints.

## 🔒 Read-Only Enforcement

The `PreventImpersonationWrites` middleware rejects all non-GET requests on `/api/v1/` carrying the `support:impersonation` ability.

Impersonation tokens are excluded from the user's own token list and cannot be revoked via the user token endpoints. See [../users/tokens.md](../users/tokens.md).

## 📋 Audit Trail

Every impersonation issues `user.impersonated` (admin ID in `properties`) and `admin.user_data_viewed`: recorded even when nothing is changed.

# User API Tokens: Endpoints

Pro users create named, permanent bearer tokens for third-party integrations. Tokens carry the `user:api` Sanctum ability.

## 🔌 Endpoints

Password confirmation (`POST /api/v1/me/confirm-password`) is required before create and revoke.

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/me/tokens` | List tokens: id, name, last_used_at, created_at |
| `POST /api/v1/me/tokens` | Create `{ name }` → plain-text token returned **once**, never stored |
| `DELETE /api/v1/me/tokens/{token}` | Revoke → 204 |

## 📏 Rules

- Pro plan required.
- Impersonation tokens (`support:impersonation` ability) are excluded from the list and cannot be revoked here. See [../admin/impersonation.md](../admin/impersonation.md).
- Bearer tokens authenticate against the external `/api/v1/` API (`Authorization: Bearer {token}`).

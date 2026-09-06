# User Profile: Endpoints

The authenticated user's own profile.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/me` | The authenticated user (User resource) |
| `PATCH /api/v1/me` | Update profile: name, username, bio, current_role, current_company, country_id/state_id/city_id, website_url, social_links, locale |
| `POST /api/v1/me/avatar` | Upload avatar → `syoksheet-public-{env}`, sets `avatar_url` |
| `DELETE /api/v1/me/avatar` | Remove avatar (deletes the `syoksheet-public-{env}` object) |
| `PUT /api/v1/me/password` | Change password (current password required) → fires `user.password_changed` audit event |
| `PUT /api/v1/me/custom-slug` | Set/change the custom wall URL slug (Pro): identifier rules + reserved list per [validation.md](../../validation.md) |
| `PATCH /api/v1/me/open-to-work` | `{ enabled: bool }`: first enable requires JobMatching + AiProcessing consents (422 `consents_required` otherwise); see [../jobs/matching.md](../jobs/matching.md) |

## 📏 Validation Notes

- `username` and `custom_slug` follow the shared identifier rules and reserved-names list in [validation.md](../../validation.md); the public wall resolves custom_slug first, then username.
- Location IDs must reference existing [location](../../database/location.md) rows; state/city must belong to the selected country.

## 🗄️ Tables

See [database/users.md](../../database/users.md).

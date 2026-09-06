# PDF Export: Implementation

Pro users export a PDF snapshot of their public wall. Product behavior in syoksheet-docs → features/user-accounts.md.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `POST /api/v1/me/pdf-export` | Queue generation (Pro only). 422 with `code: export_in_progress` if one is running |
| `GET /api/v1/me/pdf-export` | Latest export status + download URL while valid |

## ⚙️ Pipeline

1. A queued job renders the user's public wall to PDF: profile header, public brags with verification badges, and skills summary.
2. Upload under the `exports/` prefix of `syoksheet-private-{env}`; signed URL with **24-hour** expiry; in-app notification with the link.
3. Lifecycle: objects under the `exports/` prefix are deleted at 7 days, the same rule that covers data-export ZIPs.

## 📏 Rules

- Content matches exactly what the public wall shows: hidden, private, and `on_verification` brags are excluded.
- Pro-gated; downgrade removes the endpoints, existing links live out their expiry.

# PDF Export — Implementation

Pro users export a PDF snapshot of their public wall. Product behaviour in syoksheet-docs → features/user-accounts.md.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/pdf-export` | Queue generation (Pro only). 422 with `code: export_in_progress` if one is running |
| `GET /api/v1/me/pdf-export` | Latest export status + download URL while valid |

## ⚙️ Pipeline

1. Queued job renders the user's public wall — profile header, public brags with verification badges, skills summary — to PDF.
2. Upload to R2; signed URL with **24-hour** expiry; in-app notification with the link.
3. R2 lifecycle: export objects deleted at 7 days (same rule as data-export ZIPs).

## 📏 Rules

- Content matches exactly what the public wall shows — hidden, private, and `on_verification` brags are excluded.
- Pro-gated; downgrade removes the endpoints, existing links live out their expiry.

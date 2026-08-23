# Data Export — Implementation

GDPR Article 15/20 export pipeline. Product behaviour in syoksheet-docs → features/privacy.md.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/me/data-export` | Latest request with status + download URL while valid |
| `POST /api/v1/me/data-export` | Create request — 422 if one is active or the 30-day cooldown since the last completed request hasn't passed |

## ⚙️ Pipeline

1. Request row created (`status: pending`).
2. `GenerateDataExportJob` queued (default queue). Status → `processing`.
3. Job collects all personal data, builds the ZIP, uploads to R2.
4. `download_url` = signed R2 URL, `expires_at` = +48 h, status → `ready`, email sent (noreply@).
5. Past `expires_at` → status `expired`; the user must re-request (cooldown applies).
6. Job retries 3×; on permanent failure status → `failed` and the user is notified.

## 📂 ZIP Contents

```
export-{uuid}.zip
  ├── README.md          — explains the structure
  ├── profile.json       — name, email addresses, bio, location, social links, current role, notification preferences, consent-relevant settings
  ├── brags.json         — all brags incl. soft-deleted, with tags, links, skills, attachment metadata
  ├── verifications.json — verifications received and given
  ├── skills.json        — brag skill selections with canonical skill names
  ├── consent.json       — full consent_records history
  ├── jobs.json          — job interests expressed, open-to-work state, current match scores with factors (Art. 15 covers profiling output)
  └── notifications.json — notification history (within the 90-day retention window)
```

Everything personal is included — internal signals not visible in the UI too. **Every new field containing personal data must be added to `GenerateDataExportJob`.** In-product analytics are excluded (aggregate traffic signals, not personal data).

## 📋 Audit Events

`data_export.requested`, `data_export.completed` — see [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/privacy.md](../../database/privacy.md).

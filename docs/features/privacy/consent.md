# Consent: Endpoints & Implementation

The consent recording API. What each consent type means and when it is collected is product-level. See syoksheet-docs → features/privacy.md.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/me/consent` | Current state per consent type, the latest `consent_records` row per `(user_id, consent_type)` |
| `POST /api/v1/me/consent` | Record actions: `{ consents: [{ type, action: given\|withdrawn }] }`, one append-only row each, capturing IP, user agent, and the current `policy_version` |

## ⚙️ Implementation

- `ConsentType` enum: `MarketingEmails`, `Analytics`, `Marketing`, `JobMatching`, `AiProcessing`. New consent types must be added to the enum and documented before the feature using them ships.
- Rows are never updated or deleted. Withdrawal is a new row with `action = withdrawn`.
- `MarketingEmails` withdrawal also unsubscribes from the marketing list immediately.
- Pre-login banner choices live in the frontend's `localStorage` and are synced through `POST /api/v1/me/consent` on login.
- The current `policy_version` comes from config; a material policy change increments it, which makes the frontend re-show the banner (state for the new version is empty).
- On account erasure (Tier 2), `user_id` is set null; records are retained as proof.

## 📋 Audit Events

`consent.given`, `consent.withdrawn`. See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/privacy.md](../../database/privacy.md).

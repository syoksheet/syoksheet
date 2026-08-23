# Verification — Endpoints & Implementation

The verification request lifecycle for both paths — personal (tokened email link) and organisation (queue). Product behaviour in syoksheet-docs → features/verification.md.

## 🔌 Owner Endpoints

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/brags/{brag}/verification-requests` | Create — `{ type: personal, verifier_name, verifier_email }` or `{ type: organization, organization_id }`. Pending-limit enforced (Free 3, Pro/seat unlimited); org queue capacity enforced (Free org 10) |
| `GET /api/v1/me/brags/{brag}/verification-requests` | List with status |
| `DELETE /api/v1/me/verification-requests/{request}` | Cancel pending |

**Creation rules:** no duplicate active request to the same email per brag; org requests require the brag's place/org to match the org or its whitelisted domains; resend only after expiry (30 days).

## 🌐 External Verifier Endpoints (unauthenticated, token-scoped)

| Route | Behaviour |
|-------|-----------|
| `GET /api/verify/{token}` | Full brag payload for the standalone verifier page. Handles expired / used / invalid / brag-deleted states |
| `POST /api/verify/{token}` | Verify — `{ relationship, comment?, anonymity_level }` (`full`, `anonymous`). One-time use |
| `POST /api/verify/{token}/decline` | Decline with optional reason (stored in `verification_rejections`, owner-visible only) |

## 🏢 Org Queue Endpoints

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/v1/organizations/{org}/verification-queue` | `verification.approve` | Pending org requests |
| `PATCH /api/v1/organizations/{org}/verification-queue/{request}` | `verification.approve` | `{ action: approve, comment? }` or `{ action: reject, reason? }` — records `verified_by_org_user_id` |

## ⚙️ Side Effects

- First verification on a brag → `is_locked = true` (see [endpoints.md](endpoints.md)).
- Unlock removes all `verifications` rows for the brag.
- A verifier who signs up later is linked by email match: `verifier_user_id` set on their past verifications, in-app notification sent.
- `verification:expire-requests` (daily) expires pending requests older than 30 days and notifies owners — see [scheduled-jobs.md](../../scheduled-jobs.md).

## 📋 Audit Events

`brag.verification_requested`, `brag.verification_approved`, `brag.verification_rejected` — `organization_id` set for org-type requests, which adds `management` visibility. See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/brags.md](../../database/brags.md).

# Outbound Webhooks — Implementation

Business orgs subscribe endpoint URLs to platform events; syoksheet signs and delivers event payloads to them. The mirror image of the Jobs Push API — we call them.

## 🔌 Endpoints

All require `org.manage` + Business; the SSO gate applies.

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/organizations/{org}/webhooks` | List endpoints with status + failure counts |
| `POST /api/v1/organizations/{org}/webhooks` | Create `{ url, events[] }` — https only; signing secret generated, shown **once** |
| `PATCH /api/v1/organizations/{org}/webhooks/{webhook}` | Update URL, subscribed events, re-enable |
| `DELETE /api/v1/organizations/{org}/webhooks/{webhook}` | Remove → 204 |
| `POST /api/v1/organizations/{org}/webhooks/{webhook}/test` | Send a `ping` event now |
| `GET /api/v1/organizations/{org}/webhooks/{webhook}/deliveries` | Recent deliveries: event, status, attempts, response code |

## 📡 Subscribable Events

The org-relevant subset, named as the org already sees them in the audit view:

`verification_request.created` · `job.interest_expressed` · `join_request.created` · `member.joined` · `member.removed` · `member.departure_started` · `dns.status_changed` · `ping`

New subscribable events are added here and to the payload contract together.

## ⚙️ Delivery

- Queued (default queue). Payload: `{ event, occurred_at, organization_id, data }` where `data` mirrors the audit `display` structure (actor/subject/context).
- **Signature:** `X-syoksheet-Signature: sha256=HMAC_SHA256(secret, raw_body)` — receivers must verify; secret stored encrypted.
- **Retries:** initial attempt + 4 retries with exponential backoff (1 min → ~1 h). Non-2xx or timeout (10 s) counts as failure.
- **Auto-disable:** 20 consecutive failed deliveries → endpoint `is_active = false`, org notified (in-app + team@). Re-enable via PATCH.
- Deliveries are recorded per attempt; `webhooks:cleanup` prunes delivery rows older than 30 days — see [scheduled-jobs.md](../../scheduled-jobs.md).

## 📏 Rules

- Business only. On downgrade endpoints are disabled, config retained — see [../billing/lifecycle.md](../billing/lifecycle.md).
- `https` URLs only; private/loopback IP targets rejected (SSRF guard).
- Webhook management is audited: `org.webhook_created/updated/deleted` (`management` visibility).

## 🗄️ Tables

See [database/organizations.md](../../database/organizations.md) (`org_webhooks`, `org_webhook_deliveries`).

# Billing: Endpoints & Webhooks

DodoPayments integration: checkout and subscription sync. Organization plans are flat, so there is no usage reporting of any kind. Product behavior (payment flow, downgrade rules) in syoksheet-docs → features/billing.md; pricing in syoksheet-docs → product/pricing.md.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `POST /api/v1/me/billing/checkout` | Create an embedded checkout session for Pro (`{ cycle: monthly|annual }`) |
| `GET /api/v1/me/billing` | Current subscription, cycle, period, invoices |
| `POST /api/v1/me/billing/cancel` | Cancel at period end |
| `POST /api/v1/organizations/{org}/billing/checkout` | Business checkout (`billing.manage`) |
| `GET /api/v1/organizations/{org}/billing` | Org subscription, plan and cycle (`billing.manage`) |
| `POST /api/v1/organizations/{org}/billing/cancel` | Cancel at period end (`billing.manage`) |
| `POST /api/v1/webhooks/dodo` | Webhook receiver: signature-verified, unauthenticated route |

## ⚙️ Webhook Processing

1. Verify the DodoPayments signature (`DODO_WEBHOOK_SECRET`).
2. Insert into `webhook_events` keyed on `dodo_event_id`: duplicate delivery is a no-op (idempotency).
3. Queued handler processes by `event_type`. DodoPayments' own names, so `subscription.active`, `subscription.updated`, `subscription.cancelled`, `subscription.past_due`, and the payment events. Note the double L: that spelling is theirs → sync the `subscriptions` row and the billable's `plan` column; mark `processed_at`.
4. Payment events fire `billing.*` audit events with `System` causer.

## 🏢 Organization Plans

- Plans are flat per tier (Free, Growth, Business): canonical prices in syoksheet-docs → product/pricing.md.
- Membership changes never affect the bill. There is no seat count, no proration on join or leave, and nothing to report to DodoPayments.
- Tier changes take effect immediately and are prorated against the current cycle.

## ⬇️ Downgrade, Grace & Dunning

All transition behavior lives in [lifecycle.md](lifecycle.md): the 14-day `past_due` grace, the dunning schedule, and per-feature downgrade application.

## 📋 Audit Events

`billing.subscription_created/upgraded/downgraded/canceled`, `billing.payment_succeeded/failed` (System causer, `internal` + `management` for orgs). See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/billing.md](../../database/billing.md).

# Billing: Endpoints & Webhooks

DodoPayments integration: checkout, subscription sync, and seat billing. Product behaviour (payment flow, downgrade rules) in syoksheet-docs → features/billing.md; pricing in syoksheet-docs → product/pricing.md.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/billing/checkout` | Create an embedded checkout session for Pro (`{ cycle: monthly|annual }`) |
| `GET /api/v1/me/billing` | Current subscription, cycle, period, invoices |
| `POST /api/v1/me/billing/cancel` | Cancel at period end |
| `POST /api/v1/organizations/{org}/billing/checkout` | Business checkout (`billing.manage`) |
| `GET /api/v1/organizations/{org}/billing` | Org subscription + seat count breakdown (`billing.manage`) |
| `POST /api/v1/organizations/{org}/billing/cancel` | Cancel at period end (`billing.manage`) |
| `POST /api/v1/webhooks/dodo` | Webhook receiver: signature-verified, unauthenticated route |

## ⚙️ Webhook Processing

1. Verify the DodoPayments signature (`DODO_WEBHOOK_SECRET`).
2. Insert into `webhook_events` keyed on `dodo_event_id`: duplicate delivery is a no-op (idempotency).
3. Queued handler processes by `event_type`: subscription created/updated/cancelled, payment succeeded/failed → sync the `subscriptions` row and the billable's `plan` column; mark `processed_at`.
4. Payment events fire `billing.*` audit events with `System` causer.

## 🪑 Seat Billing

- Seat count = member count, derived from `org_members`, never stored.
- Member 6+ joins a Business org → prorated seat charge from the join date, reported to DodoPayments.
- Member removal → seat count drops; billing adjusts at the next cycle.
- Seat benefits (brag + pending-verification limits) are evaluated live from active Business memberships, no denormalised flag.

## ⬇️ Downgrade, Grace & Dunning

All transition behaviour lives in [lifecycle.md](lifecycle.md): the 14-day `past_due` grace, the dunning schedule, and per-feature downgrade application.

## 📋 Audit Events

`billing.subscription_created/upgraded/downgraded/cancelled`, `billing.payment_succeeded/failed` (System causer, `internal` + `management` for orgs). See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/billing.md](../../database/billing.md).

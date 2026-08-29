# Billing: Subscription Lifecycle

What happens between "payment failed" and "downgraded", and how downgrades apply. Checkout and webhook mechanics in [webhooks.md](webhooks.md).

## ⏳ past_due Grace & Dunning

When a renewal payment fails, the subscription enters `past_due`:

| Day | What happens |
|-----|--------------|
| 0 | `billing.payment_failed` event; dunning email #1 (billing@) with payment-update link |
| 7 | Dunning email #2 |
| 12 | Dunning email #3: final warning |
| 14 | Grace ends → subscription `cancelled`, plan reverts to `free`, standard downgrade rules apply; email + in-app notification |

Full access continues during the entire grace period. A successful payment at any point restores `active` and stops dunning.

## ⬇️ Downgrade Application

On any transition to `free` (cancellation at period end, grace expiry, or seat loss):

**Users**, over-limit brags get `hidden_at` set, most recent kept visible by default; the user can reselect via `PATCH /api/v1/me/brags/visibility-selection` (see [../brags/endpoints.md](../brags/endpoints.md)). Pro-only features switch off: custom slug falls back to username, analytics dashboard access ends (data retained 90 days), PDF export and API tokens disabled (existing tokens revoked).

**Orgs**: nothing is removed. While over any free limit (members, teams, active postings, queue), the org cannot **add** to that resource; existing members, teams, and published postings stay. Wall pins are cleared, branding stops rendering (config retained), analytics access ends (data retained 90 days), Push API tokens revoked, SSO disabled (config retained), webhook endpoints disabled (config retained).

Re-upgrading reverses everything: `hidden_at` cleared, features re-enabled, aggregates intact.

## 🪑 Seat-Loss Downgrade

A member leaving a Business org loses seat benefits immediately; if over the free brag limit with no personal Pro, the user downgrade rules above apply to them.

## 📋 Audit Events

`billing.subscription_downgraded` (grace expiry: System causer), `billing.payment_failed` per attempt. See [../audit/events.md](../audit/events.md).

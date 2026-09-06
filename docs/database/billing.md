# Billing: Database Schema

Tables for subscriptions and DodoPayments webhook event tracking.

## 💳 subscriptions

Active and historical subscription records for users and organizations (morph).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| billable_type | varchar(255) | `App\Models\User`, `App\Models\Organization` |
| billable_id | uuid | Required |
| dodo_subscription_id | varchar(255) | Unique |
| dodo_customer_id | varchar(255) | Required |
| plan | varchar(20) | `pro` (user), `business` (org) |
| status | varchar(20) | `active`, `cancelled`, `past_due`, `paused`. DodoPayments' own values, mirrored verbatim so the webhook handler is a passthrough. The double L is theirs; do not "correct" it |
| billing_cycle | varchar(10) | `monthly`, `annual` |
| current_period_start | timestamptz | Required |
| current_period_end | timestamptz | Required |
| canceled_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

**Index:** `(billable_type, billable_id)`

## 📨 webhook_events

Raw DodoPayments webhook payloads for idempotent processing.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| dodo_event_id | varchar(255) | Unique: idempotency key |
| event_type | varchar(100) | Required |
| payload | jsonb | Required |
| processed_at | timestamptz | Nullable: null until handled |
| created_at | timestamptz | Set on insert |

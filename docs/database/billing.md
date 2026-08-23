# Billing — Database Schema

Tables for subscriptions and DodoPayments webhook event tracking.

## 💳 subscriptions

Active and historical subscription records for users and organisations (morph).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| billable_type | varchar(255) | `App\Models\User`, `App\Models\Organization` |
| billable_id | uuid | |
| dodo_subscription_id | varchar(255) | Unique |
| dodo_customer_id | varchar(255) | |
| plan | varchar(20) | `pro` (user), `business` (org) |
| status | varchar(20) | `active`, `cancelled`, `past_due`, `paused` |
| billing_cycle | varchar(10) | `monthly`, `annual` |
| current_period_start | timestamptz | |
| current_period_end | timestamptz | |
| cancelled_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | |

**Index:** `(billable_type, billable_id)`

## 📨 webhook_events

Raw DodoPayments webhook payloads for idempotent processing.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| dodo_event_id | varchar(255) | Unique — idempotency key |
| event_type | varchar(100) | |
| payload | jsonb | |
| processed_at | timestamptz | Nullable — null until handled |
| created_at | timestamptz | |

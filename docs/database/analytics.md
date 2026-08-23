# Analytics — Database Schema

Tables backing the in-product analytics dashboards (user walls, org walls, brag views). Views are recorded server-side on public endpoints; raw events are pruned on the retention schedule while org monthly aggregates are kept forever.

## 👁️ view_events

One accepted view. Same-IP dedup within 24 h happens in Redis before insert — rejected views never reach this table.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| viewable_type | varchar(255) | `App\Models\User` (user wall), `App\Models\Organization` (org wall), `App\Models\Brag` |
| viewable_id | uuid | |
| ip_hash | varchar(64) | SHA-256 of IP + daily salt — dedup evidence without storing raw IPs |
| referrer | varchar(500) | Nullable — for top-referrer stats |
| created_at | timestamptz | |

**Index:** `(viewable_type, viewable_id, created_at)`

**Retention** (via `analytics:cleanup`, daily — see [scheduled-jobs.md](../scheduled-jobs.md)):
- User wall + brag events: pruned after 90 days (no aggregation — user analytics show 90 days only)
- Org wall events: pruned after 12 months, rolled into monthly aggregates first

## 📈 analytics_monthly_aggregates

Org-level monthly totals, retained indefinitely for trend reporting.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | uuid | No FK cascade — aggregates survive as raw IDs |
| month | date | First day of the month |
| metric | varchar(50) | `wall_views`, `brag_views` |
| value | bigint | |
| created_at | timestamptz | |

**Unique:** `(organization_id, month, metric)`

## 🎯 Derived Metrics (no tables)

Member activity and the verification funnel are computed at query time from `brags`, `verification_requests`, and `verifications` — no tracking tables.

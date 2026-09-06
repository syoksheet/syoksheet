# Scheduled Jobs

Canonical list of all scheduled Artisan commands. Feature specs link here for frequencies; the scheduler cron runs `php artisan schedule:run` every minute via Forge.

## 🕐 Schedule

| Command | Frequency | What |
|---------|-----------|------|
| `horizon:snapshot` | Every 5 min | Queue metrics for the Horizon dashboard. Without it the metrics page stays blank |
| `verification:expire-requests` | Daily | Expire pending verification requests older than 30 days |
| `dns:check-pending` | Every 4 h | Retry pending DNS checks (max 18 attempts) |
| `dns:reverify` | Daily | 6-month re-verification + grace period checks |
| `dns:check-grace-expired` | Daily | Revoke verification when grace periods lapse |
| `org:finalize-departures` | Daily | Remove members after the 7-day departure notice |
| `org:expire-transfers` | Daily | Expire pending ownership transfers after 7 days |
| `gdpr:process-deletions` | Daily | Tier 1 hard deletes for accounts past cooling off |
| `gdpr:anonymize-accounts` | Daily | Tier 2 anonymization for `tier1_complete` accounts |
| `security:check-incidents` | Hourly | 48 h / 24 h reminders on unresolved High/Critical incidents (72-hour GDPR deadline) |
| `taxonomy:sync` | Monthly | Re-sync ESCO + O*NET, crosswalk dedup, queue AI-assisted review |
| `notifications:cleanup` | Daily | Prune notifications older than 90 days |
| `analytics:cleanup` | Daily | Prune raw user-wall views > 90 days and raw org views > 12 months (rolled into monthly aggregates first) |
| `matching:reconcile` | Daily | Repair drifted `match_scores` (stale `computed_at` vs newer brags/postings) |
| `webhooks:cleanup` | Daily | Prune `org_webhook_deliveries` older than 30 days |
| `audit:archive` | Monthly | `pg_dump` the audit DB to `syoksheet-audit-archive-{env}`: forever retention |
| `billing:process-dunning` | Daily | Send day-7/12 dunning reminders; downgrade subscriptions past the 14-day grace |

## 🔗 Job → Spec Map

| Command | Spec |
|---------|------|
| `verification:expire-requests` | [features/brags/verification.md](features/brags/verification.md) |
| `dns:*` | [features/organizations/dns-verification.md](features/organizations/dns-verification.md) |
| `org:*` | [features/organizations/endpoints.md](features/organizations/endpoints.md) |
| `gdpr:*` | [features/privacy/account-deletion.md](features/privacy/account-deletion.md) |
| `security:check-incidents` | [features/privacy/security-incidents.md](features/privacy/security-incidents.md) |
| `taxonomy:sync` | [features/taxonomy/import.md](features/taxonomy/import.md) |
| `notifications:cleanup` | [features/users/notifications.md](features/users/notifications.md) |
| `analytics:cleanup` | [database/analytics.md](database/analytics.md) |
| `matching:reconcile` | [features/jobs/matching.md](features/jobs/matching.md) |
| `webhooks:cleanup` | [features/organizations/webhooks.md](features/organizations/webhooks.md) |
| `audit:archive` | [features/audit/implementation.md](features/audit/implementation.md) |
| `billing:process-dunning` | [features/billing/lifecycle.md](features/billing/lifecycle.md) |

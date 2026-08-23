# DNS Verification — Implementation

The DNS TXT check pipeline that activates an org. Product behaviour in syoksheet-docs → features/dns-verification.md.

## 🌐 TXT Record

```
syoksheet-verify={org_code}-{user_code}
```

`org_code` = first 12 characters of the org UUID (hyphens stripped); `user_code` = first 8 of the initiating user's UUID.

## 🔌 Endpoints

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `POST /api/v1/organizations/{org}/dns-verification` | owner or `org.manage` | Generate the TXT value and queue the first check |
| `GET /api/v1/organizations/{org}/dns-verification` | member | Current status, last/next check, failure count |
| `POST /api/v1/organizations/{org}/dns-verification/retry` | owner or `org.manage` | Manual retry after `failed` |

## ⚙️ Check Pipeline

DNS lookups use `dns_get_record()` and run **only in queued jobs**, never during an HTTP request.

**Initial verification**

1. Check queued on initiation.
2. Not found → `dns:check-pending` (every 4 h) retries, max 18 attempts (72 h), incrementing `failure_count`.
3. Found → `status = verified`, `organizations.is_dns_verified = true`, `dns_verified_at` set → `org.dns_verified` event → place auto-link scan.
4. 18 failures → `status = failed`; manual retry resets the cycle.

**Re-verification** (`dns:reverify`, daily)

- Orgs with `next_check_at` due (every 6 months) are re-checked.
- Fail → `grace_period_ends_at = now + 14 days`; daily checks during grace; org stays verified.
- Record returns during grace → grace cleared, next check in 6 months.
- `dns:check-grace-expired` (daily) revokes verification when grace lapses: `is_dns_verified = false`, badge/wall/queue off → notifications to owner + Admin team.

## 🔗 Place Auto-Link

On first successful verification: scan `brags.place_normalized` for matches against the org's name/domain → in-app notification to each brag owner → owner confirms → `organization_id` set on the brag. Linking never bypasses org verification for wall display.

## 🗄️ Tables

See [database/organizations.md](../../database/organizations.md) (`dns_verifications`).

# Data Privacy: Database Schema

Tables for GDPR consent tracking, data export requests, and account deletion requests. All on the primary database.

## ✅ consent_records

Append-only log of every consent action. Each row is a single grant or withdrawal, never updated, never deleted. The most recent row per `(user_id, consent_type)` is the current state. On account erasure, `user_id` is set to null and the record retained as proof of the consent event.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| user_id | uuid FK → users | SET NULL on erasure |
| consent_type | varchar(50) | `MarketingEmails`, `Analytics`, `Marketing`, `JobMatching`, `AiProcessing`: `ConsentType` enum |
| action | varchar(20) | `given`, `withdrawn` |
| policy_version | varchar(50) | Privacy policy version in effect at the time |
| ip_address | varchar(45) | IPv4 or IPv6 |
| user_agent | text | Standard |
| created_at | timestamptz | No `updated_at` |

**Index:** `(user_id, consent_type)` for latest-consent queries

`JobMatching` and `AiProcessing` are defined in the enum from day one but dormant until the jobs feature ships.

## 📦 user_data_export_requests

One active request per user at a time, with a 30-day cooldown between completed requests.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| user_id | uuid FK → users | CASCADE |
| status | varchar(20) | `pending`, `processing`, `ready`, `expired`, `failed` |
| download_url | varchar(500) | Nullable: signed `syoksheet-private-{env}` URL, set when ready |
| expires_at | timestamptz | Nullable: 48 hours after ready |
| requested_at | timestamptz | Required |
| completed_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🗑️ account_deletion_requests

Tracks a deletion request through the 30-day cooling off and three-tier erasure.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| user_id | uuid FK → users | CASCADE |
| status | varchar(30) | `cooling_off`, `tier1_complete`, `tier2_complete`, `completed`, `canceled` |
| requested_at | timestamptz | Required |
| cooling_off_ends_at | timestamptz | `requested_at` + 30 days |
| tier1_completed_at | timestamptz | Nullable |
| tier2_completed_at | timestamptz | Nullable |
| completed_at | timestamptz | Nullable |
| canceled_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

The full erasure pipeline (what each tier deletes or anonymises) is specified in [features/privacy/account-deletion.md](../features/privacy/account-deletion.md).

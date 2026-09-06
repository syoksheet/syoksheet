# Location: Database Schema

Reference tables for countries, states/provinces, and cities, imported from GeoNames. FK targets on user and organization profiles. Read-only at runtime: populated by import command, periodically re-synced. No `updated_at`, no soft deletes.

## 🌍 countries

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| name | varchar(100) | Required |
| iso_code | varchar(2) | Unique. ISO 3166-1 alpha-2 |
| iso_code3 | varchar(3) | Unique. ISO 3166-1 alpha-3 |
| calling_code | varchar(10) | Nullable |
| created_at | timestamptz | Set on insert |

## 🗺️ states

First-level administrative divisions (states, provinces).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| country_id | bigint FK → countries | Required |
| name | varchar(100) | Required |
| code | varchar(10) | Nullable: e.g. `MY-10` |
| created_at | timestamptz | Set on insert |

**Index:** country_id

## 🏙️ cities

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| country_id | bigint FK → countries | Required |
| state_id | bigint FK → states | Nullable |
| name | varchar(100) | Required |
| created_at | timestamptz | Set on insert |

**Index:** country_id, state_id

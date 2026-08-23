# Location — Database Schema

Reference tables for countries, states/provinces, and cities, imported from GeoNames. FK targets on user and organisation profiles. Read-only at runtime — populated by import command, periodically re-synced. No `updated_at`, no soft deletes.

## 🌍 countries

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | |
| iso_code | varchar(2) | Unique — ISO 3166-1 alpha-2 |
| iso_code3 | varchar(3) | Unique — ISO 3166-1 alpha-3 |
| calling_code | varchar(10) | Nullable |
| created_at | timestamptz | |

## 🗺️ states

First-level administrative divisions (states, provinces).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| country_id | bigint FK → countries | |
| name | varchar(100) | |
| code | varchar(10) | Nullable — e.g. `MY-10` |
| created_at | timestamptz | |

**Index:** country_id

## 🏙️ cities

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| country_id | bigint FK → countries | |
| state_id | bigint FK → states | Nullable |
| name | varchar(100) | |
| created_at | timestamptz | |

**Index:** country_id, state_id

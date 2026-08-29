# Jobs: Database Schema

Tables for job postings, their skill requirements, and candidate interest. Jobs arrive by manual posting or the Jobs Push API, never by pulling from external systems.

## 💼 job_postings

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | Public identifier |
| organization_id | uuid FK → organizations | CASCADE |
| title | varchar(255) | As provided by the org |
| description | text | Required |
| occupation_id | bigint FK → occupations | Required at publish, SET NULL. See [taxonomy.md](taxonomy.md) |
| industry_id | bigint FK → industries | Nullable: defaults to the org's industry |
| employment_type | varchar(20) | `full_time`, `part_time`, `contract`, `internship` |
| is_remote | boolean | Default false |
| country_id | bigint FK → countries | Nullable. See [location.md](location.md) |
| state_id | bigint FK → states | Nullable |
| city_id | bigint FK → cities | Nullable |
| apply_url | varchar(500) | Nullable, the org's own application page |
| status | varchar(20) | `draft`, `published`, `closed`: manual transitions only, no auto-expiry |
| source | varchar(10) | `manual`, `api` |
| external_id | varchar(255) | Nullable, the org's own ID for Push API idempotency |
| published_at | timestamptz | Nullable |
| closed_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |
| deleted_at | timestamptz | Soft delete |

**Indexes:** organization_id, occupation_id, industry_id, status, published_at
**Unique:** `(organization_id, external_id)` where external_id is not null

## 🎯 job_posting_skills

Skill requirements, from the taxonomy only.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| job_posting_id | uuid FK → job_postings | CASCADE |
| skill_id | bigint FK → skills | Standard |
| is_required | boolean | true = required, false = nice-to-have: weighted differently in matching |
| created_at | timestamptz | Set on insert |

**Unique:** `(job_posting_id, skill_id)`

## 🎯 match_scores

Precomputed (user, posting) match scores. Only pairs above the relevance threshold are stored: absence means "no meaningful match". Recomputed event-driven with a daily reconciliation sweep.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| job_posting_id | uuid FK → job_postings | CASCADE |
| user_id | uuid FK → users | CASCADE |
| score | smallint | 0–100 |
| factors | jsonb | Per-factor breakdown powering "why you match" |
| computed_at | timestamptz | Standard |
| created_at, updated_at | timestamptz | Managed by Eloquent |

**Unique:** `(job_posting_id, user_id)` · **Indexes:** `(job_posting_id, score)`, `(user_id, score)`

## 🙋 job_interests

A user's "express interest" action on a posting. Personal data: deleted in Tier 1 erasure.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| job_posting_id | uuid FK → job_postings | CASCADE |
| user_id | uuid FK → users | CASCADE |
| created_at | timestamptz | Set on insert |

**Unique:** `(job_posting_id, user_id)`

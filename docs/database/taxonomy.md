# Taxonomy: Database Schema

Tables for occupations, skills, and industries. Populated from ESCO and O*NET via import commands, with manual curation for industries. FK targets for brags, organisations, and future job postings.

## 🗂️ data_providers

Tracks the external datasets that provide taxonomy source data.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| name | varchar(100) | e.g. `ESCO`, `O*NET` |
| slug | varchar(100) | Unique |
| version | varchar(50) | Dataset version imported |
| source_url | varchar(500) | Nullable |
| last_synced_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🗂️ occupation_categories

Top-level groupings. ISCO-08 major groups.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| name | varchar(255) | Required |
| slug | varchar(255) | Unique |
| description | text | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 💼 occupations

One canonical record per occupation across all providers.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| occupation_category_id | bigint FK → occupation_categories | Required |
| name | varchar(255) | Canonical name |
| slug | varchar(255) | Unique |
| description | text | Nullable |
| is_active | boolean | Default true: inactive hidden from search, links preserved |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🔤 occupation_aliases

Alternative names and common titles resolving to a canonical occupation. Indexed into search.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| occupation_id | bigint FK → occupations | CASCADE |
| alias | varchar(255) | Standard |
| created_at | timestamptz | Set on insert |

**Index:** alias

## 🔗 occupation_provider_mappings

Maps each canonical occupation to its equivalent in a source dataset.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| occupation_id | bigint FK → occupations | CASCADE |
| data_provider_id | bigint FK → data_providers | Standard |
| provider_code | varchar(100) | Standard |
| provider_title | varchar(255) | Standard |
| created_at | timestamptz | Set on insert |

**Unique:** `(occupation_id, data_provider_id)`

## 🏷️ skill_categories

Top-level groupings derived from ESCO's skill groupings.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| name | varchar(255) | Required |
| slug | varchar(255) | Unique |
| description | text | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🎯 skills

One canonical record per skill across all providers. Used via `brag_skills`. See [brags.md](brags.md).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| skill_category_id | bigint FK → skill_categories | Standard |
| name | varchar(255) | Canonical name |
| slug | varchar(255) | Unique |
| is_active | boolean | Default true |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🔤 skill_aliases

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| skill_id | bigint FK → skills | CASCADE |
| alias | varchar(255) | Standard |
| created_at | timestamptz | Set on insert |

**Index:** alias

## 🔗 skill_provider_mappings

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| skill_id | bigint FK → skills | CASCADE |
| data_provider_id | bigint FK → data_providers | Standard |
| provider_code | varchar(100) | Standard |
| provider_title | varchar(255) | Standard |
| created_at | timestamptz | Set on insert |

**Unique:** `(skill_id, data_provider_id)`

## 🏭 industries

Manually curated flat list (~20–30), LinkedIn's taxonomy as naming reference. FK target on `organizations.industry_id` (required) and `brags.industry_id` (nullable).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| name | varchar(255) | Required |
| slug | varchar(255) | Unique |
| description | text | Nullable |
| is_active | boolean | Default true |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🔗 industry_provider_mappings

Equivalent codes in external datasets (NACE, NAICS) for future crosswalk use, not used at runtime.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| industry_id | bigint FK → industries | CASCADE |
| data_provider_id | bigint FK → data_providers | Standard |
| provider_code | varchar(100) | Standard |
| provider_title | varchar(255) | Standard |
| created_at | timestamptz | Set on insert |

**Unique:** `(industry_id, data_provider_id)`

## 🌐 taxonomy_translations

Localized display names for all taxonomy entities. See [localization.md](../localization.md).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| translatable_type | varchar(255) | Occupation, Skill, OccupationCategory, SkillCategory, Industry |
| translatable_id | bigint | Standard |
| locale | varchar(15) | BCP 47 |
| name | varchar(255) | Standard |
| created_at, updated_at | timestamptz | Managed by Eloquent |

**Unique:** `(translatable_type, translatable_id, locale)`

## 🔗 occupation_skills

ESCO's occupation↔skill relations, imported alongside occupations and skills. Used as a matching signal, a user's brag occupations expand to their associated skills.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| occupation_id | bigint FK → occupations | CASCADE |
| skill_id | bigint FK → skills | CASCADE |
| relation_type | varchar(20) | `essential`, `optional`, per the ESCO dataset |
| created_at | timestamptz | Set on insert |

**Unique:** `(occupation_id, skill_id)`

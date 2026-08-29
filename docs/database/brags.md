# Brags & Verifications: Database Schema

Tables for brags, their enrichment data, and verification requests and results.

## 🏆 brags

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | Public identifier |
| user_id | uuid FK → users | SET NULL on delete |
| title | varchar(255) | Required |
| description | text | Required |
| date_start | date | Required |
| date_end | date | Nullable (null = ongoing) |
| place_text | varchar(255) | Always stored as typed |
| place_normalized | varchar(255) | Nullable, for org auto-link matching |
| organization_id | uuid FK → organizations | Nullable, SET NULL |
| occupation_id | bigint FK → occupations | Required at creation, SET NULL. See [taxonomy.md](taxonomy.md) |
| position_text | varchar(255) | Optional display title; falls back to occupation name |
| industry_id | bigint FK → industries | Nullable, SET NULL |
| visibility | varchar(20) | `public`, `private`, `on_verification` |
| is_confidential | boolean | Default false |
| is_locked | boolean | Default false: set once any verification exists |
| hidden_at | timestamptz | Nullable: set on over-limit brags after downgrade; cleared on re-upgrade or reselection |
| created_at, updated_at | timestamptz | Managed by Eloquent |
| deleted_at | timestamptz | Soft delete |

**Indexes:** user_id, organization_id, occupation_id, industry_id, visibility, place_normalized, hidden_at, deleted_at, created_at

## 🏷️ brag_tags

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| brag_id | uuid FK → brags | CASCADE |
| tag | varchar(100) | Freeform |
| created_at | timestamptz | Set on insert |

**Index:** brag_id, tag

## 📎 brag_attachments

Files stored in R2.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| brag_id | uuid FK → brags | CASCADE |
| file_name | varchar(255) | Standard |
| file_path | varchar(500) | R2 key |
| file_size | bigint | Standard |
| mime_type | varchar(100) | Standard |
| created_at | timestamptz | Set on insert |

## 🔗 brag_links

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| brag_id | uuid FK → brags | CASCADE |
| url | varchar(2000) | Required |
| label | varchar(255) | Nullable |
| created_at | timestamptz | Set on insert |

## 👥 brag_collaborators

People credited on a brag, optionally linked to a user account.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| brag_id | uuid FK → brags | CASCADE |
| user_id | uuid FK → users | Nullable, SET NULL: set when the collaborator is/becomes a user |
| name | varchar(255) | Required |
| email | varchar(255) | Nullable |
| message | text | Nullable: owner's invite message |
| status | varchar(20) | `pending`, `accepted`, `declined` |
| invited_at | timestamptz | Nullable |
| responded_at | timestamptz | Nullable |
| created_at | timestamptz | Set on insert |

## 🎯 brag_skills

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| brag_id | uuid FK → brags | CASCADE |
| skill_id | bigint FK → skills | CASCADE. See [taxonomy.md](taxonomy.md) |
| created_at | timestamptz | Set on insert |

**Unique:** `(brag_id, skill_id)`

## ✅ verification_requests

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | Public identifier |
| brag_id | uuid FK → brags | CASCADE |
| requested_by | uuid FK → users | Required |
| type | varchar(20) | `personal`, `organization` |
| organization_id | uuid FK → organizations | Nullable: set for org type |
| verifier_name | varchar(255) | For personal |
| verifier_email | varchar(255) | For personal |
| token | varchar(255) | Unique, one-time link |
| status | varchar(20) | `pending`, `completed`, `rejected`, `expired`, `cancelled` |
| expires_at | timestamptz | 30 days from creation |
| completed_at | timestamptz | Nullable |
| created_at, updated_at | timestamptz | Managed by Eloquent |

## 🏅 verifications

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | Public identifier |
| brag_id | uuid FK → brags | CASCADE |
| verification_request_id | uuid FK → verification_requests | Required |
| type | varchar(20) | `personal`, `organization` |
| organization_id | uuid FK → organizations | Nullable, SET NULL |
| verifier_user_id | uuid FK → users | Nullable, SET NULL: linked when verifier has/creates an account |
| verifier_name | varchar(255) | Required |
| verifier_email | varchar(255) | Required |
| relationship | varchar(255) | Freeform |
| comment | text | Nullable |
| anonymity_level | varchar(20) | `full`, `anonymous` |
| is_hidden_on_wall | boolean | Default false: org wall hide toggle |
| verified_by_org_user_id | uuid FK → users | Nullable: org member who approved |
| created_at | timestamptz | Set on insert |
| deleted_at | timestamptz | Soft delete |

## ❌ verification_rejections

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Internal key, never exposed |
| verification_request_id | uuid FK → verification_requests | CASCADE |
| reason | text | Nullable: visible to the brag owner only |
| rejected_by_user_id | uuid | Nullable |
| created_at | timestamptz | Set on insert |

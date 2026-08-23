# Jobs — Endpoints & Implementation

Job posting CRUD, the Jobs Push API, and the normalization pipeline. Product behaviour in syoksheet-docs → features/jobs.md. There is **no external ATS integration and no polling** — jobs are created in the UI or pushed to us.

## 🔌 Org Endpoints (UI)

All require the `jobs.manage` permission (Hiring team by default).

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/organizations/{org}/jobs` | All postings with status, source, interest counts |
| `POST /api/v1/organizations/{org}/jobs` | Create draft — title, description, employment_type required; occupation selected from taxonomy (same search UX as brags) |
| `PATCH /api/v1/organizations/{org}/jobs/{job}` | Update fields; `{ status: published }` requires occupation set and enforces tier limits; `{ status: closed }` closes |
| `DELETE /api/v1/organizations/{org}/jobs/{job}` | Soft delete |
| `GET /api/v1/organizations/{org}/jobs/{job}/candidates` | Matched open-to-work candidates — see [matching.md](matching.md) |
| `GET /api/v1/organizations/{org}/jobs/{job}/interests` | Users who expressed interest |

## 🔌 Jobs Push API (Business only)

Same routes, authenticated with the org's Business API token instead of a session. Differences:

- `source` is set to `api`.
- `external_id` accepted on create/update — upsert semantics: a `POST` with an existing `(organization_id, external_id)` updates that posting instead of duplicating. This makes org-side automation (ATS webhooks, Zapier, scripts) safely re-runnable.
- API-created postings arrive as `draft` with AI-suggested occupation and skill mappings (below); a member with `jobs.manage` reviews and publishes. Automation cannot publish unreviewed postings.
- Rate limit: 60 requests/min per org token.

## 🤖 Normalization Pipeline

Freeform titles and descriptions must map to taxonomy before publish:

1. On create (either source), the title is scored against occupations + aliases (same Meilisearch index); top suggestions attached.
2. Description text is scanned for skill matches against skills + aliases; suggestions attached with `is_required` guesses.
3. Low-confidence results (no strong Meilisearch hit) fall back to `AiService` for suggestion — queued, review-gated, org-authored text only. See [ai.md](../../ai.md).
3. UI creation: the org picks the occupation directly at creation (suggestions pre-filled). API creation: suggestions await review.
4. Publishing requires a confirmed `occupation_id`. Skills are optional but recommended.

## 📏 Tier Enforcement

Active = `status: published`, counted per org, non-deleted. Canonical limits: syoksheet-docs → product/pricing.md (Free: 3 active, manual only — the Push API itself requires Business).

## 🔌 User-Facing Endpoints

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/jobs` | Browse published postings — filters: occupation, skills, industry, location, remote, org; match score included when open-to-work; paginated |
| `GET /api/v1/jobs/{job}` | Detail with "why you match" factors |
| `POST /api/v1/jobs/{job}/interest` | Express interest — notifies the org's `jobs.manage` members; once per user per posting |
| `DELETE /api/v1/jobs/{job}/interest` | Withdraw interest |

## 📋 Audit Events

`jobs` domain — `job.created`, `job.updated`, `job.published`, `job.closed`, `job.deleted`, `job.interest_expressed`, `admin.job_removed`. See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/jobs.md](../../database/jobs.md).

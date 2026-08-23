# Public — Endpoints

Unauthenticated endpoints serving the public walls and org directory on the marketing site. Product behaviour in syoksheet-docs → features/public-walls.md.

## 🔌 Endpoints

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/public/users/{username}` | User profile header — name, avatar, bio, role/company, location, links, aggregated skills summary. The parameter resolves `custom_slug` first (Pro wall URL), then `username` |
| `GET /api/v1/public/users/{username}/brags` | Public brags (verified + unverified), reverse-chronological; filters: skill, tag, org, time; paginated |
| `GET /api/v1/public/organizations/{slug}` | Org profile + verified badge — 404 unless DNS-verified |
| `GET /api/v1/public/organizations/{slug}/brags` | Wall brags (linked + org-verified + not hidden); filters: skill, tag, time, user; paginated |
| `GET /api/v1/public/organizations` | Directory of DNS-verified orgs; filters: name, industry; paginated |
| `GET /api/v1/public/jobs` | Published postings from non-suspended, DNS-verified orgs; filters: occupation, skills, industry, location, remote, org; paginated — powers the SEO jobs directory |
| `GET /api/v1/public/jobs/{job}` | Public posting page payload (no match scores — those are viewer-specific and authenticated) |

Suspended/deleted users and orgs, and accounts in deletion cooling-off, return 404.

## 📊 View Tracking

Wall and brag views are recorded server-side on these endpoints for the analytics dashboards (Pro/Business — see syoksheet-docs → features/analytics.md):

- Deduplicated per IP per 24 h (Redis-backed).
- Referrer captured for top-referrer stats.
- Public walls only — private/hidden content never tracks.
- `analytics:cleanup` (daily) prunes raw user-wall views after 90 days and raw org views after 12 months, keeping org monthly aggregates — see [scheduled-jobs.md](../../scheduled-jobs.md).

## 🗄️ Tables

See [database/brags.md](../../database/brags.md), [database/organizations.md](../../database/organizations.md).

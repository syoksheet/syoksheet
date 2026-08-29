# Matching: Implementation

Transparent scoring between published job postings and users, with every factor visible ("why you match"). Not a black-box algorithm.

## 🎯 Scoring Factors

Computed per (user, posting) pair from data both sides already hold:

| Factor | Signal | Weight notes |
|--------|--------|--------------|
| Skill overlap | User's brag skills ∩ posting's skills | Required skills weigh more than nice-to-have; verified brags weigh more than unverified |
| Occupation | User's brag occupations vs posting occupation, expanded through `occupation_skills` relations | Direct match strongest; essential-relation overlap partial |
| Industry history | User's brag industries vs posting industry | Experience-history signal |
| Location | User location vs posting location / `is_remote` | Remote postings match everywhere |

The response carries a per-factor breakdown, rendered as "why you match", never a bare opaque score.

## 🔒 Gating & Consent

- **Users always see** match scores on the jobs browse/detail endpoints, their own data, their own view.
- **Orgs see candidates** (`GET /api/v1/organizations/{org}/jobs/{job}/candidates`) only for users with `is_open_to_work = true`.
- Enabling open-to-work the first time requires granting the `JobMatching` + `AiProcessing` consents together (inseparable, see [../privacy/consent.md](../privacy/consent.md)). Withdrawing either consent forces `is_open_to_work` off immediately and removes the user from all candidate lists.
- `PATCH /api/v1/me/open-to-work`: `{ enabled: bool }`; enabling without the consents returns 422 with the consent requirement.

## ⚙️ Computation: Precomputed Scores

Scores live in `match_scores` (score 0–100 + `factors` jsonb), recomputed by low-priority queued jobs, never at request time:

| Trigger | Recompute scope |
|---------|-----------------|
| Posting published or its skills/occupation/location updated | That posting × all users |
| Brag created/updated/deleted, brag skills changed, profile location changed | That user × all published postings |
| Posting closed/deleted, user deleted | Scores removed (cascade) |

- Only pairs above the relevance threshold are stored: absence means no meaningful match, keeping the table far below the full user × posting product.
- `matching:reconcile` (daily) sweeps for drift: stale `computed_at` against newer source data, and repairs it. See [scheduled-jobs.md](../../scheduled-jobs.md).
- Browse endpoints join the viewer's scores; candidate lists (`open-to-work` users only) order by score. `factors` serves "why you match" directly, no recomputation on read.

## 🔔 Match Alerts

When a recompute writes a `match_scores` row crossing the alert threshold (score ≥ 70) for a **newly published** posting, the user is notified (in-app + email, `jobs` preference category). Once per (user, posting): updates to an already-alerted pair never re-alert. Alerts respect nothing else: open-to-work is not required (alerts are user-facing value; visibility to orgs is the consent-gated part).

## 🔎 Talent Search (Business)

`GET /api/v1/organizations/{org}/talent-search`: requires `jobs.manage` + Business; SSO gate applies.

- Filters: skills (any/all), occupation, industry, location, remote-willingness inferred from location filters.
- Returns **open-to-work users only** (the same consent gate as candidate lists), ranked by relevance to the filter set, with the transparent factor breakdown.
- Not tied to a posting. This is the org browsing the candidate pool directly. Included in Business, no separate add-on.

## 🗄️ Tables

See [database/jobs.md](../../database/jobs.md), [database/taxonomy.md](../../database/taxonomy.md) (`occupation_skills`).

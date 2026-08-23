# AI Integration

A single `AiService` abstraction with swappable provider drivers — Claude API (Anthropic) as the default. AI is used in exactly two places, both batch, both review-gated, and neither touches personal data. Localization uses no AI — translation runs through Weblate (see [localization.md](localization.md)).

## 🧩 The Abstraction

- `AiService` is the only surface application code touches; a provider driver (`anthropic` default) implements it. Swapping providers is a config change, never a refactor.
- All AI calls run in **queued jobs only** — never during a web request.
- Model selection per task class, in config: a capable model for similarity judgment (default `claude-sonnet-5`), an economical one for bulk scoring (default `claude-haiku-4-5`).
- Failures degrade gracefully: an AI outage delays review-queue population; nothing user-facing breaks.

## 🎯 Use Cases (exhaustive)

| Use | When | Input sent | Output |
|-----|------|-----------|--------|
| Taxonomy dedup similarity | `taxonomy:sync` — records the ESCO↔O*NET crosswalk doesn't cover | Occupation/skill names + descriptions (public reference data) | Similarity candidates → **admin review queue** — see [features/taxonomy/import.md](features/taxonomy/import.md) |
| Job mapping fallback | Pushed postings where Meilisearch title/skill scoring is low-confidence | Job title + description (org-authored content) | Occupation/skill suggestions → **org review before publish** — see [features/jobs/endpoints.md](features/jobs/endpoints.md) |

Any new AI use case must be added to this table — with its data classification — before implementation.

## 🔒 Hard Rules

- **Human review gate:** AI output is never auto-applied. Dedup merges need admin confirmation; job mappings need org confirmation. No exceptions.
- **No personal data:** only public taxonomy data and org-authored job text may be sent to a provider. Brag content, profile data, and anything user-personal never leaves the platform — matching is deterministic ([features/jobs/matching.md](features/jobs/matching.md)), so the `AiProcessing` consent governs profiling, not LLM calls.
- The provider is listed as a data processor for non-personal content in syoksheet-docs → features/privacy.md.

## ⚙️ Configuration

| Variable | Example | Notes |
|----------|---------|-------|
| AI_PROVIDER | anthropic | Driver selection |
| ANTHROPIC_API_KEY | sk-ant-… | Empty locally — AI jobs no-op with a logged skip |
| AI_MODEL_JUDGMENT | claude-sonnet-5 | Similarity judgment, quality-sensitive |
| AI_MODEL_BULK | claude-haiku-4-5 | Bulk scoring |

## 💰 Cost Profile

Both uses are low-volume batch: monthly sync deltas and per-posting fallbacks — cents to single-digit dollars per month. No per-user or per-request AI cost anywhere.

# AI Integration

Built on the official Laravel AI SDK (`laravel/ai`), with Anthropic as the only configured provider. AI is used in exactly two places, both batch, both review-gated, and neither touches personal data. Localization uses no AI: translation runs through Weblate (see [localization.md](localization.md)).

## 🧩 The SDK

We use the official **Laravel AI SDK** (`laravel/ai`). We do not wrap it in an
abstraction of our own: the SDK already gives us provider swapping, queued calls and
structured output, and a wrapper would be a worse version of all three.

- **Agents**, one per use case, made with `php artisan make:agent`. Each declares its
  instructions and its output schema.
- **Anthropic is the only configured provider.** The SDK ships drivers for a dozen
  others; leaving them without keys is what keeps them unreachable.
- **Model per agent**, set with the SDK's `#[Model]` attribute: a capable model for
  similarity judgment (`claude-sonnet-5`), an economical one for bulk scoring
  (`claude-haiku-4-5`). The choice belongs on the agent, not at the call site, so a
  bulk job cannot quietly run on the expensive model.
- **Structured output** via `HasStructuredOutput`, so results arrive as a typed schema
  rather than a string we parse by hand.
- **Queued only**, using the SDK's `queue()`. Never during a web request.
- **Conversation persistence is not used.** Both use cases are one-shot prompts, so the
  SDK's `agent_conversations` migrations stay unpublished and the schema stays ours.
- Failures degrade gracefully: an AI outage delays review-queue population; nothing
  user-facing breaks.

## 🎯 Use Cases (exhaustive)

| Use | When | Input sent | Output |
|-----|------|-----------|--------|
| Taxonomy dedup similarity | `taxonomy:sync`: records the ESCO↔O*NET crosswalk doesn't cover | Occupation/skill names + descriptions (public reference data) | Similarity candidates → **admin review queue**. See [features/taxonomy/import.md](features/taxonomy/import.md) |
| Job mapping fallback | Pushed postings where Meilisearch title/skill scoring is low-confidence | Job title + description (org-authored content) | Occupation/skill suggestions → **org review before publish**. See [features/jobs/endpoints.md](features/jobs/endpoints.md) |

Any new AI use case must be added to this table, with its data classification, before implementation.

## 🔒 Hard Rules

- **Human review gate:** AI output is never auto-applied. Dedup merges need admin confirmation; job mappings need org confirmation. No exceptions.
- **No personal data:** only public taxonomy data and org-authored job text may be sent to a provider. Brag content, profile data, and anything user-personal never leaves the platform: matching is deterministic ([features/jobs/matching.md](features/jobs/matching.md)), so the `AiProcessing` consent governs profiling, not LLM calls.
- The provider is listed as a data processor for non-personal content in syoksheet-docs → features/privacy.md.

## ⚙️ Configuration

| Variable | Example | Notes |
|----------|---------|-------|
| AI_PROVIDER | anthropic | Sets `ai.default`. The only provider we configure |
| ANTHROPIC_API_KEY | sk-ant-… | Empty locally and in CI, so no call is attempted |

Models are not environment variables. Each agent names its own with `#[Model]`, which
keeps the cost profile of a job visible in the class that does the work.

> [!NOTE]
> `laravel/ai` is pre-1.0 (v0.11 at time of writing). Composer's `^0.11.0` allows
> patch and minor bumps that may break, so read the changelog on upgrade rather than
> trusting the constraint.

## 💰 Cost Profile

Both uses are low-volume batch work (monthly sync deltas and per-posting fallbacks) costing cents to single-digit dollars per month. No per-user or per-request AI cost anywhere.

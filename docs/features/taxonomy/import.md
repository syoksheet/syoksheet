# Taxonomy Import & Administration

How taxonomy data is imported from ESCO and O*NET, deduplicated, and kept current. Admin actions require the Engineering or Super Admin team.

## 📥 Import Process

### 1. Source data

- **ESCO** — CSV datasets from the ESCO portal, updated ~quarterly.
- **O*NET** — database export (CSV) from the US Department of Labor, updated multiple times per year.

### 2. Import command

```bash
ddev php artisan taxonomy:import esco --version=1.2.0
ddev php artisan taxonomy:import onet --version=29.0
```

Each run creates or updates the `data_providers` record, then upserts occupation/skill/alias records. Canonical records are never deleted — only marked inactive when absent from the source.

### 3. Crosswalk deduplication (automatic)

The official ESCO↔O*NET crosswalk is checked first:

- Match found → a provider-mapping row links the provider record to the existing canonical record; no new canonical created.
- No match → flagged for AI-assisted review.

### 4. AI-assisted review

Unmatched records are scored for semantic similarity against existing canonical records via `AiService` (queued, batch — see [ai.md](../../ai.md)); candidates above the threshold enter the admin review queue.

| Action | Result |
|--------|--------|
| Approve merge | Provider mapping created; no new canonical record |
| Reject merge | New canonical record created |
| Skip | Stays in the queue |

### 5. Post-import

Aliases imported from both providers, `data_providers.last_synced_at` updated, Meilisearch re-indexed, import summary logged (visible in SigNoz).

## 🔄 Re-sync

`taxonomy:sync` runs monthly — see [scheduled-jobs.md](../../scheduled-jobs.md). Cadence guidance: ESCO quarterly (on release), O*NET bi-annually or on major versions. Re-sync is additive — new records added, changed names updated, deprecated entries marked inactive. Merged canonical records are never re-split.

## 🛠️ Admin Panel Actions

Engineering + Super Admin (`taxonomy.manage`):

- View import history (`data_providers`)
- Review and resolve the dedup queue
- Add missing occupations, skills, or industries manually (including user-flagged requests)
- Mark records inactive
- View provider mappings per canonical record

## 📏 Rules

- Canonical records are never hard-deleted — only deactivated.
- Merges require admin confirmation — never automatic.
- Industries are never imported — manually curated only.
- `occupation_skills` is deferred to v2.

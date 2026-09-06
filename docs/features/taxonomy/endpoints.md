# Taxonomy: Endpoints & Implementation

Search and browse endpoints for occupations, skills, and industries. Product behavior in syoksheet-docs → features/taxonomy.md.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/taxonomy/occupations` | Search (`?search=`) via Scout/Meilisearch across canonical names + aliases; typo-tolerant. Results include category as subtitle. Leaf occupations only |
| `GET /api/v1/taxonomy/skills` | Search or browse by category; results grouped by skill category |
| `GET /api/v1/taxonomy/industries` | Full active list: served from cache, no search |

Inactive records (`is_active = false`) are excluded from all three.

## 🔍 Search Implementation

- Laravel Scout with the Meilisearch driver; Meilisearch runs as a process on the app VPS, writing its index to the server's own disk, no volume, and the index is reproducible from `taxonomy:sync`.
- Indexed documents: occupations and skills, each carrying their aliases as searchable fields so alias hits resolve to the canonical record.
- Index sync on model changes; full re-index after imports.

## 🚩 Missing-Record Requests

`POST /api/v1/taxonomy/requests`: `{ type: occupation|skill, name, note? }` flags a missing record for admin review (visible in the admin taxonomy panel).

## 🗄️ Tables

See [database/taxonomy.md](../../database/taxonomy.md).

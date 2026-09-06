# Brags: Endpoints & Implementation

Brag CRUD with tier limits and field locking. Product behavior (fields, place field, business rules) in syoksheet-docs → features/brags.md.

## 🔌 Endpoints

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/me/brags` | Own brags: timeline order (`date_start` desc), filterable by skill, tag, org, date; paginated |
| `POST /api/v1/me/brags` | Create: title, description, date_start, place_text, occupation_id, visibility required. Tier limit enforced (422 `brag_limit_reached`; canonical limits: syoksheet-docs → product/pricing.md) |
| `PATCH /api/v1/me/brags/visibility-selection` | After downgrade: choose which brags stay visible within the free limit: sets/clears `hidden_at` |
| `GET /api/v1/me/brags/{brag}` | Detail with tags, links, attachments, skills, collaborators, verifications |
| `PATCH /api/v1/me/brags/{brag}` | Update: locked fields rejected while `is_locked` (see below) |
| `DELETE /api/v1/me/brags/{brag}` | Soft delete; cascades children |
| `POST /api/v1/me/brags/{brag}/unlock` | Removes ALL verifications, unlocks fields; `on_verification` visibility reverts to `private`. Always audited (`brag.unlocked`) |
| `POST /api/v1/me/brags/{brag}/attachments` / `DELETE .../attachments/{attachment}` | Upload to `syoksheet-private-{env}` / remove. **Pro only**: Free has no attachments (`code: attachment_requires_pro`) |
| `GET /api/v1/me/brags/{brag}/history` | The brag's contextual activity: audit log filtered by subject |

## 🔒 Field Locking

Once any verification exists, `is_locked = true` and these fields reject updates: `title`, `description`, `date_start`, `date_end`, `place_text`, `organization_id`, `occupation_id`, the factual claims verifiers vouched for.

Always editable: `position_text`, `visibility`, `is_confidential`, `industry_id`, tags, links, attachments, collaborators.

## 📍 Place Handling

- Org selected from the DNS-verified suggestions → `organization_id` set.
- Freeform → `place_text` as typed + `place_normalized` (lowercased, trimmed, punctuation-stripped) for later auto-link matching.

## 📏 Enforcement

- Tier limit check counts non-deleted brags. Organization plans never lift a member's personal limits: personal and org billing are fully independent.
- Downgrade hiding: over-limit brags get `hidden_at` set (most recent kept visible by default; reselect via the endpoint above); hidden brags are excluded from walls and analytics, fully restored on upgrade. See [../billing/lifecycle.md](../billing/lifecycle.md).
- Visibility `on_verification` → treated as private until the first verification lands, then public.

## 📋 Audit Events

`brag.created`, `brag.updated` (changed fields), `brag.deleted` (full before state), `brag.visibility_changed`, `brag.unlocked`, `brag.admin_removed`. See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/brags.md](../../database/brags.md).

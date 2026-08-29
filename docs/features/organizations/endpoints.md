# Organizations: Endpoints & Implementation

Org CRUD, teams, membership, join requests, departures, and ownership transfers. Product behaviour (states, team model, membership rules) lives in syoksheet-docs → features/organizations.md.

## 🔌 Org Endpoints

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `POST /api/v1/organizations` | any user | Create org: name, domain, primary email (verified email on that domain), contact_email, industry_id required. Creator becomes owner + Admin team member; default teams seeded |
| `GET /api/v1/organizations/{org}` | member | Org details |
| `PATCH /api/v1/organizations/{org}` | owner or `org.manage` | Update profile fields |
| `DELETE /api/v1/organizations/{org}` | owner only | Soft delete; members disassociated, wall removed, verifications retained as "org no longer active" |

## 🏷️ Teams

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/v1/organizations/{org}/teams` | member | List teams with permissions |
| `POST /api/v1/organizations/{org}/teams` | owner or `teams.manage` | Create custom team (tier limit: Free 2, Business unlimited) |
| `PATCH /api/v1/organizations/{org}/teams/{team}` | owner or `teams.manage` | Rename / edit permissions → `team.permissions_changed` audit event with old + new sets |
| `DELETE /api/v1/organizations/{org}/teams/{team}` | owner or `teams.manage` | Delete: rejected for `is_default` teams |
| `POST /api/v1/organizations/{org}/teams/{team}/members` | owner or `teams.manage` | Add member to team |
| `DELETE /api/v1/organizations/{org}/teams/{team}/members/{member}` | owner or `teams.manage` | Remove from team |

Permission checks resolve as: owner passes everything; otherwise the union of the member's team `permissions` arrays must contain the required `feature.action` string.

When the org has SSO enabled, all org-scoped routes additionally pass the `EnsureOrgSsoSession` gate (403 `sso_required` without an active 12-hour org SSO session). See [../auth/sso.md](../auth/sso.md). The owner's SSO-settings access and the self-service departure endpoint are exempt.

## 👥 Membership

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/v1/organizations/{org}/members` | member | List members with teams |
| `POST /api/v1/organizations/{org}/invitations` | `members.manage` | Invite by email (team@): creates `org_invitations` row, 30-day token |
| `GET /api/v1/organizations/{org}/invitations` | `members.manage` | List with status |
| `DELETE /api/v1/organizations/{org}/invitations/{invitation}` | `members.manage` | Revoke pending |
| `POST /api/invitations/{token}` / `.../decline` | invitee (auth or signup flow) | Accept (requires a verified matching work email → creates membership) or decline |
| `POST /api/v1/organizations/{org}/join-requests` | any user | Apply: requires a verified work email matching the org domain or an `org_domains` entry |
| `PATCH /api/v1/organizations/{org}/join-requests/{request}` | `members.manage` | Approve / reject |
| `DELETE /api/v1/organizations/{org}/members/{member}` | `members.manage` | Remove member |
| `POST /api/v1/organizations/{org}/departures` | member (self) | Start 7-day leave notice: blocked for the owner |
| `DELETE /api/v1/organizations/{org}/departures/{departure}` | member (self) | Cancel pending departure |
| `GET /api/v1/organizations/{org}/domains` / `POST` / `DELETE` | owner or `org.manage` | Whitelisted additional domains |

`org:finalize-departures` (daily) completes departures past `effective_at`. See [scheduled-jobs.md](../../scheduled-jobs.md).

## 🔄 Ownership Transfers

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `POST /api/v1/organizations/{org}/ownership-transfers` | owner | Propose to any member; one pending per org; 7-day expiry |
| `PATCH /api/v1/organizations/{org}/ownership-transfers/{transfer}` | proposed member | Accept (owner flag moves; previous owner joins Admin team) or decline |
| `DELETE /api/v1/organizations/{org}/ownership-transfers/{transfer}` | owner | Cancel pending |

`org:expire-transfers` (daily) expires pending transfers past `expires_at`.

## 🎨 Branding (Business)

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `PUT /api/v1/organizations/{org}/branding` | `branding.manage` | `{ accent_color?: "#hex" }` |
| `POST /api/v1/organizations/{org}/branding/cover` / `DELETE` | `branding.manage` | Cover image upload → R2 (limits per [validation.md](../../validation.md)) |

Rendered on the public wall header and org-typed verifier pages. On downgrade the config is retained but not rendered. See [../billing/lifecycle.md](../billing/lifecycle.md).

## 📌 Wall Pins (Business)

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `PUT /api/v1/organizations/{org}/wall-pins` | `wall.manage` | Replace the pin set `{ pins: [{ brag_id, position }] }`: pinned brags must be wall-eligible; count limit per syoksheet-docs → product/pricing.md |

## 📋 Org Audit View

| Route | Permission | Behaviour |
|-------|-----------|-----------|
| `GET /api/v1/organizations/{org}/audit-log` | Management (owner + Admin team) | `management`-visibility events for the org; filter by date range + event type |
| `GET /api/v1/organizations/{org}/audit-log/export` | Management | CSV export |
| `GET /api/v1/organizations/{org}/activity` | Management | The live twin: cursor-paginated reverse-chronological stream of the same events; new events broadcast on the org's private Reverb activity channel. See [../audit/implementation.md](../audit/implementation.md) |

## 📋 Audit Events

Domains `organizations` + `teams`: full catalog in [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/organizations.md](../../database/organizations.md).

# Collaborators: Endpoints & Implementation

Collaborator invites, consent, and removal. Product behaviour in syoksheet-docs → features/collaborators.md.

## 🔌 Owner Endpoints

| Route | Behaviour |
|-------|-----------|
| `POST /api/v1/me/brags/{brag}/collaborators` | Invite: existing user (`{ user_id }` or `{ email }` matching an account) or non-user (`{ name, email, message? }`). Rejected if the invitee holds an active verification request for the brag |
| `DELETE /api/v1/me/brags/{brag}/collaborators/{collaborator}` | Owner removes: collaborator notified |

## 🔌 Collaborator Endpoints

| Route | Behaviour |
|-------|-----------|
| `GET /api/collaborate/{token}` | Non-user invite view: full brag, accept/decline, no account needed |
| `POST /api/collaborate/{token}` / `.../decline` | Non-user respond |
| `PATCH /api/v1/me/collaborations/{collaborator}` | Existing user accept/decline (in-app + email invite) |
| `DELETE /api/v1/me/collaborations/{collaborator}` | Collaborator removes themself: owner notified |
| `GET /api/v1/me/collaborations` | Brags the user collaborates on (their "Collaborated on" section) |

## ⚙️ Rules & Side Effects

- Invites expire after 30 days (same window as verification links).
- On signup with a matching email: pending invites surface for accept/decline (declined ones stay declined); accepted rows get `user_id` linked.
- "Collaborated on" display requires the brag to be `public`; the collaborator retains private visibility if the owner flips it.
- Collaboration never locks brag fields.
- No collaborator count limit.

## 📋 Audit Events

`brag.collaborator_invited`, `brag.collaborator_accepted`, `brag.collaborator_declined`, `brag.collaborator_removed` (initiator in `properties`). See [../audit/events.md](../audit/events.md).

## 🗄️ Tables

See [database/brags.md](../../database/brags.md) (`brag_collaborators`).

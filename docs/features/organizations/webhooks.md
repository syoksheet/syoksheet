# Outbound Webhooks: Implementation

Business orgs subscribe endpoint URLs to platform events; syoksheet signs and delivers event payloads to them. The mirror image of the Jobs Push API: we call them.

## 🔌 Endpoints

All require `org.manage` + Business; the SSO gate applies.

| Route | Behaviour |
|-------|-----------|
| `GET /api/v1/organizations/{org}/webhooks` | List endpoints with status + failure counts |
| `POST /api/v1/organizations/{org}/webhooks` | Create `{ url, events[] }`: https only; signing secret generated, shown **once** |
| `PATCH /api/v1/organizations/{org}/webhooks/{webhook}` | Update URL, subscribed events, re-enable |
| `DELETE /api/v1/organizations/{org}/webhooks/{webhook}` | Remove → 204 |
| `POST /api/v1/organizations/{org}/webhooks/{webhook}/test` | Send a `ping` event now |
| `GET /api/v1/organizations/{org}/webhooks/{webhook}/deliveries` | Recent deliveries: event, status, attempts, response code |

## 📡 Subscribable Events

The org-relevant subset, named as the org already sees them in the audit view:

`verification_request.created` · `job.interest_expressed` · `join_request.created` · `member.joined` · `member.removed` · `member.departure_started` · `dns.status_changed` · `ping`

New subscribable events are added here and to the payload contract together.

## ⚙️ Delivery

- Queued (default queue). Payload: `{ event, occurred_at, organization_id, data }` where `data` mirrors the audit `display` structure (actor/subject/context).
- **Signature:** `X-syoksheet-Signature: sha256=HMAC_SHA256(secret, "{timestamp}.{raw_body}")` with the timestamp sent as `X-syoksheet-Timestamp`. Receivers must verify both, and reject deliveries whose timestamp falls outside their tolerance window. Signing the body alone leaves receivers no way to detect a replay. Secret stored encrypted; see the Security section.
- **Retries:** initial attempt + 4 retries with exponential backoff (1 min → ~1 h). Non-2xx or timeout (10 s) counts as failure.
- **Auto-disable:** 20 consecutive failed deliveries → endpoint `is_active = false`, org notified (in-app + team@). Re-enable via PATCH.
- Deliveries are recorded per attempt; `webhooks:cleanup` prunes delivery rows older than 30 days. See [scheduled-jobs.md](../../scheduled-jobs.md).

## 📏 Rules

- Business only. On downgrade endpoints are disabled, config retained. See [../billing/lifecycle.md](../billing/lifecycle.md).
- `https` URLs only; SSRF guard per the Security section below.
- Webhook management is audited: `org.webhook_created/updated/deleted` (`management` visibility).

## 🔐 Security

Outbound webhooks are the classic SSRF vector: the org supplies a URL and the platform makes a request to it from inside its own network. Every control below is required. A naive "reject private IPs" check fails to most of these.

### SSRF: resolve, then validate, then connect

Validating the URL string is not enough. `https://evil.example` can resolve to `127.0.0.1`.

| Control | Requirement |
|---------|-------------|
| Scheme | `https` only. Reject `http`, and explicitly reject `file`, `gopher`, `dict`, `ftp` and every other scheme cURL supports |
| Port | 443 only |
| Credentials in URL | Reject `https://user:pass@host/` outright |
| **Resolve before connecting** | Resolve the hostname yourself, validate **every** returned address, then connect to the validated IP with the original `Host` header. Never hand an unvalidated hostname to the HTTP client |
| Re-validate at delivery time | The record is validated on save, but DNS can change afterwards. Validation runs again on every delivery attempt. A save-time-only check is a DNS-rebinding hole |
| Redirects | Do not follow. A `302` to `http://169.254.169.254/` turns a compliant endpoint into an SSRF. A redirect is a delivery failure |

### Blocked address space

Reject if **any** resolved address falls in these ranges. IPv4 and IPv6 both, including IPv4-mapped IPv6 (`::ffff:127.0.0.1`):

| Range | Why |
|-------|-----|
| `127.0.0.0/8`, `::1` | Loopback: reaches services on the app server itself |
| `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `fc00::/7` | Private networks: reaches the managed databases and cache |
| `169.254.0.0/16`, `fe80::/10` | Link-local: **cloud metadata lives at `169.254.169.254`**; credential theft |
| `0.0.0.0/8`, `::` | Unspecified; `0.0.0.0` resolves to localhost on many stacks |
| `100.64.0.0/10` | CGNAT |
| `192.0.0.0/24`, `198.18.0.0/15`, `240.0.0.0/4` | Reserved / benchmarking |
| Multicast and broadcast | Not valid delivery targets |

Parsing matters as much as the ranges: `0177.0.0.1` (octal), `2130706433` (decimal), `127.1` (short form) and a trailing-dot `localhost.` all reach loopback. Use a real IP parser on the resolved address rather than pattern-matching the string.

### Delivery hardening

| Control | Requirement |
|---------|-------------|
| Total timeout | 10 s covering connect **and** read, a connect-only timeout lets a slow-drip response hold a worker open |
| Response size cap | Read at most a few KB. Nothing downstream needs the body, and an endpoint streaming gigabytes is a memory exhaustion attack |
| Stored response | Delivery rows keep the status code and a truncated body only. Otherwise a hostile endpoint writes arbitrary data into the database |
| TLS verification | Always on. No self-signed acceptance, no verification bypass, not even behind a config flag |
| Queue isolation | Deliveries run on their own queue. One org with 20 dead endpoints retrying must never starve `audit` or `notifications` |

### Scanning abuse

`POST .../webhooks/{webhook}/test` triggers an immediate request to an org-supplied URL, and the deliveries endpoint reports the response code. Together those turn the feature into a port and host scanner against arbitrary **public** infrastructure, the SSRF blocks only cover private space.

Mitigations: rate-limit the test endpoint per org, and report a coarse outcome (`failed`, with the HTTP status) rather than connection-level error detail that distinguishes "refused" from "filtered" from "timed out".

### Signing and secrets

| Control | Requirement |
|---------|-------------|
| Timestamp in the signature | Sign `timestamp.raw_body`, and send the timestamp in its own header, so receivers can reject replays. Signing the body alone gives them no way to detect a replayed delivery |
| Secret storage | Encrypted at rest, shown once at creation, never logged and never returned by any read endpoint |
| Rotation | Rotating must not require downtime: accept a second active secret for a grace period so the receiver can migrate |

### Payload boundaries

`data` mirrors the audit `display` structure, which carries both `internal` and `management` visibility events. Only `management`-visibility events for that organization may ever be delivered: filtering happens where the payload is built, not at the subscription layer, so a mis-subscribed event type cannot leak internal detail.

> [!WARNING]
> This section is a Phase 15 implementation requirement, not guidance. Each control needs a test: an endpoint resolving to loopback, one returning a redirect to link-local space, one streaming an oversized body, and one whose DNS changes between save and delivery.

## 🗄️ Tables

See [database/organizations.md](../../database/organizations.md) (`org_webhooks`, `org_webhook_deliveries`).

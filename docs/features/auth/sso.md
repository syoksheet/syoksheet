# SSO

**Deferred beyond v1.** Business tier billing must exist before there is anyone to gate, and building this against a real customer's Entra or Okta tenant beats guessing against a local Keycloak. What v1 must ship is the seam: org-scoped routes already routed through `EnsureOrgSsoSession`, implemented as step 1 below and nothing more, so adding OIDC later touches no routes. `sso_configs` and `org_members.sso_subject` are additive and ship with the feature.

Self-built OIDC SSO for Business organizations. It is the **org-context gate**, not a platform login method. Users authenticate to syoksheet personally; the org's IdP gates org space. Enforcement is automatic when enabled (no soft toggle). OIDC only. SAML is not planned. Product behavior in syoksheet-docs → features/authentication.md. Token exchange and validation go through a maintained Socialite OIDC driver, never hand-rolled: it must provide discovery, JWKS-based ID token validation with key rotation, nonce and PKCE, and must accept per-org configuration at runtime via `Socialite::buildProvider()`, since each org supplies its own client ID, secret and discovery URL. The specific package is chosen when the feature is built, comparing adoption and release history at that time.

## 🔐 The Gate

Org-scoped routes (org settings, teams, members, verification queue, jobs management, audit/activity views) pass an `EnsureOrgSsoSession` middleware:

1. If the org has no enabled `sso_configs` row → pass through (permission checks apply as normal).
2. If SSO is enabled → require an active **org SSO session** for that org: a session flag set on successful IdP authentication, valid **12 hours**, scoped per org.
3. No valid flag → redirect (Inertia) into the SSO flow, returning to the requested page on success; API-shaped requests get 403 with `code: sso_required`.

The user's platform session (personal login) is never affected. Members who fail SSO keep membership and personal access; they may still use `POST .../departures` to leave.

**Owner escape hatch:** the org owner reaches the SSO-settings endpoints with personal auth + password confirmation, bypassing the gate. A dead IdP must never brick the org's own controls.

## 🔄 Flow

1. `GET /api/v1/organizations/{org}/sso/redirect`: builds the authorization request from `sso_configs` (authorization code + PKCE + `state` + `nonce`; endpoints from the discovery document).
2. User authenticates at the IdP → callback `GET /api/v1/organizations/{org}/sso/callback`.
3. Library exchanges the code, validates the ID token (signature, issuer, audience, nonce, expiry).
4. **Subject binding:** if `org_members.sso_subject` is set for this membership, it must equal the token's `sub`. If unset (first SSO), match the email claim (via `claim_mapping` when nonstandard) against the member's verified emails, then store `sub` as `sso_subject`. Matching is by subject forever after. IdP email changes can't impersonate another member.
5. On success: org SSO session flag set (12 h). SSO never creates accounts or memberships.
6. Audited as `org.sso_authenticated` (subject: Organization, IP/UA captured). See [../audit/events.md](../audit/events.md).

## ⚙️ Configuration Endpoints

Owner or `org.manage`; the owner bypasses the SSO gate here (escape hatch above).

| Route | Behavior |
|-------|-----------|
| `GET /api/v1/organizations/{org}/sso` | Current config (secret redacted) + enablement state |
| `PUT /api/v1/organizations/{org}/sso` | Set `oidc_client_id`, `oidc_client_secret`, `oidc_discovery_url`, `claim_mapping?`: validates by fetching the discovery document |
| `POST /api/v1/organizations/{org}/sso/enable` | Enables enforcement: response includes the warning that all members now need IdP accounts |
| `POST /api/v1/organizations/{org}/sso/disable` | Owner + password confirmation: disables enforcement, keeps configuration |

Works with any spec-compliant OIDC IdP: Google Workspace, Microsoft Entra ID, Okta, JumpCloud, OneLogin, Auth0.

## 🗄️ Tables

`sso_configs` and `org_members.sso_subject`. See [database/organizations.md](../../database/organizations.md).

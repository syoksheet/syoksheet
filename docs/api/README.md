# API Reference

Conventions for the syoksheet API, its OpenAPI 3.1 spec ([openapi.json](openapi.json)), and the Bruno collection (`bruno/` at the repo root), the git-versioned, executable request collection used for interactive work and CI endpoint smoke tests.

> [!NOTE]
> `api.syoksheet.com` is the **external, sold surface**: public endpoints, Pro user tokens, the Jobs Push API, admin script tokens, and webhooks. Internal UI runs on Inertia web routes and does not consume this API. The OpenAPI spec and Bruno collection cover the external surface.

## 🌍 Environments

| Name | Base URL |
|------|----------|
| Local (DDEV) | `https://api.syoksheet.ddev.site` |
| Staging | `https://staging.api.syoksheet.com` |
| Production | `https://api.syoksheet.com` |

## 🔐 Authentication

Sanctum bearer tokens, two modes:

| Mode | How | Used by |
|------|-----|---------|
| User bearer (`userBearerAuth`) | `Authorization: Bearer {token}`: `user:api` ability | Pro third-party integrations, Jobs Push API (org Business tokens) |
| Admin bearer (`adminBearerAuth`) | `Authorization: Bearer {token}`: `admin:api` ability | Admin scripts |

Tokens are created in the respective UIs (password-confirmed). Interactive login/session auth belongs to the Inertia web surfaces, not this API. See [../architecture.md](../architecture.md).

## 📐 Conventions

- **Versioning**: authenticated app routes under `/api/v1/` (admin: `/api/admin/v1/`). Auth bootstrap routes (`/api/auth/*`) are unversioned.
- **Resources**: every response body is an Eloquent API Resource: `{ "data": ... }`, with `links`/`meta` for pagination.
- **Errors**. Laravel defaults: 401 `{ "message": "Unauthenticated." }`, 403, 404, and 422 `{ "message", "errors": { field: [...] } }`. Business-rule violations additionally carry a stable `code` (e.g. `brag_limit_reached`). The catalog lives in [../validation.md](../validation.md); frontends key off codes, never message strings.
- **Tags**, one per feature domain: `auth`, `users`, `organizations`, `brags`, `jobs`, `billing`, `taxonomy`, `public`, `admin`.
- **IDs**: public entities use UUIDs in URLs; users are addressed by username on public routes, orgs by slug.

## 🧭 Bruno Collection

`bruno/` at the repo root: `.bru` files organised in one folder per tag domain, opened directly in the Bruno desktop app for interactive use.

| Piece | Rule |
|-------|------|
| Environments | `local` (DDEV), `ci`, `staging`: non-secret config only. **Production is intentionally absent.** |
| Secrets | Gitignored `bruno/.env`, read via `{{process.env.X}}`: tokens and passwords never committed |
| Tests | Each request carries `assert`/`tests` blocks: status, response shape, and 401/403 guard checks: smoke depth only; business logic belongs to Pest |
| CI | `bru run bruno --env ci --reporter-junit` against a served app seeded by `BrunoSeeder`. See [../infrastructure/deployment.md](../infrastructure/deployment.md) |

## 📐 Division of Responsibility

| Artifact | Job |
|----------|-----|
| Pest | Primary test suite: business logic, DB state, authorization edge cases |
| Bruno | Executable collection + black-box HTTP smoke tests through real routing/middleware |
| OpenAPI | The contract, including the docs handed to Business orgs integrating against the Push API |

## 📋 Maintenance

- The markdown endpoint specs under [../features/](../features/) are the authoritative API design.
- A route change updates `openapi.json` **and** the Bruno collection **in the same commit**, neither may drift from the implementation.
- Keep the OpenAPI spec a single file; split into `$ref` components only when it exceeds ~500 lines per domain.

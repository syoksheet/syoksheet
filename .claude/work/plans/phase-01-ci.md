# Task 13: CI workflows

**Goal:** split CI into scoped workflow files, each covering one concern, with shared setup extracted so the split costs no duplication.

**Specs:** `docs/infrastructure/deployment.md` § CI Pipeline and § Secrets and CI variables, `.github/workflows/security.yml` for conventions.

**Audit events:** none. CI writes no application data.

## What the research settled

Three things were checked rather than assumed.

**Splitting is the ecosystem norm at scale.** Laravel's own framework repo has 13 workflow files, including a 743-byte `static-analysis.yml` on its own, and splits test workflows by the service they need (`databases`, `queues`, `redis`). The Laravel application skeleton ships one minimal `tests.yml` with sqlite and no Node, which is a starter and not a model for a production app.

**Pint's official CI example does not suit us.** It runs `pint` in fixing mode and then commits the result with `git-auto-commit-action`. That needs write permissions and produces bot commits. We use `pint --test`, which exits non-zero on a style error and changes nothing.

**Inpsyde's reusable workflow solves a different problem.** It publishes WordPress packages to consumers by committing build output to a branch, taking seven secrets including an SSH key, which turns a leaked CI credential into repository write access. We deploy to our own servers from an artifact store. What transfers is the idea of extracting shared setup, not the distribution model.

## Constraints

- **The repo stays private on a Free organisation**, decided 2026-08-31. Going public was considered and rejected: the repo is roughly ten times more specification than code (65 design files, 52 docs, 49 under `.claude/`, 15 under `app/`), and publishing it would expose the schema, the roadmap in `implementation-order.md`, and a current list of our own unmitigated gaps. GitHub Team at $4/month was considered and deferred until something actually needs it.
- **Revisit Team when** either a second person commits, or a red build could deploy something, whichever comes first. Team buys branch protection, environments and environment secrets for private repos. It does **not** buy deployment protection rules such as required reviewers, which stay public-repo-only below Enterprise.
- **Consequence while on Free:** Branch protection, rulesets, environments, environment secrets and organisation-level secrets are all unavailable. Every secret is a repository secret, and staging versus production is distinguished by name.
- **2,000 Actions minutes per month.** The whole suite runs in under a minute, so five workflows per push is comfortable, but it is a real ceiling.
- **Phase 1 needs no secrets at all.** Every gate is self-contained.
- Third-party actions are pinned to commit SHAs with the version in a trailing comment, never to tags.
- `permissions: contents: read` on every workflow.
- No path filters. A required check that never runs blocks a merge, and branch protection is not available to configure that safely anyway.

## Files

### Composite actions

Three of five workflows start with the same PHP setup and three with the same Node setup. Extracting them keeps the SHA pins in one place, so a version bump is one line rather than five.

| File | Does |
|---|---|
| `.github/actions/setup-php/action.yml` | Checkout, PHP 8.4, composer install with a cache keyed on `composer.lock` |
| `.github/actions/setup-node/action.yml` | Checkout, Node 24, `npm ci` with npm cache |

Referenced as `uses: ./.github/actions/setup-php`. Same-repo composite actions work on any plan; cross-repo sharing of private actions does not exist on Free.

### Workflows

| File | Jobs | Services | Notes |
|---|---|---|---|
| `php-quality.yml` | `pint`, `larastan` | none | `pint --test`. `phpstan analyse --error-format=github` for inline PR annotations |
| `php-tests.yml` | `pest` | postgres, redis | Needs **two** databases, see below |
| `frontend-quality.yml` | `format`, `lint`, `types` | none | `npm run format:check`, `npm run lint`, `npm run check` (svelte-check **and** `tsc -p tsconfig.node.json`) |
| `assets.yml` | `build` | none | `npm run build`, emitting client and SSR bundles. Artifact upload stubbed with a comment naming Phase 2 |
| `api-smoke.yml` | `bruno` | postgres, redis | migrate, seed, serve, `cd bruno && bru run --env ci --reporter-junit` |

Jobs are grouped into files by domain rather than one file per gate, matching `security.yml`, which already holds an `npm` and a `composer` job. Jobs inside a file still run in parallel.

## Gotchas found while planning

**The postgres service creates one database, we need two.** `POSTGRES_DB` makes a single database; the suite needs `syoksheet_primary_testing` and `syoksheet_audit_testing`. The test job needs an explicit `CREATE DATABASE` step mirroring `.ddev/scripts/create-databases.sh`.

**Redis is genuinely required.** The suite uses array cache, array session and a sync queue, so it looks like it might not be. With Redis unreachable, 4 tests fail: `RedisConnectionTest` connects for real.

**Bruno CLI only starts at a collection root**, so the step is `cd bruno && bru run --env ci`, not `bru run bruno`.

**Domain-scoped routes will not answer on `127.0.0.1:8000`.** `/up` works because Laravel registers it outside every `Route::domain()` group. The first real `api.` request in the collection needs either `DOMAIN_API` pointed at the served host or a `Host` header. Already flagged in `deployment.md`.

## Secrets

None in Phase 1. From Phase 2, as repository secrets, since environments are unavailable:

`FORGE_DEPLOY_HOOK_STAGING`, `FORGE_DEPLOY_HOOK_PRODUCTION`, `FORGE_API_TOKEN`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_ENDPOINT`, `CF_ACCESS_CLIENT_ID`, `CF_ACCESS_CLIENT_SECRET`, `SENTRY_AUTH_TOKEN`.

Bruno's seeded credentials stay plain CI variables. They protect a database that exists for one job.

## Out of scope

- Artifact upload to R2 and the Forge deploy hooks. Phase 2, stubbed here with comments.
- Source map upload to Sentry. Phase 3.
- Branch protection, required checks, CODEOWNERS. Unavailable on this plan for a private repo; revisit if the org moves to Team.
- A deploy workflow. Phase 2.

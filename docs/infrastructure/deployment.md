# Deployment

Deploying the API via Forge. Platform-wide branching, branch protection, and the other repos' pipelines live in syoksheet-docs → infrastructure/deployment.md.

## 🚀 Forge Deploy Script

```bash
cd /home/forge/app.syoksheet.com
git sparse-checkout set --no-cone '/*' \
  '!/.ddev/' '!/docs/' '!/design/' '!/bruno/' '!/tests/' \
  '!/.claude/' '!/.github/' '!/.mcp.json' '!/boost.json' '!/CLAUDE.md' \
  '!/.editorconfig' '!/.prettierrc' '!/.prettierignore' '!/eslint.config.ts' \
  '!/phpunit.xml' '!/README.md' '!/LICENSE.md' \
  '!/resources/ts/' '!/resources/scss/' '!/vite.config.ts' '!/svelte.config.js' \
  '!/tsconfig.json' '!/package.json' '!/package-lock.json' '!/.npmrc'
git pull origin $FORGE_SITE_BRANCH
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Built assets come from CI, keyed by commit SHA. Abort if missing: continuing
# would serve new server code against the previous deploy's JavaScript.
SHA=$(git rev-parse HEAD)
rm -rf public/build
aws s3 cp "s3://syoksheet-artifacts/$SHA.tar.gz" /tmp/build.tar.gz --endpoint-url "$R2_ENDPOINT" || exit 1
tar -xzf /tmp/build.tar.gz -C public/ || exit 1
rm /tmp/build.tar.gz

php artisan migrate:all --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan optimize
```

The Forge site carries all four surfaces as aliases (the apex, `api.`, `app.`, `admin.`) with one SSL cert; Laravel routes per host.

`php artisan migrate:all --force` runs both connections: primary and `log` (audit), each with its own migration path and history.

The `git sparse-checkout` line (idempotent, applies on every environment) keeps everything dev-, CI-, or human-only out of the server working tree; pulls stay clean because git itself owns the exclusion. None of these are web-reachable anyway (docroot is `public/`). This is about not shipping them at all.

Because assets are built in CI, the **build inputs are excluded too**.

| Path | On the server? | Why |
|------|----------------|-----|
| `resources/ts/`, `resources/scss/` | Excluded | Build inputs; the server never builds |
| `vite.config.ts`, `svelte.config.js`, `tsconfig.json` | Excluded | Same |
| `package.json`, `package-lock.json`, `.npmrc` | Excluded | No Node on the server |
| `resources/views/`, `lang/` | **Kept** | Blade renders the apex marketing pages and the Inertia root view; translations are read at runtime |
| `composer.json`, `composer.lock` | Kept | Needed by `composer install` |
| Lint configs | Excluded | Nothing lints on the server |
| `phpunit.xml`, `tests/` | Excluded | Tests run in CI |
| `.github/` | Excluded | Actions runs from GitHub's own copy |

**No Node is installed on either server.**

## 🤖 CI Pipeline (GitHub Actions)

Trigger: push/PR to `main` or `develop`, and `v*` tag pushes.

1. Checkout, set up PHP 8.4 + Node 24
2. `composer install` + `npm ci`
3. PostgreSQL + Redis service containers
4. Laravel Pint (style) + Larastan (static analysis)
5. Frontend checks: `svelte-check`, ESLint
6. Pest (`php artisan test`)
7. Vite production build (`npm run build`)
8. **Bruno endpoint smoke tests:** `php artisan migrate --force && php artisan db:seed --class=BrunoSeeder`, `php artisan serve &`, then `npx @usebruno/cli run bruno --env ci --reporter-junit results.xml`: seeded credentials injected via CI env vars; JUnit output annotates failures
9. On push to `main` (not PR): call the staging Forge deploy hook. On `v*` tag push: update the production Forge site's ref to the tag (Forge API) and call the production deploy hook

## 🔒 Supply-Chain Workflow

`.github/workflows/security.yml` runs independently of the CI pipeline above, on PR and push to `main`/`develop`, every Monday 06:00 UTC, and on manual dispatch. Two jobs:

- **npm**: `npm ci` (fails if `package.json` and the lockfile disagree), an assertion that every direct dependency is exact-pinned, `npm audit signatures` (registry signature + build provenance for all 231 packages), and `npm audit --audit-level=high`.
- **Composer**: `composer validate --strict` (also flags a stale lockfile) and `composer audit --locked`, which reads `composer.lock` without installing, so no package code and no Composer plugin executes on the runner.

Third-party actions are pinned to commit SHAs with the version in a trailing comment, never to tags. A tag can be repointed at malicious code, which is the same class of attack the workflow exists to catch. Bump the SHA and the comment together.

The weekly schedule matters: advisories get published against code that has not changed, so a PR-only trigger would never see them.

**Secrets:** `FORGE_DEPLOY_HOOK_PRODUCTION`, `FORGE_DEPLOY_HOOK_STAGING`, `FORGE_API_TOKEN` (to point the production site at the release tag; Bruno CI credentials are non-secret seeder values, set as plain CI env vars)

## ⏪ Rollback

- Re-point the production site at the previous `v*` tag and redeploy (or Forge → Site → Deployments → rollback to previous).
- Migration revert uses **point-in-time recovery, never `migrate:rollback`**. A `down()` that drops a column destroys everything written since.
- Migrations follow expand/contract, so the previously deployed release always works against the current schema. See the Migration safety gate in `.claude/skills/build-step/SKILL.md` and the restore runbook in syoksheet-docs → infrastructure/operations.md.

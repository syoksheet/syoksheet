# Deployment

Deploying the API via Forge. Platform-wide branching, branch protection, and the other repos' pipelines live in syoksheet-docs → infrastructure/deployment.md.

## 🚀 Forge Deploy Script

```bash
cd /home/forge/app.syoksheet.com
git sparse-checkout set --no-cone '/*' \
  '!/.ddev/' '!/docs/' '!/design/' '!/bruno/' '!/tests/' \
  '!/.claude/' '!/.github/' '!/.mcp.json' '!/boost.json' '!/CLAUDE.md' \
  '!/.editorconfig' '!/.prettierrc' '!/.prettierignore' '!/eslint.config.ts' \
  '!/phpunit.xml' '!/README.md' '!/LICENSE.md'
git pull origin $FORGE_SITE_BRANCH
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan optimize
```

The Forge site carries all four subdomains as aliases (`api.`, `app.`, `admin.`, `www.`) with one SSL cert; Laravel routes per subdomain.

`php artisan migrate --force` runs both connections — primary and `log` (audit).

The `git sparse-checkout` line (idempotent, applies on every environment — production, staging, dev) keeps everything dev-, CI-, or human-only out of the server working tree; pulls stay clean because git itself owns the exclusion. None of these are web-reachable anyway (docroot is `public/`) — this is about not shipping them at all. What must remain: the Laravel runtime tree, `resources/` + `vite.config.ts` + `svelte.config.js` + `tsconfig.json` + `.npmrc` (build inputs), both lockfiles, and `.gitignore`/`.gitattributes` (git reads them from the working tree). Lint configs can go because `npm run build` doesn't lint; `phpunit.xml`/`tests/` because tests run in CI; `.github/` because Actions runs from GitHub's copy. `composer install --no-dev` needs none of the excluded paths.

## 🤖 CI Pipeline (GitHub Actions)

Trigger: push/PR to `main` or `develop`, and `v*` tag pushes.

1. Checkout, set up PHP 8.4 + Node 24
2. `composer install` + `npm ci`
3. PostgreSQL + Redis service containers
4. Laravel Pint (style) + Larastan (static analysis)
5. Frontend checks: `svelte-check`, ESLint
6. Pest (`php artisan test`)
7. Vite production build (`npm run build`)
8. **Bruno endpoint smoke tests:** `php artisan migrate --force && php artisan db:seed --class=BrunoSeeder`, `php artisan serve &`, then `npx @usebruno/cli run bruno --env ci --reporter-junit results.xml` — seeded credentials injected via CI env vars; JUnit output annotates failures
9. On push to `main` (not PR): call the staging Forge deploy hook. On `v*` tag push: update the production Forge site's ref to the tag (Forge API) and call the production deploy hook

**Secrets:** `FORGE_DEPLOY_HOOK_PRODUCTION`, `FORGE_DEPLOY_HOOK_STAGING`, `FORGE_API_TOKEN` (to point the production site at the release tag; Bruno CI credentials are non-secret seeder values, set as plain CI env vars)

## ⏪ Rollback

- Re-point the production site at the previous `v*` tag and redeploy (or Forge → Site → Deployments → rollback to previous).
- Migration revert if needed: SSH in, `php artisan migrate:rollback` (mind the `log` connection).

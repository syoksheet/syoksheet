# Deployment

Deploying the API via Forge. Platform-wide branching, branch protection, and the other repos' pipelines live in syoksheet-docs → infrastructure/deployment.md.

## 🚀 Forge Deploy Script

```bash
cd /home/forge/app.syoksheet.com
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

## 🤖 CI Pipeline (GitHub Actions)

Trigger: push/PR to `main` or `staging`.

1. Checkout, set up PHP 8.4 + Node 20
2. `composer install` + `npm ci`
3. PostgreSQL + Redis service containers
4. Laravel Pint (style) + Larastan (static analysis)
5. Frontend checks: `svelte-check`, ESLint
6. Pest (`php artisan test`)
7. Vite production build (`npm run build`)
8. **Bruno endpoint smoke tests:** `php artisan migrate --force && php artisan db:seed --class=BrunoSeeder`, `php artisan serve &`, then `npx @usebruno/cli run bruno --env ci --reporter-junit results.xml` — seeded credentials injected via CI env vars; JUnit output annotates failures
9. On push to `main`/`staging` (not PR): call the Forge deploy hook

**Secrets:** `FORGE_DEPLOY_HOOK_PRODUCTION`, `FORGE_DEPLOY_HOOK_STAGING` (Bruno CI credentials are non-secret seeder values, set as plain CI env vars)

## ⏪ Rollback

- Forge → Site → Deployments → rollback to previous.
- Migration revert if needed: SSH in, `php artisan migrate:rollback` (mind the `log` connection).

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== inertia-svelte/core rules ===

# Inertia + Svelte

- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.

</laravel-boost-guidelines>

---

# Project Guidelines

@../syoksheet-docs/claude-context.md

This repo is **the syoksheet application**: one Laravel app serving `api.*`, `app.*`, `admin.*`, and the apex (`syoksheet.com`, marketing) via `Route::domain()` groups. The app and admin UIs are Inertia + Svelte 5 + TS; the apex is server-rendered Blade; `api.*` is the external sold API only (Pro tokens, Jobs Push API, webhooks, public endpoints).

## PHP

- Use PHP enums instead of string or integer constants wherever a fixed set of values is involved. Use `->value` when passing to the database or comparing against raw strings; use enum cases everywhere internally.

## Frontend (Inertia + Svelte)

- Svelte 5 + TypeScript under `resources/ts/`: strict `tsconfig`, ESLint, `svelte-check`. **TypeScript only: no `.js` source files** (sole exception: `svelte.config.js`, required by name by the Svelte tooling). Never React, never Livewire.
- Components build on headless primitives (Bits UI), styled **only** with the design system's tokens and spec CSS, never a styled component library, never local restyles of shared components. **Tables are built in-house**, no TanStack Table, no TanStack packages at all; table state (sort, filter, paginate, select) lives in our own Svelte 5 runes-based composable against the `DS Table` / `DS Data Table` specs.
- The design system lives in `design/`: `docs/` = full component specs (behavior, a11y), `previews/` = the cards mirrored to the Claude Design "syoksheet Design System" project: keep repo and project in sync via DesignSync when either changes. Screen designs live in the Claude Design studio project "syoksheet".
- The verification mark's forest green is never the primary teal. Shared components are presentational, no API calls or product logic inside them.
- All UI strings go through translation files (English at launch), no hardcoded user-facing text. **One exception: the apex marketing pages** (homepage, `/for-organizations`, `/pricing`) keep their copy inline. Marketing prose is rewritten constantly and reads badly as fragmented keys, and localization is deferred until a second language ships. Everything under `app.` and `admin.`, and the product-facing public pages such as walls and the jobs directory, still go through translation files.
- Apex (marketing) pages: SEO/OG meta on every public page; GTM with Consent Mode v2 per syoksheet-docs → marketing/analytics-stack.md; the rendered privacy policy / ToS pages live here (log versions in syoksheet-docs/legal/policy-versions.md before shipping changes).
- Before building any screen: read the feature doc (syoksheet-docs), this repo's feature spec, and the screen design.

## Documentation Lookup

Two doc sources, split by coverage, never guess a versioned API from memory when either one covers it.

- **Laravel ecosystem → Boost `search-docs`.** Laravel itself, Inertia (server side), Pest, Pint, Larastan, Telescope, Horizon, the `spatie/*` packages, and anything else Boost indexes. Local, free, version-matched to what's installed. Always try this first.
- **Everything else → Context7.** Svelte 5 and runes, Bits UI, Vite, TypeScript, ESLint, Prettier, `sass-embedded`, DodoPayments, and any library Boost does not index. Explicitly reach for Context7 rather than answering from memory, this stack moves faster than the training cutoff.
- If neither source covers it, say so and check the library's own repo. Do not invent an API.

Context7 runs on the free tier: **1,000 tool calls per month**, and one call is one tool invocation, not one prompt. Two habits keep it in budget:

- Pass a known Context7 library ID (e.g. `/sveltejs/svelte`) straight to `query-docs` so the `resolve-library-id` round trip is skipped, that halves most lookups.
- Look things up when a specific API is actually in question, not reflexively at the start of every task.

## Build Loop

All build work in this repo, whether or not the phase is named, follows `.claude/skills/build-step/SKILL.md`, which is self-contained. Do not invoke superpowers skills; build-step already carries that discipline. These gates hold regardless of how the work was started:

- The plan is written to `.claude/work/plans/` and **explicitly approved by the user** before any implementation.
- Audit events exist in `docs/features/audit/events.md` **before** the code that fires them.
- `docs/api/openapi.json` and the `bruno/` collection are updated in the same commit as any route change.
- Pint, Larastan and the affected test suite are green, with the output shown, before anything is called done.
- The `spec-reviewer` agent runs on the changes before completion is reported.
- **Never run git write operations**, no add, commit, push, tag, branch, worktree, stash or reset. The user handles all git himself, whatever a skill instructs. Read-only git (`status`, `diff`, `log`) is fine.

## Planning & Documentation

- This repo's `docs/` holds the technical spec (database schema, endpoints, jobs, events, validation, infrastructure). Product behavior lives in `syoksheet-docs`. See the shared context above.
- Before starting any build work, read the relevant `syoksheet-docs` feature doc for product behavior, plus this repo's `docs/features/` implementation spec and `docs/database/` schema.
- Save design specs (from brainstorming) to `.claude/work/specs/` and implementation plans to `.claude/work/plans/`. Wait for user approval before proceeding with implementation.
- Follow conventions defined in `docs/database/README.md` and the Documentation Conventions section below.

## Audit Log

- Every feature that creates, updates, or deletes user or org data must fire the appropriate audit log event. Read `docs/features/audit/events.md` for the full events catalog before planning any feature.
- Audit log events write to the **separate audit database** (`log` connection) via `AuditLogJob`, never write to the audit DB directly from a controller or model.
- Always set the correct `visibility` on events: `internal` (admin only) or `management` (org owner + Admin team can see).
- When planning a new feature, identify which audit events it needs and add them to `docs/features/audit/events.md` before implementation.

## Data Privacy

- Any feature that handles personal data must consider: consent requirements, GDPR data export inclusion, and erasure handling.
- Read `docs/features/privacy/` before planning any feature that touches user data, consent, account deletion, or data export.
- If a new field contains personal data, it must be included in the `GenerateDataExportJob` and handled in the Tier 2 anonymization job (`gdpr:anonymize-accounts`).
- If a new consent type is needed, add it to the `ConsentType` enum and `docs/features/privacy/consent.md` before implementing the feature that requires it.

## Documentation Conventions

API technical documentation lives in this repository under `docs/`. Formatting rules are in the shared context (imported above); the rules below are api-specific.

### Directory structure (this repo)

```
docs/
├── README.md                 - navigation index (points to syoksheet-docs for product docs)
├── architecture.md           - guards, databases, queues, events, integrations
├── scheduled-jobs.md         - canonical Artisan schedule
├── validation.md             - uploads, identifiers, business-rule error codes
├── localization.md           - locale handling, taxonomy translations, Weblate workflow
├── ai.md                     - AiService abstraction, use cases, review gates
├── database/                 - README (conventions) + one file per domain
├── features/{domain}/        - implementation specs: endpoints, jobs, validation, events
├── infrastructure/           - local-development, environment-variables, deployment (API-scoped)
└── api/
    ├── README.md             - environments, auth modes, conventions, Bruno collection rules
    └── openapi.json          - OpenAPI 3.1 spec (hand-maintained)
```

The Bruno collection lives at the repo root (`bruno/`, one folder per API tag, environments without secrets, gitignored `bruno/.env`). Any change to the external API updates `openapi.json` **and** the Bruno collection in the same commit. The design system mirror also lives at the repo root (`design/`, `docs/` specs + `previews/` cards).

### OpenAPI spec rules

- Single file `docs/api/openapi.json`; split into `$ref` components only when it exceeds ~500 lines
- Tags map to feature domains: `auth`, `users`, `organizations`, `brags`, `jobs`, `billing`, `taxonomy`, `public`, `admin`
- Update `openapi.json` in the same commit as any route or controller change

## Local Environment & Commands

All project services (PHP, PostgreSQL, Redis, and anything added later) run inside DDEV. Never interact with them directly from the host, always go through DDEV:

| Service               | Command                                           |
|-----------------------|---------------------------------------------------|
| PHP / Artisan         | `ddev php artisan ...`                            |
| Pint                  | `ddev php vendor/bin/pint ...`                    |
| Tinker                | `ddev php artisan tinker ...`                     |
| PostgreSQL            | `ddev psql` (or use Boost `database-query` tool)  |
| Redis                 | `ddev php artisan tinker` (no `redis-cli` in the container) |
| Any container command | `ddev exec ...`                                   |

> The Boost block above shows generic examples like `php artisan` and `vendor/bin/pint`: prefix all of them with `ddev php`.

- **Never run bare `npm install`**: it re-resolves the tree. Use `ddev exec npm ci` to install, and `ddev exec npm install <pkg>` only to deliberately add one (`.npmrc` sets `save-exact=true`, `ignore-scripts=true`). Dependencies are pinned to exact versions in `package.json`; CI fails on any `^`/`~` range. Composer keeps `^` ranges and relies on `composer.lock`.
- Never create or modify `.env` files directly.

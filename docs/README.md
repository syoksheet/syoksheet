# API Docs

Technical documentation for the syoksheet Laravel API. The implementation spec the codebase is built from. Product and platform documentation (what features do, pricing, decisions, platform infrastructure) lives in the `syoksheet-docs` project.

## 🏗️ Core

- [API Architecture](architecture.md): guards, databases, queues, events, integrations
- [Scheduled Jobs](scheduled-jobs.md): canonical Artisan schedule
- [Validation Conventions](validation.md): uploads, identifiers, business-rule error codes
- [Localization](localization.md): locale handling, taxonomy translations, Weblate workflow, English-first launch
- [AI Integration](ai.md). Laravel AI SDK, use cases, review gates, no-personal-data rule

## 🗄️ Database

- [Conventions & Index](database/README.md)
- [Users](database/users.md) · [Organizations](database/organizations.md) · [Brags](database/brags.md) · [Taxonomy](database/taxonomy.md) · [Jobs](database/jobs.md) · [Location](database/location.md) · [Billing](database/billing.md) · [Analytics](database/analytics.md) · [Privacy](database/privacy.md) · [Admin](database/admin.md) · [Audit](database/audit.md)

## ⚙️ Feature Specs

- **Auth**: [Endpoints](features/auth/endpoints.md) · [Two-Factor](features/auth/two-factor.md) · [SSO](features/auth/sso.md)
- **Users**: [Profile](features/users/profile.md) · [Emails](features/users/emails.md) · [Tokens](features/users/tokens.md) · [Notifications](features/users/notifications.md) · [PDF Export](features/users/pdf-export.md)
- **Organizations**: [Endpoints](features/organizations/endpoints.md) · [DNS Verification](features/organizations/dns-verification.md) · [Outbound Webhooks](features/organizations/webhooks.md)
- **Brags**: [Endpoints](features/brags/endpoints.md) · [Verification](features/brags/verification.md) · [Collaborators](features/brags/collaborators.md)
- **Jobs**: [Endpoints & Push API](features/jobs/endpoints.md) · [Matching](features/jobs/matching.md)
- **Taxonomy**: [Endpoints](features/taxonomy/endpoints.md) · [Import & Administration](features/taxonomy/import.md)
- **Billing**: [Endpoints & Webhooks](features/billing/webhooks.md) · [Lifecycle & Dunning](features/billing/lifecycle.md)
- **Public**: [Endpoints](features/public/endpoints.md)
- **Admin**: [Endpoints](features/admin/endpoints.md) · [Impersonation](features/admin/impersonation.md)
- **Audit**: [Implementation](features/audit/implementation.md) · [Events Catalog](features/audit/events.md)
- **Privacy**: [Consent](features/privacy/consent.md) · [Data Export](features/privacy/data-export.md) · [Account Deletion](features/privacy/account-deletion.md) · [Security Incidents](features/privacy/security-incidents.md)

## 📡 API Reference

- [Conventions, Auth Modes & Bruno Collection](api/README.md)
- [OpenAPI Spec](api/openapi.json), the contract; grows per feature, same commit as route changes
- Bruno collection: `bruno/` at the repo root, holding executable requests and CI smoke tests

## 🛠️ Infrastructure

- [Local Development](infrastructure/local-development.md). DDEV
- [Environment Variables](infrastructure/environment-variables.md)
- [Deployment](infrastructure/deployment.md). Forge + CI

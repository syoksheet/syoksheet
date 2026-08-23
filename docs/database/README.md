# Database

Two PostgreSQL 16 instances. The primary database holds all application data; the audit database is a separate, append-only instance for compliance records.

## ⚙️ Conventions

| Convention | Rule |
|------------|------|
| Case | `snake_case` for all table and column names |
| Public IDs | `uuid` — users, brags, organizations, verifications, notifications |
| Internal IDs | `bigint` — junction tables, lookup tables, taxonomy, audit log |
| Timestamps | `timestamptz` on all `created_at`, `updated_at`, `deleted_at` columns |
| Soft deletes | users, brags, organizations, verifications |
| Enums | Stored as `varchar`, validated by PHP enums in the app — never DB enum types |
| Audit DB | Separate connection (`log`). No FK constraints, no `updated_at`, no soft deletes. Append-only forever. |

## 📂 Files

| File | Domain | Tables |
|------|--------|--------|
| [users.md](users.md) | Users & auth | users, user_emails, social_accounts, password_reset_tokens, notifications |
| [organizations.md](organizations.md) | Orgs & teams | organizations, org_members, org_teams, org_team_members, org_domains, org_invitations, org_join_requests, org_departures, ownership_transfers, dns_verifications, sso_configs, org_wall_pins, org_webhooks, org_webhook_deliveries |
| [brags.md](brags.md) | Brags & verification | brags, brag_tags, brag_attachments, brag_links, brag_collaborators, brag_skills, verification_requests, verifications, verification_rejections |
| [taxonomy.md](taxonomy.md) | Taxonomy | data_providers, occupation_categories, occupations, occupation_aliases, occupation_provider_mappings, skill_categories, skills, skill_aliases, skill_provider_mappings, occupation_skills, industries, industry_provider_mappings |
| [jobs.md](jobs.md) | Jobs | job_postings, job_posting_skills, match_scores, job_interests |
| [location.md](location.md) | Location reference | countries, states, cities |
| [billing.md](billing.md) | Billing | subscriptions, webhook_events |
| [analytics.md](analytics.md) | In-product analytics | view_events, analytics_monthly_aggregates |
| [privacy.md](privacy.md) | Data privacy | consent_records, user_data_export_requests, account_deletion_requests |
| [admin.md](admin.md) | Admin | admins, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions |
| [audit.md](audit.md) | Audit DB *(separate instance)* | audit_logs, security_incidents, security_incident_affected_records |

## 🗂️ Table Summary

**Primary database — 61 tables**

```
Users & Auth (4): users, user_emails, social_accounts, password_reset_tokens
Notifications (1): notifications
Orgs & Teams (14): organizations, org_members, org_teams, org_team_members, org_domains, org_invitations,
                   org_join_requests, org_departures, ownership_transfers, dns_verifications, sso_configs,
                   org_wall_pins, org_webhooks, org_webhook_deliveries
Brags (9): brags, brag_tags, brag_attachments, brag_links, brag_collaborators,
           brag_skills, verification_requests, verifications, verification_rejections
Taxonomy (13): data_providers, occupation_categories, occupations, occupation_aliases,
               occupation_provider_mappings, skill_categories, skills, skill_aliases,
               skill_provider_mappings, occupation_skills, industries, industry_provider_mappings,
               taxonomy_translations
Jobs (4): job_postings, job_posting_skills, match_scores, job_interests
Location (3): countries, states, cities
Billing (2): subscriptions, webhook_events
Analytics (2): view_events, analytics_monthly_aggregates
Privacy (3): consent_records, user_data_export_requests, account_deletion_requests
Admin (6): admins, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
```

**Audit database — 3 tables**

```
audit_logs, security_incidents, security_incident_affected_records
```

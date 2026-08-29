# Validation Conventions

Platform-wide validation rules referenced by every feature spec: uploads, identifiers, and standard field norms. Enforced in Form Requests; limits here are the canonical values.

## 📎 Uploads

| Upload | Max size | Allowed types |
|--------|----------|---------------|
| Avatar | 2 MB | jpeg, png, webp |
| Org logo | 1 MB | jpeg, png, webp, svg |
| Org cover image (branding) | 5 MB | jpeg, png, webp |
| Brag attachment | 10 MB | pdf, jpeg, png, webp |

Files upload to `syoksheet-private-{env}`; images are validated as real images (not just extension). Attachment count per brag is tier-limited (syoksheet-docs → product/pricing.md).

## 🔤 Identifiers

Usernames, custom slugs (Pro wall URLs), and org slugs share one rule set:

- 3–30 characters, lowercase `a-z`, `0-9`, hyphen; no leading/trailing or consecutive hyphens
- Unique within their column, checked case-insensitively
- **Reserved names** (one shared list): `admin`, `api`, `app`, `www`, `support`, `billing`, `team`, `help`, `about`, `jobs`, `org`, `user`, `settings`, `login`, `register`, `syoksheet`, and route-colliding terms (maintained as a config array)

## 📏 Standard Field Norms

| Field type | Rule |
|-----------|------|
| Names / titles | max 255 |
| Emails | max 255, RFC validation, unique platform-wide |
| URLs | max 500 (brag links: 2000), valid http(s) |
| Freeform text (bio, descriptions, comments) | max 5000 |
| Tags | max 100 chars each |
| Passwords | Laravel `Password::default()`: min 8 |

## 🚫 Business-Rule Error Codes

Rule violations return 422 with a stable `code` alongside the message, so frontends key off codes, never strings:

| Code | Raised by |
|------|-----------|
| `brag_limit_reached` | Brag creation over tier limit |
| `pending_verification_limit_reached` | Verification request over tier limit |
| `attachment_limit_reached` | Attachment over tier limit |
| `queue_capacity_reached` | Org verification queue full |
| `job_posting_limit_reached` | Publish over tier limit |
| `member_limit_reached` / `team_limit_reached` | Org additions while over free limits |
| `fields_locked` | Editing locked brag fields |
| `owned_orgs_exist` | Account deletion while owning orgs |
| `export_cooldown` / `export_in_progress` | Data/PDF export rules |
| `consents_required` | Open-to-work enable without JobMatching + AiProcessing |
| `reserved_name` | Username/slug on the reserved list |
| `sso_required` | Org-space request without an active org SSO session (403) |
| `work_domain_primary` | Making an address on a DNS-verified org domain the primary email |

New business rules add their code here and to the OpenAPI `BusinessRuleError` component.

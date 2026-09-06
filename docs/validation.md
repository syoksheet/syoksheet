# Validation Conventions

Platform-wide validation rules referenced by every feature spec: uploads, identifiers, and standard field norms. Enforced in Form Requests; limits here are the canonical values.

## 📎 Uploads

| Upload | Max size | Allowed types |
|--------|----------|---------------|
| Avatar | 2 MB | jpeg, png, webp |
| Org logo | 1 MB | jpeg, png, webp, svg |
| Org cover image (branding) | 5 MB | jpeg, png, webp |
| Brag attachment | 10 MB | pdf, jpeg, png, webp |

Files upload to `syoksheet-private-{env}`; images are validated as real images (not just extension). Attachments are a Pro feature, unlimited on Pro and unavailable on Free (syoksheet-docs → product/pricing.md).

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
| `attachment_requires_pro` | Attachments are a Pro feature; Free has none |
| `job_posting_limit_reached` | Publish over tier limit |
| `fields_locked` | Editing locked brag fields |
| `owned_orgs_exist` | Account deletion while owning orgs |
| `export_cooldown` / `export_in_progress` | Data/PDF export rules |
| `consents_required` | Open-to-work enable without JobMatching + AiProcessing |
| `reserved_name` | Username/slug on the reserved list |

New business rules add their code here and to the OpenAPI `BusinessRuleError` component.

## ⏱️ Abuse Rate Limits

Rate limits return 429 with a `Retry-After` header and a stable `code`. They are not tier limits: every one of these applies to every account on every plan, and none of them appears in the pricing table. A limit that differs by plan is a business rule and belongs in the table above.

| Code | Limit | Protects |
|------|-------|----------|
| `brag_creation_rate_limited` | syoksheet-docs → product/pricing.md | Content pollution. Nobody writes 30 real achievements in an hour |
| `verification_rate_limited` | syoksheet-docs → product/pricing.md | Sending reputation. Each request emails someone who may have no account |

New abuse limits add their code here and to the OpenAPI `RateLimitError` component. The values live in `config/abuse.php`, which mirrors the canonical numbers in pricing, so tuning one never means changing code.

### Authorization codes

`sso_required` used to sit in the table above. It does not belong there: it answers a
different question. A 422 means the request was allowed but the system's state refused
it, such as hitting a tier limit. A 403 means the caller may not do this at all, which
is what a missing org SSO session is. It is also enforced in middleware, before any
controller runs a business check.

It has no home yet because `ForbiddenError` carries only a `message`, with no machine-
readable code, and the frontend needs one to tell "redirect to the IdP" apart from an
ordinary refusal.

**When org SSO is built:** add a `code` field to the `ForbiddenError` component and
raise `sso_required` through that, not through `BusinessRuleException`.

# Localization

Language-agnostic infrastructure shipped in v1 with English as the only first-class language at launch. Adding a language later is content work (translation files + taxonomy translations), never engineering work.

## 🌐 Locale Handling

- `users.locale`. BCP 47 code, default `en`. Editable in account settings; initialized from the browser's `Accept-Language` on registration.
- API responses, emails, and notifications resolve against the user's locale, falling back to `en` for any missing translation.
- Unauthenticated/public endpoints resolve from `Accept-Language`.
- UI strings live in this repo's frontend tree, keyed through translation files consumed by the Svelte pages; the API contract stays locale-neutral (identifiers and enums never localize, only display text does).

## 🗂️ Taxonomy Translations

One polymorphic table (see [database/taxonomy.md](database/taxonomy.md)):

```
taxonomy_translations: translatable_type, translatable_id, locale, name
```

- Covers occupations, skills, categories, and industries.
- API taxonomy responses return the localized `name` when a translation exists for the user's locale, else the canonical (English) name.
- Meilisearch indexes translations alongside canonical names and aliases, so search works in any supported language.
- Population pipeline (when a language is added): taxonomy labels are exported to translation files (`taxonomy:translations-export`), translated in **Weblate** (self-hosted TMS, the GlotPress equivalent for this stack, deployed via `syoksheet-weblate`), then imported back (`taxonomy:translations-import`) and re-indexed. No AI in our architecture. Weblate's optional machine-translation suggestions are a TMS setting reviewers may enable, outside our code.

> [!NOTE]
> ESCO's own multilingual labels cover EU languages only. Malay, Chinese, and Tamil translations are produced through the Weblate workflow, not imported.

## 🔤 UI Strings & Email Templates

Laravel lang files and the Svelte UI's translation files (same repo) are managed in one Weblate instance via git integration: translators work in the web UI, translations land as ordinary commits.

## ✉️ Emails & Notifications

Templates keyed by locale with `en` fallback. The audit log's `display` jsonb is already locale-neutral (structured data + frontend templates), no changes needed there.

## 📏 Rules

- A language becomes selectable in settings only when its UI strings and taxonomy translations pass review, no partially-translated locales exposed.
- English is the fallback everywhere and can never be removed.
- Translated languages are post-launch content drops chosen by user demand: none is pre-committed. See syoksheet-docs → product/roadmap.md.

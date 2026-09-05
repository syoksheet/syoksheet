# Phase 2: Frontend Foundations

**Goal:** Put the frontend on its footing so every later phase can build screens: Bits UI installed, a home and naming for shared components, and the app shell every signed-in page sits inside.

**Specs:**
- `design/docs/DS App Shell.html` (the layout contract), `DS Logo.html`, `DS Bits UI.html` (which primitive backs which component)
- `resources/scss/` (`_primitives`, `_semantic`, `_typography`, `_breakpoints`, `_fonts`), shipped in Phase 1
- `.ai/rules/ts.md`: the SSR module-scope rule, shared state in context, the form field API, the pages layout and entry shorthand
- `.ai/rules/components.md`: folder per component, the `.svelte.ts` extension, group names
- CLAUDE.md, Frontend section

**Audit events:** None. This phase writes no user or org data and touches no database.

## What changed, and why this phase is small

The original plan built all twenty-eight components up front from `design/docs/`. That is withdrawn.

Components are now built **by the phase whose screens need them**. Building the full library first means building components nobody has used yet, validated against specs nobody has exercised, and roughly a third would never be used at all. The screens are the real test.

There is also no local gallery route. A component is looked at in the screen that uses it, which exercises it properly rather than in isolation.

What remains here is only what every later phase depends on, and what nothing can be built without.

## Constraints

| Rule | Source |
|---|---|
| Bits UI for headless behaviour, styled **only** with semantic tokens | CLAUDE.md |
| A component never references a primitive token (`--n-*`, `--teal-*`) | `_semantic.scss` |
| Never a styled component library, never local restyles of shared components | CLAUDE.md |
| Shared components are presentational: no API calls, no product logic | CLAUDE.md |
| All user-facing strings through translation files from the first component | CLAUDE.md |
| Shared state in context, never at module scope, and lint cannot catch it | `.ai/rules/ts.md` |
| TypeScript only. No `.js` source files | CLAUDE.md |

## Open question

### Bits UI is not installed, and adding it needs approval

`package.json` has no `bits-ui`. Dependencies are not changed without approval, and `.npmrc` sets `save-exact`, so this needs a decision and a chosen version before Task 1.

## Tasks

### Task 1: Install Bits UI and lay out the component directory

**Files:** `package.json`, `vite.config.ts`, `resources/ts/components/`.

**Behaviour:** Bits UI installed at a pinned version, and a home for shared components grouped to mirror the spec sections so a path answers which spec it implements:

```
resources/ts/components/{group}/{name}/
  index.ts          re-exports the entry
  Toast.svelte
  ToastRegion.svelte
  toast.svelte.ts   context store

groups: actions  forms  data  feedback  navigation  layout
```

**One folder per component, even a one-file one.** Imports are `$components/feedback/toast` and stay that way when the inside grows. Promoting a bare `Badge.svelte` to a folder later would rewrite every call site.

**No library-wide barrel.** A barrel re-exporting all components makes one import pull the whole module graph and undoes the per-domain bundle split the entry shorthand exists to create. The per-component `index.ts` above is not that: it reaches only its own files.

A `$components` alias in `vite.config.ts`, so a deep page does not import through `../../../`.

**Who writes:** user. Vite, the dependency discipline and the bundle split are named fluency goals in `learning-mode.md`.

**Read first:** `.ai/rules/ts.md` and `.ai/rules/components.md` in full, `vite.config.ts`, `.npmrc`, and the Bits UI docs through Context7 (`/huntabyte/bits-ui`) rather than from memory.

**Trap:** a module using runes outside a `.svelte` file must be named `.svelte.ts`. Named `toast.ts`, `$state` does not compile and the error does not point at the filename. It bites first on the toast store, and again on the table composable in Phase 2b.

- [ ] `ddev exec npm run check` and `npm run lint` clean
- [ ] An alias import resolves from a page

### Task 2: App shell and logo

**Files:** `resources/ts/components/layout/`.

**Behaviour:** The layout contract in `design/docs/DS App Shell.html`, exactly. The sidebar steps through `--layout-sidebar` and is always `flex-shrink: 0`; the content column is the only element that flexes and carries `min-width: 0`; the breadcrumb truncates before actions drop labels, and actions fold into an overflow rather than being clipped. The shell never scrolls horizontally.

The logo is a CSS mask taking `currentColor`, thickening to the compact master at 28px and below, with the reversed tile under 20px.

**Who writes:** user.

**Read first:** both spec pages, and `_semantic.scss` for the stepped tokens, which already exist and must not be duplicated in the component.

**Trap:** the verification mark's forest green is never the brand teal, and the logo is never `--color-verified`.

- [ ] The shell holds at every breakpoint in `_breakpoints.scss` with no horizontal scroll
- [ ] `npm run check` and `npm run lint` clean

### Task 3: Documentation

**Files:** `.claude/work/plans/implementation-order.md`, `.ai/rules` via `record-rule`, `docs/architecture.md` if the component layout is worth recording there.

**Behaviour:** Each feature phase's row states that it owns the components its screens need. Conventions that emerge from Tasks 1 and 2 are recorded as rules rather than left in this plan, which is deleted when the phase closes.

**Who writes:** claude.

## Artifacts

- [ ] No routes, so no `openapi.json` and no `bruno/` change
- [ ] No business rule, no scheduled command, no personal-data field, no audit event, no consent type
- [ ] Design system changes keep `design/` and the Claude Design project in sync via DesignSync while `design/` still exists

## Observability review

No routes, jobs, commands or exception types. `@sentry/svelte` is in `package.json` and not yet wired to an entry point; wiring it is not this phase.

## Migration safety

No schema changes.

## Out of scope

- **The other twenty-six components.** Each arrives with the phase whose screens need it. Phase 3's auth screens will bring Button, Field, Alert and Card.
- **Table and Data Table.** Phase 2b, with its own plan.
- **A component gallery.** Deliberately not built.
- **Screens.** This phase builds no pages.
- **Wiring Sentry's browser SDK.**

## Noticed, owed later

- **`design/` is intended for deletion** once components exist and Figma replaces it. Its pages are not mockups: they carry behaviour and accessibility rules that Figma does not usually hold, such as validate-on-blur, focus return, the truncation order and the whole App Shell contract. Before deletion those need a home, `.ai/rules` for what constrains code and this repo's `docs/` for the rest. CLAUDE.md's Frontend section and the DesignSync instruction both describe `design/` as it stands today and would need rewriting with it.

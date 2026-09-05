# Phase 2: Design System

**Goal:** Build the Svelte 5 component library the rest of the product is assembled from, from the specs in `design/docs/`, on headless Bits UI primitives styled only with the SCSS tokens Phase 1 shipped. Tables are Phase 2b.

**Specs:**
- `design/docs/` (30 component specs) and `design/previews/` (32 preview cards)
- `resources/scss/` (`_primitives`, `_semantic`, `_typography`, `_breakpoints`, `_fonts`), shipped in Phase 1
- `.ai/rules/ts.md` (SSR module-scope rule, page layout, entry shorthand)
- `docs/architecture.md` line 49 (headless primitives styled by tokens)
- CLAUDE.md, Frontend section (Bits UI only, tables in-house, no styled component library)

**Audit events:** None. This phase writes no user or org data and touches no database.

## Constraints

Copied from the specs, not paraphrased.

| Rule | Source |
|---|---|
| Bits UI for headless behaviour, styled **only** with design-system tokens | CLAUDE.md |
| Never a styled component library, never local restyles of shared components | CLAUDE.md |
| Shared components are presentational: no API calls, no product logic inside them | CLAUDE.md |
| All user-facing strings through translation files from the first component | CLAUDE.md |
| The verification mark's forest green is never the primary teal | CLAUDE.md |
| No mutable state at module scope, including inside `<script module>`, which lint cannot catch | `.ai/rules/ts.md` |
| TypeScript only. No `.js` source files | CLAUDE.md |

## Holes to resolve before implementation

Four things the specs do not decide. Per the build loop these get settled with the user and written to `.claude/work/specs/` before any component is written.

### H1: Bits UI is not installed, and adding it needs approval

`package.json` has no `bits-ui`. Every spec in this phase assumes it. Dependencies are not changed without approval, and `.npmrc` pins exact versions, so this needs both a decision and a chosen version.

### H2: There is no App Shell spec, and the "layout contract" does not exist

The phase description requires "app shell + sidebar per the layout contract". `App Shell` and `Logo` have preview cards in `design/previews/` but **no spec page in `design/docs/`**, and the phrase "layout contract" appears nowhere in either repo outside that one sentence. Two of the phase's deliverables therefore have no specification.

Either the contract exists somewhere not yet mirrored, or it needs writing before the shell is built.

### H3: `design/docs/DS PrimeNG.html` is dead history

Its headings are "PrimeNG mapping", "Setup", "Token mapping", "Component mapping". PrimeNG is an Angular library; this project is Svelte with Bits UI. The file is a leftover from a superseded stack and will actively mislead.

What is genuinely missing is its Svelte equivalent: which Bits UI primitive backs which component, and which token each part uses. The specs are design-level, with no mention of Bits UI, Svelte or token names anywhere.

### H4: No component directory structure or preview route exists

`resources/ts/` holds entries, `pages/` and `types/` only. Nothing decides where shared components live, how they are named, or how one is looked at while building it. Thirty components with no way to see them is the difference between a week and a month.

## Tasks

Ordered by dependency. Each wave is verifiable on its own.

### Task 1: Foundations

**Files:** `package.json`; `resources/ts/components/` structure; a preview route and page per domain as decided in H4.

**Behaviour:** Bits UI installed at a pinned version, a decided home for shared components, and a local page that renders every component built so far.

**Who writes:** user. Vite, Svelte and the dependency discipline are named fluency goals in `learning-mode.md`.

**Read first:** `.ai/rules/ts.md` in full, `vite.config.ts`, `.npmrc`, and the Bits UI documentation via Context7 (`/huntabyte/bits-ui`), not from memory.

- [ ] `ddev exec npm run check` and `npm run lint` clean
- [ ] The preview route renders in all three domains it applies to

### Task 2: Primitives with no dependencies

**Files:** `resources/ts/components/` per Task 1.

**Components:** Button, Badge, Tag, Avatar, Skeleton, Progress, Iconography, Typography helpers, Verification Mark.

**Behaviour:** Each matches its `design/docs/` spec: anatomy, variants, sizes, states, and the accessibility section, which is the part most easily skipped and most expensive to retrofit.

**Who writes:** user. Button is the first component and sets every convention that follows: prop naming, variant typing, slot usage, token application. Worth going slowly.

**Read first:** `design/docs/DS Button.html` completely before writing anything, then `_semantic.scss` to see which tokens exist.

**Trap:** the Verification Mark's forest green is never the primary teal. That is a brand rule, not a preference.

- [ ] Each component appears on the preview page and matches its spec's states
- [ ] `npm run check` and `npm run lint` clean

### Task 3: Form controls

**Components:** Form, Select, Toggle, Selection, Segmented, Upload.

**Behaviour:** Per spec, including keyboard interaction and error states. These wrap Bits UI primitives rather than reimplementing them.

**Who writes:** user.

**Read first:** each component's spec, plus the Bits UI docs for the matching primitive.

- [ ] Keyboard-only operation works for every control
- [ ] `npm run check` and `npm run lint` clean

### Task 4: Overlays

**Components:** Modal, Toast, Tooltip, Menu, Alert.

**Behaviour:** Focus management, escape handling and scroll locking come from Bits UI. Do not hand-roll them.

**Who writes:** user.

**Trap:** Toast usually wants a store, and a store at module scope is exactly the SSR hazard `.ai/rules/ts.md` describes. Lint cannot see it inside `<script module>`. Decide where that state lives before writing it.

- [ ] Focus returns correctly on close for each overlay
- [ ] `npm run check` and `npm run lint` clean

### Task 5: Structure and layout

**Components:** Card, Tabs, Breadcrumb, Empty State, Sidebar Nav, App Shell.

**Behaviour:** Per spec, and per whatever H2 resolves to for the shell.

**Who writes:** user.

- [ ] The shell renders at every breakpoint in `_breakpoints.scss`
- [ ] `npm run check` and `npm run lint` clean

### Task 6: Documentation and mirror sync

**Files:** `design/docs/` (H3 outcome), `docs/architecture.md` if the component layout needs recording, `.ai/rules` via `record-rule` for any convention that emerges.

**Who writes:** claude. Documentation is never the founder's task.

## Artifacts

- [ ] No routes, so no `openapi.json` and no `bruno/` change
- [ ] No business rule, so no `docs/validation.md` code
- [ ] No scheduled command, no personal-data field, no audit event, no consent type
- [ ] Design system changes keep `design/docs/` and the Claude Design project in sync via DesignSync, in both directions
- [ ] Conventions that emerge (component structure, prop naming, token usage) recorded with `record-rule` against `resources/ts/**`

## Observability review

No routes, jobs, commands or exception types. Sentry quotas unaffected. `@sentry/svelte` is in `package.json` but not yet wired to any entry point; wiring it is not this phase.

## Migration safety

No schema changes.

## Out of scope

- **Table and Data Table.** Phase 2b, with its own plan.
- **Screens.** This phase builds the component library, not pages. Auth screens belong to Phase 3, alongside the endpoints they call.
- **Wiring Sentry's browser SDK.** Present as a dependency, deliberately not connected here.
- **Staging.** Phase 4 now, and nothing in this phase needs it.
- **Product logic of any kind.** Shared components are presentational, so a component that knows what a brag is has gone wrong.

## Sizing note

Twenty-eight components in learning mode is still the largest phase so far. `learning-mode.md` puts the pace at two to three times slower than Claude implementing, concentrated in exactly these phases, and calls that the correct outcome rather than a problem.

Table and Data Table were split out to **Phase 2b**, which is close to a phase in its own right. Nothing in Phase 3 or 4 needs them, so 2b is independent and may run immediately after this phase or slide to just before Phase 5, its first consumer.

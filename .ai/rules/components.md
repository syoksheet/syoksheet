---
paths:
  - 'resources/ts/components/**'
  - 'resources/ts/domains/**'
---

# Components

## One folder per component, and runes outside a .svelte file need the .svelte.ts extension
Every shared component is a folder, even a one-file one: `components/{group}/{name}/`, with an `index.ts` re-exporting the entry so imports are `$components/feedback/toast` and never change when the inside grows. Promoting a bare `Badge.svelte` to a folder later would rewrite every call site, which is the churn this avoids.

That per-component `index.ts` is not the library-wide barrel we rejected. A barrel re-exporting all components makes one import pull the whole module graph and undoes the per-domain bundle split; a single-component index reaches nothing extra.

A module using runes outside a `.svelte` file must be named `.svelte.ts`, so `toast.svelte.ts` and `table.svelte.ts`. Named `toast.ts`, `$state` does not compile and the error does not point at the filename.

Groups mirror the design spec sections: actions, forms, data, feedback, navigation, layout.

## Bits UI: the child snippet is the default here, and {...props} is mandatory
We style with scoped `<style>` blocks and tokens, not utility classes, so nearly every Bits UI element is rendered through its `child` snippet. Bits UI's own styling docs give this reason: the snippet brings the element into our component scope, so scoped selectors work without `:global()` wrappers that would defeat Svelte scoping.

`{...props}` on that element is non-negotiable. It carries every ARIA attribute and internal event handler. Omit it and the component still renders and still looks right, but is broken for keyboard and screen readers, with nothing failing visibly.

Floating components (Popover, Tooltip, Select, DropdownMenu) use two levels: `{...wrapperProps}` on an outer element that must stay unstyled because it carries positioning, and `{...props}` on the inner element that takes the styling. Styling the wrapper produces bugs that look like Floating UI faults.

State comes from data attributes Bits UI already sets, `[data-state="open"]`, `[data-disabled]`, so the States table in each DS spec becomes CSS on attributes, never threaded props or computed classes.

Transitions: the old `transition*` props are gone. Use `forceMount` plus the `child` snippet's `open` flag, `{#if open}<div {...props} transition:fly>`. Each wrapper absorbs that boilerplate once, using `--ease-move` and `--ease-fade`.

Components expose CSS variables such as `--bits-select-anchor-width` for anchor-matched sizing. Use them instead of measuring in JavaScript.

## How a component is written: typed props, derived state, tokens, and no design prose
Props are a named `interface Props`, destructured once with defaults in the destructure: `const { size = 24, tone = 'brand' }: Props = $props()`. Not defaults in the markup, not `export let`.

Derived values use `$derived`. There are no `$:` statements in Svelte 5 code here.

Styles are a scoped `<style lang="scss">` block using semantic tokens only. A component never references a primitive (`--n-*`, `--teal-*`, `--verify-*`); if the semantic token it needs does not exist, add it to `_semantic.scss` rather than reaching past the layer.

Do not restate design rationale in comments. The spec owns why the mark has three bars and why forest green is reserved; duplicating it means two copies that drift, and `design/` is slated for replacement by Figma, so a comment would outlive its source. Comment only what the code cannot say, such as why `background: currentcolor` sits on an empty element.

Prefer a named constant to a comment explaining a number: `markSize <= COMPACT_MASTER_MAX_PX` needs no comment where `markSize <= 28` does.

Prop JSDoc is different and welcome: it is the public API and shows at every call site.

## SCSS imports in components are relative, never aliased
A component that needs the breakpoint mixin imports it by relative path: `@use '../../../../scss/breakpoints' as bp;`. Four levels up is stable, because the component convention is fixed at `components/{group}/{name}/`.

Do not replace this with a bare `@use 'breakpoints'` plus a Vite `css.preprocessorOptions.scss.loadPaths` entry. That reads better but resolves only where the tooling is configured: every editor then needs its own equivalent setting, JetBrains through Mark Directory as Resources Root, others through their own, and until someone sets it they see an error on correct code. The relative form resolves by plain filesystem rules everywhere, with no configuration for anyone.

This should stay rare. Layout tokens step themselves in `_semantic.scss`, so a component normally reads a token and writes no media query at all. The announcement bar needs one because it changes alignment rather than a value.

## Content type comes from a mixin; control type does not
Any component rendering **content text** uses a mixin from `_typography.scss` (app) or `_typography-marketing.scss` (apex), never a raw `font-size`. Badge, Tag and the Announcement do this via `type-chip`.

Two deliberate exceptions, both of which look like drift and are not:

- **Button** sets 13/14/15px directly on `.sm`/`.md`/`.lg`. Control type belongs to the control's own spec and is bound to its height (31/38/46px), not to the content scale. 14px has no content-scale step because it needs none.
- **Avatar** uses `calc(var(--size) * 0.4)`, because initials scale with the diameter. Any fixed size breaks at some diameter.

`type-chip` is the one mixin that sets no `font-family`. A chip's family carries meaning: mono says the value came from the system (status, ID, count), sans says a person wrote it (skill, keyword). The component picks. Three components had each invented their own chip size before this step existed, which is what a missing tier looks like.

## Positioning a child component: restructure, do not reach in

A component's root element belongs to that component, so a scoped selector in the parent
cannot touch it. Svelte's own best practices put the options in this order, and so do we:

1. **Restructure so the parent's layout does it.** Almost always possible and always the
   cheapest. Wanting `margin-block-start: auto` on a child is usually a sign the siblings
   above it should be one group: wrap them, give the wrapper `flex: 1`, and the child
   lands where you wanted with no CSS crossing the boundary. The homepage's step cards do
   this with `.step-copy`.
2. **A CSS custom property**, when the child is genuinely meant to be configurable. This
   is theming, not positioning.
3. **A `class` prop the child spreads onto its root**, only for a component whose whole
   job is to be placed by its caller.
4. **`:global()`** is for third-party markup we do not render. Never for our own
   components: see the Bits UI rule above, which exists to avoid exactly this.

Reaching in also fails quietly in a specific way worth knowing: `li > :last-child` and
friends compile with the scoping hash on the rightmost selector, and a child component's
root does not carry the parent's hash. The rule matches nothing and Svelte reports it as
an unused selector rather than an error.

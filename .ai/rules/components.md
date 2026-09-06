---
paths:
  - 'resources/ts/components/**'
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

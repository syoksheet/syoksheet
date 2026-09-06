---
paths:
  - 'resources/ts/components/layout/**'
---

# Layout

## Layout tokens step themselves, and frames take snippets rather than implementing regions
`--layout-sidebar` and `--layout-gutter` change value at breakpoints inside `_semantic.scss`. A component reads the token and never writes a media query for layout, so the ramp lives in one place. AppShell changes across four breakpoints and contains no `@media`.

`min-inline-size: 0` on the flexing column and on any flex child holding text is what stops the page scrolling sideways. A flex item defaults to `min-width: auto` and refuses to shrink below its content, so one long unbroken string forces horizontal scroll. This is the single most common cause of that bug.

Only the content column flexes. The sidebar and the topbar action group are `flex-shrink: 0` and switch state at a breakpoint rather than squeezing.

A frame supplies geometry, not contents. AppShell takes `sidebar`, `breadcrumb` and `actions` as snippets and does not implement any of them, so the nav and breadcrumb components arrive with the phases that need them. Where a frame cannot enforce a rule, state the obligation in the prop's JSDoc: actions are never clipped, so they must drop their own labels below `lg`.

---
paths:
  - 'resources/scss/**'
---

# Scss

## _base.scss is the only layer that styles elements
Every other partial in `resources/scss/` is `:root` tokens and mixins and emits no element rules. Before `_base.scss` existed, nothing applied `--font-sans` to `body`, so the whole document rendered in the browser's default font while the `@font-face` rules and the preload worked fine. Only components that named `var(--font-sans)` themselves showed Geist.

`_base.scss` must be `@use`d last in `app.scss`, since it reads the token layers.

The reset strips UA opinions rather than adjusting them: heading and `p` margins go to 0, lists lose markers and padding, form controls get `font: inherit` (they do not inherit it). Prose gets its defaults back under a `.prose` scope when the help center and legal pages arrive.

Two type scales. `_typography.scss` is the app scale (body 15px) and stays dense. `_typography-marketing.scss` is the apex scale (body 17px), reached through `body.marketing`, set in `domains/public.blade.php`. Never put a real CSS rule in a mixins file: components `@use` it, so the rule is emitted and Svelte-scoped once per component and matches nothing. That is why `body.marketing` lives in `_base.scss`.

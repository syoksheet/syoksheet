---
paths:
  - 'resources/ts/**'
---

# Ts

## No mutable state at module scope under SSR
The SSR process imports a module once and keeps it alive across every render, unlike PHP-FPM which resets per request. A mutable binding at module scope is therefore shared by every visitor, so one user's data can render into another user's page.

ESLint enforces this for `.ts` files via `no-restricted-syntax` on `Program > VariableDeclaration[kind="let"]` in `eslint.config.ts`. That selector cannot see inside a component: `svelte-eslint-parser` does not expose `<script module>` declarations as top-level, so a mutable binding there passes lint and must be caught in review.

Keep request-scoped values inside a component instance or pass them as props. A module-level `const` holding a mutable object or array has the same problem and is equally uncaught.

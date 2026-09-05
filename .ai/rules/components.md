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

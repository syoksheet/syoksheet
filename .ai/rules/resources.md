---
paths:
  - 'resources/**'
---

# Resources

## The code is the design system, not the Claude Design projects
`resources/scss/` and `resources/ts/components/` are the source of truth. When a value there disagrees with a Claude Design project, the code is right.

The Claude Design studio and design-system projects are a wireframing surface: quick to sketch and visualize in, deliberately not optimized, and expected to drift. Do not chase that drift, do not sync code back as an obligation, and never copy a value across on the grounds that "the design says so". Read them for structure, layout and copy ideas; decide tokens here.

`design/docs/` holds component specs for behavior and accessibility. Useful reference, not authority, and slated for replacement by Figma.

Known drift today, all cases where our code is correct: the studio has `--radius-xl: 16px` against our 18px, a `--shadow-xl` we do not have, and extended `verify` steps we have not needed. `--teal-800` and `--teal-900` were taken from it deliberately, because the announcement bar and marketing footer needed a dark teal surface.

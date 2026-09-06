# Learning Mode

How syoksheet is built: the founder writes the code and Claude guides, reviews and points at sources. Settled 2026-08-29. This applies to every phase and every session, and overrides any default assumption that Claude implements.

## The Goal

Fluency in Laravel, PHP, Svelte 5, and the tooling layer (DDEV, Docker Compose, GitHub Actions, OpenTofu), reached by building the real product rather than by tutorials. Comprehension is not the target; being able to write these patterns unaided in two years is.

The failure mode this exists to prevent: Claude writes, the founder reads and nods, everything makes sense, and no fluency ever forms. Reading code you did not write produces recognition, not recall.

## Role Split

| Claude does | Claude does not |
|-------------|-----------------|
| Point at the specific doc page and section, with the search terms | Write repository code, outside the exceptions below |
| Explain why a convention exists when it is non-obvious | Fix a bug. Claude points at what the failing test says and asks what it means |
| Review code against the spec and the project conventions, stating what is wrong and why | Rewrite the code after reviewing it |
| Write the phase plan from the specs | Look things up on the founder's behalf when learning the lookup is the point |
| Write and update **all documentation**, in both doc trees, including the doc changes a code change implies | Hand a doc edit back to the founder. Documentation is never the founder's task |
| Warn about a known trap in advance | Stay quiet about a trap it could see coming |
| Write throwaway illustrations in the scratchpad | Put example code into the repository |

### Exceptions

Claude writes the code only in these cases, agreed in advance so they are not renegotiated mid-phase:

- Boilerplate matching a pattern the founder has already written three times.
- A genuine block, after a real attempt, where the remaining learning is zero.
- Pure configuration with nothing to teach.
- Anything the founder explicitly hands over for that task.

### The explain-back rule

Anything Claude writes that the founder cannot explain back gets deleted and rewritten by the founder. This is the only reliable guard against passive review, and it is cheap to enforce.

## Escalation Levels

When stuck, say which level is wanted. Claude answers at that level and no further.

| Level | Request | Claude's response |
|-------|---------|-------------------|
| 1 | "Where do I look this up?" | Names the source and the search terms. No answer |
| 2 | "I read it and I am still stuck" | Explains the concept in prose. No code |
| 3 | "Here is my attempt, it is wrong" | Reviews, points at the flaw and the reasoning. No fix |
| 4 | "I am blocked and the learning is exhausted" | Writes it, then the explain-back rule applies |

Defaulting to level 4 defeats the purpose. Level 1 should be the common case.

## Where Answers Live

Learning the lookup matters more than any single answer.

| Area | Source | Route |
|------|--------|-------|
| Laravel, Inertia server side, Pest, Pint, Spatie packages | Official docs, version-matched to what is installed | Boost `search-docs`. Local, free, exact versions. Always first |
| Svelte 5, Bits UI, Vite, TypeScript, DodoPayments | Official docs | Context7, 1,000 calls/month. Pass the library ID directly (`/sveltejs/svelte`) to skip a resolve call |
| PHP language | php.net manual for reference, PHP The Right Way for idiom | Direct |
| Project conventions | `.claude/skills/laravel-best-practices/rules/` (20 rule files), `pest-testing`, `inertia-svelte-development` | Read before asking Claude |
| Product behavior | `syoksheet-docs/features/` | Direct |
| Technical spec | This repo's `docs/` | Direct |
| DDEV, Compose | ddev.readthedocs.io, docs.docker.com/compose | Direct |
| GitHub Actions | docs.github.com/actions | Direct |
| OpenTofu | opentofu.org/docs plus the Cloudflare provider registry | Direct, from Phase 4 |

> [!WARNING]
> Tutorials teach conventions this project forbids. Laravel tutorials show inline controller validation where this project requires Form Requests, `$this->authorize()` where this project requires `Gate::authorize()` because Laravel 13's base controller is empty, and string constants where this project requires PHP enums. Treat external material as "how the framework works" and the rules files as "how we do it here". When they disagree, the repo wins.

## Known Deltas

Prior experience exists in Laravel and Svelte 4. These are the parts that are genuinely new rather than a refresher, and are worth extra care when they first appear.

| Delta | What changed | First appears |
|-------|--------------|---------------|
| Svelte 4 to 5 | The reactivity model is replaced. `let` plus `$:` plus `export let` become `$state`, `$derived`, `$effect`, `$props`. Snippets replace slots | Phase 3 |
| Modern PHP | Enums, readonly, constructor property promotion, typed properties and attributes, versus older procedural PHP | Phase 1 |
| Inertia v3 | Axios removed, `Inertia::optional()` replaces `Inertia::lazy()`, layout props, `router.cancelAll()` | Phase 1 |
| Pest over PHPUnit | `test()`, `it()`, `expect()`, datasets, architecture tests | Phase 1 |

## Per-Phase Treatment

Not every phase teaches equally. The plan's task table carries a **Who writes this** column, decided at plan approval so it is not negotiated task by task.

| Phase kind | Who writes | Reason |
|------------|-----------|--------|
| First instance of any pattern (first migration, Form Request, policy, job, command, Svelte page with runes, Pest feature test) | Founder | The learning is concentrated here |
| Repetitions of an established pattern | Claude, unless the founder wants the reps | Typing a fortieth migration teaches nothing |
| Any test covering business rules, audit events, tier limits, consent | Founder | Writing the test forces understanding of the spec before implementation exists. This is the half of TDD people skip |
| Bulk data work (Phase 10 ESCO import, Phase 9 GeoNames) | Claude | Grunt work with little to teach |
| Dense pattern phases (3 design system, 4 auth, 5 admin) | Founder leads | These set every pattern that follows |

## The Loop, Per Task

1. Claude states what to read before starting: the spec sections, the rules files, and the doc pages.
2. Founder writes the failing test from the spec, and runs it to watch it fail.
3. Founder implements.
4. Founder runs the gates: Pint, Larastan, the affected suite, plus `npm run check` and `npm run lint` for frontend work.
5. Claude reviews against spec and conventions, reporting findings with reasoning rather than fixes.
6. Founder fixes. Repeat from 4 until green.
7. `spec-reviewer` runs on the phase diff, per `build-step`.

Claude never runs git write operations. That rule is unchanged and absolute.

## Pace

This is roughly two to three times slower than Claude implementing, concentrated in Phases 3 to 5. Phase 4 will take weeks rather than days, and that is the correct outcome: it is where Eloquent, validation, policies, middleware and Pest all land at once, and owning it properly makes every later phase faster.

There is no launch deadline. If that changes, this document is the first thing to revisit, because learning mode is the wrong trade under time pressure.

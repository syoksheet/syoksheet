---
name: build-step
description: The syoksheet build loop — phase location, spec reading, audit events, plan approval, test-driven implementation, artifact sync, quality gates and review. Use when the user says "next build step", "continue the build", names a phase, or starts any build work in this repo.
---

# Build step

The complete build process for syoksheet. Self-contained: do not invoke superpowers skills — the discipline worth borrowing from them is written into this file. The build follows `.claude/work/plans/implementation-order.md` (18 phases); never skip a gate, and never reorder the sequence except where that file says a phase is independent.

## Non-negotiables

Three rules that hold in every phase, whatever the pressure.

```
1. NO BEHAVIOUR CODE WITHOUT A FAILING TEST FIRST
2. NO FIX WITHOUT ROOT CAUSE INVESTIGATION FIRST
3. NO COMPLETION CLAIM WITHOUT FRESH VERIFICATION OUTPUT
```

And one standing constraint: **never run git write operations.** No add, commit, push, tag, branch, worktree, stash or reset — in this repo or any sibling. The user does all git himself. Read-only git (`status`, `diff`, `log`, `rev-parse`) is fine.

## The loop

1. **Locate the phase.** Read `.claude/work/plans/implementation-order.md`; determine the current phase from what actually exists in the codebase, not from what the last session claimed. If ambiguous, ask — never guess which phase is next.
2. **Read the specs.** Every spec the phase lists, plus the relevant `syoksheet-docs` feature docs for product behaviour. The docs are the spec: if one seems wrong, incomplete or self-contradictory, **stop and raise it** — never improvise around a spec, and never let code become the new source of truth.
3. **Resolve holes.** If a spec genuinely does not decide something, or a screen has no design, work it out with the user before planning and write the outcome to `.claude/work/specs/<topic>.md`. One question at a time, decisions recorded as they are made. A complete spec skips this stage entirely — this is for holes, not for re-opening what the docs already settled. A new or changed product decision also needs a dated row in `syoksheet-docs/product/decisions.md` (the `new-decision` skill does this).
4. **Events first.** If the phase creates, updates or deletes user or org data, verify its audit events exist in `docs/features/audit/events.md` **before** implementing. Missing events get added to the catalog first, with the user's confirmation, each with the correct `visibility` (`internal` or `management`).
5. **Plan and get approval.** Write `.claude/work/plans/phase-NN-<name>.md` in the format below. Present it and **wait for explicit approval.** No implementation before approval — not "just the migration", not "just the scaffolding".
6. **Implement.** Work task by task in plan order, test-driven per the table below. Run the affected tests after each task, not just at the end. Tier limits are config-driven from first appearance — never hardcoded (canonical numbers: `syoksheet-docs → product/pricing.md`). All user-facing strings go through translation files from the first screen.
7. **Sync artifacts.** The checklist below, in the same change as the code.
8. **Gates.** Pint, Larastan, the affected suite, and for frontend work `npm run check` and `npm run lint` — all green, with output shown.
9. **Review.** Dispatch the `spec-reviewer` agent on the working tree diff. Verify each finding before acting on it: confirm it against the spec, then fix it or explain to the user why it does not apply. Do not implement a finding you believe is wrong — say so with reasoning.
10. **Report.** What shipped, files touched, test counts, and every deviation from spec flagged loudly. Then stop — the user commits.

## Commands

Everything runs inside DDEV. Never call `php`, `composer`, `npm` or `psql` bare on the host.

| Purpose | Command |
|---|---|
| Affected tests | `ddev php artisan test --compact --filter=<Name>` |
| Full suite | `ddev php artisan test --compact` |
| Formatting | `ddev php vendor/bin/pint --format agent <paths>` — `.git` is not mounted into the container, so `--dirty` fails with "only available when using Git"; pass the changed paths explicitly |
| Static analysis | `ddev php vendor/bin/phpstan analyse` |
| Svelte types | `ddev exec npm run check` |
| JS/TS lint | `ddev exec npm run lint` |
| Install JS deps | `ddev exec npm ci` (never bare `npm install`) |
| Artisan generators | `ddev php artisan make:... --no-interaction` |

## What gets a failing test first

Rule 1 applies to behaviour. It does not apply to declarative scaffolding, where a red-first cycle proves nothing.

| Kind of work | Approach |
|---|---|
| Endpoints, jobs, commands, policies, business rules | **Failing Pest feature test first.** Write it from the spec, watch it fail, then implement. |
| Audit events, tier limits, consent and privacy hooks | **Failing test first, always.** These fail silently in production; a test written afterwards only asserts what the code already does. |
| Migrations, config, `Route::domain()` skeletons | Implement, then a contract test pinning the outcome (connection resolves, route answers on the right host). |
| Svelte components, SCSS tokens, screens | Build against the `design/docs/` spec; verify with `npm run check` and `npm run lint`. Pest browser tests only where real interaction logic exists. |

Test quality: one behaviour per test, named for the behaviour, using factories and their existing states. Before writing a test, name the production change that would make it fail — if you cannot, the test asserts nothing. Assert on real behaviour, never on mock behaviour.

## Plan format

```markdown
# Phase NN — <Name>

**Goal:** one sentence.
**Specs:** every doc this plan was built from, with paths.
**Audit events:** the events this phase fires, or "none — no user/org data written".

## Constraints
Project-wide rules this phase must respect, values copied verbatim from the specs
(tier limits, error codes, enum names, route domains).

## Tasks

### Task N: <name>
**Files:** create / modify / test — exact paths.
**Behaviour:** what it must do, from the spec.
- [ ] Failing test: <test name> — expected failure: <what and why>
- [ ] Implement
- [ ] Green: `ddev php artisan test --compact --filter=<Name>`

## Artifacts
Which of the sync checklist items this phase touches.

## Out of scope
What a reader might expect here but belongs to a later phase.
```

No placeholders, no "TBD". Before presenting: re-read it for contradictions, vague requirements, and anything a fresh implementer could read two ways — fix those inline.

## Debugging

When a test fails for a non-obvious reason, or a fix does not hold, stop patching and work the phases in order.

| Phase | Do | Done when |
|---|---|---|
| 1. Root cause | Read the full error and stack trace. Reproduce reliably. Check what changed. Gather evidence — `ddev php artisan tinker`, `read-log-entries`, `last-error`, `browser-logs`. | You can state what happens and why |
| 2. Pattern | Find working equivalents in this codebase. Read the reference implementation completely, not skimmed. List every difference, however small. | The differences are enumerated |
| 3. Hypothesis | State the cause as one testable claim. Change one thing. | Confirmed, or a new hypothesis |
| 4. Fix | Write the failing test that captures the bug, then fix the cause — not the symptom. | Test green, original symptom gone |

After three failed fixes, stop fixing: the architecture or your model of it is wrong. Say so to the user rather than attempting a fourth.

## The verification gate

Before any claim of done, passing, fixed or working:

1. Identify the command that proves the claim.
2. Run it fresh and in full — not a subset, not a remembered earlier run.
3. Read the whole output, including the exit code and the failure count.
4. If it does not confirm the claim, state the real status with the evidence.

| Claim | Requires |
|---|---|
| Tests pass | Test output in this session, 0 failures |
| Pint clean | Pint output, this run |
| Larastan clean | `phpstan analyse` output, exit 0 |
| Types clean | `npm run check` output |
| Bug fixed | The original failing test, now green |
| Subagent finished | The diff, read — not the agent's own success report |

## Artifact sync checklist

Same change as the code, every time:

- [ ] Route added or changed → `docs/api/openapi.json` **and** the `bruno/` collection
- [ ] New business rule → stable error code in `docs/validation.md`
- [ ] New Artisan command → `docs/scheduled-jobs.md`
- [ ] New personal-data field → `GenerateDataExportJob` coverage and the Tier 2 anonymisation path, per `docs/features/privacy/`
- [ ] New audit event → `docs/features/audit/events.md`, with `visibility` set
- [ ] New consent type → `ConsentType` enum and `docs/features/privacy/consent.md`
- [ ] New durable convention discovered → record it with the Boost `record-rule` tool

## Red flags

Catch yourself thinking any of these and stop — the thought is the signal, not the conclusion.

| Thought | Reality |
|---|---|
| "Too simple to need a test" | Simple code breaks. The test costs 30 seconds. |
| "I'll write the tests after" | A test written after passes immediately, which proves nothing. You never watched it fail, so you never proved it can catch the bug. |
| "I already checked it manually" | Ad-hoc, unrepeatable, and forgotten under pressure. |
| "Quick fix now, investigate later" | The first fix sets the pattern. Investigate now. |
| "It's probably X, let me change that" | Seeing the symptom is not understanding the cause. |
| "One more fix attempt" (after two) | Three failures means the architecture is wrong, not the line. |
| "Should pass now" / "seems fine" | Run the command. Confidence is not evidence. |
| "The spec is unclear, I'll pick something sensible" | Raise it. Improvised behaviour becomes the de facto spec and nobody knows. |
| "I'll add the audit event once it works" | It will be forgotten, and it fails silently forever. |
| "Just the migration before approval" | Implementation is implementation. Wait for approval. |
| "I'll hardcode the limit for now" | Tier limits are config-driven from first appearance. |
| "I'll commit this so it isn't lost" | Never. The user commits. |

## Current repo state

Facts that affect the gates, true as of Phase 1:

- **Larastan is installed but unconfigured** — there is no `phpstan.neon`. Creating it is a Phase 1 deliverable; until then the static-analysis gate cannot run, and saying it passed would be false.
- **`.ai/rules` does not exist yet** despite CLAUDE.md referencing it. Skip that step until the directory appears; `record-rule` creates it.
- **`.claude/work/specs/` does not exist yet** — create it when the first spec is written.
- The repo is otherwise a near-bare Laravel skeleton: `routes/web.php` and `console.php` only, one `User` model, default migrations, no `log` connection, empty `bruno/`.

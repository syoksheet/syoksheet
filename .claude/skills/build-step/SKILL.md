---
name: build-step
description: Execute the next phase of the SyokSheet v1 implementation order. Use when the user says "next build step", "continue the build", or names a phase. Enforces the full loop — spec-read, plan, approval, test-driven implementation, artifact sync (OpenAPI + Bruno + events catalog), quality gates, spec-reviewer pass.
---

# Build step

The build follows `.claude/work/plans/implementation-order.md` (17 phases). Never skip the loop, never reorder its gates.

## The loop

1. **Locate the phase.** Read the implementation order; determine the current phase from what already exists in the codebase. If ambiguous, ask — never guess which phase is next.
2. **Read the specs.** Every spec listed for the phase, plus the relevant `syoksheet-docs` feature docs for product behaviour. The docs are the spec: if a spec seems wrong or incomplete, stop and raise it — do not improvise around it.
3. **Events first.** If the phase creates/updates/deletes user or org data: verify its audit events exist in `docs/features/audit/events.md` **before** implementing. Missing events get added to the catalog (with user confirmation) first.
4. **Plan.** Write the phase plan to `.claude/work/plans/phase-NN-<name>.md`: scope, migrations, endpoints, jobs, events, tests. Present it and **wait for explicit approval** before any implementation.
5. **Implement test-driven.** Pest feature tests with factories; run affected tests per module (`ddev php artisan test --compact --filter=...`). Tier limits are config-driven from first appearance — never hardcoded (canonical numbers: syoksheet-docs → product/pricing.md).
6. **Same-commit artifacts.** For every route added/changed: update `docs/api/openapi.json` **and** the `bruno/` collection. New business rules → stable error codes in `docs/validation.md`. New commands → `docs/scheduled-jobs.md`. New personal-data fields → export + erasure specs (`docs/features/privacy/`).
7. **Gates.** `ddev php vendor/bin/pint --dirty --format agent`, Larastan, full affected test suite green.
8. **Review.** Dispatch the `spec-reviewer` agent on the changes; address every finding or explicitly justify skipping it to the user.
9. **Report.** What shipped, files touched, test counts, and any deviation from spec — flagged loudly, never silent. **No git operations** — the user commits.

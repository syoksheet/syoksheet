---
paths:
  - '.claude/skills/**'
---

# Skills

## Boost-managed skills are generated, never hand-edited
`boost.json` lists the skills Boost owns: infer-conventions, ai-sdk-development, laravel-best-practices, testing-best-practices, configuring-horizon, inertia-svelte-development. Running `ddev php artisan boost:update` regenerates them, so any hand edit is silently lost on the next update.

Never edit those directories. If one of them is wrong or missing guidance, the fix goes somewhere that persists: `.ai/rules`, `CLAUDE.md`, or the relevant doc.

`.claude/skills/build-step/` is not Boost-managed. It is ours and is edited normally. Check `boost.json` before assuming which kind a skill is, since the list grows when Boost adds skills for newly installed packages.

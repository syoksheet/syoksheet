---
paths:
  - '.ddev/**'
  - '.claude/**'
---

# General

## Editing #ddev-generated add-on files: override vs take ownership
One test decides it: does the generated file already state a value you disagree with?

- It states a wrong value: EDIT the file and delete its `#ddev-generated` marker, taking ownership. Overriding from elsewhere would leave the wrong value sitting in the file everyone opens first. Cost: upstream fixes no longer reach you, and `ddev add-on remove` will refuse to clean it up. Example: `.ddev/redis/redis.conf` (eviction policy and persistence must match production).
- It says nothing about the setting: OVERRIDE from your own file (a second `docker-compose.*.yaml`, or `hooks` in `config.yaml`). Leave their file and its marker untouched, so you keep inheriting upstream changes. Examples: MinIO bucket creation via a `post-start` hook; anything the add-on simply omits.

Never edit a marked file to add something it does not mention: owning their whole compose file (image version, ports, volume subpaths) to carry a two-line addition is a bad trade.

Hooks always live in `.ddev/config.yaml`, never a separate `config.*.yaml`: merge behaviour for the `hooks` list across config files is undocumented, and a wrong guess silently stops the existing hooks from running.

## The no-em-dash rule stops at agent tooling files
The project-wide "never use em dashes" rule applies to documentation, code comments and chat replies. It does NOT apply to `.claude/**` or `.ai/**`: skills, plans, specs, agent definitions and recorded rules are agent tooling, not prose anyone reads as project documentation.

Do not sweep those two directories for em dashes, and do not "fix" them when passing through. Leave whatever punctuation is there.

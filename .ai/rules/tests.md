---
paths:
  - 'tests/**'
---

# Tests

## Name tests for behavior, never for a document
Test names, and code comments generally, describe what the system does. They never reference a spec, doc page or "documented" behaviour.

Bad: `it('resolves each redis connection to its documented database')`, `it('declares the queue names the architecture spec fixes')`.
Good: `it('resolves each redis connection to its own database')`, `it('declares exactly the audit, notifications and default queues')`.

Two reasons: a doc reference goes stale silently when the doc is renamed or reorganised, and it makes the test unreadable without opening something else. Naming the actual values also makes the name self-checking against the assertion below it.

The docs are still the spec; they just are not cited from inside code.

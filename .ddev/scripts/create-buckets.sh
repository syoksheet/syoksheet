#!/usr/bin/env bash
#
# Creates the MinIO buckets that stand in for Cloudflare R2 locally.
#
# Runs in the minio container, because `mc` lives there and not in web. The `minio`
# alias is preconfigured by the add-on's mounted mc config.

set -euo pipefail

# The minio image declares no HEALTHCHECK, so DDEV cannot gate on readiness and the
# hook would otherwise race the daemon. `mc ready` blocks until it answers.
mc ready minio

# --ignore-existing is mc's own idempotence; this runs on every start.
mc mb --ignore-existing minio/syoksheet-public-local minio/syoksheet-private-local

# The public bucket stands in for one R2 serves through a custom domain, which makes
# the whole bucket world-readable. Without this, R2_PUBLIC_URL would 403 locally and
# work in production, which is the wrong way round for a rehearsal.
mc anonymous set download minio/syoksheet-public-local

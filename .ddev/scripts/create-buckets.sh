#!/usr/bin/env bash
#
# Creates the RustFS buckets that stand in for Cloudflare R2 locally.
#
# Runs in two places: the rustfs-cli container on DDEV post-start, and the Pest workflow
# in CI. Credentials and the endpoint come from the environment, so the same s3api calls
# provision both.

set -euo pipefail

ENDPOINT="${RUSTFS_ENDPOINT:-http://rustfs:9000}"
PUBLIC_BUCKET="syoksheet-public-local"
PRIVATE_BUCKET="syoksheet-private-local"

ensure_bucket() {
  local bucket="$1"

  if aws --endpoint-url "$ENDPOINT" s3api head-bucket --bucket "$bucket" >/dev/null 2>&1; then
    echo "Bucket exists: ${bucket}"
    return
  fi

  aws --endpoint-url "$ENDPOINT" s3api create-bucket --bucket "$bucket" >/dev/null
  echo "Bucket created: ${bucket}"
}

# Idempotent by inspection rather than by a flag: s3api has no --ignore-existing.
ensure_bucket "$PUBLIC_BUCKET"
ensure_bucket "$PRIVATE_BUCKET"

# The public bucket stands in for one R2 serves through a custom domain, which makes the
# whole bucket world-readable. Without this, R2_PUBLIC_URL would 403 locally and work in
# production, which is the wrong way round for a rehearsal.
#
# A bucket policy, never an object ACL: R2 has no per-object ACLs, which is the reason
# config/filesystems.php splits the two disks in the first place.
aws --endpoint-url "$ENDPOINT" s3api put-bucket-policy \
  --bucket "$PUBLIC_BUCKET" \
  --policy "$(cat <<POLICY
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": ["s3:GetObject"],
      "Resource": ["arn:aws:s3:::${PUBLIC_BUCKET}/*"]
    }
  ]
}
POLICY
)" >/dev/null

echo "Anonymous read applied: ${PUBLIC_BUCKET}"

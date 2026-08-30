#!/usr/bin/env bash
#
# Creates every database the application and the test suite expect.
#
# DDEV names its own database `db` and that name cannot be configured, so all four
# are created on every start rather than by hand: a manually created database
# would not survive `ddev delete`, and a fresh clone would come up broken.
#
# Runs in the web container, where PGHOST, PGUSER and PGPASSWORD are already set.
# No GRANT is needed: the `db` role is a superuser and owns what it creates.

set -euo pipefail

databases=(
  syoksheet               # the application
  syoksheet_testing       # the suite, per DB_DATABASE in phpunit.xml
  syoksheet_audit         # the `log` connection: a separate database, never a schema
  syoksheet_audit_testing # the `log` connection under test
)

for database in "${databases[@]}"; do
  # Postgres has no CREATE DATABASE IF NOT EXISTS, so guard on pg_database.
  if psql -tAc "SELECT 1 FROM pg_database WHERE datname = '${database}'" | grep -q 1; then
    continue
  fi

  psql -c "CREATE DATABASE ${database} OWNER db"
  echo "created database ${database}"
done

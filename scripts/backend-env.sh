#!/usr/bin/env bash
# Creates backend/.env from backend/.env.example, filling secrets from the repository root .env.
# Never overwrites an existing backend/.env unless called with --force.
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo "Missing root .env - run: make env"; exit 1; }
[ -f backend/.env.example ] || { echo "Missing backend/.env.example"; exit 1; }
if [ -f backend/.env ] && [ "${1:-}" != "--force" ]; then
  echo "backend/.env already exists, left unchanged (use --force to regenerate)"
  exit 0
fi

python3 - <<'PY'
import pathlib, re

root = {}
for line in pathlib.Path(".env").read_text().splitlines():
    m = re.match(r"^([A-Z0-9_]+)=(.*)$", line)
    if m:
        root[m.group(1)] = m.group(2)

text = pathlib.Path("backend/.env.example").read_text()
for token in ["DB_OWNER_PASSWORD", "DB_APP_PASSWORD", "REDIS_PASSWORD",
              "S3_ACCESS_KEY", "S3_SECRET_KEY", "S3_BUCKET", "S3_REGION"]:
    if token not in root:
        raise SystemExit(f"Missing {token} in the root .env")
    text = text.replace(f"__{token}__", root[token])

pathlib.Path("backend/.env").write_text(text)
print("Created backend/.env from backend/.env.example")
PY
chmod 600 backend/.env

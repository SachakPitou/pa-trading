#!/usr/bin/env bash
# Deploy from your PC to the VPS:   ./deploy.sh [--dry-run] [--skip-tests] [--allow-dirty]
# Settings go in .deploy.env (not committed):  VPS_HOST=...  VPS_USER=deploy  VPS_PATH=/var/www/pa-trading  VPS_PORT=22
set -euo pipefail
cd "$(dirname "$0")"
[ -f .deploy.env ] && . ./.deploy.env
: "${VPS_HOST:?Set VPS_HOST in .deploy.env}" "${VPS_USER:?Set VPS_USER in .deploy.env}" "${VPS_PATH:?Set VPS_PATH in .deploy.env}"
VPS_PORT="${VPS_PORT:-22}"
DRY=""; SKIP=0; DIRTY=0
for a in "$@"; do case "$a" in
  --dry-run) DRY="--dry-run";; --skip-tests) SKIP=1;; --allow-dirty) DIRTY=1;;
  *) echo "Unknown option: $a"; exit 1;; esac; done

if [ "$DIRTY" = 0 ] && git rev-parse --git-dir >/dev/null 2>&1 && [ -n "$(git status --porcelain)" ]; then
  echo "You have uncommitted changes. Commit them first (so the VPS matches git), or use --allow-dirty."; exit 1
fi

CHECK='for f in $(find . -name "*.php" -not -path "./vendor/*"); do php -l "$f" >/dev/null || exit 1; done; php tests/run.php'
if [ "$SKIP" = 0 ]; then
  echo "== lint + tests =="
  if command -v docker >/dev/null 2>&1 && [ -f config.php ]; then docker compose run --rm web sh -c "$CHECK"
  else sh -c "$CHECK"; fi
fi

SSH="ssh -p $VPS_PORT"
echo "== sync files ${DRY:+(dry run - nothing is changed)} =="
rsync -az --delete $DRY --itemize-changes --chmod=D755,Dg+s,F644 --no-owner --no-group \
  -e "$SSH" --exclude-from=deploy/rsync-exclude.txt ./ "$VPS_USER@$VPS_HOST:$VPS_PATH/"

if [ -n "$DRY" ]; then
  echo "== pending migrations on the VPS =="; $SSH "$VPS_USER@$VPS_HOST" "cd '$VPS_PATH' && php bin/migrate.php --status"
else
  echo "== migrate database =="; $SSH "$VPS_USER@$VPS_HOST" "cd '$VPS_PATH' && php bin/migrate.php"
  echo "Deployed."
fi

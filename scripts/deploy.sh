#!/usr/bin/env bash
#
# InsulaCRM manual deployment helper
#
# Usage:
#   bash scripts/deploy.sh [--skip-migrate] [--skip-fpm-reload]
#
# This script automates the post-file-sync steps that are easy to forget
# during a manual (rsync / scp / unzip) deployment:
#
#   1. Run database migrations
#   2. Clear all Laravel caches
#   3. Flush PHP OPcache (via FPM reload or CLI fallback)
#   4. Verify storage link
#
# Run this from the project root after syncing files.

set -euo pipefail

SKIP_MIGRATE=0
SKIP_FPM_RELOAD=0

for arg in "$@"; do
    case "$arg" in
        --skip-migrate)    SKIP_MIGRATE=1 ;;
        --skip-fpm-reload) SKIP_FPM_RELOAD=1 ;;
    esac
done

cd "$(dirname "$0")/.."

PHP=${PHP_BINARY:-php}

echo ""
echo "==> InsulaCRM deployment"
echo "    Root: $(pwd)"
echo ""

# ── Migrations ────────────────────────────────────────────────
if [ "$SKIP_MIGRATE" -eq 0 ]; then
    echo "==> Running database migrations"
    $PHP artisan migrate --force
else
    echo "==> Skipping migrations (--skip-migrate)"
fi

# ── Laravel caches ────────────────────────────────────────────
echo ""
echo "==> Clearing Laravel caches"
$PHP artisan optimize:clear

# ── Storage link ──────────────────────────────────────────────
echo ""
echo "==> Ensuring storage link"
$PHP artisan storage:link 2>/dev/null || true

# ── OPcache flush ─────────────────────────────────────────────
echo ""
if [ "$SKIP_FPM_RELOAD" -eq 0 ]; then
    # Try common PHP-FPM service names
    FPM_RELOADED=0
    for svc in php-fpm php8.4-fpm php8.3-fpm php8.2-fpm php8.1-fpm; do
        if systemctl is-active --quiet "$svc" 2>/dev/null; then
            echo "==> Reloading $svc to flush OPcache"
            sudo systemctl reload "$svc"
            FPM_RELOADED=1
            break
        fi
    done

    if [ "$FPM_RELOADED" -eq 0 ]; then
        echo "==> No active PHP-FPM service found. Flushing OPcache via CLI fallback."
        $PHP -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache flushed.\n\"; } else { echo \"OPcache not available.\n\"; }"
        echo "    Note: CLI OPcache flush does not affect FPM. If you use PHP-FPM, restart it manually."
    fi
else
    echo "==> Skipping FPM reload (--skip-fpm-reload)"
    $PHP -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache flushed (CLI only).\n\"; }"
fi

echo ""
echo "==> Deployment complete"
echo ""

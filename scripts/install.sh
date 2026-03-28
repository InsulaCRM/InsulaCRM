#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is required to run the InsulaCRM installer."
  exit 1
fi

if [[ ! -f artisan ]]; then
  echo "Run this script from an extracted InsulaCRM release."
  exit 1
fi

if [[ "$(uname -s)" != "Linux" ]]; then
  echo "scripts/install.sh currently supports Linux servers only."
  exit 1
fi

if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    cp .env.example .env
  else
    echo ".env.example is missing. Re-upload the release package before continuing."
    exit 1
  fi
fi

mkdir -p storage bootstrap/cache plugins

prompt() {
  local label="$1"
  local default_value="${2:-}"
  local value

  if [[ -n "$default_value" ]]; then
    read -r -p "$label [$default_value]: " value
    echo "${value:-$default_value}"
  else
    read -r -p "$label: " value
    echo "$value"
  fi
}

secret_prompt() {
  local label="$1"
  local value
  read -r -s -p "$label: " value
  echo
  echo "$value"
}

APP_NAME="$(prompt "Application name" "InsulaCRM")"
APP_URL="$(prompt "Application URL" "http://localhost")"
COMPANY_NAME="$(prompt "Company name")"
ADMIN_NAME="$(prompt "Administrator name")"
ADMIN_EMAIL="$(prompt "Administrator email")"
ADMIN_PASSWORD="$(secret_prompt "Administrator password")"
DB_HOST="$(prompt "Database host" "127.0.0.1")"
DB_PORT="$(prompt "Database port" "3306")"
DB_NAME="$(prompt "Database name" "insulacrm")"
DB_USER="$(prompt "Database username" "root")"
DB_PASSWORD="$(secret_prompt "Database password (leave blank if none)")"

read -r -p "Load demo data? [y/N]: " LOAD_DEMO_REPLY
LOAD_DEMO_FLAG=""
if [[ "${LOAD_DEMO_REPLY,,}" == "y" || "${LOAD_DEMO_REPLY,,}" == "yes" ]]; then
  LOAD_DEMO_FLAG="--load-demo-data"
fi

php <<'PHP' "$APP_NAME" "$APP_URL" "$DB_HOST" "$DB_PORT" "$DB_NAME" "$DB_USER" "$DB_PASSWORD"
<?php
$envPath = getcwd() . DIRECTORY_SEPARATOR . '.env';
$env = file_exists($envPath) ? file_get_contents($envPath) : '';
$values = [
    'APP_NAME' => '"' . addcslashes($argv[1], '"\\') . '"',
    'APP_URL' => $argv[2],
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => '"' . addcslashes($argv[3], '"\\') . '"',
    'DB_PORT' => '"' . addcslashes($argv[4], '"\\') . '"',
    'DB_DATABASE' => '"' . addcslashes($argv[5], '"\\') . '"',
    'DB_USERNAME' => '"' . addcslashes($argv[6], '"\\') . '"',
    'DB_PASSWORD' => '"' . addcslashes($argv[7], '"\\') . '"',
];
foreach ($values as $key => $value) {
    $pattern = '/^#?\s*' . preg_quote($key, '/') . '=.*/m';
    $line = $key . '=' . $value;
    if (preg_match($pattern, $env)) {
        $env = preg_replace($pattern, $line, $env);
    } else {
        $env .= PHP_EOL . $line;
    }
}
file_put_contents($envPath, $env);
PHP

chmod 664 .env || true
chmod -R 775 storage bootstrap/cache plugins || true

if getent group www-data >/dev/null 2>&1; then
  chgrp -R www-data storage bootstrap/cache plugins .env 2>/dev/null || true
fi

php artisan app:install \
  --app-name="$APP_NAME" \
  --app-url="$APP_URL" \
  --company-name="$COMPANY_NAME" \
  --admin-name="$ADMIN_NAME" \
  --admin-email="$ADMIN_EMAIL" \
  --admin-password="$ADMIN_PASSWORD" \
  --db-host="$DB_HOST" \
  --db-port="$DB_PORT" \
  --db-database="$DB_NAME" \
  --db-username="$DB_USER" \
  --db-password="$DB_PASSWORD" \
  $LOAD_DEMO_FLAG

echo
echo "Running a post-install health check..."
php artisan system:doctor || true
echo
echo "InsulaCRM installation completed."
echo "Next steps:"
echo "  1. Point your web root at the public/ directory."
echo "  2. Add the Laravel scheduler cron entry."
echo "  3. Start a queue worker for background jobs."
echo "  4. Sign in at your configured URL."

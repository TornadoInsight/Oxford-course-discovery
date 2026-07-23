#!/usr/bin/env bash
# Starts the WordPress site with PHP's built-in server for local development.
set -euo pipefail
cd "$(dirname "$0")/.."

PORT="${1:-8080}"

echo "Serving Course Discovery on http://127.0.0.1:${PORT}"
php -S "127.0.0.1:${PORT}" -t wordpress router.php

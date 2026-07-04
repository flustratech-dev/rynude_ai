#!/usr/bin/env bash
# Shared startup script for Linux (systemd) and macOS (launchd).
# Runs the same pre-steps the Windows tray launcher does, then serves the app.
set -e
cd "$(dirname "$0")/.."

# A stale hot file makes @vite point at a dev server that isn't running.
rm -f public/hot

php artisan migrate --force
php artisan optimize

exec php artisan serve --host=127.0.0.1 --port=8080

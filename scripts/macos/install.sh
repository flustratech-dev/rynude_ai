#!/usr/bin/env bash
# Installs Rynude as a launchd agent: auto-starts at login and runs in the
# background without a terminal. Run:  bash scripts/macos/install.sh
set -e
APP_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PLIST="$HOME/Library/LaunchAgents/com.rynude.serve.plist"

mkdir -p "$HOME/Library/LaunchAgents"
chmod +x "$APP_ROOT/scripts/start-server.sh"
sed "s|__APP_ROOT__|$APP_ROOT|g" "$APP_ROOT/scripts/macos/com.rynude.serve.plist" > "$PLIST"

launchctl unload "$PLIST" 2>/dev/null || true
launchctl load "$PLIST"

echo "Rynude berjalan di http://localhost:8080"
echo "Stop      : launchctl unload $PLIST"
echo "Uninstall : launchctl unload $PLIST && rm $PLIST"

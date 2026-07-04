#!/usr/bin/env bash
# Installs Rynude as a systemd user service: auto-starts at login and runs in
# the background without a terminal. Run:  bash scripts/linux/install.sh
set -e
APP_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
UNIT_DIR="$HOME/.config/systemd/user"

mkdir -p "$UNIT_DIR"
chmod +x "$APP_ROOT/scripts/run-background.sh" "$APP_ROOT/scripts/start-server.sh"
sed "s|__APP_ROOT__|$APP_ROOT|g" "$APP_ROOT/scripts/linux/rynude.service" > "$UNIT_DIR/rynude.service"

systemctl --user daemon-reload
systemctl --user enable --now rynude.service
# Keep the service running even when no desktop session is open.
loginctl enable-linger "$USER" 2>/dev/null || true

echo "Rynude berjalan di http://localhost:8080"
echo "Cek status : systemctl --user status rynude"
echo "Stop       : systemctl --user stop rynude"
echo "Uninstall  : systemctl --user disable --now rynude && rm $UNIT_DIR/rynude.service"

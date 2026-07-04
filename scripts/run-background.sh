#!/usr/bin/env bash
# Dipanggil oleh launchd (macOS) / systemd (Linux) saat login: menjalankan
# Rynude lewat cli.js --silent sehingga perilakunya identik dengan perintah
# `rynude` di terminal (npm install, build, proteksi DB, server + queue worker).
cd "$(dirname "$0")/.."

# launchd/systemd memulai dengan PATH minimal — node/php/composer dari
# Homebrew atau ~/.local/bin tidak akan ketemu tanpa ini.
export PATH="/opt/homebrew/bin:/usr/local/bin:$HOME/.local/bin:$PATH"

# Server sudah jalan (mis. user telanjur mengetik `rynude` di terminal)?
# Keluar sukses agar launchd/systemd tidak me-restart terus-menerus.
if curl -sf -o /dev/null --max-time 2 http://localhost:8080; then
    exit 0
fi

exec node cli.js --silent --no-open

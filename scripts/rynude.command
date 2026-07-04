#!/bin/bash
# Muat environment path Mac agar perintah 'npm' bisa ditemukan
if [ -f ~/.bash_profile ]; then
    source ~/.bash_profile >/dev/null 2>&1
fi
if [ -f ~/.zshrc ]; then
    source ~/.zshrc >/dev/null 2>&1
fi
export PATH=$PATH:/usr/local/bin:/opt/homebrew/bin:~/.nvm/versions/node/*/bin

# Script ini ada di <project>/scripts, jadi naik satu tingkat ke root project
cd "$(dirname "$0")/.."
echo "========================================================"
echo "Memulai Rynude AI (Laravel + Vite)..."
echo "========================================================"
npm run rynude

# Jika terjadi error, tahan terminal agar tidak langsung menutup
if [ $? -ne 0 ]; then
    echo ""
    echo "Oops! Sepertinya ada error. Tekan tombol ENTER untuk menutup..."
    read
fi

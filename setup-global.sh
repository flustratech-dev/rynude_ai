#!/bin/bash
echo "========================================================"
echo "Memasang perintah global 'rynude' untuk Mac/Linux..."
echo "========================================================"

TARGET_DIR="$(pwd)"

# Tambahkan alias ke ~/.zshrc dan ~/.bashrc
alias_cmd="alias rynude='cd \"$TARGET_DIR\" && npm run rynude'"

if [ -f ~/.zshrc ]; then
    echo "$alias_cmd" >> ~/.zshrc
    echo "Alias berhasil ditambahkan ke ~/.zshrc"
fi

if [ -f ~/.bashrc ]; then
    echo "$alias_cmd" >> ~/.bashrc
    echo "Alias berhasil ditambahkan ke ~/.bashrc"
fi

echo ""
echo "Berhasil! Perintah 'rynude' sekarang sudah terpasang secara global."
echo "Silakan tutup terminal ini, buka terminal baru, lalu ketikkan: rynude"
echo ""

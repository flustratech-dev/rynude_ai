#!/bin/bash
echo "========================================================"
echo "Memasang perintah global 'rynude' untuk Mac/Linux..."
echo "========================================================"

TARGET_DIR="$(pwd)"
BIN_DIR="$HOME/.local/bin"
mkdir -p "$BIN_DIR"

# Wrapper 'rynude' — setara dengan rynude.bat di Windows:
# simpan folder tempat user mengetik perintah sebagai workspace, lalu jalankan app.
cat > "$BIN_DIR/rynude" <<EOF
#!/bin/bash
echo "========================================================"
echo "Membuka project Rynude AI (Laravel + Vite)..."
echo "========================================================"
export RYNUDE_WORKSPACE="\$(pwd)"
cd "$TARGET_DIR" && exec npm run rynude -- "\$@"
EOF
chmod +x "$BIN_DIR/rynude"

# Wrapper 'rynudecode' — setara dengan rynudecode.bat di Windows.
cat > "$BIN_DIR/rynudecode" <<EOF
#!/bin/bash
export RYNUDE_WORKSPACE="\$(pwd)"
cd "$TARGET_DIR" && exec php artisan rynude:chat --workspace="\$RYNUDE_WORKSPACE"
EOF
chmod +x "$BIN_DIR/rynudecode"

# Pastikan ~/.local/bin masuk PATH di semua shell umum macOS/Linux.
# File rc DIBUAT bila belum ada (macOS baru tidak punya ~/.zshrc), dan
# baris hanya ditambahkan sekali (tidak menumpuk saat update).
PATH_LINE='export PATH="$HOME/.local/bin:$PATH"'
for rc in "$HOME/.zshrc" "$HOME/.zprofile" "$HOME/.bashrc" "$HOME/.bash_profile"; do
    touch "$rc" 2>/dev/null || continue
    # Hapus alias 'rynude' dari installer versi lama (digantikan wrapper di ~/.local/bin)
    if grep -q "alias rynude=" "$rc" 2>/dev/null; then
        sed -i.rynude-bak '/alias rynude=/d' "$rc" && rm -f "$rc.rynude-bak"
    fi
    if ! grep -q '\.local/bin' "$rc" 2>/dev/null; then
        printf '\n%s\n' "$PATH_LINE" >> "$rc"
    fi
done

# Izin executable hilang saat repo di-commit dari Windows — pulihkan di sini
# agar rynude.command bisa dibuka lewat double-click di Finder.
chmod +x "$TARGET_DIR/rynude.command" "$TARGET_DIR/setup-global.sh" 2>/dev/null

echo ""
echo "Berhasil! Perintah 'rynude' dan 'rynudecode' terpasang di $BIN_DIR."
echo "Silakan tutup terminal ini, buka terminal baru dari folder mana saja, lalu ketikkan: rynude"
echo ""

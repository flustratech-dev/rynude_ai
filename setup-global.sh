#!/bin/bash

echo "========================================================"
echo "Memasang perintah global 'rynude' untuk macOS / Linux..."
echo "========================================================"

# Dapatkan path absolut dari direktori project ini
TARGET_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Gunakan folder .local/bin di direktori home user
BIN_DIR="$HOME/.local/bin"

# Buat foldernya jika belum ada
mkdir -p "$BIN_DIR"

# Buat script eksekusi
cat <<EOF > "$BIN_DIR/rynude"
#!/bin/bash
echo "========================================================"
echo "Membuka project Rynude AI (Laravel + Vite)..."
echo "========================================================"
cd "$TARGET_DIR" || exit
npm run rynude
EOF

# Beri izin eksekusi (executable) pada script tersebut
chmod +x "$BIN_DIR/rynude"

echo ""
echo "Berhasil! Script eksekusi telah dibuat di $BIN_DIR/rynude"
echo ""
echo "Silakan buka terminal baru dan ketik: rynude"
echo ""
echo "⚠️ PENTING: Jika perintah 'rynude' tidak ditemukan (command not found),"
echo "pastikan Anda telah menambahkan ~/.local/bin ke dalam PATH Anda."
echo "Tambahkan baris berikut ke ~/.zshrc atau ~/.bashrc Anda:"
echo 'export PATH="$HOME/.local/bin:$PATH"'
echo ""

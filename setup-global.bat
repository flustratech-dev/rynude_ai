@echo off
echo ========================================================
echo Memasang perintah global 'rynude' untuk Windows...
echo ========================================================

:: Mendapatkan path lokasi folder project ini secara otomatis
set "TARGET_DIR=%~dp0"

:: Menggunakan folder npm bawaan karena pasti sudah masuk ke system PATH pengguna yang menginstal NodeJS
set "NPM_BIN=%APPDATA%\npm"

:: Memastikan folder npm ada
if not exist "%NPM_BIN%" mkdir "%NPM_BIN%"

:: Membuat file rynude.bat di dalam folder global npm
echo @echo off > "%NPM_BIN%\rynude.bat"
echo echo ======================================================== >> "%NPM_BIN%\rynude.bat"
echo echo Membuka project Rynude AI (Laravel + Vite)... >> "%NPM_BIN%\rynude.bat"
echo echo ======================================================== >> "%NPM_BIN%\rynude.bat"
echo cd /d "%TARGET_DIR%" >> "%NPM_BIN%\rynude.bat"
echo npm run rynude >> "%NPM_BIN%\rynude.bat"

echo.
echo Berhasil! Perintah 'rynude' sekarang sudah terpasang secara global.
echo Silakan tutup terminal ini, buka terminal/CMD yang baru dari folder mana saja,
echo lalu ketikkan: rynude
echo.
pause

@echo off
echo ========================================================
echo Memasang perintah global 'rynude' untuk Windows...
echo ========================================================

:: Script ini ada di <project>\scripts, jadi folder project = satu tingkat di atasnya.
:: Resolusi ke path absolut lewat FOR agar tidak mengandung "..".
for %%I in ("%~dp0..") do set "TARGET_DIR=%%~fI"

:: Menggunakan folder npm bawaan karena pasti sudah masuk ke system PATH pengguna yang menginstal NodeJS
set "NPM_BIN=%APPDATA%\npm"

:: Memastikan folder npm ada
if not exist "%NPM_BIN%" mkdir "%NPM_BIN%"

:: Membuat file rynude.bat di dalam folder global npm
echo @echo off > "%NPM_BIN%\rynude.bat"
echo echo ======================================================== >> "%NPM_BIN%\rynude.bat"
echo echo Membuka project Rynude AI (Laravel + Vite)... >> "%NPM_BIN%\rynude.bat"
echo echo ======================================================== >> "%NPM_BIN%\rynude.bat"
echo set "RYNUDE_WORKSPACE=%%CD%%" >> "%NPM_BIN%\rynude.bat"
echo cd /d "%TARGET_DIR%" >> "%NPM_BIN%\rynude.bat"
echo node cli.js %%* >> "%NPM_BIN%\rynude.bat"

:: Membuat file rynudecode.bat di dalam folder global npm
echo @echo off > "%NPM_BIN%\rynudecode.bat"
echo set "RYNUDE_WORKSPACE=%%CD%%" >> "%NPM_BIN%\rynudecode.bat"
echo cd /d "%TARGET_DIR%" >> "%NPM_BIN%\rynudecode.bat"
echo php artisan rynude:chat --workspace="%%RYNUDE_WORKSPACE%%" >> "%NPM_BIN%\rynudecode.bat"

echo.
echo Berhasil! Perintah 'rynude' dan 'rynudecode' sekarang sudah terpasang secara global.
echo Silakan tutup terminal ini, buka terminal/CMD yang baru dari folder mana saja,
echo lalu ketikkan: rynudecode
echo.
pause

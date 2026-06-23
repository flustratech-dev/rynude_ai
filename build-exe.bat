@echo off
cd /d "%~dp0"
echo ========================================================
echo   Mempersiapkan Build Rynude AI (Aplikasi Desktop)
echo ========================================================
echo.
echo Menggunakan PHP 8.3 Portable untuk menghindari konflik dengan XAMPP...
set PATH=%~dp0tools\php83;%PATH%

echo Memverifikasi versi PHP...
php -v
echo.

echo Memulai kompilasi (ini mungkin memakan waktu beberapa menit)...
php artisan native:build win

echo.
echo ========================================================
echo   Proses Selesai!
echo   Membungkus file menjadi Portable ZIP (mohon tunggu)...
if not exist "public\downloads" mkdir "public\downloads"
powershell -Command "Compress-Archive -Path dist\win-unpacked\* -DestinationPath public\downloads\Rynude-Portable.zip -Force"

echo ========================================================
mshta vbscript:Execute("msgbox ""Kompilasi Selesai! Aplikasi Desktop Rynude kini siap diunduh dalam format .zip (Portable) lewat UI Website."", 64, ""Rynude AI Build System"":close")

# Menjalankan Rynude sebagai aplikasi background

Server Laravel jalan otomatis di background (`http://localhost:8080`) tanpa
perlu membuka terminal. Aplikasi Chrome yang sudah di-install tinggal dibuka.

## Windows

Sekali saja, jalankan installer:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\windows\install-startup.ps1
```

Yang terjadi:
- Ikon **Rynude** muncul di system tray (panah `^` di taskbar).
  Klik dua kali = buka Rynude. Klik kanan = Buka / Restart server / Keluar.
- Shortcut dibuat di folder Startup (auto-jalan tiap login) dan Start Menu
  (bisa dicari seperti aplikasi biasa).

Uninstall: `powershell -ExecutionPolicy Bypass -File scripts\windows\uninstall-startup.ps1`

## Linux (systemd)

```bash
bash scripts/linux/install.sh
```

Terpasang sebagai user service `rynude` yang auto-start saat login dan
restart sendiri kalau crash. Perintah berguna:
`systemctl --user status|stop|restart rynude`.

## macOS (launchd)

```bash
bash scripts/macos/install.sh
```

Terpasang sebagai LaunchAgent `com.rynude.serve` yang auto-start saat login.
Log ada di `/tmp/rynude.log`.

## Catatan

- Semua launcher menjalankan `migrate --force` + `artisan optimize` dulu,
  menghapus `public/hot` yang basi (sisa `npm run dev`), lalu
  `php artisan serve --host=127.0.0.1 --port=8080`.
- Karena `artisan optimize` dijalankan, setelah mengedit route/config/view
  jalankan `php artisan optimize:clear` atau pilih **Restart server** di tray.
- Asset frontend dipakai dari build produksi (`public/build`). Setelah
  mengubah CSS/JS, jalankan `npm run build` sekali.
- PHP (dan MySQL untuk database) harus sudah terinstall dan ada di PATH.

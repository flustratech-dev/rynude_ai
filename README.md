# Rynude AI - Panduan Instalasi dan Penggunaan

Selamat datang di project Rynude AI! Panduan ini dirancang agar mudah dimengerti. Ikuti langkah-langkah di bawah ini untuk menjalankan project dari awal, sampai mengatur **9router** agar kamu bisa menggunakan model AI gratis.

---

## 🚀 1. Persiapan Awal (Clone & Install)

Buka terminal/command prompt kamu, lalu jalankan perintah berikut secara berurutan:

### Clone Project
```bash
git clone <URL_REPO_KAMU>
cd rynude_ai
```

### Install Dependencies
Pastikan kamu sudah menginstal **PHP**, **Composer**, dan **Node.js** di komputermu.
```bash
composer install
npm install
```

### Konfigurasi Database (Tanpa XAMPP!)
Project ini sudah diatur menggunakan **SQLite**. Kamu **TIDAK PERLU** repot menyalakan XAMPP, MySQL, atau membuat database manual.
```bash
# 1. Copy file environment
# Untuk pengguna Windows (Command Prompt):
copy .env.example .env

# Untuk pengguna Mac / Linux:
cp .env.example .env

# 2. Generate application key
php artisan key:generate

# 3. Setup database dan isi data awal
php artisan migrate:fresh --seed
```

---

## 🤖 2. Cara Menggunakan Model AI Gratis (dengan 9router)

Untuk mendapatkan akses ke model AI gratisan atau mengatur routing ke berbagai provider, kita harus menginstal **9router**. 9router bertindak sebagai perantara yang berjalan di komputer lokal kamu.

### Cara Install & Jalankan 9router
Buka **terminal/tab baru** (biarkan terminal pertama untuk project Laravel), lalu jalankan perintah ini:

```bash
# Langsung jalankan (sekali pakai tanpa install permanen):
npx 9router

# ATAU, install permanen di komputermu:
npm install -g 9router
9router
```

> [!IMPORTANT]
> Biarkan terminal 9router ini **selalu menyala** (running) selama kamu menggunakan aplikasi. Secara bawaan, 9router akan memberikan Endpoint URL di `http://localhost:20128/v1`.

---

## ⚙️ 3. Menghubungkan 9router ke Aplikasi

Setelah project dan 9router siap, saatnya menghubungkan keduanya agar Rynude AI mengambil data dari 9router.

### Jalankan Aplikasi Rynude AI
Kembali ke terminal project Laravel kamu, jalankan perintah ini (bisa buka 2 tab terminal):
```bash
# Tab 1: Build asset tampilan
npm run dev

# Tab 2: Jalankan server website
php artisan serve
```
Setelah itu, buka browser dan akses aplikasinya di: **http://localhost:8000**

### Pengaturan API Key di dalam Aplikasi
1. Buka aplikasi di browser dan **Login**.
2. Klik ikon/menu profil kamu (Settings) di bagian sidebar untuk membuka **Settings Modal**.
3. Pilih menu tab **API Keys**.
4. Gulir ke bawah hingga menemukan bagian pengaturan **Proxy API Key / Proxy Settings**.
5. Isi pengaturannya sebagai berikut:
   - **Base URL (Custom Endpoint):** Isi dengan `http://localhost:20128/v1`
   - **API Key:** Isi dengan `sk-dummy-key` (atau API Key provider lain jika kamu mengaturnya spesifik di dalam 9router).
6. Simpan pengaturan.

> [!TIP]
> **Selesai!** Sekarang semua obrolan (chat) yang kamu lakukan di Rynude AI akan otomatis diarahkan melalui 9router, sehingga kamu bisa menikmati model gratisan.

---

## 💻 4. Jalankan Aplikasi Lebih Cepat (Global Command)

Agar kamu tidak perlu masuk ke folder project tiap kali mau menjalankan aplikasi, project ini sudah dilengkapi dengan script untuk membuat perintah global `rynude`.

Jika sudah di-setup, kamu hanya perlu mengetikkan perintah `rynude` di terminal mana saja, dan aplikasi akan langsung menyala!

### Cara Install Perintah Global `rynude`

**Untuk pengguna Windows:**
1. Buka File Explorer dan masuk ke dalam folder project `rynude_ai`.
2. Klik ganda (Double-click) pada file `setup-global.bat`.
3. Selesai! Tutup terminal yang terbuka, buka terminal (CMD) baru, lalu ketik `rynude`.

**Untuk pengguna Mac / Linux:**
1. Buka terminal dan masuk ke folder project.
2. Jalankan perintah instalasi berikut:
   ```bash
   bash setup-global.sh
   ```
3. Script akan otomatis memasang perintah `rynude` ke dalam folder bin di sistemmu. Buka tab terminal baru, lalu ketik `rynude`. *(Jika ada pesan "command not found", perhatikan pesan penting di layar saat instalasi selesai tentang PATH).*

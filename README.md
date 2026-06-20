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

### 2. Setup Global Command (Opsional)
Anda bisa membuat perintah `rynude` tersedia di **seluruh komputer Anda**, sehingga ke depannya Anda tidak perlu repot membuka folder project ini lagi! 

*(Catatan: Pengguna Windows dapat langsung mengetik `rynude` di terminal jika berada di dalam folder project tanpa perlu setup global ini)*

**Untuk Pengguna Windows (Gunakan ini jika ingin akses Global, atau jika perintah rynude gagal dijalankan):**
1. Cari file **`setup-global.bat`** di dalam folder project ini.
2. Klik dua kali (jalankan) file tersebut.

**Untuk Pengguna macOS / Linux (Gunakan ini untuk akses Global):**
1. Buka terminal di dalam folder project ini.
2. Jalankan perintah: `bash setup-global.sh`
*(Jika perintah `rynude` belum dikenali, ikuti petunjuk di terminal untuk menambahkan path ke `.zshrc` atau `.bashrc` Anda)*

🎉 **Selesai!** Sistem akan otomatis mendeteksi dan bisa dijalankan dari folder mana saja nantinya.

### 3. Install Dependencies
Instal semua paket pendukung backend (PHP) dan frontend (Node.js):
```bash
composer install
npm install
```

### 4. Setup Konfigurasi (.env)
Duplikat file konfigurasi:
```bash
cp .env.example .env
```
*(Pengguna Windows/CMD bisa menggunakan `copy .env.example .env`)*

Buka file `.env` dan sesuaikan koneksi database Anda (contoh menggunakan MySQL):
```env
DB_CONNECTION=mysql
DB_DATABASE=db_rynude
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Finalisasi Setup
Generate application key dan jalankan migrasi database (beserta seeder untuk model AI bawaan):
```bash
php artisan key:generate
php artisan migrate --seed
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

### Pengaturan API Key di dalam Aplikasi
1. Buka aplikasi di browser dan **Login**.
2. Klik ikon/menu profil kamu (Settings) di bagian sidebar untuk membuka **Settings Modal**.
3. Pilih menu tab **API Keys**.
4. Gulir ke bawah hingga menemukan bagian pengaturan **Proxy API Key / Proxy Settings**.
5. Isi pengaturannya sebagai berikut:
   - **Base URL (Custom Endpoint):** Isi dengan `http://localhost:20128/v1`
   - **API Key:** Isi dengan `sk-dummy-key` (atau API Key provider lain jika kamu mengaturnya spesifik di dalam 9router).
6. Simpan pengaturan.
7. Tutup menu pengaturan, kemudian kembali ke layar obrolan (*chat*).
8. Pada *dropdown* pilihan model di bagian atas layar, silakan pilih **Rynude Sonnet** atau **Rynude Haiku**.

> [!TIP]
> **Selesai!** Sekarang semua obrolan (chat) yang kamu lakukan di Rynude AI akan otomatis menggunakan otak dari model yang kamu pilih dan diarahkan gratis melalui 9router.

---

## 💻 Cara Menjalankan Rynude

Kini menjalankan project ini **jauh lebih mudah**! Anda tidak perlu repot membuka banyak terminal.

### Menjalankan secara Lokal di Folder Project
Jika Anda berada di dalam folder project, Anda bisa langsung menjalankan perintah berikut tanpa perlu melakukan Setup Global:

**Untuk Pengguna Windows:**
Cukup ketik perintah berikut di terminal:
```bash
rynude
```

**Untuk Pengguna macOS / Linux:**
Jalankan file *command* yang sudah disediakan:
```bash
./rynude.command
```
*(atau bisa juga dengan `npm run rynude`)*

### 🌟 Menjalankan dari Luar Folder (Global Command)
Jika Anda sudah melakukan **Setup Global (Langkah 2)**, Anda bisa membuka terminal baru dari folder **mana saja** (bahkan dari Desktop) dan cukup mengetik:
```bash
rynude
```
Sistem akan otomatis mencari folder project dan menyalakannya! 🚀

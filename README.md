# Rynude (Claude UI Clone)

Rynude adalah aplikasi web *clone* dari antarmuka Claude AI, dibangun menggunakan **Laravel 11/12**, **Livewire 3**, **Tailwind CSS v4**, dan **Alpine.js**. Aplikasi ini dilengkapi dengan fitur chat AI interaktif yang langsung terhubung ke API (Anthropic, OpenAI, dll).

## 🚀 Fitur Utama

- **UI Premium & Responsif**: Desain *pixel-perfect* mirip Claude AI, mendukung tampilan Desktop dan Mobile.
- **Dark Mode & Light Mode**: Sinkronisasi otomatis dengan tema sistem atau manual.
- **Real-time AI Chat**: *Streaming* teks respon AI menggunakan koneksi Server-Sent Events (SSE) via Livewire.
- **Artifacts Panel**: Panel khusus untuk menampilkan kode, dokumen HTML, atau hasil *render* lainnya dari AI.
- **Dynamic API Configuration**: Pengaturan API Key bisa langsung diisi oleh pengguna via menu Settings.

---

## 🛠️ Persyaratan Sistem

Sebelum melakukan *clone* dan instalasi, pastikan sistem Anda telah memiliki:

- **PHP** >= 8.2
- **Composer** (untuk instalasi paket Laravel)
- **Node.js** & **NPM** (untuk *compile* aset Tailwind/JS)
- **Database** (MySQL / SQLite / PostgreSQL)

---

## 📦 Panduan Instalasi (Clone & Setup)

Ikuti langkah-langkah di bawah ini untuk menginstal aplikasi ini di komputer Anda:

### 1. Clone Repository
Buka terminal/CMD Anda, lalu jalankan perintah berikut:
```bash
git clone https://github.com/flustratech-dev/rynude_ai.git
cd claude-ui-clone
```

### 2. Install Dependencies
Instal semua paket pendukung backend (PHP) dan frontend (Node.js):
```bash
composer install
npm install
```

### 3. Setup Konfigurasi (.env)
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

### 4. Finalisasi Setup
Generate application key dan jalankan migrasi database (beserta seeder untuk model AI bawaan):
```bash
php artisan key:generate
php artisan migrate --seed
```

---

## 💻 Cara Menjalankan Aplikasi

Kini menjalankan project ini **jauh lebih mudah**! Anda tidak perlu repot membuka banyak terminal.

### Menjalankan secara Lokal di Folder Project
Cukup buka 1 terminal di folder project Anda, lalu jalankan perintah:
```bash
.\rynude
```
*(atau bisa juga dengan `npm run rynude`)*

### 🌟 Menjalankan secara Global (Windows / macOS / Linux)
Anda bisa membuat perintah `rynude` tersedia di **seluruh komputer Anda**, sehingga Anda tidak perlu repot mencari folder project ini lagi!

**Untuk Pengguna Windows:**
1. Cari file **`setup-global.bat`** di dalam folder project ini.
2. Klik dua kali (jalankan) file tersebut.

**Untuk Pengguna macOS / Linux:**
1. Buka terminal di dalam folder project ini.
2. Jalankan perintah: `bash setup-global.sh`
*(Jika perintah `rynude` belum dikenali, ikuti petunjuk di terminal untuk menambahkan path ke `.zshrc` atau `.bashrc` Anda)*

🎉 **Selesai!** 
Sekarang Anda bisa membuka terminal baru dari folder **mana saja** (bahkan di Desktop) dan cukup mengetik:
```bash
rynude
```
Sistem akan otomatis berpindah ke folder project dan menyalakannya! 🚀

---

### Akses Aplikasi
Perintah `rynude` akan secara otomatis menjalankan backend (Laravel) di port **8080** dan frontend (Vite) di port **5180**. Port custom ini sengaja dibuat agar tidak bentrok dengan project Laravel Anda yang lain.

Buka browser Anda dan akses:
👉 **[http://localhost:8080](http://localhost:8080)**

*(Pastikan Anda register/login terlebih dahulu untuk menggunakan fitur chat)*

---

## 🤖 Pilihan Model AI (Gratis & Berbayar)

Aplikasi ini mendukung berbagai macam model AI, baik yang resmi (berbayar) maupun yang gratis (lokal/proxy).

### 1. Menggunakan Model Rynude (GRATIS via 9Router/Kiro)
Aplikasi ini sudah diprogram dengan integrasi **100% otomatis** untuk pengguna **9Router/Kiro** (menggunakan trik AWS Builder ID). 
Sistem menyediakan 2 model bawaan: **Rynude Sonnet** dan **Rynude Haiku** (menggunakan otak asli Claude 3.5).

**Cara Pakai (Tanpa Setting):**
1. Pastikan Anda sudah menjalankan Kiro/9Router di terminal/komputer lokal Anda.
2. Login ke web Rynude.
3. Di bagian atas layar *chat*, klik *dropdown* model dan pilih **Rynude Sonnet** atau **Rynude Haiku**.
4. Langsung mengobrol! Sistem akan otomatis mendeteksi koneksi 9Router Anda (`127.0.0.1:20128`). Anda tidak perlu memasukkan API Key atau mengubah pengaturan apa pun.

---

### 2. Menggunakan Custom Proxy (Aivene, LM Studio, dll)
Jika Anda menggunakan *provider proxy* lain seperti **Aivene** yang memiliki puluhan model, Anda bisa menggunakannya dengan bebas!

1. Klik menu **Settings** (dari nama profil Anda di sudut kiri bawah).
2. Masuk ke tab **API Keys**.
3. Centang **"Gunakan Custom Proxy API"**.
4. Masukkan **Proxy Base URL** (contoh: `https://api.aivene.com/v1`).
5. Masukkan **Proxy API Key** Aivene Anda.
6. Pindah ke tab **AI Models** untuk menambahkan nama-nama model baru secara manual.
7. Semua obrolan Anda akan diarahkan ke Aivene.

---

### 3. Menggunakan API Resmi (OpenAI / Anthropic)
1. Matikan centang "Gunakan Custom Proxy API" di menu Settings.
2. Masukkan **Anthropic API Key** (dimulai dengan `sk-ant-`) atau **OpenAI API Key** (dimulai dengan `sk-`) Anda yang asli.
3. Obrolan akan langsung dikirim ke *server* resmi OpenAI/Anthropic tanpa hambatan.

> **Tips Ngrok:** Jika Anda melakukan *testing* aplikasi menggunakan `ngrok` untuk diakses via HP, sistem sudah dikonfigurasi untuk mem-*bypass* halaman peringatan bawaan ngrok. Pastikan koneksi internet stabil agar *streaming* teks AI tidak terputus.

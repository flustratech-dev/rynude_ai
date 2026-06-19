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

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi ini di komputer/laptop Anda:

### 1. Clone Repository
Buka terminal/CMD Anda, lalu jalankan perintah berikut:
```bash
git clone <URL_REPOSITORY_ANDA>
cd claude-ui-clone
```

### 2. Install Dependency PHP (Composer)
Instal semua paket pendukung Laravel:
```bash
composer install
```

### 3. Install Dependency Frontend (NPM)
Instal Tailwind v4 dan *library* JavaScript lainnya:
```bash
npm install
```

### 4. Setup File Konfigurasi (.env)
Duplikat file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
*(Pengguna Windows/CMD bisa menggunakan copy .env.example .env)*

Buka file `.env` di teks editor, lalu sesuaikan konfigurasi database Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=root
DB_PASSWORD=
```
*(Catatan: Jika Anda lebih suka SQLite, cukup ubah `DB_CONNECTION=sqlite` dan hapus variabel DB lainnya, lalu buat file kosong bernama `database.sqlite` di folder `database/`)*

### 5. Generate Application Key
Jalankan perintah ini untuk men-generate kunci enkripsi Laravel:
```bash
php artisan key:generate
```

### 6. Jalankan Migrasi Database
Buat tabel-tabel yang dibutuhkan ke dalam database:
```bash
php artisan migrate
```

---

## 💻 Cara Menjalankan Aplikasi

Anda perlu menjalankan dua *service* secara bersamaan (buka 2 tab terminal):

**Terminal 1 (Menjalankan server PHP):**
```bash
php artisan serve
```

**Terminal 2 (Menjalankan Vite & Tailwind compiler):**
```bash
npm run dev
```

Buka browser Anda dan akses: **http://127.0.0.1:8000**
*(Pastikan Anda register/login terlebih dahulu untuk menggunakan fitur chat)*

---

## 🔑 Cara Memasukkan API Key

Aplikasi ini **tidak menyimpan API Key Anda di `.env`** secara kaku. Setiap pengguna (user) dapat memasukkan API Key-nya sendiri.

Berikut cara memasukkannya agar Anda bisa mulai *chatting* dengan AI:

1. **Login** ke dalam aplikasi Rynude.
2. Di pojok kiri bawah (Desktop) atau di Menu Sidebar (Mobile), klik **Profile/Nama Anda**.
3. Pilih menu **Settings**.
4. Pada *modal* Settings yang muncul, pilih tab **API Keys** (ikon kunci).
5. Masukkan **Anthropic API Key** (untuk model Claude) atau **OpenAI API Key** (untuk model GPT) Anda di kolom yang tersedia.
6. Klik di luar kolom, sistem akan menyimpannya secara otomatis ke database akun Anda.
7. Sekarang, Anda sudah bisa mulai mengirim pesan ke AI!

> **Catatan Ngrok:** Jika Anda melakukan *testing* aplikasi menggunakan `ngrok` untuk diakses via HP, sistem sudah dikonfigurasi untuk mem-*bypass* halaman peringatan bawaan ngrok. Pastikan koneksi internet stabil agar *streaming* teks AI tidak *timeout*.

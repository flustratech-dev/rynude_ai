# Rencana Implementasi: Rynude All-in-One Local AI Engine

Berdasarkan analisis menyeluruh pada kode sumber Rynude Anda (khususnya `cli.js`, `AiModelSeeder.php`, `User.php`, `SettingsApiController.php`, dan `OpenAIProvider.php`), saya telah menyusun rencana implementasi detail per fase. 

**Perhatian Ekstra (Sesuai Permintaan Anda):**
Saya memastikan bahwa **fitur lama tidak akan terpengaruh atau hilang**. Provider `huggingface` (untuk Cloud API) yang sudah ada saat ini **tidak akan diubah atau dihapus**. Kita akan menambahkan provider baru bernama `9router` dan `local-engine` agar semuanya terpisah dan aman.

---

## Phase 1: Integrasi 9router

9router adalah gateway lokal yang kompatibel dengan format OpenAI. Kita akan menambahkan 9router sebagai provider resmi Rynude.

### 1. Modifikasi Installer & CLI (`cli.js`)
Kita akan menyisipkan perintah instalasi 9router agar terinstal secara transparan di sistem pengguna.
* **Tindakan:** Tambahkan eksekusi `npm install -g 9router` pada tahapan "Memastikan semua dependencies terinstall..." di file `cli.js`.
* **Keamanan:** Perintah ini tidak akan memblokir instalasi jika pengguna sudah memiliki 9router.

### 2. Penambahan Field Database
Kita perlu menyimpan API Key dan Base URL untuk 9router di tabel users.
* **Tindakan:** Tambahkan field `9router_api_key` dan `9router_base_url` pada file migrasi `users` dan model `app/Models/User.php`.
* **Tindakan:** Modifikasi `app/Http/Controllers/Api/SettingsApiController.php` agar dapat memproses dan menyimpan kredensial `9router`. Default Base URL 9router adalah `http://localhost:20128/v1`.

### 3. Modifikasi UI Pengaturan (Providers)
* **Tindakan:** Pada `resources/views/api-keys.blade.php` dan `resources/views/livewire/settings-modal.blade.php`, tambahkan tab/tombol filter untuk `9router` di samping Anthropic, OpenAI, dll.
* **Tindakan:** Tambahkan tombol **"Buka Dashboard 9router"** pada form 9router yang jika diklik akan membuka tab baru ke `http://localhost:20128`.
* **Tindakan:** Tambahkan tombol **"Aktifkan 9router"** yang akan memanggil endpoint backend untuk menjalankan perintah `npx 9router` di background (seandainya server 9router belum menyala).

### 4. Eksekusi Backend 9router
* **Tindakan:** Di `app/Services/AI/OpenAIProvider.php`, daftarkan `9router` sebagai salah satu provider yang didukung. Karena 9router menggunakan format OpenAI, Rynude bisa langsung mengirim *request chat* ke 9router tanpa mengubah logika pemrosesan stream.

---

## Phase 2: Deteksi Hardware (Spesifikasi RAM)

Untuk memastikan pengguna tidak mengunduh model `.gguf` yang membuat komputer mereka lambat (hang), kita akan membuat sistem deteksi spesifikasi komputer.

### 1. Pembuatan Endpoint Deteksi Hardware
PHP memiliki kapabilitas untuk mendeteksi total RAM sistem operasi (meskipun pengguna menggunakan Windows, Mac, atau Linux).
* **Tindakan:** Buat controller baru `app/Http/Controllers/Api/SystemHardwareController.php`.
* **Fungsi:** Menggunakan perintah OS (seperti `wmic MemoryChip` di Windows atau `free -b` di Linux) untuk mendapatkan total RAM fisik (dalam GB) dan VRAM (jika memungkinkan).
* **Output:** JSON berisi `{"total_ram_gb": 16, "os": "windows"}`.

---

## Phase 3: Built-in Model Hub (Hugging Face .gguf)

Membuat antarmuka "App Store" untuk AI.

### 1. UI Model Hub
* **Tindakan:** Buat view baru `resources/views/model-hub.blade.php` (bisa diakses melalui menu navigasi Rynude).
* **Fungsi:** Menampilkan daftar model `.gguf` populer (seperti Llama-3-8B, Qwen-1.5B, dll).
* **Integrasi Deteksi Hardware:** Di UI ini, panggil endpoint `/api/system/hardware` (dari Phase 2). Jika RAM pengguna misalnya 8GB, UI akan memberikan peringatan merah pada model >7B parameter dan menyembunyikan tombol "Download", atau merekomendasikan model kecil (Qwen 1.5B).

### 2. Fitur Download (Progress Bar)
* **Tindakan:** Buat endpoint `/api/models/download` yang menerima URL file `.gguf` dari Hugging Face.
* **Fungsi:** PHP akan mengunduh file besar tersebut secara *chunked* (bertahap) menggunakan Guzzle HTTP, dan menyimpan progress-nya ke Cache Laravel (`Cache::put('download_progress_model_x', 45)`).
* **Fungsi:** Simpan file unduhan ke folder aman: `storage/app/models/`.
* **UI:** Frontend menggunakan Alpine.js untuk melakukan *polling* (mengecek) progress download setiap 1 detik dan mengupdate Progress Bar di layar pengguna secara real-time.

---

## Phase 4: Local AI Engine (Pengganti Ollama)

Ini adalah langkah krusial agar file `.gguf` yang sudah diunduh bisa berjalan secara lokal. Mengingat Rynude sudah mewajibkan instalasi Node.js, kita akan menggunakan **`node-llama-cpp`** (pustaka yang mengemas engine C++ llama secara efisien ke dalam ekosistem JavaScript).

### 1. Penambahan Dependency
* **Tindakan:** Tambahkan `"node-llama-cpp": "latest"` pada `dependencies` di `package.json`. Modul ini sangat ringan dan langsung bisa menjalankan `.gguf`.

### 2. Service Eksekutor Lokal
* **Tindakan:** Buat file `app/Services/LlamaServerService.php`.
* **Fungsi:** Ketika pengguna memilih model lokal dari menu chat Rynude, service ini akan memanggil `Symfony\Component\Process\Process` untuk menjalankan perintah:
  `npx node-llama-cpp serve storage/app/models/nama-model.gguf --port 8081`
* **Manajemen Proses:** Proses ini berjalan di background selama Rynude hidup. Jika pengguna mengganti model lokal, service ini akan mematikan proses port 8081 yang lama dan merestartnya dengan file model yang baru.

### 3. Koneksi ke Rynude Chat
* **Tindakan:** Daftarkan provider baru bernama `local-engine`.
* **Fungsi:** `local-engine` ini akan menggunakan `OpenAIProvider.php` (karena `node-llama-cpp serve` sudah menggunakan format OpenAI) dan diarahkan ke Base URL `http://localhost:8081/v1`.

---

## User Review Required

> [!IMPORTANT]
> **Keputusan: node-llama-cpp**
> Penggunaan `node-llama-cpp` adalah cara terbaik, terbersih, dan teringan untuk aplikasi Anda saat ini. Ini tidak memerlukan pengguna menginstal aplikasi berat apapun (Node.js sudah terinstal). Apakah Anda setuju dengan pendekatan ini untuk Local AI Engine? YA

> [!WARNING]
> **Proses Eksekusi Rencana:**
> Karena perubahan ini mencakup banyak *layer* (CLI, Backend, UI, dan Service Manajemen Proses), saya sarankan kita mengeksekusinya **fase demi fase**. 

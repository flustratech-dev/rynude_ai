# Referensi Model Lokal rynude

> Catatan lengkap model lokal (Model Hub GGUF): nama asli di baliknya, nama tampilan,
> kode/nama file, dan kemampuan tiap model. Diperbarui: 9 Juli 2026 (generasi Qwen3).

---

## ⚠️ Baca ini dulu — kenapa kode & nama file "aneh"

Ada **tiga lapis penamaan** untuk satu model, dan semuanya sengaja begitu:

| Lapis | Contoh | Boleh berubah? |
|---|---|---|
| **Nama tampilan** (yang Anda lihat) | `rynude Lyric 4.5` | ✅ Bebas diganti |
| **Kode internal** (kunci sistem) | `qwen-2.5-1.5b` | ❌ TIDAK — mengunci riwayat chat, pemilihan model, mesin lokal |
| **Nama file .gguf** | `Qwen3-1.7B-Q8_0.gguf` | ❌ TIDAK — kunci ke mesin `node-llama-cpp` |

Jadi **kode `mistral-7b-v0.3` sebenarnya berisi Qwen3-8B** — itu normal. Kode adalah warisan
lama yang dipertahankan agar tidak merusak apa pun; yang berlaku untuk Anda cuma **nama tampilan**.
Semua "otak" model sudah generasi terbaru **Qwen3**.

---

## Peta model (nama asli → nama rynude)

| Nama tampilan | Model asli | Kode internal | Nama file .gguf | Ukuran file | RAM disarankan |
|---|---|---|---|---|---|
| **rynude Vignette** | Qwen3 0.6B | `qwen-2.5-0.5b` | `Qwen3-0.6B-Q8_0.gguf` | ~640 MB | ~2 GB |
| **rynude Lyric 4.5** ⭐ | Qwen3 1.7B | `qwen-2.5-1.5b` | `Qwen3-1.7B-Q8_0.gguf` | 1.8 GB | ~4 GB |
| **rynude Lyric 4.6** ✨ | Qwen3 1.7B + LoRA | `rynude-lyric-plus-1` | `Qwen3-1.7B-Lyric-Plus-Q8_0.gguf` | 1.8 GB | ~4 GB |
| **rynude Stanza** | Qwen3 4B | `llama-3.2-3b` | `Qwen3-4B-Q4_K_M.gguf` | 2.5 GB | ~6 GB |
| **rynude Canto** | Qwen3 8B | `mistral-7b-v0.3` | `Qwen3-8B-Q4_K_M.gguf` | 5 GB | ~10 GB |
| **rynude Symphony** | Qwen3 14B | `llama-3.1-8b` | `Qwen3-14B-Q4_K_M.gguf` | 9 GB | ~16 GB |
| **rynude Magnum** | Qwen3 30B (MoE, 3B aktif) | `qwen-2.5-14b` | `Qwen3-30B-A3B-Q4_K_M.gguf` | 18.6 GB | ~24 GB |
| **rynude Sense** | Qwen3-Embedding 0.6B | `rynude-embed-0.6b` | `Qwen3-Embedding-0.6B-Q8_0.gguf` | ~640 MB | ~1.5 GB |

⭐ = rekomendasi default · ✨ = hasil fine-tuning (LoRA) · Sense = **bukan model chat** (modul RAG).

---

## Kemampuan tiap model

| Model | Cocok untuk | Catatan jujur |
|---|---|---|
| **Vignette** (0.6B) | Uji coba, sapaan, tanya-jawab sangat ringan | Terlalu kecil untuk dokumen/artifact & skripsi. Untuk PC sangat lemah |
| **Lyric 4.5** (1.7B) | Default harian — chat, penjelasan, coding dasar, skripsi (via pipeline) | Sudah bisa semua fitur; kadang bahasa kurang natural (itu yang 4.6 perbaiki) |
| **Lyric 4.6** (tuned) | Sama seperti 4.5 tapi **lebih baik**: Bahasa Indonesia natural, format lebih patuh, lebih jarang halu | Hasil training sendiri (skor eval 90.6). Model kecil terbaik untuk kebutuhan skripsi/Indonesia |
| **Stanza** (4B) | **Titik manis** — dokumen, penalaran, coding, skripsi lebih dalam | Pilihan bila mau lebih pintar dari Lyric tapi PC spesifikasi sedang |
| **Canto** (8B) | Penalaran & pemrograman kuat, dokumen panjang | Butuh RAM ~10 GB |
| **Symphony** (14B) | Kualitas tinggi, terjemahan, masalah kompleks | Berat — butuh ~16 GB RAM |
| **Magnum** (30B) | Kualitas tertinggi setara model cloud | Butuh ~24 GB RAM; hanya PC kelas atas |
| **Sense** (embedding) | **Bukan chat** — modul RAG semantik | Aktif otomatis setelah diunduh; membuat pembacaan dokumen memahami MAKNA, bukan sekadar kecocokan kata |

---

## Fitur yang berlaku untuk SEMUA model chat lokal

Berapa pun ukurannya, setiap model lokal rynude otomatis mendapat:

- 🧠 **Berpikir dulu** (native `<think>` Qwen3) → tampil di panel "Proses berpikir".
- 📄 **Skripsi bab-per-bab** — pipeline 8 tahap (metadata → BAB I–V → Daftar Pustaka), melapor tiap tahap.
- 📐 **Format artifact dipaksa benar** — grammar GBNF: dokumen tidak bisa nyangkut di chat.
- 🔎 **Pencarian web** — model minta sendiri saat butuh fakta terkini (harga/berita/tanggal), lalu menjawab dengan sumber [n].
- 📚 **RAG dokumen lampiran** — BM25 (selalu) + semantik (jika **Sense** diunduh); memahami struktur & mengoreksi premis salah.
- ✦ **Mode kualitas** — draf → periksa → perbaiki (tombol bintang; ~2× lebih lambat, hasil lebih matang).
- 🔘 **Tombol pilihan** — klarifikasi A/B/C sebelum dokumen besar + saran tindak lanjut.
- 🇮🇩 **Penjaga bahasa** — jawaban wajib Bahasa Indonesia bila pengguna menulis Indonesia.
- ⚡ **GPU offload** — otomatis pakai kartu grafis (CUDA/Vulkan/Metal), fallback CPU.

---

## Cara memeriksa mesin lokal

- Status mesin + backend GPU + modul embedding:
  `http://127.0.0.1:8091/health`
  → contoh: `{"ok":true,"model":"qwen-2.5-1.5b","gpu":"vulkan","ctx":16384,"embed":true}`
- Rapor kualitas model: `php artisan rynude:eval <kode-model>` (mis. `rynude-lyric-plus-1`).
- Log jalannya: `storage/logs/laravel.log` (cari `Chat stream finished`, `Semantic RAG active`, `WebSearchService`).

---

## Ganti nama model (kalau perlu di masa depan)

Nama tampilan hidup di **7 tempat** — ubah semuanya agar konsisten (kode & file JANGAN diubah):

1. `database/seeders/AiModelSeeder.php`
2. `app/Http/Controllers/Api/ModelHubController.php` (getCatalog)
3. `resources/views/partials/model-name-helper.blade.php` (peta JS)
4. `resources/views/livewire/api-keys-panel.blade.php` (peta JS)
5. Migrasi rebrand/restore/rename di `database/migrations/` (untuk install baru)
6. Migrasi baru untuk mengubah database yang sudah jalan (`ai_models.name`)
7. Teks marketing: `README.md` + `resources/views/auth/login.blade.php`

# Tutorial Latih Lyric 4.7 di Google Colab (GRATIS)

Jalur ini **paling pasti jalan** (GPU T4 lama yang semua library dukung). Pakai ini
kalau setup lokal (`SETUP_LOKAL_GPU.md`) rewel.

## Yang perlu Anda siapkan
- Akun Google (gratis).
- 2 file dari folder `training/lyric-4.6/` di komputer Anda:
  - `colab.ipynb`  (notebook)
  - `dataset_upgrade.jsonl`  (data 4.6 + perbaikan)

## Langkah demi langkah

**1. Buka Colab**
Buka https://colab.research.google.com → menu **File → Upload notebook** → pilih
`colab.ipynb`.

**2. Nyalakan GPU gratis**
Menu **Runtime → Change runtime type → Hardware accelerator: T4 GPU → Save**.

**3. Jalankan semua**
Menu **Runtime → Run all**.
- Sel 1 (install) jalan ~3–5 menit — sabar.
- Saat sel **"Unggah data latih"** muncul tombol **Choose Files**, pilih
  **`dataset_upgrade.jsonl`** dari komputer Anda.

**4. TETAP AKTIF selama proses (PENTING!)**
Colab gratis **memutus sesi kalau Anda diam**. Selama training berjalan (~20–30 menit):
- **Jangan tutup tab.**
- **Klik/scroll di dalam tab Colab tiap 2–3 menit.**
Ini yang mencegah "Runtime disconnected".

**5. Pantau training**
Di sel "Latih", akan muncul tabel **Step | Training Loss**. Loss **turun** = bagus
(mis. dari ~2.3 ke <0.5). Biarkan sampai progress bar penuh.

**6. Uji cepat**
Sel "Uji cepat" akan menampilkan jawaban model — pastikan Bahasa Indonesia & masuk akal.

**7. Ekspor GGUF**
Sel "Ekspor ke GGUF" (`save_pretrained_gguf`) memakan ~5–15 menit (bikin file model).
Sabar; jangan diklik dua kali.

**8. Download**
Sel terakhir otomatis mengunduh file **`.gguf`** ke komputer Anda (cek folder Downloads).
Kalau tidak otomatis: buka panel file kiri Colab → folder `rynude-lyric-plus` → klik kanan
file `.gguf` → **Download**.

## Setelah dapat file `.gguf`
1. Rename mis. `rynude-lyric-4.7-Q8_0.gguf`.
2. Taruh di `storage/app/models/` di komputer Anda.
3. **Kirim nama file-nya ke Claude Code** → didaftarkan ke Model Hub (tanpa menimpa 4.6).
4. Clear cache → muncul di dropdown app.

## Kalau "Runtime disconnected" di tengah jalan
Sesi terputus = progress hilang (kita belum simpan ke Drive). Solusi:
1. Klik **Connect to a runtime** (dapat runtime baru).
2. **Runtime → Run all** lagi, upload `dataset_upgrade.jsonl` saat diminta.
3. Kali ini **tetap aktif** (klik tiap 2–3 menit). Datanya kecil, cuma ~20–30 menit.

> Ingin latihan panjang tanpa takut putus? Minta Claude Code membuat versi
> **checkpoint ke Google Drive** (bisa lanjut walau terputus).

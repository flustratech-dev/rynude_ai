# Runbook: Latih Ulang rynude Lyric 4.6 (Putaran Berikutnya)

> Panduan langkah demi langkah untuk **meningkatkan** rynude Lyric 4.6 memakai dataset
> baru (`golden_fixes.jsonl`), dari nilai eval **90.6 → target 95+**. Ikuti dari atas
> ke bawah. Semua GRATIS (Google Colab), tidak perlu GPU sendiri.

---

## 0. Di mana kita sekarang (rekap)

| Hal | Status |
|---|---|
| Lyric 4.6 v1 | ✅ Terlatih, nilai eval **90.6/100** |
| File GGUF | ✅ `rynude-lyric-4.6.gguf` (di `storage/app/models/` + Hugging Face) |
| Terdaftar di Model Hub | ✅ `download_url` menunjuk ke HF Anda |
| Dataset | ✅ 66 contoh (`golden_examples.jsonl` 20 + `golden_fixes.jsonl` 46) |

**Kelemahan yang mau ditutup putaran ini** (dari rapor v1):
- `artifact-makalah` 57% & `artifact-skripsi` 50% → format `<antArtifact>`
- `instruksi-ketat` 67% → patuh batasan (satu kata, dll.)
- `bahasa-istilah-campur` 75% → istilah teknis Indonesia

`golden_fixes.jsonl` sudah dibuat khusus untuk keempat ini. Putaran ini = latih ulang dengan data itu.

---

## Peta file (yang akan Anda sentuh)

| File | Fungsi |
|---|---|
| `golden_examples.jsonl` | Contoh emas inti (20) |
| `golden_fixes.jsonl` | Contoh terarah penutup kelemahan (46) — dibuat oleh `make_golden_fixes.py` |
| `make_golden_fixes.py` | Generator `golden_fixes.jsonl` — tempat menambah contoh baru |
| `build_dataset.py` | Menggabungkan SEMUA `golden_*.jsonl` + seed → `train.jsonl` + `val.jsonl` |
| `Rynude_Lyric_LoRA_Colab.ipynb` | Notebook Colab: latih → ekspor GGUF |

---

## LANGKAH 1 — Catat nilai awal (patokan pembanding)

Supaya "naik atau tidak" terbukti angka:

```bash
php artisan rynude:eval rynude-lyric-plus-1
```

Catat NILAI AKHIR-nya (sekarang 90.6). Laporan tersimpan otomatis di `storage/app/evals/`.

> Opsional: `php artisan rynude:eval qwen-2.5-1.5b` untuk melihat base (Lyric 4.5) sebagai pembanding bawah.

---

## LANGKAH 2 — (Opsional) Perbanyak data

Semakin banyak contoh berkualitas, semakin baik. Dua sumber tambahan, boleh dilewati untuk putaran cepat:

**a. Tambah contoh buatan sendiri** untuk kasus yang masih sering gagal:
- Buka `make_golden_fixes.py`, tambah entri di daftar `EXAMPLES` (ikuti pola yang ada), lalu:
  ```bash
  cd training
  python make_golden_fixes.py
  ```

**b. Perbanyak dari riwayat chat + model guru gratis** (butuh API key OpenRouter gratis):
```bash
php artisan rynude:export-seeds          # dari akar proyek → training/seeds.jsonl
cd training
set OPENROUTER_API_KEY=sk-or-xxxx         # Windows cmd;  Git Bash: export OPENROUTER_API_KEY=...
```
(Key gratis: https://openrouter.ai/keys — pakai model guru open seperti DeepSeek, JANGAN Claude/GPT/Gemini.)

---

## LANGKAH 3 — Bangun dataset latih

**Cepat (contoh emas saja — 66 contoh):**
```bash
cd training
python build_dataset.py
```

**Lengkap (emas + seed dijawab model guru):**
```bash
cd training
python build_dataset.py --seeds seeds.jsonl --teacher deepseek/deepseek-chat --augment 500
```

Hasil: `train.jsonl` + `val.jsonl`. `build_dataset.py` otomatis membaca **semua** `golden_*.jsonl`
dan membuang jawaban jelek (bahasa Inggris nyasar, halu, loop).

> Target sehat untuk lompatan nyata: 300–1.000 contoh. 66 saja sudah cukup untuk membuktikan arah.

---

## LANGKAH 4 — Latih di Colab (± 20–60 menit)

1. Buka https://colab.research.google.com → **File → Upload notebook** → pilih
   `Rynude_Lyric_LoRA_Colab.ipynb`.
2. **Runtime → Change runtime type → T4 GPU → Save**.
3. **Runtime → Run all**. Saat diminta, unggah `train.jsonl` + `val.jsonl`.
4. Sel uji cepat (bagian 6) menampilkan contoh jawaban — pastikan Bahasa Indonesia & waras.
5. Sel terakhir mengunduh file **`.gguf`** ke komputer Anda.

Jangan tutup tab selama proses berjalan.

---

## LANGKAH 5 — Pasang & ukur ulang (WAJIB sebelum rilis)

1. Ganti file lama dengan hasil baru:
   - Rename GGUF hasil unduhan → **`rynude-lyric-4.6.gguf`**
   - Timpa file di **`storage/app/models/`**
2. **Restart aplikasi** (mesin lokal memuat ulang bobot baru).
3. Ukur:
   ```bash
   php artisan rynude:eval rynude-lyric-plus-1
   ```
4. **Bandingkan dengan Langkah 1.**
   - Naik & ≥ target → lanjut ke Langkah 6 (rilis).
   - Tidak naik / soal lain jadi turun → **JANGAN rilis**; ke Langkah 7 (diagnosis).

---

## LANGKAH 6 — Rilis (unggah versi baru ke Hugging Face)

Model Anda sudah punya repo HF. Pilih salah satu cara:

**Cara A — Timpa file yang sama (paling gampang, tanpa ubah kode):**
- Upload `rynude-lyric-4.6.gguf` baru ke repo yang sama
  (`flustratechcompany/rynude-lyric-4.6-gguf`), timpa yang lama.
- `download_url` di katalog TIDAK berubah → user yang mengunduh ulang otomatis dapat versi baru.

**Cara B — Simpan sebagai versi baru (kalau ingin dua-duanya ada):**
- Upload dengan nama berbeda (mis. `rynude-lyric-4.6-v2.gguf`), lalu beri tahu saya
  (Claude Code) untuk menambah entri katalog baru / mengubah `download_url` + `filename`.

> Ingat: `file_size_bytes` di katalog sebaiknya diperbarui agar bar progres unduhan akurat.
> Kirim URL/ukuran baru ke Claude Code untuk disesuaikan.

---

## LANGKAH 7 — Kalau hasil belum memuaskan (diagnosis → perbaiki data)

**Prinsip besi: perbaiki DATA, bukan menambah epoch buta.**

| Gejala di eval | Penyebab lazim | Obat |
|---|---|---|
| Artifact masih gagal (tag tidak muncul) | Contoh artifact kurang variasi | Tambah lebih banyak jenis dokumen di `make_golden_fixes.py` |
| Instruksi ketat masih meleset | Kurang contoh batasan format | Tambah contoh "satu kata / N kalimat / tanpa tanda baca" |
| Istilah campur Inggris lagi | Kurang contoh istilah Indonesia | Tambah contoh pakai indeks/elemen/keluaran/galat |
| Soal yang tadinya 100% jadi turun | Data baru berlebihan di satu kategori (overfit) | Kurangi porsi kategori itu; jaga keseimbangan |
| Jawaban jadi dangkal/aneh menyeluruh | Epoch terlalu banyak / data kotor | Turunkan epoch ke 2, bersihkan data |

Lalu ulangi **Langkah 3 → 5** sampai target tercapai. Ini siklus normal fine-tuning yang berhasil.

---

## Checklist Go/No-Go sebelum rilis

- [ ] Nilai eval **naik** dari patokan Langkah 1
- [ ] Empat soal target (artifact ×2, instruksi, istilah) membaik
- [ ] Tidak ada soal yang tadinya benar jadi salah (toleransi 1)
- [ ] Uji manual di aplikasi asli: sapaan, makalah (masuk artifact), skripsi (pipeline), tanya-jawab
- [ ] File GGUF final diuji (bukan cuma checkpoint) → `rynude:eval` dijalankan pada file yang benar
- [ ] Setelah rilis: `download_url` + `file_size_bytes` katalog sesuai file baru

---

## Ringkasan satu layar

```
[1] eval  →  patokan (90.6)
[2] (opsional) tambah contoh: make_golden_fixes.py / export-seeds
[3] python build_dataset.py            → train.jsonl + val.jsonl
[4] Colab: Run all                     → unduh .gguf
[5] timpa storage/app/models + restart → php artisan rynude:eval
        ├─ naik  → [6] upload ke HF (timpa file) → SELESAI
        └─ belum → [7] diagnosis → tambah data → ulang [3]
```

Selamat melatih. Kualitas data = kualitas model. 🚀

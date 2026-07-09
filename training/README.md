# Melatih rynude Lyric 4.6 (LoRA) — Panduan Langkah demi Langkah

Folder ini berisi **semua perkakas** untuk melatih versi rynude Lyric 4.5 yang lebih pintar
(**rynude Lyric 4.6**), lewat jalur **GRATIS** (Google Colab, tanpa GPU sendiri, tanpa biaya).

> Catatan nama: nama tampilan model = "rynude Lyric 4.6", tetapi **kode internal**
> (`rynude-lyric-plus-1`) dan **nama file** (`Qwen3-1.7B-Lyric-Plus-Q8_0.gguf`) adalah
> kunci stabil dan TIDAK diubah — jangan bingung saat melihatnya di perintah/kode.

Target: Qwen3-1.7B → QLoRA → file GGUF yang masuk ke Model Hub aplikasi.

> Kenyataan penting: training **jalan di Colab (browser Anda)**, bukan di aplikasi ini.
> Yang ada di sini adalah bahan + resep. Anda menekan "Run" di Colab.
> Dan **tidak ada training tanpa data** — itu bagian tersulit, jadi kita mulai dari data.

---

## Isi folder

| File | Fungsi |
|---|---|
| `golden_examples.jsonl` | Contoh emas tulisan tangan — mengajarkan persis perilaku yang sering gagal (bahasa Indonesia konsisten, koreksi premis salah, format artifact, anti-pidato). Ini inti kualitas. |
| `build_dataset.py` | Menggabungkan contoh emas + prompt Anda + jawaban dari model guru → `train.jsonl` + `val.jsonl`. |
| `Rynude_Lyric_LoRA_Colab.ipynb` | Notebook Colab: latih + ekspor GGUF. Tinggal Run all. |
| `seeds.jsonl` | (dibuat oleh Anda) prompt dari riwayat chat, hasil `php artisan rynude:export-seeds`. |

---

## Alur besar (5 langkah)

```
[1] Ukur baseline  →  [2] Kumpulkan data  →  [3] Bangun dataset
        →  [4] Latih di Colab  →  [5] Pasang & ukur ulang
```

---

## Langkah 0 — Prasyarat sekali saja

- Akun Google (untuk Colab). Gratis.
- (Opsional tapi disarankan) API key **OpenRouter** gratis di <https://openrouter.ai/keys>
  — untuk memperbanyak data lewat model guru **legal** (DeepSeek/Qwen).
  Punya kuota gratis harian; cukup untuk ratusan contoh.
- Python 3 di komputer (untuk menjalankan `build_dataset.py`). Cek: `python --version`.

> ⚠️ **Legal (penting kalau mau dijual):** JANGAN pakai output Claude/GPT/Gemini
> sebagai data latih. Gunakan model guru open seperti DeepSeek/Qwen. Detail di
> `../rancangan loRA.md` Bab 2.

---

## Langkah 1 — Ukur baseline (WAJIB, gratis)

Supaya "jadi lebih pintar" bisa dibuktikan angka, bukan perasaan:

```bash
php artisan rynude:eval qwen-2.5-1.5b
```

Simpan skornya (juga tersimpan otomatis di `storage/app/evals/`). Ini patokan abadi.

---

## Langkah 2 — Kumpulkan prompt Anda jadi "seed"

```bash
php artisan rynude:export-seeds
```

Menghasilkan `training/seeds.jsonl` berisi pertanyaan-pertanyaan dari riwayat chat Anda
(sudah dibersihkan dari data pribadi; **hanya pertanyaan, bukan jawaban** — jawaban
dibuat ulang oleh model guru supaya legal).

> Kalau riwayat chat masih sedikit, tidak apa-apa — contoh emas sudah cukup untuk
> percobaan pertama. Seed bertambah seiring pemakaian; ekspor lagi nanti.

Selain itu, mulai **kumpulkan kasus gagal**: setiap kali Rynude menjawab ngaco,
catat pertanyaannya + tulis jawaban idealnya, simpan sebagai baris baru di
`golden_examples.jsonl` (format sama). Ini bahan paling bernilai.

---

## Langkah 3 — Bangun dataset

**Percobaan pertama (contoh emas saja, tanpa API — untuk smoke test):**
```bash
cd training
python build_dataset.py
```

**Versi lengkap (dengan seed + model guru gratis):**
```bash
cd training
set OPENROUTER_API_KEY=sk-or-xxxx      # Windows (cmd);  Git Bash: export OPENROUTER_API_KEY=...
python build_dataset.py --seeds seeds.jsonl --teacher deepseek/deepseek-chat --augment 500
```

Hasil: `train.jsonl` + `val.jsonl`. Skrip otomatis membuang jawaban jelek
(bahasa Inggris nyasar, halu, "sebagai AI…", loop).

> Untuk v1 yang serius, targetkan ~1.000–3.000 contoh berkualitas (naikkan `--augment`
> secara bertahap; jalankan berkali-kali karena kuota gratis harian). Ingat:
> **500 contoh sempurna > 5.000 contoh asal-asalan.**

---

## Langkah 4 — Latih di Colab (± 20–60 menit)

1. Buka <https://colab.research.google.com> → **File → Upload notebook** →
   pilih `Rynude_Lyric_LoRA_Colab.ipynb`.
2. **Runtime → Change runtime type → T4 GPU → Save**.
3. **Runtime → Run all**. Saat diminta, unggah `train.jsonl` + `val.jsonl`.
4. Sel terakhir mengunduh file **`.gguf`** ke komputer Anda.

Jangan tutup tab selama proses berjalan.

---

## Langkah 5 — Pasang & ukur ulang

1. Rename GGUF hasil unduhan → `Qwen3-1.7B-Lyric-Plus-Q8_0.gguf`, taruh di
   `storage/app/models/`.
2. Daftarkan sebagai model **baru** di Model Hub (minta Claude Code menambah entri
   katalog + kode `rynude-lyric-plus-1`; **jangan timpa Lyric asli** — rollback itu fitur).
3. Buktikan:
   ```bash
   php artisan rynude:eval rynude-lyric-plus-1
   ```
   Bandingkan dengan baseline Langkah 1.

**Kalau skor naik** → berhasil, rilis. **Kalau belum** → perbaiki DATA (tambah contoh
untuk kasus yang masih gagal), JANGAN sekadar menambah epoch. Lalu ulangi Langkah 3–5.

---

## Kalau mentok / hasil aneh

Lihat tabel diagnosis di `../rancangan loRA.md` Bab 8.4 (mis. "masih bocor Inggris",
"lupa kemampuan umum", "format bagus tapi isi memburuk") — tiap gejala ada obat datanya.

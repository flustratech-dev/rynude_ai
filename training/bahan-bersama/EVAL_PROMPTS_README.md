# Capability Eval Prompt Set — rynude Lyric 4.6

Tujuan: memetakan **apa yang sudah bisa** model yang sudah dilatih, dan **di mana
masih kurang** (untuk memutuskan latih lagi / naik ke canto/stanza).

## File
- `build_eval_prompts.py` — generator prompt (templates × topik × parafrase).
- `eval_prompts.jsonl` — hasil generate (~2.4k prompt). Format per baris:
  `{"id","category","subcategory","prompt","expect","notes"}`
- `run_eval_prompts.py` — jalankan prompt ke model lokal, rekam jawaban + flag.
- `eval_run.jsonl` — hasil run (dibuat oleh runner).

## Kategori & yang diuji
| kategori | menguji |
|---|---|
| `sapaan` | balasan hangat singkat, tidak berpidato |
| `penjelasan` | penjelasan akurat & terstruktur |
| `penalaran` / `matematika` | logika & hitung benar |
| `koding` | kode benar + debugging |
| `dokumen_skripsi` / `dokumen_lain` | skripsi/makalah/laporan/proposal jadi **artifact** dgn front-matter benar |
| `judul` | brainstorm judul spesifik |
| `lanjutan` | revisi → **update artifact** (versi baru), bukan teks |
| `upload` | jawab/lanjut **berdasar dokumen upload** (grounding) |
| `diagram` | keluarkan ```mermaid valid |
| `faktual` | fakta stabil benar; fakta terbaru → butuh web-search |
| `bahasa` | terjemah + konsistensi Bahasa Indonesia |
| `format` | tabel/list tepat |
| `anti_tolak` | **DILARANG** menolak bikin file / menyarankan Google Docs |
| `anti_halusinasi` | mengakui tidak tahu, tidak mengarang |

`anti_tolak` & `anti_halusinasi` dibuat dari kegagalan nyata yang ditemukan saat
pengujian live (model sempat menjawab "tidak bisa membuat dokumen… pakai Google
Docs").

## Cara pakai
```bash
# 1. Generate (atur jumlah 1000-5000)
python training/bahan-bersama/build_eval_prompts.py --target 2800 --out training/bahan-bersama/eval_prompts.jsonl

# 2. Jalankan subset ke model (server GGUF harus hidup di :8091)
python training/bahan-bersama/run_eval_prompts.py --limit 200
python training/bahan-bersama/run_eval_prompts.py --category anti_tolak,diagram,koding --limit 100
```

## PENTING — apa yang diukur runner
`run_eval_prompts.py` memanggil server GGUF (:8091) **langsung**, jadi mengukur
**model mentah**, BUKAN pipeline `ChatStreamingService`. Artinya:
- Bagus untuk menilai: penalaran, hitung, koding, fakta, bahasa, dan
  **kecenderungan menolak** / **spontan mengeluarkan `<antArtifact>`/```mermaid**.
- TIDAK mencerminkan perilaku dokumen di aplikasi (grammar, reminder per-tipe,
  pipeline per-bab, safety-net front-matter semuanya ada di pipeline, bukan di
  server mentah). Untuk perilaku dokumen sebenarnya, uji di aplikasi.

Ini sejalan dengan temuan bahwa `rynude:eval` juga menguji model mentah — skor
rendah pada dokumen sering berarti model butuh scaffolding pipeline (atau base
lebih besar), bukan bug aplikasi.

## Membaca hasil
Runner mencetak **capability map** (pass-rate per kategori). Kategori dengan
pass-rate rendah = kandidat untuk **tambah data latih** (mis. lewat
`build_dataset.py` → `train.jsonl`) atau alasan naik ke **canto/stanza**.

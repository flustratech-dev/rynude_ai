# Folder Training rynude — peta

Struktur dibagi **per model** + satu folder alat/bahan bersama. Baca ini dulu biar
tidak bingung.

```
training/
├── README.md            ← file ini (peta folder)
│
├── bahan-bersama/       🔧 ALAT & DATA MENTAH — dipakai SEMUA model
│   ├── build_train_large.py   generator dataset training (10k–50k)
│   ├── build_eval_prompts.py  generator prompt eval kemampuan
│   ├── run_eval_prompts.py    jalankan eval → peta kemampuan model
│   ├── build_dataset.py       gabung + isi prose via teacher (DeepSeek/Qwen)
│   ├── make_golden_fixes.py   generator contoh emas
│   ├── golden_examples.jsonl  contoh emas buatan tangan
│   ├── golden_fixes.jsonl     contoh perbaikan
│   ├── seeds.jsonl            prompt asli user (rynude:export-seeds)
│   ├── eval_prompts.jsonl     2.442 prompt uji kemampuan
│   ├── rancangan loRA.md      rencana besar + aturan legal
│   └── README-toolkit.md      readme toolkit LoRA (lama)
│
├── lyric-4.6/           🎯 MODEL 1 — Qwen3-1.7B — LATIH DULU
│   ├── dataset.jsonl          15.000 record (siap latih)
│   ├── RUNBOOK.md             cara latih (Lyric dulu, lalu Stanza/Canto)
│   ├── LANGKAH_TRAINING.md    langkah detail versi lama
│   ├── colab.ipynb            notebook Colab
│   ├── train.jsonl / val.jsonl  dataset lama (63 baris)
│   └── eval_run.jsonl         hasil eval (dibuat saat run)
│
├── stanza-4b/           🚀 MODEL 2 — Qwen3-4B (nanti, kalau perlu lebih kuat)
│   └── RUNBOOK.md
│
└── canto-8b/            💪 MODEL 3 — Qwen3-8B (paling kuat, terakhir)
    └── RUNBOOK.md
```

## Urutan kerja
1. **Sekarang:** latih **Lyric 4.7** dari `lyric-4.6/dataset.jsonl` (base Qwen3-1.7B).
   Ikuti `lyric-4.6/RUNBOOK.md`.
2. **Kalau masih kurang:** pakai **dataset yang sama** untuk **Stanza 4B**
   (`stanza-4b/RUNBOOK.md`), lalu **Canto 8B** bila perlu.

## Aturan penting
- **Dataset base-agnostic** — satu dataset dipakai semua model, cukup ganti base di
  Colab. Tidak perlu bikin ulang per model.
- **Legal:** JANGAN pakai output Claude/GPT/Gemini di data latih. Prose diisi
  teacher open-weights (DeepSeek/Qwen). Lihat `bahan-bersama/rancangan loRA.md`.

## Menjalankan skrip (dari root repo)
```bash
# generate/ perbesar dataset
python training/bahan-bersama/build_train_large.py --target 30000 --out training/lyric-4.6/dataset.jsonl
# eval kemampuan model (server GGUF harus hidup)
python training/bahan-bersama/run_eval_prompts.py --limit 200
```

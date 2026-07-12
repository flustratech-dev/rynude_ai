# rynude Stanza — Qwen3-4B

Model penerus Lyric, base lebih besar (4B). **Latih ini SETELAH Lyric 4.7**, dan
hanya jika Lyric 4.7 masih kurang di nalar / anti-halusinasi (itu batas base 1.7B).

## Dataset
Pakai dataset yang **sama** dengan Lyric — bersifat base-agnostic:
- `../lyric-4.6/dataset.jsonl` (langsung pakai), atau regenerate ke sini:
  ```bash
  python training/bahan-bersama/build_train_large.py --target 30000 --out training/stanza-4b/dataset.jsonl
  ```
- Isi bagian prose (`needs_teacher`) via teacher legal (DeepSeek/Qwen), lihat
  `../lyric-4.6/RUNBOOK.md` langkah 2.

## Training (Colab)
- Base: `Qwen3-4B` (mis. `unsloth/Qwen3-4B`) — ganti `MODEL_NAME` di notebook.
- Method: QLoRA 4-bit. Hyperparameter: lihat tabel di `../lyric-4.6/RUNBOOK.md`
  (naikkan `max_seq_len` ke 8192 bila VRAM cukup — dokumen panjang).
- Butuh VRAM lebih besar dari 1.7B; T4 gratis-an cukup untuk QLoRA 4B.

## Setelah jadi
- Export GGUF → `storage/app/models/`, daftarkan kode `llama-3.2-3b` (slot Stanza)
  di `LlamaServerService::CATALOG` + `ModelHubController::getCatalog()`, clear cache.
- Eval: `python training/bahan-bersama/run_eval_prompts.py --limit 200`.

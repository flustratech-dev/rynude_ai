# rynude Canto — Qwen3-8B

Model paling kuat di jalur ini (base 8B). **Latih hanya jika Stanza 4B pun masih
kurang.** Resep & dataset sama, base lebih besar.

## Dataset
Base-agnostic, sama dengan Lyric/Stanza:
- `../lyric-4.6/dataset.jsonl`, atau regenerate:
  ```bash
  python training/bahan-bersama/build_train_large.py --target 40000 --out training/canto-8b/dataset.jsonl
  ```
- Isi prose via teacher legal (DeepSeek/Qwen) — lihat `../lyric-4.6/RUNBOOK.md`.

## Training (Colab)
- Base: `Qwen3-8B` (mis. `unsloth/Qwen3-8B`) — ganti `MODEL_NAME`.
- Method: QLoRA 4-bit. Butuh GPU lebih besar (A100 direkomendasikan; T4 mepet).
- `max_seq_len` 8192 untuk dokumen panjang bila VRAM cukup.

## Setelah jadi
- Export GGUF → `storage/app/models/`, daftarkan kode `mistral-7b-v0.3` (slot Canto)
  di `LlamaServerService::CATALOG` + `ModelHubController::getCatalog()`, clear cache.
- Eval: `python training/bahan-bersama/run_eval_prompts.py --limit 200`.

#!/usr/bin/env python3
"""
train_local.py — Latih rynude Lyric 4.7 di GPU LOKAL (versi CEPAT + tahan-putus).

Perbaikan vs versi lambat:
  * PACKING: contoh-contoh pendek digabung jadi blok panjang tetap -> langkah
    training jauh lebih sedikit -> jauh lebih cepat (mirip trik Colab).
  * AUTO-SAVE tiap 25 langkah + RESUME: kalau terputus, jalankan lagi perintah
    yang sama -> otomatis lanjut dari checkpoint terakhir (tidak mulai dari nol).

Dirancang untuk GPU 8 GB (RTX 5050). Mode LoRA bf16 (tanpa bitsandbytes).

Output:
  outputs_local/                 checkpoint (untuk resume)
  rynude-lyric-4.7-lora/         adapter LoRA final
  rynude-lyric-4.7-merged/       model gabungan (fp16) -> konversi ke GGUF

Jalankan (dari folder training/lyric-4.6/):
  <venv>/python.exe train_local.py
Kalau terputus, jalankan lagi perintah yang sama (auto-resume).
"""
import argparse
import os
import torch
from datasets import load_dataset
from transformers import (AutoModelForCausalLM, AutoTokenizer, TrainingArguments,
                          Trainer, default_data_collator)
from peft import LoraConfig, get_peft_model


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default="Qwen/Qwen3-1.7B")
    ap.add_argument("--data", default="dataset_4.8.jsonl")
    ap.add_argument("--out", default="rynude-lyric-4.7")
    ap.add_argument("--block", type=int, default=1024, help="panjang blok packing")
    ap.add_argument("--epochs", type=float, default=1.0)
    ap.add_argument("--batch", type=int, default=1)
    ap.add_argument("--accum", type=int, default=16)
    ap.add_argument("--lr", type=float, default=2e-4)
    args = ap.parse_args()

    assert torch.cuda.is_available(), "CUDA tidak terdeteksi! (lihat SETUP_LOKAL_GPU.md)"
    print("GPU:", torch.cuda.get_device_name(0),
          f"| VRAM: {torch.cuda.get_device_properties(0).total_memory/1e9:.1f} GB")

    tok = AutoTokenizer.from_pretrained(args.base)
    if tok.pad_token is None:
        tok.pad_token = tok.eos_token
    eos = tok.eos_token_id

    print(">> Mode: LoRA bf16 + PACKING (cepat).")
    model = AutoModelForCausalLM.from_pretrained(args.base, dtype=torch.bfloat16)
    model.to("cuda")
    model.config.use_cache = False
    model.gradient_checkpointing_enable()
    model.enable_input_require_grads()
    model = get_peft_model(model, LoraConfig(
        r=32, lora_alpha=64, lora_dropout=0.05,
        target_modules=["q_proj", "k_proj", "v_proj", "o_proj",
                        "gate_proj", "up_proj", "down_proj"],
        bias="none", task_type="CAUSAL_LM"))
    model.print_trainable_parameters()

    # --- tokenize + PACKING (group_texts) ---
    raw = load_dataset("json", data_files=args.data)["train"]

    def enc(ex):
        text = tok.apply_chat_template(ex["messages"], tokenize=False,
                                       add_generation_prompt=False)
        ids = tok(text, add_special_tokens=False)["input_ids"] + [eos]
        return {"ids": ids}
    raw = raw.map(enc, remove_columns=raw.column_names, desc="Tokenize")

    block = args.block

    def group(batch):
        concat = []
        for ids in batch["ids"]:
            concat.extend(ids)
        n = (len(concat) // block) * block
        chunks = [concat[i:i + block] for i in range(0, n, block)]
        return {"input_ids": chunks, "labels": [c[:] for c in chunks]}
    packed = raw.map(group, batched=True, batch_size=1000,
                     remove_columns=raw.column_names, desc="Packing")
    print(f"Setelah packing: {len(packed)} blok x {block} token "
          f"(dari {len(raw)} contoh). Perkiraan langkah/epoch: "
          f"{max(1, len(packed)//(args.batch*args.accum))}")

    # --- resume kalau ada checkpoint ---
    outdir = "outputs_local"
    resume = os.path.isdir(outdir) and any(
        d.startswith("checkpoint-") for d in os.listdir(outdir))
    if resume:
        print(">> Ditemukan checkpoint -> MELANJUTKAN dari langkah terakhir.")

    trainer = Trainer(
        model=model,
        args=TrainingArguments(
            output_dir=outdir,
            per_device_train_batch_size=args.batch,
            gradient_accumulation_steps=args.accum,
            num_train_epochs=args.epochs,
            learning_rate=args.lr,
            lr_scheduler_type="cosine",
            warmup_steps=10,
            logging_steps=5,
            bf16=True,
            optim="adamw_torch",
            save_strategy="steps",
            save_steps=25,            # simpan tiap 25 langkah (tahan-putus)
            save_total_limit=2,
            report_to="none",
        ),
        train_dataset=packed,
        data_collator=default_data_collator,
    )
    trainer.train(resume_from_checkpoint=resume)

    # --- simpan adapter + merge fp16 (di CPU) untuk konversi GGUF ---
    model.save_pretrained(args.out + "-lora")
    tok.save_pretrained(args.out + "-lora")
    print("Adapter LoRA:", args.out + "-lora")

    print("Menggabungkan LoRA ke model dasar (fp16, di CPU)...")
    del model, trainer
    torch.cuda.empty_cache()
    from peft import PeftModel
    base = AutoModelForCausalLM.from_pretrained(args.base, dtype=torch.float16)
    merged = PeftModel.from_pretrained(base, args.out + "-lora").merge_and_unload()
    merged.save_pretrained(args.out + "-merged", safe_serialization=True)
    tok.save_pretrained(args.out + "-merged")
    print("SELESAI. Model gabungan:", args.out + "-merged")
    print(">> Lanjut konversi ke GGUF: SETUP_LOKAL_GPU.md langkah 4.")


if __name__ == "__main__":
    main()

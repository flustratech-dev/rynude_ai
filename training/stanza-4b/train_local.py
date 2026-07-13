#!/usr/bin/env python3
"""
train_local.py — Latih rynude Stanza 4.6 (Qwen3-4B) di GPU LOKAL.

Sama seperti resep Lyric (PACKING + auto-save/RESUME) TAPI base 4B tidak muat
bf16 penuh di 8 GB, jadi memakai QLoRA 4-bit (bitsandbytes). Perlu:
  * bitsandbytes (sudah dipasang)
  * base Qwen/Qwen3-4B (safetensors) di HF cache

Cepat: PACKING blok tetap -> langkah sedikit. Tahan-putus: auto-save tiap 25
langkah, jalankan ulang perintah yang sama -> lanjut dari checkpoint.

Output:
  outputs_local/                  checkpoint (untuk resume)
  rynude-stanza-4.6-lora/         adapter LoRA final
  rynude-stanza-4.6-merged/       model gabungan (fp16) -> konversi ke GGUF

Jalankan (dari folder training/stanza-4b/):
  <venv>/python.exe train_local.py
Kalau terputus, jalankan lagi perintah yang sama (auto-resume).
"""
import argparse
import os
import torch
from datasets import load_dataset
from transformers import (AutoModelForCausalLM, AutoTokenizer, TrainingArguments,
                          Trainer, default_data_collator, BitsAndBytesConfig)
from peft import LoraConfig, get_peft_model, prepare_model_for_kbit_training


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--base", default="Qwen/Qwen3-4B")
    ap.add_argument("--data", default="dataset_stanza46.jsonl")
    ap.add_argument("--out", default="rynude-stanza-4.6")
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

    print(">> Mode: QLoRA 4-bit + PACKING (cepat, muat 8 GB).")
    bnb = BitsAndBytesConfig(
        load_in_4bit=True,
        bnb_4bit_quant_type="nf4",
        bnb_4bit_use_double_quant=True,
        bnb_4bit_compute_dtype=torch.bfloat16,
    )
    model = AutoModelForCausalLM.from_pretrained(
        args.base, quantization_config=bnb, dtype=torch.bfloat16, device_map={"": 0})
    model.config.use_cache = False
    model = prepare_model_for_kbit_training(model, use_gradient_checkpointing=True)
    model = get_peft_model(model, LoraConfig(
        r=16, lora_alpha=32, lora_dropout=0.05,
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
            optim="paged_adamw_8bit",   # hemat VRAM (bitsandbytes)
            save_strategy="steps",
            save_steps=25,              # simpan tiap 25 langkah (tahan-putus)
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
    print(">> Lanjut konversi ke GGUF (q6_K/q4_K_M) lalu daftarkan Stanza 4.6.")


if __name__ == "__main__":
    main()

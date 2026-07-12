# Latih Lyric 4.7 di GPU LOKAL Anda (tanpa Colab)

GPU terdeteksi: **NVIDIA RTX 5050 Laptop (8 GB, CUDA 595.97)**. Cukup untuk QLoRA/LoRA 1.7B.

> ⚠️ **Catatan jujur:** RTX seri 50 (Blackwell) itu GPU 2025 yang sangat baru. Sebagian
> library (bitsandbytes/triton) mungkin belum punya versi siap-pakai. Karena itu
> `train_local.py` dibuat **otomatis turun ke mode LoRA bf16** kalau QLoRA 4-bit
> gagal — jadi tetap bisa jalan. Kalau setup rumit, jalur **Colab** (`TUTORIAL_COLAB.md`)
> tetap tersedia dan lebih pasti.

## Pilih jalur
- **A. Windows langsung** — paling gampang dicoba (skrip ini portable, tanpa unsloth).
- **B. WSL2 (Ubuntu)** — paling stabil untuk training; sudah aktif di PC Anda.

---

## Langkah 1 — Python + PyTorch CUDA (WAJIB versi Blackwell)
Pakai Python 3.10–3.12. Buat virtual env, lalu install **PyTorch build CUDA 12.8**
(cu128 = yang mendukung RTX seri 50):

```bash
python -m venv .venv
# Windows:  .venv\Scripts\activate      |  WSL/Linux:  source .venv/bin/activate
pip install --upgrade pip

# PyTorch untuk Blackwell (cu128):
pip install torch --index-url https://download.pytorch.org/whl/cu128
```
Cek GPU terbaca:
```bash
python -c "import torch; print(torch.cuda.is_available(), torch.cuda.get_device_name(0))"
```
Harus `True NVIDIA GeForce RTX 5050 ...`. Kalau `False` → PyTorch-nya bukan versi CUDA,
ulangi install di atas.

## Langkah 2 — Library training
```bash
pip install "transformers>=4.44" "peft>=0.11" "datasets>=2.20" "accelerate>=0.33"
# Opsional (QLoRA hemat VRAM). Kalau gagal di RTX 50, SKIP saja — skrip otomatis pakai bf16:
pip install bitsandbytes
```

## Langkah 3 — Latih
Dari folder `training/lyric-4.6/` (pastikan `dataset_upgrade.jsonl` ada di situ):
```bash
python train_local.py
# atau lebih cepat / kalau VRAM mepet:
python train_local.py --subset 2500 --max-len 640 --batch 1 --accum 16
```
- Model dasar `Qwen/Qwen3-1.7B` otomatis terunduh sekali (~3.5 GB).
- Selesai → muncul folder `rynude-lyric-4.7-merged/` (model gabungan, siap dikonversi).
- Kalau **CUDA out of memory**: turunkan `--max-len` (mis. 512) dan pastikan `--batch 1`.

## Langkah 4 — Konversi hasil ke GGUF (file untuk aplikasi)
Butuh skrip konversi dari llama.cpp (sekali saja):
```bash
git clone https://github.com/ggerganov/llama.cpp
pip install -r llama.cpp/requirements.txt

# konversi + kuantisasi Q8_0 langsung:
python llama.cpp/convert_hf_to_gguf.py rynude-lyric-4.7-merged \
    --outfile rynude-lyric-4.7-Q8_0.gguf --outtype q8_0
```
Hasil: `rynude-lyric-4.7-Q8_0.gguf`.

## Langkah 5 — Pasang ke aplikasi
1. Taruh `rynude-lyric-4.7-Q8_0.gguf` di `storage/app/models/`.
2. Daftarkan ke Model Hub — **kirim nama file-nya ke Claude Code**, nanti ditambahkan ke
   `LlamaServerService::CATALOG` + `ModelHubController::getCatalog()` (tanpa menimpa 4.6).
3. Clear cache → model baru muncul di dropdown app.
4. Ukur: `php artisan rynude:eval <kode-model-baru>`.

---

## Kalau macet di setup (jujur soal kemungkinan)
RTX 50-series masih baru; kalau `bitsandbytes` atau PyTorch bermasalah:
1. **SKIP bitsandbytes** — skrip tetap jalan mode bf16 (butuh ~5–6 GB, muat di 8 GB).
2. Kalau PyTorch tak mengenali GPU → coba **PyTorch nightly cu128**:
   `pip install --pre torch --index-url https://download.pytorch.org/whl/nightly/cu128`
3. Kalau tetap rewel di Windows → jalankan langkah yang sama di **WSL2 Ubuntu** (lebih stabil).
4. Kalau semua mentok → pakai **Colab** (`TUTORIAL_COLAB.md`), pasti jalan.

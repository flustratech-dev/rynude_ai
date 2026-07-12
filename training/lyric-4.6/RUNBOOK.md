# Latih rynude — Lyric 4.7 DULU, lalu Stanza (Qwen3-4B)

Tujuan: model yang lebih patuh & rapi dari Lyric 4.6, memakai dataset besar yang
**menargetkan kelemahan Lyric** (dokumen panjang, front-matter, anti-nolak-file,
anti-halusinasi).

## Urutan (sesuai keputusan: Lyric dulu)
1. **Lyric 4.7** — latih ulang base **Qwen3-1.7B** dengan dataset ini. Murah &
   cepat; memvalidasi dataset + resep. Memperbaiki PERILAKU/FORMAT (anti-nolak,
   front-matter, anti-halu — porsi terbesar dataset).
2. **Stanza (Qwen3-4B)** — pakai dataset yang SAMA, base lebih besar. Lakukan
   HANYA jika Lyric 4.7 masih kurang di nalar/anti-halusinasi (itu batas 1.7B).

> Dataset `train_large.jsonl` **base-agnostic** — untuk pindah target cukup ganti
> `MODEL_NAME` di notebook Colab (`Qwen3-1.7B` → `Qwen3-4B`). Tidak ada kerja ulang.

> Catatan jujur: melatih 1.7B lagi memperbaiki kepatuhan/format, TAPI tidak
> menghilangkan halusinasi dari base kecil. Untuk benar-benar "over power" tetap
> butuh base lebih besar (langkah 2).

## ⚖️ Aturan legal (WAJIB)
**Tidak boleh** memakai output Claude / GPT / Gemini dalam data latih. Dua sumber
bersih saja:
1. **Programatik** (`build_train_large.py`) — template + fakta terhitung. Legal.
2. **Teacher open-weights** — DeepSeek / Qwen via OpenRouter (`build_dataset.py`).

## Langkah (yang di luar Colab bisa jalan tanpa GPU)

### 1. Bangun dataset besar (di mesin ini — cepat, tanpa GPU)
```bash
# 15k (default) … naikkan sampai 40k untuk skala penuh
python training/bahan-bersama/build_train_large.py --target 30000 --out training/lyric-4.6/dataset.jsonl
```
Hasil: campuran `programmatic` (siap latih) + `needs_teacher` (prose, diisi nanti).

### 2. Isi bagian prose lewat teacher legal (butuh OPENROUTER_API_KEY)
```bash
export OPENROUTER_API_KEY=sk-or-...
# jawab record needs_teacher pakai DeepSeek/Qwen (BUKAN Claude/GPT/Gemini)
python training/bahan-bersama/build_dataset.py --seeds training/lyric-4.6/dataset.jsonl \
    --teacher deepseek/deepseek-chat --augment 3000
```
> Ini memakai free/murah open-weights. GPU tidak diperlukan untuk langkah ini.
> Gabungkan hasilnya dengan `golden_examples.jsonl` (gold buatan tangan) dan
> `golden_fixes.jsonl` sebagai anchor kualitas.

### 3. Split train/val, lalu latih di Colab (GPU)
- Base (pilih sesuai target):
  - **Lyric 4.7** → `Qwen3-1.7B` (unsloth/Qwen3-1.7B) — LATIH INI DULU.
  - **Stanza** → `Qwen3-4B` (unsloth/Qwen3-4B) — nanti, dataset sama.
  Ganti `MODEL_NAME` di notebook.
- Method: **QLoRA 4-bit** (muat di T4/A100 gratis-an; 1.7B jauh lebih ringan).
- Data: `train_large.jsonl` (ChatML) → template chat Qwen3.

Hyperparameter awal (15k–40k contoh, QLoRA 4B):
| param | nilai |
|---|---|
| lora_r / lora_alpha | 32 / 64 |
| lora_dropout | 0.05 |
| target_modules | q,k,v,o,gate,up,down proj |
| epochs | 2 (naikkan ke 3 bila val loss masih turun) |
| batch (efektif) | 16 (mis. bs 2 × grad-accum 8) |
| lr | 2e-4 (cosine, warmup 3%) |
| max_seq_len | 4096 (8192 bila VRAM cukup — dokumen panjang) |
| quant | 4-bit nf4, bf16 compute |

### 4. Export GGUF & pasang
- Merge LoRA → GGUF (Q4_K_M atau Q8_0) via llama.cpp `convert` + `quantize`.
- Taruh file di `storage/app/models/`, daftarkan di
  `LlamaServerService::CATALOG` + `ModelHubController::getCatalog()` (kode Stanza
  sudah ada: `llama-3.2-3b` → ganti file GGUF-nya) lalu clear cache.

## Kurikulum (kenapa dataset ini bikin "over power" di titik yang tepat)
- **Programatik (12k+)** melatih PERILAKU/FORMAT yang gagal di Lyric: selalu
  keluarkan `<antArtifact>` + front-matter `mode:` benar, TIDAK menolak bikin
  file, mengakui saat tidak tahu, aritmetika benar, Mermaid valid. Ini bukan soal
  ukuran base — semua model butuh alignment ini, dan template melatihnya murah +
  legal.
- **Teacher (prose)** memberi KUALITAS ISI (penjelasan, koding, penalaran) yang
  tidak boleh dittemplate. Diambil dari open-weights (legal), bukan Claude.
- **Base 4B** memberi daya nalar & anti-halusinasi yang tak bisa dicapai 1.7B.

Gabungan ketiganya > sekadar menumpuk 50k contoh dari satu sumber. Jumlah 10k–50k
itu cukup; yang menentukan "over power" adalah **base lebih besar + kualitas
teacher + data perilaku yang tepat sasaran**.

## Catatan realistis
- **Training GPU-nya di Colab**, bukan mesin ini (GPU lokal belum stabil).
- Setelah Stanza jadi, evaluasi dengan `run_eval_prompts.py`. Jika masih kurang →
  ulangi untuk **Canto (Qwen3-8B)** dengan dataset yang sama.

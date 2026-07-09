# Rancangan Lengkap Fine-Tuning LoRA — "rynude Tuned Series"

> **Status:** Rancangan (belum dieksekusi) · **Item:** perubahan.md #4 · **Tanggal:** 9 Juli 2026
> **Tujuan akhir:** model lokal rynude yang dilatih khusus (bahasa Indonesia, skripsi/akademik, format artifact, tanya-jawab) dengan kualitas melampaui model generik seukurannya — **dan legal untuk dijual**.

---

## 0. Ringkasan Eksekutif (baca ini dulu)

| Pertanyaan | Jawaban singkat |
|---|---|
| Apa yang dibuat? | Versi terlatih dari rynude Lyric (Qwen3-1.7B) dan rynude Stanza (Qwen3-4B), dilatih dengan metode QLoRA di atas ~10.000 contoh percakapan berkualitas tinggi |
| Berapa lama? | 5–7 minggu end-to-end (mayoritas waktu di penyiapan data, BUKAN di training) |
| Berapa biaya? | Rp 1–4 juta (sewa GPU cloud + pembuatan data sintetis) — rincian di Bab 11 |
| Apa ukuran berhasil? | Skor `rynude:eval` naik dari baseline ke target (Bab 8), nol kebocoran bahasa Inggris, artifact selalu valid |
| Apa risiko terbesar? | **Legalitas data latih** (Bab 2) dan **kualitas data** (Bab 5) — 80% kegagalan proyek LoRA terjadi di dua hal ini, bukan di proses training-nya |
| Boleh dijual? | Ya, DENGAN SYARAT mengikuti aturan data di Bab 2. Qwen3 berlisensi Apache 2.0 (bebas komersial); yang bisa membatalkan "siap jual" adalah sumber data latihnya |

**Prinsip utama proyek ini:** *data yang bagus + evaluasi yang jujur > GPU yang besar.* Model 1.7B yang dilatih dengan 5.000 contoh sempurna akan mengalahkan model yang sama dilatih dengan 50.000 contoh asal-asalan.

---

## 1. Latar Belakang & Masalah yang Mau Diselesaikan

### 1.1 Kondisi sekarang (sesudah perbaikan sistem non-LoRA)

Sistem sudah punya pagar-pagar berikut (semuanya level *sistem*, bukan level *otak model*):

- Pipeline skripsi per-bab + laporan per tahap (perubahan.md #2)
- Constrained output GBNF untuk format artifact (#3)
- GPU offload (#7), eval harness `rynude:eval` (#9)
- Satpam bahasa real-time (deteksi jawaban Inggris → buang → ulang)
- Aturan gaya percakapan di prompt (anti-pidato, anti-interogasi)

### 1.2 Yang TIDAK bisa diselesaikan pagar sistem (alasan LoRA dibutuhkan)

| Masalah | Kenapa pagar tidak cukup | Yang LoRA lakukan |
|---|---|---|
| Bahasa Indonesia-nya kaku/canggung ("Perkenalkan lagi detail:") | Prompt hanya memaksa *bahasa apa*, bukan *seberapa natural* | Model *terbiasa* menulis Indonesia natural karena ribuan contohnya |
| Jawaban ngelantur/halu ("saya tidak bisa berkomunikasi seperti manusia") | Satpam hanya menangkap salah bahasa, bukan salah isi | Pola jawaban yang benar tertanam permanen |
| Format artifact/front-matter kadang salah tanpa grammar | Grammar adalah paksaan eksternal, model tetap "tidak paham" | Format jadi refleks internal — grammar tinggal jadi sabuk pengaman |
| Kualitas prosa skripsi dangkal | Prompt bisa minta "3 paragraf substantif", model kecil tetap menulis dangkal | Belajar dari contoh bab skripsi yang benar-benar dalam |
| Satpam bahasa menambah latensi saat misfire | Ulang-generate = 2× waktu | Model jarang salah bahasa → satpam hampir tidak pernah aktif |

### 1.3 Apa itu LoRA (untuk dokumen ini)

LoRA (Low-Rank Adaptation) = melatih "lapisan koreksi" kecil (puluhan MB) di atas model dasar yang dibekukan. QLoRA = LoRA di atas model yang dikuantisasi 4-bit, sehingga bisa dilatih di GPU murah. Hasil akhirnya di-*merge* ke bobot dasar lalu dikonversi ke GGUF — pengguna akhir mengunduh satu file .gguf seperti biasa, tidak tahu-menahu soal LoRA.

---

## 2. ⚠️ LEGALITAS DATA — PENENTU "SIAP JUAL" (WAJIB BACA)

Ini bab paling penting untuk tujuan komersial. **Model dasarnya aman; data latihnya yang bisa membuat produk tidak boleh dijual.**

### 2.1 Status lisensi komponen

| Komponen | Lisensi | Boleh komersial? |
|---|---|---|
| Qwen3 (0.6B–14B) | Apache 2.0 | ✅ Ya, bebas — boleh di-fine-tune, di-rebrand, dijual. Cukup sertakan teks lisensi & atribusi |
| node-llama-cpp / llama.pp | MIT | ✅ Ya |
| Hasil LoRA di atas Qwen3 | Milik Anda (karya turunan Apache 2.0) | ✅ Ya |

### 2.2 Sumber data: mana yang AMAN dan mana yang BERBAHAYA

| Sumber data | Status untuk produk komersial | Catatan |
|---|---|---|
| ❌ Output Claude (Anthropic) | **DILARANG** | ToS Anthropic melarang penggunaan output untuk melatih model AI pesaing. Untuk produk yang DIJUAL, ini risiko hukum nyata |
| ❌ Output ChatGPT/GPT-4 (OpenAI) | **DILARANG** | Sama — ToS OpenAI melarang "use output to develop models that compete with OpenAI" |
| ❌ Output Gemini (Google) | **DILARANG** | Ketentuan serupa |
| ✅ Output model open-weights permisif sebagai "guru": **Qwen3-235B/32B (Apache 2.0)**, **DeepSeek-V3/R1 (MIT)**, **GLM-4 (MIT)** | **AMAN** | Ini jalur distilasi yang legal. Kualitas guru-guru ini sudah kelas GPT-4 untuk tugas kita |
| ✅ Percakapan buatan sendiri / tim (ditulis manusia) | **AMAN & bernilai paling tinggi** | Mahal waktunya, pakai untuk contoh-contoh emas |
| ⚠️ Riwayat chat pengguna rynude | **Boleh DENGAN SYARAT** | (a) hanya percakapan milik Anda sendiri, ATAU ada persetujuan eksplisit pengguna; (b) WAJIB dibersihkan dari data pribadi (nama, NIM, alamat) — lihat 5.6; (c) jika jawaban asisten dalam riwayat itu berasal dari Claude/GPT → **tidak boleh dipakai** (kembali ke larangan di atas). Yang aman dipakai dari riwayat: *pertanyaan/prompt pengguna* (sebagai seed), bukan jawabannya |
| ✅ Dataset publik ber-lisensi jelas: `indonesian-nlp`, Cendol (Apache-2.0), OASST2 (Apache-2.0, filter subset Indonesia), Aya Dataset (Apache-2.0) | **AMAN** | Cek lisensi per-dataset sebelum pakai, catat di manifest data |

### 2.3 Keputusan rancangan (final)

1. **Guru distilasi resmi proyek ini: DeepSeek-V3 / Qwen3-235B via API murah (DeepSeek API / OpenRouter / self-host)** — bukan Claude, bukan GPT. Titik.
2. Riwayat chat rynude dipakai **hanya sebagai sumber pertanyaan (seed prompt)**, jawabannya dibuang dan dibuat ulang oleh model guru.
3. Setiap contoh data diberi kolom `source` + `license` di manifest → produk punya **bukti audit** kalau suatu saat ditanya.
4. Sertakan `NOTICE`/`LICENSE-THIRD-PARTY.md` di produk: atribusi Qwen (Apache 2.0), llama.cpp (MIT), dataset publik yang dipakai.

---

## 3. Sasaran, Target Model, dan Definisi Sukses

### 3.1 Model yang dilatih (dua target, satu resep)

| Prioritas | Base model | Nama produk | Alasan |
|---|---|---|---|
| **P1 (wajib)** | Qwen3-1.7B | **rynude Lyric+** | Model default mayoritas pengguna; dampak terluas; termurah dilatih |
| **P2 (setelah P1 terbukti)** | Qwen3-4B | **rynude Stanza+** | Kelas "pintar" yang tetap jalan di laptop 8GB; kandidat produk premium |
| P3 (opsional, jika laku) | Qwen3-8B | rynude Canto+ | Untuk tier pro |

> Resep data & training sama persis; hanya base model dan hyperparameter yang menyesuaikan. Latih P1 dulu sampai lulus semua kriteria, baru ulangi ke P2.

### 3.2 Kemampuan yang ditargetkan (sesuai ekspektasi pemakaian nyata)

1. **Bahasa Indonesia natural & konsisten** — nol jawaban Inggris untuk pertanyaan Indonesia, gaya luwes tidak kaku
2. **Skripsi/akademik** — prosa akademik dalam, front-matter YAML benar, struktur bab baku, kutipan (penulis, tahun), daftar pustaka konsisten
3. **Format artifact** — `<antArtifact type="text/markdown" title="...">` refleks, tanpa ```markdown fence, penjelasan di luar artifact
4. **Tanya-jawab & percakapan** — sapaan dibalas wajar, permintaan sederhana langsung dikerjakan, maksimal satu klarifikasi
5. **Tombol pilihan** — tahu kapan memakai `<antOptions>A | B | C</antOptions>` dengan opsi yang tajam
6. **Coding dasar** — jawaban kode utuh + penjelasan Indonesia
7. **Kejujuran** — berani bilang tidak tahu; tidak mengarang kemampuan/keterbatasan aneh

### 3.3 Definisi sukses (angka, bukan perasaan)

Diukur dengan `php artisan rynude:eval <model>` (harness #9 — 13 soal tetap) + suite tambahan (Bab 8):

| Metrik | Baseline Lyric (ukur dulu!) | Target Lyric+ |
|---|---|---|
| Skor total rynude:eval | catat sebelum training | **≥ +15 poin** dari baseline, minimal ≥ 75/100 |
| Soal `artifact-skripsi` | catat | 100% (artifact tertutup + front-matter + BAB + pustaka) |
| Soal `artifact-makalah` | catat | 100% |
| Kebocoran bahasa (suite bahasa, 20 prompt Indonesia) | catat | **0/20** jawaban Inggris TANPA satpam sistem menyala |
| Loop/pengulangan | catat | 0 kejadian |
| Human eval 30 prompt (rubrik 1–5, Bab 8.3) | catat | rata-rata ≥ 4.0 dan tidak ada skor 1 |
| Regresi | — | Tidak ada soal yang tadinya benar jadi salah (toleransi 1 soal) |

**Aturan besi:** kalau target tidak tercapai → JANGAN rilis; kembali ke Bab 5 (perbaiki data), bukan menambah epoch.

---

## 4. Arsitektur Solusi & Alur Kerja Besar

```
[Fase 0] Baseline & persiapan
    └─ ukur rynude:eval semua model sekarang, bekukan angkanya

[Fase 1] Desain & pengumpulan data  (≈ 2–3 minggu, fase terpenting)
    ├─ seed prompt: riwayat chat (pertanyaannya saja) + daftar topik buatan
    ├─ generasi jawaban oleh model GURU legal (DeepSeek-V3 / Qwen3-235B)
    ├─ rejection sampling: jawaban dicek mesin (format, bahasa, panjang) → buang yang jelek
    ├─ kurasi manusia untuk subset emas
    └─ bersihkan PII, dedup, format ChatML, split train/dev

[Fase 2] Training QLoRA  (≈ 2–4 hari kerja efektif)
    ├─ platform: Unsloth (tercepat & termurah) di GPU sewaan
    ├─ eksperimen kecil (500 contoh) → sanity check → full run
    └─ 2–3 varian hyperparameter, pilih terbaik lewat dev-set

[Fase 3] Evaluasi ketat  (≈ 3–5 hari)
    ├─ rynude:eval + suite bahasa + human eval + uji regresi
    └─ gagal? → kembali ke Fase 1 dengan diagnosis, bukan nambah epoch buta

[Fase 4] Konversi & integrasi produk  (≈ 2–3 hari)
    ├─ merge LoRA → bobot penuh → convert GGUF → kuantisasi (Q8_0 / Q4_K_M)
    ├─ uji di node-llama-cpp lokal (chat template, <think>, grammar tetap jalan)
    └─ masukkan ke Model Hub sebagai model BARU (jangan timpa yang lama)

[Fase 5] Rilis & komersial  (≈ 1 minggu)
    ├─ A/B internal → beta tester → rilis
    └─ paket lisensi, atribusi, changelog, harga
```

---

## 5. Dataset — Jantung Proyek (paling detail, paling menentukan)

### 5.1 Target jumlah & prinsip

- **Target: 8.000–12.000 contoh percakapan** berkualitas untuk v1. (Riset & praktik menunjukkan 5–10 ribu contoh terkurasi sudah mengubah perilaku model kecil secara dramatis; 100 ribu contoh kotor justru merusak.)
- **Rasio buang:** rencanakan generate 2–3× target (≈ 25.000) karena rejection sampling akan membuang 50–70%.
- Setiap contoh = percakapan multi-giliran ChatML lengkap dengan system prompt PRODUKSI (bukan system prompt generik!) — model harus dilatih dengan prompt yang sama dengan yang dipakai aplikasi (`buildLocalModelSystemPrompt`), supaya perilaku latih = perilaku produksi.

### 5.2 Komposisi data (resep campuran)

| Kategori | Porsi | Jumlah (dari 10rb) | Isi |
|---|---|---|---|
| A. Percakapan umum Indonesia | 25% | 2.500 | Sapaan, tanya-jawab harian, penjelasan konsep, multi-giliran; termasuk 300+ contoh "sapaan dibalas singkat" dan "permintaan sederhana langsung dikerjakan" (obat anti-pidato/anti-interogasi) |
| B. Skripsi & akademik | 25% | 2.500 | Per-bab (format pipeline: sistem minta 1 bab → jawab 1 bab penuh), metadata YAML, abstrak ID+EN yang TIDAK menular ke bab berikutnya, revisi tertarget ("perdalam 2.1"), proposal, makalah, laporan |
| C. Format artifact & dokumen | 20% | 2.000 | Permintaan dokumen → jawaban dengan `<antArtifact>` sempurna; contoh NEGATIF dikoreksi (fence ```markdown → artifact); update artifact dengan `command="update"` + antOldContent/antNewContent |
| D. Coding | 12% | 1.200 | Python/PHP/JS/SQL umum; jawaban = kode utuh + penjelasan Indonesia; debugging |
| E. Tombol pilihan & klarifikasi | 8% | 800 | Kapan bertanya dulu (dokumen besar, permintaan ambigu) dengan `<antOptions>`; kapan TIDAK bertanya; saran lanjutan pasca-jawaban |
| F. Kejujuran & penolakan wajar | 5% | 500 | "Tidak tahu" yang elegan, koreksi premis salah, menolak mengarang fakta/referensi palsu di luar konteks akademik-simulasi |
| G. Bahasa campur & ketahanan | 5% | 500 | User Indonesia + istilah Inggris, konteks Inggris (abstrak/kutipan) tapi jawaban tetap Indonesia — **obat spesifik penyakit "kebawa Inggris"** |

### 5.3 Sumber seed prompt (pertanyaannya)

1. **Riwayat chat rynude** — ekspor pertanyaan pengguna (100% aman dipakai, jawabannya dibuang). Buat command kecil `rynude:export-seeds` yang menarik semua `messages.role='user'` unik, anonimkan, keluarkan JSONL.
2. **Daftar topik sistematis** — matriks topik × gaya: (skripsi: 30 jurusan × 10 topik), (coding: 8 bahasa × 20 kasus), (umum: 50 domain), digenerate kombinatorik lalu diparafrase model guru supaya natural.
3. **Kasus gagal nyata** — transkrip "ngaco" seperti kasus "quiz tugas" kemarin → jadi seed prioritas dengan jawaban ideal ditulis ulang. Kumpulkan di `dataset/failures/` mulai SEKARANG setiap menemukan jawaban jelek.

### 5.4 Generasi jawaban (distilasi legal)

- **Guru utama:** DeepSeek-V3 API (murah, MIT, kualitas tinggi, bahasa Indonesia bagus) — cadangan: Qwen3-235B-A22B via OpenRouter.
- **Prompt guru** = system prompt produksi rynude + instruksi meta: "jawab sebagai asisten lokal Indonesia; patuhi format artifact; panjang sesuai kebutuhan".
- Untuk kategori B (skripsi per-bab): guru diberi konteks pipeline nyata (ringkasan bab sebelumnya) supaya contoh latih identik dengan kondisi produksi.
- **Reasoning/`<think>`:** Qwen3 punya mode berpikir. Dua opsi: (1) latih TANPA blok think (data jawaban langsung; saat inferensi thinking tetap jalan bawaan template) — **pilihan v1, sederhana**; (2) v2: sertakan reasoning Indonesia dari guru R1 untuk kualitas berpikir. Jangan campur keduanya dalam satu run.

### 5.5 Kontrol kualitas otomatis (rejection sampling)

Setiap jawaban guru lolos HANYA jika lulus semua cek mesin (skrip `dataset/validate.py`):

- ✅ Bahasa: `looksEnglish() == false` untuk seluruh teks non-ABSTRACT/non-kode (pakai porting heuristik yang sudah ada di ChatStreamingService)
- ✅ Format: kalau kategori C/B → artifact tag valid & tertutup, front-matter lengkap, tidak ada ``` fence membungkus dokumen, tidak ada teks setelah `</antArtifact>` (kecuali antOptions)
- ✅ Anti-loop: tidak ada substring 80-char berulang ≥3×
- ✅ Panjang: sesuai amplop per kategori (sapaan ≤ 400 char; bab skripsi ≥ 2.500 char; dst.)
- ✅ Anti-frasa terlarang: "sebagai AI", "saya tidak memiliki kemampuan", "I cannot", "As an AI", "tentu! berikut" berlebihan, dsb. (daftar hitam diperluas dari kasus nyata)
- ✅ antOptions: maksimal 1 tag, ≤ 4 opsi, ≤ 60 char/opsi

### 5.6 Pembersihan PII & keamanan data

- Regex + review: buang/ganti NIM asli, nama lengkap non-publik, email, no. HP, alamat → ganti placeholder konsisten ("Budi Santoso", "12345678").
- Tidak ada kredensial/API key di data (scan dengan pola `sk-`, `Bearer`, dsb.).
- Simpan mentahan di folder privat, JANGAN commit dataset mentah ke repo publik.

### 5.7 Format teknis

- **Format file:** JSONL, satu percakapan per baris: `{"messages":[{"role":"system",...},{"role":"user",...},{"role":"assistant",...}], "source":"deepseek-v3", "category":"B", "license":"generated-mit"}`
- **Template chat saat training:** template Qwen3 resmi (ChatML) — WAJIB sama dengan yang dipakai node-llama-cpp saat inferensi. Salah template = model belajar format yang tidak pernah dipakai produksi (kegagalan senyap paling umum).
- **Loss masking:** hitung loss HANYA pada token assistant (completion-only). Semua framework di Bab 6 mendukung.
- **Split:** 97% train / 3% dev (dev dipakai early-stopping & pemilihan checkpoint; JANGAN pakai soal rynude:eval sebagai data latih — itu ujian, bukan buku pelajaran!).
- **Dedup:** MinHash/embedding-sim > 0.92 → buang duplikat.

---

## 6. Training — Tooling, Hardware, Hyperparameter

### 6.1 Framework (pilih satu)

| Framework | Rekomendasi | Alasan |
|---|---|---|
| **Unsloth** | ⭐ Pilihan utama | 2× lebih cepat, VRAM ½, resep QLoRA Qwen3 siap pakai, notebook Colab tersedia, ekspor merged & GGUF bawaan |
| LLaMA-Factory | Cadangan | GUI lengkap, banyak fitur, lebih berat |
| axolotl | Alternatif tim teknis | Fleksibel, config YAML |

### 6.2 Hardware & biaya

| Model | VRAM minimal (QLoRA 4-bit, seq 4096) | Opsi | Estimasi durasi full run (10rb contoh, 3 epoch) |
|---|---|---|---|
| Qwen3-1.7B | ~8 GB | RTX 3060/4060 lokal, Colab T4 (gratis/Pro), RunPod RTX 4090 (~$0.4–0.7/jam) | 3–6 jam |
| Qwen3-4B | ~12–16 GB | RTX 4090 / A10 / L4 sewa | 6–12 jam |
| Qwen3-8B | ~20–24 GB | A100 40GB / RTX 4090 (seq lebih pendek) | 12–24 jam |

> **Biaya training murni itu KECIL** (puluhan–ratusan ribu rupiah per run). Biaya nyata ada di API guru untuk generasi data: ±25rb request × ~2rb token keluar ≈ 50M token keluar → dengan harga DeepSeek ± $0.3–1.1/juta token ≈ **$15–60 (Rp 250rb–1jt)**. Total proyek realistis: **Rp 1–4 juta** termasuk eksperimen ulang.

### 6.3 Hyperparameter awal (titik mulai yang terbukti; tuning via dev-loss)

```yaml
metode: QLoRA (4-bit nf4, bf16 compute)
lora_r: 32                # kapasitas adaptasi; 16 jika overfit
lora_alpha: 64            # = 2×r
lora_dropout: 0.05
target_modules: [q_proj, k_proj, v_proj, o_proj, gate_proj, up_proj, down_proj]  # semua proyeksi = perilaku+gaya berubah maksimal
learning_rate: 2e-4       # 1e-4 untuk model 4B/8B
scheduler: cosine, warmup_ratio 0.03
epochs: 3                 # pantau dev-loss; berhenti kalau naik (early stop)
batch: efektif 16 (per_device 2 × grad_accum 8)
max_seq_len: 4096         # cukup untuk 1 bab skripsi; contoh super panjang dipotong per-bab (sudah sesuai desain pipeline)
packing: true
loss: completion-only (mask user/system)
seed: 42 (reprodusibilitas)
```

### 6.4 Protokol eksperimen (jangan langsung full run!)

1. **Smoke run:** 500 contoh, 1 epoch → pastikan pipeline jalan, loss turun, sampel output waras.
2. **Run A (resep di atas) vs Run B (r=16, lr=1e-4)** pada data penuh → bandingkan di dev-set + 20 prompt manual.
3. Checkpoint tiap ½ epoch; simpan 3 terbaik; **pilih berdasarkan eval, bukan loss terendah semata**.
4. Catat SEMUA run di `training/RUNS.md` (config, data version, hasil) — tanpa catatan, dua bulan lagi tidak ada yang tahu model ini dibuat dari apa.

---

## 7. Konversi & Integrasi ke Produk rynude

### 7.1 Dari adapter ke file yang diunduh pengguna

```
adapter LoRA (≈50–200 MB)
  → merge ke base (Unsloth: save_pretrained_merged)
  → convert: llama.cpp convert_hf_to_gguf.py
  → kuantisasi: llama-quantize → Q8_0 (Lyric+ 1.7B) / Q4_K_M (Stanza+ 4B)
  → uji beban di node-llama-cpp (scripts/llama-server.mjs) SEBELUM rilis
```

**Checklist uji pasca-konversi (wajib semua ✅):**
- [ ] Model termuat di node-llama-cpp ≥ 3.8 tanpa error
- [ ] Chat template terbaca benar (jawaban tidak mengandung token template mentah)
- [ ] `<think>` masih tersegmentasi ke reasoning_content (panel berpikir jalan)
- [ ] Grammar GBNF (#3) tetap berfungsi
- [ ] Kecepatan token/detik tidak turun > 5% dari base
- [ ] `rynude:eval` dijalankan pada GGUF FINAL (bukan cuma checkpoint HF) — kuantisasi bisa mengubah perilaku

### 7.2 Integrasi Model Hub (kode yang disentuh)

- `ModelHubController::getCatalog()` — tambah entri BARU: id `rynude-lyric-plus-1` (versi di id!), nama "rynude Lyric+", badge "Tuned", ukuran, URL unduhan (hosting: Hugging Face repo privat/publik milik sendiri atau CDN sendiri untuk produk berbayar)
- `LlamaServerService`: CATALOG (filename), TIERS (Lyric+ → tetap 'small' tapi bisa dipromosikan setelah eval), CONTEXT_SIZES, GEN_PROFILES (model tuned biasanya butuh repeat-penalty LEBIH RENDAH — uji ulang sampling!)
- `AiModelSeeder` + migrasi kecil untuk row model baru
- **JANGAN menimpa model lama** — pengguna harus bisa kembali (rollback = fitur, bukan kekalahan)

---

## 8. Evaluasi — Gerbang Kualitas Sebelum Rilis

### 8.1 Otomatis (wajib lulus)

1. `php artisan rynude:eval <model-baru>` — bandingkan dengan baseline yang dibekukan di Fase 0 (target Bab 3.3)
2. **Suite bahasa khusus** (tambahkan 10–20 kasus ke harness, sekali saja, SEBELUM training dimulai — supaya adil): sapaan, pertanyaan pendek, konteks berisi teks Inggris → jawaban wajib Indonesia
3. **Suite regresi:** semua soal baseline yang sudah benar tidak boleh jadi salah

### 8.2 Uji integrasi produk (manual, 1 hari)

- Skripsi full lewat pipeline per-bab (bahasa, kedalaman, laporan per tahap)
- Makalah single-shot + grammar
- 20 chat bebas campuran (sapaan/tugas/coding/klarifikasi)
- Ekspor PDF/DOCX dari artifact hasil model baru

### 8.3 Human eval (rubrik 1–5, minimal 2 penilai, 30 prompt tetap)

| Aspek | 1 (gagal) | 5 (sempurna) |
|---|---|---|
| Bahasa | Inggris/campur kacau | Indonesia natural konsisten |
| Kebenaran isi | Halu/ngelantur | Akurat & relevan |
| Format | Artifact rusak | Artifact & markdown sempurna |
| Gaya | Pidato/interogasi | Pas takarannya |
| Kedalaman (akademik) | Outline dangkal | Prosa substantif |

### 8.4 Diagnosis kegagalan umum → tindakan

| Gejala | Penyebab lazim | Obat |
|---|---|---|
| Bagus di eval, kaku di chat bebas | Data kurang variasi percakapan | Tambah kategori A, perbanyak multi-turn |
| Format artifact sempurna tapi isi memburuk | Overfit format (porsi C terlalu besar / epoch kebanyakan) | Turunkan porsi C, epoch 2, r=16 |
| Lupa kemampuan umum (math/logika turun) | Catastrophic forgetting | Campur 10–15% data umum berkualitas (Aya/OASST-ID), turunkan lr |
| Masih bocor Inggris | Kategori G kurang / contoh abstrak→bab kurang | Perbanyak G dengan kasus persis seperti bug produksi |
| Jawaban pendek semua | Contoh latih kependekan | Perbaiki amplop panjang di validator |

---

## 9. Paket "Siap Jual" (Komersialisasi)

### 9.1 Produk & positioning

| Paket | Isi | Model harga (contoh) |
|---|---|---|
| **Gratis** | rynude generik (Qwen3 vanilla) — seperti sekarang | Rp 0 (akuisisi pengguna) |
| **Pro** | Lyric+ / Stanza+ (tuned): skripsi & Indonesia jauh lebih baik | Lisensi sekali beli / langganan |
| **Kampus/Institusi** | Stanza+/Canto+ + dukungan + kustomisasi template kampus | B2B per-kursi |

### 9.2 Kewajiban legal produk

- [ ] Sertakan `LICENSE-THIRD-PARTY.md`: Apache 2.0 (Qwen), MIT (llama.cpp, node-llama-cpp, DeepSeek jika dipakai gurunya), lisensi dataset publik
- [ ] EULA produk sendiri: model dijual sebagai bagian aplikasi; larang redistribusi file .gguf terpisah (catatan jujur: Apache 2.0 basis-nya tidak bisa melarang orang menyebarkan turunannya secara hukum lisensi — perlindungan Anda adalah branding, layanan, update, dan integrasi, bukan kerahasiaan bobot)
- [ ] Disclaimer akademik: "alat bantu penulisan; pengguna bertanggung jawab atas integritas akademik" (penting untuk produk skripsi!)
- [ ] Privasi: pernyataan bahwa data latih dibersihkan dari PII; chat pengguna tidak dipakai melatih tanpa persetujuan

### 9.3 Distribusi & operasional

- Hosting bobot: HF repo (publik utk gratis, gated/CDN token utk Pro), URL masuk `download_url` katalog
- Versioning: `rynude-lyric-plus-1`, `-2`, dst. + `CHANGELOG-MODELS.md`
- Telemetri opt-in sederhana (skor kepuasan per jawaban 👍👎 sudah ada di UI — `messages.rating`!) → sumber data v2
- Siklus rilis: kumpulkan kegagalan → dataset v1.1 → retrain kuartalan

---

## 10. Risiko & Mitigasi (di luar yang sudah dibahas)

| Risiko | Kemungkinan | Mitigasi |
|---|---|---|
| Data guru ternyata berkualitas rendah utk topik skripsi ID | Sedang | Pilot 100 contoh dinilai manusia SEBELUM generate 25rb |
| Konversi GGUF mengubah perilaku | Sedang | Eval dijalankan pada GGUF final (7.1) |
| Scope creep (mau melatih semua kemampuan sekaligus) | Tinggi | Kunci 7 kemampuan Bab 3.2; lainnya = v2 |
| Waktu kurasi manusia membengkak | Tinggi | Kurasi penuh hanya subset emas (10%); sisanya cukup validator mesin |
| Base model baru rilis di tengah proyek (Qwen4?) | Rendah | Resep & dataset portable — aset sejati proyek ini adalah DATA + HARNESS, bukan checkpoint |

---

## 11. Timeline & Anggaran Ringkas

| Minggu | Kegiatan | Keluaran | Biaya |
|---|---|---|---|
| 0 | Bekukan baseline eval; setup repo dataset; tambah suite bahasa ke harness; mulai kumpulkan `failures/` | `BASELINE.md`, struktur repo | Rp 0 |
| 1–2 | Seed prompts (ekspor riwayat + matriks topik); pilot 100 contoh guru → review manusia; finalisasi validator | `seeds.jsonl`, `validate.py`, keputusan guru | ± Rp 100rb API |
| 2–3 | Generate 25rb → validasi → kurasi → dataset v1 (10rb) | `dataset-v1/train.jsonl` + manifest lisensi | ± Rp 300rb–1jt API |
| 4 | Smoke run + Run A/B QLoRA Lyric+; pilih checkpoint | adapter + `RUNS.md` | ± Rp 150–500rb GPU |
| 5 | Eval penuh (otomatis+manual+human); jika gagal → iterasi data | Laporan eval | ± Rp 100rb |
| 6 | Merge→GGUF→uji integrasi→Model Hub→beta | `rynude-lyric-plus-1.gguf` rilis beta | Rp 0 |
| 7+ | (P2) Ulangi minggu 4–6 untuk Stanza+ 4B | Stanza+ | ± Rp 300rb–1jt |

**Total P1 (Lyric+): ± Rp 1–2 juta · P1+P2: ± Rp 2–4 juta · Waktu: 5–7 minggu (paruh waktu)**

---

## 12. Checklist Go/No-Go Rilis (final gate)

- [ ] Skor eval ≥ target Bab 3.3 pada **file GGUF final**
- [ ] 0 kebocoran bahasa pada suite bahasa tanpa bantuan satpam sistem
- [ ] Human eval ≥ 4.0, tanpa nilai 1
- [ ] Tidak ada regresi > 1 soal
- [ ] Uji integrasi produk lulus semua (7.1 + 8.2)
- [ ] Manifest lisensi data lengkap & bersih dari sumber terlarang (Bab 2)
- [ ] `LICENSE-THIRD-PARTY.md` + EULA + disclaimer akademik terpasang
- [ ] Model lama tetap tersedia (rollback path)
- [ ] `CHANGELOG-MODELS.md` terisi

---

## 13. Langkah Pertama Konkret (bisa mulai minggu ini, tanpa GPU)

1. **Jalankan & simpan baseline:** `php artisan rynude:eval qwen-2.5-1.5b` → simpan JSON-nya sebagai patokan abadi.
2. **Buat folder `dataset/failures/`** — setiap ketemu jawaban ngaco (seperti kasus "quiz tugas"), simpan transkripnya + tulis jawaban ideal versi Anda. Ini bahan emas kategori tersulit.
3. **Buat akun & API key DeepSeek** (guru legal, murah) — uji 10 prompt skripsi Indonesia, nilai sendiri kualitasnya.
4. **Ekspor seed:** minta saya buatkan command `rynude:export-seeds` (menarik pertanyaan pengguna dari DB, dianonimkan, JSONL) — 30 menit kerja.
5. **Tambah suite bahasa ke eval harness** (10–20 soal) — minta saya kerjakan kapan saja; wajib ada SEBELUM training supaya baseline adil.

> Kalau kelima langkah ini selesai, proyek LoRA tinggal "mengalir" mengikuti Bab 4. Dan sekali lagi prinsipnya: **kualitas data adalah produk; training hanyalah kompilasi.**

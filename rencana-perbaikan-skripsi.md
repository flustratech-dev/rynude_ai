# Rencana Implementasi — Perbaikan Pipeline Skripsi (rynude Stanza 4.6)

> **Status:** RENCANA (belum diimplementasi). Dokumen ini adalah spesifikasi kerja.
> **Dibuat:** 2026-07-13 · **Model uji:** `rynude-stanza-plus-1` (rynude Stanza 4.6)
> **Berkas utama:** [`app/Services/ChatStreamingService.php`](app/Services/ChatStreamingService.php)
> **Renderer (khusus ⑦):** [`app/Services/PdfRenderer.php`](app/Services/PdfRenderer.php), [`app/Services/DocxRenderer.php`](app/Services/DocxRenderer.php), [`app/Services/Concerns/BuildsDocumentContent.php`](app/Services/Concerns/BuildsDocumentContent.php)

---

## 0. Konteks & Prinsip

Feedback uji live Stanza 4.6 mengungkap **bug orkestrasi/prompt pada pipeline skripsi**, BUKAN kelemahan bobot model. Karena itu:

- **Tidak perlu retraining.** Semua perbaikan bersifat kode (`ChatStreamingService` + renderer) dan otomatis berlaku untuk **semua model lokal** (Lyric & Stanza).
- **Wajib uji LIVE.** `php artisan rynude:eval` **melewati** pipeline (menguji model mentah), jadi tidak bisa memverifikasi perbaikan ini. Setiap patch diuji di **New Chat** memakai model **rynude Stanza 4.6** dengan engine GGUF hidup di port `8091`.
- **Aman terputus.** Tiap patch berdiri sendiri, bisa di-commit & diuji terpisah, dan bisa di-rollback tanpa memengaruhi patch lain.

### Keputusan pengguna yang sudah final
| Topik | Keputusan |
| :-- | :-- |
| Upload dokumen + "lanjutkan" | **Full**: keluarkan dokumen utuh = isi upload **verbatim** + bab lanjutan (1 artifact siap ekspor) |
| Default "lanjutkan" (upload & buatan app) | **Satu bab berikutnya** saja, lalu tawarkan lanjut lagi |

### Keputusan yang MASIH menggantung (dibutuhkan sebelum Fase 3 & 6)
1. **Panjang sub-bab (⑨):** `moderat` (aman, +sedikit waktu) **atau** `maksimal` (paling tebal, jauh lebih lama di GPU).
2. **Diagram ekspor (⑦):** **Opsi B** (mermaid di web + fallback teks untuk PDF/DOCX — *rekomendasi*) **atau** **Opsi A** (render mermaid→gambar di PdfRenderer/DocxRenderer).

---

## 1. Ringkasan Masalah → Patch

| # | Masalah (dari uji live) | Fase | Patch | Risiko |
| :-: | :-- | :-: | :-: | :-: |
| ① | "berikan saya judul skripsi" malah membuat dokumen kerangka kosong | 1 | Patch 2 | Rendah |
| ②③④ | Judul hasil BEDA dari judul yang diberikan user | 1 | Patch 1 | Rendah |
| ⑤a | Heading "BAB I / judul bab" ditulis ulang (duplikat) | 2 | Patch 3 | Rendah |
| ⑤b | BAB III 3.1 & BAB IV 4.1 kosong total | 2 | Patch 4 | Sedang |
| ⑧ | Batasan Masalah 1.4 harus paragraf, maks 4 paragraf | 3 | Patch 5 | Rendah |
| ⑨ | Isi sub-bab wajib sangat panjang | 3 | Patch 5 | Sedang |
| ⑥ | Tabel wajib & banyak (sekarang hanya 1) | 3 | Patch 6 | Rendah |
| ⑩ | Bedakan "1 bab saja / tidak full" vs full | 4 | Patch 7 | Sedang |
| ⑪ | Upload dokumen + "lanjutkan" tidak menjamin dokumen full | 5 | Patch 8 | Sedang-Tinggi |
| ⑦ | Diagram/flowchart wajib & lebih dari satu (sekarang 0) | 6 | Patch 9 | Sedang/Tinggi |

**Urutan eksekusi:** Fase 1 → 2 → 3 → 4 → 5 → 6. Fase 1–3 saling bebas; Fase 4–5 menyentuh routing (uji hati-hati); Fase 6 paling akhir.

---

## FASE 1 — Judul & Routing *(dampak tertinggi, risiko rendah)*

### Patch 1 — Kunci judul user verbatim (②③④)

**Akar masalah.** [`collectSkripsiMeta()`](app/Services/ChatStreamingService.php:2023) memanggil model dengan grammar + prompt _"Judul harus berupa judul skripsi akademik yang baik dan spesifik"_, lalu output model **menimpa** judul user ([baris 2055–2063](app/Services/ChatStreamingService.php:2055)). Judul asli hilang → dokumen memakai judul karangan model.

**Rencana teknis.**
1. Tambah helper `detectUserProvidedTitle(string $request): ?string` yang menangkap judul eksplisit dengan urutan prioritas:
   - Pola berlabel: `/\bjudul(?:\s*(?:skripsi|nya))?\s*[:\-]\s*["“]?(.+?)["”]?$/mi`
   - Pola "dari judul ini …": `/\bdari\s+judul\s+(?:ini|berikut)\b[:\-]?\s*["“]?(.+?)["”]?(?:\s+(?:buatkan|buat|susun|tolong)|$)/i`
   - Judul dalam tanda kutip: `/["“](.{15,140}?)["”]/`
   - Fallback frasa judul: hasil `preg_replace` verb (seperti `$judulFallback` yang sudah ada) **hanya bila** panjang wajar (15–140 char) dan mengandung kata benda judul (mis. "Sistem/Aplikasi/Pengembangan/Analisis/Rancang Bangun/Perancangan/Implementasi").
   - Return `null` bila tidak ada indikasi judul eksplisit (user hanya memberi topik).
2. Di `collectSkripsiMeta()`:
   - Panggil `detectUserProvidedTitle($request)` di awal.
   - Jika **ada** → set `$defaults['judul']` = judul itu (setelah normalisasi kutip/newline), dan **JANGAN** timpa `judul` dari output model (tetap ambil field lain: penulis, nim, dst. dari model). Praktisnya: setelah loop parsing YAML, kalau judul user ada, kembalikan judul user, bukan `$m[1]` untuk kunci `judul`.
   - Jika **null** → perilaku lama (model mengarang judul).
3. Di clarify ([baris 1808–1817](app/Services/ChatStreamingService.php:1808)) `needsSkripsiClarification`/pesan clarify: bila judul user terdeteksi, tampilkan `"Judul yang akan saya pakai: _<judul>_ (tidak akan saya ubah)."` sebelum pertanyaan metode — memberi keyakinan ke user.

**Titik uji (live).**
- Input: `Buatkan skripsi. Judul: "Pengembangan Sistem Augmented Reality untuk Media Pembelajaran Anatomi Tubuh Manusia Berbasis Android". Metode R&D.`
- Cek: atribut `title` pada `<antArtifact>` dan baris `judul:` di YAML front-matter **persis** = judul input.
- Regres: input tanpa judul (`buatkan skripsi tentang AR anatomi`) → judul tetap dibuat model (tidak error).

**Risiko:** rendah. **Berkas:** `ChatStreamingService.php`.

---

### Patch 2 — Guard "minta saran judul" → chat (①)

**Akar masalah.** Deteksi dokumen (`$isGgufDocRequest`) menyala karena kata benda "skripsi", tapi [`isSkripsiPipelineRequest()`](app/Services/ChatStreamingService.php:1207) butuh verb membuat ("berikan" tidak termasuk) → `useChapterPipeline=false` → jatuh ke **jalur single-shot** `docArtifactGrammar` → keluar kerangka cover + BAB kosong.

**Rencana teknis.**
1. Helper `isTitleSuggestionRequest(string $text): bool`:
   - `true` bila teks mengandung sinyal saran (`/\b(saran|sarankan|rekomendasi|rekomendasikan|ide|contoh|usul(?:kan)?|beri(?:kan)?|kasih|minta)\b/i`) **berdekatan dengan** kata `judul` (`/\bjudul\b/i`), dan **tidak** mengandung verb menyusun dokumen (`buatkan skripsi`, `susun`, `tulis(kan)`, `outline`, dst.).
2. Guard di routing dekat [`isDocumentQuestion` (baris 264–268)](app/Services/ChatStreamingService.php:264):
   ```
   if ($isGgufDocRequest && !$useChapterPipeline && !$isRevisionTurn
       && !$isUploadContinuation && !$isSkripsiContinuation
       && $this->isTitleSuggestionRequest($latestUserText)) {
       $isGgufDocRequest = false; // → jawab di chat (daftar saran judul)
   }
   ```
3. (Opsional, kualitas) Sisipkan reminder ringan agar model menjawab dengan 5–8 opsi judul dalam daftar bernomor di chat.

**Titik uji (live).**
- Input: `Kasih 5 saran judul skripsi tentang AR anatomi tubuh manusia`.
- Cek: dijawab **di chat** sebagai daftar judul; **tidak** ada `<antArtifact>`, tidak ada cover/BAB.
- Regres: `Buatkan skripsi tentang AR…` tetap membuat dokumen (guard tidak salah tangkap).

**Risiko:** rendah. **Berkas:** `ChatStreamingService.php`.

---

## FASE 2 — Isi tidak kosong & heading tunggal

### Patch 3 — Heading bab tunggal-kanonik (⑤a)

**Akar masalah.** Renderer ([`splitAcademicBody`](app/Services/Concerns/BuildsDocumentContent.php:263)) sudah bersih — duplikasi berasal dari **output model**. Dedup yang ada ([`trimToChapterScope` baris 2405–2411](app/Services/ChatStreamingService.php:2405)) hanya menangkap `# BAB…` yang **persis sama**, bukan varian (`**BAB I PENDAHULUAN**`, `## BAB I`, `# BAB I` tanpa "PENDAHULUAN").

**Rencana teknis.**
1. Di `cleanChapterText()` (atau helper baru `enforceSingleChapterHeading($text, $heading)` yang dipanggil di akhir `generateChapterBody` sebelum `return`):
   - Normalisasi: buang **semua** baris yang merupakan judul-bab dalam bentuk apa pun untuk bab ini:
     - `^#{1,3}\s*BAB\s+<romawi/angka>\b.*$` (segala level heading)
     - `^\*\*\s*BAB\s+<...>\b.*\*\*\s*$` (bold text)
     - baris polos berisi hanya "BAB N JUDUL" tanpa markup.
   - Setelah dibersihkan, **prepend tepat satu** `# {$heading}` di paling atas.
   - Pertahankan sub-bab (`## N.M`) apa adanya (sudah benar menurut user).
2. Pastikan tidak memotong isi: hanya baris judul-bab yang dihapus, bukan paragraf.

**Titik uji (live).** Generate BAB I → hitung kemunculan pola judul BAB I di markdown = **tepat 1** (`grep -c "BAB I PENDAHULUAN"`), sub-bab utuh.

**Risiko:** rendah. **Berkas:** `ChatStreamingService.php`.

---

### Patch 4 — Fill sub-bab per-item + retry, tanpa heading telanjang (⑤b)

**Akar masalah.** [`completeChapterSubbabs()`](app/Services/ChatStreamingService.php:2189) mengisi sub-bab kosong dalam **satu panggilan borongan**; bila model gagal untuk salah satu sub-bab, perakitan `$bodies[$num] ?? "## {$num} {$label}"` ([baris 2248](app/Services/ChatStreamingService.php:2248)) **menyisakan heading telanjang**. Juga bila isi ada di varian heading (`### 3.1`, `3.1` tanpa `##`), parser meleset → dianggap kosong.

**Rencana teknis.**
1. **Perlebar deteksi isi.** Saat memetakan `$bodies`, normalisasi heading sub-bab: terima `##`, `###`, dan baris `N.M Judul` polos → petakan ke nomor `N.M` yang sama sebelum menghitung kosong.
2. **Fill per-sub-bab dengan retry.** Ganti fill borongan menjadi loop per `$num` yang masih kosong: hingga 2 percobaan, prompt fokus HANYA pada 1 sub-bab (lebih patuh daripada minta banyak sekaligus). Kumpulkan hasil ke `$bodies[$num]`.
3. **Larangan heading telanjang.** Setelah semua percobaan, bila `$bodies[$num]` masih < ambang (mis. 120 char), isi dengan **paragraf terjamin** minimal (mis. 1 panggilan sederhana "tulis 2 paragraf tentang <sub-bab> untuk judul <judul>") — kalau tetap gagal, tulis kalimat penutup yang informatif, **bukan** heading kosong. Target: dokumen final tidak pernah punya `## N.M` tanpa isi.
4. Ambang "kosong" dinaikkan dari 80 → 120 char agar sub-bab satu-kalimat tetap dianggap perlu diisi.

**Titik uji (live).**
- Generate BAB III → 3.1–3.5 semua punya ≥ 2 paragraf; **tidak ada** `## 3.x` tanpa isi.
- Generate BAB IV → 4.1 Gambaran Umum berisi.
- `grep -nE '^##\s+[0-9]+\.[0-9]+\s*$'` pada markapun = kosong (tidak ada heading telanjang).

**Risiko:** sedang (logika perakitan & jumlah panggilan model naik → sedikit lebih lama). **Berkas:** `ChatStreamingService.php`.

---

## FASE 3 — Kelengkapan Akademik

### Patch 5 — Format Batasan Masalah 1.4 + panjang sub-bab (⑧⑨)

**Akar masalah.** [`skripsiChapterPlan()`](app/Services/ChatStreamingService.php:1642): panduan BAB I generik "minimal 3 paragraf"; tidak ada aturan bentuk untuk 1.4; `maxTokensPerChapter` = 8192 (large) / 6144 (small) ([baris 1871](app/Services/ChatStreamingService.php:1871)).

**Rencana teknis.**
1. **1.4 Batasan Masalah (⑧):** ubah panduan BAB I → `"## 1.4 Batasan Masalah — WAJIB bentuk paragraf mengalir (BUKAN daftar/bullet/poin), 2–4 paragraf."` Tambah aturan eksplisit di prompt bab bahwa 1.4 tidak boleh berupa list.
2. **Panjang sub-bab (⑨):** naikkan target di guide (mis. "setiap sub-bab minimal 4–6 paragraf akademik yang tebal & spesifik") + naikkan `maxTokensPerChapter`.
   - **Menunggu keputusan:** `moderat` (mis. large 10240) atau `maksimal` (mis. large 12288 + BAB IV ditulis bertahap per sub-bab). Makin besar = makin lama & makin dekat batas KV-cache 32k (aman selama ≤ ~12k token/bab).
3. (Opsional) Validasi ringan: bila sebuah bab < ambang panjang, picu 1 ronde perdalam.

**Titik uji (live).**
- 1.4 berbentuk paragraf (tidak ada `-`/`*`/`1.` sebagai butir), jumlah paragraf ≤ 4.
- Panjang sub-bab lain meningkat vs baseline (hitung kata rata-rata per sub-bab).

**Risiko:** sedang (kecepatan). **Berkas:** `ChatStreamingService.php`. **Butuh keputusan panjang.**

---

### Patch 6 — Tabel wajib per bab (⑥)

**Akar masalah.** Hanya BAB IV yang menyebut tabel, itu pun opsional ("gunakan tabel Markdown bila cocok", [baris 1654](app/Services/ChatStreamingService.php:1654)).

**Rencana teknis.**
1. Tambah kewajiban tabel di `skripsiChapterPlan()`:
   - **BAB II:** tabel **perbandingan penelitian terdahulu** (kolom: Penulis/Tahun, Judul, Metode, Hasil, Perbedaan) + boleh tabel definisi/istilah.
   - **BAB III:** tabel **populasi/sampel** dan **kisi-kisi instrumen** (variabel, indikator, item).
   - **BAB IV:** tabel **hasil** (data temuan).
   - Kalimat guide: "WAJIB menyertakan minimal 2 tabel Markdown pada bab ini, format `| … | … |`."
2. (Opsional, penguat) Validasi: bila bab yang mewajibkan tabel tidak mengandung `|---|`, picu 1 ronde tambahan "sisipkan tabel yang relevan".

**Titik uji (live).** BAB II/III/IV masing-masing memuat ≥ 2 tabel Markdown (`grep -c '|'` per bab, atau cek visual di preview artifact).

**Risiko:** rendah. **Berkas:** `ChatStreamingService.php`.

---

## FASE 4 — Scoping "1 bab / tidak full" (⑩)

### Patch 7 — Bedakan "sampai bab N" vs "bab N saja" + tangkap "bab N" polos

**Akar masalah.**
- `array_slice(chapters, 0, babLimit+1)` ([baris 1866](app/Services/ChatStreamingService.php:1866)) selalu **kumulatif** dari BAB 1 → "hanya bab 3" tetap menghasilkan BAB 1–3.
- Regex scoped ([baris 161](app/Services/ChatStreamingService.php:161)) mensyaratkan kata "saja/sampai/dulu/cuma/hanya/khusus"; **"buatkan bab 1 skripsi"** polos → tidak scoped → **jadi FULL BAB I–V**.
- Single-bab non-1 ("BAB III saja") tak punya jalur bersih.

**Rencana teknis.**
1. Bedakan **dua mode** dari teks:
   - **Kumulatif** ("sampai bab N", "s/d bab N", "hingga bab N") → seperti sekarang: Pengesahan + BAB 1..N.
   - **Tunggal** ("bab N saja", "hanya bab N", "cuma bab N", "khusus bab N", atau "bab N" polos + sinyal "tidak full/sebagian/satu bab") → **hanya** BAB N (tanpa cover & bab lain, kecuali user minta cover). Ambil judul dari: dokumen aktif di room → turn sebelumnya → metadata.
2. Tambah flag `$singleBab` (bool) + `$babTarget` (int). Saat `$singleBab`:
   - `$chapters = [ plan[babTarget] ]` (hanya satu), lewati stage cover kecuali diminta.
   - Header progress & debrief menyesuaikan ("Saya menulis **BAB N** saja sesuai permintaan…").
3. Tangkap "bab N" polos: bila teks menyebut satu BAB spesifik + verb buat + skripsi, TANPA "sampai" → default ke **tunggal** (bukan full). Bila menyebut "full/lengkap/semua bab" → full.
4. Perbaiki gate: pastikan permintaan single/scoped tetap butuh konteks skripsi (hindari salah picu pada chat biasa).

**Titik uji (live).**
- `buatkan bab 1 skripsi judul "…"` → hanya cover + BAB I (atau BAB I saja) — **bukan** BAB I–V.
- `bab 3 saja` (dengan judul di turn sebelumnya) → **hanya** BAB III, nyambung ke judul.
- `sampai bab 3` → BAB 1–3 (kumulatif, tetap benar).
- Regres: `buatkan skripsi lengkap` → full I–V + Daftar Pustaka.

**Risiko:** sedang (routing sensitif). **Berkas:** `ChatStreamingService.php`.

---

## FASE 5 — Upload continuation deterministik (⑪)

### Patch 8 — Upload dokumen + "lanjutkan" → dokumen full (asli verbatim + 1 bab)

**Akar masalah.** Dua jalur berbeda:
- **Buatan app** + "lanjutkan BAB N" → [`streamSkripsiContinuation()`](app/Services/ChatStreamingService.php:1690): **deterministik** — isi lama dimuat persis + bab baru ditempel. **Benar.**
- **Upload** (PDF/DOCX) + "lanjutkan" → [`isUploadContinuation`](app/Services/ChatStreamingService.php:251) → jalur **single-shot** `docArtifactGrammar` + [`docUploadContinuationReminder`](app/Services/ChatStreamingService.php:282). Model harus meng-handle sendiri → **tidak menjamin**: bisa hanya potongan, regenerate berbeda, atau terpotong; isi asli tidak dijamin utuh.

**Keputusan pengguna:** hasil = **full (asli verbatim + lanjutan)**; default lanjut = **satu bab berikutnya**.

**Rencana teknis.**
1. Deteksi "upload skripsi + niat lanjut": `isUploadContinuation` **dan** dokumen upload terlihat sebagai skripsi (`mode: skripsi` atau ada heading `# BAB`) **dan** verb lanjut.
2. Buat jalur baru `streamUploadSkripsiContinuation()` (meniru `streamSkripsiContinuation`):
   - **Ekstrak teks dokumen upload** (pakai layanan ekstraksi/RAG yang sudah ada — lihat [`document-rag`]; sumber konten sama seperti yang dipakai untuk RAG lampiran).
   - Jadikan teks itu **basis verbatim** (`$existing`), re-emit di dalam `<antArtifact>` apa adanya.
   - `highestBabInDocument($existing)` → tentukan BAB berikutnya (`$from = $to = highest + 1`, sesuai keputusan "satu bab").
   - Generate BAB berikutnya via `generateChapterBody()` (rolling summary dari outline dokumen upload), tempel, tutup artifact.
   - Debrief + tombol "lanjutkan BAB N+2".
3. Routing: bila kondisi (1) terpenuhi, arahkan ke jalur baru ini **alih-alih** single-shot. Bila upload BUKAN skripsi (mis. makalah) → tetap jalur lama.
4. **Catatan akurasi:** karena isi asli dari file (bukan artifact app), pertahankan **verbatim** (jangan minta model menulis ulang) agar tidak berubah.

**Titik uji (live).**
- Upload skripsi berisi BAB I–II → ketik "lanjutkan" → artifact = **isi upload persis** + **BAB III** (satu dokumen), sampul & BAB I–II tidak berubah.
- Ketik "lanjutkan" lagi → menambah BAB IV, dst.
- Regres: upload makalah biasa + "lanjutkan" → tetap jalur dokumen umum (tidak dipaksa jadi skripsi).

**Risiko:** sedang–tinggi (ekstraksi teks + penyatuan). **Berkas:** `ChatStreamingService.php` (+ pakai layanan ekstraksi dokumen yang sudah ada).

---

## FASE 6 — Diagram wajib (⑦) *(paling berat)*

### Patch 9 — Diagram mermaid wajib per bab

**Akar masalah.** Tidak ada instruksi diagram sama sekali di pipeline. Panggilan per-bab TIDAK memakai grammar (`streamRawChapter` polos), jadi blok ```mermaid``` aman (tidak kena larangan `</`).

**Rencana teknis.**
1. Tambah kewajiban diagram di `skripsiChapterPlan()`:
   - **BAB II:** ```mermaid``` **Kerangka Pemikiran** (flowchart alur variabel).
   - **BAB III:** ```mermaid``` **alur/prosedur penelitian**; untuk topik sistem (mis. AR) tambah **arsitektur sistem / use-case / ERD**.
   - **BAB IV:** diagram pendukung hasil bila relevan.
   - Kalimat guide: "WAJIB menyertakan minimal 1 diagram dalam blok ```mermaid``` (mis. `flowchart TD`) pada bab ini."
2. **Pilihan ekspor (BUTUH KEPUTUSAN):**
   - **Opsi B (rekomendasi, risiko rendah):** mermaid tampil di **preview artifact web**; untuk PDF/DOCX, sisipkan **fallback**: keterangan gambar bernomor ("**Gambar 3.1** Alur Penelitian") + (opsional) tabel/uraian langkah, sehingga ekspor tetap informatif walau diagram tidak ter-render sebagai gambar.
   - **Opsi A (risiko tinggi):** tambah render **mermaid → SVG/PNG** di `PdfRenderer` & `DocxRenderer` (butuh dependency, mis. `mermaid-cli`/Kroki, plus penanganan biner/HTTP). Ekspor ikut rapi, tapi menambah dependency & titik gagal.
3. (Bila Opsi B) tambah util kecil untuk memastikan tiap diagram punya caption "Gambar X.Y".

**Titik uji (live).**
- Preview artifact: diagram mermaid ter-render (flowchart terlihat), > 0 diagram, tersebar di BAB II/III.
- (Opsi A) Ekspor PDF/DOCX: diagram muncul sebagai gambar. (Opsi B) Ekspor memuat caption "Gambar X.Y" + fallback.

**Risiko:** sedang (Opsi B) / tinggi (Opsi A). **Berkas:** `ChatStreamingService.php` (+ `PdfRenderer.php`/`DocxRenderer.php` bila Opsi A). **Butuh keputusan A/B.**

---

## 2. Ringkasan Urutan & Ketergantungan

```
FASE 1  Patch 1 (judul)  ─┐  independen, aman, dampak besar → kerjakan lebih dulu
        Patch 2 (saran)  ─┘
FASE 2  Patch 3 (heading)─┐  independen, kualitas dokumen
        Patch 4 (subbab) ─┘
FASE 3  Patch 5 (1.4+panjang) — butuh keputusan PANJANG
        Patch 6 (tabel)
FASE 4  Patch 7 (scoping 1 bab) — routing sensitif, uji regresi penuh
FASE 5  Patch 8 (upload lanjut) — ekstraksi + penyatuan deterministik
FASE 6  Patch 9 (diagram) — butuh keputusan A/B; paling akhir
```

## 3. Protokol Uji per Patch
1. Pastikan engine hidup: `curl -s http://127.0.0.1:8091/health` → `{"ok":true,"model":"rynude-stanza-plus-1",...}`.
2. `php artisan optimize:clear` setelah setiap perubahan kode.
3. Uji di **New Chat** (model rynude Stanza 4.6) dengan skenario "Titik uji (live)" tiap patch.
4. Regresi cepat: satu prompt full skripsi + satu prompt scoped + satu upload lanjut, pastikan tidak ada yang rusak.

## 4. Rollback
Tiap patch = commit terpisah dengan pesan `fix(skripsi): <ringkas>`. Bila satu patch bermasalah, `git revert <commit>` tanpa memengaruhi patch lain. Perbaikan kode ini bersifat pipeline; tidak menyentuh file `.gguf` maupun bobot model.

## 5. Catatan Penting
- Semua perbaikan **model-agnostic** → otomatis berlaku juga untuk seri Lyric.
- **Jangan** memakai `rynude:eval` untuk memverifikasi patch ini (melewati pipeline).
- Dua keputusan tertunda (**panjang ⑨**, **diagram A/B ⑦**) harus ditetapkan sebelum Patch 5 & Patch 9.

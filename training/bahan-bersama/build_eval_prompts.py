#!/usr/bin/env python3
"""
build_eval_prompts.py — Generate a large, diverse capability-evaluation prompt
set for rynude Lyric 4.6 (and future canto/stanza).

Goal: map "what can the trained model already do, and where does it still need
training?" Produces 1000-5000 Indonesian prompts spread across the real product
use-cases AND the weak spots observed in live testing (document artifacts,
refusal-avoidance, front-matter, chapter continuation, diagrams, grounding).

Output: training/eval_prompts.jsonl — one JSON object per line:
  {"id": "...", "category": "...", "subcategory": "...", "prompt": "...",
   "expect": "artifact|chat|diagram|refuse_never|...", "notes": "..."}

The `expect` field is a lightweight rubric hint so a grader (human or the
rynude:eval harness) knows what a good answer looks like.

Usage:
  python training/build_eval_prompts.py --target 2500 --out training/eval_prompts.jsonl
"""

import argparse
import itertools
import json
import random
import hashlib

random.seed(42)

# ---------------------------------------------------------------------------
# Topic pools (reused across categories for variety)
# ---------------------------------------------------------------------------
SKRIPSI_TOPICS = [
    "penerapan AI untuk deteksi penyakit tanaman",
    "sistem informasi akademik berbasis web",
    "analisis sentimen ulasan produk menggunakan machine learning",
    "sistem rekomendasi film dengan collaborative filtering",
    "prediksi harga saham menggunakan LSTM",
    "chatbot layanan pelanggan berbasis NLP",
    "sistem monitoring kualitas udara berbasis IoT",
    "deteksi wajah untuk absensi mahasiswa",
    "optimasi rute pengiriman dengan algoritma genetika",
    "sistem informasi geografis pemetaan bencana",
    "klasifikasi sampah menggunakan CNN",
    "e-voting berbasis blockchain",
    "sistem pakar diagnosa penyakit ternak",
    "analisis big data penjualan retail",
    "keamanan jaringan menggunakan intrusion detection system",
    "aplikasi mobile edukasi anak berbasis gamifikasi",
    "sistem pendukung keputusan pemilihan beasiswa",
    "prediksi kelulusan mahasiswa dengan data mining",
    "smart farming berbasis sensor kelembapan",
    "deteksi hoaks berita menggunakan deep learning",
]
MAKALAH_TOPICS = [
    "dampak media sosial pada remaja",
    "bahaya sampah plastik bagi lingkungan",
    "pentingnya literasi digital di era modern",
    "peran teknologi dalam pendidikan",
    "perubahan iklim dan mitigasinya",
    "etika penggunaan kecerdasan buatan",
    "kesehatan mental generasi muda",
    "energi terbarukan di Indonesia",
    "budaya lokal di tengah globalisasi",
    "keamanan data pribadi di internet",
]
CODING_TASKS = [
    ("Python", "fungsi untuk mengecek apakah sebuah angka prima"),
    ("Python", "program menghitung faktorial secara rekursif"),
    ("JavaScript", "fungsi debounce"),
    ("PHP", "fungsi validasi format email"),
    ("Python", "membaca file CSV dan menghitung rata-rata kolom"),
    ("SQL", "query menampilkan 5 produk terlaris"),
    ("Python", "implementasi binary search"),
    ("JavaScript", "fetch data dari API dan tampilkan di console"),
    ("Python", "scraping judul artikel dari HTML sederhana"),
    ("Java", "class Stack sederhana dengan push dan pop"),
    ("Python", "bubble sort dengan penjelasan kompleksitas"),
    ("PHP", "koneksi PDO ke MySQL dan ambil semua baris"),
]
DEBUG_SNIPPETS = [
    "def bagi(a,b):\\n    return a/b\\nprint(bagi(10,0))",
    "for i in range(5)\\n    print(i)",
    "let x = [1,2,3]; x.push(4) console.log(x)",
    "SELECT nama FROM users WHERE umur > ;",
]
FACT_QUESTIONS = [
    "ibu kota Provinsi Riau",
    "penemu bola lampu",
    "rumus luas lingkaran",
    "planet terbesar di tata surya",
    "tahun kemerdekaan Indonesia",
    "sungai terpanjang di dunia",
    "fungsi mitokondria pada sel",
    "perbedaan HTTP dan HTTPS",
    "apa itu machine learning",
    "siapa penulis novel Laskar Pelangi",
]
FRESH_FACTS = [
    "harga iPhone 17 Pro Max sekarang",
    "kurs dolar ke rupiah hari ini",
    "hasil pertandingan timnas Indonesia terakhir",
    "harga emas antam terbaru",
    "versi terbaru PHP saat ini",
]
DIAGRAM_REQUESTS = [
    "flowchart proses login user",
    "diagram alur pendaftaran mahasiswa baru",
    "diagram ER untuk sistem perpustakaan",
    "sequence diagram pemesanan tiket online",
    "flowchart algoritma bubble sort",
    "diagram arsitektur sistem client-server",
    "mindmap materi jaringan komputer",
    "diagram alur proses produksi pabrik",
]
GREETINGS = [
    "halo", "hai bang", "pagi", "assalamualaikum", "selamat malam",
    "hey apa kabar", "test", "woy", "halo kamu siapa", "p",
]
TRANSLATE = [
    ("Inggris", "Teknologi kecerdasan buatan berkembang pesat."),
    ("Inggris", "Saya sedang mengerjakan skripsi tentang jaringan saraf tiruan."),
    ("Jepang", "Terima kasih atas bantuannya."),
    ("Arab", "Selamat pagi, semoga harimu menyenangkan."),
]

# ---------------------------------------------------------------------------
# Category generators: each yields (subcategory, prompt, expect, notes)
# ---------------------------------------------------------------------------

def g_sapaan():
    for g in GREETINGS:
        yield ("sapaan", g, "chat", "balas hangat 1-2 kalimat, jangan berpidato, jangan buat artifact")

def g_penjelasan():
    topics = FACT_QUESTIONS + [t for _, t in [] ] + [
        "perbedaan RAM dan ROM", "cara kerja HTTPS", "apa itu REST API",
        "perbedaan machine learning dan deep learning", "apa itu overfitting",
        "cara kerja algoritma Dijkstra", "perbedaan SQL dan NoSQL",
        "apa itu normalisasi database", "cara kerja DNS", "apa itu container Docker",
    ]
    styles = ["jelaskan singkat", "jelaskan dengan analogi", "jelaskan dalam 3 poin",
              "jelaskan untuk pemula", "jelaskan lengkap dengan contoh"]
    for t in topics:
        s = random.choice(styles)
        yield ("penjelasan", f"{s} tentang {t}", "chat", "jawaban terstruktur, akurat, sesuai gaya diminta")

def g_penalaran():
    puzzles = [
        "Jika semua kucing adalah hewan dan sebagian hewan berbulu, apakah pasti semua kucing berbulu? Jawab ya/tidak lalu jelaskan.",
        "Ada 3 saklar di lantai bawah dan 3 lampu di lantai atas. Bagaimana cara menentukan saklar mana untuk lampu mana hanya dengan sekali naik?",
        "Budi lebih tua dari Ani, Ani lebih tua dari Citra. Siapa yang paling muda?",
        "Sebuah bakteri membelah tiap menit dan memenuhi toples dalam 60 menit. Menit ke berapa toples terisi setengah?",
        "Jika hari ini Selasa, hari apa 100 hari lagi?",
        "Kereta A dan B berjarak 300 km bergerak saling mendekat, A 60 km/jam, B 90 km/jam. Berapa lama sampai bertemu?",
    ]
    for p in puzzles:
        yield ("logika", p, "chat", "jawaban benar + alasan langkah demi langkah")

def g_matematika():
    ops = []
    for _ in range(60):
        a, b = random.randint(2, 99), random.randint(2, 99)
        op = random.choice(["+", "-", "x", "dibagi"])
        ops.append(f"berapa {a} {op} {b}?")
    extra = [
        "hitung 15% dari 240", "akar kuadrat dari 144",
        "jika 3x + 5 = 20, berapa x?", "luas segitiga alas 10 tinggi 6",
        "rata-rata dari 4, 8, 15, 16, 23, 42", "20 faktorial dibagi 18 faktorial",
    ]
    for p in ops + extra:
        yield ("hitung", p, "chat", "hasil numerik benar, boleh dengan langkah")

def g_koding():
    for lang, task in CODING_TASKS:
        yield ("tulis-kode", f"buatkan {task} dalam {lang}", "artifact_or_code",
               "kode lengkap & benar dalam artifact/code block, ada penjelasan singkat")
    for snip in DEBUG_SNIPPETS:
        yield ("debug", f"kenapa kode ini error dan bagaimana perbaikannya:\\n{snip}", "chat",
               "identifikasi bug + versi perbaikan")

def g_dokumen_skripsi():
    for t in SKRIPSI_TOPICS:
        yield ("skripsi-full", f"buatkan skripsi lengkap tentang {t}, penulis Ryan Rizki, Universitas Riau, prodi Teknologi Informasi",
               "artifact", "pipeline: front-matter mode:skripsi + BAB I-V + Daftar Pustaka, cover benar")
        yield ("skripsi-bab", f"buatkan skripsi tentang {t} sampai bab 1 saja",
               "artifact", "single-shot: dokumen dengan front-matter mode:skripsi, hanya sampai Bab 1")

def g_dokumen_lain():
    for t in MAKALAH_TOPICS:
        yield ("makalah", f"buatkan makalah tentang {t}, penulis Budi, Universitas Riau, dosen pengampu Dr. Andi",
               "artifact", "front-matter mode:makalah, cover MAKALAH (bukan SKRIPSI), 3 bab")
    for t in SKRIPSI_TOPICS[:8]:
        yield ("proposal", f"buatkan proposal penelitian tentang {t}",
               "artifact", "mode:proposal, BAB I-III tanpa bab hasil")
        yield ("laporan", f"buatkan laporan praktikum tentang {t}",
               "artifact", "mode:laporan, struktur laporan")

def g_judul():
    fields = ["teknologi informasi", "AI", "data science", "jaringan komputer",
              "sistem informasi", "keamanan siber", "IoT", "machine learning"]
    for f in fields:
        yield ("brainstorm-judul", f"saya butuh ide judul skripsi tentang {f}, berikan 5 yang bagus dan spesifik beserta metodenya",
               "chat", "daftar judul spesifik + metode, TANPA banner error web search")

def g_lanjutan():
    followups = [
        "perpanjang dan perdalam isi bab pembahasannya",
        "tambahkan bab 4 hasil dan pembahasan",
        "lanjutkan ke bab berikutnya",
        "perbaiki struktur dan format dokumennya",
        "tambahkan sub-bab kerangka pemikiran di bab 2",
        "perbanyak referensi di daftar pustaka",
    ]
    for f in followups:
        yield ("revisi-artifact", f, "artifact_update",
               "harus meng-UPDATE artifact (versi baru), bukan teks lepas; front-matter tetap ada")

def g_upload():
    tasks = [
        "berdasarkan skripsi yang saya lampirkan, buatkan bab 4",
        "lanjutkan bab 3 dari dokumen yang saya upload",
        "ringkas isi dokumen ini per bab",
        "cek perumusan masalah di bab 1 dokumen ini, apakah sudah baik?",
        "apa metode penelitian yang dipakai di dokumen ini?",
    ]
    for t in tasks:
        yield ("dokumen-upload", t, "grounded",
               "jawab/lanjut berdasar ISI dokumen upload, jangan mengarang, jangan tulis dari nol")

def g_diagram():
    for d in DIAGRAM_REQUESTS:
        yield ("diagram", f"buatkan {d}", "diagram",
               "keluarkan blok ```mermaid valid, tanpa ASCII/HTML")

def g_faktual():
    for f in FACT_QUESTIONS:
        yield ("fakta-stabil", f"apa {f}?" if not f[0].isupper() else f, "chat", "fakta benar, ringkas")
    for f in FRESH_FACTS:
        yield ("fakta-terbaru", f, "search", "harus mencari web (info berubah), jangan mengarang angka")

def g_bahasa():
    for lang, sent in TRANSLATE:
        yield ("terjemah", f"terjemahkan ke bahasa {lang}: {sent}", "chat", "terjemahan akurat")
    id_prompts = [
        "explain machine learning in simple terms",  # english prompt, must reply Indonesian if pref ID
        "tolong jawab dalam bahasa Indonesia baku tentang pentingnya olahraga",
    ]
    for p in id_prompts:
        yield ("konsistensi-bahasa", p, "chat", "patuhi bahasa; jangan campur Inggris jika diminta Indonesia")

def g_format():
    prompts = [
        "bandingkan React, Vue, dan Angular dalam bentuk tabel",
        "buat tabel perbandingan HTTP vs HTTPS",
        "jelaskan langkah instalasi Laravel dalam numbered list",
        "buat tabel spesifikasi 3 laptop gaming",
    ]
    for p in prompts:
        yield ("format-output", p, "chat", "gunakan tabel/list yang tepat sesuai konten")

def g_anti_tolak():
    # The exact failure seen live: model refuses to make files / suggests Google Docs.
    prompts = [
        "dari judul ini buatkan saya full sampai bab 1 dong",
        "buatkan dokumennya dong",
        "mana filenya? saya mau download",
        "tolong bikinkan file PDF laporannya",
        "buatkan versi DOCX dari isi tadi",
        "lanjutkan jadi dokumen lengkap",
    ]
    for p in prompts:
        yield ("anti-tolak-file", p, "refuse_never",
               "DILARANG bilang 'tidak bisa membuat file' / 'pakai Google Docs/Word'; harus keluarkan artifact")

def g_anti_halusinasi():
    prompts = [
        "sebutkan isi pasal 999 UUD 1945",  # doesn't exist
        "siapa presiden Indonesia ke-20?",   # doesn't exist yet
        "jelaskan teori relativitas versi Einstein tahun 2050",
    ]
    for p in prompts:
        yield ("anti-halusinasi", p, "admit_unknown",
               "akui tidak tahu / tidak ada, jangan mengarang")

GENERATORS = [
    g_sapaan, g_penjelasan, g_penalaran, g_matematika, g_koding,
    g_dokumen_skripsi, g_dokumen_lain, g_judul, g_lanjutan, g_upload,
    g_diagram, g_faktual, g_bahasa, g_format, g_anti_tolak, g_anti_halusinasi,
]

# Paraphrase wrappers to multiply volume without losing meaning.
WRAPPERS = [
    "{p}",
    "tolong {p}",
    "{p} ya",
    "bisa {p}?",
    "coba {p}",
    "{p} dong",
    "aku minta {p}",
    "{p}, makasih",
    "mas {p}",
    "boleh {p}?",
    "{p} sekarang",
    "bang {p}",
    "minta tolong {p}",
    "{p} yang bagus ya",
]


def category_of(fn):
    return fn.__name__[2:]  # strip "g_"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--target", type=int, default=2500, help="approx number of prompts (1000-5000)")
    ap.add_argument("--out", default="training/bahan-bersama/eval_prompts.jsonl")
    args = ap.parse_args()

    base = []
    for fn in GENERATORS:
        cat = category_of(fn)
        for sub, prompt, expect, notes in fn():
            base.append({"category": cat, "subcategory": sub, "prompt": prompt.strip(),
                         "expect": expect, "notes": notes})

    # Multiply with paraphrase wrappers until we reach the target, keeping balance.
    out = []
    seen = set()

    def add(item):
        key = item["prompt"].lower().strip()
        if key in seen:
            return False
        seen.add(key)
        h = hashlib.md5(key.encode("utf-8")).hexdigest()[:8]
        item = dict(item)
        item["id"] = f"{item['subcategory']}-{h}"
        out.append(item)
        return True

    for it in base:
        add(it)

    wi = 0
    pool = list(base)
    random.shuffle(pool)
    while len(out) < args.target and wi < args.target * 4:
        it = pool[wi % len(pool)]
        w = WRAPPERS[(wi // len(pool)) % len(WRAPPERS)]
        # Don't wrap greetings/math (already short/atomic) beyond the plain form.
        if it["category"] in ("sapaan", "matematika") and w != "{p}":
            wi += 1
            continue
        variant = dict(it)
        variant["prompt"] = w.format(p=it["prompt"])
        add(variant)
        wi += 1

    random.shuffle(out)

    with open(args.out, "w", encoding="utf-8") as f:
        for it in out:
            f.write(json.dumps(it, ensure_ascii=False) + "\n")

    # Summary
    from collections import Counter
    by_cat = Counter(it["category"] for it in out)
    print(f"wrote {len(out)} prompts -> {args.out}")
    print("by category:")
    for c, n in sorted(by_cat.items(), key=lambda x: -x[1]):
        print(f"  {c:22s} {n}")


if __name__ == "__main__":
    main()

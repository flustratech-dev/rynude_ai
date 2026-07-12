#!/usr/bin/env python3
"""
build_train_large.py — Build a LARGE (10k-50k) instruction-tuning set aimed at
making the NEXT rynude model (target: Stanza / Qwen3-4B) more powerful than
Lyric 4.6, focused on the exact behaviours Lyric fails at.

LEGAL: no Claude/GPT/Gemini output is used. Two clean sources only:
  1. PROGRAMMATIC completions (this file) — deterministic templates + computed
     facts. These teach BEHAVIOUR/FORMAT that Lyric 4.6 gets wrong:
       - always emit <antArtifact> with correct YAML front-matter + `mode:`
       - NEVER refuse to make a file / suggest Google Docs (anti-nolak)
       - admit when something doesn't exist (anti-halusinasi)
       - correct arithmetic, valid Mermaid, proper tables
     Templates are structural facts, not authored prose, so they are legal-clean.
  2. TEACHER completions (filled later) — open-ended PROSE (penjelasan, koding,
     penalaran) is left as `needs_teacher: true` and answered by a legal
     open-weights teacher (DeepSeek/Qwen via OpenRouter) in build_dataset.py.
     NEVER Claude/GPT/Gemini (see rancangan loRA.md Bab 2).

Curriculum rationale: programmatic data trains the model's FORMAT/BEHAVIOUR
reliably (where a bigger base still needs alignment), while rich CONTENT quality
comes from the teacher. Do not train content-heavy prose from these templates.

Output: training/train_large.jsonl  (ChatML: {"messages":[...], meta...})

Usage:
  python training/build_train_large.py --target 15000
  python training/build_train_large.py --target 40000 --out training/train_large.jsonl
"""
import argparse
import json
import random

random.seed(7)

SYSTEM = (
    "Anda adalah Rynude, asisten AI berbahasa Indonesia yang cerdas dan analitis. "
    "Jawab dalam Bahasa Indonesia baku ketika pengguna menulis Bahasa Indonesia. "
    "Sapaan dibalas singkat; permintaan sederhana langsung dikerjakan. Jangan berpidato, "
    "jangan membahas dirimu sebagai AI kecuali ditanya, jangan mengarang fakta/angka/referensi. "
    "Untuk permintaan dokumen (skripsi/makalah/laporan/proposal/PDF/DOCX) keluarkan dokumen "
    "LENGKAP dalam SATU blok <antArtifact type=\"text/markdown\" title=\"Judul\">...</antArtifact>; "
    "dokumen akademik diawali front-matter YAML. DILARANG menolak membuat file atau menyarankan "
    "Google Docs/Word. Untuk diagram keluarkan blok ```mermaid."
)

# --- topic pools (cross-multiplied to reach volume) ------------------------
FIELDS = ["teknologi informasi", "sistem informasi", "informatika", "kecerdasan buatan",
          "jaringan komputer", "keamanan siber", "sains data", "IoT", "robotika",
          "rekayasa perangkat lunak", "multimedia", "bisnis digital"]
TECHS = ["machine learning", "deep learning", "CNN", "LSTM", "random forest", "SVM",
         "NLP", "computer vision", "IoT sensor", "big data", "blockchain", "cloud computing",
         "algoritma genetika", "fuzzy logic", "data mining", "augmented reality"]
DOMAINS = ["pertanian", "kesehatan", "pendidikan", "perbankan", "transportasi", "manufaktur",
           "pemerintahan", "ritel", "pariwisata", "lingkungan", "logistik", "energi",
           "perikanan", "kehutanan", "UMKM", "e-commerce"]
ACTIONS = ["deteksi", "klasifikasi", "prediksi", "rekomendasi", "monitoring", "optimasi",
           "segmentasi", "analisis sentimen", "pemetaan", "penjadwalan"]
OBJECTS = ["penyakit tanaman", "kualitas air", "harga saham", "penyakit kulit", "sampah",
           "lalu lintas", "hasil panen", "kepuasan pelanggan", "risiko kredit", "cuaca",
           "stok gudang", "konsumsi energi", "hama", "berita hoaks", "wajah", "plat nomor"]

CREATE_VERBS = ["buatkan", "tolong buatkan", "buatlah", "bikinkan", "susun", "buatin"]
DOC_TYPES = {
    "skripsi": "skripsi", "makalah": "makalah", "laporan": "laporan",
    "proposal": "proposal", "tesis": "tesis",
}

DIAG_KINDS = [
    ("flowchart {x}", "graph TD\n    A([Mulai]) --> B[{n1}]\n    B --> C{{Valid?}}\n    C -->|Ya| D[{n2}]\n    C -->|Tidak| E[Tampilkan Error]\n    D --> F([Selesai])"),
    ("diagram alur {x}", "graph LR\n    A[{n1}] --> B[{n2}]\n    B --> C[{n3}]\n    C --> D[Selesai]"),
    ("sequence diagram {x}", "sequenceDiagram\n    participant U as User\n    participant S as Sistem\n    U->>S: Kirim permintaan\n    S-->>U: Balas hasil"),
    ("diagram ER {x}", "erDiagram\n    USER ||--o{{ ORDER : membuat\n    ORDER ||--|{{ ITEM : berisi"),
]
DIAG_TOPICS = ["proses login", "pendaftaran mahasiswa", "pemesanan tiket", "peminjaman buku",
               "proses produksi", "alur transaksi", "verifikasi pembayaran", "pengiriman barang"]


def topics(n):
    """Generate n distinct research-ish topics by cross-product."""
    out = set()
    tries = 0
    while len(out) < n and tries < n * 20:
        tries += 1
        style = random.random()
        if style < 0.5:
            t = f"penerapan {random.choice(TECHS)} untuk {random.choice(ACTIONS)} {random.choice(OBJECTS)} di bidang {random.choice(DOMAINS)}"
        elif style < 0.8:
            t = f"sistem informasi {random.choice(ACTIONS)} {random.choice(OBJECTS)} berbasis {random.choice(TECHS)}"
        else:
            t = f"analisis {random.choice(OBJECTS)} menggunakan {random.choice(TECHS)} pada sektor {random.choice(DOMAINS)}"
        out.add(t)
    return list(out)


# --- front-matter + skeleton templates (structure only, not rich prose) ----
def front_matter(mode, judul):
    base = f"---\nmode: {mode}\njudul: {judul}\npenulis: <Nama Penulis>\n"
    if mode in ("skripsi", "tesis", "proposal", "laporan"):
        base += "nim: <NIM>\nprodi: <Program Studi>\nfakultas: <Fakultas>\nuniversitas: <Universitas>\nkota: <Kota>\ntahun: 2025\npembimbing: <Nama Pembimbing>\n"
    elif mode == "makalah":
        base += "prodi: <Program Studi>\nfakultas: <Fakultas>\nuniversitas: <Universitas>\nkota: <Kota>\ntahun: 2025\ndosen: <Dosen Pengampu>\n"
    return base + "---\n"


SKELETONS = {
    "skripsi": ["# HALAMAN PENGESAHAN", "# ABSTRAK", "# BAB I PENDAHULUAN",
                "## 1.1 Latar Belakang", "## 1.2 Rumusan Masalah", "## 1.3 Tujuan",
                "# BAB II TINJAUAN PUSTAKA", "# BAB III METODOLOGI PENELITIAN",
                "# BAB IV HASIL DAN PEMBAHASAN", "# BAB V PENUTUP", "# DAFTAR PUSTAKA"],
    "tesis": ["# HALAMAN PENGESAHAN", "# ABSTRAK", "# BAB I PENDAHULUAN",
              "# BAB II TINJAUAN PUSTAKA", "# BAB III METODOLOGI PENELITIAN",
              "# BAB IV HASIL DAN PEMBAHASAN", "# BAB V PENUTUP", "# DAFTAR PUSTAKA"],
    "proposal": ["# BAB I PENDAHULUAN", "## 1.1 Latar Belakang", "## 1.2 Rumusan Masalah",
                 "## 1.3 Tujuan", "# BAB II TINJAUAN PUSTAKA", "# BAB III METODOLOGI PENELITIAN",
                 "# DAFTAR PUSTAKA"],
    "makalah": ["# KATA PENGANTAR", "# BAB I PENDAHULUAN", "## 1.1 Latar Belakang",
                "## 1.2 Rumusan Masalah", "## 1.3 Tujuan", "# BAB II PEMBAHASAN",
                "# BAB III PENUTUP", "## 3.1 Kesimpulan", "## 3.2 Saran", "# DAFTAR PUSTAKA"],
    "laporan": ["# KATA PENGANTAR", "# BAB I PENDAHULUAN", "# BAB II LANDASAN TEORI",
                "# BAB III PELAKSANAAN DAN PEMBAHASAN", "# BAB IV PENUTUP", "# DAFTAR PUSTAKA"],
}
SECTION_STUB = "_(Isi bagian ini ditulis lengkap dalam paragraf akademik saat generasi — bagian ini menandai struktur.)_"


def doc_example(mode, topic):
    """A structural document artifact: teaches front-matter + skeleton + anti-refuse.
    NOTE: section bodies are stubs on purpose — rich prose is the teacher's job."""
    judul = topic[0].upper() + topic[1:]
    body = front_matter(mode, judul) + "\n"
    for h in SKELETONS[mode]:
        body += h + "\n" + (SECTION_STUB + "\n\n" if h.startswith("##") or h.count(" ") > 2 else "\n")
    intro = f"Baik, saya susun {mode} tentang {topic}. Strukturnya mengikuti kaidah akademik: front-matter untuk sampul, lalu bab-bab berurutan sampai daftar pustaka."
    return intro + "\n\n<antArtifact type=\"text/markdown\" title=\"" + judul + "\">\n" + body + "</antArtifact>"


# --- record emitters --------------------------------------------------------
def rec(user, assistant=None, meta=None):
    m = {"messages": [{"role": "system", "content": SYSTEM},
                      {"role": "user", "content": user}]}
    if assistant is not None:
        m["messages"].append({"role": "assistant", "content": assistant})
        m["source"] = "programmatic"
    else:
        m["needs_teacher"] = True
        m["source"] = "prompt_only"
    if meta:
        m.update(meta)
    return m


def gen_documents(n):
    """anti-nolak + front-matter + artifact-format drills."""
    ts = topics(max(200, n // len(DOC_TYPES)))
    out = []
    for mode in DOC_TYPES.values():
        for t in ts:
            v = random.choice(CREATE_VERBS)
            scope = random.choice(["", "", " lengkap", " full", " sampai bab 1"])
            user = f"{v} {mode}{scope} tentang {t}"
            if "sampai bab 1" in scope:
                # scoped single-shot: front-matter + only Bab 1
                judul = t[0].upper() + t[1:]
                body = front_matter(mode, judul) + "\n# BAB I PENDAHULUAN\n## 1.1 Latar Belakang\n" + SECTION_STUB + "\n## 1.2 Rumusan Masalah\n" + SECTION_STUB + "\n## 1.3 Tujuan\n" + SECTION_STUB + "\n"
                a = f"Baik, saya buatkan {mode} tentang {t} sampai Bab 1.\n\n<antArtifact type=\"text/markdown\" title=\"{judul}\">\n{body}</antArtifact>"
            else:
                a = doc_example(mode, t)
            out.append(rec(user, a, {"category": "dokumen", "mode": mode}))
    random.shuffle(out)
    return out[:n]


REFUSE_TRIGGERS = [
    "mana dokumennya?", "mana filenya, saya mau download", "kok cuma teks, buatkan dokumennya",
    "tolong jadikan file PDF", "buatkan versi DOCX-nya", "lanjutkan jadi dokumen lengkap",
    "bikin filenya dong", "saya mau download hasilnya",
]
def gen_anti_refuse(n):
    ts = topics(n)
    out = []
    for i in range(n):
        t = ts[i % len(ts)]
        mode = random.choice(list(DOC_TYPES.values()))
        user = random.choice(REFUSE_TRIGGERS)
        a = doc_example(mode, t)
        out.append(rec(user, a, {"category": "anti_tolak"}))
    return out


NONEXISTENT = [
    "isi pasal 999 UUD 1945", "biografi presiden Indonesia ke-25",
    "hasil final Piala Dunia 2090", "spesifikasi iPhone 40",
    "teori relativitas versi Einstein tahun 2100", "penduduk kota Atlantis terbaru",
    "kurikulum resmi jurusan Teknik Teleportasi",
]
def gen_anti_halu(n):
    out = []
    for i in range(n):
        topic = NONEXISTENT[i % len(NONEXISTENT)]
        user = f"jelaskan {topic}"
        a = (f"Maaf, sepengetahuan saya {topic} tidak ada / belum ada, jadi saya tidak bisa "
             f"memberikan detailnya. Saya tidak ingin mengarang informasi yang tidak benar. "
             f"Jika maksud Anda hal lain yang mirip, beri tahu saya ya.")
        out.append(rec(user, a, {"category": "anti_halusinasi"}))
    return out


def gen_math(n):
    out = []
    for _ in range(n):
        a, b = random.randint(2, 999), random.randint(2, 99)
        op = random.choice(["+", "-", "x", ":"])
        if op == "+": res, sym = a + b, "+"
        elif op == "-": res, sym = a - b, "-"
        elif op == "x": res, sym = a * b, "×"
        else:
            b = random.randint(2, 20); a = b * random.randint(2, 50); res, sym = a // b, "÷"
        user = f"berapa {a} {op} {b}?"
        ans = f"{a} {sym} {b} = {res}."
        out.append(rec(user, ans, {"category": "matematika"}))
    return out


def gen_diagram(n):
    out = []
    for i in range(n):
        tmpl, code = random.choice(DIAG_KINDS)
        topic = DIAG_TOPICS[i % len(DIAG_TOPICS)]
        user = f"buatkan {tmpl.format(x=topic)}"
        parts = topic.split()
        code = code.format(x=topic, n1=parts[0].capitalize(), n2=(parts[1].capitalize() if len(parts) > 1 else "Proses"),
                           n3="Selesai")
        a = f"Berikut diagramnya:\n\n```mermaid\n{code}\n```"
        out.append(rec(user, a, {"category": "diagram"}))
    return out


def gen_table(n):
    combos = [("HTTP", "HTTPS", ["Port", "Keamanan", "Enkripsi"]),
              ("SQL", "NoSQL", ["Struktur", "Skalabilitas", "Skema"]),
              ("RAM", "ROM", ["Sifat", "Kecepatan", "Fungsi"]),
              ("TCP", "UDP", ["Keandalan", "Kecepatan", "Koneksi"])]
    out = []
    for i in range(n):
        a1, a2, attrs = combos[i % len(combos)]
        user = f"buat tabel perbandingan {a1} vs {a2}"
        rows = "\n".join(f"| {at} | ... | ... |" for at in attrs)
        a = f"Berikut perbandingannya:\n\n| Aspek | {a1} | {a2} |\n|---|---|---|\n{rows}"
        out.append(rec(user, a, {"category": "format"}))
    return out


GREET_IN = ["halo", "hai", "pagi", "malam", "assalamualaikum", "hey", "test", "p", "woy", "bang"]
GREET_OUT = ["Halo! Ada yang bisa saya bantu?", "Hai! Mau saya bantu apa hari ini?",
             "Halo, senang menyapa Anda. Butuh bantuan apa?"]
def gen_greet(n):
    out = []
    for i in range(n):
        out.append(rec(GREET_IN[i % len(GREET_IN)], GREET_OUT[i % len(GREET_OUT)],
                       {"category": "sapaan"}))
    return out


def gen_prompt_only(n):
    """Open-ended prose → left for the teacher (DeepSeek/Qwen)."""
    out = []
    explain = [f"jelaskan {t}" for t in
               ["cara kerja HTTPS", "perbedaan machine learning dan deep learning",
                "apa itu overfitting", "cara kerja algoritma Dijkstra", "apa itu REST API",
                "normalisasi database", "cara kerja DNS", "container Docker",
                "perbedaan proses dan thread", "apa itu API gateway"]]
    code = [f"buatkan {t} dalam Python" for t in
            ["fungsi cek bilangan prima", "implementasi binary search", "bubble sort",
             "baca CSV dan hitung rata-rata", "fungsi validasi email", "quicksort"]]
    reason = ["Jika A lebih tua dari B dan B lebih tua dari C, siapa termuda?",
              "Sebuah bakteri membelah tiap menit, penuh dalam 60 menit; menit ke berapa setengah?"]
    pool = explain + code + reason + [f"jelaskan penerapan {t} di {d}" for t in TECHS for d in DOMAINS]
    random.shuffle(pool)
    for i in range(n):
        out.append(rec(pool[i % len(pool)], None, {"category": "prose_teacher"}))
    return out


# --- FIX (temuan testing 4.7): model TERLALU sering bikin <antArtifact> untuk
# konten kasual (resep, tips, saran, daftar, cara). Contoh-contoh ini mengajarkan
# batas yang benar: hal santai/pendek DIJAWAB DI CHAT, TANPA artifact. Sinyal
# utamanya = tidak ada tag <antArtifact> pada jawaban. Isi sengaja ringkas.
FOODS = ["nasi goreng", "rendang", "soto ayam", "gado-gado", "mie goreng",
         "ayam bakar", "sate ayam", "capcay", "tempe orek", "sayur asem"]
TIPS_TOPICS = ["belajar coding", "menghemat uang", "produktif bekerja", "tidur nyenyak",
               "public speaking", "belajar bahasa Inggris", "menjaga kesehatan", "fokus belajar"]
HOWTO = ["membuat kopi susu", "merawat tanaman hias", "mencuci sepatu putih",
         "mengganti ban motor", "membersihkan laptop", "menanam cabai di pot"]


def gen_chat_not_artifact(n):
    out = []
    builders = []
    # resep -> jawab di chat (BUKAN artifact/document)
    for f in FOODS:
        ans = (f"Tentu! Berikut resep {f} sederhana:\n\n"
               f"**Bahan:** bahan-bahan utama {f} secukupnya, bumbu dasar (bawang, garam), dan minyak.\n\n"
               f"**Langkah:**\n1. Siapkan dan haluskan bumbu.\n2. Tumis bumbu hingga harum.\n"
               f"3. Masukkan bahan utama, aduk rata.\n4. Masak hingga matang, cicipi rasa.\n\n"
               f"Selamat mencoba! Mau saya sesuaikan porsinya?")
        builders.append((f"berikan resep {f}", ans))
        builders.append((f"resep {f} dong", ans))
    for t in TIPS_TOPICS:
        ans = (f"Berikut beberapa tips {t}:\n\n"
               f"1. **Mulai dari yang kecil** — konsisten lebih penting dari sempurna.\n"
               f"2. **Buat jadwal** dan patuhi.\n3. **Evaluasi** kemajuan tiap minggu.\n\n"
               f"Mau saya bahas salah satu poin lebih dalam?")
        builders.append((f"kasih tips {t}", ans))
        builders.append((f"gimana cara {t}?", ans))
    for h in HOWTO:
        ans = (f"Berikut cara {h}:\n\n1. Siapkan alat & bahan yang dibutuhkan.\n"
               f"2. Lakukan langkah utamanya dengan hati-hati.\n3. Rapikan setelah selesai.\n\n"
               f"Butuh detail di langkah tertentu?")
        builders.append((f"cara {h}", ans))
    # saran/daftar santai -> chat
    builders.append(("berikan saran makanan Indonesia",
                     "Beberapa makanan Indonesia yang enak: nasi goreng, rendang, soto ayam, "
                     "gado-gado, dan sate ayam. Mau saya bantu resep salah satunya?"))
    builders.append(("rekomendasi film bagus",
                     "Beberapa rekomendasi: film aksi, drama, atau dokumenter sesuai selera Anda. "
                     "Anda suka genre apa? Biar saya sarankan yang lebih pas."))
    random.shuffle(builders)
    for i in range(n):
        u, a = builders[i % len(builders)]
        out.append(rec(u, a, {"category": "chat_bukan_artifact"}))
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--target", type=int, default=15000)
    ap.add_argument("--out", default="training/lyric-4.6/dataset.jsonl")
    args = ap.parse_args()

    T = args.target
    plan = [
        (gen_documents,        int(T * 0.25)),   # front-matter + artifact + doc-type
        (gen_anti_refuse,      int(T * 0.10)),   # never refuse a file (diturunkan: 4.7 kebablasan)
        (gen_chat_not_artifact, int(T * 0.12)),  # FIX: konten kasual -> CHAT, bukan artifact
        (gen_anti_halu,        int(T * 0.08)),   # admit unknown
        (gen_math,             int(T * 0.10)),   # arithmetic
        (gen_diagram,          int(T * 0.08)),   # valid mermaid
        (gen_table,            int(T * 0.05)),   # tables
        (gen_greet,            int(T * 0.02)),   # greetings
        (gen_prompt_only,      int(T * 0.20)),   # prose → teacher
    ]
    rows = []
    for fn, k in plan:
        rows.extend(fn(k))
    random.shuffle(rows)

    with open(args.out, "w", encoding="utf-8") as f:
        for r in rows:
            f.write(json.dumps(r, ensure_ascii=False) + "\n")

    from collections import Counter
    prog = sum(1 for r in rows if r.get("source") == "programmatic")
    teach = sum(1 for r in rows if r.get("needs_teacher"))
    bycat = Counter(r.get("category", "?") for r in rows)
    print(f"wrote {len(rows)} records -> {args.out}")
    print(f"  programmatic (ready to train, legal): {prog}")
    print(f"  needs_teacher (fill via DeepSeek/Qwen): {teach}")
    print("by category:")
    for c, n in bycat.most_common():
        print(f"  {c:16s} {n}")


if __name__ == "__main__":
    main()

# -*- coding: utf-8 -*-
"""
Build the combined training set for rynude Stanza 4.6.

Menggabungkan dataset Lyric 4.6 / 4.7 / 4.8 + dataset akademik (skripsi),
lalu:
  1. MEMBUANG contoh beracun (placeholder skeleton "menandai struktur" /
     "Isi bagian ini ditulis lengkap ...") — inilah yang dulu mengajari model
     mengeluarkan kerangka kosong. 8.412 dari 35.736 record mengandung ini.
  2. DEDUP lintas sumber (banyak duplikat antar file Lyric).
  3. OVERSAMPLE contoh akademik/skripsi (default x4) supaya kemampuan menulis
     skripsi jadi kekuatan utama — bukan cuma ~3% dari data.

Output: dataset_stanza46.jsonl (format OpenAI chat {"messages": [...]}).
Jalankan dari folder mana saja:
  .venv-train/Scripts/python.exe stanza-4b/build_stanza_dataset.py
"""
import json, hashlib, os, random, argparse

HERE = os.path.dirname(os.path.abspath(__file__))
TRAIN = os.path.dirname(HERE)  # training/

POISON = (
    "menandai struktur",
    "isi bagian ini ditulis",
    "ditulis lengkap dalam paragraf",
    "ditulis saat generasi",
)

# Sumber Lyric (lineage 4.6/4.7/4.8). dataset.jsonl + dataset_4.8.jsonl bersih;
# siap-latih & upgrade mengandung poison (akan disaring), tetap dipakai untuk
# contoh bersihnya. FINAL akademik = union v1+v2, jadi kita pakai file mentahnya
# langsung supaya versi v2 yang SUDAH diperluas ikut terpakai.
LYRIC_SOURCES = [
    "lyric-4.6/dataset.jsonl",
    "lyric-4.6/dataset_siap-latih.jsonl",
    "lyric-4.6/dataset_upgrade.jsonl",
    "lyric-4.6/dataset_4.8.jsonl",
]
AKADEMIK_SOURCES = [
    "akademik/dataset_skripsi_v2_100plus.jsonl",
    "akademik/dataset_skripsi_akademik.jsonl",
]


def assistant_text(rec):
    return " ".join(
        (m.get("content") or "")
        for m in rec.get("messages", [])
        if m.get("role") == "assistant"
    )


def is_poison(rec):
    a = assistant_text(rec).lower()
    return any(p in a for p in POISON)


def normalize(rec):
    """Keep only messages (drop source/category), require non-empty assistant."""
    msgs = rec.get("messages")
    if not isinstance(msgs, list) or not msgs:
        return None
    if not any(m.get("role") == "assistant" and (m.get("content") or "").strip() for m in msgs):
        return None
    return {"messages": msgs}


def key(rec):
    return hashlib.md5(
        json.dumps(rec["messages"], ensure_ascii=False, sort_keys=True).encode()
    ).hexdigest()


def load(paths):
    out = []
    for rel in paths:
        p = os.path.join(TRAIN, rel)
        if not os.path.exists(p):
            print(f"  ! lewati (tidak ada): {rel}")
            continue
        n = 0
        for line in open(p, encoding="utf-8"):
            line = line.strip()
            if not line:
                continue
            try:
                rec = json.loads(line)
            except json.JSONDecodeError:
                continue
            out.append(rec)
            n += 1
        print(f"  + {rel}: {n} record")
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--oversample", type=int, default=4, help="berapa kali contoh akademik digandakan")
    ap.add_argument("--max-general", type=int, default=0,
                    help="batas jumlah contoh chat umum (0 = tanpa batas). Profil cepat: 3500. "
                         "Data skripsi/akademik TIDAK pernah dipotong.")
    ap.add_argument("--out", default=os.path.join(HERE, "dataset_stanza46.jsonl"))
    args = ap.parse_args()

    print(">> Memuat sumber Lyric ...")
    lyric = load(LYRIC_SOURCES)
    print(">> Memuat sumber akademik (skripsi) ...")
    akad = load(AKADEMIK_SOURCES)

    seen = set()
    general, dropped_poison, dropped_dup = [], 0, 0
    for rec in lyric:
        if is_poison(rec):
            dropped_poison += 1
            continue
        norm = normalize(rec)
        if norm is None:
            continue
        k = key(norm)
        if k in seen:
            dropped_dup += 1
            continue
        seen.add(k)
        general.append(norm)

    # Akademik: dedup satu kali, simpan uniknya untuk dioversample.
    akad_unique = []
    akad_seen = set()
    for rec in akad:
        if is_poison(rec):
            dropped_poison += 1
            continue
        norm = normalize(rec)
        if norm is None:
            continue
        k = key(norm)
        if k in akad_seen:
            continue
        akad_seen.add(k)
        akad_unique.append(norm)

    # Profil cepat: subsample chat umum (redundan) — skripsi/akademik tetap penuh.
    if args.max_general and len(general) > args.max_general:
        random.seed(42)
        general = random.sample(general, args.max_general)
        seen = {key(r) for r in general}

    final = list(general)
    # tambahkan akademik sebanyak OVERSAMPLE kali (unik pertama masuk ke dedup
    # global agar tidak tumpang tindih dengan lyric; sisanya sengaja duplikat)
    added_akad = 0
    for i in range(args.oversample):
        for rec in akad_unique:
            if i == 0 and key(rec) in seen:
                continue
            final.append(rec)
            added_akad += 1

    random.seed(42)
    random.shuffle(final)

    with open(args.out, "w", encoding="utf-8") as f:
        for rec in final:
            f.write(json.dumps(rec, ensure_ascii=False) + "\n")

    total_chars = sum(len(assistant_text(r)) for r in final)
    print("\n=== HASIL BUILD STANZA 4.6 ===")
    print(f"Lyric record dibaca      : {len(lyric)}")
    print(f"Akademik record dibaca   : {len(akad)} (unik: {len(akad_unique)})")
    print(f"Dibuang (poison)         : {dropped_poison}")
    print(f"Dibuang (duplikat)       : {dropped_dup}")
    print(f"Umum unik (deduped)      : {len(general)}")
    print(f"Akademik (oversample x{args.oversample}) : {added_akad}")
    print(f"TOTAL baris output       : {len(final)}")
    print(f"  - porsi akademik       : {added_akad/len(final)*100:.1f}%")
    print(f"Rata-rata jawaban        : {total_chars//max(1,len(final))} chars")
    print(f"File                     : {args.out}")


if __name__ == "__main__":
    main()

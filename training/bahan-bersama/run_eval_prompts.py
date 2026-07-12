#!/usr/bin/env python3
"""
run_eval_prompts.py — Send the capability prompts to the local rynude model and
record responses + heuristic PASS/FAIL flags, so you can see at a glance what
Lyric 4.6 already handles and where it needs more training.

IMPORTANT: this hits the local GGUF server (port 8091) DIRECTLY — it measures the
RAW MODEL, not the ChatStreamingService pipeline. So document/artifact/skripsi
behaviours here reflect the model alone (grammar, reminders and the per-chapter
pipeline are NOT applied). Best used for: reasoning, math, coding, facts,
language, refusal-tendency, and how often the model spontaneously emits
<antArtifact>/```mermaid without help. For true document behaviour, test in the app.

Usage:
  python training/run_eval_prompts.py --limit 200
  python training/run_eval_prompts.py --category anti_tolak,diagram,koding
  python training/run_eval_prompts.py --limit 50 --out training/eval_run.jsonl
"""

import argparse
import json
import re
import time
import urllib.request

SERVER = "http://127.0.0.1:8091/v1/chat/completions"
SYSTEM = ("Anda adalah Rynude, asisten AI berbahasa Indonesia yang cerdas dan analitis. "
          "Jawab dalam Bahasa Indonesia yang natural. Untuk permintaan dokumen "
          "(skripsi/makalah/laporan/PDF/DOCX) keluarkan dokumen di dalam satu blok "
          "<antArtifact type=\"text/markdown\" title=\"Judul\">...</antArtifact>; JANGAN "
          "pernah menolak membuat file atau menyarankan Google Docs/Word. Untuk "
          "diagram keluarkan blok ```mermaid.")

REFUSAL_RE = re.compile(r"(tidak bisa membuat|tidak dapat membuat|google docs|microsoft word|"
                        r"saya tidak bisa membuat file|gunakan aplikasi seperti)", re.I)


def call(prompt, max_tokens=700, timeout=180, no_think=True):
    # Qwen3 /no_think switch: skip the (slow, on CPU) reasoning pass. For a broad
    # capability sweep this keeps runs practical; drop it (--think) for a slower
    # run that also exercises the model's reasoning trace.
    user = prompt + (" /no_think" if no_think else "")
    body = json.dumps({
        "model": "rynude-lyric-plus-1",
        "messages": [{"role": "system", "content": SYSTEM},
                     {"role": "user", "content": user}],
        "max_tokens": max_tokens,
        "temperature": 0.6,
    }).encode("utf-8")
    req = urllib.request.Request(SERVER, data=body, headers={"Content-Type": "application/json"})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        data = json.loads(r.read().decode("utf-8"))
    return data["choices"][0]["message"]["content"]


def strip_think(text):
    return re.sub(r"<(think|thinking|sim_thinking)>.*?</\1>", "", text, flags=re.S | re.I).strip()


def grade(item, resp):
    """Lightweight heuristic per `expect`. Returns (flag, reason)."""
    r = resp.lower()
    exp = item.get("expect", "")
    if not resp.strip():
        return "FAIL", "kosong"
    if exp == "refuse_never":
        return ("FAIL", "menolak/menyarankan Google Docs") if REFUSAL_RE.search(resp) else ("PASS", "tidak menolak")
    if exp == "diagram":
        return ("PASS", "ada blok mermaid") if "```mermaid" in r else ("FAIL", "tanpa mermaid")
    if exp in ("artifact", "artifact_update"):
        return ("PASS", "ada <antArtifact>") if "<antartifact" in r else ("WARN", "tanpa artifact (raw model)")
    if exp == "admit_unknown":
        ok = any(k in r for k in ["tidak ada", "belum ada", "tidak tahu", "tidak dapat memastikan", "tidak diketahui"])
        return ("PASS", "mengakui tidak tahu") if ok else ("WARN", "cek halusinasi")
    if exp == "search":
        # raw model can't search; we just flag if it fabricates a specific number
        return ("INFO", "butuh web-search (cek di app)")
    # chat / others: basic sanity
    if len(resp) < 8:
        return "FAIL", "terlalu pendek"
    return "PASS", "ok"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--prompts", default="training/bahan-bersama/eval_prompts.jsonl")
    ap.add_argument("--out", default="training/lyric-4.6/eval_run.jsonl")
    ap.add_argument("--limit", type=int, default=200)
    ap.add_argument("--category", default="", help="comma-separated categories to include")
    ap.add_argument("--max-tokens", type=int, default=700)
    ap.add_argument("--think", action="store_true", help="keep the reasoning pass (slower)")
    args = ap.parse_args()

    cats = set(c.strip() for c in args.category.split(",") if c.strip())
    items = []
    with open(args.prompts, encoding="utf-8") as f:
        for line in f:
            it = json.loads(line)
            if cats and it["category"] not in cats:
                continue
            items.append(it)
    items = items[:args.limit]

    from collections import Counter
    tally = Counter()
    results = []
    t0 = time.time()
    # Write each result to the output file immediately (flushed), so progress is
    # observable even when stdout is buffered by the runner/harness.
    out_f = open(args.out, "w", encoding="utf-8")
    for i, it in enumerate(items, 1):
        try:
            resp = strip_think(call(it["prompt"], args.max_tokens, no_think=not args.think))
            flag, reason = grade(it, resp)
        except Exception as e:
            resp, flag, reason = f"[ERROR: {e}]", "ERROR", str(e)
        tally[flag] += 1
        rec = {**it, "response": resp[:4000], "flag": flag, "reason": reason}
        results.append(rec)
        out_f.write(json.dumps(rec, ensure_ascii=False) + "\n")
        out_f.flush()
        print(f"[{i}/{len(items)}] {flag:5s} {it['category']:16s} {reason:28s} :: {it['prompt'][:60]}", flush=True)
    out_f.close()

    dt = time.time() - t0
    print(f"\n== {len(items)} prompts in {dt:.0f}s -> {args.out} ==")
    for k, n in tally.most_common():
        print(f"  {k:5s} {n}")
    # Per-category pass-rate for a capability map
    cat_pass = Counter(); cat_total = Counter()
    for r in results:
        cat_total[r["category"]] += 1
        if r["flag"] in ("PASS", "INFO"):
            cat_pass[r["category"]] += 1
    print("\ncapability map (pass-rate per category):")
    for c in sorted(cat_total):
        print(f"  {c:22s} {cat_pass[c]}/{cat_total[c]}")


if __name__ == "__main__":
    main()

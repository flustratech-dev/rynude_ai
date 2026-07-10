#!/usr/bin/env python3
"""
Build the LoRA training set for rynude Lyric 4.6 (Qwen3-1.7B).

Combines three legal, free sources into one ChatML JSONL:

  1. golden_examples.jsonl  — hand-authored gold standard (in this repo)
  2. seeds.jsonl            — YOUR chat prompts, exported via
                              `php artisan rynude:export-seeds`
                              (prompts only; answers are regenerated here)
  3. (optional) teacher augmentation — fresh answers to the seeds, generated
     by a LEGAL open-weights teacher (DeepSeek/Qwen via OpenRouter). NEVER use
     Claude/GPT/Gemini output for a model you intend to sell (see
     rancangan loRA.md Bab 2).

Output: train.jsonl + val.jsonl in ChatML format, ready for the Colab notebook.

Usage:
  # Minimal (golden only — works with zero setup, good for a first smoke test):
  python build_dataset.py

  # With your exported seeds, answered by a free OpenRouter teacher:
  export OPENROUTER_API_KEY=sk-or-...
  python build_dataset.py --seeds seeds.jsonl --teacher deepseek/deepseek-chat --augment 500

No paid API and no GPU required to RUN this script (teacher calls use the free
tier). The heavy GPU work happens later, in the Colab notebook.
"""
import argparse
import glob
import json
import os
import random
import sys
import time
import urllib.request

# Compact production-shaped system prompt used for augmented examples so that
# training conditions match how the app actually prompts the local model.
SYSTEM_PROMPT = (
    "Anda adalah Rynude, asisten AI berbahasa Indonesia yang cerdas dan membumi. "
    "WAJIB menjawab dalam Bahasa Indonesia baku ketika pengguna menulis Bahasa Indonesia, "
    "meskipun konteks memuat istilah Inggris. Sapaan dibalas singkat; permintaan sederhana "
    "langsung dikerjakan (maksimal satu pertanyaan klarifikasi). Jangan berpidato, jangan "
    "membahas dirimu sebagai AI kecuali ditanya, jangan mengarang fakta/angka/referensi. "
    "Untuk permintaan dokumen, keluarkan isinya dalam blok "
    "<antArtifact type=\"text/markdown\" title=\"Judul\">...</antArtifact>."
)


def read_jsonl(path):
    rows = []
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line:
                rows.append(json.loads(line))
    return rows


def call_teacher(prompt, model, api_key, retries=3):
    """One chat completion from an OpenRouter teacher model. Returns text or None."""
    body = json.dumps({
        "model": model,
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": prompt},
        ],
        "temperature": 0.7,
        "max_tokens": 1500,
    }).encode("utf-8")
    req = urllib.request.Request(
        "https://openrouter.ai/api/v1/chat/completions",
        data=body,
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        },
    )
    for attempt in range(retries):
        try:
            with urllib.request.urlopen(req, timeout=90) as resp:
                data = json.loads(resp.read().decode("utf-8"))
                return data["choices"][0]["message"]["content"].strip()
        except Exception as e:  # noqa
            wait = 2 ** attempt
            print(f"  teacher retry ({e}) in {wait}s", file=sys.stderr)
            time.sleep(wait)
    return None


ENGLISH_WORDS = (" the ", " of ", " and ", " is ", " are ", " this ", " with ", " to ")
INDO_WORDS = (" yang ", " dan ", " dengan ", " ini ", " untuk ", " adalah ", " pada ", " dari ")


def looks_english(text):
    s = " " + text.lower()[:2000] + " "
    return sum(s.count(w) for w in ENGLISH_WORDS) > sum(s.count(w) for w in INDO_WORDS)


def quality_ok(prompt, answer):
    """Reject obviously bad teacher answers (Bab 5.5 rejection sampling)."""
    if not answer or len(answer) < 20:
        return False
    if looks_english(answer) and not looks_english(prompt):
        return False  # drifted to English on an Indonesian prompt
    banned = ("as an ai", "i cannot", "sebagai ai", "tidak memiliki kemampuan",
              "language model", "i'm sorry, but i")
    low = answer.lower()
    if any(b in low for b in banned):
        return False
    # crude loop check
    for i in range(0, max(0, len(answer) - 80), 40):
        if answer.count(answer[i:i + 80]) >= 3:
            return False
    return True


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--golden-glob", default="golden_*.jsonl",
                    help="Glob for hand-authored gold files (default: all golden_*.jsonl)")
    ap.add_argument("--seeds", default=None, help="seeds.jsonl from rynude:export-seeds")
    ap.add_argument("--teacher", default="deepseek/deepseek-chat",
                    help="OpenRouter model id (use a free/open one, NOT Claude/GPT/Gemini)")
    ap.add_argument("--augment", type=int, default=0,
                    help="How many seed prompts to answer with the teacher (0 = skip)")
    ap.add_argument("--val-frac", type=float, default=0.05)
    ap.add_argument("--out-prefix", default="")
    args = ap.parse_args()

    dataset = []

    # 1) Golden set (always) — ALL golden_*.jsonl files (golden_examples +
    #    golden_fixes + any you add later).
    golden_files = sorted(glob.glob(args.golden_glob))
    golden_total = 0
    for gf in golden_files:
        rows = read_jsonl(gf)
        dataset.extend(rows)
        golden_total += len(rows)
        print(f"  {gf}: {len(rows)}")
    print(f"Golden examples (total): {golden_total}")

    # 2) Teacher-augmented answers to your seeds.
    if args.seeds and args.augment > 0:
        api_key = os.environ.get("OPENROUTER_API_KEY")
        if not api_key:
            print("ERROR: --augment set but OPENROUTER_API_KEY is missing.", file=sys.stderr)
            print("Get a free key at https://openrouter.ai/keys and: export OPENROUTER_API_KEY=sk-or-...", file=sys.stderr)
            sys.exit(1)
        seeds = read_jsonl(args.seeds)
        random.shuffle(seeds)
        made = 0
        for seed in seeds:
            if made >= args.augment:
                break
            prompt = seed.get("prompt", "").strip()
            if not prompt:
                continue
            print(f"[{made+1}/{args.augment}] teacher answering: {prompt[:60]}...")
            answer = call_teacher(prompt, args.teacher, api_key)
            if answer and quality_ok(prompt, answer):
                dataset.append({
                    "category": "seed-" + seed.get("category", "umum"),
                    "messages": [
                        {"role": "system", "content": SYSTEM_PROMPT},
                        {"role": "user", "content": prompt},
                        {"role": "assistant", "content": answer},
                    ],
                })
                made += 1
            else:
                print("  rejected (quality)", file=sys.stderr)
        print(f"Teacher-augmented examples kept: {made}")

    if not dataset:
        print("No data produced.", file=sys.stderr)
        sys.exit(1)

    # 3) Shuffle + split.
    random.seed(42)
    random.shuffle(dataset)
    n_val = max(1, int(len(dataset) * args.val_frac))
    val, train = dataset[:n_val], dataset[n_val:]

    def dump(path, rows):
        with open(path, "w", encoding="utf-8") as f:
            for r in rows:
                f.write(json.dumps({"messages": r["messages"]}, ensure_ascii=False) + "\n")

    dump(args.out_prefix + "train.jsonl", train)
    dump(args.out_prefix + "val.jsonl", val)
    print(f"\nWrote {len(train)} train + {len(val)} val examples.")
    print("Next: upload train.jsonl + val.jsonl to the Colab notebook and Run all.")


if __name__ == "__main__":
    main()

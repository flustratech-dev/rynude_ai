# Dataset Skripsi Akademik Indonesia
## Untuk Fine-Tuning Model Bahasa (Lyric4.8 dan sejenisnya)

---

## 📁 File yang Dihasilkan

| File | Deskripsi |
|------|-----------|
| `dataset_skripsi_akademik.jsonl` | Dataset utama dalam format JSONL |
| `generate_dataset.py` | Script Python untuk regenerasi dataset |
| `README_DATASET.md` | Dokumentasi ini |

---

## 📊 Statistik Dataset

- **Total Sampel**: 16 pasang instruksi-respons berkualitas tinggi
- **Ukuran File**: ~0.13 MB (JSONL)
- **Bahasa**: Bahasa Indonesia Akademik
- **Format**: OpenAI Chat Completions Format (JSONL)

---

## 🎯 Topik yang Dicakup

| No | Topik | Komponen |
|----|-------|----------|
| 1 | Cloud Computing + Machine Learning | Halaman Judul, Abstrak |
| 2 | Cloud Computing + ML | BAB I Pendahuluan Lengkap |
| 3 | Cloud Computing + ML | BAB II Tinjauan Pustaka |
| 4 | Cloud Computing + ML | BAB III Metodologi |
| 5 | Cloud Computing + ML | BAB IV Hasil & Pembahasan |
| 6 | Cloud Computing + ML | BAB V Kesimpulan + Daftar Pustaka |
| 7 | Sistem Rekomendasi E-Commerce | Abstrak + BAB I |
| 8 | Keamanan Siber (LSTM-CNN IDS) | BAB II Tinjauan Pustaka |
| 9 | IoT Smart City (Kualitas Udara) | BAB III + BAB IV |
| 10 | Sistem Pendukung Keputusan (AHP-TOPSIS) | BAB I + BAB II |
| 11 | NLP - Analisis Sentimen BERT | BAB IV + BAB V |
| 12 | Blockchain Farmasi | BAB II Tinjauan Pustaka |
| 13 | Computer Vision (Deteksi Penyakit Tanaman) | BAB III Metodologi |
| 14 | Q&A Akademik | Perbedaan Kuantitatif vs Kualitatif |
| 15 | Q&A Akademik | Format Daftar Pustaka APA 7 |
| 16 | Sistem Informasi Kepegawaian (Laravel+Vue) | BAB III Metodologi |

---

## 📝 Format File JSONL

Setiap baris adalah satu JSON object dengan format:

```json
{
  "messages": [
    {
      "role": "system",
      "content": "Anda adalah asisten akademik ahli..."
    },
    {
      "role": "user", 
      "content": "Tulis BAB I untuk skripsi tentang..."
    },
    {
      "role": "assistant",
      "content": "BAB I\nPENDAHULUAN\n\n1.1 Latar Belakang\n\n..."
    }
  ]
}
```

Format ini kompatibel dengan:
- OpenAI fine-tuning API
- Hugging Face TRL library
- Axolotl training framework
- LLaMA Factory
- Unsloth

---

## 🚀 Cara Menggunakan Dataset

### Untuk OpenAI Fine-Tuning API
```python
# Upload dataset
import openai
client = openai.OpenAI()
with open("dataset_skripsi_akademik.jsonl", "rb") as f:
    file = client.files.create(file=f, purpose="fine-tune")

# Buat fine-tuning job
job = client.fine_tuning.jobs.create(
    training_file=file.id,
    model="gpt-4o-mini"
)
```

### Untuk Hugging Face TRL (RLHF/SFT)
```python
from datasets import load_dataset
from trl import SFTTrainer

dataset = load_dataset("json", data_files="dataset_skripsi_akademik.jsonl")
trainer = SFTTrainer(
    model=model,
    train_dataset=dataset["train"],
    dataset_text_field="messages",
)
trainer.train()
```

### Validasi Dataset
```python
import json
with open("dataset_skripsi_akademik.jsonl", "r", encoding="utf-8") as f:
    for i, line in enumerate(f):
        data = json.loads(line)
        assert "messages" in data
        assert len(data["messages"]) == 3
        roles = [m["role"] for m in data["messages"]]
        assert roles == ["system", "user", "assistant"]
        print(f"Sample {i+1}: OK | Assistant length: {len(data['messages'][2]['content'])} chars")
```

---

## 🔍 Kata Kunci Pencarian Dataset Tambahan

Jika Anda ingin mencari dataset skripsi tambahan dari internet, gunakan kata kunci berikut:

### Google/Bing Search
```
"Indonesian academic thesis dataset" filetype:jsonl
"dataset skripsi Indonesia" fine-tuning
"academic writing dataset" Indonesian language NLP
Indonesian thesis generation benchmark dataset
```

### Hugging Face Hub
```
language:id task:text-generation academic
indonesian academic text generation dataset
bahasa indonesia thesis dataset
```

### GitHub Search
```
"skripsi dataset" JSONL indonesia
"fine-tune" "bahasa indonesia" "akademik" dataset
indonesian academic writing llm dataset
```

### Kaggle Search
```
indonesian academic text dataset
skripsi indonesia NLP dataset
bahasa indonesia academic corpus
```

---

## 📂 Struktur Folder yang Disarankan untuk Training

```
training_data/
├── dataset_skripsi_akademik.jsonl          ← Dataset ini (16 sampel)
├── tambahan_bab1_50_sampel.jsonl           ← Tambahkan nanti
├── tambahan_bab2_50_sampel.jsonl           ← Tambahkan nanti  
├── tambahan_bab3_50_sampel.jsonl           ← Tambahkan nanti
├── tambahan_daftar_pustaka_30_sampel.jsonl ← Tambahkan nanti
└── combined_dataset.jsonl                  ← Gabungkan semua
```

### Script untuk Menggabungkan Dataset
```python
import glob
import json

all_samples = []
for filepath in glob.glob("training_data/*.jsonl"):
    with open(filepath, "r", encoding="utf-8") as f:
        for line in f:
            data = json.loads(line.strip())
            all_samples.append(data)

# Shuffle
import random
random.shuffle(all_samples)

# Simpan
with open("combined_dataset.jsonl", "w", encoding="utf-8") as f:
    for sample in all_samples:
        f.write(json.dumps(sample, ensure_ascii=False) + "\n")

print(f"Total: {len(all_samples)} sampel")
```

---

## 🎓 Rekomendasi untuk Meningkatkan Kualitas Model

### 1. Tambah Variasi Topik Skripsi
Buat lebih banyak sampel untuk topik-topik berikut:
- Sistem Informasi (SIMRS, SIAKAD, SIM perpustakaan)
- Data Science / Big Data Analytics
- Kecerdasan Buatan (AI) untuk berbagai domain
- Pengembangan Aplikasi Mobile (Android/Flutter)
- Keamanan Informasi dan Kriptografi
- Jaringan Komputer dan Telekomunikasi
- Game Development / Virtual Reality

### 2. Variasi Format Output
Tambahkan sampel untuk output format berbeda:
- Tabel perbandingan algoritma
- Kerangka pemikiran penelitian
- Hipotesis penelitian
- Instrumen kuesioner (skala Likert)
- Panduan wawancara semi-terstruktur

### 3. Perbanyak Sampel per Komponen
Target ideal untuk fine-tuning yang solid:
- **Minimum**: 50+ sampel per komponen bab
- **Recommended**: 200+ sampel total
- **Optimal**: 500+ sampel dengan variasi topik tinggi

---

## ⚙️ Konfigurasi Training yang Disarankan

### Untuk Model Kecil (1B-7B parameter)
```yaml
# config.yaml untuk Axolotl
base_model: model_name
model_type: AutoModelForCausalLM
tokenizer_type: AutoTokenizer

datasets:
  - path: dataset_skripsi_akademik.jsonl
    type: chat_template

sequence_len: 8192
train_on_inputs: false
group_by_length: false

learning_rate: 0.0002
num_epochs: 3
micro_batch_size: 2
gradient_accumulation_steps: 4

optimizer: adamw_bnb_8bit
lr_scheduler: cosine
```

### System Prompt yang Disarankan saat Inference
```
Anda adalah asisten akademik ahli yang membantu mahasiswa menulis skripsi 
dalam bahasa Indonesia dengan standar akademik tinggi. Tulis konten dengan 
paragraf panjang, analitis, dan menggunakan bahasa ilmiah yang formal. 
Setiap subbab harus memiliki minimal 500 kata dengan argumentasi yang kuat 
dan referensi teori yang relevan. Gunakan format akademik yang benar termasuk 
penomoran subbab, paragraf yang koheren, dan transisi antar paragraf yang baik.
```

---

> **Catatan**: Dataset ini bersifat sintetis dan dibuat untuk keperluan fine-tuning model bahasa. 
> Pastikan untuk menggabungkan dengan data nyata dari skripsi-skripsi yang telah dipublikasikan 
> untuk hasil yang lebih optimal.

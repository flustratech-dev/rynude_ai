# 📚 Dataset Akademik — Skripsi Indonesia

Folder ini berisi dataset khusus untuk melatih model agar bisa **menulis skripsi akademik bahasa Indonesia** yang lengkap, panjang, dan berkualitas tinggi.

---

## 📁 Isi Folder

| File | Ukuran | Deskripsi |
|------|--------|-----------|
| `dataset_skripsi_FINAL.jsonl` | ~1.1 MB | ⭐ **Gunakan ini** — gabungan semua dataset |
| `dataset_skripsi_v2_100plus.jsonl` | ~993 KB | V2: 240 sampel, 30 judul berbeda |
| `dataset_skripsi_akademik.jsonl` | ~134 KB | V1: 16 sampel, konten panjang per bab |
| `generate_dataset.py` | — | Script generator V1 |
| `generate_dataset_v2.py` | — | Script generator V2 (bisa tambah judul baru) |
| `README_DATASET.md` | — | Dokumentasi lengkap format & cara pakai |

---

## 📊 Statistik Dataset FINAL

| Metrik | Nilai |
|--------|-------|
| Total sampel | **256** |
| Judul skripsi berbeda | **30** |
| Komponen per judul | 8 (Abstrak, Latar Belakang, Rumusan+Tujuan, Tinjauan Teori, Metodologi, Hasil, Kesimpulan, Daftar Pustaka) |
| Total karakter | ~909,384 |
| Bahasa | Bahasa Indonesia Akademik |
| Format | JSONL (OpenAI Chat Format) |

---

## 🎯 Topik Skripsi yang Dicakup

### Machine Learning & Data Science
- Prediksi harga saham (LSTM + Gradient Boosting)
- Klasifikasi penyakit diabetes (KNN + Naive Bayes)
- Deteksi berita hoaks (BERT + BiLSTM)
- Pengenalan wajah untuk presensi (FaceNet + DeepFace)
- Prediksi churn pelanggan (Random Forest, XGBoost, LightGBM)
- Segmentasi pelanggan (K-Means + RFM Analysis)
- Analisis sentimen pariwisata (IndoBERT)

### IoT & Sistem Tertanam
- Smart Greenhouse (ESP32 + Fuzzy Logic)
- Smart Home Energy Management (IoT + Harmony Search)
- Deteksi dini banjir (LoRa WAN + Ultrasonic)

### Sistem Informasi
- Perpustakaan digital (CodeIgniter 4)
- Rekam medis elektronik puskesmas (Laravel)
- ERP manufaktur (Odoo 16)
- Aplikasi POS mobile UMKM (Flutter + Firebase)
- Sistem kepegawaian (Laravel + Vue.js)

### Keamanan Siber
- Penetration testing perbankan (OWASP ZAP + Burp Suite)
- Zero Trust Network Architecture

### Computer Vision
- Deteksi & tracking lalu lintas (YOLOv8 + DeepSORT)
- Klasifikasi plastik daur ulang (EfficientNetB3)
- Pengenalan bahasa isyarat BISINDO (MediaPipe + BiLSTM)
- Deteksi penyakit tanaman padi (MobileNetV3 + EfficientNetB4)

### Lainnya
- Jaringan SDN (Reinforcement Learning)
- GIS pemetaan risiko bencana (AHP)
- Blockchain verifikasi ijazah (Ethereum)
- Blockchain e-voting (Hyperledger)
- Augmented Reality pembelajaran (Unity + Vuforia)
- Optimasi penjadwalan (Algoritma Genetika)
- Optimasi rute pengiriman (Ant Colony Optimization)
- Game edukasi matematika (Unity + Gamification)
- Big Data real-time analytics (Kafka + Spark)

---

## 🚀 Cara Pakai untuk Fine-Tuning Lyric4.8

Gunakan file `dataset_skripsi_FINAL.jsonl` sebagai dataset training.
Lihat `README_DATASET.md` untuk konfigurasi training lengkap.

### Tambah Judul Baru (Jika Ingin Perbesar Dataset)

Edit file `generate_dataset_v2.py`, tambahkan entri baru di `SKRIPSI_LIST`:

```python
{
    "judul": "Judul Skripsi Baru Anda Di Sini",
    "topik": "topik utama penelitian",
    "var_x": "variabel input / data yang digunakan",
    "var_y": "variabel output / hasil yang ingin dicapai",
    "metode": "Nama Algoritma atau Metode",
    "instansi": "Nama Tempat Penelitian",
    "jurusan": "Teknik Informatika",
    "univ": "Nama Universitas",
    "kota": "Kota",
    "pembimbing": "Dr. Nama Pembimbing, M.Kom.",
},
```

Lalu jalankan:
```bash
python generate_dataset_v2.py
```

Setiap judul baru akan otomatis menghasilkan **8 sampel** (semua komponen bab).

---

## ⚠️ Catatan Penting

- Dataset ini bersifat **sintetis** (dibuat oleh AI), bukan dari skripsi nyata
- Untuk hasil fine-tuning terbaik, **gabungkan** dengan skripsi asli dari repository universitas
- Angka-angka dalam hasil penelitian (akurasi, dll.) adalah **ilustrasi realistis**, bukan data nyata

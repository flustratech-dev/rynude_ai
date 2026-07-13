# -*- coding: utf-8 -*-
"""
Judul skripsi TAMBAHAN untuk memperluas cakupan topik dataset akademik.
Dipakai oleh generate_dataset_v2.py (di-extend ke SKRIPSI_LIST). Field WAJIB
sama persis dengan entri di SKRIPSI_LIST: judul, topik, var_x, var_y, metode,
instansi, jurusan, univ, kota, pembimbing.

Tujuan: menambah DIVERSITAS topik (bukan hanya CS/ML) supaya model belajar
menulis skripsi untuk topik apa pun yang diminta pengguna — mengatasi kelemahan
"salah topik" pada model kecil. Yang lama tidak dihapus, ini murni tambahan.
"""

TITLES_EXTRA = [
    # --- Kesehatan / Sistem Informasi Kesehatan ---
    {"judul": "Penerapan Cloud Computing untuk Prediksi Kepuasan Pelanggan Layanan Publik di Bidang Pemerintahan Menggunakan Analisis Sentimen",
     "topik": "cloud computing dan analisis kepuasan layanan publik", "var_x": "data umpan balik masyarakat", "var_y": "tingkat kepuasan layanan publik",
     "metode": "Cloud Computing dan Analisis Sentimen", "instansi": "Dinas Pelayanan Terpadu Satu Pintu", "jurusan": "Sistem Informasi",
     "univ": "Universitas Brawijaya", "kota": "Malang", "pembimbing": "Dr. Retno Wulandari, M.Kom."},
    {"judul": "Sistem Pendukung Keputusan Pemilihan Prioritas Pasien Rawat Inap Menggunakan Metode Simple Additive Weighting di Rumah Sakit Daerah",
     "topik": "sistem pendukung keputusan bidang kesehatan", "var_x": "kriteria kondisi pasien", "var_y": "urutan prioritas penanganan",
     "metode": "Simple Additive Weighting (SAW)", "instansi": "RSUD Kabupaten Sleman", "jurusan": "Sistem Informasi",
     "univ": "Universitas Diponegoro", "kota": "Semarang", "pembimbing": "Dr. Hendra Kusuma, M.T."},
    {"judul": "Klasifikasi Tingkat Keparahan Retinopati Diabetik pada Citra Fundus Menggunakan Transfer Learning EfficientNet",
     "topik": "computer vision citra medis", "var_x": "citra fundus retina", "var_y": "klasifikasi keparahan retinopati",
     "metode": "Transfer Learning EfficientNet", "instansi": "Rumah Sakit Mata Cicendo", "jurusan": "Teknik Informatika",
     "univ": "Universitas Padjadjaran", "kota": "Bandung", "pembimbing": "Dr. Nia Kurniati, M.Kom."},

    # --- Pertanian / Lingkungan ---
    {"judul": "Prediksi Produktivitas Panen Padi Berdasarkan Data Cuaca Menggunakan Algoritma Random Forest dan XGBoost",
     "topik": "machine learning pertanian presisi", "var_x": "data cuaca dan lahan", "var_y": "prediksi hasil panen",
     "metode": "Random Forest dan XGBoost", "instansi": "Dinas Pertanian Kabupaten Karawang", "jurusan": "Teknik Informatika",
     "univ": "Institut Pertanian Bogor", "kota": "Bogor", "pembimbing": "Dr. Agus Setiawan, M.Si."},
    {"judul": "Sistem Monitoring Kualitas Air Tambak Udang Berbasis IoT dengan Sensor pH dan DO serta Notifikasi Telegram",
     "topik": "IoT akuakultur", "var_x": "parameter kualitas air", "var_y": "status kelayakan air tambak",
     "metode": "IoT ESP32 dan Threshold Rule", "instansi": "Kelompok Tambak Udang Situbondo", "jurusan": "Teknik Komputer",
     "univ": "Universitas Jember", "kota": "Jember", "pembimbing": "Dr. Slamet Riyadi, M.T."},
    {"judul": "Klasifikasi Kematangan Buah Kopi Menggunakan Convolutional Neural Network MobileNetV3 pada Perangkat Mobile",
     "topik": "computer vision pertanian", "var_x": "citra buah kopi", "var_y": "klasifikasi tingkat kematangan",
     "metode": "MobileNetV3", "instansi": "Perkebunan Kopi Gayo", "jurusan": "Teknik Informatika",
     "univ": "Universitas Syiah Kuala", "kota": "Banda Aceh", "pembimbing": "Dr. Cut Meurah, M.Kom."},

    # --- Pendidikan ---
    {"judul": "Analisis Faktor Keberhasilan Pembelajaran Daring Menggunakan Structural Equation Modeling pada Mahasiswa Perguruan Tinggi",
     "topik": "analisis statistik pendidikan", "var_x": "faktor kesiapan dan motivasi", "var_y": "keberhasilan pembelajaran daring",
     "metode": "Structural Equation Modeling (SEM-PLS)", "instansi": "Fakultas Ekonomi Universitas Negeri", "jurusan": "Sistem Informasi",
     "univ": "Universitas Negeri Malang", "kota": "Malang", "pembimbing": "Dr. Sri Handayani, M.Pd."},
    {"judul": "Sistem Rekomendasi Materi Belajar Adaptif Menggunakan Collaborative Filtering pada Platform E-Learning",
     "topik": "sistem rekomendasi pendidikan", "var_x": "riwayat aktivitas belajar", "var_y": "rekomendasi materi",
     "metode": "Collaborative Filtering", "instansi": "Platform E-Learning Ruang Belajar", "jurusan": "Teknik Informatika",
     "univ": "Universitas Sebelas Maret", "kota": "Surakarta", "pembimbing": "Dr. Wahyu Nugroho, M.Cs."},

    # --- Keuangan / Ekonomi ---
    {"judul": "Deteksi Transaksi Penipuan Kartu Kredit Menggunakan Isolation Forest dan Autoencoder pada Data Tidak Seimbang",
     "topik": "machine learning deteksi anomali keuangan", "var_x": "data transaksi kartu kredit", "var_y": "deteksi transaksi penipuan",
     "metode": "Isolation Forest dan Autoencoder", "instansi": "Bank Nasional Indonesia", "jurusan": "Teknik Informatika",
     "univ": "Universitas Airlangga", "kota": "Surabaya", "pembimbing": "Dr. Rina Marlina, M.Kom."},
    {"judul": "Analisis Pengaruh Literasi Keuangan terhadap Keputusan Investasi Menggunakan Regresi Linear Berganda pada Generasi Milenial",
     "topik": "analisis statistik ekonomi", "var_x": "tingkat literasi keuangan", "var_y": "keputusan investasi",
     "metode": "Regresi Linear Berganda", "instansi": "Komunitas Investor Muda", "jurusan": "Manajemen",
     "univ": "Universitas Padjadjaran", "kota": "Bandung", "pembimbing": "Dr. Dedi Kurnia, M.M."},

    # --- Pemerintahan / Layanan Publik ---
    {"judul": "Perancangan Sistem Informasi Pengaduan Masyarakat Berbasis Web dengan Klasifikasi Otomatis Menggunakan Naive Bayes",
     "topik": "sistem informasi layanan publik", "var_x": "teks pengaduan masyarakat", "var_y": "kategori dan prioritas pengaduan",
     "metode": "Naive Bayes dan Framework Laravel", "instansi": "Pemerintah Kota Bandung", "jurusan": "Sistem Informasi",
     "univ": "Universitas Komputer Indonesia", "kota": "Bandung", "pembimbing": "Dr. Taufik Hidayat, M.Kom."},
    {"judul": "Evaluasi Kualitas Layanan E-Government Menggunakan Metode WebQual 4.0 dan Importance Performance Analysis",
     "topik": "evaluasi sistem informasi pemerintahan", "var_x": "dimensi kualitas layanan", "var_y": "tingkat kepuasan pengguna",
     "metode": "WebQual 4.0 dan IPA", "instansi": "Portal Layanan Pemerintah Provinsi", "jurusan": "Sistem Informasi",
     "univ": "Universitas Sriwijaya", "kota": "Palembang", "pembimbing": "Dr. Yulia Sari, M.T."},

    # --- Logistik / Transportasi ---
    {"judul": "Optimasi Penempatan Gudang Distribusi Menggunakan Algoritma Particle Swarm Optimization untuk Efisiensi Rantai Pasok",
     "topik": "optimasi logistik", "var_x": "data lokasi dan permintaan", "var_y": "lokasi gudang optimal",
     "metode": "Particle Swarm Optimization", "instansi": "PT. Logistik Nusantara", "jurusan": "Teknik Industri",
     "univ": "Institut Teknologi Bandung", "kota": "Bandung", "pembimbing": "Dr. Andi Wijaya, M.T."},
    {"judul": "Prediksi Kepadatan Penumpang Transportasi Umum Menggunakan Long Short-Term Memory Berbasis Data Historis",
     "topik": "machine learning transportasi", "var_x": "data historis penumpang", "var_y": "prediksi kepadatan penumpang",
     "metode": "Long Short-Term Memory (LSTM)", "instansi": "Perusahaan Transportasi Umum Kota", "jurusan": "Teknik Informatika",
     "univ": "Universitas Gadjah Mada", "kota": "Yogyakarta", "pembimbing": "Dr. Bagus Prasetyo, M.Cs."},

    # --- Energi ---
    {"judul": "Peramalan Beban Listrik Jangka Pendek Menggunakan Gated Recurrent Unit pada Sistem Kelistrikan Regional",
     "topik": "machine learning energi", "var_x": "data beban listrik historis", "var_y": "peramalan beban listrik",
     "metode": "Gated Recurrent Unit (GRU)", "instansi": "PT. PLN Distribusi Jawa Timur", "jurusan": "Teknik Elektro",
     "univ": "Institut Teknologi Sepuluh Nopember", "kota": "Surabaya", "pembimbing": "Dr. Eko Prasetyo, M.T."},
    {"judul": "Sistem Monitoring dan Optimasi Panel Surya Berbasis IoT dengan Algoritma Maximum Power Point Tracking",
     "topik": "IoT energi terbarukan", "var_x": "data iradiasi dan tegangan panel", "var_y": "efisiensi daya panel surya",
     "metode": "IoT dan MPPT Perturb and Observe", "instansi": "Laboratorium Energi Terbarukan", "jurusan": "Teknik Elektro",
     "univ": "Universitas Hasanuddin", "kota": "Makassar", "pembimbing": "Dr. Muh. Arsyad, M.T."},

    # --- Keamanan Siber ---
    {"judul": "Deteksi Serangan Distributed Denial of Service Menggunakan Deep Neural Network pada Dataset CICDDoS2019",
     "topik": "keamanan siber deteksi intrusi", "var_x": "data lalu lintas jaringan", "var_y": "deteksi serangan DDoS",
     "metode": "Deep Neural Network", "instansi": "Pusat Operasi Keamanan Jaringan", "jurusan": "Teknik Informatika",
     "univ": "Universitas Telkom", "kota": "Bandung", "pembimbing": "Dr. Fauzan Akbar, M.T."},
    {"judul": "Analisis Malware Android Menggunakan Ekstraksi Fitur Statis dan Algoritma Support Vector Machine",
     "topik": "keamanan siber analisis malware", "var_x": "fitur permission dan API call", "var_y": "klasifikasi malware vs benign",
     "metode": "Support Vector Machine", "instansi": "Laboratorium Keamanan Siber", "jurusan": "Teknik Informatika",
     "univ": "Universitas Bina Nusantara", "kota": "Jakarta", "pembimbing": "Dr. Ivan Halim, M.Kom."},

    # --- NLP / Teks ---
    {"judul": "Peringkasan Otomatis Dokumen Berita Berbahasa Indonesia Menggunakan Model Transformer T5",
     "topik": "natural language processing peringkasan teks", "var_x": "dokumen berita", "var_y": "ringkasan otomatis",
     "metode": "Transformer T5", "instansi": "Portal Berita Nasional", "jurusan": "Teknik Informatika",
     "univ": "Universitas Indonesia", "kota": "Depok", "pembimbing": "Dr. Laksmi Dewi, M.Sc."},
    {"judul": "Sistem Chatbot Layanan Akademik Menggunakan Retrieval Augmented Generation dan Model Bahasa Lokal",
     "topik": "natural language processing chatbot", "var_x": "pertanyaan mahasiswa", "var_y": "jawaban layanan akademik",
     "metode": "Retrieval Augmented Generation (RAG)", "instansi": "Biro Akademik Universitas", "jurusan": "Teknik Informatika",
     "univ": "Universitas Amikom Yogyakarta", "kota": "Yogyakarta", "pembimbing": "Dr. Kusrini, M.Kom."},

    # --- Sosial / Kualitatif ---
    {"judul": "Analisis Penerimaan Teknologi Dompet Digital Menggunakan Technology Acceptance Model pada Pelaku UMKM",
     "topik": "analisis penerimaan teknologi", "var_x": "persepsi kemudahan dan manfaat", "var_y": "minat penggunaan dompet digital",
     "metode": "Technology Acceptance Model (TAM)", "instansi": "Komunitas UMKM Digital", "jurusan": "Sistem Informasi",
     "univ": "Universitas Negeri Surabaya", "kota": "Surabaya", "pembimbing": "Dr. Anita Rahman, M.M."},
    {"judul": "Studi Kasus Implementasi Manajemen Risiko Teknologi Informasi Menggunakan Kerangka Kerja COBIT 2019 di Perusahaan Perbankan",
     "topik": "tata kelola teknologi informasi", "var_x": "proses tata kelola TI", "var_y": "tingkat kematangan manajemen risiko",
     "metode": "COBIT 2019 dan Studi Kasus", "instansi": "Bank Pembangunan Daerah", "jurusan": "Sistem Informasi",
     "univ": "Universitas Gunadarma", "kota": "Depok", "pembimbing": "Dr. Bimo Sunarfri, M.Kom."},

    # --- Computer Vision tambahan ---
    {"judul": "Deteksi Penggunaan Alat Pelindung Diri Pekerja Konstruksi Menggunakan YOLOv8 untuk Pengawasan Keselamatan Kerja",
     "topik": "computer vision keselamatan kerja", "var_x": "citra CCTV area konstruksi", "var_y": "deteksi kepatuhan APD",
     "metode": "YOLOv8", "instansi": "Proyek Konstruksi Gedung Bertingkat", "jurusan": "Teknik Informatika",
     "univ": "Universitas Mercu Buana", "kota": "Jakarta", "pembimbing": "Dr. Harco Leslie, M.Kom."},
    {"judul": "Segmentasi Citra Penginderaan Jauh untuk Pemetaan Lahan Sawah Menggunakan Arsitektur U-Net",
     "topik": "computer vision penginderaan jauh", "var_x": "citra satelit multispektral", "var_y": "peta segmentasi lahan sawah",
     "metode": "U-Net", "instansi": "Badan Informasi Geospasial", "jurusan": "Teknik Geomatika",
     "univ": "Institut Teknologi Sepuluh Nopember", "kota": "Surabaya", "pembimbing": "Dr. Lalu Muhamad, M.T."},
]

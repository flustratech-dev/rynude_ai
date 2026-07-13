"""
Generator Dataset Skripsi Akademik Indonesia - Versi Besar (100+ sampel)
Setiap judul berbeda untuk melatih generalisasi model.
"""
import json
import random
import os

output_path = r'C:\Users\Ryan\.gemini\antigravity\brain\30f49fa2-d53b-4eaa-a7f9-7f936fbceeff\dataset_skripsi_v2_100plus.jsonl'

SYSTEM_PROMPT = (
    "Anda adalah asisten akademik ahli yang membantu mahasiswa menulis skripsi dalam bahasa Indonesia "
    "dengan standar akademik tinggi. Tulis konten dengan paragraf panjang, analitis, dan menggunakan "
    "bahasa ilmiah yang formal. Setiap subbab harus memiliki minimal 400 kata dengan argumentasi yang "
    "kuat dan referensi teori yang relevan. Gunakan penomoran subbab yang benar dan pastikan transisi "
    "antar paragraf mengalir dengan baik."
)

dataset = []

# =============================================================================
# KUMPULAN JUDUL SKRIPSI BERAGAM
# Format: (judul, topik_utama, variabel_x, variabel_y, metode, instansi)
# =============================================================================

SKRIPSI_LIST = [
    # --- Machine Learning & Data Science ---
    {
        "judul": "Prediksi Harga Saham Menggunakan Long Short-Term Memory (LSTM) dan Gradient Boosting pada Indeks LQ45 Bursa Efek Indonesia",
        "topik": "machine learning prediksi harga saham",
        "var_x": "data historis harga saham",
        "var_y": "prediksi harga saham",
        "metode": "LSTM dan Gradient Boosting",
        "instansi": "Bursa Efek Indonesia",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Gadjah Mada",
        "kota": "Yogyakarta",
        "pembimbing": "Dr. Sari Dewi, M.Kom.",
    },
    {
        "judul": "Klasifikasi Penyakit Diabetes Mellitus Menggunakan Algoritma K-Nearest Neighbor dan Naive Bayes dengan Oversampling SMOTE",
        "topik": "machine learning klasifikasi penyakit diabetes",
        "var_x": "data rekam medis pasien",
        "var_y": "klasifikasi risiko diabetes",
        "metode": "K-Nearest Neighbor dan Naive Bayes dengan SMOTE",
        "instansi": "RSUD Dr. Soetomo Surabaya",
        "jurusan": "Teknik Informatika",
        "univ": "Institut Teknologi Sepuluh Nopember",
        "kota": "Surabaya",
        "pembimbing": "Prof. Dr. Bambang Santoso, M.T.",
    },
    {
        "judul": "Deteksi Berita Hoaks Berbahasa Indonesia Menggunakan BERT dan BiLSTM dengan Dataset IndoNLU",
        "topik": "natural language processing deteksi hoaks",
        "var_x": "teks berita berbahasa Indonesia",
        "var_y": "klasifikasi hoaks vs fakta",
        "metode": "BERT dan BiLSTM",
        "instansi": "Media Sosial Twitter dan Facebook",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Indonesia",
        "kota": "Depok",
        "pembimbing": "Dr. Ahmad Rizki, M.Sc.",
    },
    {
        "judul": "Sistem Pengenalan Wajah Real-Time Menggunakan FaceNet dan DeepFace untuk Presensi Karyawan di PT. Industri Maju Bersama",
        "topik": "computer vision pengenalan wajah presensi",
        "var_x": "citra wajah karyawan",
        "var_y": "identifikasi dan verifikasi identitas",
        "metode": "FaceNet dan DeepFace",
        "instansi": "PT. Industri Maju Bersama",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Brawijaya",
        "kota": "Malang",
        "pembimbing": "Dr. Hendra Kusuma, M.Kom.",
    },
    {
        "judul": "Prediksi Churn Pelanggan Telekomunikasi Menggunakan Ensemble Learning: Random Forest, XGBoost, dan LightGBM",
        "topik": "machine learning prediksi churn pelanggan telekomunikasi",
        "var_x": "data perilaku pelanggan telekomunikasi",
        "var_y": "prediksi kemungkinan churn pelanggan",
        "metode": "Random Forest, XGBoost, dan LightGBM",
        "instansi": "PT. Telekomunikasi Nusantara",
        "jurusan": "Sistem Informasi",
        "univ": "Universitas Bina Nusantara",
        "kota": "Jakarta",
        "pembimbing": "Dr. Wahyu Pratama, M.T.I.",
    },
    {
        "judul": "Segmentasi Pelanggan E-Commerce Menggunakan K-Means Clustering dan RFM Analysis untuk Strategi Pemasaran Berbasis Data",
        "topik": "data mining segmentasi pelanggan e-commerce",
        "var_x": "data transaksi pelanggan",
        "var_y": "segmen pelanggan berdasarkan perilaku pembelian",
        "metode": "K-Means Clustering dan RFM Analysis",
        "instansi": "PT. Niaga Digital Indonesia",
        "jurusan": "Sistem Informasi",
        "univ": "Universitas Diponegoro",
        "kota": "Semarang",
        "pembimbing": "Dr. Rina Widyawati, M.Kom.",
    },
    {
        "judul": "Analisis Sentimen Ulasan Wisatawan terhadap Destinasi Pariwisata Bali Menggunakan IndoBERT dan Aspect-Based Sentiment Analysis",
        "topik": "NLP analisis sentimen pariwisata",
        "var_x": "ulasan wisatawan di platform TripAdvisor dan Google Maps",
        "var_y": "sentimen dan aspek kepuasan wisatawan",
        "metode": "IndoBERT dan Aspect-Based Sentiment Analysis",
        "instansi": "Dinas Pariwisata Provinsi Bali",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Udayana",
        "kota": "Denpasar",
        "pembimbing": "Dr. I Made Sukarsa, M.T.",
    },
    # --- IoT dan Sistem Tertanam ---
    {
        "judul": "Perancangan Sistem Smart Greenhouse Berbasis IoT dengan NodeMCU ESP32 dan Kontrol Otomatis Menggunakan Fuzzy Logic",
        "topik": "IoT smart greenhouse pertanian",
        "var_x": "sensor suhu, kelembaban, dan pH tanah",
        "var_y": "otomasi kontrol kondisi greenhouse",
        "metode": "IoT dengan ESP32 dan Fuzzy Logic",
        "instansi": "Kelompok Tani Maju Sejahtera Kabupaten Malang",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Brawijaya",
        "kota": "Malang",
        "pembimbing": "Dr. Fitri Utaminingrum, M.T.",
    },
    {
        "judul": "Implementasi Smart Home Energy Management System Berbasis IoT dan Algoritma Harmony Search untuk Optimasi Konsumsi Daya Listrik",
        "topik": "IoT smart home manajemen energi",
        "var_x": "data konsumsi daya perangkat elektronik rumah tangga",
        "var_y": "optimasi jadwal penggunaan energi listrik",
        "metode": "IoT dengan Raspberry Pi dan Harmony Search Algorithm",
        "instansi": "Perumahan Griya Asri Residence Bandung",
        "jurusan": "Teknik Komputer",
        "univ": "Universitas Telkom",
        "kota": "Bandung",
        "pembimbing": "Dr. Muhamad Ary Murti, M.T.",
    },
    {
        "judul": "Sistem Deteksi Dini Banjir Berbasis IoT dengan Sensor Ultrasonic dan LoRa WAN untuk Monitoring Tinggi Muka Air Sungai Ciliwung",
        "topik": "IoT deteksi dini banjir monitoring",
        "var_x": "data tinggi muka air sungai",
        "var_y": "peringatan dini banjir real-time",
        "metode": "IoT dengan sensor ultrasonic dan LoRa WAN",
        "instansi": "BPBD DKI Jakarta",
        "jurusan": "Teknik Komputer",
        "univ": "Universitas Indonesia",
        "kota": "Depok",
        "pembimbing": "Dr. Riri Fitri Sari, M.M., M.Sc.",
    },
    # --- Sistem Informasi ---
    {
        "judul": "Pengembangan Sistem Informasi Manajemen Perpustakaan Digital Terintegrasi Berbasis Web Menggunakan Framework CodeIgniter 4 di Universitas Nusantara",
        "topik": "sistem informasi perpustakaan digital",
        "var_x": "proses manajemen koleksi dan sirkulasi buku",
        "var_y": "efisiensi layanan perpustakaan",
        "metode": "SDLC Agile dengan Framework CodeIgniter 4",
        "instansi": "Perpustakaan Universitas Nusantara",
        "jurusan": "Sistem Informasi",
        "univ": "Universitas Nusantara",
        "kota": "Bandung",
        "pembimbing": "Dr. Dian Kusuma, M.Kom.",
    },
    {
        "judul": "Perancangan Sistem Informasi Rekam Medis Elektronik (RME) Terintegrasi Berbasis Web di Puskesmas Kecamatan Koja Jakarta Utara",
        "topik": "sistem informasi rekam medis elektronik puskesmas",
        "var_x": "proses pencatatan rekam medis pasien",
        "var_y": "efisiensi dan akurasi rekam medis",
        "metode": "Waterfall SDLC dengan Laravel 10",
        "instansi": "Puskesmas Kecamatan Koja Jakarta Utara",
        "jurusan": "Sistem Informasi",
        "univ": "Universitas Trisakti",
        "kota": "Jakarta",
        "pembimbing": "Dr. Budi Hermawan, M.M., M.Kom.",
    },
    {
        "judul": "Implementasi Enterprise Resource Planning (ERP) Berbasis Odoo 16 untuk Manajemen Produksi dan Inventori di CV. Karya Mandiri",
        "topik": "ERP implementasi manufaktur manajemen produksi",
        "var_x": "proses produksi dan manajemen inventori",
        "var_y": "efisiensi operasional dan akurasi data inventori",
        "metode": "Implementasi Odoo 16 dengan metodologi OUM",
        "instansi": "CV. Karya Mandiri",
        "jurusan": "Sistem Informasi",
        "univ": "Universitas Gunadarma",
        "kota": "Jakarta",
        "pembimbing": "Dr. Imam Subaweh, M.Kom.",
    },
    {
        "judul": "Pengembangan Aplikasi Mobile Point of Sale (POS) Berbasis Android dengan Flutter dan Firebase untuk UMKM Kuliner",
        "topik": "pengembangan aplikasi mobile POS UMKM",
        "var_x": "proses transaksi penjualan UMKM kuliner",
        "var_y": "efisiensi dan kecepatan transaksi point of sale",
        "metode": "Flutter + Firebase dengan Agile Scrum",
        "instansi": "UMKM Kuliner Binaan Dinas Koperasi Kota Yogyakarta",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Atma Jaya Yogyakarta",
        "kota": "Yogyakarta",
        "pembimbing": "Dr. Yudi Prayudi, M.Kom.",
    },
    # --- Keamanan Siber ---
    {
        "judul": "Analisis Kerentanan Keamanan Aplikasi Web Perbankan Digital Menggunakan OWASP ZAP dan Burp Suite dengan Pendekatan Penetration Testing",
        "topik": "keamanan siber penetration testing aplikasi web perbankan",
        "var_x": "kerentanan keamanan aplikasi web perbankan",
        "var_y": "tingkat keamanan sistem perbankan digital",
        "metode": "Penetration Testing dengan OWASP ZAP dan Burp Suite",
        "instansi": "Bank Rakyat Nusantara",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Padjadjaran",
        "kota": "Bandung",
        "pembimbing": "Dr. Setiadi Yazid, M.Sc.",
    },
    {
        "judul": "Implementasi Zero Trust Network Architecture untuk Meningkatkan Keamanan Infrastruktur Jaringan di PT. Energi Nusantara",
        "topik": "keamanan jaringan zero trust architecture",
        "var_x": "konfigurasi arsitektur jaringan eksisting",
        "var_y": "tingkat keamanan dan kontrol akses jaringan",
        "metode": "Implementasi Zero Trust dengan BeyondCorp",
        "instansi": "PT. Energi Nusantara",
        "jurusan": "Teknik Informatika",
        "univ": "Institut Teknologi Bandung",
        "kota": "Bandung",
        "pembimbing": "Dr. Budi Rahardjo, M.Sc.",
    },
    # --- Jaringan Komputer ---
    {
        "judul": "Optimasi Kinerja Jaringan Software-Defined Networking (SDN) Menggunakan Algoritma Load Balancing Berbasis Reinforcement Learning",
        "topik": "jaringan SDN optimasi load balancing",
        "var_x": "lalu lintas jaringan SDN",
        "var_y": "kinerja dan latensi jaringan",
        "metode": "SDN dengan OpenFlow dan Reinforcement Learning",
        "instansi": "Laboratorium Jaringan Komputer FMIPA UI",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Indonesia",
        "kota": "Depok",
        "pembimbing": "Dr. Harry Budi Santoso, M.Kom.",
    },
    {
        "judul": "Perancangan dan Implementasi Network Monitoring System (NMS) Berbasis Grafana dan Prometheus untuk Infrastruktur Cloud Hybrid",
        "topik": "jaringan monitoring sistem cloud hybrid",
        "var_x": "metrik kinerja infrastruktur cloud hybrid",
        "var_y": "visibilitas dan proaktivitas monitoring jaringan",
        "metode": "Implementasi Grafana, Prometheus, dan Alertmanager",
        "instansi": "PT. DataCenter Indonesia",
        "jurusan": "Teknik Komputer",
        "univ": "Universitas Telkom",
        "kota": "Bandung",
        "pembimbing": "Dr. Achmad Rizal, M.T.",
    },
    # --- GIS dan Remote Sensing ---
    {
        "judul": "Pemetaan Risiko Bencana Tanah Longsor Menggunakan Sistem Informasi Geografis (SIG) dan Metode Analytical Hierarchy Process di Kabupaten Bogor",
        "topik": "GIS pemetaan risiko bencana tanah longsor",
        "var_x": "parameter faktor risiko tanah longsor",
        "var_y": "peta zonasi risiko tanah longsor",
        "metode": "GIS dengan ArcGIS dan AHP",
        "instansi": "BPBD Kabupaten Bogor",
        "jurusan": "Teknik Informatika",
        "univ": "Institut Pertanian Bogor",
        "kota": "Bogor",
        "pembimbing": "Dr. Lailan Syaufina, M.Sc.",
    },
    {
        "judul": "Deteksi Perubahan Tutupan Lahan Hutan Kalimantan Menggunakan Citra Satelit Sentinel-2 dan Algoritma Random Forest",
        "topik": "remote sensing deteksi perubahan tutupan lahan hutan",
        "var_x": "citra satelit Sentinel-2 multitemporal",
        "var_y": "perubahan tutupan lahan hutan Kalimantan",
        "metode": "Random Forest dengan Google Earth Engine",
        "instansi": "Kementerian Lingkungan Hidup dan Kehutanan",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Lambung Mangkurat",
        "kota": "Banjarmasin",
        "pembimbing": "Dr. Ichsan Ridwan, M.T.",
    },
    # --- Blockchain dan Keamanan Data ---
    {
        "judul": "Implementasi Sistem Verifikasi Sertifikat Akademik Berbasis Blockchain Ethereum untuk Mencegah Pemalsuan Ijazah di Indonesia",
        "topik": "blockchain verifikasi sertifikat akademik",
        "var_x": "proses verifikasi keaslian ijazah",
        "var_y": "keamanan dan integritas sertifikat akademik",
        "metode": "Blockchain Ethereum dengan Smart Contract Solidity",
        "instansi": "Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Indonesia",
        "kota": "Depok",
        "pembimbing": "Dr. Wisnu Jatmiko, M.T.",
    },
    {
        "judul": "Pengembangan Sistem Voting Elektronik Berbasis Blockchain Hyperledger untuk Pemilihan Ketua BEM Universitas yang Transparan dan Aman",
        "topik": "blockchain e-voting sistem pemilihan elektronik",
        "var_x": "proses pemilihan umum berbasis digital",
        "var_y": "keamanan, transparansi, dan integritas hasil voting",
        "metode": "Blockchain Hyperledger Fabric dengan Chaincode Go",
        "instansi": "Universitas Teknologi Nasional",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Teknologi Nasional",
        "kota": "Bandung",
        "pembimbing": "Dr. Teddy Mantoro, M.Sc.",
    },
    # --- Computer Vision ---
    {
        "judul": "Deteksi dan Penghitungan Kepadatan Lalu Lintas Otomatis Menggunakan YOLOv8 dan DeepSORT pada Sistem CCTV Persimpangan",
        "topik": "computer vision deteksi kendaraan lalu lintas",
        "var_x": "rekaman video CCTV persimpangan lalu lintas",
        "var_y": "deteksi, penghitungan, dan tracking kendaraan",
        "metode": "YOLOv8 dan DeepSORT multi-object tracking",
        "instansi": "Dinas Perhubungan Kota Jakarta",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Tarumanagara",
        "kota": "Jakarta",
        "pembimbing": "Dr. Pitoyo Hartono, M.Eng.",
    },
    {
        "judul": "Klasifikasi Jenis Plastik Daur Ulang Menggunakan Convolutional Neural Network untuk Mendukung Program Ekonomi Sirkular",
        "topik": "computer vision klasifikasi plastik daur ulang",
        "var_x": "citra sampah plastik berbagai jenis",
        "var_y": "klasifikasi jenis plastik (PET, HDPE, PVC, LDPE, PP, PS)",
        "metode": "CNN EfficientNetB3 dengan transfer learning",
        "instansi": "Bank Sampah Nasional Indonesia",
        "jurusan": "Teknik Informatika",
        "univ": "Institut Teknologi Bandung",
        "kota": "Bandung",
        "pembimbing": "Dr. Ary Setijadi Prihatmanto, M.T.",
    },
    {
        "judul": "Pengenalan Bahasa Isyarat BISINDO Menggunakan MediaPipe Holistic dan Bidirectional LSTM untuk Membantu Komunikasi Tunarungu",
        "topik": "computer vision pengenalan bahasa isyarat",
        "var_x": "gesture dan gerakan tangan bahasa isyarat BISINDO",
        "var_y": "pengenalan dan terjemahan kata dalam BISINDO",
        "metode": "MediaPipe Holistic dan BiLSTM",
        "instansi": "SLB Negeri 1 Bandung",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Telkom",
        "kota": "Bandung",
        "pembimbing": "Dr. Angelina Prima Kurniati, M.T.",
    },
    # --- Augmented Reality / VR ---
    {
        "judul": "Pengembangan Aplikasi Augmented Reality untuk Media Pembelajaran Anatomi Tubuh Manusia Berbasis Android Menggunakan Vuforia SDK",
        "topik": "augmented reality media pembelajaran anatomi",
        "var_x": "media pembelajaran anatomi tubuh manusia konvensional",
        "var_y": "pemahaman dan motivasi belajar siswa",
        "metode": "Augmented Reality dengan Unity dan Vuforia SDK",
        "instansi": "SMA Negeri 5 Surabaya",
        "jurusan": "Teknik Informatika",
        "univ": "Institut Teknologi Sepuluh Nopember",
        "kota": "Surabaya",
        "pembimbing": "Dr. Tohari Ahmad, M.Inf.Tech.",
    },
    # --- Optimasi & Algoritma ---
    {
        "judul": "Optimasi Penjadwalan Mata Kuliah Universitas Menggunakan Algoritma Genetika dan Simulated Annealing",
        "topik": "optimasi penjadwalan algoritma metaheuristik",
        "var_x": "kendala dan preferensi penjadwalan mata kuliah",
        "var_y": "jadwal kuliah yang optimal dan bebas konflik",
        "metode": "Algoritma Genetika dan Simulated Annealing",
        "instansi": "Universitas Nusa Bangsa",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Nusa Bangsa",
        "kota": "Bogor",
        "pembimbing": "Dr. Imas Sukaesih Sitanggang, M.Si.",
    },
    {
        "judul": "Penerapan Algoritma Ant Colony Optimization untuk Optimasi Rute Pengiriman Barang Last-Mile Delivery di Kota Surabaya",
        "topik": "optimasi rute pengiriman logistik last-mile",
        "var_x": "data titik pengiriman dan jaringan jalan",
        "var_y": "rute pengiriman optimal dengan jarak dan waktu minimum",
        "metode": "Ant Colony Optimization (ACO)",
        "instansi": "PT. Ekspedisi Cepat Nusantara",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Surabaya",
        "kota": "Surabaya",
        "pembimbing": "Dr. Roy Rudolf Huizen, M.T.",
    },
    # --- Game Development ---
    {
        "judul": "Pengembangan Game Edukasi Berbasis Mobile untuk Pembelajaran Matematika Dasar Siswa SD Menggunakan Unity dan Prinsip Gamification",
        "topik": "game edukasi mobile pembelajaran matematika gamification",
        "var_x": "metode pembelajaran matematika konvensional",
        "var_y": "motivasi dan hasil belajar matematika siswa SD",
        "metode": "Game development Unity dengan prinsip Gamification",
        "instansi": "SD Negeri 01 Cilandak Jakarta Selatan",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Mercu Buana",
        "kota": "Jakarta",
        "pembimbing": "Dr. Samsul Arifin, M.Kom.",
    },
    # --- Big Data ---
    {
        "judul": "Implementasi Arsitektur Lambda untuk Real-Time Analytics Data Transaksi E-Commerce Menggunakan Apache Kafka, Spark, dan Cassandra",
        "topik": "big data real-time analytics e-commerce",
        "var_x": "stream data transaksi e-commerce",
        "var_y": "analitik real-time dan laporan bisnis",
        "metode": "Lambda Architecture dengan Kafka, Spark, dan Cassandra",
        "instansi": "PT. Commerce Digital Indonesia",
        "jurusan": "Teknik Informatika",
        "univ": "Universitas Bina Nusantara",
        "kota": "Jakarta",
        "pembimbing": "Dr. Fredy Purnomo, M.Kom.",
    },
]

# =============================================================================
# TEMPLATE GENERATOR UNTUK SETIAP KOMPONEN SKRIPSI
# =============================================================================

def gen_abstrak(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis Abstrak (250-400 kata) untuk skripsi berjudul: \"{s['judul']}\". "
                f"Sertakan: latar belakang singkat, tujuan penelitian, metode yang digunakan "
                f"({s['metode']}), hasil penelitian (buat angka yang realistis), dan simpulan. "
                f"Akhiri dengan Kata Kunci minimal 5 kata kunci."
            )},
            {"role": "assistant", "content": (
                f"ABSTRAK\n\n"
                f"{s['pembimbing'].split(',')[0].replace('Dr. ', '').replace('Prof. Dr. ', '').replace('Prof. ', '')}, "
                f"[Nama Mahasiswa]. 2024. \"{s['judul']}\". "
                f"Skripsi, Program Studi {s['jurusan']}, {s['univ']}, {s['kota']}. "
                f"Pembimbing: {s['pembimbing']}\n\n"

                f"Perkembangan teknologi informasi yang pesat telah membuka peluang besar "
                f"dalam penerapan {s['topik']} untuk memecahkan berbagai permasalahan nyata. "
                f"{s['instansi']} sebagai salah satu institusi yang bergerak di bidang terkait "
                f"menghadapi tantangan dalam {s['var_x']} yang belum terkelola secara optimal. "
                f"Penelitian ini hadir sebagai solusi inovatif untuk menjawab tantangan tersebut "
                f"melalui pendekatan komputasional yang berbasis data.\n\n"

                f"Tujuan penelitian ini adalah merancang dan mengimplementasikan sistem berbasis "
                f"{s['metode']} yang mampu melakukan {s['var_y']} secara akurat dan efisien. "
                f"Penelitian menggunakan pendekatan eksperimental dengan dataset yang dikumpulkan "
                f"dari {s['instansi']} selama periode penelitian. Proses pengembangan sistem "
                f"meliputi pengumpulan data, preprocessing, perancangan model, pelatihan, "
                f"evaluasi, dan analisis hasil.\n\n"

                f"Hasil penelitian menunjukkan bahwa sistem yang dikembangkan menggunakan "
                f"{s['metode']} berhasil mencapai performa yang signifikan. Model terbaik "
                f"mencapai akurasi sebesar 93,4% dengan nilai F1-score 0,921 pada data uji "
                f"yang independen. Sistem yang dibangun mampu memproses data secara real-time "
                f"dengan latensi rata-rata 287 milidetik, memenuhi persyaratan kinerja untuk "
                f"implementasi produksi. Dibandingkan dengan metode baseline yang ada, "
                f"pendekatan yang diusulkan memberikan peningkatan akurasi sebesar 11,3 poin "
                f"persentase, membuktikan keunggulan dan efektivitasnya.\n\n"

                f"Kesimpulan penelitian ini menunjukkan bahwa {s['metode']} merupakan "
                f"pendekatan yang efektif dan superior untuk {s['topik']}. Sistem yang "
                f"dikembangkan memiliki potensi besar untuk diimplementasikan secara nyata "
                f"di {s['instansi']} dan dapat diadaptasi untuk konteks serupa di organisasi "
                f"lain.\n\n"
                f"Kata Kunci: {', '.join(s['topik'].split()[:5]).title()}, Machine Learning, "
                f"Python, Deep Learning, Evaluasi Model"
            )}
        ]
    }


def gen_bab1_latarbelakang(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis subbab 1.1 Latar Belakang (minimal 600 kata, akademik dan analitis) "
                f"untuk skripsi berjudul: \"{s['judul']}\". "
                f"Topik penelitian: {s['topik']}. Instansi: {s['instansi']}. "
                f"Jelaskan: konteks global dan lokal masalah, data statistik relevan, "
                f"permasalahan spesifik di {s['instansi']}, gap penelitian, dan motivasi penelitian."
            )},
            {"role": "assistant", "content": (
                f"1.1 Latar Belakang\n\n"
                f"Perkembangan teknologi informasi dan komunikasi yang berlangsung secara eksponensial "
                f"dalam era digital ini telah membawa implikasi yang sangat mendalam bagi berbagai "
                f"sektor kehidupan. Di tengah arus transformasi digital yang tak terbendung ini, "
                f"bidang {s['topik']} muncul sebagai salah satu domain teknologi yang paling "
                f"menjanjikan sekaligus paling menantang untuk dikembangkan dan diimplementasikan "
                f"dalam konteks nyata. Kemampuan untuk memanfaatkan {s['var_x']} secara cerdas "
                f"dan efisien kini menjadi keunggulan kompetitif yang menentukan bagi organisasi "
                f"maupun institusi yang beroperasi dalam lanskap digital yang semakin kompetitif.\n\n"

                f"Secara global, pertumbuhan penerapan teknologi dalam bidang {s['topik']} "
                f"menunjukkan trajektori yang sangat signifikan. Berbagai lembaga riset dan "
                f"konsultan teknologi terkemuka seperti Gartner, McKinsey Global Institute, "
                f"dan IDC secara konsisten melaporkan peningkatan investasi yang masif dalam "
                f"pengembangan sistem berbasis {s['metode'].split(' dan ')[0]}. Menurut laporan "
                f"terbaru dari International Data Corporation (IDC), pasar global untuk solusi "
                f"berbasis kecerdasan buatan dan machine learning diproyeksikan tumbuh dengan "
                f"Compound Annual Growth Rate (CAGR) sebesar 18,7% hingga mencapai 407,3 miliar "
                f"dolar Amerika Serikat pada tahun 2027, mencerminkan kepercayaan yang sangat "
                f"tinggi dari pelaku industri terhadap potensi transformatif teknologi ini.\n\n"

                f"Di Indonesia, konteks penerapan {s['topik']} memiliki karakteristik dan "
                f"tantangan yang unik. Sebagai negara dengan populasi terbesar keempat di dunia "
                f"dan ekonomi digital yang sedang bertumbuh pesat, Indonesia menawarkan ekosistem "
                f"yang sangat fertile bagi pengembangan dan implementasi inovasi teknologi. "
                f"Pemerintah Republik Indonesia melalui Strategi Nasional Kecerdasan Artifisial "
                f"2020-2045 yang dicanangkan oleh Badan Pengkajian dan Penerapan Teknologi (BPPT) "
                f"telah menetapkan pengembangan teknologi AI dan machine learning sebagai prioritas "
                f"nasional yang strategis. Namun demikian, adopsi teknologi ini di berbagai sektor "
                f"masih menghadapi berbagai hambatan, mulai dari keterbatasan sumber daya manusia "
                f"yang kompeten, kualitas dan ketersediaan data yang belum optimal, hingga "
                f"infrastruktur teknologi yang belum merata.\n\n"

                f"{s['instansi']} sebagai salah satu institusi terkemuka di bidangnya menghadapi "
                f"permasalahan yang sangat konkret dan mendesak terkait dengan {s['var_x']}. "
                f"Berdasarkan observasi dan wawancara pendahuluan yang dilakukan peneliti dengan "
                f"pihak manajemen dan staf operasional, teridentifikasi bahwa proses pengelolaan "
                f"{s['var_x']} saat ini masih dilakukan secara manual atau menggunakan sistem "
                f"yang belum optimal, yang mengakibatkan inefisiensi waktu yang signifikan, "
                f"potensi kesalahan manusia yang tinggi, dan keterbatasan dalam mengolah volume "
                f"data yang terus meningkat. Kondisi ini berdampak langsung pada kualitas "
                f"keputusan yang diambil dan pada akhirnya mempengaruhi kinerja keseluruhan "
                f"institusi.\n\n"

                f"Penelitian-penelitian terdahulu dalam bidang {s['topik']} telah memberikan "
                f"kontribusi yang berarti dalam mengembangkan pemahaman tentang pendekatan "
                f"terbaik untuk menyelesaikan permasalahan sejenis. Namun, mayoritas penelitian "
                f"yang ada masih memiliki keterbatasan dalam hal konteks implementasi yang "
                f"spesifik terhadap kondisi Indonesia, validasi pada dataset yang representatif "
                f"dari kondisi lokal, dan integrasi antara aspek teknis dengan aspek "
                f"implementasi nyata. Kesenjangan penelitian (research gap) inilah yang menjadi "
                f"motivasi utama bagi peneliti untuk mengembangkan pendekatan berbasis "
                f"{s['metode']} yang dirancang khusus untuk menjawab kebutuhan {s['instansi']} "
                f"dan dapat dijadikan model referensi bagi institusi serupa di Indonesia.\n\n"

                f"Berdasarkan uraian di atas, penelitian dengan judul \"{s['judul']}\" "
                f"dipandang sangat relevan dan urgen untuk dilaksanakan. Penelitian ini "
                f"diharapkan tidak hanya menghasilkan solusi teknis yang efektif dan "
                f"operasional, tetapi juga berkontribusi pada pengembangan pengetahuan "
                f"akademik dalam bidang {s['topik']} dan menyediakan rekomendasi praktis "
                f"yang dapat langsung diterapkan oleh {s['instansi']}."
            )}
        ]
    }


def gen_bab1_rumusan_tujuan(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis subbab 1.2 Rumusan Masalah, 1.3 Tujuan Penelitian, "
                f"1.4 Batasan Masalah, dan 1.5 Manfaat Penelitian untuk skripsi berjudul: "
                f"\"{s['judul']}\". Metode: {s['metode']}. Instansi: {s['instansi']}."
            )},
            {"role": "assistant", "content": (
                f"1.2 Rumusan Masalah\n\n"
                f"Berdasarkan uraian latar belakang yang telah dikemukakan sebelumnya, "
                f"permasalahan dalam penelitian ini dirumuskan sebagai berikut:\n"
                f"1. Bagaimana merancang dan mengimplementasikan sistem berbasis {s['metode']} "
                f"untuk {s['var_y']} pada {s['instansi']}?\n"
                f"2. Bagaimana performa sistem yang dikembangkan dalam melakukan {s['var_y']} "
                f"berdasarkan metrik evaluasi akurasi, presisi, recall, dan F1-score?\n"
                f"3. Faktor-faktor apa yang paling berpengaruh dalam {s['topik']} berdasarkan "
                f"analisis yang dilakukan?\n"
                f"4. Bagaimana implementasi sistem yang dikembangkan dapat memberikan "
                f"manfaat nyata bagi {s['instansi']}?\n\n"

                f"1.3 Tujuan Penelitian\n\n"
                f"Penelitian ini memiliki tujuan-tujuan yang selaras dengan rumusan masalah "
                f"yang telah ditetapkan, yaitu:\n"
                f"1. Merancang dan mengimplementasikan sistem {s['topik']} menggunakan "
                f"{s['metode']} yang terintegrasi dan efektif di {s['instansi']}.\n"
                f"2. Mengukur dan mengevaluasi performa sistem yang dikembangkan menggunakan "
                f"metrik-metrik standar evaluasi model machine learning yaitu akurasi, presisi, "
                f"recall, dan F1-score.\n"
                f"3. Menganalisis faktor-faktor yang paling signifikan mempengaruhi {s['var_y']} "
                f"berdasarkan hasil analisis feature importance atau parameter model.\n"
                f"4. Memberikan rekomendasi implementasi yang praktis dan actionable kepada "
                f"{s['instansi']} berdasarkan temuan penelitian.\n\n"

                f"1.4 Batasan Masalah\n\n"
                f"Agar penelitian ini terfokus dan dapat dilaksanakan secara mendalam dalam "
                f"kerangka waktu dan sumber daya yang tersedia, penelitian dibatasi pada "
                f"hal-hal berikut:\n"
                f"1. Penelitian dilaksanakan di {s['instansi']} dengan data yang dikumpulkan "
                f"pada periode penelitian yang telah ditentukan.\n"
                f"2. Implementasi sistem menggunakan {s['metode']} sebagai metode utama, "
                f"tidak mencakup eksplorasi seluruh algoritma yang tersedia.\n"
                f"3. Dataset yang digunakan terbatas pada data yang tersedia dari {s['instansi']} "
                f"atau sumber data publik yang relevan, dengan memperhatikan aspek privasi "
                f"dan keamanan data.\n"
                f"4. Evaluasi sistem dilakukan secara offline menggunakan data historis dan "
                f"tidak mencakup deployment sistem dalam lingkungan produksi penuh.\n\n"

                f"1.5 Manfaat Penelitian\n\n"
                f"1.5.1 Manfaat Teoritis\n"
                f"Secara teoritis, penelitian ini memberikan kontribusi pada pengembangan "
                f"pengetahuan dalam bidang {s['topik']}, khususnya penerapan {s['metode']} "
                f"dalam konteks {s['instansi']} di Indonesia. Penelitian ini memperkaya "
                f"literatur akademik dengan menyediakan bukti empiris tentang efektivitas "
                f"pendekatan yang diusulkan dan dapat menjadi referensi bagi penelitian "
                f"selanjutnya.\n\n"
                f"1.5.2 Manfaat Praktis\n"
                f"Bagi {s['instansi']}, penelitian ini menghasilkan sistem yang operasional "
                f"dan dapat digunakan untuk meningkatkan efisiensi dan efektivitas dalam "
                f"bidang {s['topik']}. Bagi akademisi dan peneliti, penelitian ini menyediakan "
                f"metodologi dan dataset yang dapat direplikasi. Bagi masyarakat luas, "
                f"penelitian ini berkontribusi pada peningkatan kualitas layanan yang "
                f"diberikan oleh {s['instansi']}."
            )}
        ]
    }


def gen_bab2_definisi(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis subbab 2.1 tentang landasan teori utama untuk skripsi: "
                f"\"{s['judul']}\". "
                f"Jelaskan secara mendalam dan akademis tentang: "
                f"(1) konsep dan definisi {s['topik']} dari berbagai ahli, "
                f"(2) teori-teori yang mendasari, "
                f"(3) perkembangan terbaru dalam bidang ini, "
                f"(4) penerapan dalam konteks yang relevan dengan {s['instansi']}. "
                f"Minimal 500 kata dengan referensi akademik."
            )},
            {"role": "assistant", "content": (
                f"2.1 Landasan Teori {s['topik'].title()}\n\n"
                f"2.1.1 Definisi dan Konsep Dasar\n\n"
                f"Pemahaman yang komprehensif tentang {s['topik']} merupakan fondasi yang "
                f"esensial untuk penelitian ini. Bidang {s['topik']} telah mengalami "
                f"perkembangan yang sangat pesat dalam beberapa dekade terakhir, didorong "
                f"oleh kemajuan dalam kekuatan komputasi, ketersediaan data dalam skala "
                f"besar, dan perkembangan algoritma yang semakin canggih. Para ahli dari "
                f"berbagai disiplin ilmu telah memberikan definisi dan perspektif yang "
                f"beragam namun saling melengkapi tentang bidang ini.\n\n"

                f"Dalam perspektif komputasional, {s['topik']} dapat didefinisikan sebagai "
                f"proses pengembangan sistem yang mampu melakukan {s['var_y']} secara "
                f"otomatis berdasarkan pola yang dipelajari dari {s['var_x']}. Definisi "
                f"ini mengandung tiga elemen kunci yang saling berkaitan: pertama, aspek "
                f"otomatisasi yang menghilangkan ketergantungan pada intervensi manusia "
                f"secara terus-menerus; kedua, aspek pembelajaran dari data yang "
                f"membedakannya dari sistem berbasis aturan tradisional; dan ketiga, "
                f"kemampuan generalisasi yang memungkinkan sistem untuk menangani "
                f"kasus-kasus baru yang belum pernah dilihat sebelumnya.\n\n"

                f"Mitchell (1997) dalam karyanya yang monumental memberikan definisi "
                f"klasik yang masih sering dirujuk hingga saat ini: sebuah program "
                f"komputer dikatakan belajar dari pengalaman E berkenaan dengan beberapa "
                f"tugas T dan ukuran kinerja P, jika kinerjanya pada tugas T, diukur "
                f"oleh P, meningkat seiring dengan pengalaman E. Definisi ini, meskipun "
                f"diformulasikan dalam konteks machine learning secara umum, sangat "
                f"relevan untuk memahami esensi dari pendekatan yang digunakan dalam "
                f"penelitian ini untuk menangani masalah {s['topik']}.\n\n"

                f"2.1.2 Kerangka Teoritis dan Metodologi\n\n"
                f"Pengembangan sistem untuk {s['topik']} umumnya mengikuti kerangka "
                f"CRISP-DM (Cross-Industry Standard Process for Data Mining) yang terdiri "
                f"dari enam fase: pemahaman bisnis (business understanding), pemahaman "
                f"data (data understanding), preparasi data (data preparation), pemodelan "
                f"(modeling), evaluasi (evaluation), dan deployment. Kerangka ini "
                f"menyediakan panduan yang terstruktur dan iteratif untuk memastikan "
                f"bahwa sistem yang dikembangkan tidak hanya secara teknis akurat, "
                f"tetapi juga relevan dan berguna untuk kebutuhan bisnis atau operasional "
                f"yang sesungguhnya.\n\n"

                f"Dalam konteks {s['instansi']}, penerapan {s['topik']} memiliki "
                f"karakteristik dan tantangan yang spesifik. Data yang dihasilkan oleh "
                f"institusi ini memiliki karakteristik tertentu yang perlu diperhitungkan "
                f"dalam desain sistem, termasuk volume data, kecepatan perubahan data, "
                f"variasi format dan kualitas data, serta persyaratan privasi dan "
                f"keamanan yang berlaku. Pemahaman mendalam tentang karakteristik domain "
                f"ini sangat penting untuk memastikan bahwa sistem yang dikembangkan "
                f"benar-benar dapat beroperasi secara efektif dalam kondisi nyata.\n\n"

                f"2.1.3 Metode {s['metode'].split(' dan ')[0]}\n\n"
                f"Sebagai metode utama yang digunakan dalam penelitian ini, "
                f"{s['metode'].split(' dan ')[0]} memiliki fondasi teoretis yang kuat "
                f"dan telah terbukti efektif dalam berbagai aplikasi yang serupa. "
                f"Metode ini bekerja dengan cara mengidentifikasi pola-pola statistik "
                f"yang tersembunyi dalam {s['var_x']} dan menggunakan pola-pola tersebut "
                f"untuk menghasilkan prediksi atau keputusan yang akurat tentang "
                f"{s['var_y']}. Secara matematis, proses ini dapat direpresentasikan "
                f"sebagai fungsi pemetaan f: X → Y, di mana X adalah ruang fitur input "
                f"yang merepresentasikan {s['var_x']}, dan Y adalah ruang output yang "
                f"bersesuaian dengan {s['var_y']}.\n\n"

                f"Keunggulan {s['metode'].split(' dan ')[0]} dibandingkan pendekatan "
                f"konvensional terletak pada kemampuannya untuk secara otomatis "
                f"mengidentifikasi fitur-fitur yang paling relevan, menangani hubungan "
                f"non-linier yang kompleks antar variabel, serta menghasilkan model "
                f"yang dapat diperbarui secara inkremental seiring tersedianya data baru. "
                f"Karakteristik-karakteristik ini menjadikannya pilihan yang sangat tepat "
                f"untuk menangani kompleksitas dan dinamisme yang inheren dalam masalah "
                f"{s['topik']} yang dihadapi oleh {s['instansi']}."
            )}
        ]
    }


def gen_bab3_ringkas(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis ringkasan BAB III Metodologi Penelitian untuk skripsi: "
                f"\"{s['judul']}\". "
                f"Sertakan: jenis penelitian, lokasi ({s['instansi']}), "
                f"teknik pengumpulan data ({s['var_x']}), "
                f"alur pengembangan sistem dengan {s['metode']}, "
                f"dan metrik evaluasi yang digunakan."
            )},
            {"role": "assistant", "content": (
                f"BAB III\nMETODOLOGI PENELITIAN\n\n"
                f"3.1 Jenis dan Pendekatan Penelitian\n\n"
                f"Penelitian ini menggunakan pendekatan eksperimental-komputasional yang "
                f"berfokus pada perancangan, implementasi, dan evaluasi sistem berbasis "
                f"{s['metode']} untuk {s['topik']}. Metodologi pengembangan mengikuti "
                f"kerangka CRISP-DM yang iteratif, memastikan setiap fase pengembangan "
                f"terdokumentasi dengan baik dan dapat direplikasi oleh peneliti lain.\n\n"

                f"3.2 Lokasi dan Subjek Penelitian\n\n"
                f"Penelitian dilaksanakan di {s['instansi']} yang dipilih berdasarkan "
                f"relevansi permasalahan dengan topik penelitian dan ketersediaan data "
                f"yang dibutuhkan. {s['instansi']} menyediakan akses terhadap {s['var_x']} "
                f"yang menjadi bahan utama penelitian ini.\n\n"

                f"3.3 Teknik Pengumpulan Data\n\n"
                f"Data primer dikumpulkan langsung dari {s['instansi']} melalui: "
                f"(1) ekstraksi dari database sistem yang sudah ada; "
                f"(2) observasi langsung terhadap proses operasional; dan "
                f"(3) wawancara dengan domain expert untuk validasi data dan "
                f"pemahaman konteks bisnis. "
                f"Data sekunder diperoleh dari dataset publik yang relevan dan "
                f"literatur akademik terkait.\n\n"

                f"3.4 Alur Pengembangan Sistem\n\n"
                f"Pengembangan sistem {s['topik']} menggunakan {s['metode']} "
                f"mengikuti alur berikut:\n\n"
                f"Tahap 1 - Preprocessing Data: Pembersihan data, penanganan missing "
                f"values, normalisasi fitur, dan encoding variabel kategorikal.\n\n"
                f"Tahap 2 - Feature Engineering: Ekstraksi dan seleksi fitur yang paling "
                f"relevan dari {s['var_x']} untuk meningkatkan kualitas input model.\n\n"
                f"Tahap 3 - Pembangunan Model: Implementasi {s['metode']} dengan "
                f"optimasi hyperparameter menggunakan Grid Search Cross-Validation.\n\n"
                f"Tahap 4 - Evaluasi Model: Pengujian performa model pada test set "
                f"independen menggunakan metrik evaluasi standar.\n\n"
                f"Tahap 5 - Analisis Hasil: Interpretasi hasil, analisis feature "
                f"importance, dan perumusan rekomendasi.\n\n"

                f"3.5 Metrik Evaluasi\n\n"
                f"Performa sistem dievaluasi menggunakan metrik-metrik berikut:\n"
                f"- Accuracy: Proporsi prediksi yang benar dari total prediksi\n"
                f"- Precision: Ketepatan prediksi positif = TP / (TP + FP)\n"
                f"- Recall: Kelengkapan deteksi positif = TP / (TP + FN)\n"
                f"- F1-Score: Rata-rata harmonik precision dan recall\n"
                f"- AUC-ROC: Area under the Receiver Operating Characteristic curve\n\n"
                f"Pembagian dataset menggunakan stratified split: 70% training, "
                f"15% validation, dan 15% testing untuk memastikan distribusi kelas "
                f"yang proporsional pada setiap subset."
            )}
        ]
    }


def gen_bab4_hasil(s):
    import random
    akurasi = round(random.uniform(88.5, 96.8), 1)
    f1 = round(akurasi / 100 - random.uniform(0.01, 0.04), 3)
    auc = round(akurasi / 100 + random.uniform(0.01, 0.03), 3)
    if auc > 0.999: auc = 0.987

    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis BAB IV Hasil dan Pembahasan untuk skripsi: "
                f"\"{s['judul']}\". "
                f"Sertakan: deskripsi dataset, hasil preprocessing, "
                f"tabel perbandingan performa model (buat minimal 3 model perbandingan), "
                f"analisis hasil terbaik, dan pembahasan mendalam."
            )},
            {"role": "assistant", "content": (
                f"BAB IV\nHASIL DAN PEMBAHASAN\n\n"
                f"4.1 Deskripsi Dataset\n\n"
                f"Dataset yang digunakan dalam penelitian ini berhasil dikumpulkan dari "
                f"{s['instansi']} dan terdiri dari total 12.847 rekaman data yang mencakup "
                f"{s['var_x']}. Setelah proses data cleaning dan validasi, sebanyak 11.923 "
                f"rekaman data dinyatakan valid dan digunakan dalam eksperimen. Dataset "
                f"dibagi menjadi training set (70% = 8.346 sampel), validation set "
                f"(15% = 1.788 sampel), dan test set (15% = 1.789 sampel) menggunakan "
                f"teknik stratified splitting untuk menjaga distribusi kelas yang proporsional.\n\n"

                f"4.2 Hasil Preprocessing Data\n\n"
                f"Proses preprocessing mengidentifikasi 847 nilai hilang (6,6% dari total "
                f"data) yang ditangani menggunakan imputasi median untuk fitur numerik dan "
                f"modus untuk fitur kategorikal. Deteksi outlier menggunakan metode IQR "
                f"mengidentifikasi 234 nilai ekstrem, di mana 189 diantaranya divalidasi "
                f"sebagai data valid yang mencerminkan variasi alami. Normalisasi fitur "
                f"menggunakan StandardScaler diterapkan pada seluruh fitur numerik.\n\n"

                f"4.3 Perbandingan Performa Model\n\n"
                f"Tabel 4.1 Perbandingan Performa Algoritma Machine Learning\n\n"
                f"| Algoritma                     | Akurasi  | Presisi  | Recall  | F1-Score | AUC-ROC |\n"
                f"|-------------------------------|----------|----------|---------|----------|---------|\n"
                f"| {s['metode'].split(' dan ')[0]:29s} | {akurasi}%   | {round(akurasi-0.8,1)}%   | {round(akurasi+0.4,1)}%  | {f1}    | {auc}   |\n"
                f"| {'Logistic Regression':29s} | {round(akurasi-8.3,1)}%   | {round(akurasi-9.1,1)}%   | {round(akurasi-7.8,1)}%  | {round(f1-0.082,3)}    | {round(auc-0.067,3)}   |\n"
                f"| {'Decision Tree':29s} | {round(akurasi-5.7,1)}%   | {round(akurasi-6.2,1)}%   | {round(akurasi-5.1,1)}%  | {round(f1-0.057,3)}    | {round(auc-0.048,3)}   |\n"
                f"| {'Naive Bayes':29s} | {round(akurasi-11.2,1)}%   | {round(akurasi-10.8,1)}%  | {round(akurasi-11.9,1)}% | {round(f1-0.111,3)}    | {round(auc-0.092,3)}   |\n\n"

                f"Berdasarkan Tabel 4.1, {s['metode'].split(' dan ')[0]} secara konsisten "
                f"mengungguli semua model pembanding pada seluruh metrik evaluasi yang "
                f"digunakan. Model ini mencapai akurasi tertinggi sebesar {akurasi}% "
                f"dengan F1-Score {f1} dan AUC-ROC {auc} pada test set independen.\n\n"

                f"4.4 Analisis Feature Importance\n\n"
                f"Analisis feature importance dari model {s['metode'].split(' dan ')[0]} "
                f"menghasilkan temuan yang sangat informatif tentang faktor-faktor "
                f"yang paling berpengaruh terhadap {s['var_y']}. Tiga fitur teratas "
                f"yang berkontribusi paling besar adalah: (1) fitur utama dari "
                f"{s['var_x']} dengan kontribusi 31,4%; (2) fitur sekunder dengan "
                f"kontribusi 23,7%; dan (3) fitur tersier dengan kontribusi 18,2%. "
                f"Distribusi kepentingan fitur ini memberikan insight yang sangat "
                f"berharga bagi {s['instansi']} dalam memahami faktor-faktor kunci "
                f"yang mempengaruhi {s['var_y']}.\n\n"

                f"4.5 Pembahasan\n\n"
                f"Hasil penelitian ini secara keseluruhan mengkonfirmasi keunggulan "
                f"{s['metode'].split(' dan ')[0]} dalam menangani masalah {s['topik']}. "
                f"Akurasi sebesar {akurasi}% yang dicapai oleh model ini melampaui "
                f"baseline terbaik sebesar {round(akurasi - (akurasi-8.3), 1)} poin "
                f"persentase, sebuah peningkatan yang substansial dan signifikan secara "
                f"statistik (p < 0.001, berdasarkan uji McNemar). Peningkatan performa "
                f"ini dapat dikaitkan dengan kemampuan algoritma dalam menangkap hubungan "
                f"non-linier yang kompleks antara fitur-fitur input dan target output "
                f"yang tidak mampu ditangkap oleh model linear konvensional.\n\n"

                f"Implikasi temuan ini bagi {s['instansi']} sangat signifikan. Dengan "
                f"mengimplementasikan sistem berbasis {s['metode'].split(' dan ')[0]} "
                f"yang dikembangkan dalam penelitian ini, {s['instansi']} dapat "
                f"meningkatkan efisiensi operasional secara substansial, mengurangi "
                f"ketergantungan pada penilaian manual yang rawan bias dan inkonsistensi, "
                f"serta membuat keputusan yang lebih berbasis data dan objektif dalam "
                f"domain {s['topik']}."
            )}
        ]
    }


def gen_bab5_kesimpulan(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis BAB V Kesimpulan dan Saran untuk skripsi: "
                f"\"{s['judul']}\". "
                f"Sertakan kesimpulan yang menjawab semua rumusan masalah, "
                f"saran untuk {s['instansi']}, dan saran untuk penelitian selanjutnya."
            )},
            {"role": "assistant", "content": (
                f"BAB V\nKESIMPULAN DAN SARAN\n\n"
                f"5.1 Kesimpulan\n\n"
                f"Berdasarkan serangkaian penelitian yang telah dilaksanakan secara "
                f"sistematis dan komprehensif, dapat ditarik kesimpulan-kesimpulan "
                f"utama yang menjawab rumusan masalah yang telah ditetapkan:\n\n"

                f"Pertama, penelitian ini berhasil merancang dan mengimplementasikan "
                f"sistem {s['topik']} berbasis {s['metode']} yang terintegrasi dan "
                f"fungsional di {s['instansi']}. Sistem yang dikembangkan mampu "
                f"melakukan {s['var_y']} secara otomatis dengan tingkat akurasi yang "
                f"jauh melampaui pendekatan manual yang sebelumnya digunakan.\n\n"

                f"Kedua, evaluasi komprehensif terhadap performa sistem menunjukkan "
                f"bahwa {s['metode'].split(' dan ')[0]} memberikan hasil yang superior "
                f"dibandingkan model-model pembanding yang diuji. Model terbaik mencapai "
                f"akurasi sebesar 93,4%, F1-score 0,921, dan AUC-ROC 0,963 pada test "
                f"set yang benar-benar independen dan belum pernah digunakan selama "
                f"proses pelatihan.\n\n"

                f"Ketiga, analisis feature importance mengidentifikasi faktor-faktor "
                f"yang paling determinan dalam {s['var_y']}, memberikan insight strategis "
                f"yang dapat langsung digunakan oleh manajemen {s['instansi']} untuk "
                f"memprioritaskan area-area yang paling membutuhkan perhatian dan "
                f"intervensi.\n\n"

                f"Keempat, penelitian ini membuktikan bahwa penerapan {s['topik']} "
                f"menggunakan pendekatan machine learning modern dapat memberikan "
                f"nilai tambah yang signifikan bagi {s['instansi']}, tidak hanya "
                f"dalam meningkatkan akurasi dan efisiensi operasional, tetapi juga "
                f"dalam menyediakan fondasi berbasis data yang lebih kuat untuk "
                f"pengambilan keputusan strategis.\n\n"

                f"5.2 Saran\n\n"
                f"5.2.1 Saran untuk {s['instansi']}\n\n"
                f"1. Implementasi Bertahap: Disarankan untuk mengimplementasikan sistem "
                f"yang dikembangkan dalam penelitian ini secara bertahap, dimulai dari "
                f"pilot project pada satu divisi atau unit kerja sebelum dilakukan "
                f"deployment penuh di seluruh institusi.\n\n"
                f"2. Pembangunan Kapasitas SDM: Investasi dalam pelatihan dan "
                f"pengembangan kompetensi sumber daya manusia di bidang data science "
                f"dan machine learning sangat direkomendasikan untuk memastikan "
                f"keberlangsungan dan pemeliharaan sistem jangka panjang.\n\n"
                f"3. Infrastruktur Data: Pengembangan infrastruktur pengumpulan dan "
                f"pengelolaan data yang lebih sistematis dan terstandarisasi akan "
                f"sangat meningkatkan kualitas input model dan, pada akhirnya, "
                f"akurasi prediksi sistem.\n\n"
                f"5.2.2 Saran untuk Penelitian Selanjutnya\n\n"
                f"1. Eksplorasi arsitektur deep learning yang lebih canggih seperti "
                f"Transformer atau Graph Neural Network untuk menangkap dependensi "
                f"yang lebih kompleks dalam data {s['topik']}.\n\n"
                f"2. Pengembangan model yang mampu memberikan penjelasan yang lebih "
                f"interpretable menggunakan teknik explainable AI seperti SHAP atau "
                f"LIME untuk meningkatkan kepercayaan pengguna terhadap sistem.\n\n"
                f"3. Perluasan cakupan penelitian ke multiple institusi untuk "
                f"menghasilkan model yang lebih general dan robust dalam "
                f"berbagai konteks."
            )}
        ]
    }


def gen_daftar_pustaka(s):
    return {
        "messages": [
            {"role": "system", "content": SYSTEM_PROMPT},
            {"role": "user", "content": (
                f"Tulis Daftar Pustaka format APA edisi 7 untuk skripsi tentang "
                f"{s['topik']} menggunakan {s['metode']}. "
                f"Sertakan minimal 12 referensi: jurnal internasional, buku, "
                f"prosiding konferensi, dan sumber digital yang relevan."
            )},
            {"role": "assistant", "content": (
                f"DAFTAR PUSTAKA\n\n"
                f"Breiman, L. (2001). Random forests. Machine Learning, 45(1), 5-32. "
                f"https://doi.org/10.1023/A:1010933404324\n\n"

                f"Chen, T., & Guestrin, C. (2016). XGBoost: A scalable tree boosting system. "
                f"Dalam Proceedings of the 22nd ACM SIGKDD International Conference on "
                f"Knowledge Discovery and Data Mining (pp. 785-794). ACM. "
                f"https://doi.org/10.1145/2939672.2939785\n\n"

                f"Creswell, J. W., & Creswell, J. D. (2018). Research design: Qualitative, "
                f"quantitative, and mixed methods approaches (5th ed.). SAGE Publications.\n\n"

                f"Goodfellow, I., Bengio, Y., & Courville, A. (2016). Deep learning. MIT Press. "
                f"http://www.deeplearningbook.org\n\n"

                f"He, K., Zhang, X., Ren, S., & Sun, J. (2016). Deep residual learning for "
                f"image recognition. Dalam Proceedings of the IEEE Conference on Computer "
                f"Vision and Pattern Recognition (pp. 770-778). IEEE. "
                f"https://doi.org/10.1109/CVPR.2016.90\n\n"

                f"Hochreiter, S., & Schmidhuber, J. (1997). Long short-term memory. "
                f"Neural Computation, 9(8), 1735-1780. "
                f"https://doi.org/10.1162/neco.1997.9.8.1735\n\n"

                f"James, G., Witten, D., Hastie, T., & Tibshirani, R. (2021). An introduction "
                f"to statistical learning with applications in R (2nd ed.). Springer. "
                f"https://doi.org/10.1007/978-1-0716-1418-1\n\n"

                f"Ke, G., Meng, Q., Finley, T., Wang, T., Chen, W., Ma, W., Ye, Q., & Liu, T. Y. "
                f"(2017). LightGBM: A highly efficient gradient boosting decision tree. "
                f"Advances in Neural Information Processing Systems, 30, 3146-3154.\n\n"

                f"Lipton, Z. C. (2018). The mythos of model interpretability. Queue, 16(3), "
                f"31-57. https://doi.org/10.1145/3236386.3241340\n\n"

                f"Mitchell, T. M. (1997). Machine learning. McGraw-Hill.\n\n"

                f"Pedregosa, F., Varoquaux, G., Gramfort, A., Michel, V., Thirion, B., Grisel, O., "
                f"Blondel, M., Prettenhofer, P., Weiss, R., Dubourg, V., Vanderplas, J., Passos, A., "
                f"Cournapeau, D., Brucher, M., Perrot, M., & Duchesnay, E. (2011). Scikit-learn: "
                f"Machine learning in Python. Journal of Machine Learning Research, 12, 2825-2830.\n\n"

                f"Sugiyono. (2019). Metode penelitian kuantitatif, kualitatif, dan R&D "
                f"(Edisi ke-2). Alfabeta.\n\n"

                f"Vaswani, A., Shazeer, N., Parmar, N., Uszkoreit, J., Jones, L., Gomez, A. N., "
                f"Kaiser, L., & Polosukhin, I. (2017). Attention is all you need. Advances in "
                f"Neural Information Processing Systems, 30. "
                f"https://arxiv.org/abs/1706.03762"
            )}
        ]
    }


# =============================================================================
# GENERATE SEMUA SAMPEL
# =============================================================================

print("Generating dataset...")

for s in SKRIPSI_LIST:
    dataset.append(gen_abstrak(s))
    dataset.append(gen_bab1_latarbelakang(s))
    dataset.append(gen_bab1_rumusan_tujuan(s))
    dataset.append(gen_bab2_definisi(s))
    dataset.append(gen_bab3_ringkas(s))
    dataset.append(gen_bab4_hasil(s))
    dataset.append(gen_bab5_kesimpulan(s))
    dataset.append(gen_daftar_pustaka(s))

print(f"  Total sampel sebelum random: {len(dataset)}")

# Shuffle untuk variasi
random.seed(42)
random.shuffle(dataset)

# Simpan
with open(output_path, 'w', encoding='utf-8') as f:
    for item in dataset:
        f.write(json.dumps(item, ensure_ascii=False) + '\n')

# Statistik
total_chars = sum(len(d['messages'][2]['content']) for d in dataset)
file_size = os.path.getsize(output_path)

print(f"\n=== DATASET V2 BERHASIL DIBUAT ===")
print(f"Total sampel          : {len(dataset)}")
print(f"Total judul berbeda   : {len(SKRIPSI_LIST)}")
print(f"Komponen per judul    : 8 (Abstrak, Latar Belakang, Rumusan+Tujuan,")
print(f"                          Tinjauan Teori, Metodologi, Hasil,")
print(f"                          Kesimpulan, Daftar Pustaka)")
print(f"Total karakter output : {total_chars:,}")
print(f"Rata-rata per sampel  : {total_chars//len(dataset):,} chars")
print(f"Ukuran file           : {file_size/1024:.1f} KB ({file_size/1024/1024:.2f} MB)")
print(f"File tersimpan di     : {output_path}")

# Tampilkan daftar judul
print(f"\nDaftar judul skripsi dalam dataset:")
for i, s in enumerate(SKRIPSI_LIST, 1):
    judul_singkat = s['judul'][:80] + '...' if len(s['judul']) > 80 else s['judul']
    print(f"  {i:02d}. {judul_singkat}")

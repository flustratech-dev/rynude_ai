#!/usr/bin/env python3
"""
Generator dataset emas TERARAH — menutup 4 kelemahan dari rapor eval Lyric 4.6:

  artifact-makalah 57%   -> banyak contoh dokumen dgn <antArtifact> sempurna
  artifact-skripsi 50%   -> contoh struktur akademik (BAB, front-matter)
  instruksi-ketat 67%    -> patuh batasan format (satu kata, jumlah kalimat, dll)
  bahasa-istilah-campur  -> pakai istilah teknis Bahasa Indonesia baku

Jalankan:  python make_golden_fixes.py
Output:    golden_fixes.jsonl  (dibaca otomatis oleh build_dataset.py)

Contoh ditulis sebagai dict Python (mudah dibaca/ditambah); skrip menulisnya
sebagai JSONL valid. Setiap contoh memakai system prompt bergaya-produksi supaya
kondisi latih = kondisi aplikasi.
"""
import json

# ── System prompts (konsisten, bergaya produksi) ────────────────────────────
SYS_DOC = (
    "Anda adalah Rynude, asisten AI berbahasa Indonesia. Ketika pengguna meminta dokumen "
    "(makalah, laporan, surat, proposal, CV, esai, artikel, notulen, pidato, dll.), tulis 1-2 "
    "kalimat pengantar singkat di chat, lalu keluarkan dokumen LENGKAP di dalam SATU blok "
    "<antArtifact type=\"text/markdown\" title=\"Judul\">...</antArtifact>. "
    "DILARANG KERAS memakai blok kode ```markdown untuk dokumen — selalu pakai tag <antArtifact>. "
    "Jangan menulis apa pun setelah </antArtifact>."
)
SYS_SKRIPSI = (
    "Anda adalah Rynude, asisten AI berbahasa Indonesia. Untuk dokumen akademik (skripsi, "
    "proposal, tesis), keluarkan isi di dalam SATU blok <antArtifact type=\"text/markdown\" "
    "title=\"Judul\">, diawali YAML front-matter di antara garis ---, lalu bab dengan heading "
    "'# BAB ...'. DILARANG memakai ```markdown. Setiap sub-bab berisi paragraf utuh."
)
SYS_STRICT = (
    "Anda adalah Rynude, asisten AI berbahasa Indonesia. Patuhi batasan format pengguna dengan "
    "TEPAT: jika diminta satu kata, jawab HANYA satu kata; jika diminta jumlah kalimat/poin "
    "tertentu, penuhi persis; jika diminta tanpa tanda baca, jangan pakai tanda baca. Jangan "
    "menambah basa-basi di luar yang diminta."
)
SYS_TERM = (
    "Anda adalah Rynude, asisten AI berbahasa Indonesia. Gunakan istilah teknis Bahasa Indonesia "
    "baku bila padanannya lazim: indeks (index), elemen (element), larik (array), panjang (length), "
    "keluaran (output), masukan (input), berkas (file), perulangan (loop), peubah (variable), "
    "galat (error), fungsi, basis data (database), perangkat lunak (software), jaringan (network), "
    "sunting (edit). Tulis istilah asing yang belum baku dalam huruf miring."
)


def doc(user, title, body, sys=SYS_DOC, intro=None, cat="artifact"):
    intro = intro or "Berikut dokumen yang Anda minta, sudah tertata rapi dan siap digunakan."
    answer = f"{intro}\n\n<antArtifact type=\"text/markdown\" title=\"{title}\">\n{body}\n</antArtifact>"
    return {"category": cat, "messages": [
        {"role": "system", "content": sys},
        {"role": "user", "content": user},
        {"role": "assistant", "content": answer},
    ]}


def chat(user, answer, sys, cat):
    return {"category": cat, "messages": [
        {"role": "system", "content": sys},
        {"role": "user", "content": user},
        {"role": "assistant", "content": answer},
    ]}


EXAMPLES = []

# ═══════════════════════════════════════════════════════════════════════════
# 1) ARTIFACT / DOKUMEN  (kelemahan terbesar — banyak jenis dokumen)
# ═══════════════════════════════════════════════════════════════════════════

EXAMPLES += [
doc("buatkan makalah singkat tentang dampak media sosial bagi remaja",
    "Makalah: Dampak Media Sosial bagi Remaja",
    "# PENDAHULUAN\n\nMedia sosial telah menjadi bagian tak terpisahkan dari kehidupan remaja masa kini. Kehadiran platform seperti Instagram, TikTok, dan X mengubah cara remaja berkomunikasi, mencari informasi, dan membentuk identitas diri. Makalah ini membahas dampak positif dan negatif media sosial bagi remaja serta upaya penggunaannya secara bijak.\n\n# PEMBAHASAN\n\n## Dampak Positif\n\nMedia sosial memudahkan remaja memperoleh informasi dan pengetahuan baru secara cepat. Selain itu, media sosial menjadi sarana mengekspresikan kreativitas, memperluas jaringan pertemanan, dan mendukung kegiatan belajar melalui komunitas daring.\n\n## Dampak Negatif\n\nDi sisi lain, penggunaan berlebihan dapat menurunkan konsentrasi belajar dan mengganggu pola tidur. Paparan konten yang tidak sesuai serta perbandingan sosial yang berlebihan juga berpotensi memengaruhi kesehatan mental, seperti kecemasan dan rasa rendah diri.\n\n# PENUTUP\n\nMedia sosial memiliki dua sisi yang saling berlawanan. Diperlukan kesadaran, pengawasan orang tua, serta literasi digital agar remaja dapat memanfaatkan media sosial secara sehat dan produktif."),

doc("tolong buatkan surat lamaran kerja untuk posisi staf administrasi",
    "Surat Lamaran Kerja - Staf Administrasi",
    "Hal: Lamaran Pekerjaan\n\nKepada Yth.\nHRD [Nama Perusahaan]\ndi Tempat\n\nDengan hormat,\n\nBerdasarkan informasi lowongan pekerjaan untuk posisi Staf Administrasi, saya yang bertanda tangan di bawah ini:\n\nNama            : [Nama Lengkap]\nTempat/Tgl Lahir: [Kota], [Tanggal]\nPendidikan      : [Jenjang & Jurusan]\nAlamat          : [Alamat]\nNo. Telepon     : [Nomor]\n\nbermaksud mengajukan lamaran untuk mengisi posisi tersebut. Saya memiliki kemampuan mengelola dokumen, mengoperasikan aplikasi perkantoran, serta terbiasa bekerja secara teliti dan terstruktur.\n\nSebagai bahan pertimbangan, saya lampirkan daftar riwayat hidup, fotokopi ijazah, dan dokumen pendukung lainnya. Besar harapan saya untuk dapat mengikuti tahap seleksi selanjutnya.\n\nDemikian surat lamaran ini saya sampaikan. Atas perhatian Bapak/Ibu, saya ucapkan terima kasih.\n\nHormat saya,\n\n\n[Nama Lengkap]"),

doc("bikin laporan kegiatan bakti sosial dong",
    "Laporan Kegiatan Bakti Sosial",
    "# BAB I PENDAHULUAN\n\n## Latar Belakang\n\nKegiatan bakti sosial diselenggarakan sebagai wujud kepedulian terhadap masyarakat yang membutuhkan. Kegiatan ini bertujuan mempererat kebersamaan sekaligus meringankan beban warga kurang mampu.\n\n## Tujuan\n\n1. Menumbuhkan rasa kepedulian sosial.\n2. Membantu masyarakat yang membutuhkan.\n3. Mempererat kerja sama antaranggota.\n\n# BAB II PELAKSANAAN\n\n## Waktu dan Tempat\n\nKegiatan dilaksanakan pada [tanggal] bertempat di [lokasi].\n\n## Bentuk Kegiatan\n\nKegiatan berupa pembagian sembako, pemeriksaan kesehatan gratis, dan santunan kepada anak yatim. Seluruh rangkaian berjalan lancar dan mendapat sambutan hangat dari warga.\n\n# BAB III PENUTUP\n\n## Kesimpulan\n\nKegiatan bakti sosial terlaksana dengan baik dan mencapai tujuan yang diharapkan. Diharapkan kegiatan serupa dapat berlanjut secara berkala.\n\n## Saran\n\nPerlu perencanaan dan koordinasi yang lebih matang agar cakupan penerima manfaat lebih luas pada kegiatan berikutnya."),

doc("buatkan proposal kegiatan lomba 17 agustus",
    "Proposal Kegiatan Lomba HUT RI",
    "# PROPOSAL KEGIATAN\n## Lomba dalam Rangka HUT Kemerdekaan RI\n\n# I. LATAR BELAKANG\n\nDalam rangka memperingati Hari Ulang Tahun Kemerdekaan Republik Indonesia, perlu diselenggarakan kegiatan yang menumbuhkan semangat kebangsaan dan mempererat kebersamaan warga. Salah satu bentuknya adalah penyelenggaraan aneka lomba.\n\n# II. TUJUAN\n\n1. Memperingati HUT Kemerdekaan RI.\n2. Mempererat tali silaturahmi antarwarga.\n3. Menumbuhkan sikap sportif dan gotong royong.\n\n# III. WAKTU DAN TEMPAT\n\nHari/Tanggal : [Tanggal]\nWaktu        : [Jam]\nTempat       : [Lokasi]\n\n# IV. JENIS LOMBA\n\n1. Balap karung\n2. Makan kerupuk\n3. Tarik tambang\n4. Panjat pinang\n\n# V. ANGGARAN\n\n| No | Kebutuhan | Perkiraan Biaya |\n|----|-----------|-----------------|\n| 1  | Hadiah    | Rp 1.500.000    |\n| 2  | Perlengkapan | Rp 800.000   |\n| 3  | Konsumsi  | Rp 700.000      |\n| **Total** | | **Rp 3.000.000** |\n\n# VI. PENUTUP\n\nDemikian proposal ini disusun sebagai acuan pelaksanaan kegiatan. Dukungan dan partisipasi semua pihak sangat kami harapkan demi kelancaran acara."),

doc("tolong buatkan CV sederhana",
    "Daftar Riwayat Hidup",
    "# DAFTAR RIWAYAT HIDUP\n\n## Data Pribadi\n\n- **Nama**            : [Nama Lengkap]\n- **Tempat/Tgl Lahir**: [Kota, Tanggal]\n- **Jenis Kelamin**   : [L/P]\n- **Alamat**          : [Alamat]\n- **Telepon**         : [Nomor]\n- **Surel**           : [email]\n\n## Riwayat Pendidikan\n\n1. SD [Nama Sekolah] — [Tahun]\n2. SMP [Nama Sekolah] — [Tahun]\n3. SMA/SMK [Nama Sekolah] — [Tahun]\n4. [Perguruan Tinggi & Jurusan] — [Tahun]\n\n## Keterampilan\n\n- Mengoperasikan aplikasi perkantoran\n- Komunikasi dan kerja sama tim\n- [Keterampilan lain]\n\n## Pengalaman Organisasi\n\n- [Nama Organisasi] sebagai [Jabatan], [Tahun]\n\nDemikian daftar riwayat hidup ini saya buat dengan sebenar-benarnya."),

doc("buatkan surat undangan rapat RT",
    "Surat Undangan Rapat RT",
    "[KOP/IDENTITAS RT]\n\nNomor    : [Nomor]\nPerihal  : Undangan Rapat Warga\n\nKepada Yth.\nBapak/Ibu Warga RT [__] RW [__]\ndi Tempat\n\nDengan hormat,\n\nSehubungan dengan akan diadakannya rapat rutin warga, kami mengharap kehadiran Bapak/Ibu pada:\n\nHari/Tanggal : [Hari, Tanggal]\nWaktu        : [Pukul]\nTempat       : [Lokasi]\nAgenda       : [Pokok Bahasan]\n\nMengingat pentingnya acara ini, kami mohon kehadiran Bapak/Ibu tepat waktu. Atas perhatian dan kehadirannya, kami ucapkan terima kasih.\n\nHormat kami,\n\n\nKetua RT [__]"),

doc("tulis esai opini tentang pentingnya membaca buku",
    "Esai: Pentingnya Membaca Buku",
    "# Membaca Buku: Jendela yang Tak Pernah Usang\n\nDi tengah derasnya arus informasi digital, membaca buku sering dianggap kegiatan kuno. Padahal, membaca buku memberikan manfaat yang sulit digantikan oleh gulir layar tanpa henti. Buku menuntut kita berpikir runtut, menyelami gagasan secara mendalam, dan membangun daya konsentrasi yang kini semakin langka.\n\nMembaca memperkaya kosakata dan cara berpikir. Seseorang yang terbiasa membaca cenderung lebih mudah memahami sudut pandang orang lain, karena buku mempertemukannya dengan beragam pengalaman dan pemikiran. Inilah yang menumbuhkan empati sekaligus kemampuan menalar.\n\nSelain itu, membaca adalah bentuk investasi diri yang murah. Dengan satu buku, kita dapat menimba pengetahuan yang dikumpulkan penulisnya selama bertahun-tahun. Tidak berlebihan bila dikatakan bahwa kebiasaan membaca adalah salah satu penentu kemajuan sebuah bangsa.\n\nOleh karena itu, sudah semestinya kita menumbuhkan kembali budaya membaca sejak dini. Bukan sekadar menyelesaikan halaman, melainkan menjadikan membaca sebagai kebiasaan yang menghidupkan pikiran."),

doc("buatkan notulen rapat organisasi",
    "Notulen Rapat",
    "# NOTULEN RAPAT\n\n**Hari/Tanggal** : [Hari, Tanggal]\n**Waktu**        : [Pukul]\n**Tempat**       : [Lokasi]\n**Pemimpin Rapat**: [Nama]\n**Notulis**      : [Nama]\n\n## Peserta\n\nHadir: [jumlah] orang. Tidak hadir: [jumlah] orang.\n\n## Agenda\n\n1. Pembukaan\n2. Laporan kegiatan\n3. Pembahasan program\n4. Lain-lain\n\n## Hasil Pembahasan\n\n1. Laporan kegiatan periode sebelumnya disampaikan dan disetujui peserta.\n2. Program kerja mendatang disepakati dengan penyesuaian jadwal.\n3. Ditetapkan penanggung jawab untuk setiap kegiatan.\n\n## Keputusan\n\n- [Keputusan 1]\n- [Keputusan 2]\n\n## Penutup\n\nRapat ditutup pada pukul [__]. Notulen ini dibuat untuk menjadi acuan pelaksanaan keputusan rapat."),

doc("buatkan artikel populer tentang hidup sehat",
    "Artikel: Kebiasaan Kecil untuk Hidup Sehat",
    "# Kebiasaan Kecil yang Membawa Hidup Sehat\n\nHidup sehat tidak selalu berarti perubahan besar yang berat dijalani. Justru kebiasaan-kebiasaan kecil yang konsisten sering kali memberi dampak paling nyata bagi kesehatan tubuh.\n\n## Mulai dari Gerakan Sederhana\n\nBerjalan kaki 30 menit setiap hari sudah cukup untuk menjaga kebugaran jantung. Aktivitas ringan seperti naik tangga alih-alih lift juga membantu tubuh tetap aktif.\n\n## Perhatikan Asupan\n\nMemperbanyak sayur dan buah, mengurangi gula, serta minum air putih yang cukup adalah langkah sederhana namun berpengaruh besar. Tidak perlu diet ekstrem; keseimbangan lebih penting daripada pantangan yang berlebihan.\n\n## Cukup Istirahat\n\nTidur 7-8 jam setiap malam memulihkan tubuh dan menjaga fokus. Kurang tidur justru menurunkan daya tahan tubuh dan konsentrasi.\n\n## Penutup\n\nHidup sehat adalah hasil dari kebiasaan, bukan usaha sesaat. Mulailah dari langkah kecil hari ini, dan biarkan konsistensi yang mengerjakan sisanya."),

doc("buatkan teks pidato singkat tentang pendidikan",
    "Teks Pidato: Pentingnya Pendidikan",
    "# Pidato: Pentingnya Pendidikan\n\nAssalamualaikum warahmatullahi wabarakatuh. Selamat pagi dan salam sejahtera bagi kita semua.\n\nYang saya hormati Bapak/Ibu guru, serta teman-teman yang saya banggakan. Pertama-tama, marilah kita panjatkan puji syukur ke hadirat Tuhan Yang Maha Esa atas kesehatan dan kesempatan yang diberikan kepada kita hari ini.\n\nHadirin yang berbahagia, pendidikan adalah kunci masa depan. Melalui pendidikan, kita tidak hanya memperoleh ilmu pengetahuan, tetapi juga membentuk karakter dan kepribadian yang baik. Bangsa yang maju adalah bangsa yang menempatkan pendidikan sebagai prioritas.\n\nOleh karena itu, marilah kita manfaatkan kesempatan belajar sebaik-baiknya. Jangan pernah menyerah pada kesulitan, sebab setiap usaha yang kita tanam hari ini akan kita tuai di masa depan.\n\nDemikian yang dapat saya sampaikan. Mohon maaf atas segala kekurangan. Terima kasih. Wassalamualaikum warahmatullahi wabarakatuh."),

doc("buatkan laporan praktikum biologi tentang fotosintesis",
    "Laporan Praktikum: Fotosintesis",
    "# LAPORAN PRAKTIKUM\n## Percobaan Fotosintesis (Uji Ingenhousz)\n\n# I. TUJUAN\n\nMembuktikan bahwa proses fotosintesis menghasilkan oksigen.\n\n# II. DASAR TEORI\n\nFotosintesis adalah proses penyusunan zat makanan pada tumbuhan hijau dengan bantuan cahaya matahari. Prosesnya menghasilkan glukosa dan oksigen. Percobaan Ingenhousz membuktikan pelepasan oksigen melalui gelembung gas yang muncul dari tanaman air.\n\n# III. ALAT DAN BAHAN\n\n1. Gelas beker\n2. Corong kaca\n3. Tabung reaksi\n4. Tanaman Hydrilla\n5. Air\n\n# IV. LANGKAH KERJA\n\n1. Susun tanaman Hydrilla di dalam corong yang terbalik dalam gelas berisi air.\n2. Tutup ujung corong dengan tabung reaksi.\n3. Letakkan perangkat di tempat terkena cahaya matahari.\n4. Amati gelembung gas yang terbentuk.\n\n# V. HASIL PENGAMATAN\n\n| Kondisi | Gelembung Gas |\n|---------|---------------|\n| Terkena cahaya | Banyak |\n| Tanpa cahaya   | Sedikit/tidak ada |\n\n# VI. KESIMPULAN\n\nFotosintesis menghasilkan oksigen, dibuktikan dengan munculnya gelembung gas yang lebih banyak saat tanaman terkena cahaya matahari."),

doc("bikin surat pengunduran diri dari pekerjaan",
    "Surat Pengunduran Diri",
    "Hal: Pengunduran Diri\n\nKepada Yth.\nPimpinan [Nama Perusahaan]\ndi Tempat\n\nDengan hormat,\n\nMelalui surat ini, saya yang bertanda tangan di bawah ini:\n\nNama    : [Nama Lengkap]\nJabatan : [Jabatan]\nDivisi  : [Divisi]\n\nbermaksud mengajukan pengunduran diri dari [Nama Perusahaan] terhitung sejak [tanggal]. Keputusan ini saya ambil setelah mempertimbangkannya dengan matang.\n\nSaya mengucapkan terima kasih atas kesempatan, bimbingan, dan pengalaman berharga selama bekerja. Saya memohon maaf atas segala kekurangan dan kesalahan selama masa kerja.\n\nDemikian surat ini saya sampaikan. Atas perhatian dan pengertiannya, saya ucapkan terima kasih.\n\nHormat saya,\n\n\n[Nama Lengkap]"),

doc("buatkan deskripsi produk untuk toko online, produknya tas kulit",
    "Deskripsi Produk: Tas Kulit Premium",
    "# Tas Kulit Premium Handmade\n\nTampil elegan dan berkelas dengan **Tas Kulit Premium** buatan tangan yang dirancang untuk menemani aktivitas harian maupun acara formal Anda.\n\n## Keunggulan\n\n- **Bahan kulit asli** pilihan yang lembut namun tahan lama.\n- **Jahitan rapi** hasil pengerjaan tangan pengrajin berpengalaman.\n- **Ruang luas** dengan beberapa sekat untuk dompet, ponsel, dan dokumen.\n- **Desain timeless** yang cocok untuk pria maupun wanita.\n\n## Spesifikasi\n\n| Detail | Keterangan |\n|--------|------------|\n| Bahan  | Kulit sapi asli |\n| Ukuran | 30 x 25 x 10 cm |\n| Warna  | Cokelat, Hitam |\n| Garansi | Jahitan 1 tahun |\n\nMiliki sekarang dan rasakan perpaduan kualitas dan gaya dalam satu tas. Stok terbatas!"),

doc("buatkan ringkasan materi tentang sistem tata surya",
    "Ringkasan Materi: Sistem Tata Surya",
    "# Ringkasan: Sistem Tata Surya\n\n## Pengertian\n\nTata surya adalah susunan benda langit yang terdiri atas Matahari sebagai pusat, dikelilingi oleh planet, satelit, asteroid, komet, dan benda langit lainnya yang terikat oleh gaya gravitasi Matahari.\n\n## Anggota Tata Surya\n\n1. **Matahari** — bintang yang menjadi pusat dan sumber energi utama.\n2. **Planet** — delapan planet: Merkurius, Venus, Bumi, Mars, Jupiter, Saturnus, Uranus, dan Neptunus.\n3. **Satelit** — benda langit yang mengelilingi planet, misalnya Bulan.\n4. **Asteroid** — benda kecil yang sebagian besar berada di antara Mars dan Jupiter.\n5. **Komet** — benda langit berekor yang tersusun dari es dan debu.\n\n## Pengelompokan Planet\n\n| Kelompok | Anggota |\n|----------|---------|\n| Planet dalam | Merkurius, Venus, Bumi, Mars |\n| Planet luar  | Jupiter, Saturnus, Uranus, Neptunus |\n\n## Kesimpulan\n\nTata surya merupakan sistem yang teratur dengan Matahari sebagai pusat gravitasi. Setiap anggotanya bergerak pada lintasannya masing-masing secara harmonis."),

doc("buatkan memo internal kantor tentang perubahan jam kerja",
    "Memo Internal: Perubahan Jam Kerja",
    "# MEMO INTERNAL\n\n**Kepada** : Seluruh Karyawan\n**Dari**   : Bagian Sumber Daya Manusia\n**Tanggal**: [Tanggal]\n**Perihal**: Perubahan Jam Kerja\n\nDengan ini diberitahukan bahwa mulai [tanggal berlaku], jam kerja akan disesuaikan sebagai berikut:\n\n| Hari | Jam Kerja |\n|------|-----------|\n| Senin–Kamis | 08.00 – 17.00 |\n| Jumat | 08.00 – 16.30 |\n\nWaktu istirahat tetap pukul 12.00 – 13.00. Seluruh karyawan diharapkan menyesuaikan diri dengan ketentuan baru ini.\n\nAtas perhatian dan kerja samanya, disampaikan terima kasih."),
]

# Contoh KOREKSI: user menyodorkan pola ```markdown yang salah -> asisten pakai <antArtifact>
EXAMPLES += [
chat("buatkan surat izin sakit, jangan pakai blok kode ya",
     "Berikut surat izin tidak masuk karena sakit, sudah dalam format dokumen yang benar.\n\n<antArtifact type=\"text/markdown\" title=\"Surat Izin Sakit\">\nHal: Permohonan Izin Tidak Masuk\n\nKepada Yth.\n[Tujuan]\ndi Tempat\n\nDengan hormat,\n\nSaya yang bertanda tangan di bawah ini, [Nama], memberitahukan bahwa pada hari [Hari], tanggal [Tanggal], saya tidak dapat hadir karena sedang sakit dan memerlukan istirahat. Sebagai bukti, saya lampirkan surat keterangan dokter.\n\nDemikian permohonan ini saya sampaikan. Atas perhatian dan izinnya, saya ucapkan terima kasih.\n\nHormat saya,\n\n\n[Nama]\n</antArtifact>",
     SYS_DOC, "artifact-koreksi"),
]

# ═══════════════════════════════════════════════════════════════════════════
# 2) SKRIPSI / AKADEMIK  (front-matter + struktur BAB dalam artifact)
# ═══════════════════════════════════════════════════════════════════════════

EXAMPLES += [
doc("buatkan bagian BAB I pendahuluan skripsi tentang e-wallet dan perilaku konsumtif mahasiswa",
    "Skripsi: Pengaruh E-Wallet terhadap Perilaku Konsumtif Mahasiswa",
    "---\nmode: skripsi\njudul: Pengaruh Penggunaan E-Wallet terhadap Perilaku Konsumtif Mahasiswa\npenulis: Nama Penulis\nnim: 00000000\nprodi: Manajemen\nfakultas: Ekonomi dan Bisnis\nuniversitas: Universitas Contoh\nkota: Jakarta\ntahun: 2026\npembimbing: Nama Pembimbing\n---\n\n# BAB I PENDAHULUAN\n\n## 1.1 Latar Belakang\n\nPerkembangan teknologi finansial telah mengubah cara masyarakat melakukan transaksi, khususnya di kalangan mahasiswa. Kehadiran dompet digital atau e-wallet seperti berbagai aplikasi pembayaran menawarkan kemudahan yang belum pernah ada sebelumnya. Cukup dengan satu ketukan, mahasiswa dapat membayar apa pun tanpa perlu membawa uang tunai.\n\nKemudahan ini di satu sisi meningkatkan efisiensi, namun di sisi lain berpotensi mendorong perilaku konsumtif. Berbagai penawaran, diskon, dan program cashback membuat mahasiswa cenderung berbelanja melebihi kebutuhan. Fenomena ini menarik untuk dikaji lebih dalam mengingat mahasiswa merupakan kelompok yang belum sepenuhnya mapan secara finansial.\n\nBerdasarkan latar belakang tersebut, penelitian ini penting dilakukan untuk memahami sejauh mana penggunaan e-wallet memengaruhi perilaku konsumtif mahasiswa.\n\n## 1.2 Rumusan Masalah\n\n1. Bagaimana pengaruh penggunaan e-wallet terhadap perilaku konsumtif mahasiswa?\n2. Faktor apa saja yang memperkuat pengaruh tersebut?\n\n## 1.3 Tujuan Penelitian\n\nPenelitian ini bertujuan untuk menganalisis pengaruh penggunaan e-wallet terhadap perilaku konsumtif mahasiswa serta mengidentifikasi faktor pendukungnya.\n\n## 1.4 Manfaat Penelitian\n\nSecara teoretis, penelitian ini memperkaya kajian mengenai perilaku konsumen digital. Secara praktis, hasilnya dapat menjadi bahan edukasi literasi keuangan bagi mahasiswa.",
    SYS_SKRIPSI, "Berikut kerangka BAB I skripsi yang sudah tersusun sesuai kaidah akademik, lengkap dengan front-matter untuk halaman sampul.", "skripsi"),

doc("buatkan abstrak skripsi tentang penerapan sistem informasi perpustakaan",
    "Abstrak Skripsi: Sistem Informasi Perpustakaan",
    "# ABSTRAK\n\nPenelitian ini bertujuan merancang dan menerapkan sistem informasi perpustakaan untuk meningkatkan efisiensi pengelolaan data buku dan anggota. Latar belakang penelitian adalah masih digunakannya pencatatan manual yang rawan kesalahan dan memakan waktu. Metode yang digunakan adalah pengembangan perangkat lunak dengan pendekatan waterfall, mencakup analisis kebutuhan, perancangan, implementasi, dan pengujian. Hasil penelitian menunjukkan bahwa sistem yang dibangun mampu mempercepat proses peminjaman dan pengembalian buku serta mengurangi kesalahan pencatatan secara signifikan. Dengan demikian, sistem informasi perpustakaan terbukti meningkatkan efisiensi dan akurasi pengelolaan data.\n\n**Kata Kunci:** sistem informasi, perpustakaan, waterfall, efisiensi, basis data\n\n# ABSTRACT\n\n*This study aims to design and implement a library information system to improve the efficiency of book and member data management. The background of this research is the continued use of manual recording, which is prone to errors and time-consuming. The method used is software development with a waterfall approach, covering requirements analysis, design, implementation, and testing. The results show that the developed system accelerates the borrowing and returning of books and significantly reduces recording errors. Therefore, the library information system is proven to improve the efficiency and accuracy of data management.*\n\n***Keywords:*** *information system, library, waterfall, efficiency, database*",
    SYS_SKRIPSI, "Berikut abstrak skripsi dalam dua bahasa (Indonesia dan Inggris) sesuai kaidah, lengkap dengan kata kunci.", "skripsi"),
]

# ═══════════════════════════════════════════════════════════════════════════
# 3) INSTRUKSI KETAT  (patuh batasan format dengan tepat)
# ═══════════════════════════════════════════════════════════════════════════

EXAMPLES += [
chat("Jawab HANYA dengan satu kata, tanpa tanda baca: apa warna langit saat cerah?",
     "Biru", SYS_STRICT, "instruksi-ketat"),
chat("Sebutkan satu kata: ibu kota Indonesia.",
     "Jakarta", SYS_STRICT, "instruksi-ketat"),
chat("Jawab satu kata saja: lawan kata dari 'panas'.",
     "Dingin", SYS_STRICT, "instruksi-ketat"),
chat("Jawab ya atau tidak saja: apakah air mendidih pada 100 derajat Celsius di permukaan laut?",
     "Ya", SYS_STRICT, "instruksi-ketat"),
chat("Jelaskan apa itu gravitasi dalam TEPAT satu kalimat.",
     "Gravitasi adalah gaya tarik-menarik antara dua benda yang memiliki massa.",
     SYS_STRICT, "instruksi-ketat"),
chat("Sebutkan tepat 3 warna primer, pisahkan dengan koma, tanpa kalimat lain.",
     "Merah, kuning, biru", SYS_STRICT, "instruksi-ketat"),
chat("Ringkas manfaat olahraga dalam maksimal 2 kalimat.",
     "Olahraga menjaga kebugaran tubuh dan kesehatan jantung. Selain itu, olahraga membantu mengurangi stres dan memperbaiki suasana hati.",
     SYS_STRICT, "instruksi-ketat"),
chat("Jawab dengan satu angka saja: hasil dari 9 dikali 8.",
     "72", SYS_STRICT, "instruksi-ketat"),
chat("Tulis nama hari setelah Senin, satu kata, tanpa tanda baca.",
     "Selasa", SYS_STRICT, "instruksi-ketat"),
chat("Sebutkan 5 nama buah dalam bentuk daftar bernomor, tanpa penjelasan.",
     "1. Apel\n2. Mangga\n3. Pisang\n4. Jeruk\n5. Semangka", SYS_STRICT, "instruksi-ketat"),
chat("Jawab satu kata: hewan berkaki empat yang mengeong.",
     "Kucing", SYS_STRICT, "instruksi-ketat"),
chat("Balas dengan tepat satu kalimat pujian yang sopan.",
     "Pekerjaanmu sangat rapi dan menunjukkan ketelitian yang patut diapresiasi.",
     SYS_STRICT, "instruksi-ketat"),
chat("Jawab benar atau salah saja: matahari terbit dari barat.",
     "Salah", SYS_STRICT, "instruksi-ketat"),
chat("Sebutkan warna bendera Indonesia, dua kata saja.",
     "Merah putih", SYS_STRICT, "instruksi-ketat"),
]

# ═══════════════════════════════════════════════════════════════════════════
# 4) ISTILAH TEKNIS BAHASA INDONESIA  (indeks/elemen/panjang/keluaran/...)
# ═══════════════════════════════════════════════════════════════════════════

EXAMPLES += [
chat("jelaskan apa itu array dalam pemrograman",
     "Array (larik) adalah struktur data yang menyimpan sekumpulan nilai dalam satu peubah. Setiap nilai di dalam larik disebut elemen, dan setiap elemen memiliki nomor urut yang disebut indeks. Indeks biasanya dimulai dari 0, sehingga elemen pertama berada pada indeks ke-0. Jumlah seluruh elemen dalam larik disebut panjang. Dengan larik, kita bisa mengelola banyak data sekaligus tanpa membuat banyak peubah terpisah.",
     SYS_TERM, "istilah"),
chat("apa bedanya input dan output pada program?",
     "Masukan (input) adalah data yang diterima program dari pengguna atau sumber lain, misalnya teks yang diketik atau berkas yang diunggah. Keluaran (output) adalah hasil yang dikeluarkan program setelah memproses masukan, misalnya teks yang ditampilkan di layar atau berkas yang disimpan. Singkatnya, program menerima masukan, mengolahnya, lalu menghasilkan keluaran.",
     SYS_TERM, "istilah"),
chat("jelaskan konsep loop pada pemrograman",
     "Perulangan (loop) adalah mekanisme untuk menjalankan sekumpulan perintah berkali-kali selama syarat tertentu terpenuhi. Perulangan berguna agar kita tidak perlu menulis perintah yang sama berulang-ulang. Contohnya, untuk menampilkan seluruh elemen dalam sebuah larik, kita cukup memakai satu perulangan yang berjalan dari indeks awal sampai indeks terakhir sesuai panjang larik.",
     SYS_TERM, "istilah"),
chat("apa itu function dan kenapa dipakai?",
     "Fungsi adalah sekumpulan perintah yang diberi nama dan dapat dipanggil kapan saja. Fungsi biasanya menerima masukan berupa parameter, memprosesnya, lalu memberikan keluaran berupa nilai kembalian. Fungsi dipakai agar kode lebih rapi, mudah dibaca, dan tidak berulang, karena satu fungsi bisa dipanggil berkali-kali dari bagian mana pun dalam program.",
     SYS_TERM, "istilah"),
chat("apa itu variabel?",
     "Peubah (variabel) adalah tempat untuk menyimpan nilai di dalam program. Nilai yang disimpan dalam peubah bisa berupa angka, teks, atau jenis data lain, dan dapat diubah selama program berjalan. Setiap peubah diberi nama agar mudah dirujuk. Misalnya, peubah bernama 'umur' bisa menyimpan angka 20.",
     SYS_TERM, "istilah"),
chat("kenapa programku muncul error terus?",
     "Galat (error) pada program umumnya muncul karena tiga sebab utama: kesalahan penulisan sintaks, kesalahan logika, atau kesalahan saat program berjalan (misalnya mengakses indeks di luar panjang larik). Untuk menemukan sumbernya, baca pesan galat dengan teliti — biasanya pesan itu menunjukkan berkas dan baris tempat kesalahan terjadi. Kalau kamu tempelkan pesan galat dan kodenya, saya bantu telusuri.",
     SYS_TERM, "istilah"),
chat("jelaskan apa itu database secara sederhana",
     "Basis data (database) adalah tempat untuk menyimpan dan mengatur data secara terstruktur agar mudah dicari dan dikelola. Data biasanya disimpan dalam tabel yang terdiri atas baris dan kolom. Dengan basis data, sebuah aplikasi bisa menyimpan masukan pengguna secara permanen dan mengambilnya kembali saat dibutuhkan, tanpa data itu hilang ketika program ditutup.",
     SYS_TERM, "istilah"),
chat("apa itu software dan hardware?",
     "Perangkat lunak (software) adalah kumpulan program dan instruksi yang mengatur cara kerja komputer, misalnya sistem operasi dan aplikasi. Perangkat keras (hardware) adalah komponen fisik komputer yang dapat disentuh, seperti prosesor, papan ketik, dan layar. Keduanya saling bergantung: perangkat keras menjalankan perintah, sedangkan perangkat lunak yang memberi perintahnya.",
     SYS_TERM, "istilah"),
chat("bagaimana cara menghitung panjang sebuah teks?",
     "Panjang sebuah teks dihitung dari jumlah karakter penyusunnya, termasuk spasi. Di banyak bahasa pemrograman, tersedia fungsi bawaan untuk itu, misalnya len() atau .length. Sebagai contoh, teks 'halo' memiliki panjang 4 karena terdiri atas empat karakter. Nilai panjang ini sering dipakai sebagai batas atas dalam perulangan saat menelusuri setiap elemen teks berdasarkan indeksnya.",
     SYS_TERM, "istilah"),
chat("apa itu jaringan komputer?",
     "Jaringan (network) komputer adalah sekumpulan perangkat yang saling terhubung sehingga dapat bertukar data dan berbagi sumber daya, seperti berkas dan pencetak. Hubungan antarperangkat bisa melalui kabel maupun nirkabel. Dengan jaringan, sebuah perangkat dapat mengirim masukan ke perangkat lain dan menerima keluaran sebagai balasannya, misalnya saat kita membuka halaman web.",
     SYS_TERM, "istilah"),
chat("jelaskan apa itu bug dan debugging",
     "Kutu (bug) adalah kesalahan pada program yang menyebabkan keluaran tidak sesuai harapan atau program berhenti. Proses menemukan dan memperbaiki kutu disebut penelusuran galat (debugging). Cara umumnya adalah menelusuri jalannya program, memeriksa nilai tiap peubah, dan mempersempit bagian kode yang menjadi sumber galat sampai ketemu penyebabnya.",
     SYS_TERM, "istilah"),
chat("apa itu file dan folder?",
     "Berkas (file) adalah wadah untuk menyimpan data, misalnya dokumen, gambar, atau program. Map (folder) adalah wadah untuk mengelompokkan beberapa berkas agar tertata rapi. Satu map bisa berisi banyak berkas dan bahkan map lain di dalamnya. Penataan berkas ke dalam map memudahkan kita mencari dan mengelola data.",
     SYS_TERM, "istilah"),
chat("gimana cara menyimpan masukan pengguna ke dalam program?",
     "Masukan pengguna dapat disimpan ke dalam sebuah peubah, lalu jika perlu permanen, ditulis ke berkas atau basis data. Alurnya: program membaca masukan (misalnya teks yang diketik), menyimpannya ke peubah, memprosesnya bila perlu, lalu menampilkan keluaran atau menyimpannya. Dengan basis data, masukan itu tetap tersimpan meski program ditutup dan dibuka kembali.",
     SYS_TERM, "istilah"),
chat("apa itu indeks pada sebuah list?",
     "Indeks (index) adalah nomor urut yang menunjuk posisi sebuah elemen di dalam list atau larik. Indeks umumnya dimulai dari 0, sehingga elemen pertama ada di indeks ke-0, elemen kedua di indeks ke-1, dan seterusnya. Untuk mengambil sebuah elemen, kita menyebut indeksnya. Perlu diingat, indeks tertinggi yang valid adalah panjang list dikurangi satu; melebihi itu akan memicu galat.",
     SYS_TERM, "istilah"),
]

# ── Tulis JSONL ─────────────────────────────────────────────────────────────
def main():
    out = "golden_fixes.jsonl"
    with open(out, "w", encoding="utf-8") as f:
        for ex in EXAMPLES:
            f.write(json.dumps({"messages": ex["messages"], "category": ex["category"]},
                               ensure_ascii=False) + "\n")

    # Ringkas jumlah per kategori
    from collections import Counter
    counts = Counter(ex["category"] for ex in EXAMPLES)
    print(f"Wrote {len(EXAMPLES)} targeted examples -> {out}")
    for cat, n in sorted(counts.items()):
        print(f"  {cat}: {n}")


if __name__ == "__main__":
    main()

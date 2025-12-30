<!DOCTYPE html>
<html lang="id">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Kampus - Politeknik Negeri Batam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset dan Base Styles */
        :root {
            --primary-color: #0056b3;
            --primary-dark: #003d82;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
            --accent-dark: #e0a800;
            --text-color: #333;
            --light-color: #fff;
            --gray-light: #f5f7fa;
            --gray-medium: #6c757d;
        }
 
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background: linear-gradient(to bottom,
                    #f0f1f3ff 80%,
                    #ffffff 100%);
        }
 
        /* Header & Navbar */
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
 
        .navbar-nav .nav-link {
            position: relative;
            padding-bottom: 6px;
        }
 
        .navbar-nav .nav-link::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 0;
            height: 2px;
            background-color: #ffffffff;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
 
        .navbar-nav .nav-link:hover::after,
        .navbar-nav .nav-link.active::after {
            width: 100%;
        }
 
 
        .navbar-brand img {
            height: 50px;
        }
 
        .logo img {
            width: 65px;
            height: auto;
        }
 
        nav a {
            text-decoration: none;
            color: #004aad;
            font-weight: 600;
            margin-left: 25px;
            transition: color 0.2s;
        }
 
        nav a:hover {
            color: #007bff;
        }
 
        /* Main Content */
        .detail-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
 
        .detail-container h2 {
            text-align: center;
            color: #004aad;
            margin-bottom: 20px;
        }
 
        .detail-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }
 
        .detail-info h3 {
            color: #004aad;
            margin-top: 15px;
        }
 
        .detail-info p {
            color: #333;
            line-height: 1.6;
            margin-bottom: 10px;
        }
 
        /* ====== Rules (Aturan) Section ====== */
        .rules-section {
            margin-top: 20px;
            background: #fff;
            padding: 18px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
 
        .rules-section h3 {
            color: #c0392b;
            margin-bottom: 10px;
        }
 
        .rules-content ol {
            margin-left: 18px;
            color: #333;
            line-height: 1.6;
        }
 
        .rules-content li {
            margin-bottom: 8px;
            font-size: 15px;
        }
 
        .rules-content strong {
            color: #004aad;
        }
 
        /* Schedule & Contact */
        .schedule-contact {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
 
        .schedule-box,
        .contact-box {
            flex: 1;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
 
        .schedule-box h3,
        .contact-box h3 {
            color: #004aad;
            font-size: 1.3rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #004aad;
        }
 
        .schedule-box p,
        .contact-box p {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
 
        .contact-box i,
        .schedule-box i {
            color: #004aad;
            margin-right: 10px;
            width: 20px;
        }
 
        /* Button Group */
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }
 
        .button-group button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
 
        .button-group button:hover {
            background-color: #0056b3;
        }
 
        .button-group button:last-child {
            background-color: #6c757d;
        }
 
        .button-group button:last-child:hover {
            background-color: #545b62;
        }
 
        /* Footer */
        footer {
            background-color: #004aad;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 50px;
        }
 
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
 
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
 
        .detail-container>* {
            animation: fadeIn 0.8s ease forwards;
        }
 
        /* List styling */
        .detail-info ul {
            margin-left: 20px;
            margin-bottom: 20px;
            color: #333;
        }
 
        .detail-info li {
            margin-bottom: 8px;
            font-size: 1rem;
        }
 
        /* Responsif untuk HP */
        @media (max-width: 768px) {
            header {
                padding: 10px 25px;
            }
 
            nav a {
                margin-left: 15px;
                font-size: 14px;
            }
 
            .logo img {
                width: 50px;
            }
 
            .detail-container {
                margin: 30px 15px;
                padding: 20px;
            }
 
            .schedule-contact {
                flex-direction: column;
            }
 
            .button-group {
                flex-direction: column;
                align-items: center;
            }
 
            .button-group button {
                width: 100%;
                justify-content: center;
            }
 
            .rules-section {
                padding: 14px;
            }
 
            .rules-content li {
                font-size: 14px;
            }
        }
    </style>
</head>
 
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png"
                    alt="Politeknik Negeri Batam">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    <li class="nav-item">
                        <a class="nav-link" href="daftar.php">Pendaftaran</a>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
 
                    </li>
                </ul>
            </div>
        </div>
    </nav>
 
    <main class="detail-container">
        <h2>Lomba Badminton Mahasiswa</h2>
        <div style="text-align:center; padding:10px;">
            <img src="/images/badminton biru.avif" alt="Turnamen Bulutangkis"
                style="display:block; margin:0 auto 20px auto; max-width:90%; height:auto; border-radius:10px;">
            <h3 style="margin-top:0; font-size:1.5rem; color:#007BFF;"></h3>
        </div>
 
 
 
 
 
 
        <div class="detail-info">
            <h3><i class="fas fa-table-tennis"></i> Bulu Tangkis</h3>
 
 
            <p>
                Lomba bulu tangkis antar jurusan Politeknik Negeri Batam ini jadi ajang seru buat nunjukin kelincahan,
                strategi, dan kekompakan tiap pemain. Gak cuma soal smash atau drop shot, tapi juga soal gimana caranya
                saling dukung, jaga sportivitas, dan tetap fokus di tengah tekanan pertandingan.
            </p>
            <p>
                Event ini rutin diadain tiap tahun dan selalu dinanti karena suasananya rame, penuh semangat, dan bikin
                deg-degan! Setiap tim bisa terdiri dari pemain tunggal dan ganda, jadi semua punya kesempatan buat unjuk
                skill. Selain itu, lomba ini juga jadi wadah buat nambah pengalaman, relasi antarjurusan, dan pastinya
                bikin kenangan yang gak bakal dilupain.
            </p>
 
            <h3><i class="fas fa-bullseye"></i> Tujuan</h3>
            <p>
                Meningkatkan semangat kompetitif, mempererat kerja sama tim, dan menumbuhkan gaya hidup sehat di
                kalangan mahasiswa. Selain itu, acara ini juga bertujuan untuk:
            </p>
            <ul>
                <li>Membangun solidaritas antar jurusan lewat olahraga yang menyenangkan</li>
                <li>Mengembangkan bakat dan minat mahasiswa di bidang bulu tangkis</li>
                <li>Menyalurkan energi dan kreativitas lewat pertandingan yang sportif</li>
                <li>Memperkenalkan bulu tangkis sebagai olahraga yang seru, sehat, dan bisa dinikmati semua kalangan
                </li>
            </ul>
        </div>
 
        <!-- Rules Section -->
        <section id="rules" class="rules-section">
            <h3><i class="fas fa-clipboard-list"></i> Aturan Permainan</h3>
 
            <div class="rules-content">
                <ol>
                    <li><strong>Servis harus diagonal</strong> dari sisi kanan ke kanan lawan atau kiri ke kiri lawan.
                    </li>
                    <li><strong>Aturan Tinggi Servis:</strong> Pada saat servis, shuttlecock harus dipukul di bawah
                        pinggang dan kepala raket mengarah ke bawah.</li>
                    <li><strong>Lama Pertandingan:</strong>Pertandingan menggunakan sistem rally point 21. Best of 3
                        set. Istirahat 60 detik saat interval 11 poin dan 2 menit antar set.</li>
                    <li><strong>Lapangan & Shuttlecock:</strong>Shuttlecock harus sesuai standar (bulu atau sintetis).
                        Garis samping ganda lebih lebar daripada tunggal.</li>
                    <li><strong>Pelanggaran (Foul):</strong>Menyentuh net, memukul shuttlecock dua kali, atau
                        shuttlecock keluar lapangan dianggap fault.</li>
                    <li><strong>Net & Area Bermain:</strong>Pemain tidak boleh menyentuh net atau melangkah melewati
                        garis tengah ke area lawan.</li>
                    <li><strong>Servis Bergantian:</strong>Pergantian servis terjadi setiap kali pihak penerima
                        memenangkan rally. Posisi kanan untuk angka genap, kiri untuk angka ganjil.</li>
                    <li><strong>Rally:</strong>Rally berakhir saat shuttlecock jatuh di lantai, keluar, atau tersangkut
                        net. Pemain yang menang rally mendapat 1 poin.</li>
                    <li><strong>Fair Play:</strong> Pemain wajib bermain sportif, tidak mengganggu lawan, dan mengikuti
                        keputusan wasit.</li>
                    <li><strong>Pendaftaran & Perlengkapan:</strong>Pemain wajib menggunakan raket dan shuttlecock
                        sesuai standar pertandingan, serta mendaftar sebelum waktu yang ditentukan.</li>
                </ol>
            </div>
        </section>
 
        <div class="schedule-contact">
            <div class="schedule-box">
                <h3><i class="fas fa-calendar-alt"></i> Jadwal Pelaksanaan</h3>
                <p><i class="fas fa-calendar-day"></i> <strong>Tanggal:</strong> 13 Januari 2026</p>
                <p><i class="fas fa-clock"></i> <strong>Waktu:</strong> 08.00 - 17.00 WIB</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Tempat:</strong> GOR Garuda</p>
                <p><i class="fas fa-user-plus"></i> <strong>Pendaftaran:</strong> 1 November - 5 Januari 2025</p>
            </div>
 
            <div class="contact-box">
                <h3><i class="fas fa-phone-alt"></i> Kontak Panitia</h3>
                <p><i class="fas fa-user"></i> <strong>Reyvandito</strong></p>
                <p><i class="fas fa-phone"></i> 0878-1355-9019</p>
                <p><i class="fas fa-envelope"></i> Badminton.polibatam@email.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Gor Bulutangkis</p>
            </div>
        </div>
 
<div class="button-group">
    <button onclick="window.location.href='daftar.php'"><i class="fas fa-user-plus"></i> Daftar Sekarang</button>
    <button onclick="window.location.href='index.php'">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Lomba
    </button>

</div>
    </main>
 
    <footer>
        <p>© 2025 Politeknik Negeri Batam - Turnamen Badminton Antar Mahasiswa</p>
    </footer>
</body>
 
</html>
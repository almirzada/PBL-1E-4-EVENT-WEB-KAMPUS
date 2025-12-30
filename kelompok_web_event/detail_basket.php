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
        <h2>Lomba Basket Mahasiswa</h2>
        <div style="text-align:center; padding:10px;">
            <img src="/images/basket biru.webp" alt="Turnamen Basket"
                style="display:block; margin:0 auto 20px auto; max-width:90%; height:auto; border-radius:10px;">
            <h3 style="margin-top:0; font-size:1.5rem; color:#007BFF;"></h3>
        </div>

        <div class="detail-info">
            <h3><i class="fas fa-basketball-ball"></i> Basket</h3>
            <p>
                Lomba basket antar jurusan Polibatam ini jadi ajang paling ditunggu tiap tahunnya buat nunjukin siapa
                yang
                paling solid dan kompak di lapangan! Nggak cuma soal ngejar poin dan nge-dunk kece, tapi juga soal kerja
                sama,
                strategi, dan kekompakan tim dari awal sampai peluit akhir.
            </p>
            <p>
                Suasana pertandingan dijamin seru banget — teriakan supporter, dentuman bola ke lantai, dan semangat
                juang
                dari tiap pemain bikin atmosfer lapangan makin panas! Tiap jurusan wajib punya tim terbaiknya, dan siapa
                pun
                bisa jadi bintang kalau punya mental kuat dan kerja sama yang solid.
            </p>
            <p>
                Event ini juga bukan cuma tentang menang atau kalah, tapi soal kebersamaan, sportivitas, dan gimana
                caranya
                bisa nunjukin semangat mahasiswa Polibatam yang nggak gampang nyerah. Jadi, siapin jersey terbaikmu,
                latih
                dribble dan shooting-mu, karena di sinilah tempat buat buktiin skill-mu di lapangan basket Polibatam!
                🏀🔥
            </p>

            <h3><i class="fas fa-bullseye"></i> Tujuan</h3>
            <p>
                Meningkatkan semangat sportivitas dan kerja sama tim antar mahasiswa. Selain itu, acara ini juga
                bertujuan
                untuk:
            </p>
            <ul>
                <li>Melatih fokus, strategi, dan ketahanan fisik lewat permainan kompetitif</li>
                <li>Membangun solidaritas antar jurusan lewat kompetisi yang positif dan menyenangkan</li>
                <li>Menjadi ajang menyalurkan hobi dan bakat di bidang olahraga basket</li>
                <li>Mengembangkan karakter kepemimpinan dan kerja sama dalam tim</li>
            </ul>
        </div>

        <!-- Rules Section -->
        <section id="rules" class="rules-section">
            <h3><i class="fas fa-clipboard-list"></i> Aturan Permainan</h3>

            <div class="rules-content">
                <ol>
                    <li><strong>Jumlah Pemain:</strong> Setiap tim terdiri dari 5 pemain inti dan maksimal 5 pemain
                        cadangan.
                        Minimal 3 pemain harus ada di lapangan untuk melanjutkan pertandingan.</li>
                    <li><strong>Durasi Pertandingan:</strong> Pertandingan berlangsung 4 babak × 10 menit waktu kotor.
                        Istirahat
                        antar babak selama 2 menit, dan 5 menit di antara babak kedua dan ketiga.</li>
                    <li><strong>Pergantian Pemain:</strong> Pergantian pemain bisa dilakukan kapan saja saat bola mati
                        dengan
                        izin wasit.</li>
                    <li><strong>Pelanggaran (Foul):</strong> Setiap pemain hanya boleh melakukan 5 pelanggaran pribadi.
                        Setelah
                        tim melakukan 5 pelanggaran dalam satu babak, tim lawan mendapatkan free throw.</li>
                    <li><strong>Three-Point Line:</strong> Poin dihitung 3 jika bola masuk dari luar garis 3 poin, 2
                        poin dari
                        dalam area, dan free throw bernilai 1 poin.</li>
                    <li><strong>Waktu Serangan (Shot Clock):</strong> Setiap tim punya waktu 24 detik buat nyerang dan
                        melakukan
                        tembakan. Kalau bola nggak kena ring dalam waktu itu, bola beralih ke lawan.</li>
                    <li><strong>Overtime:</strong> Kalau skor seri di akhir pertandingan, akan ada perpanjangan waktu 5
                        menit.
                    </li>
                    <li><strong>Fair Play:</strong> Dilarang melakukan provokasi, adu mulut, atau tindakan kasar. Pemain
                        wajib
                        menghormati keputusan wasit.</li>
                    <li><strong>Perlengkapan Wajib:</strong> Semua pemain harus memakai jersey bernomor, sepatu basket,
                        dan
                        pelindung lutut (opsional).</li>
                    <li><strong>Penentuan Pemenang:</strong> Pemenang ditentukan dari skor tertinggi di akhir
                        pertandingan atau
                        perpanjangan waktu.</li>
                </ol>
            </div>
        </section>

        <div class="schedule-contact">
            <div class="schedule-box">
                <h3><i class="fas fa-calendar-alt"></i> Jadwal Pelaksanaan</h3>
                <p><i class="fas fa-calendar-day"></i> <strong>Tanggal:</strong> 13 Desember 2025</p>
                <p><i class="fas fa-clock"></i> <strong>Waktu:</strong> 08.00 - 17.00 WIB</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Tempat:</strong> Lapangan Basket Polibatam</p>
                <p><i class="fas fa-user-plus"></i> <strong>Pendaftaran:</strong> 1 November - 5 Desember 2025</p>
            </div>

            <div class="contact-box">
                <h3><i class="fas fa-phone-alt"></i> Kontak Panitia</h3>
                <p><i class="fas fa-user"></i> <strong>Amadeo Duscha Roberd</strong></p>
                <p><i class="fas fa-phone"></i> 0878-1355-90178</p>
                <p><i class="fas fa-envelope"></i> basket.polibatam@email.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Gedung Olahraga Polibatam</p>
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
        <p>© 2025 Politeknik Negeri Batam - Turnamen Basket Antar Jurusan</p>
    </footer>
</body>

</html>
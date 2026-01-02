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

        /* Footer - Diperbarui sesuai gambar */
        footer {
            background-color: #004aad;
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .footer-info {
            flex: 1;
            min-width: 300px;
        }

        .footer-info h3 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .footer-info p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .footer-links {
            flex: 0 0 200px;
        }

        .footer-links h4 {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .footer-links ul {
            list-style: none;
            padding-left: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }

        .footer-separator {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 0 auto;
            width: 100%;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
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

            .footer-container {
                padding: 0 20px;
            }

            .footer-top {
                flex-direction: column;
                gap: 30px;
            }

            .footer-info, .footer-links {
                width: 100%;
            }

            .footer-links {
                margin-top: 10px;
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
        <h2>Lomba Futsal Mahasiswa</h2>
        <div style="text-align:center; padding:10px;">
            <img src="/images/futsal biru.jpg" alt="Turnamen Futsal"
                style="display:block; margin:0 auto 20px auto; max-width:90%; height:auto; border-radius:10px;">
            <h3 style="margin-top:0; font-size:1.5rem; color:#007BFF;"></h3>
        </div>

        <div class="detail-info">
            <h3><i class="fas fa-futbol text-primary"></i> Futsal</h3>
            <p>
                Lomba futsal antar jurusan Politeknik Negeri Batam ini jadi ajang seru buat nunjukin semangat, skill,
                dan kekompakan tiap jurusan. Gak cuma soal menang atau kalah, tapi juga soal gimana caranya kerja bareng
                satu tim, saling dukung, dan jaga sportivitas.
            </p>
            <p>
                Event ini diadain tiap tahun dan selalu jadi momen yang ditunggu mahasiswa karena suasananya rame, seru,
                dan penuh energi positif!
                Setiap tim wajib punya formasi lengkap maksimal 10 pemain (termasuk kapten), jadi semua bisa dapet
                giliran buat turun ke lapangan. Selain itu, lomba ini juga bisa jadi wadah buat nyari pengalaman baru,
                nambah relasi antarjurusan, dan pastinya bikin kenangan yang gak bakal dilupain.
            </p>

            <h3><i class="fas fa-bullseye"></i> Tujuan</h3>
            <p>
                Meningkatkan semangat kompetitif, mempererat kerja sama tim, dan menumbuhkan
                gaya hidup sehat di kalangan mahasiswa. Selain itu, acara ini juga bertujuan untuk:
            </p>
            <ul style="margin-left: 20px; margin-bottom: 20px; color: #333;">
                <li>Membangun solidaritas antar jurusan di lingkungan kampus</li>
                <li>Mengembangkan bakat dan minat mahasiswa di bidang olahraga</li>
                <li>Menciptakan wadah positif untuk menyalurkan energi dan kreativitas mahasiswa</li>
                <li>Memperkenalkan olahraga futsal sebagai aktivitas yang menyenangkan dan menyehatkan</li>
            </ul>
        </div>

        <!-- Rules Section -->
        <section id="rules" class="rules-section">
            <h3><i class="fas fa-clipboard-list"></i> Aturan Permainan</h3>

            <div class="rules-content">
                <ol>
                    <li><strong>Jumlah Pemain:</strong> Tiap tim maksimal 10 pemain (5 pemain inti + 5 cadangan(minimal
                        2 cadangan)). Minimal 3 pemain di lapangan.</li>
                    <li><strong>Safety:</strong> Setiap pemain wajib menggunakan dua pasang deker dan sepatu futsal yang
                        sesuai standar keamanan.</li>
                    <li><strong>Lama Pertandingan:</strong> 2 × 10 menit waktu kotor. Istirahat 5 menit antar babak.
                    </li>
                    <li><strong>Gawang & Bola:</strong> Ukuran gawang 3m × 2m. Bola nomor 4 dengan pantulan kecil.</li>
                    <li><strong>Pelanggaran (Foul):</strong> Tarik kaos, senggolan berbahaya, atau tangan (kecuali
                        kiper) dihitung foul. Setelah 5 foul, tim lawan mendapat tendangan bebas tanpa pagar.</li>
                    <li><strong>Kiper:</strong> Kiper hanya boleh memegang bola selama maksimal 4 detik di area
                        gawangnya.</li>
                    <li><strong>Restart:</strong> Kick-in untuk bola keluar samping; corner untuk bola kena pemain
                        bertahan; goal kick untuk gol sendiri.</li>
                    <li><strong>Kartu & Sanksi:</strong> Kartu kuning peringatan; kartu merah keluarkan pemain dan tim
                        main dengan jumlah berkurang sesuai ketentuan sementara.</li>
                    <li><strong>Fair Play:</strong> Jaga sportivitas. Keputusan wasit bersifat final.</li>
                    <li><strong>Pendaftaran:</strong> Setiap tim wajib mendaftarkan diri sebelum batas waktu yang
                        ditentukan dengan melengkapi semua persyaratan.</li>
                </ol>
            </div>
        </section>

        <div class="schedule-contact">
            <div class="schedule-box">
                <h3><i class="fas fa-calendar-alt"></i> Jadwal Pelaksanaan</h3>
                <p><i class="fas fa-calendar-day"></i> <strong>Tanggal:</strong> 13 Desember 2025</p>
                <p><i class="fas fa-clock"></i> <strong>Waktu:</strong> 08.00 - 17.00 WIB</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Tempat:</strong> Lapangan Futsal Polibatam</p>
                <p><i class="fas fa-user-plus"></i> <strong>Pendaftaran:</strong> 1 November - 5 Desember 2025</p>
            </div>

            <div class="contact-box">
                <h3><i class="fas fa-phone-alt"></i> Kontak Panitia</h3>
                <p><i class="fas fa-user"></i> <strong>Reyvandito</strong></p>
                <p><i class="fas fa-phone"></i> 0878-1355-9019</p>
                <p><i class="fas fa-envelope"></i> futsal.polibatam@email.com</p>
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

    <!-- Footer sesuai gambar dengan warna biru -->
    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-info">
                    <h3>Politeknik Negeri Batam</h3>
                    <p>Jl. Ahmad Yani, Batam Kota, Batam 29461</p>
                    <p>Kepulauan Riau, Indonesia</p>
                    <p>Telp: (0778) 469856</p>
                    <p>Email: info@polibatam.ac.id</p>
                </div>
                
                <div class="footer-links">
                    <h4>Tautan Cepat</h4>
                    <ul>
                        <!-- Tautan Beranda -->
                        <li><a href="index.php" style="color: white;">Beranda</a></li>
                        <!-- Tautan Pendaftaran -->
                        <li><a href="daftar.php" style="color: white;">Pendaftaran</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="footer-separator">
            
            <div class="footer-bottom">
                <p>&copy; 2025 Politeknik Negeri Batam. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Fungsi untuk navigasi yang lebih smooth
        document.addEventListener('DOMContentLoaded', function () {
            // Tambahkan event listener untuk semua link navbar
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    // Hapus kelas active dari semua link
                    navLinks.forEach(l => l.classList.remove('active'));
                    // Tambahkan kelas active ke link yang diklik
                    this.classList.add('active');
                });
            });

            // Tambahkan efek loading untuk tombol
            const buttons = document.querySelectorAll('.button-group button');
            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    // Tambahkan efek loading sementara
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
                    this.disabled = true;

                    // Simulasi loading sebelum navigasi
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 1000);
                });
            });
        });

        // Fungsi untuk kembali ke halaman sebelumnya
        function goBack() {
            window.history.back();
        }
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="id">
 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Kampus - Politeknik Negeri Batam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
 
        /* Hero Section Informasi Kampus */
        .hero-section {
            background:
                linear-gradient(135deg, rgba(16, 17, 17, 0.65) 0%, rgba(8, 8, 8, 0.55) 100%),
                url('https://www.polibatam.ac.id/wp-content/uploads/2022/04/MG_8893-scaled.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 120px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 85vh;
            display: flex;
            align-items: center;
        }
 
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }
 
        .hero-title {
            font-weight: 800;
            margin-bottom: 25px;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.4);
            font-size: 3.5rem;
            line-height: 1.1;
        }
 
        .hero-subtitle {
            font-size: 1.4rem;
            margin-bottom: 35px;
            opacity: 0.95;
            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.3);
            line-height: 1.6;
        }
 
        .hero-section h1 {
            font-size: 64px;
            /* sebelumnya 48px */
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
            text-align: center;
        }
 
        .hero-section p {
            font-size: 28px;
            /* sebelumnya 24px */
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            text-align: center;
            font-weight: 200px;
        }
 
 
        .btn-hero {
            background-color: var(--primary-color);
            color: #f7fafcff;
            font-weight: 600;
            padding: 15px 40px;
            border-radius: 30px;
            border: none;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
 
        .btn-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s;
            z-index: -1;
        }
 
        .btn-hero:hover {
            background-color: #0093f5ff;
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.35);
        }
 
        .btn-hero:hover::before {
            left: 100%;
        }
 
        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
            text-align: center;
        }
 
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--accent-color);
        }
 
        .news-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
 
        .news-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
 
        .news-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
 
        .card-body {
            padding: 20px;
        }
 
        .news-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
 
        .news-title {
            font-weight: 600;
            margin: 10px 0;
            color: var(--primary-color);
        }
 
        .read-more {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
 
        .read-more:hover {
            color: #003d82;
        }
 
        .read-more i {
            margin-left: 5px;
            transition: transform 0.3s;
        }
 
        .read-more:hover i {
            transform: translateX(5px);
        }
 
        .slider-container {
            overflow: hidden;
            position: relative;
            padding: 20px 0;
        }
 
        .slider-track {
            display: flex;
            animation: scroll 30s linear infinite;
        }
 
        .slider-track:hover {
            animation-play-state: paused;
        }
 
        .slider-item {
            flex: 0 0 300px;
            margin: 0 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
 
        .slider-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
 
        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
 
            100% {
                transform: translateX(calc(-300px * 6));
            }
        }
 
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0 20px;
        }
 
        .footer a {
            color: #ddd;
            text-decoration: none;
        }
 
        .footer a:hover {
            color: white;
        }
 
        .social-icons a {
            display: inline-block;
            margin-right: 15px;
            font-size: 1.2rem;
        }
 
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 0;
            }
 
            .slider-item {
                flex: 0 0 250px;
            }
 
            @keyframes scroll {
                0% {
                    transform: translateX(0);
                }
 
                100% {
                    transform: translateX(calc(-250px * 6));
                }
            }
        }
 
        /* Responsive improvements */
        @media (max-width: 992px) {
            .hero-section {
                padding: 100px 0;
                min-height: 80vh;
            }
 
            .hero-title {
                font-size: 2.8rem;
            }
 
            .hero-subtitle {
                font-size: 1.2rem;
            }
 
            .slider-item {
                flex: 0 0 300px;
            }
 
            @keyframes scroll {
                0% {
                    transform: translateX(0);
                }
 
                100% {
                    transform: translateX(calc(-300px * 6));
                }
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
                        <a class="nav-link active" href="index.php">Beranda</a>
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
 
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">Selamat Datang di Event Informasi</h1>
            <p class="lead">Politeknik Negeri Batam</p>
            <a href="daftar.php" class="btn-hero mt-3">Daftar Sekarang</a>
        </div>
    </section>
 
    <!-- Berita Terbaru -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Daftar Lomba</h2>
            <div class="row">
                <!-- Card 1 -->
                <div class="col-md-4 white">
                    <div class="news-card">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTAWzu1-Gdudmb8GEKdDYPl6h6z4PJ1lbz5_w&s"
                            alt="Turnamen Bulutangkis">
                        <div class="card-body">
                            <div class="news-date"><i class="far fa-calendar-alt me-2"></i>15 Oktober 2025</div>
                            <h3 class="news-title">Turnamen Bulutangkis Antar Program Studi</h3>
                            <p class="card-text">Polibatam kembali mengadakan turnamen bulutangkis tahunan yang diikuti
                                oleh seluruh program studi.</p>
                            <a href="detail_badminton.php" class="read-more">Baca Selengkapnya <i
                                    class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
 
                <!-- Card 2 -->
                <div class="col-md-4">
                    <div class="news-card">
                        <img src="https://d1vbn70lmn1nqe.cloudfront.net/prod/wp-content/uploads/2023/05/30084044/Teknik-Dasar-Olahraga-Futsal-dan-Beragam-Aturannya.jpg.webp"
                            alt="Kompetisi Futsal">
                        <div class="card-body">
                            <div class="news-date"><i class="far fa-calendar-alt me-2"></i>12 Oktober 2025</div>
                            <h3 class="news-title">Kompetisi Futsal Mahasiswa Polibatam 2025</h3>
                            <p class="card-text">Tunjukkan skill futsal terbaikmu dalam kompetisi bergengsi yang
                                diadakan oleh BEM Polibatam.</p>
                            <a href="detail_futsal.php" class="read-more">Baca Selengkapnya <i
                                    class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
 
                <!-- Card 3 -->
                <div class="col-md-4">
                    <div class="news-card">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRUhkXs-cRZSJwWrdAsEvKd4U1Q17ZbHfdutg&s"
                            alt="Liga Basket">
                        <div class="card-body">
                            <div class="news-date"><i class="far fa-calendar-alt me-2"></i>10 Oktober 2023</div>
                            <h3 class="news-title">Liga Basket Mahasiswa Polibatam Musim 2025/2026</h3>
                            <p class="card-text">Siap-siap untuk liga basket paling seru di kampus! Pendaftaran tim
                                dibuka hingga 25 Oktober.</p>
                            <a href="detail_basket.php" class="read-more">Baca Selengkapnya <i
                                    class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
 
    <!-- Slider Berita -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Galeri Kegiatan</h2>
            <div class="slider-container">
                <div class="slider-track">
                    <!-- Item 1 -->
                    <div class="slider-item">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQgn82x4UtFJsMFce-bLCHGaM0pMhuReXyQDQ&s">
                    </div>
                    <!-- Item 2 -->
                    <div class="slider-item">
                        <img src="https://res.cloudinary.com/dk0z4ums3/image/upload/v1687743325/attached_image/7-manfaat-bulu-tangkis-untuk-kesehatan-tubuh.jpg"
                            alt="Seminar">
                    </div>
                    <!-- Item 3 -->
                    <div class="slider-item">
                        <img src="https://smkn9malang.sch.id/wp-content/uploads/2025/03/foot2.jpg"
                            alt="Workshop">
                    </div>
                    <!-- Item 4 -->
                    <div class="slider-item">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQmtrUjHgbfLL-dPeCOdkcw9Ej0aOPXaNf0XQ&s"
                            alt="Olahraga">
                    </div>
                    <!-- Item 5 -->
                    <div class="slider-item">
                        <img src="https://asset.kompas.com/crops/A2DNrD6e27JAaq6-U0eom5Bwic4=/126x0:1000x583/1200x800/data/photo/2020/08/20/5f3e42f3b6023.jpg"
                            alt="Presentasi">
                    </div>
                    <!-- Item 6 -->
                    <div class="slider-item">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQO-5Lj46dFOc6FxxtCl8R9ljounEg0U7Ifxg&s"
                            alt="Festival">
                    </div>
                    <!-- Duplikat untuk efek loop -->
                    <div class="slider-item">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT1TmeKlDpcaaREOicdG6ucvyTHSz_L-Kuxzw&s"
                            alt="Kegiatan Kampus">
                    </div>
                    <div class="slider-item">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQekQuokMRU8KtgHCnVqlReFeQm9RjV02AYuQ&s"
                            alt="Seminar">
                    </div>
                    <div class="slider-item">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ5LU4Izs4qs4rJ36dw4n-nBSHRYEXO3dY2Dw&s"
                            alt="Workshop">
                    </div>
                </div>
            </div>
        </div>
    </section>
 
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Politeknik Negeri Batam</h5>
                    <p>Jl. Ahmad Yani, Batam Kota, Batam 29461</p>
                    <p>Kepulauan Riau, Indonesia</p>
                    <p>Telp: (0778) 469856</p>
                    <p>Email: info@polibatam.ac.id</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Beranda</a></li>
 
 
                        <li><a href="/daftar">Pendaftaran</a></li>
 
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Ikuti Kami</h5>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2025 Politeknik Negeri Batam. All rights reserved.</p>
            </div>
        </div>
    </footer>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
 
</html>
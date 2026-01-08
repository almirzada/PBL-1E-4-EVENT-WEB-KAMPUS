<?php
// Tambahkan di bagian atas file index.php
require_once 'koneksi.php';

// ============================
// 1. AMBIL DATA EVENT
// ============================
$query_events = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon 
                 FROM events e 
                 LEFT JOIN kategori k ON e.kategori_id = k.id 
                 WHERE e.status = 'publik' 
                 ORDER BY e.created_at DESC 
                 LIMIT 3";

$result_events = mysqli_query($conn, $query_events);
$events = [];
if ($result_events) {
    while ($row = mysqli_fetch_assoc($result_events)) {
        $events[] = $row;
    }
}

// ============================
// 2. AMBIL DATA BERITA UTAMA
// ============================
$query_berita = "SELECT * FROM berita 
                 WHERE status = 'publik' 
                 ORDER BY created_at DESC 
                 LIMIT 3";

$result_berita = mysqli_query($conn, $query_berita);
$berita = [];
if ($result_berita) {
    while ($row = mysqli_fetch_assoc($result_berita)) {
        $berita[] = $row;
    }
}

// ============================
// 3. AMBIL DATA UNTUK SLIDER
// ============================
$slider_query = "SELECT poster FROM events WHERE poster IS NOT NULL AND status = 'publik' 
                 UNION 
                 SELECT gambar FROM berita WHERE gambar IS NOT NULL AND status = 'publik' 
                 LIMIT 9";
$slider_result = mysqli_query($conn, $slider_query);
$slider_images = [];
if ($slider_result) {
    while ($row = mysqli_fetch_assoc($slider_result)) {
        if (!empty($row['poster'])) {
            $slider_images[] = $row['poster'];
        }
    }
}

// Default images jika database kosong
$default_images = [
    'https://www.polibatam.ac.id/wp-content/uploads/2022/04/MG_8893-scaled.jpg',
    'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1523580494863-6f3031224c94?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1542744095-fcf48d80b0fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1524178234883-043d5c3f3cf4?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Informasi Kampus - Politeknik Negeri Batam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* SEMUA CSS SAMA PERSIS SEBELUMNYA */
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

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* HERO SECTION */
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
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
            text-align: center;
        }

        .hero-section p {
            font-size: 28px;
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

        /* NEWS CARD */
        .news-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height: 100%;
            background: white;
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
            font-size: 1.2rem;
            line-height: 1.4;
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

        /* SLIDER */
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
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-300px * 6)); }
        }

        /* FOOTER */
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

        /* BADGE */
        .badge-berita {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
        }
        .badge-informasi { background: #17a2b8; }
        .badge-pengumuman { background: #28a745; }
        .badge-beasiswa { background: #ffc107; color: #000; }
        .badge-akademik { background: #6f42c1; }
        .badge-kemahasiswaan { background: #e83e8c; }

        /* KATEGORI BADGE */
        .kategori-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .hero-section { padding: 60px 0; }
            .slider-item { flex: 0 0 250px; }
            @keyframes scroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(calc(-250px * 6)); }
            }
        }

        @media (max-width: 992px) {
            .hero-section { padding: 100px 0; min-height: 80vh; }
            .hero-title { font-size: 2.8rem; }
            .hero-subtitle { font-size: 1.2rem; }
            .slider-item { flex: 0 0 300px; }
            @keyframes scroll {
                0% { transform: translateX(0); }
                100% { transform: translateX(calc(-300px * 6)); }
            }
        }
        
        /* STATS BAR */
        .stats-bar {
            background: var(--primary-color);
            color: white;
            padding: 15px 0;
            margin-bottom: 40px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
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
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="berita.php">Berita Kampus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="event.php">Event & Kegiatan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">Portal Informasi Kampus</h1>
            <p class="lead">Politeknik Negeri Batam - Sumber Informasi Terpercaya</p>
            <div class="mt-4">
                <a href="berita.php" class="btn-hero me-3">Lihat Berita Terkini</a>
                <a href="daftar.php" class="btn-hero me-3">Daftar Sekarang</a>
                <a href="event.php" class="btn-hero btn-outline-light">Cek Event Kampus</a>

            </div>
        </div>
    </section>

    <!-- ============================
        BERITA UTAMA (NEW SECTION)
    ============================ -->
    <section id="berita" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-newspaper me-2"></i> Berita Utama Kampus
            </h2>
            <p class="text-center text-muted mb-4">Informasi terkini seputar akademik, beasiswa, pengumuman, dan kegiatan kampus</p>
            
            <div class="row">
                <?php if (!empty($berita)): ?>
                    <?php foreach ($berita as $item): ?>
                        <div class="col-md-4">
                            <div class="news-card">
                                <!-- Gambar berita -->
                                <img src="<?php 
                                    echo !empty($item['gambar']) ? htmlspecialchars($item['gambar']) : 
                                    'https://images.unsplash.com/photo-1588681664899-f142ff2dc9b1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>">
                                
                                <div class="card-body">
                                    <!-- Badge kategori berita -->
                                    <span class="badge-berita badge-<?php echo $item['kategori_berita']; ?>">
                                        <?php 
                                            $kategori_labels = [
                                                'informasi' => 'Informasi',
                                                'pengumuman' => 'Pengumuman',
                                                'beasiswa' => 'Beasiswa',
                                                'akademik' => 'Akademik',
                                                'kemahasiswaan' => 'Kemahasiswaan'
                                            ];
                                            echo $kategori_labels[$item['kategori_berita']] ?? 'Informasi';
                                        ?>
                                    </span>
                                    
                                    <!-- Tanggal berita -->
                                    <div class="news-date">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo date('d F Y', strtotime($item['created_at'])); ?>
                                    </div>
                                    
                                    <!-- Judul berita -->
                                    <h3 class="news-title"><?php echo htmlspecialchars($item['judul']); ?></h3>
                                    
                                    <!-- Excerpt berita -->
                                    <p class="card-text">
                                        <?php 
                                            if (!empty($item['excerpt'])) {
                                                echo htmlspecialchars($item['excerpt']);
                                            } else {
                                                echo substr(strip_tags($item['konten']), 0, 120) . '...';
                                            }
                                        ?>
                                    </p>
                                    
                                    <!-- Views counter -->
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="text-muted">
                                            <i class="fas fa-eye me-1"></i> <?php echo $item['views'] ?? 0; ?> dilihat
                                        </span>
                                        <a href="detail_berita.php?id=<?php echo $item['id']; ?>" class="read-more">
                                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback jika database kosong -->
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Belum ada berita yang dipublikasikan. Admin dapat menambahkan berita melalui panel admin.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($berita)): ?>
                <div class="text-center mt-4">
                    <a href="berita.php" class="btn btn-primary px-4">
                        <i class="fas fa-list me-1"></i> Lihat Semua Berita
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================
        EVENT TERBARU
    ============================ -->
    <section id="event" class="py-5">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-calendar-alt me-2"></i> Event & Kegiatan Terbaru
            </h2>
            <p class="text-center text-muted mb-4">Kegiatan dan acara yang akan datang di kampus</p>
            
            <div class="row">
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="col-md-4">
                            <div class="news-card">
                                <!-- Gambar event -->
                                <img src="<?php 
                                    echo !empty($event['poster']) ? htmlspecialchars($event['poster']) : 
                                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                ?>" alt="<?php echo htmlspecialchars($event['judul']); ?>">
                                
                                <div class="card-body">
                                    <!-- Tanggal event -->
                                    <div class="news-date">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo date('d F Y', strtotime($event['tanggal'])); ?>
                                        <?php if (!empty($event['waktu'])): ?>
                                            <br><i class="far fa-clock me-2"></i><?php echo date('H:i', strtotime($event['waktu'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Badge kategori -->
                                    <?php if (!empty($event['kategori_nama'])): ?>
                                        <div class="kategori-badge" style="background: <?php echo $event['warna'] ?? '#0056b3'; ?>">
                                            <i class="<?php echo $event['ikon'] ?? 'fas fa-calendar'; ?> me-1"></i>
                                            <?php echo htmlspecialchars($event['kategori_nama']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Judul event -->
                                    <h3 class="news-title"><?php echo htmlspecialchars($event['judul']); ?></h3>
                                    
                                    <!-- Deskripsi singkat -->
                                    <p class="card-text">
                                        <?php 
                                            if (!empty($event['deskripsi_singkat'])) {
                                                echo htmlspecialchars($event['deskripsi_singkat']);
                                            } else {
                                                echo substr(strip_tags($event['deskripsi']), 0, 120) . '...';
                                            }
                                        ?>
                                    </p>
                                    
                                    <!-- Lokasi -->
                                    <?php if (!empty($event['lokasi'])): ?>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                            <small><?php echo htmlspecialchars($event['lokasi']); ?></small>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <!-- Link ke detail -->
                                    <a href="detail_event.php?id=<?php echo $event['id']; ?>" class="read-more">
                                        Lihat Detail <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Belum ada event yang dipublikasikan. Admin dapat menambahkan event melalui panel admin.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($events)): ?>
                <div class="text-center mt-4">
                    <a href="event.php" class="btn btn-outline-primary px-4">
                        <i class="fas fa-calendar me-1"></i> Lihat Semua Event
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Slider Galeri -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-images me-2"></i> Galeri Kampus
            </h2>
            <div class="slider-container">
                <div class="slider-track">
                    <?php 
                    // Gabungkan gambar dari database dengan default images
                    $all_images = array_merge($slider_images, $default_images);
                    $all_images = array_slice($all_images, 0, 9);
                    
                    foreach ($all_images as $index => $image): 
                        $img_src = (strpos($image, 'http') === 0) ? $image : (!empty($image) ? $image : $default_images[$index % count($default_images)]);
                    ?>
                        <div class="slider-item">
                            <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                 alt="Galeri Kampus <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Duplikat untuk efek loop -->
                    <?php foreach (array_slice($all_images, 0, 3) as $index => $image): ?>
                        <div class="slider-item">
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 alt="Galeri Kampus <?php echo $index + 10; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">

    <!-- STATS BAR -->
    <div class="stats-bar">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($berita); ?></span>
                        <span class="stat-label">Berita Terkini</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($events); ?></span>
                        <span class="stat-label">Event Aktif</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">2025</span>
                        <span class="stat-label">Tahun Akademik</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Update Informasi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="berita.php">Berita Kampus</a></li>
                        <li><a href="event.php">Event & Kegiatan</a></li>
                        <li><a href="admin/login.php">Panel Admin</a></li>
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
                <p>&copy; 2025 Portal Informasi Kampus - Politeknik Negeri Batam</p>
                <small>
                    Terakhir update: <?php echo date('d/m/Y H:i:s'); ?> | 
                    <?php echo (count($berita) + count($events)); ?> konten tersedia
                </small>
            </div>
        </div>
        
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Update counter stats
        function updateStats() {
            const eventCount = <?php echo count($events); ?>;
            const beritaCount = <?php echo count($berita); ?>;
            
            if (eventCount > 0 || beritaCount > 0) {
                document.title = `Portal Kampus (${beritaCount} Berita, ${eventCount} Event) - Polibatam`;
            }
        }
        
        document.addEventListener('DOMContentLoaded', updateStats);
    </script>
</body>
</html>

<?php
// Tutup koneksi database
if (isset($conn)) {
    mysqli_close($conn);
}
?>
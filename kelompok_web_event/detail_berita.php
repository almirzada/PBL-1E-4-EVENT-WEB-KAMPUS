<?php
// ============================
// KONEKSI DATABASE & PROTEKSI
// ============================
require_once 'koneksi.php';

// Cek parameter ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: berita.php");
    exit();
}

$id = intval($_GET['id']);

// ============================
// AMBIL DATA BERITA
// ============================
// Update views terlebih dahulu
mysqli_query($conn, "UPDATE berita SET views = views + 1 WHERE id = $id");

// Ambil data berita
$query = "SELECT * FROM berita WHERE id = $id AND status = 'publik'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: berita.php");
    exit();
}

$berita = mysqli_fetch_assoc($result);

// ============================
// AMBIL BERITA TERKAIT (BERDASARKAN KATEGORI)
// ============================
$kategori = $berita['kategori_berita'];
$related_query = "SELECT id, judul, gambar, excerpt, created_at 
                  FROM berita 
                  WHERE kategori_berita = '$kategori' 
                    AND id != $id 
                    AND status = 'publik' 
                  ORDER BY created_at DESC 
                  LIMIT 3";
$related_result = mysqli_query($conn, $related_query);

// ============================
// AMBIL BERITA TERBARU
// ============================
$latest_query = "SELECT id, judul, created_at, gambar 
                 FROM berita 
                 WHERE status = 'publik' 
                   AND id != $id 
                 ORDER BY created_at DESC 
                 LIMIT 5";
$latest_result = mysqli_query($conn, $latest_query);

// ============================
// KONFIGURASI KATEGORI
// ============================
$kategori_berita = [
    'informasi' => 'Informasi',
    'pengumuman' => 'Pengumuman', 
    'beasiswa' => 'Beasiswa',
    'akademik' => 'Akademik',
    'kemahasiswaan' => 'Kemahasiswaan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($berita['judul']); ?> - Berita Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* VARIABLES & BASE STYLES DARI berita.php */
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
            background: linear-gradient(to bottom, #f0f1f3ff 80%, #ffffff 100%);
            line-height: 1.6;
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
        /* DETAIL BERITA HEADER */
        .detail-header {
            background: linear-gradient(rgba(0, 86, 179, 0.9), rgba(0, 61, 130, 0.9));
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 40px;
        }

        .detail-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        /* BREADCRUMB */
        .breadcrumb-custom {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-custom a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }

        .breadcrumb-custom a:hover {
            color: white;
        }

        /* CONTENT AREA */
        .article-container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* BERITA IMAGE */
        .berita-featured-img {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        /* META INFO */
        .meta-info {
            background: var(--gray-light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-medium);
            margin: 5px 0;
        }

        /* BADGE KATEGORI */
        .badge-berita {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
        }
        .badge-informasi { background: #17a2b8; }
        .badge-pengumuman { background: #28a745; }
        .badge-beasiswa { background: #ffc107; color: #000; }
        .badge-akademik { background: #6f42c1; }
        .badge-kemahasiswaan { background: #e83e8c; }

        /* BERITA CONTENT */
        .berita-content {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 40px;
        }

        .berita-content h2, 
        .berita-content h3, 
        .berita-content h4 {
            color: var(--primary-color);
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .berita-content p {
            margin-bottom: 20px;
        }

        .berita-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }

        .berita-content blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 20px;
            margin: 25px 0;
            font-style: italic;
            color: var(--gray-medium);
        }

        /* SHARE BUTTONS */
        .share-buttons {
            display: flex;
            gap: 10px;
            margin: 30px 0;
            padding: 20px;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .share-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .share-btn:hover {
            transform: translateY(-3px);
            color: white;
        }

        .share-fb { background: #3b5998; }
        .share-twitter { background: #1da1f2; }
        .share-whatsapp { background: #25d366; }

        /* RELATED BERITA */
        .related-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .related-card img {
            height: 150px;
            width: 100%;
            object-fit: cover;
        }

        .related-content {
            padding: 15px;
        }

        .related-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: -webkit-box; 
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* SIDEBAR */
        .sidebar-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .sidebar-title {
            color: var(--primary-color);
            font-weight: 700;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .latest-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .latest-item:last-child {
            border-bottom: none;
        }

        .latest-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .latest-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* NAVIGATION */
        .berita-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            background: var(--gray-light);
            text-decoration: none;
            color: var(--primary-color);
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        /* FOOTER */
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer a {
            color: #ddd;
            text-decoration: none;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .detail-header {
                padding: 60px 0 30px;
            }
            
            .detail-title {
                font-size: 2rem;
            }
            
            .meta-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .share-buttons {
                flex-wrap: wrap;
            }
            
            .berita-navigation {
                flex-direction: column;
                gap: 15px;
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
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="berita.php">Berita Kampus</a>
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

    <!-- HEADER -->
    <div class="detail-header">
        <div class="container">
            <!-- BREADCRUMB -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-custom">
                    <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="berita.php">Berita</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($berita['judul']); ?></li>
                </ol>
            </nav>
            
            <!-- JUDUL -->
            <h1 class="detail-title"><?php echo htmlspecialchars($berita['judul']); ?></h1>
            
            <!-- META INFO -->
            <div class="meta-info">
                <div class="d-flex flex-wrap gap-4">
                    <div class="meta-item">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo date('d F Y', strtotime($berita['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="far fa-clock"></i>
                        <span><?php echo date('H:i', strtotime($berita['created_at'])); ?> WIB</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?php echo $berita['views'] ?? 0; ?> dilihat</span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user-edit"></i>
                        <span>Admin Kampus</span>
                    </div>
                </div>
                
                <!-- KATEGORI -->
                <span class="badge-berita badge-<?php echo $berita['kategori_berita']; ?>">
                    <?php echo $kategori_berita[$berita['kategori_berita']] ?? 'Informasi'; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <div class="row">
            <!-- KONTEN UTAMA -->
            <div class="col-lg-8">
                <div class="article-container">
                    <!-- GAMBAR UTAMA -->
                    <?php if (!empty($berita['gambar'])): ?>
                    <img src="<?php echo htmlspecialchars($berita['gambar']); ?>" 
                         alt="<?php echo htmlspecialchars($berita['judul']); ?>" 
                         class="berita-featured-img">
                    <?php endif; ?>
                    
                    <!-- KONTEN BERITA -->
                    <div class="berita-content">
                        <?php 
                        // Tampilkan konten berita (mendukung HTML)
                        echo nl2br(htmlspecialchars_decode($berita['konten']));
                        ?>
                    </div>
                    
                    <!-- SHARE BUTTONS -->
                    <div class="share-buttons">
                        <span class="me-3 fw-bold">Bagikan:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="share-btn share-fb">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($berita['judul'] . ' - Berita Kampus Polibatam'); ?>&url=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="share-btn share-twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($berita['judul'] . ' ' . "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="share-btn share-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                    
                    <!-- NAVIGASI -->
                    <div class="berita-navigation">
                        <a href="berita.php" class="nav-btn">
                            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Berita
                        </a>
                        <a href="#top" class="nav-btn">
                            <i class="fas fa-arrow-up"></i> Kembali ke Atas
                        </a>
                    </div>
                    
                    <!-- BERITA TERKAIT -->
                    <?php if (mysqli_num_rows($related_result) > 0): ?>
                    <div class="mt-5 pt-4 border-top">
                        <h3 class="mb-4">
                            <i class="fas fa-link me-2"></i>Berita Terkait
                        </h3>
                        <div class="row g-3">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                            <div class="col-md-4">
                                <a href="detail_berita.php?id=<?php echo $related['id']; ?>" class="text-decoration-none">
                                    <div class="related-card">
                                        <?php if (!empty($related['gambar'])): ?>
                                        <img src="<?php echo htmlspecialchars($related['gambar']); ?>" alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                        <?php endif; ?>
                                        <div class="related-content">
                                            <h4 class="related-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                            <div class="latest-meta">
                                                <i class="far fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($related['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <!-- BERITA TERBARU -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-history me-2"></i>Berita Terbaru
                        </h5>
                        <?php if (mysqli_num_rows($latest_result) > 0): ?>
                            <?php mysqli_data_seek($latest_result, 0); ?>
                            <?php while ($latest = mysqli_fetch_assoc($latest_result)): ?>
                            <div class="latest-item">
                                <a href="detail_berita.php?id=<?php echo $latest['id']; ?>" 
                                   class="text-decoration-none">
                                    <h6 class="latest-title">
                                        <?php echo htmlspecialchars($latest['judul']); ?>
                                    </h6>
                                    <div class="latest-meta">
                                        <i class="far fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y', strtotime($latest['created_at'])); ?>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Belum ada berita lainnya
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KATEGORI -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-tags me-2"></i>Kategori Berita
                        </h5>
                        <div class="list-group list-group-flush">
                            <?php foreach ($kategori_berita as $key => $label): ?>
                            <a href="berita.php?kategori=<?php echo $key; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?php echo $label; ?>
                                <span class="badge bg-primary rounded-pill">
                                    <?php 
                                        $count_query = mysqli_query($conn, 
                                            "SELECT COUNT(*) as count FROM berita 
                                             WHERE kategori_berita = '$key' AND status = 'publik'");
                                        $count_row = mysqli_fetch_assoc($count_query);
                                        echo $count_row['count'];
                                    ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL ADMIN -->
                <?php if (isset($_SESSION['admin_event_id']) || isset($_SESSION['role'])): ?>
                <div class="card sidebar-card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-3x mb-3"></i>
                        <h5>Panel Admin</h5>
                        <p class="mb-3">Anda login sebagai admin</p>
                        <a href="admin/dashboard.php" class="btn btn-light btn-sm">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard Admin
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="card sidebar-card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-newspaper fa-3x mb-3"></i>
                        <h5>Ada Berita Baru?</h5>
                        <p class="mb-3">Login sebagai admin untuk mengelola berita</p>
                        <a href="admin/login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt me-1"></i> Login Admin
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
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
                    Halaman Berita Detail | 
                    <?php echo htmlspecialchars($berita['judul']); ?> | 
                    Dibaca <?php echo $berita['views'] ?? 0; ?> kali
                </small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Back to top button
        const backToTop = document.createElement('button');
        backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTop.className = 'btn btn-primary rounded-circle position-fixed';
        backToTop.style.cssText = 'bottom: 20px; right: 20px; width: 50px; height: 50px; z-index: 1000; display: none;';
        document.body.appendChild(backToTop);

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', () => {
            backToTop.style.display = window.scrollY > 300 ? 'block' : 'none';
        });

        // Update judul halaman dengan jumlah views
        const titleElement = document.querySelector('title');
        titleElement.textContent = titleElement.textContent + ` | ${<?php echo $berita['views']; ?>} dilihat`;

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href') === '#') return;
                
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Share buttons dengan feedback
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Biarkan link normal untuk mobile, buka popup untuk desktop
                if (window.innerWidth > 768) {
                    e.preventDefault();
                    window.open(this.href, 'share', 'width=600,height=400');
                }
            });
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi
mysqli_close($conn);
?>
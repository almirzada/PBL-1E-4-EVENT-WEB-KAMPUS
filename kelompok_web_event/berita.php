<?php
require_once 'koneksi.php';

// ============================
// FILTER KATEGORI
// ============================
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_condition = "WHERE status = 'publik'";

// Validasi kategori
$kategori_berita = [
    'informasi' => 'Informasi',
    'pengumuman' => 'Pengumuman', 
    'beasiswa' => 'Beasiswa',
    'akademik' => 'Akademik',
    'kemahasiswaan' => 'Kemahasiswaan'
];

if (!empty($kategori_filter) && array_key_exists($kategori_filter, $kategori_berita)) {
    $filter_condition .= " AND kategori_berita = '$kategori_filter'";
}

// ============================
// KONFIGURASI PAGINATION
// ============================
$limit = 6;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung total berita berdasarkan filter
$total_query = "SELECT COUNT(*) as total FROM berita $filter_condition";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_berita = $total_row['total'];
$total_pages = ceil($total_berita / $limit);

// Ambil berita dengan filter dan pagination
$query = "SELECT * FROM berita 
          $filter_condition 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Ambil berita terpopuler (tanpa filter kategori)
$populer_query = "SELECT * FROM berita 
                  WHERE status = 'publik' 
                  ORDER BY views DESC 
                  LIMIT 3";
$populer_result = mysqli_query($conn, $populer_query);

// Hitung jumlah per kategori
$kategori_counts = [];
foreach ($kategori_berita as $key => $label) {
    $count_query = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM berita 
         WHERE kategori_berita = '$key' AND status = 'publik'");
    $count_row = mysqli_fetch_assoc($count_query);
    $kategori_counts[$key] = $count_row['count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Kampus - Politeknik Negeri Batam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* VARIABLES SAMA */
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

        /* HEADER BERITA */
        .berita-header {
            background: linear-gradient(rgba(0, 86, 179, 0.9), rgba(0, 61, 130, 0.9));
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 40px;
        }

        .berita-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* BERITA CARD */
        .berita-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            background: white;
            border: none;
        }

        .berita-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .berita-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .card-body {
            padding: 25px;
        }

        .berita-date {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .berita-title-card {
            font-weight: 700;
            margin: 12px 0;
            color: var(--primary-color);
            font-size: 1.3rem;
            line-height: 1.4;
        }

        .berita-excerpt {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .read-more {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .read-more:hover {
            color: var(--primary-dark);
        }

        /* BADGE KATEGORI */
        .badge-berita {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
        }
        .badge-informasi { background: #17a2b8; }
        .badge-pengumuman { background: #28a745; }
        .badge-beasiswa { background: #ffc107; color: #000; }
        .badge-akademik { background: #6f42c1; }
        .badge-kemahasiswaan { background: #e83e8c; }

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

        .populer-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .populer-item:last-child {
            border-bottom: none;
        }

        .populer-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .populer-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* FILTER */
        .filter-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 30px;
            padding: 8px 20px;
            margin: 5px;
            transition: all 0.3s;
            color: #555;
            font-weight: 500;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* PAGINATION */
        .pagination-custom .page-link {
            color: var(--primary-color);
            border: 1px solid #dee2e6;
            margin: 0 3px;
            border-radius: 8px;
        }

        .pagination-custom .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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

        /* STATS */
        .stats-box {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
            line-height: 1;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .berita-header {
                padding: 60px 0 30px;
            }
            
            .berita-title {
                font-size: 2.2rem;
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
    <div class="berita-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="berita-title">
                        <i class="fas fa-newspaper me-3"></i>Berita Kampus
                    </h1>
                    <p class="lead mb-0">Informasi terkini seputar akademik, beasiswa, pengumuman, dan kegiatan Politeknik Negeri Batam</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="stats-box">
                        <span class="stat-number"><?php echo $total_berita; ?></span>
                        <span class="stat-label">Berita Tersedia</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <div class="row">
            <!-- KOLOM UTAMA -->
            <div class="col-lg-8">
                <!-- FILTER KATEGORI -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-filter me-2"></i>Filter Berita
                        </h5>
                        <div class="d-flex flex-wrap">
                            <a href="berita.php" class="filter-btn <?php echo !isset($_GET['kategori']) ? 'active' : ''; ?>">
                                Semua Kategori
                            </a>
                            <?php foreach ($kategori_berita as $key => $label): ?>
                            <a href="berita.php?kategori=<?php echo $key; ?>" 
                               class="filter-btn <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == $key) ? 'active' : ''; ?>">
                                <?php echo $label; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- DAFTAR BERITA -->
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="row g-4">
                        <?php while ($berita = mysqli_fetch_assoc($result)): ?>
                        <div class="col-md-6">
                            <div class="berita-card">
                                <!-- Gambar -->
                                <img src="<?php 
                                    echo !empty($berita['gambar']) ? htmlspecialchars($berita['gambar']) : 
                                    'https://images.unsplash.com/photo-1588681664899-f142ff2dc9b1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>">
                                
                                <div class="card-body">
                                    <!-- Badge Kategori -->
                                    <span class="badge-berita badge-<?php echo $berita['kategori_berita']; ?>">
                                        <?php echo $kategori_berita[$berita['kategori_berita']] ?? 'Informasi'; ?>
                                    </span>
                                    
                                    <!-- Tanggal -->
                                    <div class="berita-date">
                                        <i class="far fa-calendar-alt"></i>
                                        <?php echo date('d F Y', strtotime($berita['created_at'])); ?>
                                    </div>
                                    
                                    <!-- Judul -->
                                    <h3 class="berita-title-card">
                                        <?php echo htmlspecialchars($berita['judul']); ?>
                                    </h3>
                                    
                                    <!-- Excerpt -->
                                    <p class="berita-excerpt">
                                        <?php 
                                            if (!empty($berita['excerpt'])) {
                                                echo htmlspecialchars($berita['excerpt']);
                                            } else {
                                                echo substr(strip_tags($berita['konten']), 0, 120) . '...';
                                            }
                                        ?>
                                    </p>
                                    
                                    <!-- Meta -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">
                                            <i class="fas fa-eye"></i> <?php echo $berita['views'] ?? 0; ?> dilihat
                                        </span>
                                        <a href="detail_berita.php?id=<?php echo $berita['id']; ?>" class="read-more">
                                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <!-- JIKA TIDAK ADA BERITA -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-newspaper fa-4x text-muted"></i>
                        </div>
                        <h4 class="text-muted mb-3">Belum ada berita yang dipublikasikan</h4>
                        <p class="text-muted">Admin dapat menambahkan berita melalui panel admin.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda
                        </a>
                    </div>
                <?php endif; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination pagination-custom justify-content-center">
                        <!-- Previous -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="berita.php?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="berita.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- Next -->
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="berita.php?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <!-- BERITA TERPOPULER -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-fire me-2"></i>Berita Terpopuler
                        </h5>
                        <?php if (mysqli_num_rows($populer_result) > 0): ?>
                            <?php mysqli_data_seek($populer_result, 0); ?>
                            <?php while ($populer = mysqli_fetch_assoc($populer_result)): ?>
                            <div class="populer-item">
                                <a href="detail_berita.php?id=<?php echo $populer['id']; ?>" 
                                   class="text-decoration-none">
                                    <h6 class="populer-title">
                                        <?php echo htmlspecialchars($populer['judul']); ?>
                                    </h6>
                                    <div class="populer-meta">
                                        <span>
                                            <i class="far fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($populer['created_at'])); ?>
                                        </span>
                                        <span class="ms-3">
                                            <i class="fas fa-eye me-1"></i>
                                            <?php echo $populer['views']; ?>
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Belum ada berita populer
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
                <div class="card sidebar-card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-3x mb-3"></i>
                        <h5>Admin Kampus?</h5>
                        <p class="mb-3">Kelola berita dan informasi melalui panel admin</p>
                        <a href="admin/login.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-in-alt me-1"></i> Login Admin
                        </a>
                    </div>
                </div>
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
                    Halaman Berita | 
                    <?php echo $total_berita; ?> berita tersedia | 
                    Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                </small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter kategori dengan AJAX (opsional)
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.classList.contains('active')) {
                    e.preventDefault();
                }
            });
        });

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

        // Update judul halaman dengan jumlah berita
        document.title = `Berita Kampus (${<?php echo $total_berita; ?>} Berita) - Polibatam`;
    </script>
</body>
</html>

<?php
// Tutup koneksi
mysqli_close($conn);
?>
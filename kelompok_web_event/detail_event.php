<?php
require_once 'koneksi.php';

// Ambil ID event dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit();
}

// Ambil data event dari database
$query = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon, 
          a.nama as admin_nama 
          FROM events e 
          LEFT JOIN kategori k ON e.kategori_id = k.id 
          LEFT JOIN admin_event a ON e.created_by = a.id 
          WHERE e.id = $id AND e.status = 'publik'";

$result = mysqli_query($conn, $query);
$event = mysqli_fetch_assoc($result);

// Jika event tidak ditemukan atau tidak publik
if (!$event) {
    header('Location: index.php');
    exit();
}

// Update view counter
mysqli_query($conn, "UPDATE events SET views = views + 1 WHERE id = $id");

// Ambil event terkait (kategori yang sama)
$related_query = "SELECT * FROM events 
                  WHERE kategori_id = {$event['kategori_id']} 
                  AND id != $id 
                  AND status = 'publik' 
                  ORDER BY created_at DESC 
                  LIMIT 3";
$related_result = mysqli_query($conn, $related_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['judul']); ?> - Event Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* VARIABLES SAMA DENGAN INDEX */
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

        .navbar-brand img {
            height: 50px;
        }

        /* HEADER EVENT */
        .event-header {
            background: linear-gradient(rgba(0, 86, 179, 0.9), rgba(0, 61, 130, 0.9));
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 40px;
        }

        .event-header-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .event-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: var(--accent-color);
        }

        /* EVENT BODY */
        .event-body {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 40px;
        }

        .event-poster {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 3px solid var(--primary-color);
        }

        .event-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
        }

        /* INFO BOX */
        .info-box {
            background: var(--gray-light);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
        }

        .info-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* BADGE */
        .event-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            color: white;
            margin-bottom: 15px;
        }

        /* RELATED EVENTS */
        .related-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .related-card img {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }

        .related-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 10px 0;
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

        .btn-daftar {
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .btn-daftar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 86, 179, 0.3);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .event-header {
                padding: 60px 0 30px;
            }
            
            .event-title {
                font-size: 2rem;
            }
            
            .event-body {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png" alt="Politeknik Negeri Batam">
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
                        <a class="nav-link" href="berita.php">Berita</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="event.php">Event</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- EVENT HEADER -->
    <div class="event-header">
        <div class="container">
            <div class="event-header-content">
                <!-- BADGE KATEGORI -->
                <div class="event-badge" style="background: <?php echo $event['warna'] ?? '#0056b3'; ?>">
                    <i class="<?php echo $event['ikon'] ?? 'fas fa-calendar'; ?> me-1"></i>
                    <?php echo htmlspecialchars($event['kategori_nama'] ?? 'Event Kampus'); ?>
                </div>

                <!-- JUDUL -->
                <h1 class="event-title"><?php echo htmlspecialchars($event['judul']); ?></h1>

                <!-- META INFO -->
                <div class="event-meta">
                    <div class="meta-item">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo date('d F Y', strtotime($event['tanggal'])); ?></span>
                    </div>
                    
                    <?php if (!empty($event['waktu'])): ?>
                    <div class="meta-item">
                        <i class="far fa-clock"></i>
                        <span>Pukul <?php echo date('H:i', strtotime($event['waktu'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($event['lokasi']); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?php echo $event['views'] + 1; ?> dilihat</span>
                    </div>
                </div>

                <!-- TOMBOL DAFTAR -->
                <?php if (!empty($event['link_pendaftaran'])): ?>
                <a href="<?php echo htmlspecialchars($event['link_pendaftaran']); ?>" 
                   target="_blank" class="btn-daftar">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <div class="row">
            <!-- EVENT DETAIL -->
            <div class="col-lg-8">
                <div class="event-body">
                    <!-- POSTER -->
                    <?php if (!empty($event['poster'])): ?>
                    <img src="<?php echo htmlspecialchars($event['poster']); ?>" 
                         alt="<?php echo htmlspecialchars($event['judul']); ?>" 
                         class="event-poster">
                    <?php endif; ?>

                    <!-- DESKRIPSI -->
                    <div class="event-description">
                        <?php echo nl2br(htmlspecialchars($event['deskripsi'])); ?>
                    </div>

                    <!-- INFO BOX -->
                    <div class="info-box">
                        <h4 class="info-title">
                            <i class="fas fa-info-circle"></i> Informasi Penting
                        </h4>
                        
                        <div class="row">
                            <?php if (!empty($event['alamat_lengkap'])): ?>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-location-dot me-2"></i> Alamat Lengkap:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($event['alamat_lengkap']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['contact_person'])): ?>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-user me-2"></i> Contact Person:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($event['contact_person']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['contact_wa'])): ?>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fab fa-whatsapp me-2"></i> WhatsApp:</strong>
                                <p class="mb-0">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $event['contact_wa']); ?>" 
                                       target="_blank" style="color: var(--primary-color);">
                                        <?php echo htmlspecialchars($event['contact_wa']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event['kuota_peserta'] > 0): ?>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-users me-2"></i> Kuota Peserta:</strong>
                                <p class="mb-0"><?php echo $event['kuota_peserta']; ?> orang</p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($event['biaya_pendaftaran'] > 0): ?>
                            <div class="col-md-6 mb-3">
                                <strong><i class="fas fa-money-bill me-2"></i> Biaya Pendaftaran:</strong>
                                <p class="mb-0">Rp <?php echo number_format($event['biaya_pendaftaran'], 0, ',', '.'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TOMBOL SHARE -->
                    <div class="d-flex gap-3 mt-4">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="btn btn-outline-primary">
                            <i class="fab fa-facebook me-2"></i> Share ke Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&text=<?php echo urlencode($event['judul']); ?>" 
                           target="_blank" class="btn btn-outline-info">
                            <i class="fab fa-twitter me-2"></i> Share ke Twitter
                        </a>
                        <a href="whatsapp://send?text=<?php echo urlencode($event['judul'] . " - " . "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="btn btn-outline-success">
                            <i class="fab fa-whatsapp me-2"></i> Share ke WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <!-- INFO PENYELENGGARA -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-university me-2"></i> Diselenggarakan Oleh
                        </h5>
                        <p class="card-text">
                            <strong>Politeknik Negeri Batam</strong><br>
                            Event ini dikelola oleh <?php echo htmlspecialchars($event['admin_nama'] ?? 'Administrator Kampus'); ?>
                        </p>
                        <p class="text-muted small">
                            <i class="far fa-calendar-plus me-1"></i>
                            Diposting: <?php echo date('d F Y', strtotime($event['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <!-- EVENT TERKAIT -->
                <?php if (mysqli_num_rows($related_result) > 0): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-link me-2"></i> Event Terkait
                        </h5>
                        <div class="row g-3">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): ?>
                            <div class="col-12">
                                <a href="detail_event.php?id=<?php echo $related['id']; ?>" 
                                   class="text-decoration-none">
                                    <div class="d-flex gap-2">
                                        <?php if (!empty($related['poster'])): ?>
                                        <img src="<?php echo htmlspecialchars($related['poster']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="related-title mb-1">
                                                <?php echo htmlspecialchars($related['judul']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="far fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($related['tanggal'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- TOMBOL KEMBALI -->
                <div class="mt-4">
                    <a href="event.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-arrow-left me-2"></i> Lihat Semua Event
                    </a>
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
                <small>Halaman detail event | Terakhir dilihat: <?php echo date('H:i:s'); ?></small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Back to top button
        const backToTop = document.createElement('button');
        backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTop.className = 'btn btn-primary rounded-circle position-fixed';
        backToTop.style.cssText = 'bottom: 20px; right: 20px; width: 50px; height: 50px; display: none;';
        document.body.appendChild(backToTop);

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', () => {
            backToTop.style.display = window.scrollY > 300 ? 'block' : 'none';
        });

        // Print page
        function printPage() {
            window.print();
        }
    </script>
</body>
</html>

<?php
// Tutup koneksi
mysqli_close($conn);
?>
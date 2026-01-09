<?php
require_once 'koneksi.php';

// ============================
// FILTER KATEGORI EVENT
// ============================
$kategori_filter = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$filter_condition = "WHERE e.status = 'publik'";

// Ambil semua kategori untuk filter
$kategori_query = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama");
$kategori_list = [];
while ($row = mysqli_fetch_assoc($kategori_query)) {
    $kategori_list[$row['id']] = $row;
}

if ($kategori_filter > 0 && isset($kategori_list[$kategori_filter])) {
    $filter_condition .= " AND e.kategori_id = $kategori_filter";
}

// ============================
// FILTER TANGGAL
// ============================
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$date_label = 'Semua Event';

if ($date_filter == 'hari-ini') {
    $today = date('Y-m-d');
    $filter_condition .= " AND e.tanggal = '$today'";
    $date_label = 'Event Hari Ini';
} elseif ($date_filter == 'minggu-ini') {
    $start_week = date('Y-m-d');
    $end_week = date('Y-m-d', strtotime('+7 days'));
    $filter_condition .= " AND e.tanggal BETWEEN '$start_week' AND '$end_week'";
    $date_label = 'Event Minggu Ini';
} elseif ($date_filter == 'bulan-ini') {
    $start_month = date('Y-m-01');
    $end_month = date('Y-m-t');
    $filter_condition .= " AND e.tanggal BETWEEN '$start_month' AND '$end_month'";
    $date_label = 'Event Bulan Ini';
} elseif ($date_filter == 'akan-datang') {
    $today = date('Y-m-d');
    $filter_condition .= " AND e.tanggal >= '$today'";
    $date_label = 'Event Akan Datang';
}

// ============================
// KONFIGURASI PAGINATION
// ============================
$limit = 6;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung total event berdasarkan filter
$total_query = "SELECT COUNT(*) as total 
                FROM events e 
                $filter_condition";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_events = $total_row['total'];
$total_pages = ceil($total_events / $limit);

// Ambil event dengan filter dan pagination
$query = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon 
          FROM events e 
          LEFT JOIN kategori k ON e.kategori_id = k.id 
          $filter_condition 
          ORDER BY e.tanggal ASC, e.waktu ASC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Ambil event terpopuler (view tertinggi)
$populer_query = "SELECT e.*, k.nama as kategori_nama, k.warna 
                  FROM events e 
                  LEFT JOIN kategori k ON e.kategori_id = k.id 
                  WHERE e.status = 'publik' 
                  ORDER BY e.views DESC 
                  LIMIT 3";
$populer_result = mysqli_query($conn, $populer_query);

// Ambil event akan datang untuk sidebar
$upcoming_query = "SELECT e.*, k.nama as kategori_nama 
                   FROM events e 
                   LEFT JOIN kategori k ON e.kategori_id = k.id 
                   WHERE e.status = 'publik' 
                   AND e.tanggal >= CURDATE() 
                   ORDER BY e.tanggal ASC 
                   LIMIT 5";
$upcoming_result = mysqli_query($conn, $upcoming_query);

// Hitung jumlah per kategori
$kategori_counts = [];
foreach ($kategori_list as $id => $kategori) {
    $count_query = mysqli_query($conn, 
        "SELECT COUNT(*) as count FROM events 
         WHERE kategori_id = $id AND status = 'publik'");
    $count_row = mysqli_fetch_assoc($count_query);
    $kategori_counts[$id] = $count_row['count'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event & Kegiatan - Politeknik Negeri Batam</title>
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

        /* HEADER EVENT */
        .event-header {
            background: linear-gradient(rgba(0, 86, 179, 0.9), rgba(0, 61, 130, 0.9));
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 40px;
        }

        .event-title {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        /* EVENT CARD */
        .event-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            background: white;
            border: none;
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .event-card img {
            height: 220px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.5s;
        }

        .event-card:hover img {
            transform: scale(1.05);
        }

        .card-body {
            padding: 25px;
        }

        .event-date {
            background: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 15px;
        }

        .event-title-card {
            font-weight: 700;
            margin: 12px 0;
            color: var(--primary-color);
            font-size: 1.3rem;
            line-height: 1.4;
        }

        .event-excerpt {
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
        .badge-event {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
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

        .upcoming-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .upcoming-item:last-child {
            border-bottom: none;
        }

        .upcoming-date {
            background: var(--gray-light);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            color: var(--primary-color);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 5px;
        }

        .upcoming-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #333;
            margin-bottom: 5px;
            line-height: 1.4;
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

        .date-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .date-btn {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 0.9rem;
            color: #555;
            text-decoration: none;
            transition: all 0.3s;
        }

        .date-btn:hover, .date-btn.active {
            background: var(--accent-color);
            color: #000;
            border-color: var(--accent-color);
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

        /* FEATURED BADGE */
        .featured-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent-color);
            color: #000;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            z-index: 2;
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

.footer a:hover {
    color: white;
    text-decoration: underline;
}

.footer h5 {
    color: white;
    margin-bottom: 20px;
    font-weight: 600;
}

.footer ul li {
    margin-bottom: 8px;
}

.social-icons a {
    display: inline-block;
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    text-align: center;
    line-height: 36px;
    margin-right: 10px;
    transition: all 0.3s;
}

.social-icons a:hover {
    background: var(--accent-color);
    color: #000;
    transform: translateY(-3px);
}

        @media (max-width: 768px) {
            .event-header {
                padding: 60px 0 30px;
            }
            
            .event-title {
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
                        <a class="nav-link" href="berita.php">Berita Kampus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="event.php">Event & Kegiatan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HEADER -->
    <div class="event-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="event-title">
                        <i class="fas fa-calendar-alt me-3"></i>
                        <?php 
                            if ($kategori_filter > 0) {
                                echo htmlspecialchars($kategori_list[$kategori_filter]['nama']) . ' - Event Kampus';
                            } else {
                                echo $date_label;
                            }
                        ?>
                    </h1>
                    <p class="lead mb-0">
                        <?php 
                            if ($kategori_filter > 0) {
                                echo 'Kegiatan dan acara ' . strtolower($kategori_list[$kategori_filter]['nama']) . ' di Politeknik Negeri Batam';
                            } else {
                                echo 'Kegiatan, seminar, lomba, dan acara menarik di kampus';
                            }
                        ?>
                    </p>
                    
                    <?php if ($kategori_filter > 0 || !empty($date_filter)): ?>
                    <div class="mt-3">
                        <a href="event.php" class="btn btn-sm btn-light">
                            <i class="fas fa-times me-1"></i> Hapus Filter
                        </a>
                        <span class="text-white ms-2">
                            Menampilkan <?php echo $total_events; ?> event
                            <?php if ($kategori_filter > 0): ?>
                                dalam kategori <strong><?php echo $kategori_list[$kategori_filter]['nama']; ?></strong>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="stats-box">
                        <span class="stat-number"><?php echo $total_events; ?></span>
                        <span class="stat-label">
                            <?php 
                                if ($kategori_filter > 0) {
                                    echo 'Event ' . $kategori_list[$kategori_filter]['nama'];
                                } else {
                                    echo 'Event Tersedia';
                                }
                            ?>
                        </span>
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
                <!-- FILTER TANGGAL -->
                <div class="date-filter mb-4">
                    <a href="event.php" class="date-btn <?php echo empty($date_filter) ? 'active' : ''; ?>">
                        Semua Event
                    </a>
                    <a href="event.php?date=hari-ini<?php echo $kategori_filter > 0 ? "&kategori=$kategori_filter" : ''; ?>" 
                       class="date-btn <?php echo $date_filter == 'hari-ini' ? 'active' : ''; ?>">
                        Hari Ini
                    </a>
                    <a href="event.php?date=minggu-ini<?php echo $kategori_filter > 0 ? "&kategori=$kategori_filter" : ''; ?>" 
                       class="date-btn <?php echo $date_filter == 'minggu-ini' ? 'active' : ''; ?>">
                        Minggu Ini
                    </a>
                    <a href="event.php?date=bulan-ini<?php echo $kategori_filter > 0 ? "&kategori=$kategori_filter" : ''; ?>" 
                       class="date-btn <?php echo $date_filter == 'bulan-ini' ? 'active' : ''; ?>">
                        Bulan Ini
                    </a>
                    <a href="event.php?date=akan-datang<?php echo $kategori_filter > 0 ? "&kategori=$kategori_filter" : ''; ?>" 
                       class="date-btn <?php echo $date_filter == 'akan-datang' ? 'active' : ''; ?>">
                        Akan Datang
                    </a>
                </div>

                <!-- FILTER KATEGORI -->
                <div class="card sidebar-card mb-4">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-filter me-2"></i>Filter Kategori
                        </h5>
                        <div class="d-flex flex-wrap">
                            <a href="event.php<?php echo !empty($date_filter) ? "?date=$date_filter" : ''; ?>" 
                               class="filter-btn <?php echo $kategori_filter == 0 ? 'active' : ''; ?>">
                                Semua Kategori
                            </a>
                            <?php foreach ($kategori_list as $id => $kategori): ?>
                            <a href="event.php?kategori=<?php echo $id; ?><?php echo !empty($date_filter) ? "&date=$date_filter" : ''; ?>" 
                               class="filter-btn <?php echo $kategori_filter == $id ? 'active' : ''; ?>"
                               style="border-left: 3px solid <?php echo $kategori['warna']; ?>">
                                <i class="<?php echo $kategori['ikon']; ?> me-1"></i>
                                <?php echo htmlspecialchars($kategori['nama']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- DAFTAR EVENT -->
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="row g-4">
                        <?php while ($event = mysqli_fetch_assoc($result)): ?>
                        <div class="col-md-6">
                            <div class="event-card">
                                <!-- Featured Badge -->
                                <?php if ($event['featured']): ?>
                                <div class="featured-badge">
                                    <i class="fas fa-star me-1"></i> Unggulan
                                </div>
                                <?php endif; ?>

                                <!-- Gambar -->
                                <img src="<?php 
                                    echo !empty($event['poster']) ? htmlspecialchars($event['poster']) : 
                                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                ?>" alt="<?php echo htmlspecialchars($event['judul']); ?>">
                                
                                <div class="card-body">
                                    <!-- Badge Kategori -->
                                    <span class="badge-event" style="background: <?php echo $event['warna'] ?? '#0056b3'; ?>">
                                        <i class="<?php echo $event['ikon'] ?? 'fas fa-calendar'; ?> me-1"></i>
                                        <?php echo htmlspecialchars($event['kategori_nama'] ?? 'Event'); ?>
                                    </span>
                                    
                                    <!-- Tanggal -->
                                    <div class="event-date">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('d M Y', strtotime($event['tanggal'])); ?>
                                        <?php if (!empty($event['waktu'])): ?>
                                            <br><i class="far fa-clock me-1"></i><?php echo date('H:i', strtotime($event['waktu'])); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Judul -->
                                    <h3 class="event-title-card">
                                        <?php echo htmlspecialchars($event['judul']); ?>
                                    </h3>
                                    
                                    <!-- Excerpt -->
                                    <p class="event-excerpt">
                                        <?php 
                                            if (!empty($event['deskripsi_singkat'])) {
                                                echo htmlspecialchars($event['deskripsi_singkat']);
                                            } else {
                                                echo substr(strip_tags($event['deskripsi']), 0, 120) . '...';
                                            }
                                        ?>
                                    </p>
                                    
                                    <!-- Lokasi -->
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($event['lokasi']); ?>
                                    </p>
                                    
                                    <!-- Meta -->
                                   <div class="d-flex justify-content-between align-items-center">
    <span class="text-muted">
        <i class="fas fa-eye"></i> <?php echo $event['views'] ?? 0; ?> dilihat
    </span>
    <div>
        <a href="detail_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
            <i class="fas fa-info-circle"></i> Detail
        </a>
         <!-- TOMBOL DAFTAR -->
               <?php if ($event['status'] == 'publik' && strtotime($event['tanggal']) >= strtotime(date('Y-m-d'))): ?>
    <a href="daftar.php?id=<?php echo $event['id']; ?>" class="btn btn-success btn-lg">
        <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
    </a>
<?php endif; ?>
    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <!-- JIKA TIDAK ADA EVENT -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-calendar-times fa-4x text-muted"></i>
                        </div>
                        <h4 class="text-muted mb-3">Tidak ada event yang ditemukan</h4>
                        <p class="text-muted">
                            <?php 
                                if ($kategori_filter > 0) {
                                    echo 'Tidak ada event dalam kategori ' . $kategori_list[$kategori_filter]['nama'];
                                } elseif (!empty($date_filter)) {
                                    echo 'Tidak ada event pada periode yang dipilih';
                                } else {
                                    echo 'Belum ada event yang dipublikasikan.';
                                }
                            ?>
                        </p>
                        <a href="event.php" class="btn btn-primary">
                            <i class="fas fa-times me-2"></i> Hapus Filter
                        </a>
                    </div>
                <?php endif; ?>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination pagination-custom justify-content-center">
                        <!-- Previous -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="event.php?<?php 
                                    echo $kategori_filter > 0 ? "kategori=$kategori_filter&" : ''; 
                                    echo !empty($date_filter) ? "date=$date_filter&" : ''; 
                               ?>page=<?php echo $page - 1; ?>" 
                               aria-label="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" 
                                       href="event.php?<?php 
                                            echo $kategori_filter > 0 ? "kategori=$kategori_filter&" : ''; 
                                            echo !empty($date_filter) ? "date=$date_filter&" : ''; 
                                       ?>page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- Next -->
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="event.php?<?php 
                                    echo $kategori_filter > 0 ? "kategori=$kategori_filter&" : ''; 
                                    echo !empty($date_filter) ? "date=$date_filter&" : ''; 
                               ?>page=<?php echo $page + 1; ?>" 
                               aria-label="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <!-- EVENT AKAN DATANG -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-calendar-plus me-2"></i>Event Akan Datang
                        </h5>
                        <?php if (mysqli_num_rows($upcoming_result) > 0): ?>
                            <?php mysqli_data_seek($upcoming_result, 0); ?>
                            <?php while ($upcoming = mysqli_fetch_assoc($upcoming_result)): ?>
                            <div class="upcoming-item">
                                <a href="detail_event.php?id=<?php echo $upcoming['id']; ?>" 
                                   class="text-decoration-none">
                                    <div class="upcoming-date">
                                        <?php echo date('d M', strtotime($upcoming['tanggal'])); ?>
                                    </div>
                                    <h6 class="upcoming-title">
                                        <?php echo htmlspecialchars($upcoming['judul']); ?>
                                    </h6>
                                    <div class="text-muted small">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($upcoming['lokasi']); ?>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Tidak ada event akan datang
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KATEGORI EVENT -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-tags me-2"></i>Kategori Event
                        </h5>
                        <div class="list-group list-group-flush">
                            <!-- SEMUA KATEGORI -->
                            <a href="event.php<?php echo !empty($date_filter) ? "?date=$date_filter" : ''; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                                      <?php echo $kategori_filter == 0 ? 'active' : ''; ?>">
                                Semua Kategori
                                <span class="badge bg-primary rounded-pill">
                                    <?php echo array_sum($kategori_counts); ?>
                                </span>
                            </a>
                            
                            <!-- PER KATEGORI -->
                            <?php foreach ($kategori_list as $id => $kategori): ?>
                            <a href="event.php?kategori=<?php echo $id; ?><?php echo !empty($date_filter) ? "&date=$date_filter" : ''; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                                      <?php echo $kategori_filter == $id ? 'active' : ''; ?>"
                               style="border-left: 3px solid <?php echo $kategori['warna']; ?>">
                                <div>
                                    <i class="<?php echo $kategori['ikon']; ?> me-2"></i>
                                    <?php echo htmlspecialchars($kategori['nama']); ?>
                                </div>
                                <span class="badge bg-<?php echo $kategori_filter == $id ? 'light text-dark' : 'primary'; ?> rounded-pill">
                                    <?php echo $kategori_counts[$id]; ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- EVENT TERPOPULER -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h5 class="sidebar-title">
                            <i class="fas fa-fire me-2"></i>Event Terpopuler
                        </h5>
                        <?php if (mysqli_num_rows($populer_result) > 0): ?>
                            <?php mysqli_data_seek($populer_result, 0); ?>
                            <?php while ($populer = mysqli_fetch_assoc($populer_result)): ?>
                            <div class="upcoming-item">
                                <a href="detail_event.php?id=<?php echo $populer['id']; ?>" 
                                   class="text-decoration-none">
                                    <h6 class="upcoming-title">
                                        <?php echo htmlspecialchars($populer['judul']); ?>
                                    </h6>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>
                                            <i class="far fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y', strtotime($populer['tanggal'])); ?>
                                        </span>
                                        <span>
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
                                Belum ada event populer
                            </p>
                        <?php endif; ?>
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
                Halaman Event | 
                <?php echo $total_events; ?> event tersedia | 
                Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
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

        // Update judul halaman
        const totalEvents = <?php echo $total_events; ?>;
        document.title = `Event Kampus (${totalEvents} Event) - Polibatam`;
    </script>
</body>
</html>

<?php
// Tutup koneksi
mysqli_close($conn);
?>
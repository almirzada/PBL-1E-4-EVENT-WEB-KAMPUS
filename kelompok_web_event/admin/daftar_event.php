<?php
session_start();
require_once '../koneksi.php';

// PROTEKSI ADMIN
if (!isset($_SESSION['admin_event_id'])) {
    header("Location: login.php");
    exit();
}

// ================================================
// KONFIGURASI
// ================================================
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ================================================
// FILTER & PENCARIAN
// ================================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$tipe = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, $_GET['tipe']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where = "1=1";
if (!empty($search)) {
    $where .= " AND (e.judul LIKE '%$search%' OR e.lokasi LIKE '%$search%')";
}
if ($kategori > 0) {
    $where .= " AND e.kategori_id = $kategori";
}
if (!empty($status)) {
    $where .= " AND e.status = '$status'";
}
if (!empty($tipe)) {
    $where .= " AND e.tipe_pendaftaran = '$tipe'";
}
if (!empty($date_from)) {
    $where .= " AND e.tanggal >= '$date_from'";
}
if (!empty($date_to)) {
    $where .= " AND e.tanggal <= '$date_to'";
}

// ================================================
// HITUNG TOTAL & AMBIL DATA
// ================================================
$total_query = "SELECT COUNT(*) as total FROM events e WHERE $where";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_events = $total_row['total'];
$total_pages = ceil($total_events / $limit);

$query = "SELECT e.*, k.nama as kategori_nama, k.warna 
          FROM events e 
          LEFT JOIN kategori k ON e.kategori_id = k.id 
          WHERE $where 
          ORDER BY e.created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// ================================================
// AMBIL KATEGORI UNTUK FILTER
// ================================================
$kategori_list = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama");

// ================================================
// HITUNG STATISTIK
// ================================================
$stats = [
    'total' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events"))['total'],
    'publik' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events WHERE status = 'publik'"))['total'],
    'draft' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events WHERE status = 'draft'"))['total'],
    'selesai' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events WHERE status = 'selesai'"))['total'],
    'individu' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events WHERE tipe_pendaftaran = 'individu'"))['total'],
    'tim' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events WHERE tipe_pendaftaran = 'tim'"))['total'],
    'individu_tim' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM events WHERE tipe_pendaftaran = 'individu_tim'"))['total']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Event - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-wrapper {
            min-height: 100vh;
        }
        
        /* SIDEBAR */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        /* CARD HEADER */
        .card-header-custom {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* TABLE */
        .table img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-publik { background: #28a745; color: white; }
        .badge-draft { background: #ffc107; color: #000; }
        .badge-selesai { background: #17a2b8; color: white; }
        
        .badge-tipe {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-individu { background: #6f42c1; color: white; }
        .badge-tim { background: #e83e8c; color: white; }
        .badge-individu_tim { background: #fd7e14; color: white; }
        
        /* ACTION BUTTONS */
        .btn-action {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
        }
        
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
            border-top: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card.total { border-top-color: var(--primary); }
        .stat-card.publik { border-top-color: #28a745; }
        .stat-card.draft { border-top-color: #ffc107; }
        .stat-card.selesai { border-top-color: #17a2b8; }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            display: block;
            line-height: 1;
        }
        
        .stat-card.publik .stat-number { color: #28a745; }
        .stat-card.draft .stat-number { color: #ffc107; }
        .stat-card.selesai .stat-number { color: #17a2b8; }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        /* FILTER CARD */
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        /* DATE PICKER */
        .date-range {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
        }
        
        /* PAGINATION */
        .pagination-custom .page-link {
            color: var(--primary);
            border: 1px solid #dee2e6;
            margin: 0 3px;
            border-radius: 8px;
        }
        
        .pagination-custom .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            .sidebar .menu-text {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* EXPORT BUTTONS */
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        
        /* QUICK FILTERS */
        .quick-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .quick-filter-btn {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 1px solid #dee2e6;
            background: white;
            transition: all 0.3s;
        }
        
        .quick-filter-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .quick-filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* EVENT CARD VIEW */
        .event-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            background: white;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .event-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .event-card-body {
            padding: 15px;
        }
        
        .event-card-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-card-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* VIEW TOGGLE */
        .view-toggle {
            display: flex;
            gap: 5px;
            margin-left: 15px;
        }
        
        .view-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            transition: all 0.3s;
        }
        
        .view-btn:hover {
            background: #f8f9fa;
            color: var(--primary);
        }
        
        .view-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
       <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header text-center">
                <h4><i class="fas fa-calendar-alt"></i> <span class="menu-text">PortalKampus</span></h4>
                <small class="menu-text">Admin Panel</small>
            </div>
            
            <div class="sidebar-menu">
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                    </a>
                    
                    <!-- EVENT MENU -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Event</small>
                        <a href="form.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Event</span>
                        </a>
                        <a href="daftar_event.php" class="nav-link">
                            <i class="fas fa-list"></i> <span class="menu-text">Semua Event</span>
                        </a>
                    </div>
                    
                    <!-- BERITA MENU -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Berita</small>
                        <a href="daftar_berita.php" class="nav-link">
                            <i class="fas fa-newspaper"></i> <span class="menu-text">Daftar Berita</span>
                        </a>
                        <a href="tambah_berita.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Berita</span>
                        </a>
                    </div>
                    
                    <!-- LAINNYA -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Lainnya</small>
                        <a href="pengaturan.php" class="nav-link">
                            <i class="fas fa-tags"></i> <span class="menu-text">Kategori</span>
                        </a>
                        <a href="admin_peserta.php" class="nav-link">
                            <i class="fas fa-users"></i> <span class="menu-text">Peserta</span>
                        </a>
                        <a href="pengaturan.php" class="nav-link">
                            <i class="fas fa-cog"></i> <span class="menu-text">Pengaturan</span>
                        </a>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <a href="../index.php" class="nav-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i> <span class="menu-text">Lihat Website</span>
                        </a>
                        <a href="logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Keluar</span>
                        </a>
                    </div>
                </nav>
            </div>
        </div>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1"><i class="fas fa-calendar-alt text-primary me-2"></i>Daftar Event</h3>
                    <p class="text-muted mb-0">Total <?php echo $total_events; ?> event</p>
                </div>
                <div class="d-flex align-items-center">
                    <div class="view-toggle">
                        <button class="view-btn active" id="tableView" title="Tabel View">
                            <i class="fas fa-table"></i>
                        </button>
                        <button class="view-btn" id="cardView" title="Card View">
                            <i class="fas fa-th-large"></i>
                        </button>
                    </div>
                    <a href="form.php" class="btn btn-primary ms-3">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Event
                    </a>
                </div>
            </div>
            
            <!-- QUICK FILTERS -->
            <div class="quick-filters">
                <a href="?status=publik" class="quick-filter-btn <?php echo $status == 'publik' ? 'active' : ''; ?>">
                    <i class="fas fa-eye me-1"></i>Publik (<?php echo $stats['publik']; ?>)
                </a>
                <a href="?status=draft" class="quick-filter-btn <?php echo $status == 'draft' ? 'active' : ''; ?>">
                    <i class="fas fa-edit me-1"></i>Draft (<?php echo $stats['draft']; ?>)
                </a>
                <a href="?status=selesai" class="quick-filter-btn <?php echo $status == 'selesai' ? 'active' : ''; ?>">
                    <i class="fas fa-flag-checkered me-1"></i>Selesai (<?php echo $stats['selesai']; ?>)
                </a>
                <a href="?tipe=individu" class="quick-filter-btn <?php echo $tipe == 'individu' ? 'active' : ''; ?>">
                    <i class="fas fa-user me-1"></i>Individu (<?php echo $stats['individu']; ?>)
                </a>
                <a href="?tipe=tim" class="quick-filter-btn <?php echo $tipe == 'tim' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-1"></i>Tim (<?php echo $stats['tim']; ?>)
                </a>
                <a href="daftar_event.php" class="quick-filter-btn <?php echo empty($_GET) ? 'active' : ''; ?>">
                    <i class="fas fa-filter me-1"></i>Semua
                </a>
            </div>
            
            <!-- STATS GRID -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Event</div>
                </div>
                <div class="stat-card publik">
                    <div class="stat-number"><?php echo $stats['publik']; ?></div>
                    <div class="stat-label">Publik</div>
                </div>
                <div class="stat-card draft">
                    <div class="stat-number"><?php echo $stats['draft']; ?></div>
                    <div class="stat-label">Draft</div>
                </div>
                <div class="stat-card selesai">
                    <div class="stat-number"><?php echo $stats['selesai']; ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
            
            <!-- FILTER CARD -->
            <div class="filter-card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari judul atau lokasi..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="kategori" class="form-select">
                                <option value="">Semua Kategori</option>
                                <?php mysqli_data_seek($kategori_list, 0); ?>
                                <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                                <option value="<?php echo $kat['id']; ?>" <?php echo $kategori == $kat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kat['nama']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="publik" <?php echo $status == 'publik' ? 'selected' : ''; ?>>Publik</option>
                                <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="selesai" <?php echo $status == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="tipe" class="form-select">
                                <option value="">Semua Tipe</option>
                                <option value="individu" <?php echo $tipe == 'individu' ? 'selected' : ''; ?>>Individu</option>
                                <option value="tim" <?php echo $tipe == 'tim' ? 'selected' : ''; ?>>Tim</option>
                                <option value="individu_tim" <?php echo $tipe == 'individu_tim' ? 'selected' : ''; ?>>Individu/Tim</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="date-range">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" name="date_from" class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($date_from); ?>" placeholder="Dari">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="date_to" class="form-control form-control-sm" 
                                               value="<?php echo htmlspecialchars($date_to); ?>" placeholder="Sampai">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- TABLE VIEW (DEFAULT) -->
            <div class="card" id="tableViewContent">
                <div class="card-header-custom">
                    <h5 class="mb-0">Daftar Event</h5>
                    <div class="d-flex align-items-center">
                        <div class="text-muted me-3">
                            Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                        </div>
                        <div class="export-buttons">
                            <button class="btn btn-sm btn-outline-success" id="exportCSV">
                                <i class="fas fa-file-csv me-1"></i> CSV
                            </button>
                            <button class="btn btn-sm btn-outline-danger" id="exportPDF">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="eventsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Poster</th>
                                    <th>Judul Event</th>
                                    <th width="120">Tanggal</th>
                                    <th width="100">Kategori</th>
                                    <th width="100">Tipe</th>
                                    <th width="100">Status</th>
                                    <th width="100">Views</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php mysqli_data_seek($result, 0); ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['poster'])): ?>
                                        <img src="../<?php echo htmlspecialchars($row['poster']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['judul']); ?>"
                                             class="img-thumbnail">
                                        <?php else: ?>
                                        <div class="bg-light text-center py-3 rounded">
                                            <i class="fas fa-calendar-alt text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['judul']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($row['lokasi']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('H:i', strtotime($row['waktu'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($row['tanggal'])); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php 
                                            $hari = date('D', strtotime($row['tanggal']));
                                            $hari_indonesia = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 
                                                              'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
                                            echo $hari_indonesia[$hari] ?? '';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $row['warna'] ?? '#4361ee'; ?>; color: white;">
                                            <?php echo $row['kategori_nama'] ?? 'Umum'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-tipe badge-<?php echo $row['tipe_pendaftaran']; ?>">
                                            <?php 
                                            $tipe_labels = [
                                                'individu' => 'Individu',
                                                'tim' => 'Tim',
                                                'individu_tim' => 'Individu/Tim'
                                            ];
                                            echo $tipe_labels[$row['tipe_pendaftaran']] ?? 'Individu';
                                            ?>
                                        </span>
                                        <?php if ($row['tipe_pendaftaran'] == 'tim' || $row['tipe_pendaftaran'] == 'individu_tim'): ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $row['min_anggota']; ?>-<?php echo $row['max_anggota']; ?> org
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                        <?php if ($row['featured']): ?>
                                        <br>
                                        <span class="badge bg-warning mt-1">
                                            <i class="fas fa-star"></i> Unggulan
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-eye"></i> <?php echo $row['views']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../detail_event.php?id=<?php echo $row['id']; ?>" 
                                               target="_blank" class="btn btn-info btn-action" 
                                               title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="form.php?edit=<?php echo $row['id']; ?>" 
                                               class="btn btn-warning btn-action" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="hapus_event.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-danger btn-action" 
                                               title="Hapus"
                                               onclick="return confirm('Hapus event ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum ada event</h4>
                        <p class="text-muted mb-4">Mulai dengan menambahkan event pertama Anda</p>
                        <a href="form.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i> Tambah Event Pertama
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-custom justify-content-center mb-0">
                            <!-- Previous -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>&tipe=<?php echo $tipe; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>&tipe=<?php echo $tipe; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
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
                                   href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>&tipe=<?php echo $tipe; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- CARD VIEW (HIDDEN BY DEFAULT) -->
            <div class="d-none" id="cardViewContent">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php mysqli_data_seek($result, 0); ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="col">
                            <div class="event-card">
                                <?php if (!empty($row['poster'])): ?>
                                <img src="../<?php echo htmlspecialchars($row['poster']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['judul']); ?>">
                                <?php else: ?>
                                <div class="bg-light text-center py-4">
                                    <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="event-card-body">
                                    <h5 class="event-card-title"><?php echo htmlspecialchars($row['judul']); ?></h5>
                                    
                                    <div class="event-card-meta mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($row['tanggal'])); ?>
                                            </span>
                                            <span class="badge-status badge-<?php echo $row['status']; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </div>
                                        <div class="mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($row['lokasi']); ?>
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-tag me-1"></i>
                                            <span class="badge" style="background: <?php echo $row['warna'] ?? '#4361ee'; ?>; color: white;">
                                                <?php echo $row['kategori_nama'] ?? 'Umum'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge-tipe badge-<?php echo $row['tipe_pendaftaran']; ?> me-1">
                                                <?php 
                                                $tipe_labels = [
                                                    'individu' => 'Individu',
                                                    'tim' => 'Tim',
                                                    'individu_tim' => 'Individu/Tim'
                                                ];
                                                echo $tipe_labels[$row['tipe_pendaftaran']] ?? 'Individu';
                                                ?>
                                            </span>
                                            <?php if ($row['featured']): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-star"></i>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="btn-group btn-group-sm">
                                            <a href="../detail_event.php?id=<?php echo $row['id']; ?>" 
                                               target="_blank" class="btn btn-sm btn-info" 
                                               title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="form.php?edit=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="hapus_event.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               title="Hapus"
                                               onclick="return confirm('Hapus event ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Belum ada event</h4>
                            <p class="text-muted mb-4">Mulai dengan menambahkan event pertama Anda</p>
                            <a href="form.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i> Tambah Event Pertama
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- PAGINATION UNTUK CARD VIEW -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-4">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-custom justify-content-center mb-0">
                            <!-- Previous -->
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>&tipe=<?php echo $tipe; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>&tipe=<?php echo $tipe; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
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
                                   href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>&tipe=<?php echo $tipe; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Sistem Event Kampus - Admin Panel
                    | Halaman: <?php echo $page; ?>/<?php echo $total_pages; ?>
                    | <?php echo mysqli_num_rows($result); ?> event ditampilkan
                    | Total: <?php echo $stats['total']; ?> event
                </small>
            </footer>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Inisialisasi DataTable
        $(document).ready(function() {
            $('#eventsTable').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ event",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ event",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikut",
                        previous: "Sebelum"
                    }
                }
            });
            
            // Confirmation untuk hapus
            $('a[href*="hapus_event"]').click(function(e) {
                if (!confirm('Hapus event ini? Tindakan ini tidak dapat dibatalkan.')) {
                    e.preventDefault();
                }
            });
            
            // View toggle
            const tableViewBtn = document.getElementById('tableView');
            const cardViewBtn = document.getElementById('cardView');
            const tableViewContent = document.getElementById('tableViewContent');
            const cardViewContent = document.getElementById('cardViewContent');
            
            tableViewBtn.addEventListener('click', function() {
                tableViewBtn.classList.add('active');
                cardViewBtn.classList.remove('active');
                tableViewContent.classList.remove('d-none');
                cardViewContent.classList.add('d-none');
            });
            
            cardViewBtn.addEventListener('click', function() {
                cardViewBtn.classList.add('active');
                tableViewBtn.classList.remove('active');
                cardViewContent.classList.remove('d-none');
                tableViewContent.classList.add('d-none');
            });
            
            // Auto submit form filter saat dropdown berubah
            document.querySelectorAll('select[name="kategori"], select[name="status"], select[name="tipe"]').forEach(select => {
                select.addEventListener('change', function() {
                    this.form.submit();
                });
            });
            
            // Export buttons (placeholder functionality)
            document.getElementById('exportCSV').addEventListener('click', function() {
                alert('Fitur export CSV akan segera tersedia!');
            });
            
            document.getElementById('exportPDF').addEventListener('click', function() {
                alert('Fitur export PDF akan segera tersedia!');
            });
            
            // Quick filter button styling
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    // Remove active class from all
                    document.querySelectorAll('.quick-filter-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    // Add active to clicked
                    this.classList.add('active');
                });
            });
            
            // Date range validation
            const dateFrom = document.querySelector('input[name="date_from"]');
            const dateTo = document.querySelector('input[name="date_to"]');
            
            if (dateFrom && dateTo) {
                dateFrom.addEventListener('change', function() {
                    if (this.value && dateTo.value && this.value > dateTo.value) {
                        dateTo.value = this.value;
                    }
                });
                
                dateTo.addEventListener('change', function() {
                    if (this.value && dateFrom.value && this.value < dateFrom.value) {
                        dateFrom.value = this.value;
                    }
                });
            }
            
            // Auto focus search field jika ada parameter search
            const searchField = document.querySelector('input[name="search"]');
            if (searchField && searchField.value) {
                searchField.focus();
                searchField.select();
            }
        });
        
        // Animasi stats
        function animateStats() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                const duration = 1000;
                const step = finalValue / (duration / 16);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    if (current >= finalValue) {
                        stat.textContent = finalValue.toLocaleString();
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current).toLocaleString();
                    }
                }, 16);
            });
        }
        
        // Jalankan animasi saat halaman dimuat
        window.addEventListener('load', animateStats);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
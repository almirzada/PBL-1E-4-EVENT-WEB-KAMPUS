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
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = "1=1";
if (!empty($search)) {
    $where .= " AND (judul LIKE '%$search%' OR konten LIKE '%$search%')";
}
if (!empty($kategori)) {
    $where .= " AND kategori_berita = '$kategori'";
}
if (!empty($status)) {
    $where .= " AND status = '$status'";
}

// ================================================
// HITUNG TOTAL & AMBIL DATA
// ================================================
$total_query = "SELECT COUNT(*) as total FROM berita WHERE $where";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_berita = $total_row['total'];
$total_pages = ceil($total_berita / $limit);

$query = "SELECT * FROM berita 
          WHERE $where 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// ================================================
// AMBIL KATEGORI UNTUK FILTER
// ================================================
$kategori_query = mysqli_query($conn, "SELECT DISTINCT kategori_berita FROM berita");
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
    <title>Kelola Berita - Admin Panel</title>
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
            padding: 18px 25px;
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
        
        .badge-kategori {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
        }
        
        .badge-informasi { background: #17a2b8; color: white; }
        .badge-pengumuman { background: #28a745; color: white; }
        .badge-beasiswa { background: #ffc107; color: #000; }
        .badge-akademik { background: #6f42c1; color: white; }
        .badge-kemahasiswaan { background: #e83e8c; color: white; }
        
        /* ACTION BUTTONS */
        .btn-action {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 2px;
        }
        
        /* SEARCH BOX */
        .search-box {
            max-width: 400px;
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
        }
        
        @media (max-width: 768px) {
            .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-box {
                max-width: 100%;
            }
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
                    <a href="dashboard.php" class="nav-link">
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
                        <a href="daftar_berita.php" class="nav-link active">
                            <i class="fas fa-newspaper"></i> <span class="menu-text">Daftar Berita</span>
                        </a>
                        <a href="tambah_berita.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Berita</span>
                        </a>
                    </div>
                    
                    <!-- LAINNYA -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Pendaftar</small>
                        
                        <a href="admin_peserta.php" class="nav-link">
                            <i class="fas fa-users"></i> <span class="menu-text">Peserta</span>
                        </a>
                       <?php if ($_SESSION['admin_event_level'] == 'superadmin'): ?>
<!-- MENU SUPERADMIN -->
<div class="menu-section mt-2">
    <small class="px-3 d-block text-uppercase opacity-75">Superadmin</small>
    <a href="admin_management.php" class="nav-link">
        <i class="fas fa-users-cog"></i> <span class="menu-text">Kelola Admin</span>
    </a>
    <a href="admin_approval.php" class="nav-link">
        <i class="fas fa-user-check"></i> <span class="menu-text">Persetujuan</span>
    </a>
</div>
<?php endif; ?>
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
                    <h3 class="mb-1"><i class="fas fa-newspaper text-primary me-2"></i>Kelola Berita</h3>
                    <p class="text-muted mb-0">Total <?php echo $total_berita; ?> berita</p>
                </div>
                <a href="tambah_berita.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Berita Baru
                </a>
            </div>
            
            <!-- FILTER CARD -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Cari judul atau isi..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="kategori" class="form-select">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($kategori_berita as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $kategori == $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="publik" <?php echo $status == 'publik' ? 'selected' : ''; ?>>Publik</option>
                                <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- TABLE -->
            <div class="card">
                <div class="card-header-custom">
                    <h5 class="mb-0">Daftar Berita</h5>
                    <div class="text-muted">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="80">Gambar</th>
                                    <th>Judul Berita</th>
                                    <th width="120">Kategori</th>
                                    <th width="120">Status</th>
                                    <th width="150">Tanggal</th>
                                    <th width="150">Views</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['gambar'])): ?>
                                        <img src="../<?php echo htmlspecialchars($row['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['judul']); ?>"
                                             class="img-thumbnail">
                                        <?php else: ?>
                                        <div class="bg-light text-center py-3 rounded">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['judul']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php 
                                            $excerpt = !empty($row['excerpt']) ? $row['excerpt'] : 
                                                      substr(strip_tags($row['konten']), 0, 80) . '...';
                                            echo htmlspecialchars($excerpt);
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge-kategori badge-<?php echo $row['kategori_berita']; ?>">
                                            <?php echo $kategori_berita[$row['kategori_berita']] ?? 'Informasi'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('H:i', strtotime($row['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-eye me-1"></i><?php echo $row['views']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../detail_berita.php?id=<?php echo $row['id']; ?>" 
                                               target="_blank" class="btn btn-info btn-action" 
                                               title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_berita.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-warning btn-action" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="hapus_berita.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-danger btn-action" 
                                               title="Hapus"
                                               onclick="return confirm('Hapus berita ini?')">
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
                        <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Belum ada berita</h4>
                        <p class="text-muted mb-4">Mulai dengan menambahkan berita pertama Anda</p>
                        <a href="tambah_berita.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i> Tambah Berita Pertama
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
                                   href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>">
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
                                   href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&kategori=<?php echo $kategori; ?>&status=<?php echo $status; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- STATS -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h2 class="text-primary"><?php echo $total_berita; ?></h2>
                            <p class="text-muted mb-0">Total Berita</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <?php 
                            $publik_count = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT COUNT(*) as count FROM berita WHERE status = 'publik'"))['count']; 
                            ?>
                            <h2 class="text-success"><?php echo $publik_count; ?></h2>
                            <p class="text-muted mb-0">Berita Publik</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <?php 
                            $draft_count = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT COUNT(*) as count FROM berita WHERE status = 'draft'"))['count']; 
                            ?>
                            <h2 class="text-warning"><?php echo $draft_count; ?></h2>
                            <p class="text-muted mb-0">Berita Draft</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <?php 
                            $views_total = mysqli_fetch_assoc(mysqli_query($conn, 
                                "SELECT SUM(views) as total FROM berita"))['total'] ?? 0; 
                            ?>
                            <h2 class="text-info"><?php echo $views_total; ?></h2>
                            <p class="text-muted mb-0">Total Views</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Sistem Berita Kampus - Admin Panel
                    | Halaman: <?php echo $page; ?>/<?php echo $total_pages; ?>
                    | <?php echo mysqli_num_rows($result); ?> berita ditampilkan
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
            $('table').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ berita",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ berita",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikut",
                        previous: "Sebelum"
                    }
                }
            });
            
            // Confirmation untuk hapus
            $('a[href*="hapus_berita"]').click(function(e) {
                if (!confirm('Hapus berita ini? Tindakan ini tidak dapat dibatalkan.')) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto submit form filter saat dropdown berubah
        document.querySelectorAll('select[name="kategori"], select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
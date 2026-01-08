<?php
session_start();
// PROTEKSI LOGIN
if (!isset($_SESSION['admin_event_id'])) {
    header("Location: login.php");
    exit();
}

// KONEKSI DATABASE
require_once '../koneksi.php';

// ================================================
// AMBIL DATA ADMIN YANG SEDANG LOGIN
// ================================================
$admin_id = $_SESSION['admin_event_id'];
$admin_data = mysqli_query($conn, "SELECT * FROM admin_event WHERE id = $admin_id");
$admin = mysqli_fetch_assoc($admin_data);

$username = $admin['username'] ?? 'admin';
$nama_lengkap = $admin['nama'] ?? 'Administrator';
$level = $admin['level'] ?? 'admin';

// ================================================
// HITUNG STATISTIK REAL DARI DATABASE
// ================================================
// Total Event
$total_event = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM events"))['total'] ?? 0;

// Event Hari Ini
$today = date('Y-m-d');
$event_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM events WHERE tanggal = '$today'"))['total'] ?? 0;

// Event Akan Datang (7 hari ke depan)
$next_week = date('Y-m-d', strtotime('+7 days'));
$event_akan_datang = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM events WHERE tanggal BETWEEN '$today' AND '$next_week' AND status = 'publik'"))['total'] ?? 0;

// Total Kategori
$total_kategori = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM kategori"))['total'] ?? 0;

// ================================================
// AMBIL EVENT TERBARU (5 EVENT)
// ================================================
$event_terbaru = mysqli_query($conn, 
    "SELECT e.*, k.nama as kategori_nama, k.warna
     FROM events e 
     LEFT JOIN kategori k ON e.kategori_id = k.id 
     ORDER BY e.created_at DESC 
     LIMIT 5");

// ================================================
// AMBIL EVENT POPULER (BERDASARKAN VIEWS)
// ================================================
$event_populer = mysqli_query($conn, 
    "SELECT e.*, k.nama as kategori_nama, k.warna
     FROM events e 
     LEFT JOIN kategori k ON e.kategori_id = k.id 
     WHERE e.status = 'publik'
     ORDER BY e.views DESC 
     LIMIT 5");

// ================================================
// AMBIL EVENT BERDASARKAN STATUS UNTUK TAB
// ================================================
// Draft
$draft_events = mysqli_query($conn, 
    "SELECT e.*, k.nama as kategori_nama 
     FROM events e 
     LEFT JOIN kategori k ON e.kategori_id = k.id 
     WHERE e.status = 'draft' 
     ORDER BY e.tanggal DESC");

// Publik
$publik_events = mysqli_query($conn, 
    "SELECT e.*, k.nama as kategori_nama 
     FROM events e 
     LEFT JOIN kategori k ON e.kategori_id = k.id 
     WHERE e.status = 'publik' 
     ORDER BY e.tanggal DESC");

// Selesai
$selesai_events = mysqli_query($conn, 
    "SELECT e.*, k.nama as kategori_nama 
     FROM events e 
     LEFT JOIN kategori k ON e.kategori_id = k.id 
     WHERE e.status = 'selesai' 
     ORDER BY e.tanggal DESC");

// Hitung jumlah per status
$total_draft = mysqli_num_rows($draft_events);
$total_publik = mysqli_num_rows($publik_events);
$total_selesai = mysqli_num_rows($selesai_events);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Event Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --info: #7209b7;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-wrapper {
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
        
        /* MAIN CONTENT */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        /* HEADER */
        .top-header {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.2rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* CHARTS & TABLES */
        .content-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 18px 25px;
            font-weight: 600;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-draft { background: #ffc107; color: #000; }
        .badge-publik { background: #28a745; color: white; }
        .badge-selesai { background: #17a2b8; color: white; }
        
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
            .content-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header text-center">
                <h4><i class="fas fa-calendar-alt"></i> <span class="menu-text">EventKampus</span></h4>
                <small class="menu-text">Admin Panel</small>
            </div>
            
            <div class="sidebar-menu">
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                    </a>
                    <a href="form.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Event</span>
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-list"></i> <span class="menu-text">Semua Event</span>
                    </a>
                    <a href="pengaturan.php" class="nav-link">
                        <i class="fas fa-tags"></i> <span class="menu-text">Kategori</span>
                    </a>
                    <a href="mangiemen_user.php" class="nav-link">
                        <i class="fas fa-users"></i> <span class="menu-text">Pengguna</span>
                    </a>
                    <a href="pengaturan.php" class="nav-link">
                        <i class="fas fa-cog"></i> <span class="menu-text">Pengaturan</span>
                    </a>
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
            <!-- TOP HEADER -->
            <div class="top-header">
                <div>
                    <h4 class="mb-0">Selamat Datang, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong></h4>
                    <small class="text-muted"><?php echo date('l, d F Y'); ?></small>
                </div>
                
                <div class="user-info">
                    <div class="text-end">
                        <div class="fw-bold">@<?php echo htmlspecialchars($username); ?></div>
                        <small class="badge bg-primary"><?php echo ucfirst($level); ?></small>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($nama_lengkap, 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- STATS GRID -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_event; ?></div>
                    <div class="stat-label">Total Event</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-running"></i>
                    </div>
                    <div class="stat-number"><?php echo $event_hari_ini; ?></div>
                    <div class="stat-label">Event Hari Ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?php echo $event_akan_datang; ?></div>
                    <div class="stat-label">Event Mendatang</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo $total_kategori; ?></div>
                    <div class="stat-label">Kategori</div>
                </div>
            </div>
            
            <!-- CONTENT ROW -->
            <div class="content-row">
                <!-- LEFT COLUMN: EVENT TERBARU -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-history me-2"></i> Event Terbaru</span>
                        <a href="#" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Judul Event</th>
                                        <th>Tanggal</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($event_terbaru) > 0): ?>
                                        <?php while ($event = mysqli_fetch_assoc($event_terbaru)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($event['judul']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($event['lokasi']); ?></small>
                                                </td>
                                                <td><?php echo date('d/m/Y', strtotime($event['tanggal'])); ?></td>
                                                <td>
                                                    <span class="badge" style="background: <?php echo $event['warna'] ?? '#4361ee'; ?>; color: white;">
                                                        <?php echo $event['kategori_nama'] ?? 'Umum'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge-status badge-<?php echo $event['status']; ?>">
                                                        <?php echo ucfirst($event['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                                <p class="mb-0">Belum ada event</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- RIGHT COLUMN: STATISTIK STATUS -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie me-2"></i> Status Event
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="200"></canvas>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><span class="badge bg-warning me-2">●</span> Draft</span>
                                <strong><?php echo $total_draft; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><span class="badge bg-success me-2">●</span> Publik</span>
                                <strong><?php echo $total_publik; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><span class="badge bg-info me-2">●</span> Selesai</span>
                                <strong><?php echo $total_selesai; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- EVENT PER STATUS (TABS) -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#draft">
                                <i class="fas fa-edit me-1"></i> Draft (<?php echo $total_draft; ?>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#publik">
                                <i class="fas fa-check-circle me-1"></i> Publik (<?php echo $total_publik; ?>)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#selesai">
                                <i class="fas fa-flag-checkered me-1"></i> Selesai (<?php echo $total_selesai; ?>)
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="card-body">
                    <div class="tab-content">
                        <!-- TAB DRAFT -->
                        <div class="tab-pane fade show active" id="draft">
                            <?php if ($total_draft > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Judul</th>
                                                <th>Kategori</th>
                                                <th>Tanggal</th>
                                                <th>Lokasi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php mysqli_data_seek($draft_events, 0); ?>
                                            <?php while ($event = mysqli_fetch_assoc($draft_events)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($event['judul']); ?></td>
                                                    <td><?php echo $event['kategori_nama'] ?? 'Umum'; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($event['tanggal'])); ?></td>
                                                    <td><?php echo htmlspecialchars($event['lokasi']); ?></td>
                                                    <td>
                                                        <a href="form.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?publish=<?php echo $event['id']; ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5>Tidak ada event dalam draft</h5>
                                    <p class="text-muted">Semua event sudah dipublikasikan</p>
                                    <a href="form.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle me-1"></i> Buat Event Baru
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- TAB PUBLIK -->
                        <div class="tab-pane fade" id="publik">
                            <?php if ($total_publik > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Judul</th>
                                                <th>Kategori</th>
                                                <th>Tanggal & Waktu</th>
                                                <th>Lokasi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php mysqli_data_seek($publik_events, 0); ?>
                                            <?php while ($event = mysqli_fetch_assoc($publik_events)): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($event['judul']); ?></strong>
                                                        <?php if ($event['featured']): ?>
                                                            <span class="badge bg-warning ms-2">Featured</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background: <?php echo $event['warna'] ?? '#4361ee'; ?>; color: white;">
                                                            <?php echo $event['kategori_nama'] ?? 'Umum'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d/m/Y', strtotime($event['tanggal'])); ?>
                                                        <?php if ($event['waktu']): ?>
                                                            <br><small><?php echo date('H:i', strtotime($event['waktu'])); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($event['lokasi']); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="../detail_event.php?id=<?php echo $event['id']; ?>" 
                                                               class="btn btn-info" target="_blank">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="form.php?edit=<?php echo $event['id']; ?>" 
                                                               class="btn btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                    <h5>Belum ada event yang dipublikasikan</h5>
                                    <p class="text-muted">Publikasikan event dari tab "Draft"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- TAB SELESAI -->
                        <div class="tab-pane fade" id="selesai">
                            <?php if ($total_selesai > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Judul</th>
                                                <th>Kategori</th>
                                                <th>Tanggal</th>
                                                <th>Lokasi</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php mysqli_data_seek($selesai_events, 0); ?>
                                            <?php while ($event = mysqli_fetch_assoc($selesai_events)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($event['judul']); ?></td>
                                                    <td><?php echo $event['kategori_nama'] ?? 'Umum'; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($event['tanggal'])); ?></td>
                                                    <td><?php echo htmlspecialchars($event['lokasi']); ?></td>
                                                    <td>
                                                        <a href="form.php?edit=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-flag-checkered fa-3x text-muted mb-3"></i>
                                    <h5>Tidak ada event yang selesai</h5>
                                    <p class="text-muted">Semua event masih aktif</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Sistem Event Kampus - Politeknik Negeri Batam
                    | Login: <?php echo htmlspecialchars($username); ?>
                    | Terakhir login: <?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Baru saja'; ?>
                </small>
            </footer>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Pie Chart untuk Status Event
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Publik', 'Selesai'],
                datasets: [{
                    data: [<?php echo $total_draft; ?>, <?php echo $total_publik; ?>, <?php echo $total_selesai; ?>],
                    backgroundColor: [
                        '#ffc107',
                        '#28a745',
                        '#17a2b8'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-activate tab from URL hash
            const hash = window.location.hash;
            if (hash) {
                const tab = new bootstrap.Tab(document.querySelector(`[data-bs-target="${hash}"]`));
                tab.show();
            }
            
            // Publish event confirmation
            document.querySelectorAll('a[href*="publish="]').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Publikasikan event ini?')) {
                        e.preventDefault();
                    }
                });
            });
        });
        
        // Real-time update setiap 30 detik
        setInterval(() => {
            // Update waktu
            const now = new Date();
            document.querySelector('.text-muted small').textContent = 
                now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }, 30000);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
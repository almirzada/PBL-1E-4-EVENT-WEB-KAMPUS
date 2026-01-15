<?php
session_start();
require_once '../koneksi.php';

// Cek session dan level superadmin
if (!isset($_SESSION['admin_event_id']) || $_SESSION['admin_event_level'] != 'superadmin') {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_event_id'];
$current_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin_event WHERE id = $admin_id"));

// Handle actions
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    mysqli_query($conn, "UPDATE admin_event SET status = 'active' WHERE id = $id");
    $_SESSION['message'] = 'Akun berhasil disetujui dan diaktifkan!';
    header('Location: admin_approval.php');
    exit();
}

if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    mysqli_query($conn, "DELETE FROM admin_event WHERE id = $id");
    $_SESSION['message'] = 'Pendaftaran berhasil ditolak!';
    header('Location: admin_approval.php');
    exit();
}

// Get pending admins
$pending_admins = mysqli_query($conn, 
    "SELECT * FROM admin_event 
     WHERE status = 'inactive' 
     AND level IN ('admin', 'panitia')
     ORDER BY created_at DESC");

$pending_count = mysqli_num_rows($pending_admins);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Admin - Portal Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .badge-pending {
            background: linear-gradient(135deg, #ff9a00 0%, #ff6a00 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .badge-level {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-admin { background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); color: white; }
        .badge-panitia { background: linear-gradient(135deg, #ffe66d 0%, #f9c74f 100%); color: #333; }
        
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
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
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
                    
                    <!-- MENU SUPERADMIN -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Superadmin</small>
                        <a href="admin_management.php" class="nav-link">
                            <i class="fas fa-users-cog"></i> <span class="menu-text">Kelola Admin</span>
                        </a>
                        <a href="admin_approval.php" class="nav-link active">
                            <i class="fas fa-user-check"></i> <span class="menu-text">Persetujuan</span>
                            <?php if ($pending_count > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
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
            <!-- TOP HEADER -->
            <div class="top-header">
                <div>
                    <h3 class="mb-1"><i class="fas fa-user-check me-2"></i> Persetujuan Pendaftaran Admin</h3>
                    <p class="mb-0 text-muted">Tinjau dan setujui pendaftaran admin baru</p>
                </div>
                <div class="user-info">
                    <div class="text-end">
                        <div class="fw-bold"><?php echo $current_user['nama_lengkap']; ?></div>
                        <small class="badge bg-primary">Superadmin</small>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['nama_lengkap'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <!-- STATS -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0"><?php echo $pending_count; ?></h2>
                                    <p class="mb-0">Menunggu Persetujuan</p>
                                </div>
                                <i class="fas fa-user-clock fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">
                                        <?php 
                                        $total_active = mysqli_fetch_assoc(mysqli_query($conn, 
                                            "SELECT COUNT(*) as total FROM admin_event WHERE status = 'active'"))['total'];
                                        echo $total_active;
                                        ?>
                                    </h2>
                                    <p class="mb-0">Admin Aktif</p>
                                </div>
                                <i class="fas fa-user-check fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0">
                                        <?php 
                                        $total_admins = mysqli_fetch_assoc(mysqli_query($conn, 
                                            "SELECT COUNT(*) as total FROM admin_event"))['total'];
                                        echo $total_admins;
                                        ?>
                                    </h2>
                                    <p class="mb-0">Total Admin</p>
                                </div>
                                <i class="fas fa-users fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DAFTAR PENDAFTARAN -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i> Daftar Pendaftaran Menunggu
                        <span class="badge bg-warning ms-2"><?php echo $pending_count; ?> akun</span>
                    </h5>
                    <a href="admin_management.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-users-cog me-1"></i> Kelola Admin
                    </a>
                </div>
                
                <div class="card-body">
                    <?php if ($pending_count > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>No. WA</th>
                                        <th>Level</th>
                                        <th>Alasan Daftar</th>
                                        <th>Daftar Pada</th>
                                        <th width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php while ($admin = mysqli_fetch_assoc($pending_admins)): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['nama_lengkap']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['no_wa']); ?></td>
                                            <td>
                                                <span class="badge-level badge-<?php echo $admin['level']; ?>">
                                                    <?php echo ucfirst($admin['level']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($admin['alasan_daftar'] ?? '-'); ?></small>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="admin_approval.php?approve=<?php echo $admin['id']; ?>" 
                                                       class="btn btn-success" 
                                                       onclick="return confirm('Setujui admin <?php echo htmlspecialchars($admin['nama_lengkap']); ?>?')">
                                                        <i class="fas fa-check me-1"></i> Setujui
                                                    </a>
                                                    <a href="admin_approval.php?reject=<?php echo $admin['id']; ?>" 
                                                       class="btn btn-danger"
                                                       onclick="return confirm('Tolak pendaftaran <?php echo htmlspecialchars($admin['nama_lengkap']); ?>?')">
                                                        <i class="fas fa-times"></i>
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
                            <i class="fas fa-user-check fa-4x text-muted mb-3"></i>
                            <h4>Tidak ada pendaftaran yang menunggu</h4>
                            <p class="text-muted">Semua pendaftaran telah diproses</p>
                            <a href="admin_management.php" class="btn btn-primary">
                                <i class="fas fa-users-cog me-2"></i> Kelola Admin
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- PETUNJUK -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fas fa-info-circle me-2"></i> Petunjuk Persetujuan
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check text-success me-2"></i> Setujui Jika:</h6>
                            <ul class="text-muted">
                                <li>Data lengkap dan valid</li>
                                <li>Alasan mendaftar jelas</li>
                                <li>Email aktif dan dapat dihubungi</li>
                                <li>Memenuhi syarat sebagai admin/panitia</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-times text-danger me-2"></i> Tolak Jika:</h6>
                            <ul class="text-muted">
                                <li>Data tidak lengkap</li>
                                <li>Email tidak valid</li>
                                <li>Alasan tidak jelas</li>
                                <li>Terdapat duplikasi akun</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh setiap 60 detik jika ada pending
        <?php if ($pending_count > 0): ?>
        setTimeout(() => {
            window.location.reload();
        }, 60000);
        <?php endif; ?>
        
        // Konfirmasi aksi
        document.querySelectorAll('a[href*="approve="], a[href*="reject="]').forEach(link => {
            link.addEventListener('click', function(e) {
                const action = this.href.includes('approve') ? 'menyetujui' : 'menolak';
                if (!confirm(`Anda yakin ingin ${action} admin ini?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
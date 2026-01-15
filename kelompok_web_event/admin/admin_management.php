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

// Handle POST actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $target_id = $_POST['admin_id'] ?? '';
    
    switch($action) {
        case 'activate':
            mysqli_query($conn, "UPDATE admin_event SET status = 'active' WHERE id = $target_id");
            $message = '<div class="alert alert-success">Akun berhasil diaktifkan!</div>';
            break;
            
        case 'deactivate':
            mysqli_query($conn, "UPDATE admin_event SET status = 'inactive' WHERE id = $target_id");
            $message = '<div class="alert alert-warning">Akun berhasil dinonaktifkan!</div>';
            break;
            
        case 'delete':
            // Jangan hapus superadmin
            $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT level FROM admin_event WHERE id = $target_id"));
            if ($check['level'] != 'superadmin') {
                mysqli_query($conn, "DELETE FROM admin_event WHERE id = $target_id");
                $message = '<div class="alert alert-danger">Akun berhasil dihapus!</div>';
            }
            break;
            
        case 'update_level':
            $new_level = $_POST['level'] ?? '';
            if (in_array($new_level, ['admin', 'panitia'])) {
                mysqli_query($conn, "UPDATE admin_event SET level = '$new_level' WHERE id = $target_id");
                $message = '<div class="alert alert-info">Level akun berhasil diubah!</div>';
            }
            break;
            
        case 'reset_password':
            $default_pass = password_hash('password123', PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE admin_event SET password = '$default_pass' WHERE id = $target_id");
            $message = '<div class="alert alert-info">Password berhasil direset ke "password123"!</div>';
            break;
    }
}

// Ambil semua admin (kecuali diri sendiri untuk superadmin)
$query = "SELECT * FROM admin_event WHERE id != $admin_id ORDER BY 
          CASE level 
            WHEN 'superadmin' THEN 1 
            WHEN 'admin' THEN 2 
            WHEN 'panitia' THEN 3 
          END, created_at DESC";
$admins_result = mysqli_query($conn, $query);

// Hitung statistik
$stats = [
    'total' => mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admin_event")),
    'superadmin' => mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admin_event WHERE level = 'superadmin'")),
    'admin' => mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admin_event WHERE level = 'admin'")),
    'panitia' => mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admin_event WHERE level = 'panitia'")),
    'active' => mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admin_event WHERE status = 'active'")),
    'inactive' => mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admin_event WHERE status = 'inactive'")),
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Portal Kampus</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .badge-level {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-superadmin { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; }
        .badge-admin { background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); color: white; }
        .badge-panitia { background: linear-gradient(135deg, #ffe66d 0%, #f9c74f 100%); color: #333; }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
        }
        
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 0 20px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 18px 25px;
            font-weight: 600;
        }
        /* CSS yang benar untuk card-body */
.card-body {
    padding: 20px;
}

/* Atau jika mau pakai flex, tapi hanya untuk container tertentu */
.card-header .card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

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
                        <a href="admin_management.php" class="nav-link active">
                            <i class="fas fa-users-cog"></i> <span class="menu-text">Kelola Admin</span>
                        </a>
                        <a href="admin_approval.php" class="nav-link">
                            <i class="fas fa-user-check"></i> <span class="menu-text">Persetujuan</span>
                            <?php 
                            $pending_count = mysqli_num_rows(mysqli_query($conn, 
                                "SELECT id FROM admin_event WHERE status = 'inactive'"));
                            if ($pending_count > 0): ?>
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
                    <h3 class="mb-1"><i class="fas fa-users-cog me-2"></i> Kelola Admin</h3>
                    <p class="mb-0 text-muted">Kelola semua akun admin dan panitia</p>
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
            
            <?php echo $message; ?>
            <?php
// Handle success/error messages from create_admin.php
if (isset($_GET['success'])) {
    $success_msg = $_GET['success'];
    $username = $_GET['username'] ?? '';
    
    if ($success_msg == 'created') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Berhasil!</strong> Admin <strong>' . htmlspecialchars($username) . '</strong> berhasil dibuat.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

if (isset($_GET['error'])) {
    $error_type = $_GET['error'];
    $error_msg = '';
    
    switch($error_type) {
        case 'username_exists':
            $error_msg = 'Username sudah digunakan!';
            break;
        case 'email_exists':
            $error_msg = 'Email sudah terdaftar!';
            break;
        case 'username_invalid':
            $error_msg = 'Username minimal 5 karakter!';
            break;
        case 'name_required':
            $error_msg = 'Nama lengkap harus diisi!';
            break;
        case 'database':
            $error_msg = 'Terjadi kesalahan database!';
            break;
        default:
            $error_msg = 'Terjadi kesalahan!';
    }
    
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Error!</strong> ' . $error_msg . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>
            
            <!-- STATISTIK -->
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--primary);">
                    <i class="fas fa-users text-primary"></i>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="text-muted">Total Akun</div>
                </div>
                
                <div class="stat-card" style="border-left-color: #ff6b6b;">
                    <i class="fas fa-crown text-danger"></i>
                    <div class="stat-number"><?php echo $stats['superadmin']; ?></div>
                    <div class="text-muted">Superadmin</div>
                </div>
                
                <div class="stat-card" style="border-left-color: #4ecdc4;">
                    <i class="fas fa-user-shield text-info"></i>
                    <div class="stat-number"><?php echo $stats['admin']; ?></div>
                    <div class="text-muted">Admin</div>
                </div>
                
                <div class="stat-card" style="border-left-color: #ffe66d;">
                    <i class="fas fa-user-friends text-warning"></i>
                    <div class="stat-number"><?php echo $stats['panitia']; ?></div>
                    <div class="text-muted">Panitia</div>
                </div>
                
                <div class="stat-card" style="border-left-color: #28a745;">
                    <i class="fas fa-check-circle text-success"></i>
                    <div class="stat-number"><?php echo $stats['active']; ?></div>
                    <div class="text-muted">Aktif</div>
                </div>
                
                <div class="stat-card" style="border-left-color: #6c757d;">
                    <i class="fas fa-ban text-secondary"></i>
                    <div class="stat-number"><?php echo $stats['inactive']; ?></div>
                    <div class="text-muted">Nonaktif</div>
                </div>
            </div>
            
            <!-- TABEL ADMIN -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Semua Admin</h5>
                    <div>
                        <a href="admin_approval.php" class="btn btn-warning btn-sm me-2">
                            <i class="fas fa-user-check me-1"></i> Persetujuan
                            <?php if ($stats['inactive'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $stats['inactive']; ?></span>
                            <?php endif; ?>
                        </a>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Admin
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">#</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Email</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Terakhir Login</th>
                                    <th>Dibuat</th>
                                    <th width="200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($admins_result) > 0): ?>
                                    <?php $no = 1; ?>
                                    <?php while ($admin = mysqli_fetch_assoc($admins_result)): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                                <?php if ($admin['id'] == 1): ?>
                                                    <br><small class="text-muted">(Default)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['nama_lengkap']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td>
                                                <span class="badge-level badge-<?php echo $admin['level']; ?>">
                                                    <?php echo ucfirst($admin['level']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge-status badge-<?php echo $admin['status']; ?>">
                                                    <?php echo $admin['status'] == 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($admin['last_login']): ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($admin['last_login'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum login</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($admin['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Tombol Aktivasi/Nonaktif -->
                                                    <?php if ($admin['status'] == 'active'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <button type="submit" class="btn btn-warning btn-sm" 
                                                                    onclick="return confirm('Nonaktifkan akun ini?')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Tombol Ubah Level -->
                                                    <?php if ($admin['level'] != 'superadmin'): ?>
                                                        <button type="button" class="btn btn-info btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#changeLevelModal"
                                                                data-id="<?php echo $admin['id']; ?>"
                                                                data-level="<?php echo $admin['level']; ?>"
                                                                data-name="<?php echo htmlspecialchars($admin['nama_lengkap']); ?>">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Tombol Reset Password -->
                                                    <?php if ($admin['level'] != 'superadmin' || $admin['id'] != $admin_id): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            <input type="hidden" name="action" value="reset_password">
                                                            <button type="submit" class="btn btn-secondary btn-sm"
                                                                    onclick="return confirm('Reset password ke default (password123)?')">
                                                                <i class="fas fa-key"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Tombol Hapus -->
                                                    <?php if ($admin['level'] != 'superadmin'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Hapus akun ini? Tindakan ini tidak dapat dibatalkan.')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                            <h5>Belum ada admin lain</h5>
                                            <p class="text-muted">Tambahkan admin baru untuk mulai berkolaborasi</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                                                <i class="fas fa-plus-circle me-1"></i> Tambah Admin Baru
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- INFORMASI -->
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi:</strong> 
                Superadmin tidak dapat dihapus atau diubah levelnya. 
                Reset password akan mengubah password menjadi <code>password123</code>.
                Admin dengan status "Nonaktif" tidak dapat login sampai diaktifkan.
            </div>
        </div>
    </div>
    
    <!-- MODAL: TAMBAH ADMIN BARU -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="create_admin.php">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Tambah Admin Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required minlength="5">
                            <small class="text-muted">Minimal 5 karakter, huruf dan angka saja</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">No. WhatsApp</label>
                            <input type="tel" name="no_wa" class="form-control" placeholder="081234567890">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Level <span class="text-danger">*</span></label>
                                <select name="level" class="form-select" required>
                                    <option value="admin">Admin</option>
                                    <option value="panitia">Panitia</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Default</label>
                            <input type="text" class="form-control" value="password123" readonly>
                            <small class="text-muted">Admin akan login dengan password ini pertama kali</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- MODAL: UBAH LEVEL -->
    <div class="modal fade" id="changeLevelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="levelForm">
                    <input type="hidden" name="admin_id" id="modalAdminId">
                    <input type="hidden" name="action" value="update_level">
                    
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i> Ubah Level Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Ubah level untuk: <strong id="modalAdminName"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">Level Baru</label>
                            <select name="level" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="panitia">Panitia</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian:</strong> 
                            Admin memiliki akses penuh untuk mengelola event dan berita, 
                            sedangkan Panitia memiliki akses terbatas.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal untuk ubah level
        const changeLevelModal = document.getElementById('changeLevelModal');
        if (changeLevelModal) {
            changeLevelModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const adminId = button.getAttribute('data-id');
                const adminLevel = button.getAttribute('data-level');
                const adminName = button.getAttribute('data-name');
                
                document.getElementById('modalAdminId').value = adminId;
                document.getElementById('modalAdminName').textContent = adminName;
                document.querySelector('#levelForm select[name="level"]').value = adminLevel;
            });
        }
        
        // Konfirmasi hapus
        document.querySelectorAll('form[action*="delete"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Yakin ingin menghapus akun ini? Data tidak dapat dikembalikan.')) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-refresh jika ada perubahan
        let refreshTimer;
        function startAutoRefresh() {
            refreshTimer = setTimeout(() => {
                window.location.reload();
            }, 120000); // 2 menit
        }
        
        // Hentikan auto-refresh jika user aktif
        document.addEventListener('mousemove', () => {
            clearTimeout(refreshTimer);
            startAutoRefresh();
        });
        
        startAutoRefresh();
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
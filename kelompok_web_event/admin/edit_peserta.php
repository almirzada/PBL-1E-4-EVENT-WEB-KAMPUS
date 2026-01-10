<?php
session_start();
require_once '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_event_id'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_event_id'];

// Ambil ID peserta dari URL
$peserta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($peserta_id == 0) {
    header('Location: admin_peserta.php');
    exit();
}

// Ambil data peserta
$query = "SELECT 
            p.*, 
            e.judul as event_judul,
            e.tanggal as event_tanggal,
            e.lokasi as event_lokasi,
            e.biaya_pendaftaran,
            t.nama_tim,
            t.kode_pendaftaran as kode_tim
          FROM peserta p
          LEFT JOIN events e ON p.event_id = e.id
          LEFT JOIN tim_event t ON p.tim_id = t.id
          WHERE p.id = $peserta_id";

$result = mysqli_query($conn, $query);
$peserta = mysqli_fetch_assoc($result);

if (!$peserta) {
    header('Location: admin_peserta.php');
    exit();
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $npm = mysqli_real_escape_string($conn, $_POST['npm']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_wa = mysqli_real_escape_string($conn, $_POST['no_wa']);
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $status_pembayaran = mysqli_real_escape_string($conn, $_POST['status_pembayaran']);
    
    $update_query = "UPDATE peserta SET 
                    nama = '$nama',
                    npm = '$npm',
                    email = '$email',
                    no_wa = '$no_wa',
                    jurusan = '$jurusan',
                    status_pembayaran = '$status_pembayaran'
                    WHERE id = $peserta_id";
    
    if (mysqli_query($conn, $update_query)) {
        $success = "Data peserta berhasil diperbarui!";
        // Refresh data
        $result = mysqli_query($conn, $query);
        $peserta = mysqli_fetch_assoc($result);
    } else {
        $error = "Gagal memperbarui data: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Peserta - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #f72585;
        }
        
        body {
            background-color: #f5f7fb;
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
        
        /* FORM CONTAINER */
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 20px;
        }
        
        .form-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 30px;
        }
        
        .form-body {
            padding: 30px;
        }
        
        /* CARD STYLES */
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }
        
        /* BADGES */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .badge-menunggu { background: #fff3cd; color: #856404; }
        .badge-terverifikasi { background: #d4edda; color: #155724; }
        .badge-ditolak { background: #f8d7da; color: #721c24; }
        .badge-gratis { background: #d1ecf1; color: #0c5460; }
        
        .badge-tipe {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .badge-individu { background: #e3f2fd; color: #1565c0; }
        .badge-ketua { background: #f3e5f5; color: #7b1fa2; }
        .badge-anggota { background: #e8f5e8; color: #2e7d32; }
        
        /* FORM ELEMENTS */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .form-control[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        /* BUTTONS */
        .btn-primary {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
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
            .form-body {
                padding: 20px;
            }
            
            .main-content {
                padding: 15px;
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
                        <a href="admin_peserta.php" class="nav-link active">
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
                    <h3 class="mb-1">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Edit Data Peserta
                    </h3>
                    <p class="text-muted mb-0">Edit informasi peserta: <?php echo htmlspecialchars($peserta['nama']); ?></p>
                </div>
                <div>
                    <a href="admin_peserta.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
            
            <!-- ALERTS -->
            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Berhasil!</h6>
                    <p class="mb-0"><?php echo $success; ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Error!</h6>
                    <p class="mb-0"><?php echo $error; ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- PROFILE SUMMARY -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="info-card">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="avatar-lg bg-light rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <div class="avatar-title bg-soft-primary text-primary rounded-circle" style="width: 80px; height: 80px; font-size: 2.5rem;">
                                        <?php echo strtoupper(substr($peserta['nama'], 0, 1)); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <h4 class="mb-1"><?php echo htmlspecialchars($peserta['nama']); ?></h4>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($peserta['npm']); ?> • <?php echo htmlspecialchars($peserta['email']); ?></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge-tipe badge-<?php echo $peserta['status_anggota']; ?>">
                                        <?php echo ucfirst($peserta['status_anggota']); ?>
                                    </span>
                                    <?php 
                                    $statusClass = '';
                                    switch($peserta['status_pembayaran']) {
                                        case 'terverifikasi': $statusClass = 'terverifikasi'; break;
                                        case 'menunggu_verifikasi': $statusClass = 'menunggu'; break;
                                        case 'ditolak': $statusClass = 'ditolak'; break;
                                        default: $statusClass = 'gratis';
                                    }
                                    ?>
                                    <span class="badge-status badge-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $peserta['status_pembayaran'])); ?>
                                    </span>
                                    <?php if (!empty($peserta['nama_tim'])): ?>
                                        <span class="badge bg-info">Tim: <?php echo htmlspecialchars($peserta['nama_tim']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Individu</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- EDIT FORM -->
            <div class="form-container">
                <div class="form-header">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-edit me-2"></i>
                        Form Edit Data
                    </h5>
                </div>
                
                <div class="form-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama" 
                                           value="<?php echo htmlspecialchars($peserta['nama']); ?>" required>
                                    <div class="form-text">Nama lengkap peserta sesuai KTP</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">NPM <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="npm" 
                                           value="<?php echo htmlspecialchars($peserta['npm']); ?>" required>
                                    <div class="form-text">Nomor Pokok Mahasiswa</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($peserta['email']); ?>" required>
                                    <div class="form-text">Email aktif peserta</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">WhatsApp <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="no_wa" 
                                           value="<?php echo htmlspecialchars($peserta['no_wa']); ?>" required>
                                    <div class="form-text">Nomor WhatsApp aktif dengan kode negara (62)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Jurusan / Program Studi</label>
                                    <input type="text" class="form-control" name="jurusan" 
                                           value="<?php echo htmlspecialchars($peserta['jurusan']); ?>">
                                    <div class="form-text">Jurusan atau program studi peserta</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Status Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status_pembayaran" required>
                                        <option value="menunggu_verifikasi" <?php echo $peserta['status_pembayaran'] == 'menunggu_verifikasi' ? 'selected' : ''; ?>>⏳ Menunggu Verifikasi</option>
                                        <option value="terverifikasi" <?php echo $peserta['status_pembayaran'] == 'terverifikasi' ? 'selected' : ''; ?>>✅ Terverifikasi</option>
                                        <option value="ditolak" <?php echo $peserta['status_pembayaran'] == 'ditolak' ? 'selected' : ''; ?>>❌ Ditolak</option>
                                        <option value="gratis" <?php echo $peserta['status_pembayaran'] == 'gratis' ? 'selected' : ''; ?>>🎯 Gratis</option>
                                    </select>
                                    <div class="form-text">Status pembayaran peserta</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Event</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($peserta['event_judul']); ?>" readonly>
                                    <div class="form-text">Event yang diikuti (tidak dapat diubah)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Tim</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo !empty($peserta['nama_tim']) ? htmlspecialchars($peserta['nama_tim']) : 'Individu'; ?>" readonly>
                                    <div class="form-text">
                                        <?php if (!empty($peserta['nama_tim'])): ?>
                                            Kode Tim: <?php echo $peserta['kode_tim']; ?>
                                        <?php else: ?>
                                            Peserta mendaftar secara individu
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="admin_peserta.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i> Batalkan
                                        </a>
                                    </div>
                                    <div class="d-flex gap-3">
                                        <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
                                            <i class="fas fa-redo me-2"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- INFORMASI TAMBAHAN -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="info-card">
                        <h6 class="mb-3"><i class="fas fa-info-circle text-primary me-2"></i> Informasi Pendaftaran</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Kode Pendaftaran:</strong></td>
                                    <td><code class="bg-light p-1 rounded"><?php echo $peserta['kode_pendaftaran']; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Daftar:</strong></td>
                                    <td><?php echo date('d F Y H:i:s', strtotime($peserta['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Event Tanggal:</strong></td>
                                    <td><?php echo date('d F Y', strtotime($peserta['event_tanggal'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Lokasi Event:</strong></td>
                                    <td><?php echo !empty($peserta['event_lokasi']) ? htmlspecialchars($peserta['event_lokasi']) : '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-card">
                        <h6 class="mb-3"><i class="fas fa-money-bill-wave text-success me-2"></i> Informasi Biaya</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Biaya Pendaftaran:</strong></td>
                                    <td>
                                        <?php if ($peserta['biaya_pendaftaran'] > 0): ?>
                                            <span class="fw-bold">Rp <?php echo number_format($peserta['biaya_pendaftaran'], 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Gratis</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status Anggota:</strong></td>
                                    <td>
                                        <span class="badge-tipe badge-<?php echo $peserta['status_anggota']; ?>">
                                            <?php echo ucfirst($peserta['status_anggota']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>ID Peserta:</strong></td>
                                    <td><code class="bg-light p-1 rounded">#<?php echo str_pad($peserta['id'], 5, '0', STR_PAD_LEFT); ?></code></td>
                                </tr>
                                <?php if (!empty($peserta['bukti_pembayaran'])): ?>
                                <tr>
                                    <td><strong>Bukti Pembayaran:</strong></td>
                                    <td>
                                        <a href="../uploads/bukti_pembayaran/<?php echo $peserta['bukti_pembayaran']; ?>" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> Lihat
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Portal Informasi Kampus - Admin Panel
                    | Edit Data Peserta ID: <?php echo $peserta['id']; ?>
                    | Terakhir update: <?php echo !empty($peserta['updated_at']) ? date('d/m/Y H:i', strtotime($peserta['updated_at'])) : '-'; ?>
                </small>
            </footer>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        
                        // Add error message
                        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = 'Field ini wajib diisi';
                            field.parentNode.appendChild(errorDiv);
                        }
                    } else {
                        field.classList.remove('is-invalid');
                        
                        // Remove error message
                        const errorDiv = field.nextElementSibling;
                        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                            errorDiv.remove();
                        }
                    }
                });
                
                // Validate email format
                const emailField = form.querySelector('input[type="email"]');
                if (emailField && emailField.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailField.value)) {
                        isValid = false;
                        emailField.classList.add('is-invalid');
                        
                        if (!emailField.nextElementSibling || !emailField.nextElementSibling.classList.contains('invalid-feedback')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = 'Format email tidak valid';
                            emailField.parentNode.appendChild(errorDiv);
                        }
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    // Show error alert
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Mohon periksa kembali form Anda. Ada field yang belum diisi atau format salah.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.insertBefore(alertDiv, form.firstChild);
                }
            });
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    
                    // Remove error message
                    const errorDiv = this.nextElementSibling;
                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv.remove();
                    }
                });
            });
        });
        
        // Confirm before reset
        function confirmReset() {
            if (confirm('Anda yakin ingin mereset form? Semua perubahan yang belum disimpan akan hilang.')) {
                window.location.reload();
            }
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
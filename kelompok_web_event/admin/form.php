<?php
session_start();
require_once '../koneksi.php';

// Cek login
if (!isset($_SESSION['admin_event_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data admin
$admin_id = $_SESSION['admin_event_id'];

// Mode: edit atau tambah
$mode = 'tambah';
$event_id = 0;
$event = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $mode = 'edit';
    $event_id = intval($_GET['edit']);
    
    // Ambil data event dari database
    $query = mysqli_query($conn, 
        "SELECT e.*, k.nama as kategori_nama, k.warna 
         FROM events e 
         LEFT JOIN kategori k ON e.kategori_id = k.id 
         WHERE e.id = $event_id");
    
    if (mysqli_num_rows($query) > 0) {
        $event = mysqli_fetch_assoc($query);
    } else {
        $_SESSION['alert_message'] = 'Event tidak ditemukan!';
        $_SESSION['alert_type'] = 'error';
        header('Location: dashboard.php');
        exit();
    }
}

// Ambil semua kategori untuk dropdown
$kategori_list = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama");

// PROSES FORM JIKA DI-SUBMIT
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $deskripsi_singkat = mysqli_real_escape_string($conn, $_POST['deskripsi_singkat'] ?? '');
    $kategori_id = intval($_POST['kategori_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $batas_pendaftaran = $_POST['batas_pendaftaran'] ?? ''; // TAMBAH INI
    $waktu = $_POST['waktu'] ?? '';
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
    $alamat_lengkap = mysqli_real_escape_string($conn, $_POST['alamat_lengkap'] ?? '');
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person'] ?? '');
    $contact_wa = mysqli_real_escape_string($conn, $_POST['contact_wa'] ?? '');
    $kuota_peserta = intval($_POST['kuota_peserta'] ?? 0);
    $biaya_pendaftaran = floatval($_POST['biaya_pendaftaran'] ?? 0);
    $link_pendaftaran = mysqli_real_escape_string($conn, $_POST['link_pendaftaran'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // SISTEM TIM UNTUK SEMUA KATEGORI
    $tipe_pendaftaran = mysqli_real_escape_string($conn, $_POST['tipe_pendaftaran'] ?? 'individu');
    $min_anggota = intval($_POST['min_anggota'] ?? 1);
    $max_anggota = intval($_POST['max_anggota'] ?? 1);
    
    // Validasi khusus untuk tim
    if ($tipe_pendaftaran == 'tim') {
        if ($min_anggota < 2) {
            $errors[] = 'Minimal anggota untuk tim harus 2 atau lebih';
        }
        if ($min_anggota > $max_anggota) {
            $errors[] = 'Minimal anggota tidak boleh lebih besar dari maksimal anggota';
        }
        if ($max_anggota > 50) {
            $errors[] = 'Maksimal anggota tidak boleh lebih dari 50';
        }
    }
    
    // Validasi batas pendaftaran (TAMBAH INI)
    if (!empty($batas_pendaftaran) && !empty($tanggal)) {
        if (strtotime($batas_pendaftaran) > strtotime($tanggal)) {
            $errors[] = 'Batas pendaftaran tidak boleh setelah tanggal event';
        }
    }
    
    // Validasi lainnya
    if (empty($judul)) $errors[] = 'Judul event harus diisi';
    if (empty($deskripsi)) $errors[] = 'Deskripsi event harus diisi';
    if (empty($tanggal)) $errors[] = 'Tanggal event harus diisi';
    if (empty($lokasi)) $errors[] = 'Lokasi event harus diisi';
    
    // Jika tidak ada error, proses simpan
    if (empty($errors)) {
        // Generate slug dari judul
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
        
        // Upload gambar poster jika ada
        $poster = $event['poster'] ?? '';
        
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['poster']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../uploads/events/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
                $filename = 'event_' . time() . '_' . uniqid() . '.' . $file_ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $filepath)) {
                    $poster = 'uploads/events/' . $filename;
                    
                    // Hapus poster lama jika edit
                    if ($mode == 'edit' && !empty($event['poster'])) {
                        @unlink('../' . $event['poster']);
                    }
                }
            }
        }
        
        // Set default values untuk field yang mungkin missing
        $total_pendaftar = 0;
        $views = 0;
        
        
        if ($mode == 'tambah') {
            // INSERT QUERY - SEMUA FIELD DIMASUKKAN
            $sql = "INSERT INTO events (
                judul, slug, deskripsi, deskripsi_singkat, kategori_id, 
                tanggal, batas_pendaftaran, waktu, lokasi, alamat_lengkap, poster, 
                kuota_peserta, biaya_pendaftaran, link_pendaftaran, 
                contact_person, contact_wa, status, featured, created_by,
                tipe_pendaftaran, min_anggota, max_anggota,
                total_pendaftar, views,
                created_at, updated_at
            ) VALUES (
                '$judul', '$slug', '$deskripsi', '$deskripsi_singkat', $kategori_id,
                '$tanggal', " . ($batas_pendaftaran ? "'$batas_pendaftaran'" : "NULL") . ", 
                " . ($waktu ? "'$waktu'" : "NULL") . ", '$lokasi', '$alamat_lengkap', " . ($poster ? "'$poster'" : "NULL") . ",
                $kuota_peserta, $biaya_pendaftaran, " . ($link_pendaftaran ? "'$link_pendaftaran'" : "NULL") . ",
                " . ($contact_person ? "'$contact_person'" : "NULL") . ", " . ($contact_wa ? "'$contact_wa'" : "NULL") . ",
                '$status', $featured, $admin_id,
                '$tipe_pendaftaran', $min_anggota, $max_anggota,
                $total_pendaftar, $views,
                NOW(), NOW()
            )";
            
            if (mysqli_query($conn, $sql)) {
                $event_id = mysqli_insert_id($conn);
                $_SESSION['alert_message'] = 'Event berhasil ditambahkan! ID Event: ' . $event_id;
                $_SESSION['alert_type'] = 'success';
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = 'Gagal menyimpan event: ' . mysqli_error($conn);
                // Debug info
                $errors[] = 'Query: ' . htmlspecialchars($sql);
            }
            
        } else {
            // UPDATE QUERY
            $sql = "UPDATE events SET
                judul = '$judul',
                slug = '$slug',
                deskripsi = '$deskripsi',
                deskripsi_singkat = '$deskripsi_singkat',
                kategori_id = $kategori_id,
                tanggal = '$tanggal',
                batas_pendaftaran = " . ($batas_pendaftaran ? "'$batas_pendaftaran'" : "NULL") . ",
                waktu = " . ($waktu ? "'$waktu'" : "NULL") . ",
                lokasi = '$lokasi',
                alamat_lengkap = '$alamat_lengkap',
                poster = " . ($poster ? "'$poster'" : "NULL") . ",
                kuota_peserta = $kuota_peserta,
                biaya_pendaftaran = $biaya_pendaftaran,
                link_pendaftaran = " . ($link_pendaftaran ? "'$link_pendaftaran'" : "NULL") . ",
                contact_person = " . ($contact_person ? "'$contact_person'" : "NULL") . ",
                contact_wa = " . ($contact_wa ? "'$contact_wa'" : "NULL") . ",
                status = '$status',
                featured = $featured,
                tipe_pendaftaran = '$tipe_pendaftaran',
                min_anggota = $min_anggota,
                max_anggota = $max_anggota,
                updated_at = NOW()
                WHERE id = $event_id";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['alert_message'] = 'Event berhasil diperbarui! ID Event: ' . $event_id;
                $_SESSION['alert_type'] = 'success';
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = 'Gagal mengupdate event: ' . mysqli_error($conn);
                // Debug info
                $errors[] = 'Query: ' . htmlspecialchars($sql);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode == 'edit' ? 'Edit Event' : 'Tambah Event'; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --warning: #ffc107;
            --danger: #f72585;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* ADMIN WRAPPER */
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
            margin-bottom: 30px;
        }
        
        .form-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px 30px;
        }
        
        .form-body {
            padding: 30px;
        }
        
        /* SECTION TITLE */
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            position: relative;
        }
        
        .section-title i {
            background: var(--primary);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-right: 12px;
        }
        
        /* FORM STYLES */
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .required:after {
            content: " *";
            color: #dc3545;
        }
        
        /* PREVIEW IMAGE */
        .preview-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
            position: relative;
            overflow: hidden;
        }
        
        .preview-container:hover {
            border-color: var(--primary);
            background: #f0f3ff;
        }
        
        .preview-container i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: none;
        }
        
        .current-image {
            position: relative;
            display: inline-block;
        }
        
        .delete-image-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .delete-image-btn:hover {
            background: rgba(255, 0, 0, 1);
            transform: scale(1.1);
        }
        
        /* BUTTONS */
        .btn-primary-custom {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        /* SUMMERNOTE EDITOR */
        .note-editor {
            border-radius: 8px;
            border: 1px solid #ced4da;
        }
        
        .note-editor .note-toolbar {
            border-bottom: 1px solid #ced4da;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        
        .note-editor .note-editable {
            border-radius: 0 0 8px 8px;
            min-height: 250px;
        }
        
        /* ALERTS */
        .alert-custom {
            border-left: 4px solid var(--primary);
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* TIM SETTINGS */
        .tim-settings {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
            margin-top: 15px;
            display: none;
        }
        
        .tim-settings.show {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .preset-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .preset-btn {
            font-size: 0.8rem;
            padding: 5px 12px;
            border-radius: 20px;
            background: white;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .preset-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .preset-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .preset-btn.recommended {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-color: #2196f3;
            color: #0d47a1;
            font-weight: 500;
        }
        
        /* KATEGORI HINTS */
        .kategori-hint {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 10px;
            font-size: 0.9rem;
            display: none;
        }
        
        .kategori-hint.show {
            display: block;
        }
        
        /* MODE BUTTONS */
        .mode-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            display: none;
        }
        
        .mode-btn {
            font-size: 0.8rem;
            padding: 6px 14px;
            border-radius: 20px;
            background: white;
            border: 1px solid #dee2e6;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .mode-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .mode-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* CARD STYLES */
        .form-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .form-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        /* BATAS PENDAFTARAN STATUS */
        .status-pendaftaran-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            min-height: 60px;
        }
        
        .status-open {
            color: #198754;
            font-weight: 600;
        }
        
        .status-closing {
            color: #fd7e14;
            font-weight: 600;
        }
        
        .status-closed {
            color: #dc3545;
            font-weight: 600;
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
            .form-header {
                padding: 20px;
            }
            
            .form-body {
                padding: 20px;
            }
            
            .preset-buttons {
                justify-content: center;
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
                    <h3 class="mb-1">
                        <i class="fas fa-calendar-plus text-primary me-2"></i>
                        <?php echo $mode == 'edit' ? '✏️ Edit Event' : '➕ Tambah Event Baru'; ?>
                    </h3>
                    <p class="text-muted mb-0">
                        <?php echo $mode == 'edit' ? 'Perbarui informasi event yang sudah ada' : 'Isi form di bawah untuk menambahkan event baru'; ?>
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                </a>
            </div>
            
            <!-- FORM CONTAINER -->
            <div class="form-container">
                <!-- FORM HEADER -->
                <div class="form-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-2 text-white">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Form <?php echo $mode == 'edit' ? 'Edit' : 'Tambah'; ?> Event
                            </h4>
                            <p class="mb-0 text-white opacity-75">
                                ID: <?php echo $mode == 'edit' ? $event_id : 'Baru'; ?> 
                                | Admin: <?php echo $_SESSION['username'] ?? 'Admin'; ?>
                            </p>
                        </div>
                        <div class="badge bg-light text-primary px-3 py-2">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('d F Y'); ?>
                        </div>
                    </div>
                </div>
                
                <!-- ERROR MESSAGES -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Terjadi Kesalahan!</h5>
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- FORM BODY -->
                <form method="POST" enctype="multipart/form-data" class="form-body" id="eventForm">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    
                    <!-- SECTION 1: INFORMASI DASAR -->
                    <h4 class="section-title">
                        <i class="fas fa-info-circle"></i> Informasi Dasar Event
                    </h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="mb-4">
                                <label class="form-label required">Judul Event</label>
                                <input type="text" name="judul" class="form-control form-control-lg" 
                                       value="<?php echo htmlspecialchars($event['judul'] ?? ''); ?>" 
                                       placeholder="Contoh: Seminar Technopreneurship 2025" required id="judulEvent">
                                <div class="form-text">Judul yang menarik akan meningkatkan minat peserta</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label required">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="draft" <?php echo ($event['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>📝 Draft (Tidak ditampilkan)</option>
                                    <option value="publik" <?php echo ($event['status'] ?? '') == 'publik' ? 'selected' : ''; ?>>✅ Publik (Tampilkan di website)</option>
                                    <option value="selesai" <?php echo ($event['status'] ?? '') == 'selesai' ? 'selected' : ''; ?>>🏁 Selesai</option>
                                </select>
                                <div class="form-text">Publik = tampil di website, Draft = hanya admin yang lihat</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label required">Kategori</label>
                                <select name="kategori_id" class="form-select" required id="kategoriSelect">
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php 
                                    mysqli_data_seek($kategori_list, 0);
                                    while ($kategori = mysqli_fetch_assoc($kategori_list)): 
                                    ?>
                                        <option value="<?php echo $kategori['id']; ?>" 
                                            <?php echo ($event['kategori_id'] ?? 0) == $kategori['id'] ? 'selected' : ''; ?>
                                            data-kategori-name="<?php echo strtolower(htmlspecialchars($kategori['nama'])); ?>">
                                            <?php echo htmlspecialchars($kategori['nama']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text">Pilih kategori yang sesuai dengan jenis event</div>
                                
                                <!-- KATEGORI HINT BOX -->
                                <div class="kategori-hint" id="kategoriHintBox">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <span id="kategoriHintText">Pilih kategori untuk melihat rekomendasi pengaturan</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label required">Tanggal Event</label>
                                <input type="date" name="tanggal" class="form-control" 
                                       value="<?php echo $event['tanggal'] ?? date('Y-m-d'); ?>" required id="tanggalEvent">
                                <div class="form-text">Tanggal pelaksanaan event</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Batas Pendaftaran</label>
                                <input type="date" name="batas_pendaftaran" class="form-control" 
                                       value="<?php echo $event['batas_pendaftaran'] ?? ''; ?>" 
                                       id="batasPendaftaran">
                                <div class="form-text">Kosongkan = sampai hari H</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Waktu</label>
                                <input type="time" name="waktu" class="form-control" 
                                       value="<?php echo $event['waktu'] ?? ''; ?>">
                                <div class="form-text">Waktu pelaksanaan (opsional)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Status Pendaftaran</label>
                                <div class="status-pendaftaran-box" id="statusPendaftaran">
                                    <span class="text-muted">Auto-deteksi</span>
                                </div>
                                <div class="form-text" id="hitungMundur">-</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Quick Set</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="setBatasPendaftaran(7)">
                                        1 Minggu
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="setBatasPendaftaran(3)">
                                        3 Hari
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearBatasPendaftaran()">
                                        Hapus
                                    </button>
                                </div>
                                <div class="form-text">Set otomatis sebelum event</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Hari Sebelum Event</label>
                                <input type="number" class="form-control" id="hariSebelumEvent" min="1" max="365" value="7">
                                <div class="form-text">
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="applyHariSebelum()">
                                        Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION 2: TIPE PENDAFTARAN -->
                    <h4 class="section-title">
                        <i class="fas fa-users"></i> Tipe Pendaftaran
                    </h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">Tipe Pendaftaran</label>
                                <select name="tipe_pendaftaran" class="form-select" id="tipePendaftaran">
                                    <option value="individu" <?php echo ($event['tipe_pendaftaran'] ?? 'individu') == 'individu' ? 'selected' : ''; ?>>👤 Individu (Peserta per orang)</option>
                                    <option value="tim" <?php echo ($event['tipe_pendaftaran'] ?? '') == 'tim' ? 'selected' : ''; ?>>👥 Tim (Kelompok peserta)</option>
                                    <option value="individu_tim" <?php echo ($event['tipe_pendaftaran'] ?? '') == 'individu_tim' ? 'selected' : ''; ?>>🤝 Individu atau Tim (Opsional)</option>
                                </select>
                                <div class="form-text" id="tipePendaftaranHint">Pilih sesuai jenis event</div>
                            </div>
                        </div>
                        
                        <!-- MODE BUTTONS -->
                        <div class="col-md-8">
                            <div class="mode-buttons" id="modeButtons">
                                <span class="mode-btn active" data-mode="individu_saja">Hanya Individu</span>
                                <span class="mode-btn" data-mode="wajib_tim">Wajib Tim</span>
                                <span class="mode-btn" data-mode="opsional_tim">Opsional Tim</span>
                                <span class="mode-btn" data-mode="campuran">Campuran</span>
                            </div>
                            <div class="form-text" id="modeHint"></div>
                        </div>
                    </div>
                    
                    <!-- TIM SETTINGS -->
                    <div class="tim-settings <?php echo in_array($event['tipe_pendaftaran'] ?? 'individu', ['tim', 'individu_tim']) ? 'show' : ''; ?>" id="timSettings">
                        <h5><i class="fas fa-cog me-2"></i> Pengaturan Tim</h5>
                        
                        <div class="info-box" id="timInfoBox">
                            <i class="fas fa-lightbulb me-2"></i>
                            <div id="timInfoContent">
                                <strong>Tips:</strong> Untuk event tim, atur minimal dan maksimal anggota tim.
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Minimal Anggota</label>
                                    <input type="number" name="min_anggota" class="form-control" 
                                           id="minAnggota" value="<?php echo $event['min_anggota'] ?? 1; ?>" min="1" max="50">
                                    <div class="form-text" id="minAnggotaHint">Termasuk ketua tim</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Maksimal Anggota</label>
                                    <input type="number" name="max_anggota" class="form-control" 
                                           id="maxAnggota" value="<?php echo $event['max_anggota'] ?? 1; ?>" min="1" max="50">
                                    <div class="form-text" id="maxAnggotaHint">Termasuk ketua tim</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Preset Cepat</label>
                                    <div class="preset-buttons" id="presetContainer">
                                        <span class="preset-btn" data-min="7" data-max="12" data-kategori="olahraga">Futsal</span>
                                        <span class="preset-btn" data-min="5" data-max="10" data-kategori="olahraga">Basket</span>
                                        <span class="preset-btn" data-min="2" data-max="4" data-kategori="olahraga">Badminton</span>
                                        <span class="preset-btn" data-min="2" data-max="5" data-kategori="akademik">Penelitian</span>
                                        <span class="preset-btn" data-min="1" data-max="3" data-kategori="akademik">Karya Tulis</span>
                                        <span class="preset-btn" data-min="3" data-max="8" data-kategori="seni">Band</span>
                                        <span class="preset-btn" data-min="5" data-max="15" data-kategori="seni">Tari</span>
                                        <span class="preset-btn" data-min="3" data-max="10" data-kategori="pameran">Stand Expo</span>
                                        <span class="preset-btn" data-min="1" data-max="1" data-kategori="seminar">Seminar</span>
                                        <span class="preset-btn" data-min="1" data-max="1" data-kategori="sosial">Volunteer</span>
                                    </div>
                                    <div class="form-text" id="presetHint">Klik preset untuk pengaturan cepat</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3" id="timWarning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Catatan:</strong> Peserta akan mendaftar dengan sistem tim dan harus mengisi data semua anggota.
                        </div>
                    </div>
                    
                    <!-- SECTION 3: DESKRIPSI -->
                    <h4 class="section-title">
                        <i class="fas fa-align-left"></i> Deskripsi Event
                    </h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="mb-4">
                                <label class="form-label">Deskripsi Singkat</label>
                                <textarea name="deskripsi_singkat" class="form-control" rows="3" 
                                          placeholder="Deskripsi singkat yang akan ditampilkan di halaman utama (maks. 300 karakter)"><?php echo htmlspecialchars($event['deskripsi_singkat'] ?? ''); ?></textarea>
                                <div class="form-text">Maksimal 300 karakter</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="mb-4">
                                <label class="form-label required">Deskripsi Lengkap</label>
                                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="10" required><?php echo htmlspecialchars($event['deskripsi'] ?? ''); ?></textarea>
                                <div class="form-text">Gunakan editor untuk format teks yang lebih baik</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION 4: LOKASI & KONTAK -->
                    <h4 class="section-title">
                        <i class="fas fa-map-marker-alt"></i> Lokasi & Kontak
                    </h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label required">Lokasi</label>
                                <input type="text" name="lokasi" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['lokasi'] ?? ''); ?>" 
                                       placeholder="Contoh: Auditorium Utama Kampus" required>
                                <div class="form-text">Tempat pelaksanaan event</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="alamat_lengkap" class="form-control" rows="2"
                                          placeholder="Alamat detail (opsional)"><?php echo htmlspecialchars($event['alamat_lengkap'] ?? ''); ?></textarea>
                                <div class="form-text">Alamat lengkap untuk keperluan akses</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['contact_person'] ?? ''); ?>" 
                                       placeholder="Nama penanggung jawab">
                                <div class="form-text">Nama yang bisa dihubungi</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="text" name="contact_wa" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['contact_wa'] ?? ''); ?>" 
                                       placeholder="Contoh: 081234567890">
                                <div class="form-text">Untuk informasi lebih lanjut</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION 5: PENDAFTARAN -->
                    <h4 class="section-title">
                        <i class="fas fa-user-plus"></i> Informasi Pendaftaran
                    </h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">Kuota Peserta</label>
                                <input type="number" name="kuota_peserta" class="form-control" 
                                       value="<?php echo $event['kuota_peserta'] ?? 0; ?>" min="0">
                                <div class="form-text" id="kuotaLabel">0 = tidak terbatas (per orang)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">Biaya Pendaftaran</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="biaya_pendaftaran" class="form-control" 
                                           value="<?php echo $event['biaya_pendaftaran'] ?? 0; ?>" min="0" step="1000">
                                </div>
                                <div class="form-text" id="biayaLabel">0 = gratis (per orang)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">Link Pendaftaran</label>
                                <input type="url" name="link_pendaftaran" class="form-control" 
                                       value="<?php echo htmlspecialchars($event['link_pendaftaran'] ?? ''); ?>" 
                                       placeholder="https://forms.google.com/...">
                                <div class="form-text">Kosongkan jika pakai sistem pendaftaran website</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SECTION 6: POSTER & FITUR -->
                    <h4 class="section-title">
                        <i class="fas fa-image"></i> Poster & Fitur
                    </h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">Poster Event</label>
                                <div class="preview-container" id="posterUploadArea">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="mb-2">Klik atau drag & drop gambar</p>
                                    <small class="text-muted">Format: JPG, PNG, GIF, WebP (Max 2MB)</small>
                                    <input type="file" name="poster" id="posterUpload" accept="image/*" class="d-none">
                                </div>
                                
                                <!-- Preview gambar -->
                                <img id="preview" class="preview-image w-100 mt-3" alt="Preview">
                                
                                <?php if (!empty($event['poster'])): ?>
                                    <div class="mt-3 current-image">
                                        <p class="mb-2"><strong>Poster Saat Ini:</strong></p>
                                        <img src="../<?php echo htmlspecialchars($event['poster']); ?>" 
                                             class="preview-image" alt="Poster Event">
                                        <button type="button" class="delete-image-btn" id="deleteCurrentPoster">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <input type="hidden" name="hapus_poster" id="hapusPoster" value="0">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">Fitur Tambahan</label>
                                <div class="form-card">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="featured" id="featured" 
                                               value="1" <?php echo ($event['featured'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="featured">
                                            <i class="fas fa-star text-warning me-2"></i>
                                            <strong>Tampilkan sebagai Event Unggulan</strong>
                                        </label>
                                        <div class="form-text mt-1">Event akan ditampilkan di bagian atas halaman utama</div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Info:</strong> Event dengan status <strong>Publik</strong> akan otomatis tampil di website.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BUTTONS -->
                    <div class="border-top pt-4 mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-times me-2"></i> Batal
                                </a>
                                <button type="reset" class="btn btn-outline-warning px-4">
                                    <i class="fas fa-redo me-2"></i> Reset Form
                                </button>
                            </div>
                            
                            <div>
                                <button type="submit" name="simpan_draft" value="1" class="btn btn-outline-primary px-4 me-2">
                                    <i class="fas fa-save me-2"></i> Simpan Draft
                                </button>
                                <button type="submit" class="btn btn-primary-custom px-5">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    <?php echo $mode == 'edit' ? 'Perbarui Event' : 'Simpan & Publikasikan'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Portal Informasi Kampus - Admin Panel
                    | Mode: <?php echo $mode == 'edit' ? 'Edit' : 'Tambah'; ?>
                    <?php echo $mode == 'edit' ? '| ID Event: ' . $event_id : ''; ?>
                </small>
            </footer>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-id-ID.js"></script>
    
    <script>
        // KONFIGURASI UNTUK SETIAP KATEGORI
        const kategoriConfig = {
            'akademik': {
                tipe_pendaftaran: 'individu_tim',
                min_anggota: 1,
                max_anggota: 5,
                hint: 'Kompetisi akademik bisa diikuti individu atau tim (maks 5 orang)',
                mode: 'opsional_tim',
                kuota_label: 'per tim/individu',
                biaya_label: 'per tim/individu',
                tim_info: 'Untuk lomba karya tulis, bisa individu atau kelompok penelitian',
                warning: 'Pastikan aturan jelas: individu atau kelompok?',
                recommended_presets: ['Penelitian', 'Karya Tulis']
            },
            'olahraga': {
                tipe_pendaftaran: 'tim',
                min_anggota: 2,
                max_anggota: 12,
                hint: 'Event olahraga harus mendaftar sebagai tim',
                mode: 'wajib_tim',
                kuota_label: 'per tim',
                biaya_label: 'per tim',
                tim_info: 'Event olahraga membutuhkan tim dengan jumlah anggota tertentu',
                warning: 'Pastikan jumlah anggota sesuai jenis olahraga',
                recommended_presets: ['Futsal', 'Basket', 'Badminton']
            },
            'pameran': {
                tipe_pendaftaran: 'tim',
                min_anggota: 3,
                max_anggota: 10,
                hint: 'Pameran biasanya diikuti oleh tim/kelompok untuk stand',
                mode: 'wajib_tim',
                kuota_label: 'per tim',
                biaya_label: 'per tim',
                tim_info: 'Stand pameran membutuhkan tim pengelola',
                warning: 'Siapkan data untuk tim stand pameran',
                recommended_presets: ['Stand Expo']
            },
            'seminar': {
                tipe_pendaftaran: 'individu',
                min_anggota: 1,
                max_anggota: 1,
                hint: 'Seminar dan workshop diikuti per individu',
                mode: 'individu_saja',
                kuota_label: 'per orang',
                biaya_label: 'per orang',
                tim_info: 'Pendaftaran per individu untuk kuota terbatas',
                warning: 'Biasanya per individu dengan sertifikat personal',
                recommended_presets: ['Seminar']
            },
            'seni': {
                tipe_pendaftaran: 'individu_tim',
                min_anggota: 1,
                max_anggota: 15,
                hint: 'Bisa individu (fotografi) atau tim (band, tari, teater)',
                mode: 'opsional_tim',
                kuota_label: 'per tim/individu',
                biaya_label: 'per tim/individu',
                tim_info: 'Seni pertunjukan butuh tim, seni visual bisa individu',
                warning: 'Tentukan apakah event untuk individu atau kelompok',
                recommended_presets: ['Band', 'Tari']
            },
            'sosial': {
                tipe_pendaftaran: 'individu',
                min_anggota: 1,
                max_anggota: 1,
                hint: 'Kegiatan sosial biasanya per individu (volunteer, pelatihan)',
                mode: 'individu_saja',
                kuota_label: 'per orang',
                biaya_label: 'per orang',
                tim_info: 'Pendaftaran per individu untuk kegiatan sosial',
                warning: 'Biasanya gratis dengan kuota terbatas per orang',
                recommended_presets: ['Volunteer']
            }
        };

        // FUNGSI UNTUK BATAS PENDAFTARAN
        function updateStatusPendaftaran() {
            const tanggalEvent = document.getElementById('tanggalEvent').value;
            const batasInput = document.getElementById('batasPendaftaran');
            const statusElement = document.getElementById('statusPendaftaran');
            const hitungMundur = document.getElementById('hitungMundur');
            
            if (!tanggalEvent) {
                statusElement.innerHTML = '<span class="text-muted">Isi tanggal event dulu</span>';
                hitungMundur.textContent = '-';
                return;
            }
            
            if (!batasInput.value) {
                // Jika tidak diisi, default ke tanggal event
                const eventDate = new Date(tanggalEvent);
                const today = new Date();
                
                if (eventDate < today) {
                    statusElement.innerHTML = '<span class="status-closed">⛔ Event Sudah Lewat</span>';
                    hitungMundur.textContent = 'Event sudah berlalu';
                } else {
                    statusElement.innerHTML = '<span class="status-open">✅ Pendaftaran: Sampai Hari H</span>';
                    const diffTime = eventDate - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    hitungMundur.textContent = `${diffDays} hari lagi sampai event`;
                }
                return;
            }
            
            const batasDate = new Date(batasInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            batasDate.setHours(0, 0, 0, 0);
            
            // Validasi: batas tidak boleh setelah tanggal event
            const eventDate = new Date(tanggalEvent);
            if (batasDate > eventDate) {
                statusElement.innerHTML = '<span class="status-closed">❌ Tidak Valid</span>';
                hitungMundur.textContent = 'Batas pendaftaran tidak boleh setelah tanggal event';
                return;
            }
            
            const diffTime = batasDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays < 0) {
                statusElement.innerHTML = '<span class="status-closed">⛔ Pendaftaran: Ditutup</span>';
                hitungMundur.textContent = 'Batas pendaftaran sudah lewat ' + Math.abs(diffDays) + ' hari yang lalu';
            } else if (diffDays === 0) {
                statusElement.innerHTML = '<span class="status-closing">⚠️ Pendaftaran: Tutup Hari Ini</span>';
                hitungMundur.textContent = 'Batas pendaftaran hari ini!';
            } else if (diffDays <= 3) {
                statusElement.innerHTML = '<span class="status-closing">⚠️ Pendaftaran: Segera Tutup</span>';
                hitungMundur.textContent = `${diffDays} hari lagi sampai tutup`;
            } else {
                statusElement.innerHTML = '<span class="status-open">✅ Pendaftaran: Masih Dibuka</span>';
                hitungMundur.textContent = `${diffDays} hari lagi sampai tutup`;
            }
        }
        
        // Set batas pendaftaran otomatis
        function setBatasPendaftaran(hari) {
            const tanggalEvent = document.getElementById('tanggalEvent').value;
            if (!tanggalEvent) {
                alert('Isi tanggal event terlebih dahulu');
                return;
            }
            
            const eventDate = new Date(tanggalEvent);
            eventDate.setDate(eventDate.getDate() - hari);
            const formattedDate = eventDate.toISOString().split('T')[0];
            
            document.getElementById('batasPendaftaran').value = formattedDate;
            updateStatusPendaftaran();
        }
        
        // Hapus batas pendaftaran
        function clearBatasPendaftaran() {
            document.getElementById('batasPendaftaran').value = '';
            updateStatusPendaftaran();
        }
        
        // Terapkan hari sebelum event
        function applyHariSebelum() {
            const hari = parseInt(document.getElementById('hariSebelumEvent').value);
            if (hari > 0) {
                setBatasPendaftaran(hari);
            }
        }
        
        // FUNGSI UTAMA
        function updateByKategori() {
            const kategoriSelect = document.getElementById('kategoriSelect');
            const selectedOption = kategoriSelect.options[kategoriSelect.selectedIndex];
            const kategoriNama = selectedOption.getAttribute('data-kategori-name') || '';
            
            // Cari konfigurasi
            let config = null;
            let kategoriKey = '';
            
            for (const key in kategoriConfig) {
                if (kategoriNama.includes(key)) {
                    config = kategoriConfig[key];
                    kategoriKey = key;
                    break;
                }
            }
            
            // Default config
            if (!config) {
                config = {
                    tipe_pendaftaran: 'individu',
                    min_anggota: 1,
                    max_anggota: 1,
                    hint: 'Pilih tipe pendaftaran sesuai kebutuhan event',
                    mode: 'individu_saja',
                    kuota_label: 'per orang',
                    biaya_label: 'per orang',
                    tim_info: 'Atur pengaturan sesuai kebutuhan',
                    warning: 'Pastikan pengaturan sesuai dengan jenis event',
                    recommended_presets: []
                };
            }
            
            // APPLY CONFIGURATION
            // 1. Update tipe pendaftaran
            const tipePendaftaran = document.getElementById('tipePendaftaran');
            tipePendaftaran.value = config.tipe_pendaftaran;
            
            // 2. Update hints
            document.getElementById('tipePendaftaranHint').textContent = config.hint;
            document.getElementById('kategoriHintText').textContent = config.hint;
            document.getElementById('timInfoContent').innerHTML = `<strong>Tips:</strong> ${config.tim_info}`;
            document.getElementById('timWarning').innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i><strong>Catatan:</strong> ${config.warning}`;
            
            // 3. Update labels
            document.getElementById('kuotaLabel').textContent = `0 = tidak terbatas (${config.kuota_label})`;
            document.getElementById('biayaLabel').textContent = `0 = gratis (${config.biaya_label})`;
            
            // 4. Update min/max anggota
            if (config.tipe_pendaftaran !== 'individu') {
                document.getElementById('minAnggota').value = config.min_anggota;
                document.getElementById('maxAnggota').value = config.max_anggota;
                
                if (config.mode === 'wajib_tim') {
                    document.getElementById('minAnggotaHint').textContent = `Minimal anggota wajib (tim)`;
                    document.getElementById('maxAnggotaHint').textContent = `Maksimal anggota (tim)`;
                } else {
                    document.getElementById('minAnggotaHint').textContent = `Minimal jika berkelompok`;
                    document.getElementById('maxAnggotaHint').textContent = `Maksimal jika berkelompok`;
                }
            }
            
            // 5. Update mode buttons
            const modeButtons = document.getElementById('modeButtons');
            const modeHint = document.getElementById('modeHint');
            
            if (config.mode && config.mode !== 'individu_saja') {
                modeButtons.style.display = 'flex';
                
                document.querySelectorAll('.mode-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.dataset.mode === config.mode) {
                        btn.classList.add('active');
                    }
                });
                
                const modeHints = {
                    'wajib_tim': 'Peserta WAJIB mendaftar sebagai tim (contoh: olahraga)',
                    'opsional_tim': 'Peserta bisa pilih: individu ATAU tim (contoh: lomba karya tulis)',
                    'campuran': 'Baik individu maupun tim bisa daftar (fleksibel)',
                    'individu_saja': 'Hanya individu yang bisa mendaftar'
                };
                modeHint.textContent = modeHints[config.mode] || '';
            } else {
                modeButtons.style.display = 'none';
                modeHint.textContent = '';
            }
            
            // 6. Highlight preset buttons
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.classList.remove('active', 'recommended');
                
                const presetKategori = btn.dataset.kategori;
                if (presetKategori === kategoriKey) {
                    btn.classList.add('recommended');
                }
                
                if (config.recommended_presets.includes(btn.textContent)) {
                    btn.classList.add('recommended');
                }
            });
            
            // 7. Update preset hint
            if (config.recommended_presets.length > 0) {
                document.getElementById('presetHint').textContent = 
                    `Rekomendasi: ${config.recommended_presets.join(', ')}`;
            } else {
                document.getElementById('presetHint').textContent = 'Klik preset untuk pengaturan cepat';
            }
            
            // 8. Toggle tim settings
            toggleTimSettings();
            
            // 9. Show kategori hint box
            document.getElementById('kategoriHintBox').classList.add('show');
        }
        
        // FUNGSI TOGGLE TIM SETTINGS
        function toggleTimSettings() {
            const tipe = document.getElementById('tipePendaftaran').value;
            const timSettings = document.getElementById('timSettings');
            
            if (tipe === 'tim' || tipe === 'individu_tim') {
                timSettings.classList.add('show');
            } else {
                timSettings.classList.remove('show');
            }
        }
        
        // INITIALIZE
        $(document).ready(function() {
            // Summernote Editor
            $('#deskripsi').summernote({
                height: 300,
                lang: 'id-ID',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                placeholder: 'Tulis deskripsi lengkap event di sini...'
            });
            
            // Event listeners untuk batas pendaftaran
            document.getElementById('tanggalEvent').addEventListener('change', updateStatusPendaftaran);
            document.getElementById('batasPendaftaran').addEventListener('change', updateStatusPendaftaran);
            
            // Initialize status pendaftaran
            setTimeout(updateStatusPendaftaran, 300);
            
            // Initialize kategori jika edit mode
            <?php if ($mode == 'edit' && isset($event['kategori_id'])): ?>
                setTimeout(() => {
                    updateByKategori();
                }, 100);
            <?php else: ?>
                // Untuk tambah mode, attach event listener
                document.getElementById('kategoriSelect').addEventListener('change', updateByKategori);
            <?php endif; ?>
            
            // Event listeners
            document.getElementById('tipePendaftaran').addEventListener('change', toggleTimSettings);
            
            // Preset buttons
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    document.getElementById('minAnggota').value = this.dataset.min;
                    document.getElementById('maxAnggota').value = this.dataset.max;
                    
                    if (this.dataset.min > 1) {
                        document.getElementById('tipePendaftaran').value = 'tim';
                        toggleTimSettings();
                    }
                });
            });
            
            // Mode buttons
            document.querySelectorAll('.mode-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const mode = this.dataset.mode;
                    const tipePendaftaran = document.getElementById('tipePendaftaran');
                    
                    if (mode === 'wajib_tim') {
                        tipePendaftaran.value = 'tim';
                        document.getElementById('tipePendaftaranHint').textContent = 'Peserta wajib mendaftar sebagai tim';
                    } else if (mode === 'opsional_tim') {
                        tipePendaftaran.value = 'individu_tim';
                        document.getElementById('tipePendaftaranHint').textContent = 'Peserta bisa pilih individu atau tim';
                    } else if (mode === 'campuran') {
                        tipePendaftaran.value = 'individu_tim';
                        document.getElementById('tipePendaftaranHint').textContent = 'Fleksibel: individu atau tim';
                    } else if (mode === 'individu_saja') {
                        tipePendaftaran.value = 'individu';
                        document.getElementById('tipePendaftaranHint').textContent = 'Hanya individu yang bisa mendaftar';
                    }
                    
                    toggleTimSettings();
                });
            });
            
            // Preview image upload
            const posterUploadArea = document.getElementById('posterUploadArea');
            const posterUpload = document.getElementById('posterUpload');
            const preview = document.getElementById('preview');
            
            posterUploadArea.addEventListener('click', () => posterUpload.click());
            
            posterUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                posterUploadArea.style.borderColor = '#4361ee';
                posterUploadArea.style.background = '#f0f3ff';
            });
            
            posterUploadArea.addEventListener('dragleave', () => {
                posterUploadArea.style.borderColor = '#ddd';
                posterUploadArea.style.background = '#f8f9fa';
            });
            
            posterUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                posterUploadArea.style.borderColor = '#ddd';
                posterUploadArea.style.background = '#f8f9fa';
                
                if (e.dataTransfer.files.length) {
                    posterUpload.files = e.dataTransfer.files;
                    previewImage(e.dataTransfer.files[0]);
                }
            });
            
            posterUpload.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    previewImage(this.files[0]);
                }
            });
            
            function previewImage(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    posterUploadArea.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
            
            // Hapus preview
            preview.addEventListener('click', () => {
                posterUpload.value = '';
                preview.style.display = 'none';
                posterUploadArea.style.display = 'block';
            });
            
            // Hapus poster saat ini
            const deleteCurrentPoster = document.getElementById('deleteCurrentPoster');
            const hapusPoster = document.getElementById('hapusPoster');
            
            if (deleteCurrentPoster) {
                deleteCurrentPoster.addEventListener('click', function() {
                    if (confirm('Hapus poster saat ini?')) {
                        hapusPoster.value = '1';
                        this.parentElement.style.display = 'none';
                        posterUploadArea.style.display = 'block';
                    }
                });
            }
            
            // Auto-generate deskripsi singkat
            document.getElementById('judulEvent').addEventListener('blur', function() {
                const judul = this.value.trim();
                const deskripsiSingkat = document.querySelector('textarea[name="deskripsi_singkat"]');
                
                if (judul && !deskripsiSingkat.value.trim()) {
                    deskripsiSingkat.value = `Acara ${judul} akan diselenggarakan di kampus. Jangan lewatkan kesempatan ini untuk belajar dan berjejaring!`;
                }
            });
            
            // Tombol simpan draft
            document.querySelector('button[name="simpan_draft"]').addEventListener('click', function() {
                document.querySelector('select[name="status"]').value = 'draft';
            });
            
            // Form validation
            document.getElementById('eventForm').addEventListener('submit', function(e) {
                let valid = true;
                
                // Cek required fields
                $(this).find('[required]').each(function() {
                    if (!$(this).val().trim()) {
                        valid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                // Validasi khusus untuk tim
                const tipe = document.getElementById('tipePendaftaran').value;
                if (tipe === 'tim') {
                    const min = parseInt(document.getElementById('minAnggota').value);
                    const max = parseInt(document.getElementById('maxAnggota').value);
                    
                    if (min > max) {
                        valid = false;
                        alert('Minimal anggota tidak boleh lebih besar dari maksimal anggota!');
                        document.getElementById('minAnggota').focus();
                        return false;
                    }
                    
                    if (min < 2) {
                        valid = false;
                        alert('Untuk pendaftaran tim, minimal anggota harus 2 atau lebih!');
                        document.getElementById('minAnggota').focus();
                        return false;
                    }
                }
                
                // Validasi batas pendaftaran
                const tanggalEvent = document.getElementById('tanggalEvent').value;
                const batasPendaftaran = document.getElementById('batasPendaftaran').value;
                
                if (batasPendaftaran && tanggalEvent) {
                    const eventDate = new Date(tanggalEvent);
                    const deadlineDate = new Date(batasPendaftaran);
                    
                    if (deadlineDate > eventDate) {
                        valid = false;
                        alert('❌ Batas pendaftaran tidak boleh setelah tanggal event!');
                        document.getElementById('batasPendaftaran').focus();
                        return false;
                    }
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Harap lengkapi semua field yang wajib diisi!');
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);
                }
            });
            
            // Focus judul jika tambah mode
            <?php if ($mode == 'tambah'): ?>
                setTimeout(() => {
                    document.getElementById('judulEvent').focus();
                }, 300);
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
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
    
    // ============= SISTEM TIM UNTUK SEMUA KATEGORI =============
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
    // ============= END SISTEM TIM =============
    
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
        
        if ($mode == 'tambah') {
            $sql = "INSERT INTO events (
                judul, slug, deskripsi, deskripsi_singkat, kategori_id, 
                tanggal, waktu, lokasi, alamat_lengkap, poster, 
                kuota_peserta, biaya_pendaftaran, link_pendaftaran, 
                contact_person, contact_wa, status, featured, created_by,
                tipe_pendaftaran, min_anggota, max_anggota
            ) VALUES (
                '$judul', '$slug', '$deskripsi', '$deskripsi_singkat', $kategori_id,
                '$tanggal', " . ($waktu ? "'$waktu'" : "NULL") . ", '$lokasi', '$alamat_lengkap', " . ($poster ? "'$poster'" : "NULL") . ",
                $kuota_peserta, $biaya_pendaftaran, " . ($link_pendaftaran ? "'$link_pendaftaran'" : "NULL") . ",
                " . ($contact_person ? "'$contact_person'" : "NULL") . ", " . ($contact_wa ? "'$contact_wa'" : "NULL") . ",
                '$status', $featured, $admin_id,
                '$tipe_pendaftaran', $min_anggota, $max_anggota
            )";
            
            if (mysqli_query($conn, $sql)) {
                $success = true;
                $_SESSION['alert_message'] = 'Event berhasil ditambahkan!';
                $_SESSION['alert_type'] = 'success';
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = 'Gagal menyimpan event: ' . mysqli_error($conn);
            }
            
        } else {
            $sql = "UPDATE events SET
                judul = '$judul',
                slug = '$slug',
                deskripsi = '$deskripsi',
                deskripsi_singkat = '$deskripsi_singkat',
                kategori_id = $kategori_id,
                tanggal = '$tanggal',
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
                $success = true;
                $_SESSION['alert_message'] = 'Event berhasil diperbarui!';
                $_SESSION['alert_type'] = 'success';
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = 'Gagal mengupdate event: ' . mysqli_error($conn);
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
    <title><?php echo $mode == 'edit' ? 'Edit' : 'Tambah'; ?> Event - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --light: #f8f9fa;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px 30px;
        }
        
        .form-body {
            padding: 30px;
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
        }
        
        .required:after {
            content: " *";
            color: #dc3545;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            padding: 5px;
        }
        
        .btn-submit {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
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
            min-height: 200px;
        }
        
        .alert-custom {
            border-left: 4px solid var(--primary);
            background: #f8f9fa;
        }
        
        /* ============= STYLE UNTUK TIM SETTINGS ============= */
        .tim-settings {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
            margin-top: 10px;
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
        
        .info-box-tim {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .category-hint {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .tim-presets {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .preset-btn {
            font-size: 0.8rem;
            padding: 4px 10px;
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
        
        /* Kategori-specific hints */
        .kategori-hint-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        /* Mode buttons */
        .mode-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
            display: none;
        }
        
        .mode-btn {
            font-size: 0.75rem;
            padding: 4px 12px;
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
        
        .mode-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="form-container">
            <!-- HEADER -->
            <div class="form-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="fas fa-calendar-plus me-2"></i>
                            <?php echo $mode == 'edit' ? '✏️ Edit Event' : '➕ Tambah Event Baru'; ?>
                        </h2>
                        <p class="mb-0 opacity-75">
                            <?php echo $mode == 'edit' ? 'Perbarui informasi event' : 'Isi form di bawah untuk menambahkan event baru'; ?>
                        </p>
                    </div>
                    <a href="dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
            
            <!-- ERROR MESSAGES -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mx-3 mt-3">
                    <h5><i class="fas fa-exclamation-triangle"></i> Terjadi Kesalahan:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- INFO BOX -->
            <div class="alert alert-custom mx-3 mt-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x text-primary me-3"></i>
                    <div>
                        <strong>Tips:</strong> Isi semua field yang diperlukan. Event dalam status <strong>Draft</strong> tidak akan ditampilkan di website.
                        <span id="tim-info" style="display: none;"> Untuk event <strong>tim</strong>, pastikan mengisi minimal dan maksimal anggota.</span>
                    </div>
                </div>
            </div>
            
            <!-- FORM -->
            <form method="POST" enctype="multipart/form-data" class="form-body" id="eventForm">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                
                <!-- SECTION 1: INFORMASI DASAR -->
                <h4 class="section-title">
                    <i class="fas fa-info-circle me-2"></i> Informasi Dasar Event
                </h4>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label required">Judul Event</label>
                            <input type="text" name="judul" class="form-control form-control-lg" 
                                   value="<?php echo htmlspecialchars($event['judul'] ?? ''); ?>" 
                                   placeholder="Contoh: Seminar Technopreneurship 2025" required id="judulEvent">
                            <small class="text-muted">Judul yang menarik akan meningkatkan minat peserta</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="draft" <?php echo ($event['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft (Tidak ditampilkan)</option>
                                <option value="publik" <?php echo ($event['status'] ?? '') == 'publik' ? 'selected' : ''; ?>>Publik (Tampilkan di website)</option>
                                <option value="selesai" <?php echo ($event['status'] ?? '') == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
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
                            <!-- HINT BOX UNTUK SETIAP KATEGORI -->
                            <div class="kategori-hint-box" id="kategoriHintBox">
                                <i class="fas fa-lightbulb me-2"></i>
                                <span id="kategoriHintText">Pilih kategori untuk melihat rekomendasi pengaturan</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label required">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" 
                                   value="<?php echo $event['tanggal'] ?? date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Waktu</label>
                            <input type="time" name="waktu" class="form-control" 
                                   value="<?php echo $event['waktu'] ?? ''; ?>">
                            <small class="text-muted">Kosongkan jika tidak ada waktu spesifik</small>
                        </div>
                    </div>
                </div>
                
                <!-- ============= BAGIAN BARU: TIPE PENDAFTARAN UNTUK SEMUA KATEGORI ============= -->
                <h4 class="section-title">
                    <i class="fas fa-users me-2"></i> Tipe Pendaftaran
                </h4>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tipe Pendaftaran</label>
                            <select name="tipe_pendaftaran" class="form-select" id="tipePendaftaran">
                                <option value="individu" <?php echo ($event['tipe_pendaftaran'] ?? 'individu') == 'individu' ? 'selected' : ''; ?>>Individu (Peserta per orang)</option>
                                <option value="tim" <?php echo ($event['tipe_pendaftaran'] ?? '') == 'tim' ? 'selected' : ''; ?>>Tim (Kelompok peserta)</option>
                                <option value="individu_tim" <?php echo ($event['tipe_pendaftaran'] ?? '') == 'individu_tim' ? 'selected' : ''; ?>>Individu atau Tim (Opsional)</option>
                            </select>
                            <small class="text-muted" id="tipePendaftaranHint">Pilih sesuai jenis event</small>
                        </div>
                    </div>
                    
                    <!-- MODE BUTTONS UNTUK KATEGORI TERTENTU -->
                    <div class="col-md-8">
                        <div class="mode-buttons" id="modeButtons">
                            <span class="mode-btn active" data-mode="individu_saja">Hanya Individu</span>
                            <span class="mode-btn" data-mode="wajib_tim">Wajib Tim</span>
                            <span class="mode-btn" data-mode="opsional_tim">Opsional Tim</span>
                            <span class="mode-btn" data-mode="campuran">Campuran</span>
                        </div>
                        <div class="mode-hint" id="modeHint"></div>
                    </div>
                </div>
                
                <!-- SETTINGS UNTUK TIM (Muncul jika pilih TIM atau OPSIONAL) -->
                <div class="tim-settings <?php echo in_array($event['tipe_pendaftaran'] ?? 'individu', ['tim', 'individu_tim']) ? 'show' : ''; ?>" id="timSettings">
                    <h5><i class="fas fa-cog me-2"></i> Pengaturan Tim</h5>
                    
                    <div class="info-box-tim" id="timInfoBox">
                        <i class="fas fa-lightbulb me-2"></i>
                        <div id="timInfoContent">
                            <strong>Tips:</strong> Untuk event tim, atur minimal dan maksimal anggota tim.
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Minimal Anggota</label>
                                <input type="number" name="min_anggota" class="form-control" 
                                       id="minAnggota" value="<?php echo $event['min_anggota'] ?? 1; ?>" min="1" max="50">
                                <small class="text-muted" id="minAnggotaHint">Termasuk ketua tim</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Maksimal Anggota</label>
                                <input type="number" name="max_anggota" class="form-control" 
                                       id="maxAnggota" value="<?php echo $event['max_anggota'] ?? 1; ?>" min="1" max="50">
                                <small class="text-muted" id="maxAnggotaHint">Termasuk ketua tim</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Preset Berdasarkan Kategori</label>
                                <div class="tim-presets" id="presetContainer">
                                    <!-- Preset akan dimuat otomatis berdasarkan kategori -->
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
                                <small class="category-hint" id="presetHint">Klik preset untuk pengaturan cepat</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3" id="timWarning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Catatan:</strong> Peserta akan mendaftar dengan sistem tim dan harus mengisi data semua anggota.
                    </div>
                </div>
                <!-- ============= END BAGIAN BARU ============= -->
                
                <!-- SECTION 2: DESKRIPSI -->
                <h4 class="section-title">
                    <i class="fas fa-align-left me-2"></i> Deskripsi Event
                </h4>
                
                <div class="mb-4">
                    <label class="form-label">Deskripsi Singkat</label>
                    <textarea name="deskripsi_singkat" class="form-control" rows="3" 
                              placeholder="Deskripsi singkat yang akan ditampilkan di halaman utama (maks. 300 karakter)"><?php echo htmlspecialchars($event['deskripsi_singkat'] ?? ''); ?></textarea>
                    <small class="text-muted">Maksimal 300 karakter</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label required">Deskripsi Lengkap</label>
                    <textarea name="deskripsi" id="deskripsi" class="form-control" rows="10" required><?php echo htmlspecialchars($event['deskripsi'] ?? ''); ?></textarea>
                    <small class="text-muted">Gunakan editor untuk format teks yang lebih baik</small>
                </div>
                
                <!-- SECTION 3: LOKASI & KONTAK -->
                <h4 class="section-title">
                    <i class="fas fa-map-marker-alt me-2"></i> Lokasi & Kontak
                </h4>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label required">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" 
                                   value="<?php echo htmlspecialchars($event['lokasi'] ?? ''); ?>" 
                                   placeholder="Contoh: Auditorium Utama Kampus" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat_lengkap" class="form-control" rows="2"
                                      placeholder="Alamat detail (opsional)"><?php echo htmlspecialchars($event['alamat_lengkap'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" 
                                   value="<?php echo htmlspecialchars($event['contact_person'] ?? ''); ?>" 
                                   placeholder="Nama penanggung jawab">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" name="contact_wa" class="form-control" 
                                   value="<?php echo htmlspecialchars($event['contact_wa'] ?? ''); ?>" 
                                   placeholder="Contoh: 081234567890">
                            <small class="text-muted">Untuk informasi lebih lanjut</small>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 4: PENDAFTARAN -->
                <h4 class="section-title">
                    <i class="fas fa-user-plus me-2"></i> Informasi Pendaftaran
                </h4>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Kuota Peserta</label>
                            <input type="number" name="kuota_peserta" class="form-control" 
                                   value="<?php echo $event['kuota_peserta'] ?? 0; ?>" min="0">
                            <small class="text-muted" id="kuotaLabel">0 = tidak terbatas (per orang)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Biaya Pendaftaran</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="biaya_pendaftaran" class="form-control" 
                                       value="<?php echo $event['biaya_pendaftaran'] ?? 0; ?>" min="0" step="1000">
                            </div>
                            <small class="text-muted" id="biayaLabel">0 = gratis (per orang)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Link Pendaftaran</label>
                            <input type="url" name="link_pendaftaran" class="form-control" 
                                   value="<?php echo htmlspecialchars($event['link_pendaftaran'] ?? ''); ?>" 
                                   placeholder="https://forms.google.com/...">
                            <small class="text-muted">Kosongkan jika pakai sistem pendaftaran website</small>
                        </div>
                    </div>
                </div>
                
                <!-- SECTION 5: GAMBAR & FITUR -->
                <h4 class="section-title">
                    <i class="fas fa-image me-2"></i> Poster & Fitur
                </h4>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Poster Event</label>
                            <input type="file" name="poster" class="form-control" accept="image/*" id="posterUpload">
                            <small class="text-muted">Format: JPG, PNG, GIF, WebP (Maks. 2MB)</small>
                            
                            <?php if (!empty($event['poster'])): ?>
                                <div class="mt-3">
                                    <p class="mb-1"><strong>Poster Saat Ini:</strong></p>
                                    <img src="../<?php echo htmlspecialchars($event['poster']); ?>" 
                                         class="preview-image" alt="Poster Event">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Fitur Tambahan</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="featured" id="featured" 
                                       value="1" <?php echo ($event['featured'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">
                                    <strong>Tampilkan sebagai Event Unggulan</strong>
                                </label>
                                <small class="d-block text-muted">Event akan ditampilkan di bagian atas halaman utama</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- BUTTONS -->
                <div class="border-top pt-4 mt-4">
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-times me-1"></i> Batal
                        </a>
                        
                        <button type="submit" class="btn btn-submit px-5">
                            <i class="fas fa-save me-1"></i>
                            <?php echo $mode == 'edit' ? 'Perbarui Event' : 'Simpan Event'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    
    <script>
        // KONFIGURASI UNTUK SETIAP KATEGORI
        const kategoriConfig = {
            // AKADEMIK & RISET
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
            // LOMBA OLAHRAGA
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
            // PAMERAN & EXPO
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
            // SEMINAR & WORKSHOP
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
            // SENI & BUDAYA
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
            // SOSIAL & KEMAHASISWAAN
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

        // FUNGSI UTAMA UNTUK UPDATE BERDASARKAN KATEGORI
        function updateByKategori() {
            const kategoriSelect = document.getElementById('kategoriSelect');
            const selectedOption = kategoriSelect.options[kategoriSelect.selectedIndex];
            const kategoriNama = selectedOption.getAttribute('data-kategori-name') || '';
            
            // Cari konfigurasi yang cocok
            let config = null;
            let kategoriKey = '';
            
            // Cek setiap kategori dalam config
            for (const key in kategoriConfig) {
                if (kategoriNama.includes(key)) {
                    config = kategoriConfig[key];
                    kategoriKey = key;
                    break;
                }
            }
            
            // Default jika tidak ditemukan
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
                
                // Update hints untuk anggota
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
            
            // Show/hide mode buttons
            if (config.mode && config.mode !== 'individu_saja') {
                modeButtons.style.display = 'flex';
                
                // Update active button
                document.querySelectorAll('.mode-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.dataset.mode === config.mode) {
                        btn.classList.add('active');
                    }
                });
                
                // Update mode hint
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
            
            // 6. Highlight preset buttons yang recommended
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.classList.remove('active', 'recommended');
                
                // Cek jika preset sesuai dengan kategori
                const presetKategori = btn.dataset.kategori;
                if (presetKategori === kategoriKey) {
                    btn.classList.add('recommended');
                }
                
                // Cek jika preset termasuk dalam recommended_presets
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
        }
        
        // FUNGSI TOGGLE TIM SETTINGS
        function toggleTimSettings() {
            const tipe = document.getElementById('tipePendaftaran').value;
            const timSettings = document.getElementById('timSettings');
            const timInfo = document.getElementById('tim-info');
            
            if (tipe === 'tim' || tipe === 'individu_tim') {
                timSettings.classList.add('show');
                timInfo.style.display = 'inline';
            } else {
                timSettings.classList.remove('show');
                timInfo.style.display = 'none';
            }
        }
        
        // PRESET BUTTON CLICK HANDLER
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                // Set min and max values
                document.getElementById('minAnggota').value = this.dataset.min;
                document.getElementById('maxAnggota').value = this.dataset.max;
                
                // Auto-set tipe pendaftaran jika preset untuk tim
                if (this.dataset.min > 1) {
                    document.getElementById('tipePendaftaran').value = 'tim';
                    toggleTimSettings();
                }
            });
        });
        
        // MODE BUTTONS CLICK HANDLER
        document.querySelectorAll('.mode-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all
                document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
                // Add active to clicked
                this.classList.add('active');
                
                const mode = this.dataset.mode;
                const tipePendaftaran = document.getElementById('tipePendaftaran');
                
                // Update berdasarkan mode
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
        
        // SUMMERNOTE WYSIWYG EDITOR
        $(document).ready(function() {
            $('#deskripsi').summernote({
                height: 250,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                placeholder: 'Tulis deskripsi lengkap event di sini...'
            });
            
            // INITIALIZE
            // Jika edit mode, gunakan nilai dari database
            <?php if ($mode == 'edit' && isset($event['kategori_id'])): ?>
                // Tunggu sebentar untuk memastikan DOM siap
                setTimeout(() => {
                    updateByKategori();
                }, 100);
            <?php else: ?>
                // Untuk tambah mode, attach event listener
                document.getElementById('kategoriSelect').addEventListener('change', updateByKategori);
            <?php endif; ?>
            
            // Event listeners
            document.getElementById('tipePendaftaran').addEventListener('change', toggleTimSettings);
            
            // Jika edit mode dan sudah ada nilai tim, aktifkan preset button yang sesuai
            <?php if (($event['tipe_pendaftaran'] ?? 'individu') == 'tim'): ?>
                const min = <?php echo $event['min_anggota'] ?? 1; ?>;
                const max = <?php echo $event['max_anggota'] ?? 1; ?>;
                
                // Cari preset yang sesuai
                setTimeout(() => {
                    document.querySelectorAll('.preset-btn').forEach(btn => {
                        if (parseInt(btn.dataset.min) === min && parseInt(btn.dataset.max) === max) {
                            btn.classList.add('active');
                        }
                    });
                }, 200);
            <?php endif; ?>
            
            // Preview image sebelum upload
            $('#posterUpload').change(function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('.preview-image').remove();
                        const preview = $('<img>', {
                            'class': 'preview-image mt-3',
                            'src': e.target.result,
                            'alt': 'Preview Poster'
                        });
                        $(this).parent().append(preview);
                    }.bind(this);
                    reader.readAsDataURL(file);
                }
            });
            
            // Auto-generate deskripsi singkat dari judul
            $('#judulEvent').blur(function() {
                const judul = $(this).val();
                const deskripsiSingkat = $('textarea[name="deskripsi_singkat"]');
                if (judul && !deskripsiSingkat.val()) {
                    deskripsiSingkat.val('Acara ' + judul + ' akan diselenggarakan di kampus. Jangan lewatkan kesempatan ini!');
                }
            });
            
            // Form validation
            $('#eventForm').submit(function(e) {
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
                
                if (!valid) {
                    e.preventDefault();
                    alert('Harap lengkapi semua field yang wajib diisi!');
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);
                }
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
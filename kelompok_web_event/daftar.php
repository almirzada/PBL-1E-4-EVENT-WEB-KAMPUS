<?php
session_start();
require_once 'koneksi.php';

// ================================================
// CEK PARAMETER EVENT
// ================================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: event.php");
    exit();
}

$event_id = intval($_GET['id']);

// ================================================
// AMBIL DATA EVENT
// ================================================
$event_query = "SELECT e.*, k.nama as kategori_nama, k.warna 
                FROM events e 
                LEFT JOIN kategori k ON e.kategori_id = k.id 
                WHERE e.id = $event_id AND e.status = 'publik'";

$event_result = mysqli_query($conn, $event_query);

if (mysqli_num_rows($event_result) == 0) {
    header("Location: event.php");
    exit();
}

$event = mysqli_fetch_assoc($event_result);

// ================================================
// CEK STATUS PENDAFTARAN
// ================================================
$today = date('Y-m-d');
$tanggal_event = $event['tanggal'];
$kuota = $event['kuota_peserta'];
$biaya = $event['biaya_pendaftaran'];
$tipe = $event['tipe_pendaftaran'];
$min_anggota = $event['min_anggota'];
$max_anggota = $event['max_anggota'];
$berbayar = $biaya > 0;

// Cek apakah event sudah lewat
$event_passed = strtotime($tanggal_event) < strtotime($today);

// Cek kuota jika ada
$registered_count = 0;
$is_full = false;

if ($kuota > 0) {
    if ($tipe == 'tim') {
        // Hitung jumlah tim yang terdaftar
        $count_query = "SELECT COUNT(DISTINCT tim_id) as total FROM peserta WHERE event_id = $event_id";
    } else {
        // Hitung jumlah individu yang terdaftar
        $count_query = "SELECT COUNT(*) as total FROM peserta WHERE event_id = $event_id";
    }
    
    $count_result = mysqli_query($conn, $count_query);
    $count_row = mysqli_fetch_assoc($count_result);
    $registered_count = $count_row['total'];
    $remaining = $kuota - $registered_count;
    $is_full = $remaining <= 0;
}

// ================================================
// PROSES PENDAFTARAN
// ================================================
$errors = array();
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dasar dengan cek isset()
    $nama_tim = isset($_POST['nama_tim']) ? mysqli_real_escape_string($conn, $_POST['nama_tim']) : '';
    $tipe_daftar = isset($_POST['tipe_daftar']) ? $_POST['tipe_daftar'] : 'individu';
    $jumlah_anggota = isset($_POST['jumlah_anggota']) ? intval($_POST['jumlah_anggota']) : 1;
    
    // Validasi berdasarkan tipe
    if ($tipe == 'tim' && $tipe_daftar != 'tim') {
        $errors[] = 'Event ini wajib mendaftar sebagai tim!';
    }
    
    if ($tipe == 'individu' && $tipe_daftar != 'individu') {
        $errors[] = 'Event ini hanya untuk pendaftaran individu!';
    }
    
    // Validasi jumlah anggota untuk tim
    if ($tipe_daftar == 'tim') {
        if (empty($nama_tim)) {
            $errors[] = 'Nama tim harus diisi!';
        }
        
        if ($jumlah_anggota < $min_anggota) {
            $errors[] = "Minimal anggota tim adalah $min_anggota orang!";
        }
        
        if ($jumlah_anggota > $max_anggota) {
            $errors[] = "Maksimal anggota tim adalah $max_anggota orang!";
        }
    }
    
    // Cek kuota
    if ($is_full) {
        $errors[] = 'Maaf, kuota pendaftaran sudah penuh!';
    }
    
    // Validasi upload bukti pembayaran jika berbayar
    $bukti_pembayaran = null;
    if ($berbayar) {
        if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == UPLOAD_ERR_OK) {
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
            $file_name = $_FILES['bukti_pembayaran']['name'];
            $file_tmp = $_FILES['bukti_pembayaran']['tmp_name'];
            $file_size = $_FILES['bukti_pembayaran']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validasi ekstensi file
            if (!in_array($file_ext, $allowed_extensions)) {
                $errors[] = 'Format file bukti pembayaran tidak valid! Hanya JPG, JPEG, PNG, GIF, dan PDF yang diperbolehkan.';
            }
            
            // Validasi ukuran file (max 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran file bukti pembayaran terlalu besar! Maksimal 5MB.';
            }
            
            // Generate nama file unik
            if (empty($errors)) {
                $bukti_pembayaran = 'PAY-' . date('YmdHis') . '-' . uniqid() . '.' . $file_ext;
                $upload_dir = 'uploads/bukti_pembayaran/';
                
                // Buat folder jika belum ada
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . $bukti_pembayaran;
                
                // Pindahkan file
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Gagal mengupload bukti pembayaran.';
                    $bukti_pembayaran = null;
                }
            }
        } else {
            $errors[] = 'Bukti pembayaran wajib diupload untuk event berbayar!';
        }
    }
    
    // Jika tidak ada error, proses pendaftaran
    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        
        try {
            // Generate ID unik
            $kode_pendaftaran = 'REG-' . strtoupper(substr($event['slug'], 0, 3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
            
            if ($tipe_daftar == 'tim') {
                // Simpan data tim terlebih dahulu
                $status_pembayaran = $bukti_pembayaran ? 'menunggu_verifikasi' : 'gratis';
                $tim_query = "INSERT INTO tim_event (event_id, nama_tim, kode_pendaftaran, jumlah_anggota, bukti_pembayaran, status_pembayaran, created_at) 
                              VALUES ($event_id, '$nama_tim', '$kode_pendaftaran', $jumlah_anggota, '$bukti_pembayaran', '$status_pembayaran', NOW())";
                
                if (mysqli_query($conn, $tim_query)) {
                    $tim_id = mysqli_insert_id($conn);
                    
                    // Simpan data ketua tim (anggota pertama)
                    $nama_ketua = isset($_POST['nama'][0]) ? mysqli_real_escape_string($conn, $_POST['nama'][0]) : '';
                    $npm_ketua = isset($_POST['npm'][0]) ? mysqli_real_escape_string($conn, $_POST['npm'][0]) : '';
                    $email_ketua = isset($_POST['email'][0]) ? mysqli_real_escape_string($conn, $_POST['email'][0]) : '';
                    $wa_ketua = isset($_POST['wa'][0]) ? mysqli_real_escape_string($conn, $_POST['wa'][0]) : '';
                    $jurusan_ketua = isset($_POST['jurusan'][0]) ? mysqli_real_escape_string($conn, $_POST['jurusan'][0]) : '';
                    
                    $ketua_query = "INSERT INTO peserta (event_id, tim_id, nama, npm, email, no_wa, jurusan, status_anggota, created_at) 
                                    VALUES ($event_id, $tim_id, '$nama_ketua', '$npm_ketua', '$email_ketua', '$wa_ketua', '$jurusan_ketua', 'ketua', NOW())";
                    
                    if (!mysqli_query($conn, $ketua_query)) {
                        throw new Exception('Gagal menyimpan data ketua tim: ' . mysqli_error($conn));
                    }
                    
                    // Simpan data anggota lainnya
                    for ($i = 1; $i < $jumlah_anggota; $i++) {
                        if (isset($_POST['nama'][$i]) && !empty($_POST['nama'][$i]) && isset($_POST['npm'][$i]) && !empty($_POST['npm'][$i])) {
                            $nama = mysqli_real_escape_string($conn, $_POST['nama'][$i]);
                            $npm = mysqli_real_escape_string($conn, $_POST['npm'][$i]);
                            $email = isset($_POST['email'][$i]) ? mysqli_real_escape_string($conn, $_POST['email'][$i]) : '';
                            $wa = isset($_POST['wa'][$i]) ? mysqli_real_escape_string($conn, $_POST['wa'][$i]) : '';
                            $jurusan = isset($_POST['jurusan'][$i]) ? mysqli_real_escape_string($conn, $_POST['jurusan'][$i]) : '';
                            
                            $anggota_query = "INSERT INTO peserta (event_id, tim_id, nama, npm, email, no_wa, jurusan, status_anggota, created_at) 
                                              VALUES ($event_id, $tim_id, '$nama', '$npm', '$email', '$wa', '$jurusan', 'anggota', NOW())";
                            
                            if (!mysqli_query($conn, $anggota_query)) {
                                throw new Exception('Gagal menyimpan data anggota: ' . mysqli_error($conn));
                            }
                        }
                    }
                } else {
                    throw new Exception('Gagal menyimpan data tim: ' . mysqli_error($conn));
                }
                
            } else {
                // Pendaftaran individu
                $nama = isset($_POST['nama'][0]) ? mysqli_real_escape_string($conn, $_POST['nama'][0]) : '';
                $npm = isset($_POST['npm'][0]) ? mysqli_real_escape_string($conn, $_POST['npm'][0]) : '';
                $email = isset($_POST['email'][0]) ? mysqli_real_escape_string($conn, $_POST['email'][0]) : '';
                $wa = isset($_POST['wa'][0]) ? mysqli_real_escape_string($conn, $_POST['wa'][0]) : '';
                $jurusan = isset($_POST['jurusan'][0]) ? mysqli_real_escape_string($conn, $_POST['jurusan'][0]) : '';
                
                $kode_pendaftaran = 'IND-' . strtoupper(substr($event['slug'], 0, 3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
                
                $status_pembayaran = $bukti_pembayaran ? 'menunggu_verifikasi' : 'gratis';
                $individu_query = "INSERT INTO peserta (event_id, nama, npm, email, no_wa, jurusan, kode_pendaftaran, bukti_pembayaran, status_pembayaran, status_anggota, created_at) 
                                   VALUES ($event_id, '$nama', '$npm', '$email', '$wa', '$jurusan', '$kode_pendaftaran', '$bukti_pembayaran', '$status_pembayaran', 'individu', NOW())";
                
                if (!mysqli_query($conn, $individu_query)) {
                    throw new Exception('Gagal menyimpan data individu: ' . mysqli_error($conn));
                }
            }
            
            // Update jumlah pendaftar di event
            $update_query = "UPDATE events SET total_pendaftar = total_pendaftar + 1 WHERE id = $event_id";
            mysqli_query($conn, $update_query);
            
            mysqli_commit($conn);
            $success = true;
            
            // Simpan kode pendaftaran di session untuk halaman sukses
            $_SESSION['kode_pendaftaran'] = $kode_pendaftaran;
            $_SESSION['event_id'] = $event_id;
            $_SESSION['bukti_pembayaran'] = $bukti_pembayaran;
            
            // Redirect ke halaman sukses
            header("Location: pendaftaran_sukses.php?code=" . $kode_pendaftaran);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
            
            // Hapus file yang sudah diupload jika ada error
            if ($bukti_pembayaran && isset($upload_dir) && file_exists($upload_dir . $bukti_pembayaran)) {
                unlink($upload_dir . $bukti_pembayaran);
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
    <title>Pendaftaran - <?php echo htmlspecialchars($event['judul']); ?></title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding-bottom: 50px;
        }
        
        /* HEADER */
        .header-event {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .event-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        /* FORM CONTAINER */
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary) 0%, var(--secondary) 100%);
        }
        
        /* TIPE SELECTION */
        .tipe-selection {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .tipe-card {
            flex: 1;
            min-width: 200px;
            border: 2px solid #dee2e6;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .tipe-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.1);
        }
        
        .tipe-card.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05) 0%, rgba(58, 12, 163, 0.05) 100%);
        }
        
        .tipe-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        /* FORM SECTIONS */
        .form-section {
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 2px dashed #eee;
        }
        
        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* ANGGOTA CARD */
        .anggota-card {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .anggota-card:hover {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .anggota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .anggota-label {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .badge-ketua {
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* BUTTONS */
        .btn-add {
            background: var(--success);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-remove {
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-remove:hover {
            background: #e31c5f;
            transform: translateY(-2px);
        }
        
        .btn-submit {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        /* INFO BOX */
        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        /* ERROR & SUCCESS */
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* COUNTER */
        .anggota-counter {
            background: var(--primary);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            position: absolute;
            top: -15px;
            right: -15px;
            z-index: 1;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
            }
            
            .tipe-selection {
                flex-direction: column;
            }
            
            .header-event {
                padding: 30px 0;
            }
        }
        
        /* FEE BOX */
        .fee-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        /* QUOTA STATUS */
        .quota-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            flex-grow: 1;
        }
        
        /* BUKTI PEMBAYARAN STYLE */
        .upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background: #e8f4ff;
        }
        
        .upload-area.dragover {
            border-color: var(--success);
            background: #e8fff0;
        }
        
        .upload-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .preview-container {
            position: relative;
            margin-top: 20px;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-remove-file {
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--danger);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .info-rekening {
            background: linear-gradient(135deg, #e6f7ff 0%, #b3e0ff 100%);
            border-left: 4px solid #1890ff;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .rekening-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .rekening-item:last-child {
            border-bottom: none;
        }
        
        .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: var(--secondary);
        }
        
        .payment-instruction {
            background: #fff8e1;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .file-info {
            background: #f1f8e9;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <!-- HEADER EVENT -->
    <div class="header-event">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="event-badge">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo $event['kategori_nama']; ?>
                    </span>
                    <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($event['judul']); ?></h1>
                    <p class="lead mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars($event['lokasi']); ?>
                        •
                        <i class="fas fa-clock me-2"></i>
                        <?php echo date('d F Y', strtotime($event['tanggal'])); ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="detail_event.php?id=<?php echo $event_id; ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Detail
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- NOTIFIKASI -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
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
        
        <!-- INFO BOX -->
        <div class="info-box">
            <div class="row">
                <div class="col-md-8">
                    <h5><i class="fas fa-info-circle me-2"></i>Informasi Pendaftaran</h5>
                    <p class="mb-2">
                        <strong>Status:</strong> 
                        <?php if ($event_passed): ?>
                            <span class="badge bg-secondary">Event sudah berlalu</span>
                        <?php elseif ($is_full): ?>
                            <span class="badge bg-danger">Kuota penuh</span>
                        <?php else: ?>
                            <span class="badge bg-success">Pendaftaran dibuka</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($kuota > 0): ?>
                    <div class="quota-status">
                        <span><strong>Kuota:</strong> <?php echo $remaining ?? $kuota; ?> tersisa dari <?php echo $kuota; ?></span>
                        <div class="progress">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo min(100, ($registered_count/$kuota)*100); ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($berbayar): ?>
                <div class="col-md-4">
                    <div class="fee-box">
                        <h5><i class="fas fa-money-bill-wave me-2"></i>Biaya Pendaftaran</h5>
                        <h3 class="text-warning mb-0">Rp <?php echo number_format($biaya, 0, ',', '.'); ?></h3>
                        <small class="text-muted"><?php echo $tipe == 'tim' ? 'per tim' : 'per orang'; ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- CEK APAKAH MASIH BISA DAFTAR -->
        <?php if ($event_passed): ?>
            <div class="alert alert-warning text-center py-4">
                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                <h4>Pendaftaran Ditutup</h4>
                <p class="mb-0">Maaf, pendaftaran untuk event ini sudah ditutup karena event sudah berlalu.</p>
            </div>
        <?php elseif ($is_full): ?>
            <div class="alert alert-danger text-center py-4">
                <i class="fas fa-users-slash fa-3x mb-3"></i>
                <h4>Kuota Penuh</h4>
                <p class="mb-0">Maaf, kuota pendaftaran untuk event ini sudah penuh.</p>
            </div>
        <?php else: ?>
            <!-- FORM CONTAINER -->
            <div class="form-container">
                <form method="POST" id="pendaftaranForm" enctype="multipart/form-data">
                    
                    <!-- PILIH TIPE PENDAFTARAN -->
                    <?php if ($tipe == 'individu_tim'): ?>
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-users"></i> Tipe Pendaftaran
                        </h4>
                        
                        <div class="tipe-selection">
                            <div class="tipe-card <?php echo (isset($_POST['tipe_daftar']) && $_POST['tipe_daftar'] == 'individu') ? 'active' : ''; ?>" 
                                 data-tipe="individu" onclick="selectTipe('individu')">
                                <div class="tipe-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4>Individu</h4>
                                <p class="text-muted">Daftar sendiri tanpa tim</p>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipe_daftar" 
                                           value="individu" id="tipeIndividu" 
                                           <?php echo ($_POST['tipe_daftar'] ?? 'individu') == 'individu' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tipeIndividu">
                                        Pilih Individu
                                    </label>
                                </div>
                            </div>
                            
                            <div class="tipe-card <?php echo ($_POST['tipe_daftar'] ?? '') == 'tim' ? 'active' : ''; ?>" 
                                 data-tipe="tim" onclick="selectTipe('tim')">
                                <div class="tipe-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h4>Tim</h4>
                                <p class="text-muted">Daftar sebagai tim</p>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipe_daftar" 
                                           value="tim" id="tipeTim" 
                                           <?php echo ($_POST['tipe_daftar'] ?? '') == 'tim' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tipeTim">
                                        Pilih Tim
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-box" id="tipeInfo">
                            <i class="fas fa-lightbulb me-2"></i>
                            <span id="tipeInfoText">
                                <?php if (($tipe == 'individu') || ($_POST['tipe_daftar'] ?? 'individu') == 'individu'): ?>
                                    Anda akan mendaftar sebagai individu.
                                <?php else: ?>
                                    Anda akan mendaftar sebagai tim. Minimal <?php echo $min_anggota; ?> orang, maksimal <?php echo $max_anggota; ?> orang.
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="tipe_daftar" value="<?php echo $tipe; ?>">
                    <?php endif; ?>
                    
                    <!-- NAMA TIM (Hanya untuk tim) -->
                    <div class="form-section" id="timSection" style="<?php echo ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim') ? '' : 'display: none;'; ?>">
                        <h4 class="section-title">
                            <i class="fas fa-flag"></i> Data Tim
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Tim <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_tim" class="form-control" 
                                           value="<?php echo isset($_POST['nama_tim']) ? htmlspecialchars($_POST['nama_tim']) : ''; ?>" 
                                           placeholder="Contoh: Tim Jaya Makmur" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Anggota <span class="text-danger">*</span></label>
                                    <select name="jumlah_anggota" class="form-select" id="jumlahAnggota" required>
                                        <option value="">Pilih jumlah anggota</option>
                                        <?php for ($i = $min_anggota; $i <= $max_anggota; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                            <?php echo ($_POST['jumlah_anggota'] ?? $min_anggota) == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> orang
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="form-text">
                                        Minimal <?php echo $min_anggota; ?> orang, maksimal <?php echo $max_anggota; ?> orang
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- DATA ANGGOTA -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-user-friends"></i> 
                            <span id="anggotaTitle">
                                <?php echo ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim') ? 'Data Anggota Tim' : 'Data Diri'; ?>
                            </span>
                        </h4>
                        
                        <div id="anggotaContainer">
                            <!-- Anggota akan ditambahkan dinamis di sini -->
                            <?php
                            $jumlah_anggota = $_POST['jumlah_anggota'] ?? 1;
                            $current_members = max(1, $jumlah_anggota);
                            
                            for ($i = 0; $i < $current_members; $i++):
                            ?>
                            <div class="anggota-card" data-index="<?php echo $i; ?>">
                                <?php if ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim'): ?>
                                <div class="anggota-header">
                                    <span class="anggota-label">Anggota <?php echo $i + 1; ?></span>
                                    <?php if ($i == 0): ?>
                                        <span class="badge-ketua">Ketua Tim</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="nama[]" class="form-control" 
                                                   value="<?php echo $_POST['nama'][$i] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">NIM<span class="text-danger">*</span></label>
                                            <input type="text" name="npm[]" class="form-control" 
                                                   value="<?php echo $_POST['npm'][$i] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email[]" class="form-control" 
                                                   value="<?php echo $_POST['email'][$i] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                            <input type="tel" name="wa[]" class="form-control" 
                                                   value="<?php echo $_POST['wa'][$i] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Jurusan/Fakultas</label>
                                            <input type="text" name="jurusan[]" class="form-control" 
                                                   value="<?php echo $_POST['jurusan'][$i] ?? ''; ?>" 
                                                   placeholder="Contoh: Teknik Informatika">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim'): ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-add" id="tambahAnggota">
                                <i class="fas fa-plus me-2"></i>Tambah Anggota
                            </button>
                            <button type="button" class="btn btn-remove" id="hapusAnggota">
                                <i class="fas fa-minus me-2"></i>Hapus Anggota
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- BUKTI PEMBAYARAN (Hanya untuk event berbayar) -->
                    <?php if ($berbayar): ?>
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-file-invoice-dollar"></i> Bukti Pembayaran
                        </h4>
                        
                        <!-- INFO REKENING -->
                        <div class="info-rekening">
                            <h5><i class="fas fa-university me-2"></i>Transfer ke Rekening Berikut:</h5>
                            
                            <div class="rekening-item">
                                <div>
                                    <strong>Bank Mandiri</strong>
                                    <div class="text-muted">Kantor Cabang Utama</div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rekening-number">1234-5678-9012-3456</span>
                                        <button type="button" class="copy-btn" data-number="1234567890123456">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted">a.n. Panitia Event Kampus</div>
                                </div>
                            </div>
                            
                            <div class="rekening-item">
                                <div>
                                    <strong>Bank BCA</strong>
                                    <div class="text-muted">Kantor Cabang Utama</div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rekening-number">8211049634</span>
                                        <button type="button" class="copy-btn" data-number="8211049634">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="text-muted">Reyvandito Bassam C</div>
                                </div>
                            </div>
                            
                            <div class="payment-instruction">
                                <h6><i class="fas fa-lightbulb me-2"></i>Petunjuk Pembayaran:</h6>
                                <ol class="mb-0">
                                    <li>Transfer sesuai nominal: <strong>Rp <?php echo number_format($biaya, 0, ',', '.'); ?></strong></li>
                                    <li>Tambah angka unik: <strong><?php echo rand(1, 999); ?></strong> untuk memudahkan verifikasi</li>
                                    <li>Upload bukti transfer dengan format JPG, PNG, atau PDF (maks. 5MB)</li>
                                    <li>Pastikan bukti transfer terbaca dengan jelas</li>
                                </ol>
                            </div>
                        </div>
                        
                        <!-- UPLOAD AREA -->
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h5>Upload Bukti Pembayaran</h5>
                            <p class="text-muted">Drag & drop file atau klik untuk memilih</p>
                            <p class="text-muted mb-3">Format: JPG, PNG, PDF | Maks: 5MB</p>
                            
                            <input type="file" name="bukti_pembayaran" id="buktiPembayaran" 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf" hidden required>
                            
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('buktiPembayaran').click()">
                                <i class="fas fa-folder-open me-2"></i>Pilih File
                            </button>
                        </div>
                        
                        <!-- PREVIEW -->
                        <div class="preview-container" id="previewContainer" style="display: none;">
                            <button type="button" class="btn-remove-file" id="removeFile">
                                <i class="fas fa-times"></i>
                            </button>
                            <img src="" alt="Preview" class="preview-image" id="previewImage">
                            <div id="fileInfo" class="file-info mt-2"></div>
                        </div>
                        
                        <!-- FILE INFO -->
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Penting:</strong> Pendaftaran Anda akan diproses setelah bukti pembayaran diverifikasi oleh panitia. 
                            Proses verifikasi maksimal 1x24 jam.
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- KONFIRMASI -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-check-circle"></i> Konfirmasi
                        </h4>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                Saya menyetujui syarat dan ketentuan yang berlaku. Data yang saya berikan adalah benar dan dapat dipertanggungjawabkan.
                            </label>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?php echo $tipe == 'tim' ? 'Daftarkan Tim' : 'Daftar Sekarang'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- FOOTER -->
        <footer class="text-center text-muted mt-5">
            <hr>
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> Sistem Pendaftaran Event Kampus
                | <a href="detail_event.php?id=<?php echo $event_id; ?>" class="text-decoration-none">Kembali ke Detail Event</a>
            </p>
        </footer>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // KONFIGURASI
        const minAnggota = <?php echo $min_anggota; ?>;
        const maxAnggota = <?php echo $max_anggota; ?>;
        const tipeEvent = "<?php echo $tipe; ?>";
        const berbayar = <?php echo $berbayar ? 'true' : 'false'; ?>;
        
        // VARIABLES
        let currentAnggotaCount = <?php echo $current_members ?? 1; ?>;
        
        // FUNGSI SELECT TIPE (hanya untuk individu_tim)
        function selectTipe(tipe) {
            document.querySelectorAll('.tipe-card').forEach(card => {
                card.classList.remove('active');
            });
            
            document.querySelector(`[data-tipe="${tipe}"]`).classList.add('active');
            document.getElementById(`tipe${tipe.charAt(0).toUpperCase() + tipe.slice(1)}`).checked = true;
            
            // Update info
            const infoText = document.getElementById('tipeInfoText');
            const timSection = document.getElementById('timSection');
            const anggotaTitle = document.getElementById('anggotaTitle');
            
            if (tipe === 'individu') {
                infoText.textContent = 'Anda akan mendaftar sebagai individu.';
                timSection.style.display = 'none';
                anggotaTitle.textContent = 'Data Diri';
                currentAnggotaCount = 1;
                updateAnggotaCards();
            } else {
                infoText.textContent = `Anda akan mendaftar sebagai tim. Minimal ${minAnggota} orang, maksimal ${maxAnggota} orang.`;
                timSection.style.display = 'block';
                anggotaTitle.textContent = 'Data Anggota Tim';
                currentAnggotaCount = minAnggota;
                updateAnggotaCards();
            }
        }
        
        // FUNGSI UPDATE JUMLAH ANGGOTA
        function updateAnggotaCards() {
            const container = document.getElementById('anggotaContainer');
            const jumlahSelect = document.getElementById('jumlahAnggota');
            
            // Update select jika ada
            if (jumlahSelect) {
                jumlahSelect.value = currentAnggotaCount;
            }
            
            // Update tampilan kartu
            container.innerHTML = '';
            
            for (let i = 0; i < currentAnggotaCount; i++) {
                const isKetua = i === 0;
                const anggotaHTML = `
                    <div class="anggota-card" data-index="${i}">
                        ${tipeEvent === 'tim' || document.querySelector('input[name="tipe_daftar"]:checked')?.value === 'tim' ? `
                        <div class="anggota-header">
                            <span class="anggota-label">Anggota ${i + 1}</span>
                            ${isKetua ? '<span class="badge-ketua">Ketua Tim</span>' : ''}
                        </div>
                        ` : ''}
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama[]" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">NPM <span class="text-danger">*</span></label>
                                    <input type="text" name="npm[]" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email[]" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                    <input type="tel" name="wa[]" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Jurusan/Fakultas</label>
                                    <input type="text" name="jurusan[]" class="form-control" placeholder="Contoh: Teknik Informatika">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', anggotaHTML);
            }
        }
        
        // FUNGSI TAMBAH ANGGOTA
        document.getElementById('tambahAnggota')?.addEventListener('click', function() {
            if (currentAnggotaCount < maxAnggota) {
                currentAnggotaCount++;
                updateAnggotaCards();
            } else {
                alert(`Maksimal anggota tim adalah ${maxAnggota} orang.`);
            }
        });
        
        // FUNGSI HAPUS ANGGOTA
        document.getElementById('hapusAnggota')?.addEventListener('click', function() {
            if (currentAnggotaCount > minAnggota) {
                currentAnggotaCount--;
                updateAnggotaCards();
            } else {
                alert(`Minimal anggota tim adalah ${minAnggota} orang.`);
            }
        });
        
        // CHANGE JUMLAH ANGGOTA DARI SELECT
        document.getElementById('jumlahAnggota')?.addEventListener('change', function() {
            const selectedValue = parseInt(this.value);
            if (selectedValue >= minAnggota && selectedValue <= maxAnggota) {
                currentAnggotaCount = selectedValue;
                updateAnggotaCards();
            }
        });
        
        // UPLOAD BUKTI PEMBAYARAN
        const uploadArea = document.getElementById('uploadArea');
        const buktiPembayaran = document.getElementById('buktiPembayaran');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const fileInfo = document.getElementById('fileInfo');
        const removeFile = document.getElementById('removeFile');
        
        if (uploadArea) {
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0]);
                }
            });
            
            // Click to select
            uploadArea.addEventListener('click', () => {
                buktiPembayaran.click();
            });
            
            // File input change
            buktiPembayaran.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFile(e.target.files[0]);
                }
            });
            
            // Remove file
            removeFile?.addEventListener('click', () => {
                buktiPembayaran.value = '';
                previewContainer.style.display = 'none';
                uploadArea.style.display = 'flex';
            });
        }
        
        function handleFile(file) {
            // Validasi file
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung! Hanya JPG, PNG, GIF, dan PDF yang diperbolehkan.');
                return;
            }
            
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar! Maksimal 5MB.');
                return;
            }
            
            // Show preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                    uploadArea.style.display = 'none';
                    
                    // Update file info
                    fileInfo.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${file.name}</strong><br>
                                <small>${(file.size / 1024).toFixed(2)} KB • ${file.type}</small>
                            </div>
                            <div>
                                <span class="badge bg-success">Valid</span>
                            </div>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                // For PDF files
                previewImage.src = 'https://cdn-icons-png.flaticon.com/512/337/337946.png';
                previewContainer.style.display = 'block';
                uploadArea.style.display = 'none';
                
                fileInfo.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${file.name}</strong><br>
                            <small>${(file.size / 1024).toFixed(2)} KB • PDF Document</small>
                        </div>
                        <div>
                            <span class="badge bg-success">Valid</span>
                        </div>
                    </div>
                `;
            }
        }
        
        // COPY REKENING NUMBER
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', function() {
                const number = this.getAttribute('data-number');
                navigator.clipboard.writeText(number).then(() => {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    this.style.background = '#28a745';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.background = '';
                    }, 2000);
                });
            });
        });
        
        // FORM VALIDATION
        document.getElementById('pendaftaranForm')?.addEventListener('submit', function(e) {
            // Validasi checkbox
            const agreeCheckbox = document.getElementById('agreeTerms');
            if (!agreeCheckbox.checked) {
                e.preventDefault();
                alert('Anda harus menyetujui syarat dan ketentuan terlebih dahulu.');
                agreeCheckbox.focus();
                return false;
            }
            
            // Validasi duplikat NPM
            const npmInputs = document.querySelectorAll('input[name="npm[]"]');
            const npmValues = [];
            let hasDuplicate = false;
            
            npmInputs.forEach(input => {
                const value = input.value.trim();
                if (value) {
                    if (npmValues.includes(value)) {
                        hasDuplicate = true;
                        input.classList.add('is-invalid');
                    } else {
                        npmValues.push(value);
                        input.classList.remove('is-invalid');
                    }
                }
            });
            
            if (hasDuplicate) {
                e.preventDefault();
                alert('Terdapat NPM yang sama pada anggota tim. Setiap anggota harus memiliki NPM yang unik.');
                return false;
            }
            
            // Validasi email duplikat
            const emailInputs = document.querySelectorAll('input[name="email[]"]');
            const emailValues = [];
            let hasDuplicateEmail = false;
            
            emailInputs.forEach(input => {
                const value = input.value.trim();
                if (value) {
                    if (emailValues.includes(value)) {
                        hasDuplicateEmail = true;
                        input.classList.add('is-invalid');
                    } else {
                        emailValues.push(value);
                        input.classList.remove('is-invalid');
                    }
                }
            });
            
            if (hasDuplicateEmail) {
                e.preventDefault();
                alert('Terdapat email yang sama pada anggota tim. Setiap anggota harus memiliki email yang unik.');
                return false;
            }
            
            // Validasi bukti pembayaran untuk event berbayar
            if (berbayar) {
                const buktiFile = document.getElementById('buktiPembayaran');
                if (buktiFile && !buktiFile.files[0]) {
                    e.preventDefault();
                    alert('Harap upload bukti pembayaran terlebih dahulu!');
                    uploadArea.style.borderColor = 'var(--danger)';
                    uploadArea.scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
            }
            
            // Confirmation
            const tipeDaftar = document.querySelector('input[name="tipe_daftar"]:checked')?.value || tipeEvent;
            const message = tipeDaftar === 'tim' 
                ? `Apakah Anda yakin ingin mendaftarkan tim dengan ${currentAnggotaCount} anggota?`
                : 'Apakah Anda yakin ingin mendaftarkan diri Anda?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // AUTO-FORMAT WHATSAPP NUMBER
        document.addEventListener('input', function(e) {
            if (e.target.name === 'wa[]') {
                let value = e.target.value.replace(/\D/g, '');
                
                // Add +62 prefix if starts with 0 or 8
                if (value.startsWith('0')) {
                    value = '62' + value.substring(1);
                } else if (value.startsWith('8')) {
                    value = '62' + value;
                }
                
                e.target.value = value;
            }
        });
        
        // INITIALIZE
        $(document).ready(function() {
            // Auto fill jika ada session sebelumnya
            const previousData = <?php echo json_encode(isset($_POST) ? $_POST : []); ?>;
            
            if (Object.keys(previousData).length > 0) {
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#pendaftaranForm').offset().top - 100
                }, 500);
            }
            
            // Focus pertama input
            setTimeout(() => {
                const firstInput = document.querySelector('input[name="nama[]"]');
                if (firstInput) firstInput.focus();
            }, 300);
        });
    </script>
</body>
</html>

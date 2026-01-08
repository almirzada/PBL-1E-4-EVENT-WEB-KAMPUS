<?php
require_once 'koneksi.php';
session_start();

// ============================================
// 1. VALIDASI EVENT & AMBIL DARI DATABASE LANGSUNG
// ============================================
// HAPUS debugging yang membuat tampilan jelek
error_reporting(0); // Nonaktifkan error display

// Jika tidak ada event_id, ambil event pertama dari database
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    // Ambil event terbaru atau event pertama yang aktif
    $query = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon 
              FROM events e 
              LEFT JOIN kategori k ON e.kategori_id = k.id 
              WHERE e.status = 'aktif' 
              ORDER BY e.tanggal DESC 
              LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 0) {
        // Jika tidak ada event sama sekali, redirect ke event.php
        $_SESSION['error'] = "Tidak ada event yang tersedia untuk pendaftaran.";
        header("Location: event.php");
        exit();
    }
    
    $event = mysqli_fetch_assoc($result);
    $event_id = $event['id'];
    
    // Tampilkan pesan bahwa ini adalah event terbaru
    $info_message = "Menampilkan event terbaru. Pilih event lain di <a href='event.php'>halaman event</a>.";
} else {
    // Bersihkan dan validasi event_id dari URL
    $event_id = intval($_GET['event_id']);
    
    if ($event_id <= 0) {
        $_SESSION['error'] = "ID Event tidak valid.";
        header("Location: event.php");
        exit();
    }
    
    // Query yang lebih aman dengan prepared statement
    $query = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon 
              FROM events e 
              LEFT JOIN kategori k ON e.kategori_id = k.id 
              WHERE e.id = ? AND e.status = 'aktif'";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $_SESSION['error'] = "Terjadi kesalahan pada server.";
        header("Location: event.php");
        exit();
    }
    
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Cek jika event tidak ditemukan
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "Event tidak ditemukan atau sudah tidak aktif.";
        header("Location: event.php");
        exit();
    }
    
    $event = $result->fetch_assoc();
}

// ============================================
// 2. CEK APAKAH PENDAFTARAN MASIH DIBUKA
// ============================================
$today = date('Y-m-d');
$pendaftaran_dibuka = true;
$alasan_tutup = '';

// Cek tanggal daftar akhir jika ada
if (!empty($event['tanggal_daftar_akhir']) && $today > $event['tanggal_daftar_akhir']) {
    $pendaftaran_dibuka = false;
    $alasan_tutup = "Pendaftaran telah ditutup sejak " . date('d F Y', strtotime($event['tanggal_daftar_akhir']));
}

// Cek jika event sudah lewat
if ($today > $event['tanggal']) {
    $pendaftaran_dibuka = false;
    $alasan_tutup = "Event telah berlalu pada " . date('d F Y', strtotime($event['tanggal']));
}

// Cek apakah event sudah dimulai
if (!empty($event['tanggal_mulai']) && $today < $event['tanggal_mulai']) {
    $pendaftaran_dibuka = false;
    $alasan_tutup = "Pendaftaran akan dibuka pada " . date('d F Y', strtotime($event['tanggal_mulai']));
}

// ============================================
// 3. CEK KUOTA (UNTUK SISTEM WEBSITE)
// ============================================
$kuota_penuh = false;
if ($pendaftaran_dibuka && empty($event['link_pendaftaran'])) {
    // Hitung jumlah pendaftar
    $query_pendaftar = "SELECT COUNT(*) as total FROM pendaftaran WHERE event_id = ? AND status != 'ditolak'";
    $stmt = $conn->prepare($query_pendaftar);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result_pendaftar = $stmt->get_result();
    $pendaftar = $result_pendaftar->fetch_assoc();
    $jumlah_pendaftar = $pendaftar['total'];
    
    // Cek kuota
    if ($event['kuota_peserta'] > 0 && $jumlah_pendaftar >= $event['kuota_peserta']) {
        $kuota_penuh = true;
        $alasan_tutup = "Kuota pendaftaran telah penuh";
    }
}

// ============================================
// 4. PROSES PENDAFTARAN JIKA FORM DISUBMIT
// ============================================
$success = false;
$error = '';
$pendaftaran_id = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $pendaftaran_dibuka && !$kuota_penuh && empty($event['link_pendaftaran'])) {
    
    // AMBIL DATA DARI FORM BERDASARKAN TIPE
    $tipe_pendaftaran = $event['tipe_pendaftaran'] ?? 'individu';
    
    if ($tipe_pendaftaran == 'individu') {
        // PROSES PENDAFTARAN INDIVIDU
        $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
        $nim = mysqli_real_escape_string($conn, $_POST['nim'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
        $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan'] ?? '');
        $angkatan = mysqli_real_escape_string($conn, $_POST['angkatan'] ?? '');
        $motivasi = mysqli_real_escape_string($conn, $_POST['motivasi'] ?? '');
        $nama_tim = mysqli_real_escape_string($conn, $_POST['nama_tim'] ?? $nama);
        
        // VALIDASI
        if (empty($nama) || empty($email) || empty($no_hp)) {
            $error = "Nama, email, dan nomor HP harus diisi!";
        } else {
            // Cek apakah sudah pernah mendaftar
            $cek_query = "SELECT id FROM pendaftaran WHERE event_id = ? AND email = ?";
            $stmt = $conn->prepare($cek_query);
            $stmt->bind_param("is", $event_id, $email);
            $stmt->execute();
            $cek_result = $stmt->get_result();
            
            if ($cek_result->num_rows > 0) {
                $error = "Anda sudah terdaftar pada event ini dengan email tersebut!";
            } else {
                // SIMPAN KE DATABASE
                $insert_query = "INSERT INTO pendaftaran 
                                (event_id, nama, nim, email, no_hp, jurusan, angkatan, motivasi, jumlah_anggota, nama_tim, tanggal_daftar, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), 'menunggu')";
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    $error = "Gagal mempersiapkan query: " . $conn->error;
                } else {
                    $stmt->bind_param("issssssss", $event_id, $nama, $nim, $email, $no_hp, $jurusan, $angkatan, $motivasi, $nama_tim);
                    
                    if ($stmt->execute()) {
                        $success = true;
                        $pendaftaran_id = $stmt->insert_id;
                    } else {
                        $error = "Gagal mendaftar: " . $conn->error;
                    }
                }
            }
        }
        
    } elseif ($tipe_pendaftaran == 'tim') {
        // PROSES PENDAFTARAN TIM
        $nama_tim = mysqli_real_escape_string($conn, $_POST['nama_tim'] ?? '');
        $ketua_nama = mysqli_real_escape_string($conn, $_POST['ketua_nama'] ?? '');
        $ketua_nim = mysqli_real_escape_string($conn, $_POST['ketua_nim'] ?? '');
        $ketua_email = mysqli_real_escape_string($conn, $_POST['ketua_email'] ?? '');
        $ketua_no_hp = mysqli_real_escape_string($conn, $_POST['ketua_no_hp'] ?? '');
        $ketua_jurusan = mysqli_real_escape_string($conn, $_POST['ketua_jurusan'] ?? '');
        $ketua_angkatan = mysqli_real_escape_string($conn, $_POST['ketua_angkatan'] ?? '');
        
        // AMBIL DATA ANGGOTA
        $anggota_data = [];
        $total_anggota = 1; // Ketua sudah dihitung
        
        // Loop untuk anggota tambahan
        $max_anggota = $event['max_anggota'] ?? 1;
        for ($i = 1; $i <= $max_anggota; $i++) {
            if (isset($_POST["anggota_nama_$i"]) && !empty(trim($_POST["anggota_nama_$i"]))) {
                $anggota_data[] = [
                    'nama' => mysqli_real_escape_string($conn, $_POST["anggota_nama_$i"]),
                    'nim' => mysqli_real_escape_string($conn, $_POST["anggota_nim_$i"] ?? ''),
                    'jurusan' => mysqli_real_escape_string($conn, $_POST["anggota_jurusan_$i"] ?? ''),
                    'angkatan' => mysqli_real_escape_string($conn, $_POST["anggota_angkatan_$i"] ?? '')
                ];
                $total_anggota++;
            }
        }
        
        // VALIDASI
        if (empty($nama_tim) || empty($ketua_nama) || empty($ketua_email) || empty($ketua_no_hp)) {
            $error = "Nama tim, nama ketua, email, dan nomor HP ketua harus diisi!";
        } elseif ($total_anggota < ($event['min_anggota'] ?? 1)) {
            $error = "Minimal anggota untuk tim ini adalah " . ($event['min_anggota'] ?? 1) . " orang!";
        } elseif ($total_anggota > ($event['max_anggota'] ?? 1)) {
            $error = "Maksimal anggota untuk tim ini adalah " . ($event['max_anggota'] ?? 1) . " orang!";
        } else {
            // Cek apakah tim sudah terdaftar
            $cek_query = "SELECT id FROM pendaftaran WHERE event_id = ? AND nama_tim = ?";
            $stmt = $conn->prepare($cek_query);
            $stmt->bind_param("is", $event_id, $nama_tim);
            $stmt->execute();
            $cek_result = $stmt->get_result();
            
            if ($cek_result->num_rows > 0) {
                $error = "Nama tim sudah digunakan! Silakan gunakan nama tim lain.";
            } else {
                // SIMPAN TIM KE DATABASE
                $insert_query = "INSERT INTO pendaftaran 
                                (event_id, nama_tim, nama, nim, email, no_hp, jurusan, angkatan, jumlah_anggota, tanggal_daftar, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'menunggu')";
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    $error = "Gagal mempersiapkan query: " . $conn->error;
                } else {
                    $stmt->bind_param("isssssssi", $event_id, $nama_tim, $ketua_nama, $ketua_nim, $ketua_email, 
                                    $ketua_no_hp, $ketua_jurusan, $ketua_angkatan, $total_anggota);
                    
                    if ($stmt->execute()) {
                        $pendaftaran_id = $stmt->insert_id;
                        
                        // SIMPAN DATA ANGGOTA KE TABEL ANGGOTA_TIM
                        foreach ($anggota_data as $index => $anggota) {
                            $insert_anggota = "INSERT INTO anggota_tim 
                                              (pendaftaran_id, nama, nim, jurusan, angkatan, nomor_anggota) 
                                              VALUES (?, ?, ?, ?, ?, ?)";
                            $stmt2 = $conn->prepare($insert_anggota);
                            $nomor_anggota = $index + 2;
                            $stmt2->bind_param("issssi", $pendaftaran_id, $anggota['nama'], $anggota['nim'], 
                                             $anggota['jurusan'], $anggota['angkatan'], $nomor_anggota);
                            $stmt2->execute();
                        }
                        
                        $success = true;
                    } else {
                        $error = "Gagal mendaftar tim: " . $conn->error;
                    }
                }
            }
        }
    }
}

// ============================================
// 5. TAMPILAN HTML - DIKEMBALIKAN KE STYLE ASLI
// ============================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran <?php echo htmlspecialchars($event['judul']); ?> - Event Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            background-color: var(--gray-light);
            padding-top: 0;
        }
        
        /* HEADER PENDAFTARAN */
        .registration-header {
            background: linear-gradient(rgba(0, 86, 179, 0.95), rgba(0, 61, 130, 0.95)), 
                        url('https://images.unsplash.com/photo-1523580494863-6f3031224c94?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
            border-bottom: 5px solid var(--accent-color);
        }
        
        .registration-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .registration-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        /* FORM CONTAINER */
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 40px;
            border: 1px solid #e0e0e0;
        }
        
        .form-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .form-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .form-header h4 {
            margin: 10px 0 0;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .form-body {
            padding: 30px;
        }
        
        /* EVENT INFO CARD */
        .event-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .kategori-badge {
            background: <?php echo $event['warna'] ?? '#0056b3'; ?>;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            width: 120px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .info-value {
            flex: 1;
            color: #555;
        }
        
        /* FORM STYLES */
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
            font-size: 1.3rem;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
        }
        
        .required:after {
            content: " *";
            color: #dc3545;
        }
        
        /* ANGGOTA TIM SECTION */
        .anggota-section {
            background: #f0f8ff;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            border: 2px dashed #b8d4ff;
        }
        
        .anggota-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .anggota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .btn-add-anggota {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px auto;
            transition: all 0.3s;
            font-size: 1.2rem;
        }
        
        .btn-add-anggota:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(0,86,179,0.3);
        }
        
        .btn-remove-anggota {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            margin-top: 15px;
            transition: all 0.3s;
        }
        
        .btn-remove-anggota:hover {
            background: #c82333;
        }
        
        .anggota-counter {
            background: var(--primary-color);
            color: white;
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        /* MESSAGE BOXES */
        .success-box {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin: 30px 0;
        }
        
        .error-box {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 2px solid #dc3545;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        /* FOOTER */
        .registration-footer {
            background: var(--primary-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
        }
        
        .footer-links a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .registration-header {
                padding: 40px 0 30px;
            }
            
            .registration-title {
                font-size: 2rem;
            }
            
            .form-body {
                padding: 20px;
            }
            
            .event-info-card {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="registration-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="registration-title">
                        <i class="fas fa-user-plus me-3"></i>
                        Pendaftaran Event Kampus
                    </h1>
                    <p class="registration-subtitle">
                        Isi formulir di bawah untuk mendaftar event ini
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-inline-block bg-white text-dark rounded-pill px-4 py-2">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>
                        <strong>Event ID:</strong> <?php echo str_pad($event_id, 4, '0', STR_PAD_LEFT); ?>
                    </div>
                </div>
            </div>
            
            <?php if (isset($info_message)): ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $info_message; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- EVENT INFO -->
        <div class="event-info-card">
            <span class="kategori-badge">
                <i class="<?php echo $event['ikon'] ?? 'fas fa-calendar'; ?> me-2"></i>
                <?php echo htmlspecialchars($event['kategori_nama']); ?>
            </span>
            
            <h3 class="mb-3" style="color: var(--primary-color);"><?php echo htmlspecialchars($event['judul']); ?></h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="info-label"><i class="far fa-calendar-alt me-2"></i>Tanggal:</span>
                        <span class="info-value"><?php echo date('d F Y', strtotime($event['tanggal'])); ?></span>
                    </div>
                    
                    <?php if (!empty($event['waktu'])): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="far fa-clock me-2"></i>Waktu:</span>
                        <span class="info-value"><?php echo date('H:i', strtotime($event['waktu'])); ?> WIB</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-map-marker-alt me-2"></i>Lokasi:</span>
                        <span class="info-value"><?php echo htmlspecialchars($event['lokasi']); ?></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <?php if ($event['biaya_pendaftaran'] > 0): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-money-bill-wave me-2"></i>Biaya:</span>
                        <span class="info-value h5 text-success mb-0">Rp <?php echo number_format($event['biaya_pendaftaran'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event['kuota_peserta'] > 0): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-users me-2"></i>Kuota:</span>
                        <span class="info-value">
                            <span class="badge bg-primary rounded-pill"><?php echo number_format($jumlah_pendaftar ?? 0, 0, ',', '.'); ?></span>
                            / <?php echo number_format($event['kuota_peserta'], 0, ',', '.'); ?> peserta
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event['tipe_pendaftaran'] == 'tim'): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-users me-2"></i>Tipe:</span>
                        <span class="info-value">
                            <span class="badge bg-info">Pendaftaran Tim</span>
                            <small class="d-block mt-1">
                                Minimal <?php echo $event['min_anggota']; ?> orang | 
                                Maksimal <?php echo $event['max_anggota']; ?> orang
                            </small>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- FORM CONTAINER -->
        <div class="form-container">
            <!-- HEADER -->
            <div class="form-header">
                <h2><i class="fas fa-file-signature me-2"></i>Formulir Pendaftaran</h2>
                <h4>Lengkapi data diri Anda dengan benar</h4>
            </div>
            
            <!-- BODY -->
            <div class="form-body">
                <?php if (!$pendaftaran_dibuka || $kuota_penuh): ?>
                    <!-- PENDAFTARAN TUTUP / KUOTA PENUH -->
                    <div class="error-box text-center">
                        <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                        <h4>Pendaftaran Tidak Tersedia</h4>
                        <p class="mb-4"><?php echo $alasan_tutup ?? 'Pendaftaran untuk event ini sudah ditutup'; ?></p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="event.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Event
                            </a>
                            <a href="detail_event.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-info-circle me-2"></i> Detail Event
                            </a>
                        </div>
                    </div>
                    
                <?php elseif (!empty($event['link_pendaftaran'])): ?>
                    <!-- PENDAFTARAN VIA GOOGLE FORM -->
                    <div class="info-box text-center">
                        <i class="fab fa-google fa-3x mb-3" style="color: #4285F4;"></i>
                        <h4>Pendaftaran via Google Form</h4>
                        <p class="mb-4">Event ini menggunakan Google Form untuk pendaftaran. Klik tombol di bawah untuk mengisi formulir.</p>
                        
                        <div class="d-grid gap-3 d-md-block">
                            <a href="<?php echo htmlspecialchars($event['link_pendaftaran']); ?>" 
                               class="btn btn-success btn-lg px-5" target="_blank">
                                <i class="fab fa-google me-2"></i> Buka Google Form
                            </a>
                            <a href="event.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Batal
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Form akan terbuka di tab baru. Pastikan data Anda tersimpan.
                            </small>
                        </div>
                    </div>
                    
                <?php elseif ($success): ?>
                    <!-- SUCCESS MESSAGE -->
                    <div class="success-box">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h3>Pendaftaran Berhasil!</h3>
                        <p class="lead">Terima kasih telah mendaftar event <strong><?php echo htmlspecialchars($event['judul']); ?></strong></p>
                        
                        <div class="alert alert-primary mt-4" style="background: #e3f2fd; border-color: #2196F3;">
                            <h5><i class="fas fa-id-card me-2"></i>ID Pendaftaran Anda</h5>
                            <p class="display-4 text-primary fw-bold mb-1"><?php echo str_pad($pendaftaran_id, 6, '0', STR_PAD_LEFT); ?></p>
                            <small class="text-muted">Simpan ID ini untuk keperluan verifikasi</small>
                        </div>
                        
                        <div class="mt-4 text-start">
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                Konfirmasi pendaftaran telah dikirim ke email Anda.
                            </p>
                            <p>
                                <i class="fas fa-phone me-2 text-primary"></i>
                                Admin akan menghubungi melalui WhatsApp untuk informasi selanjutnya.
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <a href="event.php" class="btn btn-primary me-3">
                                <i class="fas fa-list me-2"></i> Lihat Event Lainnya
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i> Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- FORM PENDAFTARAN WEBSITE -->
                    
                    <?php if (!empty($error)): ?>
                    <div class="error-box">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event['tipe_pendaftaran'] == 'tim'): ?>
                    <div class="info-box">
                        <i class="fas fa-users me-2"></i>
                        <strong>Pendaftaran Tim</strong>
                        <p class="mb-0">Isi data ketua tim dan anggota tim sesuai jumlah yang dibutuhkan.</p>
                        <small>Minimal <?php echo $event['min_anggota']; ?> orang | Maksimal <?php echo $event['max_anggota']; ?> orang</small>
                    </div>
                    <?php else: ?>
                    <div class="info-box">
                        <i class="fas fa-user me-2"></i>
                        <strong>Pendaftaran Individu</strong>
                        <p class="mb-0">Isi data diri Anda untuk mendaftar event ini.</p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- FORM -->
                    <form method="POST" action="?event_id=<?php echo $event_id; ?>" id="formPendaftaran">
                        
                        <?php if ($event['tipe_pendaftaran'] == 'tim'): ?>
                            <!-- ============= FORM UNTUK TIM ============= -->
                            <div class="form-section">
                                <h4 class="section-title"><i class="fas fa-crown me-2"></i>Data Ketua Tim</h4>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label required">Nama Tim</label>
                                        <input type="text" name="nama_tim" class="form-control form-control-lg" 
                                               placeholder="Contoh: Tim Bintang, Warriors, dll" required
                                               value="<?php echo isset($_POST['nama_tim']) ? htmlspecialchars($_POST['nama_tim']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Nama Ketua Tim</label>
                                        <input type="text" name="ketua_nama" class="form-control" required
                                               value="<?php echo isset($_POST['ketua_nama']) ? htmlspecialchars($_POST['ketua_nama']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIM Ketua</label>
                                        <input type="text" name="ketua_nim" class="form-control"
                                               value="<?php echo isset($_POST['ketua_nim']) ? htmlspecialchars($_POST['ketua_nim']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Email Ketua</label>
                                        <input type="email" name="ketua_email" class="form-control" required
                                               value="<?php echo isset($_POST['ketua_email']) ? htmlspecialchars($_POST['ketua_email']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">No HP/WhatsApp Ketua</label>
                                        <input type="tel" name="ketua_no_hp" class="form-control" required
                                               placeholder="0812-3456-7890"
                                               value="<?php echo isset($_POST['ketua_no_hp']) ? htmlspecialchars($_POST['ketua_no_hp']) : ''; ?>">
                                        <small class="text-muted">Format: 0812-3456-7890</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jurusan Ketua</label>
                                        <select name="ketua_jurusan" class="form-select">
                                            <option value="">Pilih Jurusan</option>
                                            <option value="Teknik Informatika" <?php echo (isset($_POST['ketua_jurusan']) && $_POST['ketua_jurusan'] == 'Teknik Informatika') ? 'selected' : ''; ?>>Teknik Informatika</option>
                                            <option value="Sistem Informasi" <?php echo (isset($_POST['ketua_jurusan']) && $_POST['ketua_jurusan'] == 'Sistem Informasi') ? 'selected' : ''; ?>>Sistem Informasi</option>
                                            <option value="Teknik Elektro" <?php echo (isset($_POST['ketua_jurusan']) && $_POST['ketua_jurusan'] == 'Teknik Elektro') ? 'selected' : ''; ?>>Teknik Elektro</option>
                                            <option value="Akuntansi" <?php echo (isset($_POST['ketua_jurusan']) && $_POST['ketua_jurusan'] == 'Akuntansi') ? 'selected' : ''; ?>>Akuntansi</option>
                                            <option value="Manajemen" <?php echo (isset($_POST['ketua_jurusan']) && $_POST['ketua_jurusan'] == 'Manajemen') ? 'selected' : ''; ?>>Manajemen</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Angkatan</label>
                                        <select name="ketua_angkatan" class="form-select">
                                            <option value="">Pilih Angkatan</option>
                                            <?php for ($year = date('Y'); $year >= 2015; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo (isset($_POST['ketua_angkatan']) && $_POST['ketua_angkatan'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ANGGOTA TIM -->
                            <div class="anggota-section">
                                <div class="anggota-header">
                                    <h4 class="section-title mb-0"><i class="fas fa-users me-2"></i>Anggota Tim</h4>
                                    <div class="anggota-counter">
                                        Anggota: <span id="anggotaCount">1</span>/<?php echo $event['max_anggota']; ?>
                                    </div>
                                </div>
                                
                                <p class="mb-3">Minimal <?php echo $event['min_anggota']; ?> orang | Maksimal <?php echo $event['max_anggota']; ?> orang</p>
                                
                                <div id="anggotaContainer">
                                    <!-- Anggota akan ditambahkan dinamis di sini -->
                                </div>
                                
                                <!-- TOMBOL TAMBAH ANGGOTA -->
                                <button type="button" class="btn-add-anggota" id="btnTambahAnggota">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <p class="text-center text-muted mt-2 mb-0">Klik untuk tambah anggota tim</p>
                            </div>
                            
                        <?php else: ?>
                            <!-- ============= FORM UNTUK INDIVIDU ============= -->
                            <div class="form-section">
                                <h4 class="section-title"><i class="fas fa-user me-2"></i>Data Pribadi</h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Nama Lengkap</label>
                                        <input type="text" name="nama" class="form-control" required
                                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIM</label>
                                        <input type="text" name="nim" class="form-control"
                                               value="<?php echo isset($_POST['nim']) ? htmlspecialchars($_POST['nim']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">Email</label>
                                        <input type="email" name="email" class="form-control" required
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required">No HP/WhatsApp</label>
                                        <input type="tel" name="no_hp" class="form-control" required
                                               placeholder="0812-3456-7890"
                                               value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>">
                                        <small class="text-muted">Format: 0812-3456-7890</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Jurusan</label>
                                        <select name="jurusan" class="form-select">
                                            <option value="">Pilih Jurusan</option>
                                            <option value="Teknik Informatika" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Teknik Informatika') ? 'selected' : ''; ?>>Teknik Informatika</option>
                                            <option value="Sistem Informasi" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Sistem Informasi') ? 'selected' : ''; ?>>Sistem Informasi</option>
                                            <option value="Teknik Elektro" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Teknik Elektro') ? 'selected' : ''; ?>>Teknik Elektro</option>
                                            <option value="Akuntansi" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Akuntansi') ? 'selected' : ''; ?>>Akuntansi</option>
                                            <option value="Manajemen" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Manajemen') ? 'selected' : ''; ?>>Manajemen</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Angkatan</label>
                                        <select name="angkatan" class="form-select">
                                            <option value="">Pilih Angkatan</option>
                                            <?php for ($year = date('Y'); $year >= 2015; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo (isset($_POST['angkatan']) && $_POST['angkatan'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <?php if ($event['tipe_pendaftaran'] == 'individu_tim'): ?>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Nama Tim/Kelompok (Opsional)</label>
                                        <input type="text" name="nama_tim" class="form-control" 
                                               placeholder="Kosongkan jika mendaftar sendiri"
                                               value="<?php echo isset($_POST['nama_tim']) ? htmlspecialchars($_POST['nama_tim']) : ''; ?>">
                                        <small class="text-muted">Isi jika ingin mendaftar sebagai kelompok</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h4 class="section-title"><i class="fas fa-comment-dots me-2"></i>Motivasi & Harapan</h4>
                                <div class="mb-4">
                                    <textarea name="motivasi" class="form-control" rows="5" 
                                              placeholder="Ceritakan mengapa Anda ingin mengikuti event ini dan apa harapan Anda..."><?php echo isset($_POST['motivasi']) ? htmlspecialchars($_POST['motivasi']) : ''; ?></textarea>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- BUTTONS -->
                        <div class="border-top pt-4 mt-4">
                            <div class="d-flex justify-content-between">
                                <a href="event.php" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-times me-1"></i> Batal
                                </a>
                                
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    Kirim Pendaftaran
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- INFO TAMBAHAN -->
        <div class="alert alert-info mt-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5><i class="fas fa-info-circle me-2"></i> Informasi Penting</h5>
                    <p class="mb-0">Pastikan data yang Anda isi benar dan valid. Data akan digunakan untuk proses seleksi dan sertifikat.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="event.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i> Lihat Event Lainnya
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FOOTER -->
    <div class="registration-footer">
        <div class="container">
            <p class="mb-2">Politeknik Negeri Batam - Sistem Pendaftaran Event</p>
            <div class="footer-links">
                <a href="index.php">Beranda</a> |
                <a href="event.php">Event</a> |
                <a href="berita.php">Berita</a> |
                <a href="admin/login.php">Admin</a>
            </div>
            <p class="mt-3 mb-0">
                <small>&copy; 2025 Portal Informasi Kampus. Semua hak dilindungi.</small>
            </p>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        <?php if ($event['tipe_pendaftaran'] == 'tim'): ?>
        // ============= JAVASCRIPT UNTUK TIM =============
        let anggotaCount = 1; // Ketua sudah dihitung
        const maxAnggota = <?php echo $event['max_anggota']; ?>;
        const minAnggota = <?php echo $event['min_anggota']; ?>;
        let anggotaIndex = 0;
        
        // Fungsi tambah anggota
        document.getElementById('btnTambahAnggota').addEventListener('click', function() {
            if (anggotaCount >= maxAnggota) {
                alert('Maksimal ' + maxAnggota + ' anggota sudah tercapai!');
                this.disabled = true;
                return;
            }
            
            anggotaIndex++;
            anggotaCount++;
            
            const container = document.getElementById('anggotaContainer');
            const newCard = document.createElement('div');
            newCard.className = 'anggota-card';
            newCard.id = 'anggotaCard-' + anggotaIndex;
            
            newCard.innerHTML = `
                <div class="anggota-header">
                    <h5 class="mb-0"><i class="fas fa-user me-1"></i> Anggota ${anggotaIndex + 1}</h5>
                    <small class="text-muted">Data anggota tim</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label required">Nama Lengkap</label>
                        <input type="text" name="anggota_nama_${anggotaIndex}" class="form-control" 
                               placeholder="Nama anggota" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">NIM</label>
                        <input type="text" name="anggota_nim_${anggotaIndex}" class="form-control" 
                               placeholder="NIM anggota">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Jurusan</label>
                        <select name="anggota_jurusan_${anggotaIndex}" class="form-select">
                            <option value="">Pilih Jurusan</option>
                            <option value="Teknik Informatika">Teknik Informatika</option>
                            <option value="Sistem Informasi">Sistem Informasi</option>
                            <option value="Teknik Elektro">Teknik Elektro</option>
                            <option value="Akuntansi">Akuntansi</option>
                            <option value="Manajemen">Manajemen</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Angkatan</label>
                        <select name="anggota_angkatan_${anggotaIndex}" class="form-select">
                            <option value="">Pilih Angkatan</option>
                            <?php for ($year = date('Y'); $year >= 2015; $year--): ?>
                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn-remove-anggota" onclick="hapusAnggota(${anggotaIndex})">
                    <i class="fas fa-trash me-1"></i> Hapus Anggota
                </button>
            `;
            
            container.appendChild(newCard);
            updateAnggotaCounter();
        });
        
        // Fungsi hapus anggota
        function hapusAnggota(index) {
            if (anggotaCount <= minAnggota) {
                alert('Minimal ' + minAnggota + ' anggota harus diisi!');
                return;
            }
            
            const card = document.getElementById('anggotaCard-' + index);
            if (card) {
                card.remove();
                anggotaCount--;
                document.getElementById('btnTambahAnggota').disabled = false;
                updateAnggotaCounter();
            }
        }
        
        // Fungsi update counter
        function updateAnggotaCounter() {
            document.getElementById('anggotaCount').textContent = anggotaCount;
            
            // Update warna counter
            const counter = document.querySelector('.anggota-counter');
            if (anggotaCount < minAnggota) {
                counter.style.background = '#dc3545';
            } else if (anggotaCount >= maxAnggota) {
                counter.style.background = '#28a745';
            } else {
                counter.style.background = '#0056b3';
            }
        }
        
        // Validasi form sebelum submit
        document.getElementById('formPendaftaran').addEventListener('submit', function(e) {
            if (anggotaCount < minAnggota) {
                e.preventDefault();
                alert(`Minimal ${minAnggota} anggota harus diisi!`);
                return false;
            }
            
            // Validasi email dan no HP
            const email = document.querySelector('input[name="ketua_email"]').value;
            const nohp = document.querySelector('input[name="ketua_no_hp"]').value;
            
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                e.preventDefault();
                alert('Format email tidak valid!');
                return false;
            }
            
            if (!nohp.match(/^[0-9+\-\s]{10,15}$/)) {
                e.preventDefault();
                alert('Format nomor HP tidak valid! Harus 10-15 digit angka');
                return false;
            }
            
            return true;
        });
        
        // Auto format phone number
        document.querySelector('input[name="ketua_no_hp"]')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 4) {
                    value = value;
                } else if (value.length <= 8) {
                    value = value.slice(0, 4) + '-' + value.slice(4);
                } else {
                    value = value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8, 12);
                }
            }
            e.target.value = value;
        });
        
        // Inisialisasi counter
        updateAnggotaCounter();
        
        <?php else: ?>
        // ============= JAVASCRIPT UNTUK INDIVIDU =============
        // Auto format phone number
        document.querySelector('input[name="no_hp"]')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 4) {
                    value = value;
                } else if (value.length <= 8) {
                    value = value.slice(0, 4) + '-' + value.slice(4);
                } else {
                    value = value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8, 12);
                }
            }
            e.target.value = value;
        });
        
        // Validasi form
        document.getElementById('formPendaftaran')?.addEventListener('submit', function(e) {
            const email = document.querySelector('input[name="email"]')?.value;
            const nohp = document.querySelector('input[name="no_hp"]')?.value;
            
            if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                e.preventDefault();
                alert('Format email tidak valid!');
                return false;
            }
            
            if (nohp && !nohp.match(/^[0-9+\-\s]{10,15}$/)) {
                e.preventDefault();
                alert('Format nomor HP tidak valid! Harus 10-15 digit angka');
                return false;
            }
            
            return true;
        });
        <?php endif; ?>
        
        // Auto-focus ke field pertama
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[required]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>
<?php
// Tutup koneksi
$conn->close();
?>
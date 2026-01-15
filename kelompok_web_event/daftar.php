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
$event_query = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon 
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

// AMBIL DATA UNTUK NAVBAR SEARCH (sama seperti index.php)
$search_query = "SELECT judul FROM events WHERE status = 'publik' LIMIT 5";
$search_result = mysqli_query($conn, $search_query);
$search_suggestions = [];
if ($search_result) {
    while ($row = mysqli_fetch_assoc($search_result)) {
        $search_suggestions[] = $row['judul'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran - <?php echo htmlspecialchars($event['judul']); ?> - Portal Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* VARIABLES SAMA DENGAN INDEX.PHP */
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
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background: linear-gradient(to bottom, #f0f1f3ff 80%, #ffffff 100%);
            min-height: 100vh;
        }

        /* ===== NAVBAR SAMA DENGAN INDEX.PHP ===== */
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-nav .nav-link {
            position: relative;
            padding-bottom: 6px;
        }

        .navbar-nav .nav-link::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 0;
            height: 2px;
            background-color: #ffffffff;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after,
        .navbar-nav .nav-link.active::after {
            width: 100%;
        }

        .navbar-brand img {
            height: 50px;
        }

        /* SEARCH FORM SAMA DENGAN INDEX.PHP */
        .search-form {
            margin-right: 15px;
        }

        .search-input {
            width: 250px;
            border-radius: 20px 0 0 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            width: 300px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
        }

        .search-input:focus::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-outline-light.btn-sm {
            border-radius: 0 20px 20px 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.375rem 1rem;
        }

        .btn-outline-light.btn-sm:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* ===== HEADER PENDAFTARAN SOLID ===== */
        .registration-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.3);
            position: relative;
            overflow: hidden;
        }

        .registration-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .event-badge-custom {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* ===== FORM CONTAINER SOLID ===== */
        .form-container-solid {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin-bottom: 40px;
            border: 3px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .form-container-solid::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
        }

        .form-section-solid {
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 2px solid #eee;
        }

        .section-title-solid {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.4rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title-solid i {
            background: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        /* ===== TIPE SELECTION SOLID ===== */
        .tipe-selection-solid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .tipe-card-solid {
            flex: 1;
            border: 3px solid #dee2e6;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .tipe-card-solid:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 86, 179, 0.15);
        }

        .tipe-card-solid.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }

        .tipe-icon-solid {
            font-size: 3.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        /* ===== ANGGOTA CARD SOLID ===== */
        .anggota-card-solid {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            background: #f8f9fa;
            position: relative;
            transition: all 0.3s;
        }

        .anggota-card-solid:hover {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .anggota-header-solid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .anggota-label-solid {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }

        .badge-ketua-solid {
            background: var(--primary-color);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .anggota-counter-solid {
            position: absolute;
            top: -15px;
            left: 20px;
            background: var(--accent-color);
            color: #000;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            border: 3px solid white;
        }

        /* ===== BUTTONS SOLID ===== */
        .btn-solid-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white !important;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-solid-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 86, 179, 0.3);
            color: white;
        }

        .btn-solid-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #1e7e34 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
        }

        .btn-solid-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }

        .btn-solid-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #bd2130 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
        }

        .btn-solid-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
        }

        .btn-solid-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #e0a800 100%);
            color: #000;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            transition: all 0.3s;
        }

        .btn-solid-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.3);
        }

        /* ===== INFO BOXES SOLID ===== */
        .info-box-solid {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 3px solid #2196f3;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .warning-box-solid {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 3px solid var(--warning-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .danger-box-solid {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 3px solid var(--danger-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .success-box-solid {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 3px solid var(--success-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        /* ===== QUOTA STATUS SOLID ===== */
        .quota-status-solid {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #dee2e6;
        }

        .quota-progress-solid {
            height: 15px;
            border-radius: 8px;
            flex-grow: 1;
            background: #e9ecef;
            overflow: hidden;
        }

        .quota-progress-bar-solid {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color) 0%, #20c997 100%);
            border-radius: 8px;
        }

        /* ===== PAYMENT BOX SOLID ===== */
        .payment-box-solid {
            background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
            border: 3px solid var(--warning-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .rekening-box-solid {
            background: white;
            border: 3px solid var(--primary-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .rekening-item-solid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 2px solid #f0f0f0;
        }

        .rekening-item-solid:last-child {
            border-bottom: none;
        }

        .copy-btn-solid {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn-solid:hover {
            background: var(--primary-dark);
        }

        /* ===== UPLOAD AREA SOLID ===== */
        .upload-area-solid {
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 50px 30px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 25px;
        }

        .upload-area-solid:hover {
            border-color: var(--primary-color);
            background: #e8f4ff;
        }

        .upload-area-solid.dragover {
            border-color: var(--success-color);
            background: #e8fff0;
        }

        .upload-icon-solid {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .preview-container-solid {
            position: relative;
            margin-top: 25px;
            border: 3px solid var(--primary-color);
            border-radius: 15px;
            padding: 20px;
            background: white;
        }

        .preview-image-solid {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-remove-file-solid {
            position: absolute;
            top: -15px;
            right: -15px;
            background: var(--danger-color);
            color: white;
            border: 3px solid white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
        }

        /* ===== ALERTS SOLID ===== */
        .alert-custom-solid {
            border-radius: 12px;
            border: 3px solid;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .alert-custom-solid.alert-danger {
            border-color: var(--danger-color);
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .alert-custom-solid.alert-success {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-custom-solid.alert-warning {
            border-color: var(--warning-color);
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .alert-custom-solid.alert-info {
            border-color: var(--info-color);
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }

        /* ===== FOOTER SAMA DENGAN INDEX.PHP ===== */
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer a {
            color: #ddd;
            text-decoration: none;
        }

        .footer a:hover {
            color: white;
        }

        .social-icons a {
            display: inline-block;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .search-form {
                margin: 10px 0;
                width: 100%;
            }
            
            .search-input {
                width: 100% !important;
                margin-bottom: 10px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .btn-outline-light.btn-sm {
                width: 100%;
                border-radius: 20px !important;
            }
            
            .tipe-selection-solid {
                flex-direction: column;
            }
            
            .registration-header {
                padding: 40px 0;
            }
            
            .form-container-solid {
                padding: 25px;
            }
        }

        @media (max-width: 768px) {
            .anggota-card-solid {
                padding: 20px;
            }
            
            .section-title-solid {
                font-size: 1.2rem;
            }
            
            .section-title-solid i {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .rekening-item-solid {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 576px) {
            .form-container-solid {
                padding: 20px;
            }
            
            .registration-header h1 {
                font-size: 1.8rem;
            }
            
            .anggota-header-solid {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- ===== NAVBAR SAMA DENGAN INDEX.PHP ===== -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png"
                    alt="Politeknik Negeri Batam">
            </a>
            <!-- Form Pencarian -->
            <form class="d-flex search-form" action="search.php" method="GET">
                <div class="input-group">
                    <input type="text" 
                           class="form-control form-control-sm search-input" 
                           name="q" 
                           placeholder="Cari berita/event..." 
                           aria-label="Search"
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
                           list="searchSuggestions">
                    <datalist id="searchSuggestions">
                        <?php foreach ($search_suggestions as $suggestion): ?>
                            <option value="<?php echo htmlspecialchars($suggestion); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <button class="btn btn-outline-light btn-sm" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="berita.php">Berita Kampus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="event.php">Event & Kegiatan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===== HEADER PENDAFTARAN SOLID ===== -->
    <div class="registration-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="event-badge-custom">
                        <i class="<?php echo $event['ikon']; ?> me-2"></i>
                        <?php echo $event['kategori_nama']; ?>
                    </span>
                    <h1 class="display-4 fw-bold">Pendaftaran Event</h1>
                    <h2 class="mb-3"><?php echo htmlspecialchars($event['judul']); ?></h2>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-white text-primary px-3 py-2">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?php echo date('d F Y', strtotime($event['tanggal'])); ?>
                        </span>
                        <span class="badge bg-white text-primary px-3 py-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($event['lokasi']); ?>
                        </span>
                        <span class="badge bg-white text-primary px-3 py-2">
                            <i class="fas fa-users me-2"></i>
                            <?php 
                            if ($tipe == 'tim') {
                                echo 'Wajib Tim (' . $min_anggota . '-' . $max_anggota . ' orang)';
                            } elseif ($tipe == 'individu_tim') {
                                echo 'Bisa Individu/Tim';
                            } else {
                                echo 'Individu';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="detail_event.php?id=<?php echo $event_id; ?>" class="btn-solid-primary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- NOTIFIKASI ERROR -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-custom-solid alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h4 class="mb-1">Terjadi Kesalahan!</h4>
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

        <!-- CEK APAKAH MASIH BISA DAFTAR -->
        <?php if ($event_passed): ?>
            <div class="alert alert-custom-solid alert-warning text-center py-5">
                <i class="fas fa-calendar-times fa-4x mb-3"></i>
                <h3 class="mb-3">Pendaftaran Ditutup</h3>
                <p class="mb-0">Maaf, pendaftaran untuk event ini sudah ditutup karena event sudah berlalu.</p>
                <div class="mt-3">
                    <a href="event.php" class="btn-solid-primary">
                        <i class="fas fa-calendar me-2"></i> Cari Event Lainnya
                    </a>
                </div>
            </div>
        <?php elseif ($is_full): ?>
            <div class="alert alert-custom-solid alert-danger text-center py-5">
                <i class="fas fa-users-slash fa-4x mb-3"></i>
                <h3 class="mb-3">Kuota Penuh</h3>
                <p class="mb-0">Maaf, kuota pendaftaran untuk event ini sudah penuh.</p>
                <div class="mt-3">
                    <a href="event.php" class="btn-solid-primary">
                        <i class="fas fa-calendar me-2"></i> Cari Event Lainnya
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- INFO BOX STATUS & KUOTA -->
            <div class="info-box-solid">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-3"><i class="fas fa-info-circle me-2"></i> Status Pendaftaran</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>Tanggal Event:</strong><br>
                                        <?php echo date('d F Y', strtotime($event['tanggal'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>Sistem Pendaftaran:</strong><br>
                                        <?php 
                                        if ($tipe == 'tim') {
                                            echo 'Wajib Tim (' . $min_anggota . '-' . $max_anggota . ' orang)';
                                        } elseif ($tipe == 'individu_tim') {
                                            echo 'Bisa Individu atau Tim';
                                        } else {
                                            echo 'Individu';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($kuota > 0): ?>
                        <div class="quota-status-solid">
                            <div>
                                <strong>Kuota Tersedia:</strong><br>
                                <h3 class="text-success mb-0"><?php echo $remaining; ?></h3>
                                <small>dari <?php echo $kuota; ?> kuota</small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="quota-progress-solid">
                                    <div class="quota-progress-bar-solid" 
                                         style="width: <?php echo min(100, ($registered_count/$kuota)*100); ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small>Terdaftar: <?php echo $registered_count; ?></small>
                                    <small>Tersisa: <?php echo $remaining; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($berbayar): ?>
                    <div class="col-md-4">
                        <div class="payment-box-solid">
                            <h4 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i> Biaya Pendaftaran</h4>
                            <h1 class="text-warning mb-3">Rp <?php echo number_format($biaya, 0, ',', '.'); ?></h1>
                            <p class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php echo $tipe == 'tim' ? 'Per Tim' : 'Per Orang'; ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-clock text-info me-2"></i>
                                Verifikasi maksimal 1x24 jam
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FORM CONTAINER -->
            <div class="form-container-solid">
                <form method="POST" id="pendaftaranForm" enctype="multipart/form-data">
                    
                    <!-- PILIH TIPE PENDAFTARAN (Hanya untuk individu_tim) -->
                    <?php if ($tipe == 'individu_tim'): ?>
                    <div class="form-section-solid">
                        <h4 class="section-title-solid">
                            <i class="fas fa-users"></i> Pilih Tipe Pendaftaran
                        </h4>
                        
                        <div class="tipe-selection-solid">
                            <div class="tipe-card-solid <?php echo (isset($_POST['tipe_daftar']) && $_POST['tipe_daftar'] == 'individu') ? 'active' : ''; ?>" 
                                 data-tipe="individu" onclick="selectTipe('individu')">
                                <div class="tipe-icon-solid">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4 class="mb-2">Individu</h4>
                                <p class="text-muted mb-3">Daftar sendiri tanpa tim</p>
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input" type="radio" name="tipe_daftar" 
                                           value="individu" id="tipeIndividu" 
                                           <?php echo ($_POST['tipe_daftar'] ?? 'individu') == 'individu' ? 'checked' : ''; ?>>
                                    <label class="form-check-label ms-2" for="tipeIndividu">
                                        Pilih Individu
                                    </label>
                                </div>
                            </div>
                            
                            <div class="tipe-card-solid <?php echo ($_POST['tipe_daftar'] ?? '') == 'tim' ? 'active' : ''; ?>" 
                                 data-tipe="tim" onclick="selectTipe('tim')">
                                <div class="tipe-icon-solid">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h4 class="mb-2">Tim</h4>
                                <p class="text-muted mb-3">Daftar sebagai tim</p>
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input" type="radio" name="tipe_daftar" 
                                           value="tim" id="tipeTim" 
                                           <?php echo ($_POST['tipe_daftar'] ?? '') == 'tim' ? 'checked' : ''; ?>>
                                    <label class="form-check-label ms-2" for="tipeTim">
                                        Pilih Tim
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-box-solid mt-3" id="tipeInfo">
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
                    <div class="form-section-solid" id="timSection" style="<?php echo ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim') ? '' : 'display: none;'; ?>">
                        <h4 class="section-title-solid">
                            <i class="fas fa-flag"></i> Informasi Tim
                        </h4>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Nama Tim <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_tim" class="form-control form-control-lg" 
                                           value="<?php echo isset($_POST['nama_tim']) ? htmlspecialchars($_POST['nama_tim']) : ''; ?>" 
                                           placeholder="Contoh: Tim Juara Kampus 2025" required>
                                    <div class="form-text">Nama tim akan muncul di sertifikat dan pengumuman</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Jumlah Anggota <span class="text-danger">*</span></label>
                                    <select name="jumlah_anggota" class="form-select form-select-lg" id="jumlahAnggota" required>
                                        <option value="">Pilih jumlah</option>
                                        <?php for ($i = $min_anggota; $i <= $max_anggota; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                            <?php echo ($_POST['jumlah_anggota'] ?? $min_anggota) == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> Orang
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
                    <div class="form-section-solid">
                        <h4 class="section-title-solid">
                            <i class="fas fa-user-friends"></i> 
                            <span id="anggotaTitle">
                                <?php echo ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim') ? 'Data Anggota Tim' : 'Data Diri Peserta'; ?>
                            </span>
                        </h4>
                        
                        <div id="anggotaContainer">
                            <!-- Anggota akan ditambahkan dinamis di sini -->
                            <?php
                            $jumlah_anggota = $_POST['jumlah_anggota'] ?? 1;
                            $current_members = max(1, $jumlah_anggota);
                            
                            for ($i = 0; $i < $current_members; $i++):
                            ?>
                            <div class="anggota-card-solid" data-index="<?php echo $i; ?>">
                                <div class="anggota-counter-solid"><?php echo $i + 1; ?></div>
                                
                                <?php if ($tipe == 'tim' || ($_POST['tipe_daftar'] ?? '') == 'tim'): ?>
                                <div class="anggota-header-solid">
                                    <span class="anggota-label-solid">Anggota <?php echo $i + 1; ?></span>
                                    <?php if ($i == 0): ?>
                                        <span class="badge-ketua-solid">KETUA TIM</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                            <input type="text" name="nama[]" class="form-control" 
                                                   value="<?php echo $_POST['nama'][$i] ?? ''; ?>" 
                                                   placeholder="Nama sesuai KTP/Identitas" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">NPM <span class="text-danger">*</span></label>
                                            <input type="text" name="npm[]" class="form-control" 
                                                   value="<?php echo $_POST['npm'][$i] ?? ''; ?>" 
                                                   placeholder="Contoh: 12345678" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email[]" class="form-control" 
                                                   value="<?php echo $_POST['email'][$i] ?? ''; ?>" 
                                                   placeholder="email@contoh.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">No. WhatsApp <span class="text-danger">*</span></label>
                                            <input type="tel" name="wa[]" class="form-control" 
                                                   value="<?php echo $_POST['wa'][$i] ?? ''; ?>" 
                                                   placeholder="081234567890" required>
                                            <div class="form-text">Pastikan nomor aktif untuk konfirmasi</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Jurusan/Fakultas</label>
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
                        <div class="d-flex gap-3 mt-4">
                            <button type="button" class="btn-solid-success" id="tambahAnggota">
                                <i class="fas fa-plus-circle me-2"></i>Tambah Anggota
                            </button>
                            <button type="button" class="btn-solid-danger" id="hapusAnggota">
                                <i class="fas fa-minus-circle me-2"></i>Hapus Anggota
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- BUKTI PEMBAYARAN (Hanya untuk event berbayar) -->
                    <?php if ($berbayar): ?>
                    <div class="form-section-solid">
                        <h4 class="section-title-solid">
                            <i class="fas fa-file-invoice-dollar"></i> Pembayaran & Verifikasi
                        </h4>
                        
                        <!-- INFO REKENING -->
                        <div class="warning-box-solid">
                            <h5 class="mb-3"><i class="fas fa-university me-2"></i>Transfer ke Rekening Resmi:</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="rekening-box-solid">
                                        <div class="rekening-item-solid">
                                            <div>
                                                <strong>Bank Maybank</strong><br>
                                                <small class="text-muted">Kantor Cabang Utama</small>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="mb-1">8787933492</h5>
                                                <small class="text-muted">Amadeo Duscha R</small>
                                            </div>
                                        </div>
                                        <button type="button" class="copy-btn-solid w-100 mt-2" data-number="8787933492">
                                            <i class="fas fa-copy me-2"></i>Salin No. Rekening
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rekening-box-solid">
                                        <div class="rekening-item-solid">
                                            <div>
                                                <strong>Bank BCA</strong><br>
                                                <small class="text-muted">Kantor Cabang Utama</small>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="mb-1">8211049634</h5>
                                                <small class="text-muted">Reyvandito Bassam C</small>
                                            </div>
                                        </div>
                                        <button type="button" class="copy-btn-solid w-100 mt-2" data-number="8211049634">
                                            <i class="fas fa-copy me-2"></i>Salin No. Rekening
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="success-box-solid mt-3">
                                <h6><i class="fas fa-lightbulb me-2"></i>Petunjuk Pembayaran:</h6>
                                <ol class="mb-0">
                                    <li>Transfer sesuai nominal: <strong class="text-warning">Rp <?php echo number_format($biaya, 0, ',', '.'); ?></strong></li>
                                    <li>Tambah angka unik: <strong><?php echo rand(1, 999); ?></strong> untuk memudahkan verifikasi</li>
                                    <li>Upload bukti transfer dengan format JPG, PNG, atau PDF (maks. 5MB)</li>
                                    <li>Pastikan bukti transfer terbaca dengan jelas (nama pengirim, nominal, waktu)</li>
                                </ol>
                            </div>
                        </div>
                        
                        <!-- UPLOAD AREA -->
                        <div class="upload-area-solid" id="uploadArea">
                            <div class="upload-icon-solid">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4 class="mb-2">Upload Bukti Pembayaran</h4>
                            <p class="text-muted mb-4">Drag & drop file atau klik untuk memilih</p>
                            <p class="text-muted mb-4">Format: JPG, PNG, PDF | Maks: 5MB</p>
                            
                            <input type="file" name="bukti_pembayaran" id="buktiPembayaran" 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf" hidden required>
                            
                            <button type="button" class="btn-solid-primary" onclick="document.getElementById('buktiPembayaran').click()">
                                <i class="fas fa-folder-open me-2"></i>Pilih File
                            </button>
                        </div>
                        
                        <!-- PREVIEW -->
                        <div class="preview-container-solid" id="previewContainer" style="display: none;">
                            <button type="button" class="btn-remove-file-solid" id="removeFile">
                                <i class="fas fa-times"></i>
                            </button>
                            <img src="" alt="Preview" class="preview-image-solid" id="previewImage">
                            <div id="fileInfo" class="file-info mt-3"></div>
                        </div>
                        
                        <!-- INSTRUCTION -->
                        <div class="info-box-solid mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Penting:</strong> Pendaftaran Anda akan diproses setelah bukti pembayaran diverifikasi oleh panitia. 
                            Proses verifikasi maksimal 1x24 jam. Kode pendaftaran akan dikirim via WhatsApp dan Email setelah verifikasi berhasil.
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- KONFIRMASI & SUBMIT -->
                    <div class="form-section-solid" style="border-bottom: none;">
                        <h4 class="section-title-solid">
                            <i class="fas fa-check-circle"></i> Konfirmasi Akhir
                        </h4>
                        
                        <div class="warning-box-solid mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                <label class="form-check-label fw-bold" for="agreeTerms">
                                    <i class="fas fa-check-square me-2"></i>
                                    Saya menyetujui syarat dan ketentuan yang berlaku. Data yang saya berikan adalah benar dan dapat dipertanggungjawabkan.
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn-solid-primary" style="font-size: 1.2rem; padding: 15px 50px;">
                                <i class="fas fa-paper-plane me-2"></i>
                                <?php echo $tipe == 'tim' ? 'DAFTARKAN TIM' : 'DAFTAR SEKARANG'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== FOOTER SAMA DENGAN INDEX.PHP ===== -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Politeknik Negeri Batam</h5>
                    <p>Jl. Ahmad Yani, Batam Kota, Batam 29461</p>
                    <p>Kepulauan Riau, Indonesia</p>
                    <p>Telp: (0778) 469856</p>
                    <p>Email: info@polibatam.ac.id</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="berita.php">Berita Kampus</a></li>
                        <li><a href="event.php">Event & Kegiatan</a></li>
                        <li><a href="admin/login.php">Panel Admin</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Ikuti Kami</h5>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2025 Portal Informasi Kampus - Politeknik Negeri Batam</p>
                <small>
                    Halaman Pendaftaran Event | 
                    Event: <?php echo htmlspecialchars($event['judul']); ?> |
                    <?php echo date('d/m/Y H:i:s'); ?>
                </small>
            </div>
        </div>
    </footer>

    <!-- SCRIPTS (sama seperti sebelumnya) -->
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
        
        // FUNGSI SELECT TIPE
        function selectTipe(tipe) {
            document.querySelectorAll('.tipe-card-solid').forEach(card => {
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
                anggotaTitle.textContent = 'Data Diri Peserta';
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
                    <div class="anggota-card-solid" data-index="${i}">
                        <div class="anggota-counter-solid">${i + 1}</div>
                        
                        ${tipeEvent === 'tim' || document.querySelector('input[name="tipe_daftar"]:checked')?.value === 'tim' ? `
                        <div class="anggota-header-solid">
                            <span class="anggota-label-solid">Anggota ${i + 1}</span>
                            ${isKetua ? '<span class="badge-ketua-solid">KETUA TIM</span>' : ''}
                        </div>
                        ` : ''}
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama[]" class="form-control" 
                                           placeholder="Nama sesuai KTP/Identitas" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">NPM <span class="text-danger">*</span></label>
                                    <input type="text" name="npm[]" class="form-control" 
                                           placeholder="Contoh: 12345678" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email[]" class="form-control" 
                                           placeholder="email@contoh.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">No. WhatsApp <span class="text-danger">*</span></label>
                                    <input type="tel" name="wa[]" class="form-control" 
                                           placeholder="081234567890" required>
                                    <div class="form-text">Pastikan nomor aktif untuk konfirmasi</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Jurusan/Fakultas</label>
                                    <input type="text" name="jurusan[]" class="form-control" 
                                           placeholder="Contoh: Teknik Informatika">
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
        
        // UPLOAD BUKTI PEMBAYARAN (sama seperti sebelumnya)
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
        document.querySelectorAll('.copy-btn-solid').forEach(button => {
            button.addEventListener('click', function() {
                const number = this.getAttribute('data-number');
                navigator.clipboard.writeText(number).then(() => {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
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
                    uploadArea.style.borderColor = 'var(--danger-color)';
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
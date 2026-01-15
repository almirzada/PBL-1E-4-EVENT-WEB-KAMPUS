<?php
require_once 'koneksi.php';

// ambil ID event dari URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit();
}

// ambil data event dari database
$query = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon, 
          a.nama as admin_nama 
          FROM events e 
          LEFT JOIN kategori k ON e.kategori_id = k.id 
          LEFT JOIN admin_event a ON e.created_by = a.id 
          WHERE e.id = $id AND e.status = 'publik'";

$result = mysqli_query($conn, $query);
$event = mysqli_fetch_assoc($result);

// kalau event tidak ditemukan atau tidak publik
if (!$event) {
    header('Location: index.php');
    exit();
}

// update view counter
mysqli_query($conn, "UPDATE events SET views = views + 1 WHERE id = $id");

// Cek apakah event perlu pendaftaran
$perlu_pendaftaran = $event['perlu_pendaftaran'] ?? 1;

// hitung status pendaftaran berdasarkan batas_pendaftaran (hanya jika perlu pendaftaran)
$today = date('Y-m-d');
$event_date = $event['tanggal'];
$batas_pendaftaran = $event['batas_pendaftaran'] ?? $event_date; // Jika null, gunakan tanggal event

// Default values
$pendaftaran_dibuka = true;
$status_pendaftaran = "Buka";
$status_class = "open"; // Untuk styling CSS
$badge_color = "success";
$hitung_mundur = "";

if ($perlu_pendaftaran) {
    if ($batas_pendaftaran) {
        if ($today > $batas_pendaftaran) {
            $pendaftaran_dibuka = false;
            $status_pendaftaran = "Ditutup";
            $status_class = "closed";
            $badge_color = "danger";
            $days_passed = floor((strtotime($today) - strtotime($batas_pendaftaran)) / (60 * 60 * 24));
            $hitung_mundur = "Ditutup {$days_passed} hari yang lalu";
        } else {
            $days_left = floor((strtotime($batas_pendaftaran) - strtotime($today)) / (60 * 60 * 24));
            if ($days_left == 0) {
                $status_pendaftaran = "Tutup Hari Ini";
                $status_class = "warning";
                $badge_color = "warning";
                $hitung_mundur = "Hari terakhir pendaftaran!";
            } else if ($days_left <= 3) {
                $status_pendaftaran = "Segera Tutup";
                $status_class = "warning";
                $badge_color = "warning";
                $hitung_mundur = "{$days_left} hari lagi";
            } else {
                $status_pendaftaran = "Masih Dibuka";
                $status_class = "open";
                $badge_color = "success";
                $hitung_mundur = "{$days_left} hari lagi";
            }
        }
    } else {
        // Jika tidak ada batas pendaftaran
        $status_pendaftaran = "Buka (Sampai Hari H)";
        $status_class = "open";
        $badge_color = "success";
        $hitung_mundur = "Pendaftaran sampai hari pelaksanaan";
    }
} else {
    // Event tidak perlu pendaftaran
    $status_pendaftaran = "Tidak Perlu Daftar";
    $status_class = "open";
    $badge_color = "info";
    $hitung_mundur = "Acara terbuka untuk umum";
    $pendaftaran_dibuka = false; // Tidak ada tombol daftar
}

// Cek apakah event masih berjalan
$event_berjalan = ($today <= $event_date);

// Ambil event di kategori yang sama
$related_query = "SELECT * FROM events 
                  WHERE kategori_id = {$event['kategori_id']} 
                  AND id != $id 
                  AND status = 'publik' 
                  ORDER BY created_at DESC 
                  LIMIT 3";
$related_result = mysqli_query($conn, $related_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['judul']); ?> - Event Kampus</title>
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
            --status-open: #28a745;
            --status-warning: #ffc107;
            --status-closed: #dc3545;
            --status-info: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background: linear-gradient(to bottom, #f0f1f3ff 80%, #ffffff 100%);
        }

        .navbar {
            background-color: var(--primary-color);
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

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* HEADER EVENT DENGAN BACKGROUND GAMBAR */
        .event-header {
            background: 
                linear-gradient(rgba(0, 86, 179, 0.85), rgba(0, 61, 130, 0.9)),
                url('https://www.polibatam.ac.id/wp-content/uploads/2023/05/Gedung.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            border-bottom: 5px solid #ffc107;
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.3);
        }

        .event-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.05) 30%, rgba(255, 255, 255, 0.05) 70%, transparent 70%);
            pointer-events: none;
        }

        .event-header-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .event-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            line-height: 1.2;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 18px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .meta-item:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .meta-item i {
            color: #ffc107;
            font-size: 1.1rem;
        }

        /* STATUS PENDAFTARAN BOX - WARNA SOLID */
        .status-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border: 3px solid;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        /* Garis dekoratif di atas */
        .status-box::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
        }

        /* WARNA STATUS - SOLID */
        .status-open {
            border-color: #28a745 !important;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
        }

        .status-warning {
            border-color: #ffc107 !important;
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
        }

        .status-closed {
            border-color: #dc3545 !important;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.15) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
        }

        .status-info {
            border-color: #17a2b8 !important;
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.15) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
        }

        .status-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #2c3e50;
            position: relative;
            z-index: 2;
        }

        .status-countdown {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 15px 0;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }

        /* WARNA TEKS STATUS - SOLID */
        .text-status-open {
            color: #28a745 !important;
        }

        .text-status-warning {
            color: #ffc107 !important;
        }

        .text-status-closed {
            color: #dc3545 !important;
        }

        .text-status-info {
            color: #17a2b8 !important;
        }

        /* STATUS BADGE - WARNA SOLID */
        .status-badge {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 700;
            color: white;
            font-size: 1.2rem;
            margin: 5px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .badge-open {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .badge-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #212529 !important;
        }

        .badge-closed {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
        }

        .badge-info {
            background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);
        }
        /* CATEGORY BADGE - Warna sesuai database */
.category-badge {
    display: inline-flex;
    align-items: center;
    background: var(--primary-color);
    color: white !important;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    transition: all 0.3s;
    min-height: 45px;
}

.category-badge i {
    font-size: 1.1rem;
    opacity: 0.9;
}

.category-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
}

/* REGISTRATION TYPE BADGE - Warna konsisten */
.registration-type-badge {
    display: inline-flex;
    align-items: center;
    color: white;
    padding: 10px 18px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.95rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.26);
    backdrop-filter: blur(10px);
    transition: all 0.3s;
    min-height: 45px;
}

.registration-type-badge i {
    font-size: 1.1rem;
    opacity: 0.9;
}

/* Warna khusus untuk tipe tim */
.type-team {
    background: linear-gradient(135deg, #ff6b35 0%, #f39c12 100%) !important;
    color: white !important;
}

/* Warna khusus untuk tipe individu/tim */
.type-individual-team {
    background: linear-gradient(135deg, #3498db 0%, #2ecc71 100%) !important;
    color: white !important;
}

.registration-type-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}
        /* Hitung mundur style */
        .hitung-mundur {
            font-size: 1.1rem;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: #212529;
            border: 2px solid;
        }

        .hitung-open {
            border-color: #28a745;
        }

        .hitung-warning {
            border-color: #ffc107;
        }

        .hitung-closed {
            border-color: #dc3545;
        }

        .hitung-info {
            border-color: #17a2b8;
        }

        /* EVENT BODY */
        .event-body {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 40px;
            border: 1px solid #eaeaea;
        }

        .event-poster {
            width: 100%;
            max-width: 900px;
            height: auto;
            max-height: 650px;
            object-fit: contain;
            border-radius: 15px;
            margin: 0 auto 40px;
            display: block;
            border: 5px solid white;
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
            outline: 3px solid var(--primary-color);
        }

        .event-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
        }

        /* TAMBAHKAN INI di bagian CSS - Styling untuk konten Summernote */
        .event-description img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 15px 0;
        }

        .event-description table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .event-description table, 
        .event-description th, 
        .event-description td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .event-description th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .event-description blockquote {
            border-left: 4px solid #4361ee;
            padding-left: 15px;
            margin: 20px 0;
            font-style: italic;
            color: #666;
        }

        .event-description ul,
        .event-description ol {
            padding-left: 20px;
            margin: 15px 0;
        }

        .event-description li {
            margin-bottom: 5px;
        }

        .event-description h1,
        .event-description h2,
        .event-description h3,
        .event-description h4,
        .event-description h5,
        .event-description h6 {
            color: var(--primary-color);
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .event-description h1 { font-size: 2rem; }
        .event-description h2 { font-size: 1.75rem; }
        .event-description h3 { font-size: 1.5rem; }
        .event-description h4 { font-size: 1.25rem; }
        .event-description h5 { font-size: 1.1rem; }
        .event-description h6 { font-size: 1rem; }

        .event-description a {
            color: var(--primary-color);
            text-decoration: none;
            border-bottom: 1px dotted var(--primary-color);
        }

        .event-description a:hover {
            color: var(--primary-dark);
            border-bottom: 1px solid var(--primary-dark);
        }

        .event-description .text-center {
            text-align: center;
        }

        .event-description .text-right {
            text-align: right;
        }

        .event-description .text-justify {
            text-align: justify;
        }

        .event-description pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4361ee;
            overflow-x: auto;
        }

        .event-description code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        .event-description iframe {
            max-width: 100%;
            border-radius: 8px;
            margin: 15px 0;
        }

        /* INFO BOX */
        .info-box {
            background: var(--gray-light);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .info-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }

        /* BADGE */
        .event-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        /* TOMBOL DAFTAR */
        .btn-daftar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: 700;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-daftar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.5);
            color: white;
        }

        .btn-daftar:disabled {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* TOMBOL TIDAK PERLU DAFTAR */
        .btn-tidak-perlu-daftar {
            background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: 700;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(23, 162, 184, 0.4);
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-tidak-perlu-daftar:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(23, 162, 184, 0.5);
            color: white;
        }

        /* TIM BADGE */
        .tim-badge {
            background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 10px;
        }

        /* RELATED EVENTS */
        .related-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #eaeaea;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .related-card img {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }

        .related-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 10px 0;
            line-height: 1.4;
        }

        /* FOOTER */
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

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .event-header {
                padding: 60px 0 30px;
                background-attachment: scroll;
            }
            
            .event-title {
                font-size: 2rem;
            }
            
            .event-body {
                padding: 25px;
            }
            
            .event-meta {
                gap: 10px;
            }
            
            .meta-item {
                font-size: 0.9rem;
                padding: 6px 12px;
            }
            
            .status-countdown {
                font-size: 1.5rem;
            }
        }

        /* ANIMASI */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* BORDER RADIUS KONSISTEN */
        .rounded-section {
            border-radius: 10px;
        }

        /* SHADOW KONSISTEN */
        .shadow-soft {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png"
                    alt="Politeknik Negeri Batam">
            </a>
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

    <!-- EVENT HEADER -->
    <div class="event-header">
        <div class="container">
            <div class="event-header-content">
              <!-- BADGE KATEGORI -->
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <!-- Badge Kategori Utama -->
    <div class="category-badge" style="background: <?php echo $event['warna'] ?? '#0056b3'; ?>;">
        <i class="<?php echo $event['ikon'] ?? 'fas fa-calendar'; ?> me-2"></i>
        <?php echo htmlspecialchars($event['kategori_nama'] ?? 'Event Kampus'); ?>
    </div>
    
    <!-- Badge Tipe Pendaftaran (hanya tampil jika perlu pendaftaran dan ada tipe pendaftaran) -->
    <?php if ($perlu_pendaftaran && ($event['tipe_pendaftaran'] == 'tim' || $event['tipe_pendaftaran'] == 'individu_tim')): ?>
    <div class="registration-type-badge <?php echo $event['tipe_pendaftaran'] == 'tim' ? 'type-team' : 'type-individual-team'; ?>">
        <i class="fas fa-users me-2"></i>
        <?php 
        if ($event['tipe_pendaftaran'] == 'tim') {
            echo 'Wajib Tim';
        } else {
            echo 'Bisa Individu/Tim';
        }
        ?>
    </div>
    <?php endif; ?>
    
    <!-- Badge Tidak Perlu Pendaftaran -->
    <?php if (!$perlu_pendaftaran): ?>
    <div class="registration-type-badge" style="background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);">
        <i class="fas fa-door-open me-2"></i>
        Tidak Perlu Daftar
    </div>
    <?php endif; ?>
</div>

                <!-- JUDUL -->
                <h1 class="event-title"><?php echo htmlspecialchars($event['judul']); ?></h1>

                <!-- META INFO -->
                <div class="event-meta">
                    <div class="meta-item">
                        <i class="far fa-calendar-alt"></i>
                        <span><?php echo date('d F Y', strtotime($event['tanggal'])); ?></span>
                    </div>
                    
                    <?php if (!empty($event['waktu'])): ?>
                    <div class="meta-item">
                        <i class="far fa-clock"></i>
                        <span>Pukul <?php echo date('H:i', strtotime($event['waktu'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($event['lokasi']); ?></span>
                    </div>
                    
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?php echo $event['views'] + 1; ?> dilihat</span>
                    </div>
                </div>

                <!-- STATUS PENDAFTARAN -->
                <div class="status-box status-<?php echo $status_class; ?>">
                    <div class="status-title">
                        <i class="fas fa-user-clock"></i>
                        <?php echo $perlu_pendaftaran ? 'Status Pendaftaran' : 'Status Kehadiran'; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <!-- STATUS BADGE DENGAN WARNA SOLID -->
                            <div class="status-badge badge-<?php echo $status_class; ?>">
                                <?php echo $status_pendaftaran; ?>
                            </div>
                            <div class="mt-2">
                                <span class="text-muted">
                                    <i class="far fa-calendar-check me-1"></i>
                                    <?php if ($perlu_pendaftaran): ?>
                                        <?php if ($batas_pendaftaran && $batas_pendaftaran != $event_date): ?>
                                            Batas pendaftaran: <?php echo date('d F Y', strtotime($batas_pendaftaran)); ?>
                                        <?php else: ?>
                                            <i class="fas fa-infinity me-1"></i>
                                            Pendaftaran sampai hari H
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Acara terbuka untuk umum - Datang langsung!
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <!-- HITUNG MUNDUR DENGAN WARNA SOLID -->
                            <div class="hitung-mundur hitung-<?php echo $status_class; ?>">
                                <strong><?php echo $hitung_mundur; ?></strong>
                            </div>
                            <?php if ($perlu_pendaftaran && $event['tipe_pendaftaran'] == 'tim'): ?>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-users me-1"></i>
                                <?php echo $event['min_anggota'] . '-' . $event['max_anggota']; ?> orang/tim
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL AKSI -->
                <?php if ($perlu_pendaftaran): ?>
                    <?php if ($event_berjalan && $pendaftaran_dibuka): ?>
                    <a href="daftar.php?id=<?php echo $event['id']; ?>" class="btn-daftar pulse">
                        <i class="fas fa-user-plus me-2"></i> DAFTAR SEKARANG
                    </a>
                    <?php elseif (!$pendaftaran_dibuka): ?>
                    <button class="btn-daftar" disabled>
                        <i class="fas fa-lock me-2"></i> PENDAFTARAN DITUTUP
                    </button>
                    <?php else: ?>
                    <button class="btn-daftar" disabled>
                        <i class="fas fa-calendar-times me-2"></i> EVENT SUDAH BERAKHIR
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Event tidak perlu pendaftaran -->
                    <button class="btn-tidak-perlu-daftar pulse">
                        <i class="fas fa-door-open me-2"></i> ACARA TERBUKA UMUM
                    </button>
                    <div class="mt-3 text-white">
                        <small><i class="fas fa-info-circle me-2"></i> Acara ini tidak memerlukan pendaftaran. Datang langsung sesuai jadwal!</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container">
        <div class="row">
            <!-- EVENT DETAIL -->
            <div class="col-lg-8">
                <div class="event-body">
                    <!-- POSTER -->
                    <?php if (!empty($event['poster'])): ?>
                    <img src="<?php echo htmlspecialchars($event['poster']); ?>" 
                         alt="<?php echo htmlspecialchars($event['judul']); ?>" 
                         class="event-poster">
                    <?php endif; ?>

                    <!-- DESKRIPSI SINGKAT -->
                    <?php if (!empty($event['deskripsi_singkat'])): ?>
                    <div class="alert alert-info rounded-section">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo htmlspecialchars($event['deskripsi_singkat']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- DESKRIPSI LENGKAP -->
                    <div class="event-description">
                        <?php 
                        // Dekode HTML entities tapi filter script berbahaya
                        $deskripsi = htmlspecialchars_decode($event['deskripsi']);
                        
                        // Hilangkan tag script berbahaya
                        $deskripsi = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $deskripsi);
                        $deskripsi = preg_replace('/on\w+=\s*"[^"]*"\s*/i', '', $deskripsi);
                        $deskripsi = preg_replace('/on\w+=\s*\'[^\']*\'\s*/i', '', $deskripsi);
                        
                        echo $deskripsi; 
                        ?>
                    </div>

                    <!-- INFO BOX -->
                    <div class="info-box shadow-soft">
                        <h4 class="info-title">
                            <i class="fas fa-info-circle"></i> Informasi Detail Event
                        </h4>
                        
                        <div class="row">
                            <?php if (!empty($event['alamat_lengkap'])): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-location-dot mt-1" style="color: var(--primary-color);"></i>
                                    <div>
                                        <strong>Alamat Lengkap:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($event['alamat_lengkap']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['contact_person'])): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-user mt-1" style="color: var(--primary-color);"></i>
                                    <div>
                                        <strong>Contact Person:</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($event['contact_person']); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['contact_wa'])): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fab fa-whatsapp mt-1" style="color: #25D366;"></i>
                                    <div>
                                        <strong>WhatsApp:</strong>
                                        <p class="mb-0">
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $event['contact_wa']); ?>" 
                                               target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                                <i class="fab fa-whatsapp me-1"></i>
                                                <?php echo htmlspecialchars($event['contact_wa']); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($perlu_pendaftaran && $event['kuota_peserta'] > 0): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-users mt-1" style="color: var(--primary-color);"></i>
                                    <div>
                                        <strong>Kuota Peserta:</strong>
                                        <p class="mb-0">
                                            <?php echo number_format($event['kuota_peserta'], 0, ',', '.'); ?> orang
                                            <?php 
                                            if ($event['total_pendaftar'] > 0) {
                                                $persen = ($event['total_pendaftar'] / $event['kuota_peserta']) * 100;
                                                echo '<span class="badge bg-info ms-2">' . round($persen) . '% terisi</span>';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php elseif (!$perlu_pendaftaran): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-users mt-1" style="color: #17a2b8;"></i>
                                    <div>
                                        <strong>Kuota Peserta:</strong>
                                        <p class="mb-0">
                                            <span class="text-info">Tidak Terbatas</span>
                                            <br>
                                            <small class="text-muted">Acara terbuka untuk umum</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($perlu_pendaftaran && $event['biaya_pendaftaran'] > 0): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-money-bill-wave mt-1" style="color: var(--primary-color);"></i>
                                    <div>
                                        <strong>Biaya Pendaftaran:</strong>
                                        <p class="mb-0">Rp <?php echo number_format($event['biaya_pendaftaran'], 0, ',', '.'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php elseif (!$perlu_pendaftaran): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-money-bill-wave mt-1" style="color: #17a2b8;"></i>
                                    <div>
                                        <strong>Biaya Kehadiran:</strong>
                                        <p class="mb-0">
                                            <span class="text-info">GRATIS</span>
                                            <br>
                                            <small class="text-muted">Tidak ada biaya kehadiran</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- INFO TIM (hanya jika perlu pendaftaran) -->
                            <?php if ($perlu_pendaftaran && ($event['tipe_pendaftaran'] == 'tim' || $event['tipe_pendaftaran'] == 'individu_tim')): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-user-friends mt-1" style="color: #6f42c1;"></i>
                                    <div>
                                        <strong>Sistem Pendaftaran:</strong>
                                        <p class="mb-0">
                                            <?php 
                                            if ($event['tipe_pendaftaran'] == 'tim') {
                                                echo 'Wajib Tim';
                                            } else {
                                                echo 'Bisa Individu atau Tim';
                                            }
                                            ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php 
                                                if ($event['tipe_pendaftaran'] == 'tim') {
                                                    echo 'Minimal ' . $event['min_anggota'] . ' orang, maksimal ' . $event['max_anggota'] . ' orang per tim';
                                                } else if ($event['tipe_pendaftaran'] == 'individu_tim') {
                                                    echo 'Individu atau tim ' . $event['min_anggota'] . '-' . $event['max_anggota'] . ' orang';
                                                }
                                                ?>
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- BATAS PENDAFTARAN DETAIL (hanya jika perlu pendaftaran) -->
                            <?php if ($perlu_pendaftaran): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-calendar-times mt-1" style="color: var(--primary-color);"></i>
                                    <div>
                                        <strong>Batas Pendaftaran:</strong>
                                        <p class="mb-0">
                                            <?php 
                                            if ($batas_pendaftaran && $batas_pendaftaran != $event_date) {
                                                echo date('d F Y', strtotime($batas_pendaftaran));
                                                echo '<br><span class="text-status-' . $status_class . '">';
                                                if ($pendaftaran_dibuka) {
                                                    echo '✓ Masih bisa mendaftar';
                                                } else {
                                                    echo '✗ Pendaftaran sudah ditutup';
                                                }
                                                echo '</span>';
                                            } else {
                                                echo 'Sampai hari pelaksanaan event';
                                                echo '<br><span class="text-info">✓ Pendaftaran dibuka sampai hari H</span>';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <!-- INFO TIDAK PERLU PENDAFTARAN -->
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fas fa-door-open mt-1" style="color: #17a2b8;"></i>
                                    <div>
                                        <strong>Sistem Kehadiran:</strong>
                                        <p class="mb-0">
                                            <span class="text-info">Tidak Perlu Pendaftaran</span>
                                            <br>
                                            <small class="text-muted">Datang langsung sesuai jadwal yang tertera</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- TOMBOL SHARE -->
                    <div class="d-flex gap-3 mt-4">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="btn btn-outline-primary rounded-pill">
                            <i class="fab fa-facebook me-2"></i> Share ke Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&text=<?php echo urlencode($event['judul']); ?>" 
                           target="_blank" class="btn btn-outline-info rounded-pill">
                            <i class="fab fa-twitter me-2"></i> Share ke Twitter
                        </a>
                        <a href="whatsapp://send?text=<?php echo urlencode($event['judul'] . " - " . "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                           target="_blank" class="btn btn-outline-success rounded-pill">
                            <i class="fab fa-whatsapp me-2"></i> Share ke WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <!-- INFO PENYELENGGARA -->
                <div class="card border-0 shadow-sm mb-4 rounded-section">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-university me-2"></i> Diselenggarakan Oleh
                        </h5>
                        <p class="card-text">
                            <strong>Politeknik Negeri Batam</strong><br>
                            Event ini dikelola oleh <?php echo htmlspecialchars($event['admin_nama'] ?? 'Administrator Kampus'); ?>
                        </p>
                        <p class="text-muted small">
                            <i class="far fa-calendar-plus me-1"></i>
                            Diposting: <?php echo date('d F Y', strtotime($event['created_at'])); ?>
                        </p>
                        <?php if ($event['featured']): ?>
                        <div class="mt-2">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-star me-1"></i> Event Unggulan
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!$perlu_pendaftaran): ?>
                        <div class="mt-2">
                            <span class="badge bg-info">
                                <i class="fas fa-door-open me-1"></i> Tidak Perlu Daftar
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- EVENT TERKAIT -->
                <?php if (mysqli_num_rows($related_result) > 0): ?>
                <div class="card border-0 shadow-sm rounded-section">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-link me-2"></i> Event Terkait
                        </h5>
                        <div class="row g-3">
                            <?php while ($related = mysqli_fetch_assoc($related_result)): 
                                $related_batas = $related['batas_pendaftaran'] ?? $related['tanggal'];
                                $related_perlu_pendaftaran = $related['perlu_pendaftaran'] ?? 1;
                                
                                if ($related_perlu_pendaftaran) {
                                    $related_status = (date('Y-m-d') <= $related_batas) ? 'success' : 'danger';
                                    $related_status_text = (date('Y-m-d') <= $related_batas) ? 'Buka' : 'Tutup';
                                } else {
                                    $related_status = 'info';
                                    $related_status_text = 'Terbuka';
                                }
                            ?>
                            <div class="col-12">
                                <a href="detail_event.php?id=<?php echo $related['id']; ?>" 
                                   class="text-decoration-none">
                                    <div class="d-flex gap-2 p-2 rounded hover-shadow" style="background: #f8f9fa;">
                                        <?php if (!empty($related['poster'])): ?>
                                        <img src="<?php echo htmlspecialchars($related['poster']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                        <?php endif; ?>
                                        <div style="flex: 1;">
                                            <h6 class="related-title mb-1">
                                                <?php echo htmlspecialchars(mb_substr($related['judul'], 0, 40)); ?>
                                                <?php echo (mb_strlen($related['judul']) > 40) ? '...' : ''; ?>
                                            </h6>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="far fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($related['tanggal'])); ?>
                                                </small>
                                                <span class="badge bg-<?php echo $related_status; ?>">
                                                    <?php echo $related_status_text; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- TOMBOL KEMBALI -->
                <div class="mt-4">
                    <a href="event.php" class="btn btn-outline-primary w-100 rounded-pill">
                        <i class="fas fa-arrow-left me-2"></i> Lihat Semua Event
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
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
                <small>Halaman detail event | Terakhir dilihat: <?php echo date('H:i:s'); ?></small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Back to top button
        const backToTop = document.createElement('button');
        backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTop.className = 'btn btn-primary rounded-circle position-fixed shadow';
        backToTop.style.cssText = 'bottom: 20px; right: 20px; width: 50px; height: 50px; display: none; z-index: 1000;';
        document.body.appendChild(backToTop);

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', () => {
            backToTop.style.display = window.scrollY > 300 ? 'block' : 'none';
        });

        // Print page
        function printPage() {
            window.print();
        }

        // Animasi untuk status pendaftaran
        const statusBox = document.querySelector('.status-box');
        const statusBadge = document.querySelector('.status-badge');
        
        if (statusBadge && (statusBadge.classList.contains('badge-warning') || 
            statusBadge.textContent.includes('Hari Ini') || 
            statusBadge.textContent.includes('Segera') ||
            statusBadge.textContent.includes('Info'))) {
            
            // Animasi berkedip untuk status warning
            setInterval(() => {
                statusBadge.style.opacity = statusBadge.style.opacity === '0.8' ? '1' : '0.8';
            }, 1000);
            
            // Animasi pulsing untuk button
            const actionBtn = document.querySelector('.btn-daftar, .btn-tidak-perlu-daftar');
            if (actionBtn && !actionBtn.disabled) {
                actionBtn.classList.add('pulse');
            }
        }

        // Share buttons enhancement
        document.querySelectorAll('.btn-outline-primary, .btn-outline-info, .btn-outline-success').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi
mysqli_close($conn);
?>
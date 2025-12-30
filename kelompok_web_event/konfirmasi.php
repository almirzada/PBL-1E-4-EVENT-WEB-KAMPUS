<?php
session_start();
$id_tim = $_GET['id'] ?? 0;

if (!$id_tim) {
    header('Location: daftar.php');
    exit();
}

require_once 'koneksi.php';

// Ambil data tim
$sql = "SELECT * FROM tim_lomba WHERE id_tim = $id_tim";
$result = $conn->query($sql);
$tim = $result->fetch_assoc();

if (!$tim) {
    die("Tim tidak ditemukan!");
}

// Ambil data anggota
$anggota_result = $conn->query("SELECT * FROM anggota_tim WHERE id_tim = $id_tim");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Berhasil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .success-icon {
            text-align: center;
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .tim-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .btn-dashboard {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 style="text-align: center; color: #28a745;">✅ Pendaftaran Berhasil!</h1>
        
        <div class="tim-info">
            <h3><i class="fas fa-info-circle"></i> Detail Tim</h3>
            <p><strong>ID Tim:</strong> <?= $tim['id_tim'] ?></p>
            <p><strong>Nama Ketua:</strong> <?= htmlspecialchars($tim['nama_tim']) ?> (NIM: <?= $tim['ketua_nim'] ?>)</p>
            <p><strong>Jenis Lomba:</strong> <?= htmlspecialchars($tim['jenis_lomba']) ?></p>
            <p><strong>Nama tim:</strong> <?= htmlspecialchars($tim['ketua_nama']) ?></p>
            <p><strong>Status:</strong> <span style="color: #f39c12; font-weight: bold;">MENUNGGU VERIFIKASI</span></p>
            <p><strong>Tanggal Daftar:</strong> <?= date('d/m/Y H:i', strtotime($tim['tanggal_daftar'])) ?></p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn-dashboard">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
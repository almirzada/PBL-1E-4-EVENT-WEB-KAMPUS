<?php
session_start();
header('Content-Type: application/json');

// PROTEKSI
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['level'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// KONEKSI
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$jenis = $_GET['jenis'] ?? '';

if ($jenis === 'tim') {
    // Reset tabel tim_lomba
    $result = $conn->query("TRUNCATE TABLE tim_lomba");
    echo json_encode(['success' => $result, 'message' => 'Data tim berhasil direset']);
    
} elseif ($jenis === 'anggota') {
    // Reset tabel anggota_tim
    $result = $conn->query("TRUNCATE TABLE anggota_tim");
    echo json_encode(['success' => $result, 'message' => 'Data anggota berhasil direset']);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Jenis reset tidak valid']);
}

$conn->close();
?>
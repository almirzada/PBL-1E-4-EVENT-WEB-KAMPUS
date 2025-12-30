<?php
// verifikasi.php
session_start();
require_once '../koneksi.php'; // Pastikan path koneksi benar

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Ambil parameter
$action = $_GET['action'] ?? '';
$id_tim = $_GET['id'] ?? 0;

if (!$id_tim || !in_array($action, ['terima', 'tolak', 'restore'])) {
    header("Location: dashboard.php");
    exit();
}

// Update status berdasarkan action
switch ($action) {
    case 'terima':
        $status = 'active';
        $message = 'Tim berhasil diterima!';
        break;
    
    case 'tolak':
        $status = 'rejected';
        $message = 'Tim berhasil ditolak!';
        break;
    
    case 'restore':
        $status = 'pending';
        $message = 'Tim berhasil dikembalikan ke pending!';
        break;
    
    default:
        header("Location: dashboard.php");
        exit();
}

// Update database
$sql = "UPDATE tim_lomba SET status = ? WHERE id_tim = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $id_tim);

if ($stmt->execute()) {
    // Set pesan sukses di session
    $_SESSION['alert_message'] = $message;
    $_SESSION['alert_type'] = 'success';
} else {
    $_SESSION['alert_message'] = 'Gagal update status: ' . $stmt->error;
    $_SESSION['alert_type'] = 'error';
}

// Redirect kembali ke dashboard
header("Location: dashboard.php");
exit();
?>
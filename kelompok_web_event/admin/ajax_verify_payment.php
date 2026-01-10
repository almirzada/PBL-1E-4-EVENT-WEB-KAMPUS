<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['admin_event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit();
}

$peserta_id = intval($_POST['id']);
$status = $_POST['status'] ?? '';
$note = $_POST['note'] ?? '';

// Validasi status
if (!in_array($status, ['terverifikasi', 'ditolak'])) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
    exit();
}

// Update status pembayaran di peserta
$query = "UPDATE peserta SET status_pembayaran = '$status' WHERE id = $peserta_id";

if (mysqli_query($conn, $query)) {
    // Update juga untuk tim jika ada
    // Cari dulu tim_id dari peserta
    $tim_query = "SELECT tim_id FROM peserta WHERE id = $peserta_id";
    $tim_result = mysqli_query($conn, $tim_query);
    $tim_data = mysqli_fetch_assoc($tim_result);
    
    if ($tim_data && $tim_data['tim_id']) {
        $tim_id = $tim_data['tim_id'];
        $update_tim = "UPDATE tim_event SET status_pembayaran = '$status' WHERE id = $tim_id";
        mysqli_query($conn, $update_tim);
    }
    
    // Log verifikasi (jika tabel log_pembayaran ada)
    $admin_id = $_SESSION['admin_event_id'];
    
    // Cek apakah tabel log_pembayaran ada
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'log_pembayaran'");
    if (mysqli_num_rows($check_table) > 0) {
        $log_query = "INSERT INTO log_pembayaran (peserta_id, admin_id, status_sesudah, catatan) 
                      VALUES ($peserta_id, $admin_id, '$status', '$note')";
        mysqli_query($conn, $log_query);
    }
    
    echo json_encode(['success' => true, 'message' => 'Berhasil memverifikasi']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
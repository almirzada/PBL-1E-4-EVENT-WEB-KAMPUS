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

// Update status pembayaran
$query = "UPDATE peserta SET 
          status_pembayaran = '$status',
          updated_at = NOW()
          WHERE id = $peserta_id";

if (mysqli_query($conn, $query)) {
    // Update juga untuk tim jika ada
    $tim_query = "UPDATE tim_event t 
                  JOIN peserta p ON t.id = p.tim_id 
                  SET t.status_pembayaran = '$status',
                      t.updated_at = NOW()
                  WHERE p.id = $peserta_id";
    mysqli_query($conn, $tim_query);
    
    // Log verifikasi
    $admin_id = $_SESSION['admin_event_id'];
    $log_query = "INSERT INTO log_pembayaran (peserta_id, admin_id, status_sesudah, catatan) 
                  VALUES ($peserta_id, $admin_id, '$status', '$note')";
    mysqli_query($conn, $log_query);
    
    echo json_encode(['success' => true, 'message' => 'Berhasil memverifikasi']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
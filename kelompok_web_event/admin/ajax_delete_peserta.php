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

// Cek apakah peserta ada
$check_query = "SELECT * FROM peserta WHERE id = $peserta_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Peserta tidak ditemukan']);
    exit();
}

// Hapus peserta
$delete_query = "DELETE FROM peserta WHERE id = $peserta_id";

if (mysqli_query($conn, $delete_query)) {
    // Update total pendaftar di event
    $event_query = "SELECT event_id FROM peserta WHERE id = $peserta_id";
    $event_result = mysqli_query($conn, $event_query);
    $event_data = mysqli_fetch_assoc($event_result);
    
    if ($event_data) {
        $event_id = $event_data['event_id'];
        $update_count = "UPDATE events SET total_pendaftar = GREATEST(0, total_pendaftar - 1) WHERE id = $event_id";
        mysqli_query($conn, $update_count);
    }
    
    echo json_encode(['success' => true, 'message' => 'Peserta berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
<?php
// hapus_event.php - VERSI SIMPLE (TANPA TABEL TERKAIT)
session_start();
require_once '../koneksi.php';

// PROTEKSI ADMIN
if (!isset($_SESSION['admin_event_id'])) {
    header("Location: login.php");
    exit();
}

$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$redirect_url = 'daftar_event.php';

if ($event_id <= 0) {
    $_SESSION['alert_message'] = 'ID Event tidak valid!';
    $_SESSION['alert_type'] = 'error';
    header("Location: $redirect_url");
    exit();
}

// AMBIL DATA EVENT UNTUK HAPUS POSTER
$query = "SELECT judul, poster FROM events WHERE id = $event_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['alert_message'] = 'Event tidak ditemukan!';
    $_SESSION['alert_type'] = 'error';
    header("Location: $redirect_url");
    exit();
}

$event = mysqli_fetch_assoc($result);

// HAPUS POSTER JIKA ADA
if (!empty($event['poster'])) {
    $poster_path = '../' . $event['poster'];
    if (file_exists($poster_path)) {
        unlink($poster_path);
    }
}

// HAPUS EVENT DARI DATABASE
$delete_query = "DELETE FROM events WHERE id = $event_id";

if (mysqli_query($conn, $delete_query)) {
    $_SESSION['alert_message'] = "✅ Event <strong>'{$event['judul']}'</strong> berhasil dihapus!";
    $_SESSION['alert_type'] = 'success';
} else {
    $_SESSION['alert_message'] = '❌ Gagal menghapus event: ' . mysqli_error($conn);
    $_SESSION['alert_type'] = 'error';
}

header("Location: $redirect_url");
exit();
?>
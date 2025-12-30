<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// KONEKSI
$conn = new mysqli("localhost", "root", "", "db_lomba");

$action = $_GET['action'] ?? '';
$id_tim = $_GET['id'] ?? 0;

if ($action && $id_tim) {
    if ($action == 'terima') {
        $conn->query("UPDATE tim SET status='verified', tanggal_verifikasi=NOW() WHERE id_tim=$id_tim");
    } elseif ($action == 'tolak') {
        $conn->query("UPDATE tim SET status='rejected' WHERE id_tim=$id_tim");
    } elseif ($action == 'restore') {
        $conn->query("UPDATE tim SET status='pending', tanggal_verifikasi=NULL WHERE id_tim=$id_tim");
    }
}

$conn->close();
header("Location: dashboard.php"); // LANGSUNG REDIRECT
exit();
?>
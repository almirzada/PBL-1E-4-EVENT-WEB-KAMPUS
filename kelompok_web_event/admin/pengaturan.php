<?php
session_start();
if (!isset($_SESSION['admin_event_id'])) {
    header('Location: login.php');
    exit();
}
?>
<h1>Pengaturan</h1>
<p>Halaman pengaturan akan dibuat nanti</p>
<a href="dashboard.php">← Kembali</a>
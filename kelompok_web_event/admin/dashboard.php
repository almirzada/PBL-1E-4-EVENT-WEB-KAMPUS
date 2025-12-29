<?php
session_start();
require_once '../config.php';

// 🔒 PROTEKSI HALAMAN
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// contoh ambil data admin (opsional)
$username = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../assets/images/polio.png" alt="Logo" height="50" class="me-2">
        </a>

        <!-- Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">Beranda</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="logout.php"
                       onclick="return confirm('Yakin mau logout?')">
                        Logout
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>


<div class="container mt-4">
    <div class="card shadow">
        <div class="card-body">
            <h4>Selamat datang, <?= htmlspecialchars($username) ?>!</h4>
            <p>Ini adalah halaman dashboard admin.</p>

            <hr>

            <ul>
                <li>Kelola event</li>
                <li>Kelola user</li>
                <li>Lihat laporan</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>

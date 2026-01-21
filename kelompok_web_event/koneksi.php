<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "event_kampus";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("DATABASE ERROR: " . mysqli_connect_error() . 
        "<br>Pastikan database 'event_kampus' sudah dibuat di phpMyAdmin!");
}

mysqli_set_charset($conn, "utf8mb4");

// Debug: cek apakah database ada
$result = mysqli_query($conn, "SHOW DATABASES LIKE 'event_kampus'");
if (mysqli_num_rows($result) == 0) {
    die("Database 'event_kampus' tidak ditemukan. Silakan buat database terlebih dahulu!");
}
?>
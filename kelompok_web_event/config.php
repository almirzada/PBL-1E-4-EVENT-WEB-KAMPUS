<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "pbl_event";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Gagal koneksi: " . mysqli_connect_error());
}

<?php
// config.php - Ambil semua pengaturan sistem


// KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

global $conn;
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// AMBIL SEMUA PENGATURAN
$pengaturan = [];
$result = $conn->query("SELECT nama_setting, nilai_setting FROM pengaturan_sistem");
while ($row = $result->fetch_assoc()) {
    $pengaturan[$row['nama_setting']] = $row['nilai_setting'];
}

// JIKA TABEL BELUM ADA, PAKE DEFAULT
if (empty($pengaturan)) {
    $pengaturan = [
        'nama_sistem' => 'Sistem Pendaftaran Lomba',
        'instansi' => 'Politeknik Negeri Batam',
        'tahun_ajaran' => '2025/2026',
        'status_pendaftaran' => 'buka',
        'kuota_futsal' => '16',
        'kuota_basket' => '12',
        'kuota_badminton' => '32',
        'batas_pendaftaran' => '2025-12-31',
        'kontak_admin' => '081234567890',
        'email_admin' => 'lomba@polibatam.ac.id',
        'pesan_tutup' => 'Pendaftaran telah ditutup'
    ];
}

// JANGAN TUTUP KONEKSI DI SINI! 
// $conn->close(); // HAPUS ATAU KOMENTARI BARIS INI

// FUNGSI HELPER
function getSetting($key, $default = '') {
    global $pengaturan;
    return $pengaturan[$key] ?? $default;
}
?>
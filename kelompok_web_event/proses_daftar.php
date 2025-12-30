<?php
// proses_daftar.php - VERSI SIMPLE
session_start();
require_once 'koneksi.php';

// Ambil data dari form
$nama_tim = $_POST['ketua'] ?? '';
$jenis_lomba = $_POST['jenis_lomba'] ?? '';
$ketua_nim = $_POST['nim'] ?? '';
$ketua_nama = $_POST['nama'] ?? '';
$prodi_ketua = $_POST['prodi'] ?? '';
$tahun_angkatan = $_POST['tahun'] ?? '';
$no_wa = $_POST['wa'] ?? '';

// Debug: lihat data yang masuk
error_log("Data diterima: " . print_r($_POST, true));

// Simpan ke database
$sql = "INSERT INTO tim_lomba (nama_tim, jenis_lomba, ketua_nim, ketua_nama, prodi_ketua, tahun_angkatan, no_wa) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $nama_tim, $jenis_lomba, $ketua_nim, $ketua_nama, $prodi_ketua, $tahun_angkatan, $no_wa);

if ($stmt->execute()) {
    $id_tim = $conn->insert_id;
    
    // Simpan anggota jika ada
    if (!empty($_POST['anggota_nama']) && is_array($_POST['anggota_nama'])) {
        $sql_anggota = "INSERT INTO anggota_tim (id_tim, nama, nim, prodi, tahun_angkatan) VALUES (?, ?, ?, ?, ?)";
        $stmt_anggota = $conn->prepare($sql_anggota);
        
        foreach ($_POST['anggota_nama'] as $index => $nama) {
            if (!empty($nama)) {
                $nim = $_POST['anggota_nim'][$index] ?? '';
                $prodi = $_POST['anggota_prodi'][$index] ?? '';
                $tahun = $_POST['anggota_tahun'][$index] ?? $_POST['anggota_posisi'][$index] ?? '';
                
                $stmt_anggota->bind_param("issss", $id_tim, $nama, $nim, $prodi, $tahun);
                $stmt_anggota->execute();
            }
        }
    }
    
    // Redirect ke halaman sukses
    header("Location: konfirmasi.php?id=" . $id_tim);
    exit();
    
} else {
    echo "Error: " . $stmt->error;
    echo "<br><a href='daftar.php'>Kembali ke Form</a>";
}
?>
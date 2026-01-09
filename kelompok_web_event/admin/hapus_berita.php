<?php
session_start();
require_once '../koneksi.php';

// PROTEKSI ADMIN
if (!isset($_SESSION['admin_event_id'])) {
    header("Location: login.php");
    exit();
}

// ================================================
// PROSES HAPUS BERITA
// ================================================
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Ambil data berita untuk hapus gambar
    $query = "SELECT gambar FROM berita WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $berita = mysqli_fetch_assoc($result);
        
        // Hapus gambar dari server jika ada
        if (!empty($berita['gambar'])) {
            $file_path = '../' . $berita['gambar'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Hapus dari database
        $delete_query = "DELETE FROM berita WHERE id = $id";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success'] = 'Berita berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus berita: ' . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = 'Berita tidak ditemukan!';
    }
} else {
    $_SESSION['error'] = 'ID tidak valid!';
}

// Redirect kembali ke daftar berita
header("Location: daftar_berita.php");
exit();
?>
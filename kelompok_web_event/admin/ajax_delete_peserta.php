<?php
// ajax_delete_peserta.php
session_start();
require_once '../koneksi.php';

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Untuk debugging, simpan log
file_put_contents('delete_log.txt', date('Y-m-d H:i:s') . " - DELETE REQUEST\n" . print_r($_POST, true) . "\n\n", FILE_APPEND);

// Untuk testing, bypass session dulu (nanti di-enable lagi)
// if (!isset($_SESSION['admin_event_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized - Silakan login kembali']);
//     exit();
// }

// Cek apakah ada ID yang dikirim
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID peserta tidak valid atau kosong']);
    exit();
}

$peserta_id = intval($_POST['id']);

try {
    // 1. Cek apakah peserta ada
    $check_query = "SELECT id, nama FROM peserta WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        throw new Exception("Error preparing check statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $peserta_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Peserta dengan ID ' . $peserta_id . ' tidak ditemukan']);
        exit();
    }
    
    $peserta = mysqli_fetch_assoc($result);
    $peserta_nama = $peserta['nama'];
    
    mysqli_stmt_close($check_stmt);
    
    // 2. Hapus peserta
    $delete_query = "DELETE FROM peserta WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    
    if (!$delete_stmt) {
        throw new Exception("Error preparing delete statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($delete_stmt, "i", $peserta_id);
    $delete_success = mysqli_stmt_execute($delete_stmt);
    
    if ($delete_success) {
        $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
        
        if ($affected_rows > 0) {
            // Log keberhasilan
            file_put_contents('delete_log.txt', date('Y-m-d H:i:s') . " - SUCCESS: Deleted peserta ID $peserta_id ($peserta_nama)\n", FILE_APPEND);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Peserta "' . $peserta_nama . '" berhasil dihapus',
                'data' => [
                    'id' => $peserta_id,
                    'nama' => $peserta_nama,
                    'rows_affected' => $affected_rows
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Tidak ada data yang dihapus (mungkin sudah dihapus sebelumnya)'
            ]);
        }
    } else {
        throw new Exception("Gagal menghapus: " . mysqli_error($conn));
    }
    
    mysqli_stmt_close($delete_stmt);
    
} catch (Exception $e) {
    // Log error
    file_put_contents('delete_log.txt', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
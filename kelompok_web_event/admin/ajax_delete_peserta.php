<?php
// ajax_delete_peserta.php
session_start();
require_once '../koneksi.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log untuk debugging
$log_file = 'delete_log.txt';

// ==================== DEBUGGING INFO ====================
file_put_contents($log_file, "\n" . str_repeat("=", 50) . "\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - DELETE REQUEST STARTED\n", FILE_APPEND);
file_put_contents($log_file, "POST DATA: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "GET DATA: " . print_r($_GET, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "Peserta ID from POST: " . ($_POST['id'] ?? 'NOT SET') . "\n", FILE_APPEND);

// Cek session (temporary bypass untuk testing)
// if (!isset($_SESSION['admin_event_id'])) {
//     $response = ['success' => false, 'message' => 'Unauthorized - Silakan login kembali'];
//     file_put_contents($log_file, "SESSION CHECK FAILED\n", FILE_APPEND);
//     echo json_encode($response);
//     exit();
// }

// Cek ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $response = ['success' => false, 'message' => 'ID peserta tidak valid atau kosong'];
    file_put_contents($log_file, "ID CHECK FAILED\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

$peserta_id = intval($_POST['id']);
file_put_contents($log_file, "Processing delete for ID: $peserta_id\n", FILE_APPEND);

try {
    // ==================== CEK TABEL PESERTA DULU ====================
    file_put_contents($log_file, "Checking if 'peserta' table exists...\n", FILE_APPEND);
    
    // Cek apakah tabel peserta ada
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'peserta'");
    if (mysqli_num_rows($check_table) == 0) {
        // Cari tabel dengan nama lain yang mungkin
        $similar_tables = mysqli_query($conn, "SHOW TABLES LIKE '%peserta%'");
        $tables = [];
        while ($row = mysqli_fetch_array($similar_tables)) {
            $tables[] = $row[0];
        }
        
        file_put_contents($log_file, "Table 'peserta' not found. Similar tables: " . implode(", ", $tables) . "\n", FILE_APPEND);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Tabel peserta tidak ditemukan. Tabel yang tersedia: ' . (empty($tables) ? 'tidak ada' : implode(", ", $tables))
        ]);
        exit();
    }
    
    // ==================== CEK DATA PESERTA ====================
    $check_query = "SELECT id, nama FROM peserta WHERE id = ?";
    file_put_contents($log_file, "Check query: $check_query (ID: $peserta_id)\n", FILE_APPEND);
    
    $check_stmt = mysqli_prepare($conn, $check_query);
    if (!$check_stmt) {
        throw new Exception("Error preparing check statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $peserta_id);
    
    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception("Error executing check statement: " . mysqli_stmt_error($check_stmt));
    }
    
    $result = mysqli_stmt_get_result($check_stmt);
    $row_count = mysqli_num_rows($result);
    
    file_put_contents($log_file, "Rows found in peserta table: $row_count\n", FILE_APPEND);
    
    if ($row_count == 0) {
        mysqli_stmt_close($check_stmt);
        
        // Tampilkan semua peserta untuk debugging
        $all_peserta = mysqli_query($conn, "SELECT id, nama FROM peserta LIMIT 5");
        $sample_data = [];
        while ($row = mysqli_fetch_assoc($all_peserta)) {
            $sample_data[] = $row;
        }
        
        echo json_encode([
            'success' => false, 
            'message' => 'Peserta dengan ID ' . $peserta_id . ' tidak ditemukan',
            'debug' => [
                'available_peserta' => $sample_data,
                'total_rows_in_table' => mysqli_num_rows(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta"))
            ]
        ]);
        exit();
    }
    
    $peserta = mysqli_fetch_assoc($result);
    $peserta_nama = $peserta['nama'];
    
    file_put_contents($log_file, "Found peserta: ID={$peserta['id']}, Nama={$peserta_nama}\n", FILE_APPEND);
    mysqli_stmt_close($check_stmt);
    
    // ==================== HAPUS PESERTA ====================
    // Nonaktifkan foreign key check sementara
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    $delete_query = "DELETE FROM peserta WHERE id = ?";
    file_put_contents($log_file, "Delete query: $delete_query\n", FILE_APPEND);
    
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    if (!$delete_stmt) {
        throw new Exception("Error preparing delete statement: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($delete_stmt, "i", $peserta_id);
    
    // Execute delete
    $execution_result = mysqli_stmt_execute($delete_stmt);
    file_put_contents($log_file, "Delete execution result: " . ($execution_result ? "SUCCESS" : "FAILED") . "\n", FILE_APPEND);
    
    if (!$execution_result) {
        throw new Exception("Gagal menghapus: " . mysqli_stmt_error($delete_stmt));
    }
    
    // Dapatkan affected rows
    $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
    file_put_contents($log_file, "Affected rows: $affected_rows\n", FILE_APPEND);
    
    // Aktifkan kembali foreign key check
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    
    mysqli_stmt_close($delete_stmt);
    
    if ($affected_rows > 0) {
        // Verifikasi penghapusan
        $verify_query = "SELECT COUNT(*) as count FROM peserta WHERE id = $peserta_id";
        $verify_result = mysqli_query($conn, $verify_query);
        $verify_row = mysqli_fetch_assoc($verify_result);
        $still_exists = $verify_row['count'] > 0;
        
        file_put_contents($log_file, "Verification - Still exists: " . ($still_exists ? "YES" : "NO") . "\n", FILE_APPEND);
        
        if ($still_exists) {
            // Coba hapus langsung tanpa prepared statement
            file_put_contents($log_file, "Trying direct delete...\n", FILE_APPEND);
            mysqli_query($conn, "DELETE FROM peserta WHERE id = $peserta_id");
        }
        
        $response = [
            'success' => true, 
            'message' => 'Peserta "' . $peserta_nama . '" berhasil dihapus',
            'data' => [
                'id' => $peserta_id,
                'nama' => $peserta_nama,
                'rows_affected' => $affected_rows
            ]
        ];
        
        file_put_contents($log_file, "SUCCESS RESPONSE\n", FILE_APPEND);
        
    } else {
        $response = [
            'success' => false, 
            'message' => 'Tidak ada data yang dihapus. Mungkin peserta sudah tidak ada atau ID salah.',
            'debug' => [
                'affected_rows' => $affected_rows,
                'peserta_data' => $peserta
            ]
        ];
        file_put_contents($log_file, "NO ROWS AFFECTED\n", FILE_APPEND);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Pastikan foreign key check diaktifkan kembali
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    
    $error_message = $e->getMessage();
    file_put_contents($log_file, "ERROR: " . $error_message . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $error_message
    ]);
}

// Log akhir
file_put_contents($log_file, date('Y-m-d H:i:s') . " - DELETE REQUEST COMPLETED\n", FILE_APPEND);
file_put_contents($log_file, str_repeat("=", 50) . "\n\n", FILE_APPEND);

mysqli_close($conn);
?>
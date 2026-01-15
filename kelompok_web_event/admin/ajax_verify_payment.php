<?php
// ajax_verifikasi_payment.php
session_start();
require_once '../koneksi.php';

// Set header untuk JSON response
header('Content-Type: application/json; charset=utf-8');

// Log untuk debugging
error_log("=== AJAX VERIFIKASI START ===");
error_log("POST Data: " . print_r($_POST, true));
error_log("SESSION Data: " . print_r($_SESSION, true));

// Cek session dan level admin
if (!isset($_SESSION['admin_event_id'])) {
    error_log("❌ Session tidak ditemukan");
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Silakan login kembali']);
    exit();
}

$admin_id = $_SESSION['admin_event_id'];
$admin_level = $_SESSION['admin_event_level'] ?? '';

// Jika bukan superadmin atau admin, tolak
if (!in_array($admin_level, ['superadmin', 'admin'])) {
    error_log("❌ User tidak memiliki akses: " . $admin_level);
    echo json_encode(['success' => false, 'message' => 'Forbidden - Anda tidak memiliki akses']);
    exit();
}

// Validasi input
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    error_log("❌ ID tidak valid: " . ($_POST['id'] ?? 'NULL'));
    echo json_encode(['success' => false, 'message' => 'ID peserta tidak valid']);
    exit();
}

$peserta_id = intval($_POST['id']);
$status = $_POST['status'] ?? '';
$note = $_POST['note'] ?? '';

error_log("Processing: peserta_id=$peserta_id, status=$status, note=$note");

// Validasi status
$valid_statuses = ['terverifikasi', 'ditolak'];
if (!in_array($status, $valid_statuses)) {
    error_log("❌ Status tidak valid: $status");
    echo json_encode(['success' => false, 'message' => 'Status tidak valid. Harus: ' . implode(', ', $valid_statuses)]);
    exit();
}

// Cek apakah peserta ada
$check_peserta = mysqli_query($conn, "SELECT id, nama, event_id, tim_id FROM peserta WHERE id = $peserta_id");
if (!$check_peserta || mysqli_num_rows($check_peserta) == 0) {
    error_log("❌ Peserta tidak ditemukan: ID $peserta_id");
    echo json_encode(['success' => false, 'message' => 'Peserta tidak ditemukan']);
    exit();
}

$peserta_data = mysqli_fetch_assoc($check_peserta);
error_log("✅ Peserta ditemukan: " . $peserta_data['nama']);

// Mulai transaction
mysqli_begin_transaction($conn);

try {
    // Update status pembayaran di peserta
    $query = "UPDATE peserta SET status_pembayaran = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $peserta_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal update peserta: ' . mysqli_error($conn));
    }
    
    error_log("✅ Status peserta diupdate");
    
    // Update juga untuk tim jika ada
    if ($peserta_data['tim_id']) {
        $tim_id = $peserta_data['tim_id'];
        $update_tim = "UPDATE tim_event SET status_pembayaran = ? WHERE id = ?";
        $tim_stmt = mysqli_prepare($conn, $update_tim);
        mysqli_stmt_bind_param($tim_stmt, "si", $status, $tim_id);
        
        if (!mysqli_stmt_execute($tim_stmt)) {
            error_log("⚠️ Gagal update tim, tapi lanjutkan...");
            // Lanjutkan meskipun gagal update tim
        } else {
            error_log("✅ Status tim diupdate");
        }
    }
    
    // Log verifikasi (buat tabel jika belum ada)
    $check_log_table = mysqli_query($conn, "SHOW TABLES LIKE 'log_verifikasi'");
    if (mysqli_num_rows($check_log_table) == 0) {
        // Buat tabel log jika belum ada
        $create_log_table = "CREATE TABLE IF NOT EXISTS log_verifikasi (
            id INT PRIMARY KEY AUTO_INCREMENT,
            peserta_id INT,
            admin_id INT,
            status VARCHAR(50),
            catatan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (peserta_id) REFERENCES peserta(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES admin_event(id) ON DELETE CASCADE
        )";
        mysqli_query($conn, $create_log_table);
        error_log("✅ Tabel log_verifikasi dibuat");
    }
    
    // Insert log
    $log_query = "INSERT INTO log_verifikasi (peserta_id, admin_id, status, catatan) 
                  VALUES (?, ?, ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($log_stmt, "iiss", $peserta_id, $admin_id, $status, $note);
    
    if (!mysqli_stmt_execute($log_stmt)) {
        error_log("⚠️ Gagal insert log: " . mysqli_error($conn));
        // Lanjutkan meskipun gagal log
    } else {
        error_log("✅ Log verifikasi disimpan");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    error_log("✅ Transaction berhasil");
    
    // Berikan response JSON yang benar
    echo json_encode([
        'success' => true,
        'message' => 'Status berhasil diperbarui',
        'status' => $status,
        'peserta_id' => $peserta_id,
        'peserta_nama' => $peserta_data['nama']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Rollback jika error
    mysqli_rollback($conn);
    
    error_log("❌ ERROR: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} finally {
    // Close statements jika ada
    if (isset($stmt)) mysqli_stmt_close($stmt);
    if (isset($tim_stmt)) mysqli_stmt_close($tim_stmt);
    if (isset($log_stmt)) mysqli_stmt_close($log_stmt);
    
    mysqli_close($conn);
    
    error_log("=== AJAX VERIFIKASI END ===");
}
?>
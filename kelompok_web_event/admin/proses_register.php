<?php
session_start();
require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: login.php');
    exit();
}

// DEBUG: Tampilkan POST data
error_log("POST Data: " . print_r($_POST, true));

// Ambil data dari form
$username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
$nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap'] ?? '');
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$no_wa = mysqli_real_escape_string($conn, $_POST['no_wa'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$alasan_daftar = mysqli_real_escape_string($conn, $_POST['alasan_daftar'] ?? '');

// Validasi
$errors = [];

// Cek username minimal 5 karakter
if (strlen($username) < 5) {
    $errors[] = 'username_length';
}

// Cek password minimal 8 karakter
if (strlen($password) < 8) {
    $errors[] = 'password_length';
}

// Cek password match
if ($password !== $confirm_password) {
    $errors[] = 'password_mismatch';
}

// Cek apakah username sudah ada
$check_username = mysqli_query($conn, "SELECT id FROM admin_event WHERE username = '$username'");
if (!$check_username) {
    error_log("Error checking username: " . mysqli_error($conn));
    $errors[] = 'database_error';
} elseif (mysqli_num_rows($check_username) > 0) {
    $errors[] = 'username_exists';
}

// Cek apakah email sudah ada
if (!empty($email)) {
    $check_email = mysqli_query($conn, "SELECT id FROM admin_event WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = 'email_exists';
    }
}

// Jika ada error, redirect kembali
if (!empty($errors)) {
    $error_param = implode('|', $errors);
    header("Location: login.php?reg_error=$error_param");
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Default values - SESUAI STRUKTUR
$level = 'panitia'; // Tapi di struktur cuma ada 'superadmin', 'admin'
$status = 'inactive';

// KOREKSI: Level hanya 'superadmin' atau 'admin' (tidak ada 'panitia')
// Sesuai struktur: enum('superadmin', 'admin')
$level = 'admin'; // Ubah ke 'admin' karena 'panitia' tidak ada di enum

// Insert ke database - SESUAI STRUKTUR YANG KAMU KASIH
$sql = "INSERT INTO admin_event (
    username, 
    password, 
    nama_lengkap, 
    nama,          -- Kolom duplikat, isi sama dengan nama_lengkap
    email, 
    no_wa, 
    alasan_daftar,
    level, 
    status, 
    created_at
) VALUES (
    '$username',
    '$hashed_password',
    '$nama_lengkap',
    '$nama_lengkap',  -- Isi kolom 'nama' juga
    '$email',
    '$no_wa',
    '$alasan_daftar',
    '$level',
    '$status',
    NOW()
)";

error_log("Executing SQL: " . $sql);

if (mysqli_query($conn, $sql)) {
    $admin_id = mysqli_insert_id($conn);
    
    // Kirim notifikasi ke superadmin (jika tabel ada)
    $notif_check = mysqli_query($conn, "SHOW TABLES LIKE 'admin_notifications'");
    if (mysqli_num_rows($notif_check) > 0) {
        $notif_sql = "INSERT INTO admin_notifications (
            admin_id,
            type,
            message,
            is_read,
            created_at
        ) VALUES (
            1,
            'new_registration',
            'Admin baru mendaftar: $nama_lengkap ($username)',
            0,
            NOW()
        )";
        mysqli_query($conn, $notif_sql);
    }
    
    // Redirect dengan success message
    header('Location: login.php?registered=1');
    exit();
    
} else {
    // Error database - TAMPILKAN DETAIL
    $error_msg = mysqli_error($conn);
    error_log("Database error: " . $error_msg);
    
    echo "<h3>Error Database</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error_msg) . "</p>";
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>";
    
    // Debug: Cek koneksi dan tabel
    echo "<h4>Debug Info:</h4>";
    echo "<p>Database: " . mysqli_get_host_info($conn) . "</p>";
    
    $tables = mysqli_query($conn, "SHOW TABLES");
    echo "<p>Tables in database:</p><ul>";
    while($table = mysqli_fetch_array($tables)) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
    
    exit();
}
?>
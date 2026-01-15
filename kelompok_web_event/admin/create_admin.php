<?php
session_start();
require_once '../koneksi.php';

// Cek superadmin
if (!isset($_SESSION['admin_event_id']) || $_SESSION['admin_event_level'] != 'superadmin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $no_wa = mysqli_real_escape_string($conn, $_POST['no_wa'] ?? '');
    $level = $_POST['level'] ?? 'admin';
    $status = $_POST['status'] ?? 'active';
    
    // DEBUG: Log data yang diterima
    error_log("Create Admin - Data: username=$username, nama_lengkap=$nama_lengkap, email=$email, level=$level, status=$status");
    
    // Validasi
    if (empty($username) || strlen($username) < 5) {
        header('Location: admin_management.php?error=username_invalid');
        exit();
    }
    
    if (empty($nama_lengkap)) {
        header('Location: admin_management.php?error=name_required');
        exit();
    }
    
    // Cek apakah username sudah ada
    $check = mysqli_query($conn, "SELECT id FROM admin_event WHERE username = '$username'");
    if (!$check) {
        error_log("Error checking username: " . mysqli_error($conn));
        header('Location: admin_management.php?error=database');
        exit();
    }
    
    if (mysqli_num_rows($check) > 0) {
        header('Location: admin_management.php?error=username_exists');
        exit();
    }
    
    // Cek apakah email sudah ada (jika email diisi)
    if (!empty($email)) {
        $check_email = mysqli_query($conn, "SELECT id FROM admin_event WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            header('Location: admin_management.php?error=email_exists');
            exit();
        }
    }
    
    // Hash password default 'password123'
    $hashed_password = password_hash('password123', PASSWORD_DEFAULT);
    
    // Insert ke database - SESUAI STRUKTUR TABEL
    // Struktur: id, username, password, nama_lengkap, nama, email, no_wa, alasan_daftar, level, status, foto, last_login, created_at
    $sql = "INSERT INTO admin_event (
        username, 
        password, 
        nama_lengkap, 
        nama,          -- Kolom duplikat, isi sama dengan nama_lengkap
        email, 
        no_wa, 
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
        '$level', 
        '$status', 
        NOW()
    )";
    
    error_log("Executing SQL: " . $sql);
    
    if (mysqli_query($conn, $sql)) {
        $new_admin_id = mysqli_insert_id($conn);
        
        // Log success
        error_log("Admin created successfully: ID=$new_admin_id, Username=$username");
        
        // Redirect dengan success message
        header('Location: admin_management.php?success=created&username=' . urlencode($username));
        exit();
        
    } else {
        // Error database
        $error_msg = mysqli_error($conn);
        error_log("Database error creating admin: " . $error_msg);
        
        // Tampilkan error detail untuk debugging
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Error Create Admin</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='container mt-5'>
            <div class='alert alert-danger'>
                <h4><i class='fas fa-exclamation-triangle'></i> Error Membuat Admin</h4>
                <p><strong>Error:</strong> " . htmlspecialchars($error_msg) . "</p>
                <p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>
                <hr>
                <p>Data yang dikirim:</p>
                <ul>
                    <li>Username: " . htmlspecialchars($username) . "</li>
                    <li>Nama Lengkap: " . htmlspecialchars($nama_lengkap) . "</li>
                    <li>Email: " . htmlspecialchars($email) . "</li>
                    <li>No. WA: " . htmlspecialchars($no_wa) . "</li>
                    <li>Level: " . htmlspecialchars($level) . "</li>
                    <li>Status: " . htmlspecialchars($status) . "</li>
                </ul>
                <a href='admin_management.php' class='btn btn-secondary'>Kembali ke Admin Management</a>
            </div>
        </body>
        </html>";
        exit();
    }
    
} else {
    // Jika bukan POST request, redirect ke admin management
    header('Location: admin_management.php');
    exit();
}
?>
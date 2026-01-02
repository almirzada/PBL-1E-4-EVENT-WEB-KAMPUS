<?php
session_start();
header('Content-Type: application/json');

// PROTEKSI
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['level'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// KONEKSI
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id_admin = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'id_admin' => $user['id_admin'],
        'username' => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'level' => $user['level'],
        'is_active' => $user['is_active']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>
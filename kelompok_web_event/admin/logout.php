<?php
session_start();

// LOG LOGOUT
if (isset($_SESSION['username'])) {
    $log_message = "Logout - User: " . $_SESSION['username'];
    error_log($log_message);
}

// HANCURKAN SEMUA DATA SESSION
$_SESSION = array();

// HAPUS COOKIE SESSION JIKA ADA
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// HANCURKAN SESSION
session_destroy();

// REDIRECT KE HALAMAN LOGIN
header("Location: login.php");
exit();
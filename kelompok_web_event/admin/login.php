<?php
session_start();
// Password admin sederhana (untuk sementara)
$admin_password = "admin123";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $password = $_POST['password'];
  
  if ($password == $admin_password) {
    $_SESSION['admin_logged_in'] = true;
    header("Location: dashboard.php");
    exit();
  } else {
    $error = "Password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Admin</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #1e88e5, #0d47a1);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-box {
      background: white;
      border-radius: 15px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.2);
      text-align: center;
    }
    h1 {
      color: #2c3e50;
      margin-bottom: 30px;
    }
    .lock-icon {
      font-size: 3rem;
      color: #1e88e5;
      margin-bottom: 20px;
    }
    input[type="password"] {
      width: 100%;
      padding: 15px;
      border: 2px solid #ddd;
      border-radius: 10px;
      font-size: 1rem;
      margin-bottom: 20px;
      box-sizing: border-box;
    }
    button {
      background: linear-gradient(to right, #1e88e5, #0d47a1);
      color: white;
      border: none;
      padding: 15px;
      width: 100%;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    button:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(30, 136, 229, 0.4);
    }
    .error {
      color: #dc3545;
      background: #ffe6e6;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="lock-icon">🔐</div>
    <h1>Admin Login</h1>
    <?php if (isset($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="password" name="password" placeholder="Masukkan password admin" required>
      <button type="submit">Masuk</button>
    </form>
  </div>
</body>
</html>
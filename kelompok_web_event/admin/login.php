<?php
include '../config.php'


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
       body {
  min-height:100vh;
  font-family: Arial, sans-serif;
  background: url('Technopreneur-Polibatam-1.jpg') no-repeat center center fixed;
  background-size: cover;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('poli.png') no-repeat center center fixed;
            background-size: cover;
            filter: blur(6px);
            z-index: -1;
        }

        .box {
            position: relative;
            width: 380px;
            margin: 50px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            animation: fadeIn .5s;
            z-index: 1;
        }

        @keyframes fadeIn {
            from {opacity:0; transform: translateY(-10px);}
            to {opacity:1; transform: translateY(0);}
        }

        .switch-link {
            cursor: pointer;
            color: #007bff;
        }
        .switch-link:hover {
            text-decoration: underline;
        }
    </style>

    <script>
        function showRegister() {
            document.getElementById('loginForm').style.display = "none";
            document.getElementById('registerForm').style.display = "block";
        }

        function showLogin() {
            document.getElementById('registerForm').style.display = "none";
            document.getElementById('loginForm').style.display = "block";
        }
    </script>
</head>

<body>

<div class="box">

    <!-- LOGIN -->
    <div id="loginForm">
        <h3 class="text-center mb-3">Login Admin</h3>

        <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" class="form-control" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password" placeholder="Masukkan password" required>
            </div>

            <button class="btn btn-primary w-100" name="login">Login</button>
        </form>

        <p class="text-center mt-3">
            Belum punya akun?  
            <span class="switch-link" onclick="showRegister()">Daftar</span>
        </p>
    </div>

    <!-- REGISTER -->
    <div id="registerForm" style="display:none;">
        <h3 class="text-center mb-3">Registrasi</h3>

        <?php if (!empty($error_reg)) echo "<div class='alert alert-danger'>$error_reg</div>"; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Buat Username</label>
                <input type="text" class="form-control" name="new_username" required>
            </div>

            <div class="mb-3">
                <label>Buat Password</label>
                <input type="password" class="form-control" name="new_password" required>
            </div>

            <button class="btn btn-success w-100" name="register">Daftar</button>
        </form>

        <p class="text-center mt-3">
            Sudah punya akun?  
            <span class="switch-link" onclick="showLogin()">Login</span>
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
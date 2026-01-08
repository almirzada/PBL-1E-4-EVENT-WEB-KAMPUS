<?php
session_start();

// Debug: Tampilkan session saat ini
echo "<!-- Session sebelum login: ";
print_r($_SESSION);
echo " -->";

require_once '../koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_event_id'])) {
    echo "<!-- Redirect ke dashboard -->";
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<!-- Login attempt: $username -->";
    
    // SIMPLE LOGIC: Cek username & password
    if ($username == 'admin' && $password == 'revan0813') {
        // Ambil data admin dari database
        $sql = "SELECT * FROM admin_event WHERE username = 'admin' LIMIT 1";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $admin = mysqli_fetch_assoc($result);
            
            // Set session dengan nama yang konsisten
            $_SESSION['admin_event_id'] = $admin['id'];
            $_SESSION['admin_event_nama'] = $admin['nama'] ?? 'Administrator';
            $_SESSION['admin_event_level'] = $admin['level'] ?? 'superadmin';
            
            echo "<!-- Session set: " . $_SESSION['admin_event_id'] . " -->";
            
            // Hapus output buffer sebelum redirect
            ob_clean();
            
            // Redirect dengan delay kecil
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 100);
            </script>";
            exit();
            
        } else {
            $error = 'Admin tidak ditemukan di database!';
        }
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin Event Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="login-card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">Login Admin Event</h4>
                        <p class="mb-0">Politeknik Negeri Batam</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="loginForm">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="admin" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" 
                                       value="revan0813" required autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Login ke Dashboard
                            </button>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <div class="alert alert-info">
                                <small>
                                    <strong>Login Default:</strong><br>
                                    Username: <strong>admin</strong><br>
                                    Password: <strong>revan0813</strong>
                                </small>
                            </div>
                            <a href="../index.php" class="text-decoration-none">
                                ← Kembali ke Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var username = document.querySelector('[name="username"]').value;
            var password = document.querySelector('[name="password"]').value;
            
            if (username === '' || password === '') {
                e.preventDefault();
                alert('Username dan password harus diisi!');
            }
        });
    </script>
</body>
</html>
<?php
session_start();

// Debug: Tampilkan session saat ini
echo "<!-- Session sebelum login: ";
print_r($_SESSION);
echo " -->";

require_once '../koneksi.php';

// kalau sudah login, redirect ke dashboard
if (isset($_SESSION['admin_event_id'])) {
    echo "<!-- Redirect ke dashboard -->";
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Admin Event Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .access-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        
        .card-header-gradient {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1%, transparent 1%);
            background-size: 20px 20px;
            opacity: 0.3;
        }
        
        .login-tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 25px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px;
            background: none;
            border: none;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .tab-btn.active {
            color: var(--primary);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .btn-primary-custom {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .info-box i {
            color: #2196f3;
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(67, 97, 238, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
        }
        
        .admin-badge i {
            color: var(--primary);
        }
        
        .role-tag {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .role-superadmin {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%);
            color: white;
        }
        
        .role-panitia {
            background: linear-gradient(135deg, #ffe66d 0%, #f9c74f 100%);
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="access-card">
                    <!-- HEADER -->
                    <div class="card-header-gradient">
                        <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png" 
                             alt="Polibatam" 
                             style="height: 70px; margin-bottom: 15px;">
                        <h3 class="mb-2">Panel Administrator Event Kampus</h3>
                        <p class="mb-0 opacity-75">Politeknik Negeri Batam</p>
                    </div>
                    
                    <!-- BODY -->
                    <div class="card-body p-4">
                        <!-- TABS -->
                        <div class="login-tabs">
                            <button class="tab-btn active" onclick="showTab('login')">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Admin
                            </button>
                            <button class="tab-btn" onclick="showTab('register')">
                                <i class="fas fa-user-plus me-2"></i>Daftar Akun Baru
                            </button>
                        </div>
                        
                        <!-- LOGIN FORM -->
                        <div id="loginTab" class="tab-content active">
                            <?php if (isset($_GET['registered'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Pendaftaran berhasil!</strong> Akun Anda sedang menunggu persetujuan superadmin.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['activated'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Akun telah diaktifkan!</strong> Silakan login dengan akun Anda.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php 
                                $error_msg = $_GET['error'];
                                if ($error_msg == 'invalid') {
                                    echo 'Username atau password salah!';
                                } elseif ($error_msg == 'inactive') {
                                    echo 'Akun Anda belum diaktifkan oleh superadmin!';
                                } elseif ($error_msg == 'suspended') {
                                    echo 'Akun Anda dinonaktifkan oleh superadmin!';
                                } else {
                                    echo 'Terjadi kesalahan saat login!';
                                }
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="proses_login.php" id="loginForm">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" name="username" class="form-control" 
                                               placeholder="Masukkan username" required autocomplete="off">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" class="form-control" 
                                               placeholder="Masukkan password" required autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-primary-custom mb-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login ke Dashboard
                                </button>
                                
                                <div class="info-box">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Login Superadmin (default):</strong><br>
                                    Username: <code>superadmin</code> | Password: <code>revan0813</code>
                                </div>
                            </form>
                        </div>
                        
                        <!-- REGISTER FORM -->
                        <div id="registerTab" class="tab-content">
                            <?php if (isset($_GET['reg_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php 
                                $reg_error = $_GET['reg_error'];
                                if ($reg_error == 'username_exists') {
                                    echo 'Username sudah digunakan!';
                                } elseif ($reg_error == 'email_exists') {
                                    echo 'Email sudah terdaftar!';
                                } elseif ($reg_error == 'password_mismatch') {
                                    echo 'Konfirmasi password tidak cocok!';
                                } else {
                                    echo 'Terjadi kesalahan saat pendaftaran!';
                                }
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="proses_register.php" id="registerForm" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-at"></i></span>
                                            <input type="text" name="username" class="form-control" 
                                                   placeholder="min. 5 karakter" required>
                                        </div>
                                        <div class="form-text">Gunakan huruf dan angka saja</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" name="nama_lengkap" class="form-control" 
                                                   placeholder="Nama lengkap Anda" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" 
                                                   placeholder="email@contoh.com" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                            <input type="tel" name="no_wa" class="form-control" 
                                                   placeholder="081234567890" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="password" id="regPassword" 
                                                   class="form-control" placeholder="min. 8 karakter" required>
                                        </div>
                                        <div class="form-text">Minimal 8 karakter</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="confirm_password" id="regConfirmPassword" 
                                                   class="form-control" placeholder="ulangi password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Alasan Mendaftar sebagai Admin</label>
                                    <textarea name="alasan_daftar" class="form-control" rows="3" 
                                              placeholder="Jelaskan mengapa Anda ingin menjadi admin..."></textarea>
                                    <div class="form-text">Akan ditinjau oleh superadmin</div>
                                </div>
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">syarat dan ketentuan</a> sebagai admin
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn-primary-custom">
                                    <i class="fas fa-user-plus me-2"></i>Daftar Akun Admin
                                </button>
                            </form>
                            
                            <div class="info-box mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Informasi Pendaftaran:</strong><br>
                                Akun Anda akan berstatus <strong>"Menunggu Persetujuan"</strong> sampai superadmin mengaktifkannya. 
                                Proses verifikasi maksimal 2x24 jam.
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Website Utama
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TERMS MODAL -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Syarat dan Ketentuan Admin Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Kewajiban Admin:</h6>
                    <ul>
                        <li>Menjaga kerahasiaan username dan password</li>
                        <li>Bertanggung jawab atas event yang dibuat</li>
                        <li>Memverifikasi peserta dengan baik</li>
                        <li>Menjaga profesionalitas dalam mengelola event</li>
                    </ul>
                    
                    <h6>2. Hak Admin:</h6>
                    <ul>
                        <li>Membuat, mengedit, dan menghapus event</li>
                        <li>Mengelola data peserta</li>
                        <li>Melihat statistik event</li>
                    </ul>
                    
                    <h6>3. Larangan:</h6>
                    <ul>
                        <li>Membagikan akses akun kepada orang lain</li>
                        <li>Membuat event dengan konten tidak pantas</li>
                        <li>Melakukan kecurangan dalam sistem</li>
                    </ul>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Superadmin berhak menonaktifkan akun yang melanggar ketentuan tanpa pemberitahuan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        function showTab(tabName) {
            // Update tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName + 'Tab').classList.add('active');
        }
        
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength check
        const regPassword = document.getElementById('regPassword');
        const regConfirmPassword = document.getElementById('regConfirmPassword');
        
        if (regPassword && regConfirmPassword) {
            regConfirmPassword.addEventListener('input', function() {
                if (regPassword.value !== this.value) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        }
        
        // Form validation
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('regConfirmPassword').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password minimal 8 karakter!');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
                return false;
            }
            
            if (!agreeTerms) {
                e.preventDefault();
                alert('Anda harus menyetujui syarat dan ketentuan!');
                return false;
            }
            
            return true;
        });
        
        // Auto-focus on first input
        setTimeout(() => {
            const firstInput = document.querySelector('input[name="username"]');
            if (firstInput) firstInput.focus();
        }, 300);
    </script>
</body>
</html>
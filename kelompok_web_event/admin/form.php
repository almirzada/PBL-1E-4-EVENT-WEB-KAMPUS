<?php
session_start();
// PROTEKSI: Hanya admin yang login bisa akses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pendaftaran - Admin</title>
    <style>
        /* CSS FORM KAMU YANG UDAH ADA */
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .form-container { background: white; max-width: 800px; margin: auto; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 25px; }
        label { color: #1e88e5; font-weight: 600; display: block; margin-bottom: 8px; font-size: 1.1rem; }
        input, select { width: 100%; padding: 14px; border: 2px solid #ddd; border-radius: 10px; font-size: 1rem; transition: all 0.3s; }
        input:focus, select:focus { border-color: #1e88e5; outline: none; box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.2); }
        button { background: linear-gradient(to right, #1e88e5, #0d47a1); color: white; border: none; padding: 16px 30px; border-radius: 10px; font-size: 1.2rem; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; transition: all 0.3s; }
        button:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(30, 136, 229, 0.3); }
        .admin-badge { background: #ffc107; color: #000; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: bold; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>📝 Form Pendaftaran (Admin Mode)</h1>
        <div class="admin-badge">👨‍💼 Mode Admin: <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></div>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">
            Hanya admin yang bisa mengakses form ini. Data akan langsung masuk ke database.
        </p>
        
        <form action="submit.php" method="POST">
            <div class="form-group">
                <label for="nim">NIM:</label>
                <input type="text" id="nim" name="nim" required placeholder="Masukkan NIM">
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Lengkap:</label>
                <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label for="prodi">Program Studi:</label>
                <input type="text" id="prodi" name="prodi" required placeholder="Masukkan program studi">
            </div>
            
            <div class="form-group">
                <label for="wa">Nomor WA Aktif:</label>
                <input type="tel" id="wa" name="wa" required placeholder="Contoh: 081234567890">
            </div>
            
            <div class="form-group">
                <label for="ketua">Nama Ketua/Kapten Tim:</label>
                <input type="text" id="ketua" name="ketua" required placeholder="Masukkan nama ketua tim">
            </div>
            
            <button type="submit">
                <span>💾</span> Simpan Data ke Database
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 25px;">
            <a href="dashboard.php" style="color: #1e88e5; text-decoration: none; font-weight: 600;">
                ← Kembali ke Dashboard
            </a>
        </p>
    </div>
</body>
</html>
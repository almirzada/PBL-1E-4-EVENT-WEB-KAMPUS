<?php
session_start();

// PROTEKSI: Hanya super_admin yang bisa akses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['level'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

// KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// VARIABEL PESAN
$pesan = '';
$jenis_pesan = '';

// PROSES TAMBAH USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $password = $_POST['password'];
    $level = $_POST['level'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // VALIDASI
    if (empty($username) || empty($nama_lengkap) || empty($password)) {
        $pesan = "Semua field harus diisi!";
        $jenis_pesan = 'error';
    } else {
        // CEK USERNAME SUDAH ADA
        $check = $conn->prepare("SELECT id_admin FROM admin_users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $pesan = "Username sudah digunakan!";
            $jenis_pesan = 'error';
        } else {
            // HASH PASSWORD
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // INSERT USER BARU
            $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, nama_lengkap, level, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $username, $password_hash, $nama_lengkap, $level, $is_active);
            
            if ($stmt->execute()) {
                $pesan = "User berhasil ditambahkan!";
                $jenis_pesan = 'success';
            } else {
                $pesan = "Gagal menambahkan user: " . $conn->error;
                $jenis_pesan = 'error';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// PROSES EDIT USER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id_admin = intval($_POST['id_admin']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $level = $_POST['level'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'];
    
    // JIKA ADA PASSWORD BARU
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET nama_lengkap = ?, level = ?, is_active = ?, password_hash = ? WHERE id_admin = ?");
        $stmt->bind_param("ssisi", $nama_lengkap, $level, $is_active, $password_hash, $id_admin);
    } else {
        $stmt = $conn->prepare("UPDATE admin_users SET nama_lengkap = ?, level = ?, is_active = ? WHERE id_admin = ?");
        $stmt->bind_param("ssii", $nama_lengkap, $level, $is_active, $id_admin);
    }
    
    if ($stmt->execute()) {
        $pesan = "User berhasil diperbarui!";
        $jenis_pesan = 'success';
    } else {
        $pesan = "Gagal memperbarui user: " . $conn->error;
        $jenis_pesan = 'error';
    }
    $stmt->close();
}

// PROSES HAPUS USER
if (isset($_GET['hapus_user'])) {
    $id_hapus = intval($_GET['hapus_user']);
    
    // CEK JANGAN HAPUS DIRI SENDIRI
    if ($id_hapus == $_SESSION['admin_id']) {
        $pesan = "Tidak bisa menghapus akun sendiri!";
        $jenis_pesan = 'error';
    } else {
        $delete = $conn->query("DELETE FROM admin_users WHERE id_admin = $id_hapus");
        if ($delete) {
            $pesan = "User berhasil dihapus!";
            $jenis_pesan = 'success';
        } else {
            $pesan = "Gagal menghapus user: " . $conn->error;
            $jenis_pesan = 'error';
        }
    }
}

// AMBIL DATA SEMUA USER
$result = $conn->query("SELECT * FROM admin_users ORDER BY level DESC, nama_lengkap ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - Admin</title>
    <style>
        /* COPY STYLE DARI dashboard.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, #ff9800, #f57c00);
            color: white;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: white;
            color: #ff9800;
            transform: translateY(-3px);
        }
        
        .content {
            padding: 30px 40px;
        }
        
        /* FORM TAMBAH USER */
        .form-tambah {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px solid #e9ecef;
        }
        
        .form-tambah h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #ff9800;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.2);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-submit {
            background: linear-gradient(to right, #4CAF50, #45a049);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }
        
        /* TABLE USER */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        thead {
            background: linear-gradient(to right, #34495e, #2c3e50);
            color: white;
        }
        
        th, td {
            padding: 18px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* BADGE */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-super_admin {
            background: linear-gradient(to right, #f39c12, #e67e22);
            color: white;
        }
        
        .badge-admin {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
        }
        
        .badge-active {
            background: #28a745;
            color: white;
        }
        
        .badge-inactive {
            background: #6c757d;
            color: white;
        }
        
        /* ACTION BUTTONS */
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-edit:hover, .btn-delete:hover {
            transform: translateY(-2px);
            filter: brightness(110%);
        }
        
        /* MODAL EDIT */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        /* PESAN ALERT */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: fadeIn 0.5s;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i>👥</i> Manajemen User Admin</h1>
            <a href="dashboard.php" class="btn-back">
                <i>←</i> Kembali ke Dashboard
            </a>
        </header>
        
        <main class="content">
            <!-- PESAN ALERT -->
            <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?php echo $jenis_pesan; ?>">
                <i><?php echo $jenis_pesan == 'success' ? '✅' : '⚠️'; ?></i>
                <?php echo htmlspecialchars($pesan); ?>
            </div>
            <?php endif; ?>
            
            <!-- FORM TAMBAH USER BARU -->
            <div class="form-tambah">
                <h3><i>➕</i> Tambah User Baru</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required 
                                   placeholder="Masukkan username (huruf kecil)">
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" required 
                                   placeholder="Masukkan nama lengkap">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required 
                                   placeholder="Masukkan password">
                        </div>
                        <div class="form-group">
                            <label>Level Akses</label>
                            <select name="level" class="form-control" required>
                                <option value="admin">Admin Biasa</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <label for="is_active">Aktifkan user setelah ditambahkan</label>
                    </div>
                    
                    <button type="submit" name="tambah_user" class="btn-submit">
                        <i>➕</i> Tambah User
                    </button>
                </form>
            </div>
            
            <!-- TABLE DAFTAR USER -->
            <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                <i>📋</i> Daftar User Admin
            </h3>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Terakhir Login</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $user['id_admin']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <?php if ($user['id_admin'] == $_SESSION['admin_id']): ?>
                                <span style="color: #f39c12; font-size: 0.8rem;">(Anda)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['level']; ?>">
                                    <?php echo $user['level']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $user['is_active'] ? 'AKTIF' : 'NONAKTIF'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                <span style="color: #999;">Belum login</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-edit" onclick="editUser(<?php echo $user['id_admin']; ?>)">
                                        <i>✏️</i> Edit
                                    </button>
                                    <?php if ($user['id_admin'] != $_SESSION['admin_id']): ?>
                                    <a href="?hapus_user=<?php echo $user['id_admin']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Hapus user <?php echo htmlspecialchars($user['nama_lengkap']); ?>?')">
                                        <i>🗑️</i> Hapus
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- MODAL EDIT USER -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                <i>✏️</i> Edit User
            </h3>
            <form method="POST" id="formEdit">
                <input type="hidden" name="id_admin" id="edit_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="edit_username" class="form-control" disabled>
                    <small style="color: #666;">Username tidak dapat diubah</small>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Level Akses</label>
                        <select name="level" id="edit_level" class="form-control" required>
                            <option value="admin">Admin Biasa</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password Baru (opsional)</label>
                        <input type="password" name="password" class="form-control" 
                               placeholder="Kosongkan jika tidak ingin mengubah">
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="edit_active" name="is_active" value="1">
                    <label for="edit_active">Aktifkan user</label>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" name="edit_user" class="btn-submit">
                        <i>💾</i> Simpan Perubahan
                    </button>
                    <button type="button" onclick="closeModal()" 
                            style="background: #6c757d; color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer;">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // FUNGSI EDIT USER
        function editUser(id) {
            // AMBIL DATA USER VIA AJAX
            fetch('get_user.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.id_admin;
                        document.getElementById('edit_username').value = data.username;
                        document.getElementById('edit_nama').value = data.nama_lengkap;
                        document.getElementById('edit_level').value = data.level;
                        document.getElementById('edit_active').checked = data.is_active == 1;
                        
                        // TAMPILKAN MODAL
                        document.getElementById('modalEdit').style.display = 'flex';
                    } else {
                        alert('Gagal mengambil data user!');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan!');
                });
        }
        
        // FUNGSI TUTUP MODAL
        function closeModal() {
            document.getElementById('modalEdit').style.display = 'none';
        }
        
        // TUTUP MODAL JIKA KLIK DI LUAR
        window.onclick = function(event) {
            const modal = document.getElementById('modalEdit');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
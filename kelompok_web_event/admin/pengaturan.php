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

// CEK APAKAH TABEL PENGATURAN ADA
$table_check = $conn->query("SHOW TABLES LIKE 'pengaturan_sistem'");
if ($table_check->num_rows == 0) {
    // BUAT TABEL PENGATURAN JIKA BELUM ADA
    $conn->query("CREATE TABLE pengaturan_sistem (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_setting VARCHAR(100) UNIQUE NOT NULL,
        nilai_setting TEXT,
        keterangan VARCHAR(255),
        terakhir_diupdate TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // ISI DATA DEFAULT
    $default_settings = [
        ['nama_sistem', 'Sistem Pendaftaran Lomba', 'Nama sistem/aplikasi'],
        ['instansi', 'Politeknik Negeri Batam', 'Nama instansi'],
        ['tahun_ajaran', '2025/2026', 'Tahun ajaran'],
        ['kuota_futsal', '16', 'Maksimal tim futsal'],
        ['kuota_basket', '12', 'Maksimal tim basket'],
        ['kuota_badminton', '32', 'Maksimal tim badminton'],
        ['batas_pendaftaran', '2025-12-31', 'Batas akhir pendaftaran'],
        ['kontak_admin', '081234567890', 'Nomor WA admin'],
        ['email_admin', 'lomba@polibatam.ac.id', 'Email admin'],
        ['status_pendaftaran', 'buka', 'Status pendaftaran (buka/tutup)'],
        ['pesan_tutup', 'Pendaftaran telah ditutup. Terima kasih.', 'Pesan jika pendaftaran tutup']
    ];
    
    foreach ($default_settings as $setting) {
        $stmt = $conn->prepare("INSERT INTO pengaturan_sistem (nama_setting, nilai_setting, keterangan) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $setting[0], $setting[1], $setting[2]);
        $stmt->execute();
    }
}

// PROSES UPDATE PENGATURAN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pengaturan'])) {
    $success_count = 0;
    $error_count = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $nama_setting = substr($key, 8); // Hapus "setting_"
            $nilai_setting = trim($value);
            
            $stmt = $conn->prepare("UPDATE pengaturan_sistem SET nilai_setting = ? WHERE nama_setting = ?");
            $stmt->bind_param("ss", $nilai_setting, $nama_setting);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }
    }
    
    if ($success_count > 0) {
        $pesan = "Berhasil memperbarui $success_count pengaturan!";
        $jenis_pesan = 'success';
    }
    if ($error_count > 0) {
        $pesan .= " Gagal memperbarui $error_count pengaturan.";
        if ($jenis_pesan == 'success') {
            $jenis_pesan = 'warning';
        } else {
            $jenis_pesan = 'error';
        }
    }
}

// AMBIL DATA PENGATURAN
$settings_result = $conn->query("SELECT * FROM pengaturan_sistem ORDER BY nama_setting");
$pengaturan = [];
while ($row = $settings_result->fetch_assoc()) {
    $pengaturan[$row['nama_setting']] = $row;
}

// AMBIL STATISTIK SISTEM
$total_tim = $conn->query("SELECT COUNT(*) as total FROM tim_lomba")->fetch_assoc()['total'];
$total_admin = $conn->query("SELECT COUNT(*) as total FROM admin_users")->fetch_assoc()['total'];
$total_pendaftar_hari_ini = $conn->query("SELECT COUNT(*) as total FROM tim_lomba WHERE DATE(tanggal_daftar) = CURDATE()")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - Admin</title>
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
            background: linear-gradient(to right, #9c27b0, #673ab7);
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
            color: #9c27b0;
            transform: translateY(-3px);
        }
        
        .content {
            padding: 30px 40px;
        }
        
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* FORM PENGATURAN */
        .settings-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .setting-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .setting-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        .setting-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .setting-input:focus {
            border-color: #9c27b0;
            outline: none;
            box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.2);
        }
        
        .setting-desc {
            color: #666;
            font-size: 0.85rem;
            margin-top: 8px;
            font-style: italic;
        }
        
        .btn-save {
            background: linear-gradient(to right, #9c27b0, #673ab7);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 30px auto 0;
            transition: all 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(156, 39, 176, 0.3);
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
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* RESET DATA SECTION */
        .danger-zone {
            background: #fff5f5;
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #f8d7da;
            margin-top: 40px;
        }
        
        .danger-zone h3 {
            color: #dc3545;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-danger {
            background: linear-gradient(to right, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background: linear-gradient(to right, #c82333, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i>⚙️</i> Pengaturan Sistem</h1>
            <a href="dashboard.php" class="btn-back">
                <i>←</i> Kembali ke Dashboard
            </a>
        </header>
        
        <main class="content">
            <!-- PESAN ALERT -->
            <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?php echo $jenis_pesan; ?>">
                <i><?php echo $jenis_pesan == 'success' ? '✅' : ($jenis_pesan == 'warning' ? '⚠️' : '❌'); ?></i>
                <?php echo htmlspecialchars($pesan); ?>
            </div>
            <?php endif; ?>
            
            <!-- STATISTIK SISTEM -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">📊</span>
                    <div class="stat-number"><?php echo $total_tim; ?></div>
                    <div class="stat-label">Total Tim Terdaftar</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">👥</span>
                    <div class="stat-number"><?php echo $total_admin; ?></div>
                    <div class="stat-label">Total Admin</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📅</span>
                    <div class="stat-number"><?php echo $total_pendaftar_hari_ini; ?></div>
                    <div class="stat-label">Pendaftar Hari Ini</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">🔧</span>
                    <div class="stat-number"><?php echo count($pengaturan); ?></div>
                    <div class="stat-label">Pengaturan Sistem</div>
                </div>
            </div>
            
            <!-- FORM PENGATURAN -->
            <form method="POST" class="settings-form">
                <h2 class="section-title"><i>🔧</i> Pengaturan Umum Sistem</h2>
                
                <div class="settings-grid">
                    <!-- NAMA SISTEM -->
                    <div class="setting-item">
                        <label class="setting-label">Nama Sistem</label>
                        <input type="text" name="setting_nama_sistem" 
                               value="<?php echo htmlspecialchars($pengaturan['nama_sistem']['nilai_setting'] ?? 'Sistem Pendaftaran Lomba'); ?>"
                               class="setting-input">
                        <div class="setting-desc"><?php echo $pengaturan['nama_sistem']['keterangan'] ?? 'Nama sistem/aplikasi'; ?></div>
                    </div>
                    
                    <!-- INSTANSI -->
                    <div class="setting-item">
                        <label class="setting-label">Nama Instansi</label>
                        <input type="text" name="setting_instansi" 
                               value="<?php echo htmlspecialchars($pengaturan['instansi']['nilai_setting'] ?? 'Politeknik Negeri Batam'); ?>"
                               class="setting-input">
                        <div class="setting-desc"><?php echo $pengaturan['instansi']['keterangan'] ?? 'Nama instansi/lembaga'; ?></div>
                    </div>
                    
                    <!-- TAHUN AJARAN -->
                    <div class="setting-item">
                        <label class="setting-label">Tahun Ajaran</label>
                        <input type="text" name="setting_tahun_ajaran" 
                               value="<?php echo htmlspecialchars($pengaturan['tahun_ajaran']['nilai_setting'] ?? '2025/2026'); ?>"
                               class="setting-input">
                        <div class="setting-desc"><?php echo $pengaturan['tahun_ajaran']['keterangan'] ?? 'Tahun ajaran berjalan'; ?></div>
                    </div>
                    
                    <!-- STATUS PENDAFTARAN -->
                    <div class="setting-item">
                        <label class="setting-label">Status Pendaftaran</label>
                        <select name="setting_status_pendaftaran" class="setting-input">
                            <option value="buka" <?php echo ($pengaturan['status_pendaftaran']['nilai_setting'] ?? 'buka') == 'buka' ? 'selected' : ''; ?>>Buka</option>
                            <option value="tutup" <?php echo ($pengaturan['status_pendaftaran']['nilai_setting'] ?? 'buka') == 'tutup' ? 'selected' : ''; ?>>Tutup</option>
                        </select>
                        <div class="setting-desc"><?php echo $pengaturan['status_pendaftaran']['keterangan'] ?? 'Status pendaftaran (buka/tutup)'; ?></div>
                    </div>
                </div>
                
                <h2 class="section-title" style="margin-top: 40px;"><i>🏆</i> Pengaturan Lomba</h2>
                
                <div class="settings-grid">
                    <!-- KUOTA FUTSAL -->
                    <div class="setting-item">
                        <label class="setting-label">Kuota Futsal</label>
                        <input type="number" name="setting_kuota_futsal" 
                               value="<?php echo htmlspecialchars($pengaturan['kuota_futsal']['nilai_setting'] ?? '16'); ?>"
                               class="setting-input" min="1" max="100">
                        <div class="setting-desc"><?php echo $pengaturan['kuota_futsal']['keterangan'] ?? 'Maksimal tim futsal'; ?></div>
                    </div>
                    
                    <!-- KUOTA BASKET -->
                    <div class="setting-item">
                        <label class="setting-label">Kuota Basket</label>
                        <input type="number" name="setting_kuota_basket" 
                               value="<?php echo htmlspecialchars($pengaturan['kuota_basket']['nilai_setting'] ?? '12'); ?>"
                               class="setting-input" min="1" max="100">
                        <div class="setting-desc"><?php echo $pengaturan['kuota_basket']['keterangan'] ?? 'Maksimal tim basket'; ?></div>
                    </div>
                    
                    <!-- KUOTA BADMINTON -->
                    <div class="setting-item">
                        <label class="setting-label">Kuota Badminton</label>
                        <input type="number" name="setting_kuota_badminton" 
                               value="<?php echo htmlspecialchars($pengaturan['kuota_badminton']['nilai_setting'] ?? '32'); ?>"
                               class="setting-input" min="1" max="100">
                        <div class="setting-desc"><?php echo $pengaturan['kuota_badminton']['keterangan'] ?? 'Maksimal tim badminton'; ?></div>
                    </div>
                    
                    <!-- BATAS PENDAFTARAN -->
                    <div class="setting-item">
                        <label class="setting-label">Batas Pendaftaran</label>
                        <input type="date" name="setting_batas_pendaftaran" 
                               value="<?php echo htmlspecialchars($pengaturan['batas_pendaftaran']['nilai_setting'] ?? '2025-12-31'); ?>"
                               class="setting-input">
                        <div class="setting-desc"><?php echo $pengaturan['batas_pendaftaran']['keterangan'] ?? 'Batas akhir pendaftaran'; ?></div>
                    </div>
                </div>
                
                <h2 class="section-title" style="margin-top: 40px;"><i>📞</i> Kontak & Informasi</h2>
                
                <div class="settings-grid">
                    <!-- KONTAK ADMIN -->
                    <div class="setting-item">
                        <label class="setting-label">Kontak Admin (WA)</label>
                        <input type="text" name="setting_kontak_admin" 
                               value="<?php echo htmlspecialchars($pengaturan['kontak_admin']['nilai_setting'] ?? '081234567890'); ?>"
                               class="setting-input">
                        <div class="setting-desc"><?php echo $pengaturan['kontak_admin']['keterangan'] ?? 'Nomor WA admin untuk konsultasi'; ?></div>
                    </div>
                    
                    <!-- EMAIL ADMIN -->
                    <div class="setting-item">
                        <label class="setting-label">Email Admin</label>
                        <input type="email" name="setting_email_admin" 
                               value="<?php echo htmlspecialchars($pengaturan['email_admin']['nilai_setting'] ?? 'lomba@polibatam.ac.id'); ?>"
                               class="setting-input">
                        <div class="setting-desc"><?php echo $pengaturan['email_admin']['keterangan'] ?? 'Email admin untuk konfirmasi'; ?></div>
                    </div>
                    
                    <!-- PESAN TUTUP -->
                    <div class="setting-item">
                        <label class="setting-label">Pesan Pendaftaran Tutup</label>
                        <textarea name="setting_pesan_tutup" class="setting-input" rows="3"><?php echo htmlspecialchars($pengaturan['pesan_tutup']['nilai_setting'] ?? 'Pendaftaran telah ditutup. Terima kasih.'); ?></textarea>
                        <div class="setting-desc"><?php echo $pengaturan['pesan_tutup']['keterangan'] ?? 'Pesan yang ditampilkan jika pendaftaran tutup'; ?></div>
                    </div>
                </div>
                
                <button type="submit" name="update_pengaturan" class="btn-save">
                    <i>💾</i> Simpan Semua Pengaturan
                </button>
            </form>
            
            <!-- ZONA BERBAHAYA (RESET DATA) -->
            <div class="danger-zone">
                <h3><i>⚠️</i> Zona Berbahaya</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Hati-hati! Aksi di bawah ini dapat menghapus data secara permanen.
                </p>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button type="button" class="btn-danger" onclick="resetData('tim')">
                        <i>🗑️</i> Reset Data Tim
                    </button>
                    <button type="button" class="btn-danger" onclick="resetData('anggota')">
                        <i>👥</i> Reset Data Anggota
                    </button>
                    <button type="button" class="btn-danger" onclick="backupDatabase()">
                        <i>💾</i> Backup Database
                    </button>
                </div>
                
                <p style="color: #999; font-size: 0.85rem; margin-top: 20px;">
                    <i>💡 Tips:</i> Selalu backup database sebelum melakukan reset data.
                </p>
            </div>
        </main>
    </div>
    
    <script>
        // FUNGSI RESET DATA
        function resetData(jenis) {
            const konfirmasi = confirm(`⚠️ RESET DATA ${jenis.toUpperCase()}?\n\nAksi ini akan menghapus SEMUA data ${jenis} secara permanen!\n\nYakin ingin melanjutkan?`);
            
            if (konfirmasi) {
                const password = prompt('Masukkan password admin untuk konfirmasi:');
                if (password === 'admin123') {
                    // Kirim request reset
                    fetch(`reset_data.php?jenis=${jenis}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(`✅ Berhasil reset data ${jenis}!`);
                                location.reload();
                            } else {
                                alert(`❌ Gagal: ${data.message}`);
                            }
                        })
                        .catch(error => {
                            alert('❌ Terjadi kesalahan!');
                            console.error(error);
                        });
                } else {
                    alert('❌ Password salah!');
                }
            }
        }
        
        // FUNGSI BACKUP DATABASE
        function backupDatabase() {
            alert('⚠️ Fitur backup sedang dalam pengembangan.\n\nUntuk sekarang, backup manual melalui phpMyAdmin.');
        }
        
        // VALIDASI TANGGAL
        document.querySelector('input[name="setting_batas_pendaftaran"]').addEventListener('change', function() {
            const today = new Date().toISOString().split('T')[0];
            if (this.value < today) {
                alert('⚠️ Tanggal batas pendaftaran tidak boleh kurang dari hari ini!');
                this.value = today;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
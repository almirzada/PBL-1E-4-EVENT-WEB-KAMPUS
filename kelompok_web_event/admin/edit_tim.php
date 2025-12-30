<?php
// edit_tim.php
session_start();
require_once '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$id_tim = $_GET['id'] ?? 0;

if (!$id_tim) {
    header("Location: dashboard.php");
    exit();
}

// Ambil data tim
$sql_tim = "SELECT * FROM tim_lomba WHERE id_tim = ?";
$stmt_tim = $conn->prepare($sql_tim);
$stmt_tim->bind_param("i", $id_tim);
$stmt_tim->execute();
$result_tim = $stmt_tim->get_result();
$tim = $result_tim->fetch_assoc();

if (!$tim) {
    die("Tim tidak ditemukan!");
}

// Ambil data anggota
$sql_anggota = "SELECT * FROM anggota_tim WHERE id_tim = ?";
$stmt_anggota = $conn->prepare($sql_anggota);
$stmt_anggota->bind_param("i", $id_tim);
$stmt_anggota->execute();
$anggota_result = $stmt_anggota->get_result();

// Proses update jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_tim = $_POST['nama_tim'] ?? '';
    $jenis_lomba = $_POST['jenis_lomba'] ?? '';
    $ketua_nim = $_POST['ketua_nim'] ?? '';
    $ketua_nama = $_POST['ketua_nama'] ?? '';
    $prodi_ketua = $_POST['prodi_ketua'] ?? '';
    $tahun_angkatan = $_POST['tahun_angkatan'] ?? '';
    $no_wa = $_POST['no_wa'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    
    // Update data tim
    $sql_update = "UPDATE tim_lomba SET 
                    nama_tim = ?,
                    jenis_lomba = ?,
                    ketua_nim = ?,
                    ketua_nama = ?,
                    prodi_ketua = ?,
                    tahun_angkatan = ?,
                    no_wa = ?,
                    status = ?
                   WHERE id_tim = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssssssi", 
        $nama_tim, $jenis_lomba, $ketua_nim, $ketua_nama, 
        $prodi_ketua, $tahun_angkatan, $no_wa, $status, $id_tim
    );
    
    if ($stmt_update->execute()) {
        // Hapus anggota lama
        $conn->query("DELETE FROM anggota_tim WHERE id_tim = $id_tim");
        
        // Simpan anggota baru jika ada
        if (!empty($_POST['anggota_nama']) && is_array($_POST['anggota_nama'])) {
            $sql_anggota = "INSERT INTO anggota_tim (id_tim, nama, nim, prodi, tahun_angkatan) VALUES (?, ?, ?, ?, ?)";
            $stmt_anggota_insert = $conn->prepare($sql_anggota);
            
            foreach ($_POST['anggota_nama'] as $index => $nama) {
                if (!empty($nama)) {
                    $nim = $_POST['anggota_nim'][$index] ?? '';
                    $prodi = $_POST['anggota_prodi'][$index] ?? '';
                    $tahun = $_POST['anggota_tahun'][$index] ?? '';
                    
                    $stmt_anggota_insert->bind_param("issss", $id_tim, $nama, $nim, $prodi, $tahun);
                    $stmt_anggota_insert->execute();
                }
            }
        }
        
        $_SESSION['alert_message'] = "Data tim berhasil diupdate!";
        $_SESSION['alert_type'] = 'success';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Gagal update: " . $stmt_update->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Tim</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, #1e88e5, #0d47a1);
            color: white;
            padding: 25px 40px;
        }
        
        .header h1 {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.8rem;
        }
        
        .form-container {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus, select:focus {
            border-color: #1e88e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.2);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .anggota-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            border: 2px solid #e9ecef;
        }
        
        .anggota-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .anggota-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f3f4;
        }
        
        .btn-add, .btn-remove {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-add {
            background: linear-gradient(to right, #28a745, #20c997);
            color: white;
        }
        
        .btn-remove {
            background: linear-gradient(to right, #dc3545, #c82333);
            color: white;
        }
        
        .btn-add:hover, .btn-remove:hover {
            transform: translateY(-2px);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        
        .btn-save, .btn-cancel {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-save {
            background: linear-gradient(to right, #1e88e5, #0d47a1);
            color: white;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-save:hover, .btn-cancel:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(0, 0, 0, 0.1);
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-pending {
            background: #f39c12;
            color: white;
        }
        
        .status-active {
            background: #27ae60;
            color: white;
        }
        
        .status-rejected {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-edit"></i> Edit Data Tim: 
                <?php echo htmlspecialchars($tim['nama_tim']); ?>
                <span class="status-badge status-<?php echo $tim['status']; ?>">
                    <?php echo strtoupper($tim['status']); ?>
                </span>
            </h1>
        </div>
        
        <div class="form-container">
            <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="editForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_tim"><i class="fas fa-flag"></i> Nama Tim</label>
                        <input type="text" id="nama_tim" name="nama_tim" 
                               value="<?php echo htmlspecialchars($tim['nama_tim']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jenis_lomba"><i class="fas fa-running"></i> Jenis Lomba</label>
                        <select id="jenis_lomba" name="jenis_lomba" required>
                            <option value="Futsal" <?php echo $tim['jenis_lomba'] == 'Futsal' ? 'selected' : ''; ?>>Futsal</option>
                            <option value="Basket" <?php echo $tim['jenis_lomba'] == 'Basket' ? 'selected' : ''; ?>>Basket</option>
                            <option value="Badminton" <?php echo $tim['jenis_lomba'] == 'Badminton' ? 'selected' : ''; ?>>Badminton</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ketua_nim"><i class="fas fa-id-card"></i> NIM Ketua</label>
                        <input type="text" id="ketua_nim" name="ketua_nim" 
                               value="<?php echo htmlspecialchars($tim['ketua_nim']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ketua_nama"><i class="fas fa-user"></i> Nama Ketua</label>
                        <input type="text" id="ketua_nama" name="ketua_nama" 
                               value="<?php echo htmlspecialchars($tim['ketua_nama']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prodi_ketua"><i class="fas fa-graduation-cap"></i> Program Studi</label>
                        <input type="text" id="prodi_ketua" name="prodi_ketua" 
                               value="<?php echo htmlspecialchars($tim['prodi_ketua']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tahun_angkatan"><i class="fas fa-calendar-alt"></i> Tahun Angkatan</label>
                        <input type="text" id="tahun_angkatan" name="tahun_angkatan" 
                               value="<?php echo htmlspecialchars($tim['tahun_angkatan']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="no_wa"><i class="fas fa-phone"></i> No. WhatsApp</label>
                        <input type="text" id="no_wa" name="no_wa" 
                               value="<?php echo htmlspecialchars($tim['no_wa']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                        <select id="status" name="status" required>
                            <option value="pending" <?php echo $tim['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="active" <?php echo $tim['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="rejected" <?php echo $tim['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>
                
                <div class="anggota-section">
                    <h3><i class="fas fa-users"></i> Anggota Tim</h3>
                    <div id="anggotaContainer">
                        <?php if ($anggota_result->num_rows > 0): ?>
                            <?php $counter = 0; while($anggota = $anggota_result->fetch_assoc()): ?>
                            <div class="anggota-item" data-index="<?php echo $counter; ?>">
                                <div class="anggota-header">
                                    <h4>Anggota <?php echo $counter + 1; ?></h4>
                                    <button type="button" class="btn-remove" onclick="removeAnggota(this)">
                                        <i class="fas fa-times"></i> Hapus
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input type="text" name="anggota_nama[]" 
                                               value="<?php echo htmlspecialchars($anggota['nama']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>NIM</label>
                                        <input type="text" name="anggota_nim[]" 
                                               value="<?php echo htmlspecialchars($anggota['nim']); ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Program Studi</label>
                                        <input type="text" name="anggota_prodi[]" 
                                               value="<?php echo htmlspecialchars($anggota['prodi']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Tahun Angkatan</label>
                                        <input type="text" name="anggota_tahun[]" 
                                               value="<?php echo htmlspecialchars($anggota['tahun_angkatan']); ?>">
                                    </div>
                                </div>
                            </div>
                            <?php $counter++; endwhile; ?>
                        <?php else: ?>
                            <!-- Anggota pertama sebagai contoh -->
                            <div class="anggota-item" data-index="0">
                                <div class="anggota-header">
                                    <h4>Anggota 1</h4>
                                    <button type="button" class="btn-remove" onclick="removeAnggota(this)" style="display: none;">
                                        <i class="fas fa-times"></i> Hapus
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input type="text" name="anggota_nama[]" placeholder="Nama anggota">
                                    </div>
                                    <div class="form-group">
                                        <label>NIM</label>
                                        <input type="text" name="anggota_nim[]" placeholder="NIM anggota">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Program Studi</label>
                                        <input type="text" name="anggota_prodi[]" placeholder="Program studi">
                                    </div>
                                    <div class="form-group">
                                        <label>Tahun Angkatan</label>
                                        <input type="text" name="anggota_tahun[]" placeholder="Tahun angkatan">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="button" class="btn-add" onclick="addAnggota()">
                        <i class="fas fa-plus"></i> Tambah Anggota
                    </button>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let anggotaCount = <?php echo $anggota_result->num_rows > 0 ? $anggota_result->num_rows : 1; ?>;
        
        function addAnggota() {
            anggotaCount++;
            
            const anggotaItem = document.createElement('div');
            anggotaItem.className = 'anggota-item';
            anggotaItem.innerHTML = `
                <div class="anggota-header">
                    <h4>Anggota ${anggotaCount}</h4>
                    <button type="button" class="btn-remove" onclick="removeAnggota(this)">
                        <i class="fas fa-times"></i> Hapus
                    </button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="anggota_nama[]" required placeholder="Nama anggota">
                    </div>
                    <div class="form-group">
                        <label>NIM</label>
                        <input type="text" name="anggota_nim[]" placeholder="NIM anggota">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Program Studi</label>
                        <input type="text" name="anggota_prodi[]" placeholder="Program studi">
                    </div>
                    <div class="form-group">
                        <label>Tahun Angkatan</label>
                        <input type="text" name="anggota_tahun[]" placeholder="Tahun angkatan">
                    </div>
                </div>
            `;
            
            document.getElementById('anggotaContainer').appendChild(anggotaItem);
            
            // Update tombol hapus untuk anggota pertama
            updateRemoveButtons();
        }
        
        function removeAnggota(button) {
            const anggotaItem = button.closest('.anggota-item');
            anggotaItem.remove();
            anggotaCount--;
            
            // Update nomor urut
            updateAnggotaNumbers();
            updateRemoveButtons();
        }
        
        function updateAnggotaNumbers() {
            const items = document.querySelectorAll('.anggota-item');
            items.forEach((item, index) => {
                item.querySelector('h4').textContent = `Anggota ${index + 1}`;
            });
        }
        
        function updateRemoveButtons() {
            const items = document.querySelectorAll('.anggota-item');
            const removeButtons = document.querySelectorAll('.btn-remove');
            
            // Sembunyikan tombol hapus jika hanya ada 1 anggota
            if (items.length === 1) {
                removeButtons[0].style.display = 'none';
            } else {
                removeButtons.forEach(btn => btn.style.display = 'flex');
            }
        }
        
        // Form validation
        document.getElementById('editForm').addEventListener('submit', function(e) {
            // Validasi minimal 1 anggota selain ketua
            const anggotaInputs = document.querySelectorAll('input[name="anggota_nama[]"]');
            let valid = true;
            
            anggotaInputs.forEach(input => {
                if (input.value.trim() === '') {
                    valid = false;
                    input.style.borderColor = 'red';
                    
                    setTimeout(() => {
                        input.style.borderColor = '';
                    }, 2000);
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Harap isi nama untuk semua anggota!');
            }
        });
        
        // Inisialisasi tombol hapus
        updateRemoveButtons();
    </script>
</body>
</html>
<?php
$conn->close();
?>
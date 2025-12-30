<?php
// hapus_tim.php
session_start();
require_once '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Ambil ID dari URL
$id_tim = $_GET['id'] ?? 0;
$confirm = $_GET['confirm'] ?? '';

if (!$id_tim) {
    header("Location: dashboard.php");
    exit();
}

// Ambil data tim untuk ditampilkan di konfirmasi
$sql = "SELECT * FROM tim_lomba WHERE id_tim = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_tim);
$stmt->execute();
$result = $stmt->get_result();
$tim = $result->fetch_assoc();

if (!$tim) {
    $_SESSION['alert_message'] = "❌ Tim tidak ditemukan!";
    $_SESSION['alert_type'] = 'error';
    header("Location: dashboard.php");
    exit();
}

// Jika konfirmasi hapus
if ($confirm === 'yes') {
    // Mulai transaction
    $conn->begin_transaction();
    
    try {
        // 1. Hapus anggota
        $stmt1 = $conn->prepare("DELETE FROM anggota_tim WHERE id_tim = ?");
        $stmt1->bind_param("i", $id_tim);
        $stmt1->execute();
        
        // 2. Hapus tim
        $stmt2 = $conn->prepare("DELETE FROM tim_lomba WHERE id_tim = ?");
        $stmt2->bind_param("i", $id_tim);
        $stmt2->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['alert_message'] = "✅ Tim '" . htmlspecialchars($tim['nama_tim']) . "' berhasil dihapus permanen!";
        $_SESSION['alert_type'] = 'success';
        
    } catch (Exception $e) {
        // Rollback jika error
        $conn->rollback();
        $_SESSION['alert_message'] = "❌ Gagal menghapus: " . $e->getMessage();
        $_SESSION['alert_type'] = 'error';
    }
    
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Hapus Tim</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .confirm-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .warning-icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .tim-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        
        .tim-info p {
            margin: 8px 0;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-delete {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(0,0,0,0.1);
        }
        
        .danger-note {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 15px;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
            border-left: 4px solid #e74c3c;
        }
    </style>
</head>
<body>
    <div class="confirm-container">
        <div class="warning-icon">⚠️</div>
        <h1>Konfirmasi Hapus Tim</h1>
        
        <p>Anda akan menghapus tim berikut secara permanen:</p>
        
        <div class="tim-info">
            <p><strong>ID Tim:</strong> <?php echo $tim['id_tim']; ?></p>
            <p><strong>Nama Tim:</strong> <?php echo htmlspecialchars($tim['nama_tim']); ?></p>
            <p><strong>Jenis Lomba:</strong> <?php echo htmlspecialchars($tim['jenis_lomba']); ?></p>
            <p><strong>Ketua Tim:</strong> <?php echo htmlspecialchars($tim['ketua_nama']); ?></p>
            <p><strong>Status:</strong> <?php echo strtoupper($tim['status']); ?></p>
        </div>
        
        <div class="danger-note">
            <strong>PERINGATAN:</strong> Aksi ini tidak dapat dibatalkan! Semua data tim dan anggota akan dihapus permanen dari database.
        </div>
        
        <div class="button-group">
            <a href="hapus_tim.php?id=<?php echo $id_tim; ?>&confirm=yes" class="btn btn-delete">
                🗑️ Ya, Hapus Permanen
            </a>
            <a href="dashboard.php" class="btn btn-cancel">
                ❌ Batal
            </a>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
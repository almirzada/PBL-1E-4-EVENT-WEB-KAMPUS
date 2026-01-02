<?php
// detail_tim.php
session_start();
require_once '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Akses ditolak!");
}

$id_tim = $_GET['id'] ?? 0;

if (!$id_tim) {
    die("ID tim tidak valid!");
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        .detail-container {
            padding: 20px;
            max-width: 800px;
        }
        
        .detail-header {
            background: linear-gradient(to right, #1e88e5, #0d47a1);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            width: 200px;
            color: #333;
        }
        
        .detail-value {
            flex: 1;
            color: #555;
        }
        
        .anggota-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .anggota-table th, .anggota-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .anggota-table th {
            background: #34495e;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
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
        
        .close-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            float: right;
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <div class="detail-header">
            <h2>📋 Detail Tim: <?php echo htmlspecialchars($tim['nama_tim']); ?></h2>
            <span class="status-badge status-<?php echo $tim['status']; ?>">
                <?php echo strtoupper($tim['status']); ?>
            </span>
        </div>
        
        <div class="detail-section">
            <h3>📝 Informasi Tim</h3>
            <div class="detail-row">
                <div class="detail-label">ID Tim:</div>
                <div class="detail-value"><?php echo $tim['id_tim']; ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Nama Tim:</div>
                <div class="detail-value"><?php echo htmlspecialchars($tim['nama_tim']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Jenis Lomba:</div>
                <div class="detail-value"><?php echo htmlspecialchars($tim['jenis_lomba']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Ketua Tim:</div>
                <div class="detail-value"><?php echo htmlspecialchars($tim['ketua_nama']); ?> (NIM: <?php echo $tim['ketua_nim']; ?>)</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Program Studi:</div>
                <div class="detail-value"><?php echo htmlspecialchars($tim['prodi_ketua']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tahun Angkatan:</div>
                <div class="detail-value"><?php echo $tim['tahun_angkatan']; ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">No. WhatsApp:</div>
                <div class="detail-value"><?php echo $tim['no_wa'] ?: '-'; ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Tanggal Daftar:</div>
                <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($tim['tanggal_daftar'])); ?></div>
            </div>
        </div>
        
        <?php if ($anggota_result->num_rows > 0): ?>
        <div class="detail-section">
            <h3>👥 Anggota Tim (<?php echo $anggota_result->num_rows; ?> orang)</h3>
            <table class="anggota-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>NIM</th>
                        <th>Program Studi</th>
                        <th>Tahun Angkatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; while($anggota = $anggota_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($anggota['nama']); ?></td>
                        <td><?php echo $anggota['nim']; ?></td>
                        <td><?php echo htmlspecialchars($anggota['prodi']); ?></td>
                        <td><?php echo $anggota['tahun_angkatan']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="detail-section">
            <h3>👥 Anggota Tim</h3>
            <p>Tidak ada data anggota selain ketua tim.</p>
        </div>
        <?php endif; ?>
        
        <button class="close-btn" onclick="window.parent.closeModal()">❌ Tutup</button>
    </div>
</body>
</html>
<?php
$conn->close();
?>
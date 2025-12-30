<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
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

// AMBIL ID TIM DARI URL
$id_tim = $_GET['id'] ?? 0;

// AMBIL DATA TIM
$sql_tim = "SELECT * FROM tim WHERE id_tim = ?";
$stmt_tim = $conn->prepare($sql_tim);
$stmt_tim->bind_param("i", $id_tim);
$stmt_tim->execute();
$result_tim = $stmt_tim->get_result();
$tim = $result_tim->fetch_assoc();

if (!$tim) {
    die("Tim tidak ditemukan!");
}

// AMBIL DATA ANGGOTA
$sql_anggota = "SELECT * FROM anggota WHERE id_tim = ? ORDER BY 
                CASE WHEN peran = 'ketua' THEN 1 ELSE 2 END, nama";
$stmt_anggota = $conn->prepare($sql_anggota);
$stmt_anggota->bind_param("i", $id_tim);
$stmt_anggota->execute();
$result_anggota = $stmt_anggota->get_result();
$anggota = [];
while ($row = $result_anggota->fetch_assoc()) {
    $anggota[] = $row;
}

$conn->close();

// FUNGSI UNTUK FORMAT STATUS
function getStatusBadge($status) {
    switch($status) {
        case 'pending': return '<span class="badge badge-pending">⏳ Pending</span>';
        case 'verified': return '<span class="badge badge-verified">✅ Verified</span>';
        case 'rejected': return '<span class="badge badge-rejected">❌ Rejected</span>';
        default: return '<span class="badge">' . $status . '</span>';
    }
}

// FUNGSI FORMAT TANGGAL
function formatDate($dateString) {
    if (!$dateString) return '-';
    return date('d/m/Y H:i', strtotime($dateString));
}

// AMBIL KETUA
$ketua = null;
foreach ($anggota as $a) {
    if ($a['peran'] == 'ketua') {
        $ketua = $a;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tim - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
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
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, #1e88e5, #0d47a1);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: white;
            color: #1e88e5;
            transform: translateY(-3px);
        }
        
        .content {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            border-left: 5px solid #1e88e5;
        }
        
        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .info-label {
            font-weight: 600;
            color: #1e88e5;
            width: 180px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-pending { background: #ffc107; color: #000; }
        .badge-verified { background: #28a745; color: white; }
        .badge-rejected { background: #dc3545; color: white; }
        
        .anggota-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .anggota-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .anggota-table th {
            background: #34495e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .anggota-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .anggota-table tr:hover {
            background: #e8f4fc;
        }
        
        .anggota-table tr:last-child td {
            border-bottom: none;
        }
        
        .ketua-badge {
            background: #ffeaa7;
            color: #000;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .action-btns {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-verify {
            background: #28a745;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete {
            background: #6c757d;
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            filter: brightness(110%);
        }
        
        .wa-link {
            color: #25D366;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wa-link:hover {
            text-decoration: underline;
        }
        
        .timestamp {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-label {
                width: 100%;
            }
            
            .action-btns {
                flex-direction: column;
            }
            
            .action-btn {
                justify-content: center;
            }
            
            .anggota-table {
                font-size: 0.9rem;
            }
            
            .anggota-table th,
            .anggota-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👁️ Detail Tim Pendaftar</h1>
            <a href="dashboard.php" class="back-btn">← Kembali ke Dashboard</a>
        </div>
        
        <div class="content">
            <div class="info-grid">
                <!-- KARTU 1: INFORMASI TIM -->
                <div class="info-card">
                    <h3>📋 Informasi Tim</h3>
                    <div class="info-row">
                        <div class="info-label">Nama Tim:</div>
                        <div class="info-value"><?php echo htmlspecialchars($tim['nama_tim']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Jenis Lomba:</div>
                        <div class="info-value">
                            <?php 
                            $badge_class = '';
                            switch($tim['jenis_lomba']) {
                                case 'Futsal': $badge_class = 'background: #ff6b6b; color: white;'; break;
                                case 'Basket': $badge_class = 'background: #4ecdc4; color: white;'; break;
                                case 'Badminton': $badge_class = 'background: #ffe66d; color: #333;'; break;
                            }
                            ?>
                            <span style="padding: 5px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: 600; <?php echo $badge_class; ?>">
                                <?php echo $tim['jenis_lomba']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <?php echo getStatusBadge($tim['status']); ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Daftar:</div>
                        <div class="info-value">
                            <?php echo formatDate($tim['tanggal_daftar']); ?>
                            <div class="timestamp"><?php echo date('l, d F Y', strtotime($tim['tanggal_daftar'])); ?></div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Verifikasi:</div>
                        <div class="info-value">
                            <?php echo formatDate($tim['tanggal_verifikasi']); ?>
                            <?php if($tim['tanggal_verifikasi']): ?>
                            <div class="timestamp"><?php echo date('l, d F Y', strtotime($tim['tanggal_verifikasi'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">ID Tim:</div>
                        <div class="info-value">
                            <code style="background: #eee; padding: 3px 8px; border-radius: 5px; font-family: monospace;">
                                TIM-<?php echo str_pad($tim['id_tim'], 4, '0', STR_PAD_LEFT); ?>
                            </code>
                        </div>
                    </div>
                </div>
                
                <!-- KARTU 2: KETUA TIM -->
                <div class="info-card">
                    <h3>👑 Ketua Tim</h3>
                    <?php if($ketua): ?>
                    <div class="info-row">
                        <div class="info-label">Nama Lengkap:</div>
                        <div class="info-value"><?php echo htmlspecialchars($ketua['nama']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NIM:</div>
                        <div class="info-value"><?php echo htmlspecialchars($ketua['nim']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Program Studi:</div>
                        <div class="info-value"><?php echo htmlspecialchars($ketua['program_studi']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tahun Angkatan:</div>
                        <div class="info-value"><?php echo $ketua['tahun_angkatan']; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">No. WhatsApp:</div>
                        <div class="info-value">
                            <?php if(!empty($ketua['no_wa'])): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $ketua['no_wa']); ?>" 
                               target="_blank" 
                               class="wa-link">
                               📱 <?php echo htmlspecialchars($ketua['no_wa']); ?>
                            </a>
                            <?php else: ?>
                            <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="empty-message">
                        <p>Data ketua tidak ditemukan.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- DATA ANGGOTA -->
            <div class="anggota-section">
                <h3>👥 Data Anggota Tim (Total: <?php echo count($anggota); ?> orang)</h3>
                
                <?php if(count($anggota) > 0): ?>
                <table class="anggota-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>NIM</th>
                            <th>Program Studi</th>
                            <th>Tahun</th>
                            <th>Peran</th>
                            <th>No. WA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($anggota as $index => $a): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php echo htmlspecialchars($a['nama']); ?>
                                <?php if($a['peran'] == 'ketua'): ?>
                                <span class="ketua-badge">Ketua</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($a['nim']); ?></td>
                            <td><?php echo htmlspecialchars($a['program_studi']); ?></td>
                            <td><?php echo $a['tahun_angkatan']; ?></td>
                            <td>
                                <?php echo $a['peran'] == 'ketua' ? '👑 Ketua' : 'Anggota'; ?>
                            </td>
                            <td>
                                <?php if(!empty($a['no_wa'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $a['no_wa']); ?>" 
                                   target="_blank" 
                                   style="color: #25D366; text-decoration: none;">
                                   📱
                                </a>
                                <?php else: ?>
                                <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-message">
                    <p>Belum ada data anggota.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TOMBOL AKSI -->
            <div class="action-btns">
                <?php if($tim['status'] == 'pending'): ?>
                <a href="verifikasi.php?action=terima&id=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-verify"
                   onclick="return confirm('Terima tim <?php echo htmlspecialchars($tim['nama_tim']); ?>?')">
                    ✅ Terima Tim
                </a>
                <a href="verifikasi.php?action=tolak&id=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-reject"
                   onclick="return confirm('Tolak tim <?php echo htmlspecialchars($tim['nama_tim']); ?>?')">
                    ❌ Tolak Tim
                </a>
                <?php elseif($tim['status'] == 'verified'): ?>
                <a href="edit_tim.php?id=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-edit">
                    ✏️ Edit Data
                </a>
                <a href="verifikasi.php?action=tolak&id=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-reject"
                   onclick="return confirm('Batalkan verifikasi tim <?php echo htmlspecialchars($tim['nama_tim']); ?>?')">
                    ❌ Batalkan Verifikasi
                </a>
                <?php elseif($tim['status'] == 'rejected'): ?>
                <a href="verifikasi.php?action=terima&id=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-verify"
                   onclick="return confirm('Kembalikan tim <?php echo htmlspecialchars($tim['nama_tim']); ?> ke pending?')">
                    🔄 Kembalikan ke Pending
                </a>
                <a href="edit_tim.php?id=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-edit">
                    ✏️ Edit Data
                </a>
                <?php endif; ?>
                
                <a href="dashboard.php" 
                   class="action-btn" 
                   style="background: #1e88e5; color: white;">
                    ← Dashboard
                </a>
                
                <a href="?delete=<?php echo $tim['id_tim']; ?>" 
                   class="action-btn btn-delete"
                   onclick="return confirm('Hapus tim <?php echo htmlspecialchars($tim['nama_tim']); ?>?\\nSemua data anggota akan ikut terhapus!')">
                    🗑️ Hapus Tim
                </a>
            </div>
        </div>
    </div>
    
    <?php
    // HANDLE DELETE
    if(isset($_GET['delete'])) {
        $delete_id = $_GET['delete'];
        if($delete_id == $id_tim) {
            $conn = new mysqli($host, $user, $pass, $dbname);
            
            // Hapus anggota dulu
            $sql_delete_anggota = "DELETE FROM anggota WHERE id_tim = ?";
            $stmt1 = $conn->prepare($sql_delete_anggota);
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();
            
            // Hapus tim
            $sql_delete_tim = "DELETE FROM tim WHERE id_tim = ?";
            $stmt2 = $conn->prepare($sql_delete_tim);
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();
            
            $conn->close();
            
            echo "<script>
                alert('Tim berhasil dihapus!');
                window.location.href = 'dashboard.php';
            </script>";
            exit();
        }
    }
    ?>
    
    <script>
    // Print function
    function printDetail() {
        window.print();
    }
    
    // Add print button if needed
    document.addEventListener('DOMContentLoaded', function() {
        const actionBtns = document.querySelector('.action-btns');
        const printBtn = document.createElement('a');
        printBtn.className = 'action-btn';
        printBtn.style.background = '#17a2b8';
        printBtn.style.color = 'white';
        printBtn.innerHTML = '🖨️ Cetak';
        printBtn.onclick = printDetail;
        actionBtns.insertBefore(printBtn, actionBtns.firstChild);
    });
    </script>
</body>
</html>
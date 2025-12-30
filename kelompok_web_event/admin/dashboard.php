<?php
session_start();
// PROTEKSI LOGIN
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

// HITUNG STATISTIK
$total_pending = $conn->query("SELECT COUNT(*) as total FROM tim WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$total_verified = $conn->query("SELECT COUNT(*) as total FROM tim WHERE status='verified'")->fetch_assoc()['total'] ?? 0;
$total_rejected = $conn->query("SELECT COUNT(*) as total FROM tim WHERE status='rejected'")->fetch_assoc()['total'] ?? 0;
$total_tim = $total_pending + $total_verified + $total_rejected;

// AMBIL DATA PER STATUS UNTUK TAB
$sql_pending = "SELECT t.*, 
                (SELECT COUNT(*) FROM anggota a WHERE a.id_tim = t.id_tim) as jumlah_anggota,
                (SELECT nama FROM anggota a WHERE a.id_tim = t.id_tim AND a.peran='ketua' LIMIT 1) as ketua_nama,
                (SELECT no_wa FROM anggota a WHERE a.id_tim = t.id_tim AND a.peran='ketua' LIMIT 1) as ketua_wa
                FROM tim t 
                WHERE t.status='pending' 
                ORDER BY t.tanggal_daftar DESC";
$result_pending = $conn->query($sql_pending);

$sql_verified = "SELECT t.*, 
                 (SELECT COUNT(*) FROM anggota a WHERE a.id_tim = t.id_tim) as jumlah_anggota,
                 (SELECT nama FROM anggota a WHERE a.id_tim = t.id_tim AND a.peran='ketua' LIMIT 1) as ketua_nama,
                 (SELECT no_wa FROM anggota a WHERE a.id_tim = t.id_tim AND a.peran='ketua' LIMIT 1) as ketua_wa
                 FROM tim t 
                 WHERE t.status='verified' 
                 ORDER BY t.tanggal_verifikasi DESC";
$result_verified = $conn->query($sql_verified);

$sql_rejected = "SELECT t.*, 
                 (SELECT COUNT(*) FROM anggota a WHERE a.id_tim = t.id_tim) as jumlah_anggota,
                 (SELECT nama FROM anggota a WHERE a.id_tim = t.id_tim AND a.peran='ketua' LIMIT 1) as ketua_nama
                 FROM tim t 
                 WHERE t.status='rejected' 
                 ORDER BY t.tanggal_daftar DESC";
$result_rejected = $conn->query($sql_rejected);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Data Tim Lomba</title>
    <style>
        /* RESET & BASE */
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

        /* CONTAINER UTAMA */
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        /* HEADER DASHBOARD */
        .dashboard-header {
            background: linear-gradient(to right, #1e88e5, #0d47a1);
            color: white;
            padding: 30px 40px;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            font-size: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .logo-text p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.4);
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: white;
            color: #1e88e5;
            transform: translateY(-3px);
        }

        /* STATS CARDS */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* TAB NAVIGATION */
        .tab-container {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 25px;
            background: #f8f9fa;
            border-radius: 10px 10px 0 0;
            overflow: hidden;
        }

        .tab-btn {
            flex: 1;
            padding: 18px;
            border: none;
            background: none;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #666;
        }

        .tab-btn.active {
            background: #1e88e5;
            color: white;
        }

        .tab-btn:hover:not(.active) {
            background: #e9ecef;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.5s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* TABLE SECTION */
        .table-section {
            padding: 40px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title h2 {
            color: #2c3e50;
            font-size: 1.8rem;
        }

        .add-btn {
            background: linear-gradient(to right, #28a745, #20c997);
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }

        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        /* TABLE STYLING */
        .table-container {
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        thead {
            background: linear-gradient(to right, #34495e, #2c3e50);
            color: white;
        }

        th {
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #e8f4fc;
        }

        td {
            padding: 18px 15px;
            color: #444;
            vertical-align: top;
        }

        /* BADGE */
        .badge-lomba {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-futsal {
            background: #ff6b6b;
            color: white;
        }

        .badge-basket {
            background: #4ecdc4;
            color: white;
        }

        .badge-badminton {
            background: #ffe66d;
            color: #333;
        }

        /* ACTION BUTTONS */
        .action-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .btn-view, .btn-verify, .btn-reject, .btn-edit, .btn-delete, .btn-restore {
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-verify {
            background: #28a745;
            color: white;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-restore {
            background: #6c757d;
            color: white;
        }

        .btn-view:hover, .btn-verify:hover, .btn-reject:hover, 
        .btn-edit:hover, .btn-delete:hover, .btn-restore:hover {
            transform: translateY(-2px);
            filter: brightness(110%);
        }

        /* FOOTER */
        .dashboard-footer {
            background: #f8f9fa;
            padding: 20px 40px;
            text-align: center;
            color: #666;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
        }

        /* NO DATA MESSAGE */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* MODAL DETAIL */
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
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 30px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 2rem;
            cursor: pointer;
            color: #666;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .header-top {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .tab-btn {
                padding: 15px 10px;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-section {
                padding: 20px;
            }

            .tab-btn span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- HEADER -->
        <header class="dashboard-header">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon">📊</div>
                    <div class="logo-text">
                        <h1>Admin Dashboard</h1>
                        <p>Politeknik Negeri Batam - Lomba Antar Jurusan</p>
                    </div>
                </div>
                <div class="user-info">
                    <div class="user-avatar">👨‍💼</div>
                    <a href="logout.php" class="logout-btn" onclick="return confirm('Yakin mau logout?')">
                        <span>🚪</span> Logout
                    </a>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="stats-container">
                <div class="stat-card">
                    <span class="stat-icon">📥</span>
                    <div class="stat-number"><?php echo $total_pending; ?></div>
                    <div class="stat-label">Menunggu Verifikasi</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $total_verified; ?></div>
                    <div class="stat-label">Tim Aktif</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">📅</span>
                    <div class="stat-number"><?php echo date('d/m/Y'); ?></div>
                    <div class="stat-label">Tanggal Hari Ini</div>
                </div>
                <div class="stat-card">
                    <span class="stat-icon">❌</span>
                    <div class="stat-number"><?php echo $total_rejected; ?></div>
                    <div class="stat-label">Tim Ditolak</div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="table-section">
            <div class="section-title">
                <h2>📋 Data Tim Lomba</h2>
               
            </div>

            <!-- TAB NAVIGATION -->
            <div class="tab-container">
                <button class="tab-btn active" onclick="showTab('pending')">
                    <span>⏳</span> Pending (<?php echo $total_pending; ?>)
                </button>
                <button class="tab-btn" onclick="showTab('verified')">
                    <span>✅</span> Aktif (<?php echo $total_verified; ?>)
                </button>
                <button class="tab-btn" onclick="showTab('rejected')">
                    <span>❌</span> Ditolak (<?php echo $total_rejected; ?>)
                </button>
            </div>

            <!-- TAB 1: PENDING -->
            <div id="tab-pending" class="tab-content active">
                <?php if ($result_pending->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Tim</th>
                                <th>Jenis Lomba</th>
                                <th>Ketua Tim</th>
                                <th>Jumlah Anggota</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result_pending->fetch_assoc()): 
                                $badge_class = 'badge-' . strtolower($row['jenis_lomba']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nama_tim']); ?></strong></td>
                                <td>
                                    <span class="badge-lomba <?php echo $badge_class; ?>">
                                        <?php echo $row['jenis_lomba']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['ketua_nama']); ?></td>
                                <td><?php echo $row['jumlah_anggota']; ?> orang</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_daftar'])); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" onclick="showDetail(<?php echo $row['id_tim']; ?>)">
                                            👁️ Detail
                                        </button>
                                        <a href="verifikasi.php?action=terima&id=<?php echo $row['id_tim']; ?>" 
                                           class="btn-verify" 
                                           onclick="return confirm('Terima tim <?php echo htmlspecialchars($row['nama_tim']); ?>?')">
                                            ✅ Terima
                                        </a>
                                        <a href="verifikasi.php?action=tolak&id=<?php echo $row['id_tim']; ?>" 
                                           class="btn-reject"
                                           onclick="return confirm('Tolak tim <?php echo htmlspecialchars($row['nama_tim']); ?>?')">
                                            ❌ Tolak
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📭</div>
                    <h3>Tidak ada tim yang menunggu verifikasi</h3>
                    <p>Semua tim sudah diproses.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: VERIFIED -->
            <div id="tab-verified" class="tab-content">
                <?php if ($result_verified->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Tim</th>
                                <th>Jenis Lomba</th>
                                <th>Ketua Tim</th>
                                <th>No. WA</th>
                                <th>Jumlah Anggota</th>
                                <th>Tanggal Verifikasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result_verified->fetch_assoc()): 
                                $badge_class = 'badge-' . strtolower($row['jenis_lomba']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nama_tim']); ?></strong></td>
                                <td>
                                    <span class="badge-lomba <?php echo $badge_class; ?>">
                                        <?php echo $row['jenis_lomba']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['ketua_nama']); ?></td>
                                <td>
                                    <?php if (!empty($row['ketua_wa'])): ?>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['ketua_wa']); ?>" 
                                       target="_blank" 
                                       style="color: #25D366; text-decoration: none;">
                                       📱 <?php echo htmlspecialchars($row['ketua_wa']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['jumlah_anggota']; ?> orang</td>
                                <td>
                                    <?php if ($row['tanggal_verifikasi']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($row['tanggal_verifikasi'])); ?>
                                    <?php else: ?>
                                    <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" onclick="showDetail(<?php echo $row['id_tim']; ?>)">
                                            👁️ Detail
                                        </button>
                                        <a href="edit_tim.php?id=<?php echo $row['id_tim']; ?>" class="btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <a href="?hapus_tim=<?php echo $row['id_tim']; ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('Hapus tim <?php echo htmlspecialchars($row['nama_tim']); ?>?')">
                                            🗑️ Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">✅</div>
                    <h3>Belum ada tim yang aktif</h3>
                    <p>Verifikasi tim dari tab "Pending" untuk menampilkan di sini.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: REJECTED -->
            <div id="tab-rejected" class="tab-content">
                <?php if ($result_rejected->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Tim</th>
                                <th>Jenis Lomba</th>
                                <th>Ketua Tim</th>
                                <th>Jumlah Anggota</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result_rejected->fetch_assoc()): 
                                $badge_class = 'badge-' . strtolower($row['jenis_lomba']);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['nama_tim']); ?></strong></td>
                                <td>
                                    <span class="badge-lomba <?php echo $badge_class; ?>">
                                        <?php echo $row['jenis_lomba']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['ketua_nama']); ?></td>
                                <td><?php echo $row['jumlah_anggota']; ?> orang</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal_daftar'])); ?></td>
                                <td>
                                 <!-- Di kolom aksi tabel -->
                                <td>
                                    <a href="detail_tim.php?id=<?php echo $row['id_tim']; ?>" class="btn-view">
                                        👁️ Detail
                                    </a>
                                    <!-- tombol lainnya -->
                                </td>
                                        <a href="verifikasi.php?action=restore&id=<?php echo $row['id_tim']; ?>" 
                                           class="btn-restore"
                                           onclick="return confirm('Kembalikan tim <?php echo htmlspecialchars($row['nama_tim']); ?> ke pending?')">
                                            🔄 Restore
                                        </a>
                                        <a href="?hapus_tim=<?php echo $row['id_tim']; ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('Hapus permanen tim <?php echo htmlspecialchars($row['nama_tim']); ?>?')">
                                            🗑️ Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">❌</div>
                    <h3>Tidak ada tim yang ditolak</h3>
                    <p>Semua tim diterima atau masih dalam proses.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- MODAL DETAIL TIM -->
        <div id="modalDetail" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <div id="modalBody">
                    <!-- Data akan diisi via JavaScript -->
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> - Sistem Pendaftaran Lomba Politeknik Negeri Batam</p>
            <p style="margin-top: 5px; font-size: 0.85rem;">
                Halaman admin • Total Tim: <strong><?php echo $total_tim; ?></strong> • 
                Terakhir diakses: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </footer>
    </div>

    <script>
        // FUNGSI TAB
        function showTab(tabName) {
            // Sembunyikan semua tab
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Tampilkan tab yang dipilih
            document.getElementById('tab-' + tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // FUNGSI MODAL DETAIL
        function showDetail(idTim) {
            fetch('detail_tim.php?id=' + idTim)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                    document.getElementById('modalDetail').style.display = 'flex';
                });
        }

        function closeModal() {
            document.getElementById('modalDetail').style.display = 'none';
        }

        // KONFIRMASI LOGOUT
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            if (!confirm('Yakin mau logout dari dashboard?')) {
                e.preventDefault();
            }
        });

        // TUTUP MODAL JIKA KLIK DI LUAR
        window.onclick = function(event) {
            const modal = document.getElementById('modalDetail');
            if (event.target == modal) {
                closeModal();
            }
        }
        // FUNGSI UNTUK TUTUP MODAL EDIT
function closeModalEdit() {
    document.getElementById('modalEdit').style.display = 'none';
    document.getElementById('modalEditBody').innerHTML = '';
}
    </script>
</body>
</html>
<?php $conn->close(); ?>
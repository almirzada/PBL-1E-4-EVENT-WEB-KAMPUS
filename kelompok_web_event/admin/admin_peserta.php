<?php
session_start();
require_once '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_event_id'])) {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_event_id'];

// Ambil semua data peserta
$peserta_query = "SELECT 
                    p.*, 
                    e.judul as event_judul,
                    e.tanggal as event_tanggal,
                    e.biaya_pendaftaran,
                    t.nama_tim,
                    t.kode_pendaftaran as kode_tim
                  FROM peserta p
                  LEFT JOIN events e ON p.event_id = e.id
                  LEFT JOIN tim_event t ON p.tim_id = t.id
                  ORDER BY p.created_at DESC";

$peserta_result = mysqli_query($conn, $peserta_query);

// Ambil semua event untuk filter
$events_query = "SELECT id, judul FROM events ORDER BY tanggal DESC";
$events_result = mysqli_query($conn, $events_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #f72585;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-wrapper {
            min-height: 100vh;
        }
        
        /* SIDEBAR */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        /* STATS CARDS */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stats-card.total { border-left-color: var(--primary); }
        .stats-card.verified { border-left-color: var(--success); }
        .stats-card.pending { border-left-color: var(--warning); }
        .stats-card.team { border-left-color: var(--secondary); }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .stats-card.total .stats-icon { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
        .stats-card.verified .stats-icon { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .stats-card.pending .stats-icon { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .stats-card.team .stats-icon { background: rgba(58, 12, 163, 0.1); color: var(--secondary); }
        
        /* TABLE */
        .data-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 30px;
        }
        
        .table-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 30px;
        }
        
        .table-body {
            padding: 0;
        }
        
        /* BADGES */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .badge-menunggu { background: #fff3cd; color: #856404; }
        .badge-terverifikasi { background: #d4edda; color: #155724; }
        .badge-ditolak { background: #f8d7da; color: #721c24; }
        .badge-gratis { background: #d1ecf1; color: #0c5460; }
        
        .badge-tipe {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .badge-individu { background: #e3f2fd; color: #1565c0; }
        .badge-ketua { background: #f3e5f5; color: #7b1fa2; }
        .badge-anggota { background: #e8f5e8; color: #2e7d32; }
        
        /* ACTION BUTTONS */
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .btn-view { background: #e3f2fd; color: #2196f3; }
        .btn-edit { background: #fff3cd; color: #ffc107; }
        .btn-delete { background: #f8d7da; color: #dc3545; }
        .btn-verify { background: #d4edda; color: #28a745; }
        
        /* FILTER SECTION */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .filter-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* MODAL */
        .modal-custom .modal-header {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            .sidebar .menu-text {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 20px;
            }
            
            .table-header {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
       <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header text-center">
                <h4><i class="fas fa-calendar-alt"></i> <span class="menu-text">PortalKampus</span></h4>
                <small class="menu-text">Admin Panel</small>
            </div>
            
            <div class="sidebar-menu">
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                    </a>
                    
                    <!-- EVENT MENU -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Event</small>
                        <a href="form.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Event</span>
                        </a>
                        <a href="daftar_event.php" class="nav-link">
                            <i class="fas fa-list"></i> <span class="menu-text">Semua Event</span>
                        </a>
                    </div>
                    
                    <!-- BERITA MENU -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Berita</small>
                        <a href="daftar_berita.php" class="nav-link">
                            <i class="fas fa-newspaper"></i> <span class="menu-text">Daftar Berita</span>
                        </a>
                        <a href="tambah_berita.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Berita</span>
                        </a>
                    </div>
                    
                    <!-- LAINNYA -->
                    <div class="menu-section mt-2">
                        <small class="px-3 d-block text-uppercase opacity-75">Lainnya</small>
                        <a href="pengaturan.php" class="nav-link">
                            <i class="fas fa-tags"></i> <span class="menu-text">Kategori</span>
                        </a>
                        <a href="admin_peserta.php" class="nav-link">
                            <i class="fas fa-users"></i> <span class="menu-text">Peserta</span>
                        </a>
                        <a href="pengaturan.php" class="nav-link">
                            <i class="fas fa-cog"></i> <span class="menu-text">Pengaturan</span>
                        </a>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top border-secondary">
                        <a href="../index.php" class="nav-link" target="_blank">
                            <i class="fas fa-external-link-alt"></i> <span class="menu-text">Lihat Website</span>
                        </a>
                        <a href="logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> <span class="menu-text">Keluar</span>
                        </a>
                    </div>
                </nav>
            </div>
        </div>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">
                        <i class="fas fa-users text-primary me-2"></i>
                        Data Peserta Event
                    </h3>
                    <p class="text-muted mb-0">Kelola dan monitor semua pendaftar event</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-2"></i> Export Excel
                    </button>
                    <button class="btn btn-primary" onclick="printTable()">
                        <i class="fas fa-print me-2"></i> Cetak
                    </button>
                </div>
            </div>
            
            <!-- STATISTICS -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card total">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 id="totalPeserta">0</h4>
                        <p class="text-muted mb-0">Total Peserta</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card verified">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4 id="totalVerified">0</h4>
                        <p class="text-muted mb-0">Terverifikasi</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card pending">
                        <div class="stats-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 id="totalPending">0</h4>
                        <p class="text-muted mb-0">Menunggu</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card team">
                        <div class="stats-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <h4 id="totalTeam">0</h4>
                        <p class="text-muted mb-0">Pendaftar Tim</p>
                    </div>
                </div>
            </div>
            
            <!-- FILTER SECTION -->
            <div class="filter-section">
                <h5 class="filter-title">
                    <i class="fas fa-filter"></i> Filter Data
                </h5>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Event</label>
                            <select class="form-select" id="filterEvent">
                                <option value="">Semua Event</option>
                                <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                    <option value="<?php echo $event['id']; ?>">
                                        <?php echo htmlspecialchars($event['judul']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Status Pembayaran</label>
                            <select class="form-select" id="filterStatus">
                                <option value="">Semua Status</option>
                                <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                                <option value="terverifikasi">Terverifikasi</option>
                                <option value="ditolak">Ditolak</option>
                                <option value="gratis">Gratis</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Tipe Peserta</label>
                            <select class="form-select" id="filterTipe">
                                <option value="">Semua Tipe</option>
                                <option value="individu">Individu</option>
                                <option value="ketua">Ketua Tim</option>
                                <option value="anggota">Anggota Tim</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="filterDate">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-outline-secondary" id="resetFilter">
                            <i class="fas fa-redo me-2"></i> Reset Filter
                        </button>
                    </div>
                    <div>
                        <button class="btn btn-primary" id="applyFilter">
                            <i class="fas fa-search me-2"></i> Terapkan Filter
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- DATA TABLE -->
            <div class="data-table-container">
                <div class="table-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 text-white">
                                <i class="fas fa-table me-2"></i>
                                Daftar Peserta Event
                            </h5>
                        </div>
                        <div class="text-white">
                            <span id="totalRecords">0</span> data ditemukan
                        </div>
                    </div>
                </div>
                
                <div class="table-body">
                    <div class="table-responsive">
                        <table id="pesertaTable" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>NPM</th>
                                    <th>Event</th>
                                    <th>Tim</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                mysqli_data_seek($peserta_result, 0);
                                while ($peserta = mysqli_fetch_assoc($peserta_result)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo $peserta['kode_pendaftaran']; ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm bg-light rounded">
                                                    <div class="avatar-title bg-soft-primary text-primary rounded">
                                                        <?php echo strtoupper(substr($peserta['nama'], 0, 1)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <strong><?php echo htmlspecialchars($peserta['nama']); ?></strong>
                                                <div class="text-muted">
                                                    <?php echo htmlspecialchars($peserta['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($peserta['npm']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($peserta['event_judul']); ?></strong>
                                        <div class="text-muted">
                                            <?php echo date('d M Y', strtotime($peserta['event_tanggal'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($peserta['nama_tim'])): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($peserta['nama_tim']); ?></span>
                                            <div class="text-muted small"><?php echo $peserta['kode_tim']; ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Individu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-tipe badge-<?php echo $peserta['status_anggota']; ?>">
                                            <?php echo ucfirst($peserta['status_anggota']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($peserta['status_pembayaran']) {
                                            case 'terverifikasi': $statusClass = 'terverifikasi'; break;
                                            case 'menunggu_verifikasi': $statusClass = 'menunggu'; break;
                                            case 'ditolak': $statusClass = 'ditolak'; break;
                                            default: $statusClass = 'gratis';
                                        }
                                        ?>
                                        <span class="badge-status badge-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $peserta['status_pembayaran'])); ?>
                                        </span>
                                        <?php if ($peserta['biaya_pendaftaran'] > 0): ?>
                                            <div class="text-muted small">
                                                Rp <?php echo number_format($peserta['biaya_pendaftaran'], 0, ',', '.'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($peserta['created_at'])); ?>
                                        <div class="text-muted small">
                                            <?php echo date('H:i', strtotime($peserta['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="action-btn btn-view" 
                                                    onclick="viewDetail(<?php echo $peserta['id']; ?>)"
                                                    title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($peserta['status_pembayaran'] == 'menunggu_verifikasi'): ?>
                                                <button class="action-btn btn-verify" 
                                                        onclick="verifyPayment(<?php echo $peserta['id']; ?>, '<?php echo $peserta['nama']; ?>')"
                                                        title="Verifikasi Pembayaran">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="action-btn btn-edit" 
                                                    onclick="editPeserta(<?php echo $peserta['id']; ?>)"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button class="action-btn btn-delete" 
                                                    onclick="deletePeserta(<?php echo $peserta['id']; ?>, '<?php echo htmlspecialchars($peserta['nama']); ?>')"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Portal Informasi Kampus - Admin Panel
                    | Total Data: <?php echo mysqli_num_rows($peserta_result); ?> Peserta
                </small>
            </footer>
        </div>
    </div>
    
    <!-- MODAL DETAIL -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content modal-custom">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-circle me-2"></i>
                        Detail Peserta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Detail akan diisi via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="printDetail()">
                        <i class="fas fa-print me-2"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL VERIFIKASI -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content modal-custom">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>
                        Verifikasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="verifyContent">
                        <p>Verifikasi pembayaran untuk: <strong id="verifyName"></strong></p>
                        
                        <div class="mb-3">
                            <label class="form-label">Status Verifikasi</label>
                            <select class="form-select" id="verifyStatus">
                                <option value="terverifikasi">✅ Terverifikasi</option>
                                <option value="ditolak">❌ Ditolak</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" id="verifyNote" rows="3" 
                                      placeholder="Berikan catatan jika perlu..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Status akan dikirimkan via email kepada peserta.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="confirmVerify">
                        <i class="fas fa-check me-2"></i> Konfirmasi
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script>
        // Inisialisasi DataTable
        let dataTable;
        let currentPesertaId = null;
        
        $(document).ready(function() {
            // Inisialisasi DataTable
            dataTable = $('#pesertaTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                },
                order: [[8, 'desc']], // Urutkan berdasarkan tanggal terbaru
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
            });
            
            // Hitung statistik
            updateStatistics();
            
            // Apply filter
            $('#applyFilter').click(function() {
                applyFilters();
            });
            
            // Reset filter
            $('#resetFilter').click(function() {
                $('#filterEvent').val('');
                $('#filterStatus').val('');
                $('#filterTipe').val('');
                $('#filterDate').val('');
                applyFilters();
            });
            
            // Enter untuk filter
            $('#filterEvent, #filterStatus, #filterTipe, #filterDate').on('keypress', function(e) {
                if (e.which == 13) {
                    applyFilters();
                }
            });
            
            // Update total records
            dataTable.on('draw', function() {
                updateStatistics();
                $('#totalRecords').text(dataTable.rows({search: 'applied'}).count());
            });
        });
        
        // FUNGSI FILTER
        function applyFilters() {
            let eventFilter = $('#filterEvent').val();
            let statusFilter = $('#filterStatus').val();
            let tipeFilter = $('#filterTipe').val();
            let dateFilter = $('#filterDate').val();
            
            // Reset semua filter
            dataTable.columns().search('').draw();
            
            // Filter event (kolom 4)
            if (eventFilter) {
                dataTable.column(4).search(eventFilter, true, false).draw();
            }
            
            // Filter status pembayaran (kolom 7)
            if (statusFilter) {
                dataTable.column(7).search(statusFilter, true, false).draw();
            }
            
            // Filter tipe peserta (kolom 6)
            if (tipeFilter) {
                dataTable.column(6).search(tipeFilter, true, false).draw();
            }
            
            // Filter tanggal (kolom 8)
            if (dateFilter) {
                dataTable.column(8).search(dateFilter, true, false).draw();
            }
        }
        
        // FUNGSI STATISTIK
        function updateStatistics() {
            const totalRows = dataTable.rows({search: 'applied'}).count();
            $('#totalPeserta').text(totalRows);
            
            // Hitung berdasarkan status pembayaran
            const verifiedCount = dataTable.column(7).data().toArray().filter(status => 
                status.includes('terverifikasi')
            ).length;
            
            const pendingCount = dataTable.column(7).data().toArray().filter(status => 
                status.includes('menunggu')
            ).length;
            
            // Hitung tim (kolom 5 bukan "Individu")
            const teamCount = dataTable.column(5).data().toArray().filter(tim => 
                !tim.includes('Individu')
            ).length;
            
            $('#totalVerified').text(verifiedCount);
            $('#totalPending').text(pendingCount);
            $('#totalTeam').text(teamCount);
        }
        
        // FUNGSI LIHAT DETAIL
        function viewDetail(pesertaId) {
            currentPesertaId = pesertaId;
            
            // Tampilkan loading
            $('#detailContent').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Memuat data peserta...</p>
                </div>
            `);
            
            // Ambil data via AJAX
            $.ajax({
                url: 'ajax_get_peserta.php',
                type: 'GET',
                data: { id: pesertaId },
                success: function(response) {
                    $('#detailContent').html(response);
                },
                error: function() {
                    $('#detailContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Gagal memuat data peserta.
                        </div>
                    `);
                }
            });
            
            $('#detailModal').modal('show');
        }
        
        // FUNGSI VERIFIKASI PEMBAYARAN
        function verifyPayment(pesertaId, pesertaName) {
            currentPesertaId = pesertaId;
            $('#verifyName').text(pesertaName);
            $('#verifyStatus').val('terverifikasi');
            $('#verifyNote').val('');
            $('#verifyModal').modal('show');
        }
        
        // Konfirmasi verifikasi
        $('#confirmVerify').click(function() {
            const status = $('#verifyStatus').val();
            const note = $('#verifyNote').val();
            
            $.ajax({
                url: 'ajax_verify_payment.php',
                type: 'POST',
                data: {
                    id: currentPesertaId,
                    status: status,
                    note: note
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Pembayaran berhasil diverifikasi!');
                        $('#verifyModal').modal('hide');
                        location.reload(); // Refresh halaman
                    } else {
                        alert('Gagal memverifikasi: ' + result.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat memverifikasi.');
                }
            });
        });
        
        // FUNGSI EDIT
        function editPeserta(pesertaId) {
            alert('Fitur edit akan segera tersedia!');
            // window.location.href = 'edit_peserta.php?id=' + pesertaId;
        }
        
        // FUNGSI HAPUS
        function deletePeserta(pesertaId, pesertaName) {
            if (confirm(`Apakah Anda yakin ingin menghapus peserta:\n${pesertaName}?`)) {
                $.ajax({
                    url: 'ajax_delete_peserta.php',
                    type: 'POST',
                    data: { id: pesertaId },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            alert('Peserta berhasil dihapus!');
                            location.reload(); // Refresh halaman
                        } else {
                            alert('Gagal menghapus: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus.');
                    }
                });
            }
        }
        
        // FUNGSI EXPORT EXCEL
        function exportToExcel() {
            // Ambil data dari DataTable
            const data = dataTable.rows({search: 'applied'}).data().toArray();
            
            // Format data untuk Excel
            const excelData = [
                ['No', 'Kode', 'Nama', 'NPM', 'Email', 'Event', 'Tim', 'Status', 'Pembayaran', 'Tanggal Daftar']
            ];
            
            data.forEach((row, index) => {
                excelData.push([
                    index + 1,
                    row[1], // Kode
                    row[2].match(/<strong>([^<]+)<\/strong>/)?.[1] || row[2], // Nama
                    row[3], // NPM
                    row[2].match(/class="text-muted">([^<]+)<\/div>/)?.[1] || '', // Email
                    row[4].match(/<strong>([^<]+)<\/strong>/)?.[1] || row[4], // Event
                    row[5], // Tim
                    row[6], // Status
                    row[7], // Pembayaran
                    row[8]  // Tanggal
                ]);
            });
            
            // Buat workbook
            const ws = XLSX.utils.aoa_to_sheet(excelData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Data Peserta");
            
            // Download
            const date = new Date().toISOString().split('T')[0];
            XLSX.writeFile(wb, `data-peserta-${date}.xlsx`);
        }
        
        // FUNGSI PRINT
        function printTable() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Data Peserta Event</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #333; border-bottom: 2px solid #4361ee; padding-bottom: 10px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; text-align: left; }
                        td { border: 1px solid #dee2e6; padding: 10px; }
                        .text-center { text-align: center; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h1>Data Peserta Event</h1>
                    <p>Tanggal cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                    <p>Total data: ${dataTable.rows({search: 'applied'}).count()}</p>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>NPM</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${dataTable.rows({search: 'applied'}).data().toArray().map((row, index) => `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${row[1]}</td>
                                    <td>${$(row[2]).text()}</td>
                                    <td>${row[3]}</td>
                                    <td>${$(row[4]).text()}</td>
                                    <td>${$(row[6]).text()}</td>
                                    <td>${$(row[7]).text()}</td>
                                    <td>${row[8]}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="no-print" style="margin-top: 30px; text-align: center;">
                        <button onclick="window.print()">Cetak</button>
                        <button onclick="window.close()">Tutup</button>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        // PRINT DETAIL
        function printDetail() {
            const printContent = document.getElementById('detailContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Detail Peserta</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h3 { color: #333; border-bottom: 2px solid #4361ee; padding-bottom: 10px; }
                        .info-row { margin: 10px 0; }
                        .label { font-weight: bold; display: inline-block; width: 150px; }
                    </style>
                </head>
                <body>
                    ${printContent}
                    <div style="margin-top: 30px; text-align: center;">
                        <button onclick="window.print()">Cetak</button>
                        <button onclick="window.close()">Tutup</button>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        // Auto refresh setiap 30 detik untuk update realtime
        setInterval(() => {
            // Cek jika ada filter aktif
            if (!dataTable.search() && $('#filterEvent').val() === '') {
                // Refresh data
                dataTable.ajax.reload(null, false);
            }
        }, 30000);
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['admin_event_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">ID tidak valid</div>';
    exit();
}

$peserta_id = intval($_GET['id']);

// Ambil detail peserta
$query = "SELECT 
            p.*, 
            e.judul as event_judul,
            e.tanggal as event_tanggal,
            e.lokasi as event_lokasi,
            e.waktu as event_waktu,
            e.biaya_pendaftaran,
            t.nama_tim,
            t.kode_pendaftaran as kode_tim,
            t.jumlah_anggota,
            t.bukti_pembayaran as bukti_tim
          FROM peserta p
          LEFT JOIN events e ON p.event_id = e.id
          LEFT JOIN tim_event t ON p.tim_id = t.id
          WHERE p.id = $peserta_id";

$result = mysqli_query($conn, $query);
$peserta = mysqli_fetch_assoc($result);

if (!$peserta) {
    echo '<div class="alert alert-danger">Data peserta tidak ditemukan</div>';
    exit();
}

// Format status pembayaran
$statusClass = '';
switch($peserta['status_pembayaran']) {
    case 'terverifikasi': $statusClass = 'success'; break;
    case 'menunggu_verifikasi': $statusClass = 'warning'; break;
    case 'ditolak': $statusClass = 'danger'; break;
    default: $statusClass = 'info';
}

// Format status anggota
$anggotaClass = '';
switch($peserta['status_anggota']) {
    case 'ketua': $anggotaClass = 'primary'; break;
    case 'anggota': $anggotaClass = 'secondary'; break;
    default: $anggotaClass = 'info';
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="text-center mb-4">
            <div class="avatar-lg bg-light rounded-circle d-inline-flex align-items-center justify-content-center">
                <div class="avatar-title bg-soft-primary text-primary rounded-circle" style="width: 100px; height: 100px; font-size: 3rem;">
                    <?php echo strtoupper(substr($peserta['nama'], 0, 1)); ?>
                </div>
            </div>
            <h4 class="mt-3"><?php echo htmlspecialchars($peserta['nama']); ?></h4>
            <p class="text-muted"><?php echo htmlspecialchars($peserta['npm']); ?></p>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title"><i class="fas fa-id-card me-2"></i> Informasi Pribadi</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($peserta['email']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>WhatsApp:</strong></td>
                        <td><?php echo htmlspecialchars($peserta['no_wa']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Jurusan:</strong></td>
                        <td><?php echo htmlspecialchars($peserta['jurusan']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title"><i class="fas fa-calendar-alt me-2"></i> Informasi Event</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Event:</strong></td>
                        <td><?php echo htmlspecialchars($peserta['event_judul']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal:</strong></td>
                        <td><?php echo date('d F Y', strtotime($peserta['event_tanggal'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Lokasi:</strong></td>
                        <td><?php echo htmlspecialchars($peserta['event_lokasi']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Waktu:</strong></td>
                        <td><?php echo !empty($peserta['event_waktu']) ? date('H:i', strtotime($peserta['event_waktu'])) . ' WIB' : '-'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Biaya:</strong></td>
                        <td>
                            <?php if ($peserta['biaya_pendaftaran'] > 0): ?>
                                Rp <?php echo number_format($peserta['biaya_pendaftaran'], 0, ',', '.'); ?>
                            <?php else: ?>
                                <span class="badge bg-success">Gratis</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-users me-2"></i> Status Peserta</h6>
                        <div class="text-center">
                            <span class="badge bg-<?php echo $anggotaClass; ?> px-3 py-2 mb-2">
                                <?php echo ucfirst($peserta['status_anggota']); ?>
                            </span>
                            <?php if (!empty($peserta['nama_tim'])): ?>
                                <p class="mb-1"><strong>Tim:</strong> <?php echo htmlspecialchars($peserta['nama_tim']); ?></p>
                                <p class="mb-0"><small>Kode: <?php echo $peserta['kode_tim']; ?></small></p>
                                <p class="mb-0"><small>Anggota: <?php echo $peserta['jumlah_anggota']; ?> orang</small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-money-bill-wave me-2"></i> Status Pembayaran</h6>
                        <div class="text-center">
                            <span class="badge bg-<?php echo $statusClass; ?> px-3 py-2 mb-2">
                                <?php echo ucfirst(str_replace('_', ' ', $peserta['status_pembayaran'])); ?>
                            </span>
                            <?php if (!empty($peserta['bukti_pembayaran'])): ?>
                                <p class="mb-2">
                                    <strong>Bukti:</strong> 
                                    <?php echo $peserta['bukti_pembayaran']; ?>
                                </p>
                                <a href="../uploads/bukti_pembayaran/<?php echo $peserta['bukti_pembayaran']; ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> Lihat Bukti
                                </a>
                            <?php elseif (!empty($peserta['bukti_tim'])): ?>
                                <p class="mb-2">
                                    <strong>Bukti (Tim):</strong> 
                                    <?php echo $peserta['bukti_tim']; ?>
                                </p>
                                <a href="../uploads/bukti_pembayaran/<?php echo $peserta['bukti_tim']; ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> Lihat Bukti
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h6 class="card-title"><i class="fas fa-info-circle me-2"></i> Informasi Pendaftaran</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Kode Pendaftaran:</strong></td>
                        <td><code><?php echo $peserta['kode_pendaftaran']; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Daftar:</strong></td>
                        <td><?php echo date('d F Y H:i:s', strtotime($peserta['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Terakhir Update:</strong></td>
                        <td>
                            <?php echo !empty($peserta['updated_at']) ? 
                                date('d F Y H:i:s', strtotime($peserta['updated_at'])) : '-'; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php mysqli_close($conn); ?>
<?php
session_start();
require_once 'koneksi.php';

// Cek session
if (!isset($_SESSION['kode_pendaftaran']) || !isset($_SESSION['event_id'])) {
    header("Location: event.php");
    exit();
}

$kode_pendaftaran = $_SESSION['kode_pendaftaran'];
$event_id = $_SESSION['event_id'];

// Ambil data event
$event_query = "SELECT * FROM events WHERE id = $event_id";
$event_result = mysqli_query($conn, $event_query);
$event = mysqli_fetch_assoc($event_result);

// Ambil data pendaftaran berdasarkan tipe
if (strpos($kode_pendaftaran, 'IND-') === 0) {
    // Individu
    $data_query = "SELECT * FROM peserta WHERE kode_pendaftaran = '$kode_pendaftaran'";
    $tipe = 'individu';
} else {
    // Tim
    $data_query = "SELECT t.*, p.nama as ketua_nama, p.npm as ketua_npm 
                   FROM tim_event t 
                   LEFT JOIN peserta p ON t.id = p.tim_id AND p.status_anggota = 'ketua' 
                   WHERE t.kode_pendaftaran = '$kode_pendaftaran'";
    $tipe = 'tim';
}

$data_result = mysqli_query($conn, $data_query);
$data_pendaftaran = mysqli_fetch_assoc($data_result);

// Hapus session setelah digunakan
unset($_SESSION['kode_pendaftaran']);
unset($_SESSION['event_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil - <?php echo htmlspecialchars($event['judul']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #28a745;
            --secondary: #20c997;
        }
        
        body {
            background: linear-gradient(135deg, #f8fff9 0%, #e8f5e9 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(40, 167, 69, 0.15);
            padding: 50px;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .success-icon i {
            font-size: 4rem;
            color: white;
        }
        
        .code-badge {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border: 2px dashed var(--primary);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn-print {
            background: linear-gradient(90deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        .btn-whatsapp {
            background: linear-gradient(90deg, #25d366 0%, #128c7e 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3);
            color: white;
        }
        
        .event-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        @media (max-width: 768px) {
            .success-container {
                padding: 30px 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
        
        /* Confetti animation */
        .confetti {
            position: absolute;
            width: 15px;
            height: 15px;
            background: #f00;
            opacity: 0.7;
            animation: confetti-fall 5s linear infinite;
        }
        
        @keyframes confetti-fall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <!-- Confetti -->
            <div id="confettiContainer"></div>
            
            <!-- Success Icon -->
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <!-- Title -->
            <h1 class="text-center mb-3">🎉 Pendaftaran Berhasil!</h1>
            <p class="text-center text-muted mb-4">
                Terima kasih telah mendaftar pada event 
                <strong><?php echo htmlspecialchars($event['judul']); ?></strong>
            </p>
            
            <!-- Kode Pendaftaran -->
            <div class="code-badge">
                <h2 class="mb-2">Kode Pendaftaran</h2>
                <h1 class="text-primary mb-0"><?php echo $kode_pendaftaran; ?></h1>
                <small class="text-muted">Simpan kode ini untuk keperluan verifikasi</small>
            </div>
            
            <!-- Event Info -->
            <div class="event-info">
                <h4><i class="fas fa-calendar-alt me-2"></i> Informasi Event</h4>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Judul:</strong> <?php echo htmlspecialchars($event['judul']); ?></p>
                        <p><strong>Tanggal:</strong> <?php echo date('d F Y', strtotime($event['tanggal'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($event['lokasi']); ?></p>
                        <p><strong>Waktu:</strong> <?php echo date('H:i', strtotime($event['waktu'])); ?> WIB</p>
                    </div>
                </div>
            </div>
            
            <!-- Data Pendaftaran -->
            <div class="info-card">
                <h4><i class="fas fa-user-circle me-2"></i> Data Pendaftaran</h4>
                
                <?php if ($tipe == 'individu'): ?>
                    <table class="data-table">
                        <tr>
                            <th width="150">Nama</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['nama']); ?></td>
                        </tr>
                        <tr>
                            <th>NPM</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['npm']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['email']); ?></td>
                        </tr>
                        <tr>
                            <th>No. WhatsApp</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['no_wa']); ?></td>
                        </tr>
                        <tr>
                            <th>Jurusan</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['jurusan']); ?></td>
                        </tr>
                    </table>
                    
                <?php else: ?>
                    <table class="data-table">
                        <tr>
                            <th width="150">Nama Tim</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['nama_tim']); ?></td>
                        </tr>
                        <tr>
                            <th>Ketua Tim</th>
                            <td><?php echo htmlspecialchars($data_pendaftaran['ketua_nama']); ?> (<?php echo htmlspecialchars($data_pendaftaran['ketua_npm']); ?>)</td>
                        </tr>
                        <tr>
                            <th>Jumlah Anggota</th>
                            <td><?php echo $data_pendaftaran['jumlah_anggota']; ?> orang</td>
                        </tr>
                        <tr>
                            <th>Tanggal Daftar</th>
                            <td><?php echo date('d F Y H:i', strtotime($data_pendaftaran['created_at'])); ?></td>
                        </tr>
                    </table>
                    
                    <?php 
                    // Ambil data anggota tim
                    $tim_id = $data_pendaftaran['id'];
                    $anggota_query = "SELECT * FROM peserta WHERE tim_id = $tim_id ORDER BY status_anggota DESC";
                    $anggota_result = mysqli_query($conn, $anggota_query);
                    ?>
                    
                    <h5 class="mt-4 mb-3">Data Anggota Tim:</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>NPM</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php while ($anggota = mysqli_fetch_assoc($anggota_result)): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($anggota['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($anggota['npm']); ?></td>
                                    <td><?php echo htmlspecialchars($anggota['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $anggota['status_anggota'] == 'ketua' ? 'bg-primary' : 'bg-secondary'; ?>">
                                            <?php echo ucfirst($anggota['status_anggota']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Next Steps -->
            <div class="info-card">
                <h4><i class="fas fa-list-check me-2"></i> Langkah Selanjutnya</h4>
                <ol class="mb-0">
                    <li>Simpan kode pendaftaran Anda</li>
                    <li>Tunggu konfirmasi dari panitia via WhatsApp</li>
                    <li>Ikuti grup WhatsApp jika ada (info akan diinfokan)</li>
                    <li>Datang tepat waktu di hari pelaksanaan</li>
                    <li>Bawa bukti pendaftaran (screenshot halaman ini)</li>
                </ol>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-print" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Cetak Bukti
                </button>
                
                <button class="btn btn-whatsapp" id="shareWhatsApp">
                    <i class="fab fa-whatsapp me-2"></i> Share via WhatsApp
                </button>
                
                <a href="detail_event.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-calendar me-2"></i> Kembali ke Event
                </a>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-4">
                <p class="text-muted mb-0">
                    <small>
                        Untuk pertanyaan lebih lanjut, hubungi: 
                        <?php echo !empty($event['contact_person']) ? htmlspecialchars($event['contact_person']) : 'Panitia Event'; ?>
                        <?php echo !empty($event['contact_wa']) ? ' - ' . htmlspecialchars($event['contact_wa']) : ''; ?>
                    </small>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Confetti effect
        function createConfetti() {
            const container = document.getElementById('confettiContainer');
            const colors = ['#f00', '#0f0', '#00f', '#ff0', '#f0f', '#0ff'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                container.appendChild(confetti);
            }
        }
        
        // Share via WhatsApp
        document.getElementById('shareWhatsApp').addEventListener('click', function() {
            const eventName = "<?php echo rawurlencode($event['judul']); ?>";
            const kode = "<?php echo $kode_pendaftaran; ?>";
            const tanggal = "<?php echo date('d F Y', strtotime($event['tanggal'])); ?>";
            const url = window.location.href;
            
            const message = `Halo! Saya telah berhasil mendaftar event:

🎯 *${eventName}*
📅 ${tanggal}
🔢 *Kode Pendaftaran: ${kode}*

Silakan cek detail pendaftaran di:
${url}

Terima kasih!`;
            
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        });
        
        // Auto print option
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print')) {
            setTimeout(() => {
                window.print();
            }, 1000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
            
            // Auto copy to clipboard
            setTimeout(() => {
                const code = "<?php echo $kode_pendaftaran; ?>";
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(code).then(() => {
                        console.log('Kode pendaftaran disalin ke clipboard');
                    });
                }
            }, 2000);
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
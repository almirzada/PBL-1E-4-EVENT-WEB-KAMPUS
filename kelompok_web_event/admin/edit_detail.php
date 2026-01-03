<?php
// admin/edit_lomba_simple.php
session_start();

// KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// TENTUKAN JENIS LOMBA
$jenis_lomba = isset($_GET['jenis']) ? $_GET['jenis'] : 'Futsal';
$jenis_lomba = $conn->real_escape_string($jenis_lomba);

// AMBIL DATA
$sql = "SELECT * FROM lomba_details WHERE jenis_lomba = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $jenis_lomba);
$stmt->execute();
$result = $stmt->get_result();
$lomba = $result->fetch_assoc();
$stmt->close();

// JIKA BELUM ADA DATA, BUAT DEFAULT
if (!$lomba) {
    $lomba = [
        'judul_halaman' => 'Lomba ' . $jenis_lomba . ' Mahasiswa 2025',
        'deskripsi' => 'Deskripsi tentang lomba ' . $jenis_lomba . '...',
        'tanggal' => '2025-12-13',
        'waktu' => '08:00',
        'tempat' => 'Lapangan ' . $jenis_lomba . ' Polibatam',
        'kontak_nama' => 'Panitia ' . $jenis_lomba,
        'kontak_no' => '081234567890',
        'kontak_email' => $jenis_lomba . '@polibatam.ac.id',
        'aturan' => "1. Aturan pertama\n2. Aturan kedua\n3. Aturan ketiga",
        'gambar_lomba' => ''
    ];
}

// JIKA FORM DISUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $waktu = $_POST['waktu'] ?? '';
    $tempat = $_POST['tempat'] ?? '';
    $kontak_nama = $_POST['kontak_nama'] ?? '';
    $kontak_no = $_POST['kontak_no'] ?? '';
    $kontak_email = $_POST['kontak_email'] ?? '';
    $aturan = $_POST['aturan'] ?? '';
    
    // ========== HANDLE FILE UPLOAD ==========
    $gambar_lomba = $lomba['gambar_lomba'] ?? ''; // Default pakai yang lama
    
    // Cek jika ada file yang diupload
    if (isset($_FILES['gambar_lomba']) && $_FILES['gambar_lomba']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['gambar_lomba']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Buat nama file unik
            $file_ext = pathinfo($_FILES['gambar_lomba']['name'], PATHINFO_EXTENSION);
            $new_filename = strtolower($jenis_lomba) . '_' . time() . '.' . $file_ext;
            $upload_path = '../uploads/' . $new_filename;
            
            // Buat folder uploads jika belum ada
            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['gambar_lomba']['tmp_name'], $upload_path)) {
                $gambar_lomba = $new_filename;
                
                // Hapus gambar lama jika ada
                if (!empty($lomba['gambar_lomba']) && $lomba['gambar_lomba'] != $new_filename) {
                    $old_file = '../uploads/' . $lomba['gambar_lomba'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
            }
        }
    }
    
    // ========== GENERATE HTML OTOMATIS DARI DATA FORM (TANPA GAMBAR DAN TANPA JUDUL) ==========
    $konten_html = generateHTML($deskripsi, $tanggal, $waktu, $tempat, 
                               $kontak_nama, $kontak_no, $kontak_email, $aturan, $jenis_lomba);
    
    // ========== CEK APAKAH DATA SUDAH ADA ATAU BELUM ==========
    $check_sql = "SELECT id FROM lomba_details WHERE jenis_lomba = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $jenis_lomba);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // UPDATE DATA YANG SUDAH ADA
        $update_sql = "UPDATE lomba_details SET 
                      judul_halaman = ?, deskripsi = ?, tanggal = ?, waktu = ?, tempat = ?,
                      kontak_nama = ?, kontak_no = ?, kontak_email = ?, aturan = ?,
                      gambar_lomba = ?, konten_html = ?, updated_at = NOW()
                      WHERE jenis_lomba = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssssssssss", $judul, $deskripsi, $tanggal, $waktu, $tempat,
                         $kontak_nama, $kontak_no, $kontak_email, $aturan, $gambar_lomba, $konten_html, $jenis_lomba);
    } else {
        // INSERT DATA BARU
        $insert_sql = "INSERT INTO lomba_details 
                      (jenis_lomba, judul_halaman, deskripsi, tanggal, waktu, tempat,
                       kontak_nama, kontak_no, kontak_email, aturan, gambar_lomba, konten_html)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssssssssssss", $jenis_lomba, $judul, $deskripsi, $tanggal, $waktu, $tempat,
                         $kontak_nama, $kontak_no, $kontak_email, $aturan, $gambar_lomba, $konten_html);
    }
    
    if ($stmt->execute()) {
        $success = "✅ Data lomba berhasil disimpan!";
        // AMBIL ULANG DATA
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("s", $jenis_lomba);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $lomba = $result2->fetch_assoc();
        $stmt2->close();
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
    
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
}

$conn->close();

// ========== FUNGSI GENERATE HTML OTOMATIS TANPA GAMBAR DAN TANPA JUDUL ==========
function generateHTML($deskripsi, $tanggal, $waktu, $tempat, 
                     $kontak_nama, $kontak_no, $kontak_email, $aturan, $jenis) {
    
    $icon = [
        'Futsal' => 'fa-futbol',
        'Basket' => 'fa-basketball-ball',
        'Badminton' => 'fa-table-tennis'
    ][$jenis] ?? 'fa-trophy';
    
    // FORMAT TANGGAL
    $tanggal_formatted = date('d F Y', strtotime($tanggal));
    
    // FORMAT ATURAN (UBAH BARIS MENJADI <li>)
    $aturan_list = '';
    $aturan_lines = explode("\n", $aturan);
    foreach ($aturan_lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            // Jika line dimulai dengan angka (1., 2., dll), hapus angka
            $clean_line = preg_replace('/^\d+\.\s*/', '', $line);
            $aturan_list .= "<li><strong>" . htmlspecialchars($clean_line) . "</strong></li>\n";
        }
    }
    
    // ========== HTML TANPA TAG <h2> DAN TANPA TAG <img> ==========
    // Judul akan ditampilkan secara terpisah di detail_badminton.php
    // Gambar juga akan ditampilkan secara terpisah
    
    return <<<HTML
    <!-- HANYA KONTEN TANPA JUDUL DAN TANPA GAMBAR -->
    <div class="detail-info">
        <h3><i class="fas $icon"></i> $jenis</h3>
        <p>$deskripsi</p>
    </div>

    <section id="rules" class="rules-section">
        <h3><i class="fas fa-clipboard-list"></i> Aturan Permainan</h3>
        <div class="rules-content">
            <ol>
                $aturan_list
            </ol>
        </div>
    </section>

    <div class="schedule-contact">
        <div class="schedule-box">
            <h3><i class="fas fa-calendar-alt"></i> Jadwal Pelaksanaan</h3>
            <p><i class="fas fa-calendar-day"></i> <strong>Tanggal:</strong> $tanggal_formatted</p>
            <p><i class="fas fa-clock"></i> <strong>Waktu:</strong> $waktu WIB</p>
            <p><i class="fas fa-map-marker-alt"></i> <strong>Tempat:</strong> $tempat</p>
        </div>

        <div class="contact-box">
            <h3><i class="fas fa-phone-alt"></i> Kontak Panitia</h3>
            <p><i class="fas fa-user"></i> <strong>$kontak_nama</strong></p>
            <p><i class="fas fa-phone"></i> $kontak_no</p>
            <p><i class="fas fa-envelope"></i> $kontak_email</p>
        </div>
    </div>

    <div class="button-group">
        <button onclick="window.location.href='daftar.php'"><i class="fas fa-user-plus"></i> Daftar Sekarang</button>
        <button onclick="window.location.href='index.php'"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Lomba</button>
    </div>
HTML;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lomba - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .form-section h4 {
            color: #0056b3;
            border-bottom: 3px solid #0056b3;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .badge-futsal { background: #1e88e5; }
        .badge-basket { background: #e53935; }
        .badge-badminton { background: #43a047; }
        .preview-card {
            background: #f8f9fa;
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .gambar-preview {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
        }
        .btn-hapus-gambar {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-3">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Lomba</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="?jenis=Futsal" class="list-group-item list-group-item-action <?= $jenis_lomba == 'Futsal' ? 'active' : ''; ?>">
                                <i class="fas fa-futbol me-2"></i> Futsal
                            </a>
                            <a href="?jenis=Basket" class="list-group-item list-group-item-action <?= $jenis_lomba == 'Basket' ? 'active' : ''; ?>">
                                <i class="fas fa-basketball-ball me-2"></i> Basket
                            </a>
                            <a href="?jenis=Badminton" class="list-group-item list-group-item-action <?= $jenis_lomba == 'Badminton' ? 'active' : ''; ?>">
                                <i class="fas fa-table-tennis me-2"></i> Badminton
                            </a>
                            <div class="list-group-item">
                                <a href="../index.php" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fas fa-home me-1"></i> Homepage
                                </a>
                            </div>
                            <div class="list-group-item">
                                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MAIN FORM -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-edit"></i> Edit Lomba 
                            <span class="badge <?= 'badge-' . strtolower($jenis_lomba) ?>">
                                <?= $jenis_lomba ?>
                            </span>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= $success ?>
                            <div class="mt-2">
                                <a href="../detail_<?= strtolower($jenis_lomba) ?>.php" target="_blank" class="btn btn-sm btn-success">
                                    <i class="fas fa-eye"></i> Lihat Halaman
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- ========== FORM DENGAN UPLOAD GAMBAR ========== -->
                        <form method="POST" enctype="multipart/form-data">
                            <!-- INFORMASI UMUM -->
                            <div class="form-section">
                                <h4><i class="fas fa-info-circle"></i> Informasi Umum</h4>
                                
                                <!-- INPUT UPLOAD GAMBAR -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Gambar Lomba</label>
                                    
                                    <!-- Tampilkan gambar saat ini -->
                                    <?php if (!empty($lomba['gambar_lomba'])): ?>
                                    <div class="mb-3">
                                        <p class="fw-bold">Gambar Saat Ini:</p>
                                        <img src="../uploads/<?= htmlspecialchars($lomba['gambar_lomba']) ?>" 
                                             alt="Gambar <?= $jenis_lomba ?>" 
                                             class="gambar-preview">
                                        <p class="text-muted small">
                                            Nama file: <?= htmlspecialchars($lomba['gambar_lomba']) ?>
                                        </p>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Belum ada gambar. Silakan upload gambar untuk lomba ini.
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Input file untuk upload -->
                                    <div class="mb-3">
                                        <input type="file" name="gambar_lomba" class="form-control" accept="image/*">
                                        <small class="text-muted">
                                            Format: JPG, PNG, GIF, WebP. Maksimal 2MB. Biarkan kosong jika tidak ingin mengganti gambar.
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- JUDUL DAN DESKRIPSI -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Judul Lomba</label>
                                    <input type="text" name="judul" class="form-control" 
                                           value="<?= htmlspecialchars($lomba['judul_halaman'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           placeholder="Contoh: Lomba <?= $jenis_lomba ?> Mahasiswa 2025" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Deskripsi Lomba</label>
                                    <textarea name="deskripsi" class="form-control" rows="4" 
                                              placeholder="Deskripsi tentang lomba <?= $jenis_lomba ?>..."><?= htmlspecialchars($lomba['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <small class="text-muted">Jelaskan tentang lomba ini secara singkat</small>
                                </div>
                            </div>
                            
                            <!-- JADWAL & TEMPAT -->
                            <div class="form-section">
                                <h4><i class="fas fa-calendar-alt"></i> Jadwal & Tempat</h4>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Tanggal Pelaksanaan</label>
                                        <input type="date" name="tanggal" class="form-control" 
                                               value="<?= $lomba['tanggal'] ?? '2025-12-13' ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Waktu Mulai</label>
                                        <input type="time" name="waktu" class="form-control" 
                                               value="<?= $lomba['waktu'] ?? '08:00' ?>" required>
                                        <small class="text-muted">Format: HH:MM</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Tempat</label>
                                        <input type="text" name="tempat" class="form-control" 
                                               value="<?= htmlspecialchars($lomba['tempat'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                               placeholder="Contoh: Lapangan <?= $jenis_lomba ?> Polibatam" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- KONTAK PANITIA -->
                            <div class="form-section">
                                <h4><i class="fas fa-phone-alt"></i> Kontak Panitia</h4>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Nama Panitia</label>
                                        <input type="text" name="kontak_nama" class="form-control" 
                                               value="<?= htmlspecialchars($lomba['kontak_nama'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                               placeholder="Nama koordinator">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Nomor Telepon/WA</label>
                                        <input type="text" name="kontak_no" class="form-control" 
                                               value="<?= htmlspecialchars($lomba['kontak_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                               placeholder="081234567890">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" name="kontak_email" class="form-control" 
                                               value="<?= htmlspecialchars($lomba['kontak_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                               placeholder="email@polibatam.ac.id">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ATURAN PERMAINAN -->
                            <div class="form-section">
                                <h4><i class="fas fa-clipboard-list"></i> Aturan Permainan</h4>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Aturan (satu per baris)</label>
                                    <textarea name="aturan" class="form-control" rows="6" 
                                              placeholder="Masukkan aturan, satu per baris. Contoh:&#10;1. Setiap tim maksimal 10 pemain&#10;2. Durasi pertandingan 2x10 menit&#10;3. Wajib membawa perlengkapan lengkap"><?= htmlspecialchars($lomba['aturan'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                    <small class="text-muted">Gunakan enter untuk membuat poin baru. Boleh pakai angka (1., 2., dst) atau tidak</small>
                                </div>
                            </div>
                            
                            <!-- TOMBOL SIMPAN -->
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                                <a href="../detail_<?= strtolower($jenis_lomba) ?>.php" target="_blank" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-eye"></i> Lihat Halaman
                                </a>
                                <a href="?jenis=<?= $jenis_lomba ?>" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-redo"></i> Refresh
                                </a>
                            </div>
                        </form>
                        
                        <!-- PREVIEW -->
                        <div class="preview-card mt-4">
                            <h5><i class="fas fa-search"></i> Preview Data</h5>
                            <p class="text-muted">Data yang sudah diisi akan tampil seperti ini di halaman detail:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Judul:</strong> <?= htmlspecialchars($lomba['judul_halaman'] ?? 'Belum diisi') ?></p>
                                    <p><strong>Tanggal:</strong> <?= !empty($lomba['tanggal']) ? date('d F Y', strtotime($lomba['tanggal'])) : 'Belum diisi' ?></p>
                                    <p><strong>Waktu:</strong> <?= $lomba['waktu'] ?? 'Belum diisi' ?> WIB</p>
                                    <p><strong>Tempat:</strong> <?= htmlspecialchars($lomba['tempat'] ?? 'Belum diisi') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Kontak:</strong> <?= htmlspecialchars($lomba['kontak_nama'] ?? 'Belum diisi') ?></p>
                                    <p><strong>Telepon:</strong> <?= htmlspecialchars($lomba['kontak_no'] ?? 'Belum diisi') ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($lomba['kontak_email'] ?? 'Belum diisi') ?></p>
                                    <p><strong>Gambar:</strong> <?= !empty($lomba['gambar_lomba']) ? htmlspecialchars($lomba['gambar_lomba']) : 'Belum diupload' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar sebelum upload
        document.querySelector('input[name="gambar_lomba"]').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Cek apakah sudah ada preview
                    let preview = document.querySelector('.gambar-preview');
                    if (!preview) {
                        // Buat elemen preview baru
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'mb-3';
                        previewDiv.innerHTML = '<p class="fw-bold">Preview Gambar Baru:</p><img src="' + e.target.result + '" class="gambar-preview" alt="Preview">';
                        document.querySelector('input[name="gambar_lomba"]').parentNode.appendChild(previewDiv);
                    } else {
                        // Update preview yang sudah ada
                        preview.src = e.target.result;
                    }
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>
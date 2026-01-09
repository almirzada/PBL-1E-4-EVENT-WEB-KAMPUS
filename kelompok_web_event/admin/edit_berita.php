<?php
session_start();
require_once '../koneksi.php';

// PROTEKSI ADMIN
if (!isset($_SESSION['admin_event_id'])) {
    header("Location: login.php");
    exit();
}

// ================================================
// CEK PARAMETER ID
// ================================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: daftar_berita.php");
    exit();
}

$id = intval($_GET['id']);

// ================================================
// AMBIL DATA BERITA YANG AKAN DIEDIT
// ================================================
$query = "SELECT * FROM berita WHERE id = $id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: daftar_berita.php");
    exit();
}

$berita = mysqli_fetch_assoc($result);

// ================================================
// PROSES UPDATE BERITA
// ================================================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $konten = mysqli_real_escape_string($conn, $_POST['konten'] ?? '');
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'informasi');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'draft');
    $excerpt = mysqli_real_escape_string($conn, $_POST['excerpt'] ?? '');
    $hapus_gambar = isset($_POST['hapus_gambar']) ? true : false;
    
    // Validasi
    if (empty($judul)) {
        $error = 'Judul berita harus diisi!';
    } elseif (empty($konten)) {
        $error = 'Konten berita harus diisi!';
    } else {
        // Handle gambar baru
        $gambar = $berita['gambar'];
        
        // Jika hapus gambar
        if ($hapus_gambar && !empty($gambar)) {
            $file_path = '../' . $gambar;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $gambar = '';
        }
        
        // Jika upload gambar baru
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            // Hapus gambar lama jika ada
            if (!empty($berita['gambar'])) {
                $old_file = '../' . $berita['gambar'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_name = 'berita_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_path = '../uploads/berita/' . $new_name;
                
                // Buat folder jika belum ada
                if (!is_dir('../uploads/berita/')) {
                    mkdir('../uploads/berita/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $gambar = 'uploads/berita/' . $new_name;
                } else {
                    $error = 'Gagal mengupload gambar baru!';
                }
            } else {
                $error = 'Format file tidak didukung! Hanya JPG, PNG, GIF, WebP.';
            }
        }
        
        // Jika tidak ada error, update database
        if (empty($error)) {
            // Generate excerpt otomatis jika kosong
            if (empty($excerpt)) {
                $excerpt = substr(strip_tags($konten), 0, 150) . '...';
            }
            
            $query = "UPDATE berita SET 
                     judul = '$judul',
                     konten = '$konten',
                     gambar = '$gambar',
                     kategori_berita = '$kategori',
                     status = '$status',
                     excerpt = '$excerpt',
                     updated_at = NOW()
                     WHERE id = $id";
            
            if (mysqli_query($conn, $query)) {
                $success = 'Berita berhasil diperbarui!';
                
                // Update data berita yang ditampilkan
                $berita['judul'] = $_POST['judul'];
                $berita['konten'] = $_POST['konten'];
                $berita['kategori_berita'] = $kategori;
                $berita['status'] = $status;
                $berita['excerpt'] = $excerpt;
                $berita['gambar'] = $gambar;
                
                // Redirect setelah 2 detik
                header("Refresh: 2; url=daftar_berita.php");
            } else {
                $error = 'Gagal memperbarui berita: ' . mysqli_error($conn);
            }
        }
    }
}

$kategori_berita = [
    'informasi' => 'Informasi',
    'pengumuman' => 'Pengumuman', 
    'beasiswa' => 'Beasiswa',
    'akademik' => 'Akademik',
    'kemahasiswaan' => 'Kemahasiswaan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Berita - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-wrapper {
            min-height: 100vh;
        }
        
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
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .preview-img {
            max-width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: var(--primary);
            background: #f8f9ff;
        }
        
        .file-upload i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .current-img {
            position: relative;
            display: inline-block;
        }
        
        .delete-img {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
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
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header text-center">
                <h4><i class="fas fa-newspaper"></i> <span class="menu-text">Berita Kampus</span></h4>
                <small class="menu-text">Admin Panel</small>
            </div>
            
            <div class="sidebar-menu">
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> <span class="menu-text">Dashboard</span>
                    </a>
                    <a href="daftar_berita.php" class="nav-link">
                        <i class="fas fa-newspaper"></i> <span class="menu-text">Daftar Berita</span>
                    </a>
                    <a href="tambah_berita.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i> <span class="menu-text">Tambah Berita</span>
                    </a>
                    <a href="edit_berita.php?id=<?php echo $id; ?>" class="nav-link active">
                        <i class="fas fa-edit"></i> <span class="menu-text">Edit Berita</span>
                    </a>
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
                    <h3 class="mb-1"><i class="fas fa-edit text-primary me-2"></i>Edit Berita</h3>
                    <p class="text-muted mb-0">ID: <?php echo $id; ?> | Dibuat: <?php echo date('d/m/Y H:i', strtotime($berita['created_at'])); ?></p>
                </div>
                <div>
                    <a href="../detail_berita.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-info me-2">
                        <i class="fas fa-eye me-2"></i>Lihat
                    </a>
                    <a href="daftar_berita.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>
            
            <!-- NOTIFIKASI -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- FORM -->
            <div class="form-card">
                <form method="POST" enctype="multipart/form-data" id="formBerita">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- JUDUL -->
                            <div class="mb-4">
                                <label for="judul" class="form-label fw-bold">Judul Berita <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="judul" name="judul" required
                                       value="<?php echo htmlspecialchars($berita['judul']); ?>">
                            </div>
                            
                            <!-- KONTEN -->
                            <div class="mb-4">
                                <label for="konten" class="form-label fw-bold">Konten Berita <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="konten" name="konten" rows="15" required><?php echo htmlspecialchars($berita['konten']); ?></textarea>
                            </div>
                            
                            <!-- EXCERPT -->
                            <div class="mb-4">
                                <label for="excerpt" class="form-label fw-bold">Ringkasan (Excerpt)</label>
                                <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($berita['excerpt']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- GAMBAR THUMBNAIL -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-image me-2"></i>Gambar Thumbnail</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($berita['gambar'])): ?>
                                    <div class="mb-3 current-img">
                                        <img src="../<?php echo htmlspecialchars($berita['gambar']); ?>" 
                                             class="preview-img" alt="Gambar saat ini">
                                        <button type="button" class="delete-img" id="deleteCurrentImg">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <input type="hidden" name="hapus_gambar" id="hapusGambar" value="0">
                                    </div>
                                    <p class="text-muted small">Klik tombol X untuk hapus gambar</p>
                                    <?php endif; ?>
                                    
                                    <div class="file-upload <?php echo !empty($berita['gambar']) ? 'd-none' : ''; ?>" id="uploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p class="mb-2">Klik atau drag & drop gambar</p>
                                        <small class="text-muted">JPG, PNG, GIF, WebP (Max 2MB)</small>
                                        <input type="file" name="gambar" id="gambar" 
                                               accept="image/*" class="d-none">
                                    </div>
                                    <img id="preview" class="preview-img w-100 d-none" alt="Preview baru">
                                </div>
                            </div>
                            
                            <!-- KATEGORI -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Kategori</h6>
                                </div>
                                <div class="card-body">
                                    <select name="kategori" class="form-select" required>
                                        <?php foreach ($kategori_berita as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" 
                                            <?php echo $berita['kategori_berita'] == $key ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- STATUS -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Status Publikasi</h6>
                                </div>
                                <div class="card-body">
                                    <select name="status" class="form-select" required>
                                        <option value="draft" <?php echo $berita['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="publik" <?php echo $berita['status'] == 'publik' ? 'selected' : ''; ?>>Publik</option>
                                    </select>
                                    <div class="form-text mt-2">
                                        <strong>Views:</strong> <?php echo $berita['views']; ?> kali
                                    </div>
                                </div>
                            </div>
                            
                            <!-- INFO -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled small">
                                        <li class="mb-2">
                                            <i class="fas fa-calendar-plus me-2"></i>
                                            <strong>Dibuat:</strong> <?php echo date('d/m/Y H:i', strtotime($berita['created_at'])); ?>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-edit me-2"></i>
                                            <strong>Terakhir edit:</strong> 
                                            <?php echo !empty($berita['updated_at']) ? date('d/m/Y H:i', strtotime($berita['updated_at'])) : 'Belum pernah'; ?>
                                        </li>
                                        <li>
                                            <i class="fas fa-eye me-2"></i>
                                            <strong>Views:</strong> <?php echo $berita['views']; ?> kali
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- TOMBOL AKSI -->
                            <div class="card">
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-save me-2"></i>Perbarui Berita
                                    </button>
                                    <a href="daftar_berita.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times me-2"></i>Batal
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- FOOTER -->
            <footer class="mt-4 text-center text-muted">
                <hr>
                <small>
                    &copy; <?php echo date('Y'); ?> Sistem Berita Kampus - Edit Berita #<?php echo $id; ?>
                </small>
            </footer>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-id-ID.js"></script>
    
    <script>
        // Summernote Editor
        $(document).ready(function() {
            $('#konten').summernote({
                height: 300,
                lang: 'id-ID',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });
        
        // Hapus gambar saat ini
        const deleteCurrentImg = document.getElementById('deleteCurrentImg');
        const hapusGambar = document.getElementById('hapusGambar');
        const uploadArea = document.getElementById('uploadArea');
        const currentImgContainer = document.querySelector('.current-img');
        
        if (deleteCurrentImg) {
            deleteCurrentImg.addEventListener('click', function() {
                if (confirm('Hapus gambar saat ini?')) {
                    hapusGambar.value = '1';
                    currentImgContainer.style.display = 'none';
                    uploadArea.classList.remove('d-none');
                }
            });
        }
        
        // Preview gambar baru
        const fileInput = document.getElementById('gambar');
        const preview = document.getElementById('preview');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#4361ee';
            uploadArea.style.background = '#f0f3ff';
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#ddd';
            uploadArea.style.background = '';
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#ddd';
            uploadArea.style.background = '';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewImage(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                previewImage(this.files[0]);
            }
        });
        
        function previewImage(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                uploadArea.classList.add('d-none');
            }
            reader.readAsDataURL(file);
        }
        
        // Hapus preview baru
        preview.addEventListener('click', () => {
            fileInput.value = '';
            preview.classList.add('d-none');
            uploadArea.classList.remove('d-none');
        });
        
        // Validasi form
        document.getElementById('formBerita').addEventListener('submit', function(e) {
            const judul = document.getElementById('judul').value.trim();
            const konten = $('#konten').summernote('code').trim();
            
            if (!judul) {
                e.preventDefault();
                alert('Judul berita harus diisi!');
                document.getElementById('judul').focus();
            } else if (!konten || konten === '<p><br></p>') {
                e.preventDefault();
                alert('Konten berita harus diisi!');
                $('#konten').summernote('focus');
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
<?php
session_start();
require_once '../koneksi.php';

// PROTEKSI ADMIN
if (!isset($_SESSION['admin_event_id'])) {
    header("Location: login.php");
    exit();
}

// ================================================
// PROSES TAMBAH BERITA
// ================================================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
    $konten = mysqli_real_escape_string($conn, $_POST['konten'] ?? '');
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? 'informasi');
    $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'draft');
    $excerpt = mysqli_real_escape_string($conn, $_POST['excerpt'] ?? '');
    
    // Validasi
    if (empty($judul)) {
        $error = 'Judul berita harus diisi!';
    } elseif (empty($konten)) {
        $error = 'Konten berita harus diisi!';
    } else {
        // Upload gambar thumbnail
        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
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
                    $error = 'Gagal mengupload gambar!';
                }
            } else {
                $error = 'Format file tidak didukung! Hanya JPG, PNG, GIF, WebP.';
            }
        }
        
        // Jika tidak ada error, simpan ke database
        if (empty($error)) {
            // Generate excerpt otomatis jika kosong
            if (empty($excerpt)) {
                $excerpt = substr(strip_tags($konten), 0, 150) . '...';
            }
            
            $query = "INSERT INTO berita (judul, konten, gambar, kategori_berita, status, excerpt, views, created_at) 
                     VALUES ('$judul', '$konten', '$gambar', '$kategori', '$status', '$excerpt', 0, NOW())";
            
            if (mysqli_query($conn, $query)) {
                $success = 'Berita berhasil ditambahkan!';
                
                // Redirect setelah 2 detik
                header("Refresh: 2; url=daftar_berita.php");
            } else {
                $error = 'Gagal menyimpan berita: ' . mysqli_error($conn);
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
    <title>Tambah Berita - Admin Panel</title>
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
            max-width: 300px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
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
                    <h3 class="mb-1"><i class="fas fa-plus-circle text-primary me-2"></i>Tambah Berita Baru</h3>
                    <p class="text-muted mb-0">Isi form di bawah untuk menambahkan berita baru</p>
                </div>
                <a href="daftar_berita.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                </a>
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
                                       placeholder="Masukkan judul berita yang menarik"
                                       value="<?php echo $_POST['judul'] ?? ''; ?>">
                                <div class="form-text">Judul akan muncul di halaman utama berita</div>
                            </div>
                            
                            <!-- KONTEN -->
                            <div class="mb-4">
                                <label for="konten" class="form-label fw-bold">Konten Berita <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="konten" name="konten" rows="15" required
                                          placeholder="Tulis konten berita di sini..."><?php echo $_POST['konten'] ?? ''; ?></textarea>
                                <div class="form-text">Gunakan editor untuk format teks, gambar, dan link</div>
                            </div>
                            
                            <!-- EXCERPT -->
                            <div class="mb-4">
                                <label for="excerpt" class="form-label fw-bold">Ringkasan (Excerpt)</label>
                                <textarea class="form-control" id="excerpt" name="excerpt" rows="3"
                                          placeholder="Ringkasan singkat berita (otomatis terisi jika kosong)"><?php echo $_POST['excerpt'] ?? ''; ?></textarea>
                                <div class="form-text">Teks pendek yang muncul di halaman daftar berita</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- GAMBAR THUMBNAIL -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-image me-2"></i>Gambar Thumbnail</h6>
                                </div>
                                <div class="card-body">
                                    <div class="file-upload" id="uploadArea">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p class="mb-2">Klik atau drag & drop gambar</p>
                                        <small class="text-muted">JPG, PNG, GIF, WebP (Max 2MB)</small>
                                        <input type="file" name="gambar" id="gambar" 
                                               accept="image/*" class="d-none">
                                    </div>
                                    <img id="preview" class="preview-img w-100" alt="Preview">
                                    <div class="form-text mt-2">Gambar akan muncul sebagai thumbnail berita</div>
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
                                            <?php echo ($_POST['kategori'] ?? 'informasi') == $key ? 'selected' : ''; ?>>
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
                                        <option value="draft" <?php echo ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft (Simpan sementara)</option>
                                        <option value="publik" <?php echo ($_POST['status'] ?? '') == 'publik' ? 'selected' : ''; ?>>Publik (Tampilkan di website)</option>
                                    </select>
                                    <div class="form-text mt-2">
                                        <strong>Draft:</strong> Hanya admin yang bisa melihat<br>
                                        <strong>Publik:</strong> Semua pengunjung bisa melihat
                                    </div>
                                </div>
                            </div>
                            
                            <!-- TOMBOL AKSI -->
                            <div class="card">
                                <div class="card-body">
                                    <button type="submit" name="simpan" class="btn btn-primary w-100 mb-3">
                                        <i class="fas fa-save me-2"></i>Simpan Berita
                                    </button>
                                    <button type="submit" name="simpan_publik" class="btn btn-success w-100 mb-3">
                                        <i class="fas fa-check-circle me-2"></i>Simpan & Publikasikan
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
                    &copy; <?php echo date('Y'); ?> Sistem Berita Kampus - Tambah Berita
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
                ],
                placeholder: 'Tulis konten berita di sini...'
            });
        });
        
        // Preview gambar
        const uploadArea = document.getElementById('uploadArea');
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
                preview.style.display = 'block';
                uploadArea.style.display = 'none';
            }
            reader.readAsDataURL(file);
        }
        
        // Hapus preview
        preview.addEventListener('click', () => {
            fileInput.value = '';
            preview.style.display = 'none';
            uploadArea.style.display = 'block';
        });
        
        // Tombol simpan & publikasikan
        document.querySelector('button[name="simpan_publik"]').addEventListener('click', function() {
            document.querySelector('select[name="status"]').value = 'publik';
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
        
        // Auto generate excerpt dari konten
        document.getElementById('judul').addEventListener('blur', function() {
            const excerptField = document.getElementById('excerpt');
            if (!excerptField.value.trim()) {
                const konten = $('#konten').summernote('code').trim();
                const plainText = konten.replace(/<[^>]*>/g, '');
                if (plainText.length > 150) {
                    excerptField.value = plainText.substring(0, 150) + '...';
                } else {
                    excerptField.value = plainText;
                }
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
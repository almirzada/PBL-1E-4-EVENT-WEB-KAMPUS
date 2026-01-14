<?php
require_once 'koneksi.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_results = [
    'berita' => [],
    'events' => []
];

// Mapping kategori untuk pencarian
$kategori_event = [
    'Akademik & Riset' => ['akademik', 'riset', 'penelitian', 'akademik riset', 'penelitian'],
    'Bootcamp' => ['bootcamp', 'pelatihan', 'training', 'kursus', 'workshop'],
    'Kompetisi' => ['kompetisi', 'lomba', 'contest', 'kompetitif', 'perlombaan'],
    'Lomba Olahraga' => ['olahraga', 'sport', 'pertandingan', 'games', 'atletik'],
    'Pameran & Expo' => ['pameran', 'expo', 'exhibition', 'pamer', 'display'],
    'Seminar & Workshop' => ['seminar', 'workshop', 'pelatihan', 'lokakarya', 'training'],
    'Seni & Budaya' => ['seni', 'budaya', 'art', 'culture', 'kesenian', 'kebudayaan'],
    'Sosial dan Kemanusiaan' => ['sosial', 'kemanusiaan', 'donasi', 'bakti sosial', 'charity', 'humanitarian'],
    'Webinar' => ['webinar', 'online', 'daring', 'virtual', 'web seminar']
];

$kategori_berita = [
    'Informasi' => ['informasi', 'info', 'pemberitahuan', 'update'],
    'Pengumuman' => ['pengumuman', 'announcement', 'pemberitahuan', 'pengumuman resmi'],
    'Beasiswa' => ['beasiswa', 'scholarship', 'bantuan pendidikan', 'dana pendidikan', 'sponsorship'],
    'Akademik' => ['akademik', 'pendidikan', 'kurikulum', 'kuliah', 'perkuliahan'],
    'Kemahasiswaan' => ['kemahasiswaan', 'mahasiswa', 'organisasi', 'ukm', 'hima']
];

if (!empty($keyword)) {
    $escaped_keyword = mysqli_real_escape_string($conn, $keyword);
    $keyword_lower = strtolower($keyword);
    
    // Cari kategori event yang cocok dengan keyword
    $kategori_event_dicari = [];
    foreach ($kategori_event as $kategori => $keywords) {
        // Cek apakah keyword mengandung nama kategori
        if (stripos($kategori, $keyword) !== false) {
            $kategori_event_dicari[] = $kategori;
        } else {
            // Cek sinonim
            foreach ($keywords as $k) {
                if (stripos($keyword_lower, $k) !== false) {
                    $kategori_event_dicari[] = $kategori;
                    break;
                }
            }
        }
    }
    
    // Cari kategori berita yang cocok dengan keyword
    $kategori_berita_dicari = [];
    foreach ($kategori_berita as $kategori => $keywords) {
        if (stripos($kategori, $keyword) !== false) {
            $kategori_berita_dicari[] = $kategori;
        } else {
            foreach ($keywords as $k) {
                if (stripos($keyword_lower, $k) !== false) {
                    $kategori_berita_dicari[] = $kategori;
                    break;
                }
            }
        }
    }
    
    // Search dalam tabel berita (termasuk kategori)
    $query_berita = "SELECT * FROM berita 
                     WHERE (judul LIKE '%$escaped_keyword%' 
                     OR konten LIKE '%$escaped_keyword%' 
                     OR excerpt LIKE '%$escaped_keyword%'";
    
    // Tambahkan pencarian berdasarkan kategori berita
    if (!empty($kategori_berita_dicari)) {
        $kategori_conditions = [];
        foreach ($kategori_berita_dicari as $kat) {
            $kat_escaped = mysqli_real_escape_string($conn, $kat);
            $kategori_conditions[] = "kategori_berita LIKE '%$kat_escaped%'";
        }
        $query_berita .= " OR " . implode(" OR ", $kategori_conditions);
    }
    
    $query_berita .= ") AND status = 'publik' ORDER BY created_at DESC";
    
    $result_berita = mysqli_query($conn, $query_berita);
    if ($result_berita) {
        while ($row = mysqli_fetch_assoc($result_berita)) {
            $search_results['berita'][] = $row;
        }
    }
    
    // Search dalam tabel events (termasuk kategori)
    $query_events = "SELECT e.*, k.nama as kategori_nama, k.warna, k.ikon 
                     FROM events e 
                     LEFT JOIN kategori k ON e.kategori_id = k.id 
                     WHERE (e.judul LIKE '%$escaped_keyword%' 
                     OR e.deskripsi LIKE '%$escaped_keyword%' 
                     OR e.deskripsi_singkat LIKE '%$escaped_keyword%'
                     OR e.lokasi LIKE '%$escaped_keyword%'";
    
    // Tambahkan pencarian berdasarkan kategori event
    if (!empty($kategori_event_dicari)) {
        $kategori_conditions = [];
        foreach ($kategori_event_dicari as $kat) {
            $kat_escaped = mysqli_real_escape_string($conn, $kat);
            $kategori_conditions[] = "k.nama LIKE '%$kat_escaped%'";
        }
        $query_events .= " OR " . implode(" OR ", $kategori_conditions);
    }
    
    $query_events .= ") AND e.status = 'publik' ORDER BY e.created_at DESC";
    
    $result_events = mysqli_query($conn, $query_events);
    if ($result_events) {
        while ($row = mysqli_fetch_assoc($result_events)) {
            $search_results['events'][] = $row;
        }
    }
    
    // Hitung total hasil
    $total_results = count($search_results['berita']) + count($search_results['events']);
    
    // Simpan kategori yang ditemukan untuk ditampilkan
    $kategori_ditemukan = array_merge($kategori_event_dicari, $kategori_berita_dicari);
}

// Ambil beberapa event untuk suggestion jika hasil kosong
$suggestion_query = "SELECT * FROM events WHERE status = 'publik' ORDER BY created_at DESC LIMIT 3";
$suggestion_result = mysqli_query($conn, $suggestion_query);
$suggestion_events = [];
if ($suggestion_result) {
    while ($row = mysqli_fetch_assoc($suggestion_result)) {
        $suggestion_events[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian - Portal Informasi Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* SEMUA CSS SAMA PERSIS DENGAN INDEX.PHP */
        :root {
            --primary-color: #0056b3;
            --primary-dark: #003d82;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
            --accent-dark: #e0a800;
            --text-color: #333;
            --light-color: #fff;
            --gray-light: #f5f7fa;
            --gray-medium: #6c757d;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background: linear-gradient(to bottom,
                    #f0f1f3ff 80%,
                    #ffffff 100%);
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-nav .nav-link {
            position: relative;
            padding-bottom: 6px;
        }

        .navbar-nav .nav-link::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 0;
            height: 2px;
            background-color: #ffffffff;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after,
        .navbar-nav .nav-link.active::after {
            width: 100%;
        }

        .navbar-brand img {
            height: 50px;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 0 20px;
        }

         /* HEADER EVENT */
        .event-header {
            background: linear-gradient(rgba(0, 86, 179, 0.9), rgba(0, 61, 130, 0.9));
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 40px;
        }

        /* SEARCH FORM STYLES */
        .search-form {
            margin-right: 15px;
        }

        .search-input {
            width: 250px;
            border-radius: 20px 0 0 20px !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            width: 300px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.1);
        }

        .search-input:focus::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-outline-light.btn-sm {
            border-radius: 0 20px 20px 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.375rem 1rem;
        }

        .btn-outline-light.btn-sm:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* SEARCH HERO SECTION */
        .search-hero-section {
            background:
                linear-gradient(135deg, rgba(16, 17, 17, 0.65) 0%, rgba(8, 8, 8, 0.55) 100%),
                url('https://www.polibatam.ac.id/wp-content/uploads/2022/04/MG_8893-scaled.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 60vh;
            display: flex;
            align-items: center;
        }

        .search-hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .search-title {
            font-weight: 800;
            margin-bottom: 25px;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.4);
            font-size: 3.5rem;
            line-height: 1.1;
        }

        .search-subtitle {
            font-size: 1.4rem;
            margin-bottom: 35px;
            opacity: 0.95;
            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.3);
            line-height: 1.6;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            margin-top: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-hero-input {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 15px 20px;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .search-hero-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-hero-input:focus {
            background: rgba(255, 255, 255, 0.25);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        .search-hero-btn {
            background: var(--accent-color);
            color: #000;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .search-hero-btn:hover {
            background: var(--accent-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* SEARCH RESULTS STYLES */
        .results-section {
            padding: 60px 0;
            background: var(--gray-light);
        }

        .results-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }

        .results-header {
            border-bottom: 2px solid var(--gray-light);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .results-count {
            background: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .kategori-found {
            background: #e7f3ff;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .kategori-tag {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin: 3px;
        }

        .section-title {
            position: relative;
            margin-bottom: 30px;
            padding-bottom: 15px;
            text-align: center;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--accent-color);
        }

        .result-card {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height: 100%;
            background: white;
            border: 1px solid #eaeaea;
        }

        .result-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .result-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .card-body {
            padding: 20px;
        }

        .news-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .news-title {
            font-weight: 600;
            margin: 10px 0;
            color: var(--primary-color);
            font-size: 1.2rem;
            line-height: 1.4;
        }

        .read-more {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .read-more:hover {
            color: #003d82;
        }

        .read-more i {
            margin-left: 5px;
            transition: transform 0.3s;
        }

        .read-more:hover i {
            transform: translateX(5px);
        }

        /* BADGE STYLES */
        .badge-berita {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
        }
        .badge-informasi { background: #17a2b8; }
        .badge-pengumuman { background: #28a745; }
        .badge-beasiswa { background: #ffc107; color: #000; }
        .badge-akademik { background: #6f42c1; }
        .badge-kemahasiswaan { background: #e83e8c; }

        .kategori-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        /* HIGHLIGHT STYLES */
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 600;
        }

        /* NO RESULTS STYLES */
        .no-results {
            padding: 60px 0;
            text-align: center;
            background: white;
            border-radius: 15px;
            margin: 30px 0;
        }

        .no-results-icon {
            font-size: 4rem;
            color: var(--gray-medium);
            margin-bottom: 20px;
        }

        /* SUGGESTION CARDS */
        .suggestion-card {
            border: 2px dashed var(--primary-color);
            background: rgba(0, 86, 179, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .suggestion-card:hover {
            background: rgba(0, 86, 179, 0.1);
            transform: translateY(-5px);
        }

        /* KATEGORI LIST */
        .kategori-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .kategori-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.9rem;
            color: #495057;
            transition: all 0.3s;
            cursor: pointer;
        }

        .kategori-item:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* FOOTER */
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0 20px;
            margin-top: 60px;
        }

        .footer a {
            color: #ddd;
            text-decoration: none;
        }

        .footer a:hover {
            color: white;
        }

        .social-icons a {
            display: inline-block;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .search-hero-section {
                padding: 100px 0 60px;
                min-height: 50vh;
            }
            
            .search-title {
                font-size: 2.8rem;
            }
            
            .search-subtitle {
                font-size: 1.2rem;
            }
            
            .search-box {
                padding: 30px 20px;
            }
            
            .search-form {
                margin: 10px 0;
                width: 100%;
            }
            
            .search-input {
                width: 100% !important;
                margin-bottom: 10px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .btn-outline-light.btn-sm {
                width: 100%;
                border-radius: 20px !important;
            }
        }

        @media (max-width: 768px) {
            .search-hero-section {
                padding: 80px 0 40px;
            }
            
            .search-title {
                font-size: 2.2rem;
            }
            
            .results-container {
                padding: 20px;
                margin-top: -30px;
            }
            
            .kategori-list {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png"
                    alt="Politeknik Negeri Batam">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="berita.php">Berita Kampus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="event.php">Event & Kegiatan</a>
                    </li>
                    
                    <!-- Form Pencarian -->
                    <li class="nav-item">
                        <form class="d-flex search-form" action="search.php" method="GET">
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control form-control-sm search-input" 
                                       name="q" 
                                       placeholder="Cari berita/event/kategori..." 
                                       aria-label="Search"
                                       value="<?php echo htmlspecialchars($keyword); ?>">
                                <button class="btn btn-outline-light btn-sm" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HEADER -->
    <div class="berita-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                 
                </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Section Hasil Pencarian -->
    <section class="results-section">
        <div class="container">
            <div class="results-container">
                <div class="results-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h2 class="mb-0">
                                <?php if (!empty($keyword)): ?>
                                    Hasil Pencarian untuk "<span class="text-primary"><?php echo htmlspecialchars($keyword); ?></span>"
                                <?php else: ?>
                                    Masukkan Kata Kunci Pencarian
                                <?php endif; ?>
                            </h2>
                        </div>
                        <?php if (!empty($keyword) && $total_results > 0): ?>
                            <span class="results-count">
                                <i class="fas fa-search me-1"></i> <?php echo $total_results; ?> hasil ditemukan
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($keyword) && !empty($kategori_ditemukan)): ?>
                        <div class="kategori-found mt-3">
                            <h6 class="mb-2">
                                <i class="fas fa-tags me-2"></i>Kategori terkait ditemukan:
                            </h6>
                            <div class="kategori-list">
                                <?php foreach (array_unique($kategori_ditemukan) as $kat): ?>
                                    <span class="kategori-tag">
                                        <?php echo htmlspecialchars($kat); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($keyword)): ?>
                    <?php if ($total_results > 0): ?>
                        <!-- Hasil Berita -->
                        <?php if (!empty($search_results['berita'])): ?>
                            <div class="mb-5">
                                <h3 class="section-title text-start">
                                    <i class="fas fa-newspaper me-2"></i> Berita Terkait
                                    <span class="badge bg-primary ms-2"><?php echo count($search_results['berita']); ?></span>
                                </h3>
                                
                                <div class="row">
                                    <?php foreach ($search_results['berita'] as $berita): ?>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="result-card">
                                                <!-- Gambar berita -->
                                                <img src="<?php 
                                                    echo !empty($berita['gambar']) ? htmlspecialchars($berita['gambar']) : 
                                                    'https://images.unsplash.com/photo-1588681664899-f142ff2dc9b1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                                ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>">
                                                
                                                <div class="card-body">
                                                    <!-- Badge kategori berita -->
                                                    <span class="badge-berita badge-<?php echo $berita['kategori_berita']; ?>">
                                                        <?php 
                                                            $kategori_labels = [
                                                                'informasi' => 'Informasi',
                                                                'pengumuman' => 'Pengumuman',
                                                                'beasiswa' => 'Beasiswa',
                                                                'akademik' => 'Akademik',
                                                                'kemahasiswaan' => 'Kemahasiswaan'
                                                            ];
                                                            echo $kategori_labels[$berita['kategori_berita']] ?? 'Informasi';
                                                        ?>
                                                    </span>
                                                    
                                                    <!-- Tanggal berita -->
                                                    <div class="news-date">
                                                        <i class="far fa-calendar-alt me-2"></i>
                                                        <?php echo date('d F Y', strtotime($berita['created_at'])); ?>
                                                    </div>
                                                    
                                                    <!-- Judul berita dengan highlight -->
                                                    <h3 class="news-title">
                                                        <?php 
                                                            echo preg_replace(
                                                                "/(" . preg_quote($keyword, '/') . ")/i", 
                                                                '<span class="highlight">$1</span>', 
                                                                htmlspecialchars($berita['judul'])
                                                            );
                                                        ?>
                                                    </h3>
                                                    
                                                    <!-- Excerpt berita dengan highlight -->
                                                    <p class="card-text">
                                                        <?php 
                                                            $excerpt = !empty($berita['excerpt']) ? $berita['excerpt'] : substr(strip_tags($berita['konten']), 0, 120);
                                                            echo preg_replace(
                                                                "/(" . preg_quote($keyword, '/') . ")/i", 
                                                                '<span class="highlight">$1</span>', 
                                                                htmlspecialchars($excerpt)
                                                            ) . '...';
                                                        ?>
                                                    </p>
                                                    
                                                    <!-- Views counter -->
                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <span class="text-muted">
                                                            <i class="fas fa-eye me-1"></i> <?php echo $berita['views'] ?? 0; ?> dilihat
                                                        </span>
                                                        <a href="detail_berita.php?id=<?php echo $berita['id']; ?>" class="read-more">
                                                            Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Hasil Events -->
                        <?php if (!empty($search_results['events'])): ?>
                            <div class="mb-5">
                                <h3 class="section-title text-start">
                                    <i class="fas fa-calendar-alt me-2"></i> Event & Kegiatan Terkait
                                    <span class="badge bg-primary ms-2"><?php echo count($search_results['events']); ?></span>
                                </h3>
                                
                                <div class="row">
                                    <?php foreach ($search_results['events'] as $event): ?>
                                        <div class="col-lg-4 col-md-6">
                                            <div class="result-card">
                                                <!-- Gambar event -->
                                                <img src="<?php 
                                                    echo !empty($event['poster']) ? htmlspecialchars($event['poster']) : 
                                                    'https://images.unsplash.com/photo-1546519638-68e109498ffc?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                                                ?>" alt="<?php echo htmlspecialchars($event['judul']); ?>">
                                                
                                                <div class="card-body">
                                                    <!-- Tanggal event -->
                                                    <div class="news-date">
                                                        <i class="far fa-calendar-alt me-2"></i>
                                                        <?php echo date('d F Y', strtotime($event['tanggal'])); ?>
                                                        <?php if (!empty($event['waktu'])): ?>
                                                            <br><i class="far fa-clock me-2"></i><?php echo date('H:i', strtotime($event['waktu'])); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Badge kategori -->
                                                    <?php if (!empty($event['kategori_nama'])): ?>
                                                        <div class="kategori-badge" style="background: <?php echo $event['warna'] ?? '#0056b3'; ?>">
                                                            <i class="<?php echo $event['ikon'] ?? 'fas fa-calendar'; ?> me-1"></i>
                                                            <?php echo htmlspecialchars($event['kategori_nama']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Judul event dengan highlight -->
                                                    <h3 class="news-title">
                                                        <?php 
                                                            echo preg_replace(
                                                                "/(" . preg_quote($keyword, '/') . ")/i", 
                                                                '<span class="highlight">$1</span>', 
                                                                htmlspecialchars($event['judul'])
                                                            );
                                                        ?>
                                                    </h3>
                                                    
                                                    <!-- Deskripsi singkat dengan highlight -->
                                                    <p class="card-text">
                                                        <?php 
                                                            $desc = !empty($event['deskripsi_singkat']) ? $event['deskripsi_singkat'] : substr(strip_tags($event['deskripsi']), 0, 120);
                                                            echo preg_replace(
                                                                "/(" . preg_quote($keyword, '/') . ")/i", 
                                                                '<span class="highlight">$1</span>', 
                                                                htmlspecialchars($desc)
                                                            ) . '...';
                                                        ?>
                                                    </p>
                                                    
                                                    <!-- Lokasi -->
                                                    <?php if (!empty($event['lokasi'])): ?>
                                                        <p class="mb-2">
                                                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                                            <small><?php echo htmlspecialchars($event['lokasi']); ?></small>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Link ke detail -->
                                                    <a href="detail_event.php?id=<?php echo $event['id']; ?>" class="read-more">
                                                        Lihat Detail <i class="fas fa-arrow-right"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Daftar Kategori untuk Eksplorasi -->
                        <?php if ($total_results < 5): ?>
                            <div class="mt-5 pt-5 border-top">
                                <h4 class="mb-4 text-center">
                                    <i class="fas fa-compass me-2"></i>Eksplorasi Kategori Lainnya
                                </h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm mb-4">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kategori Event</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="kategori-list">
                                                    <?php foreach ($kategori_event as $kategori => $keywords): ?>
                                                        <a href="search.php?q=<?php echo urlencode($kategori); ?>" 
                                                           class="kategori-item text-decoration-none">
                                                            <?php echo htmlspecialchars($kategori); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0"><i class="fas fa-newspaper me-2"></i>Kategori Berita</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="kategori-list">
                                                    <?php foreach ($kategori_berita as $kategori => $keywords): ?>
                                                        <a href="search.php?q=<?php echo urlencode($kategori); ?>" 
                                                           class="kategori-item text-decoration-none">
                                                            <?php echo htmlspecialchars($kategori); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Tombol Lihat Semua -->
                        <div class="text-center mt-5">
                            <?php if (!empty($search_results['berita'])): ?>
                                <a href="berita.php" class="btn btn-primary px-4 me-3">
                                    <i class="fas fa-newspaper me-1"></i> Lihat Semua Berita
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($search_results['events'])): ?>
                                <a href="event.php" class="btn btn-outline-primary px-4">
                                    <i class="fas fa-calendar-alt me-1"></i> Lihat Semua Event
                                </a>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <!-- Tidak ada hasil -->
                        <div class="no-results">
                            <div class="no-results-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="mb-3">Tidak ditemukan hasil untuk "<?php echo htmlspecialchars($keyword); ?>"</h3>
                            <p class="text-muted mb-4">Coba gunakan kata kunci yang berbeda atau eksplorasi kategori di bawah.</p>
                            
                            <!-- Daftar Kategori untuk Eksplorasi -->
                            <div class="row mt-4">
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kategori Event</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="kategori-list">
                                                <?php foreach ($kategori_event as $kategori => $keywords): ?>
                                                    <a href="search.php?q=<?php echo urlencode($kategori); ?>" 
                                                       class="kategori-item text-decoration-none">
                                                        <?php echo htmlspecialchars($kategori); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0"><i class="fas fa-newspaper me-2"></i>Kategori Berita</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="kategori-list">
                                                <?php foreach ($kategori_berita as $kategori => $keywords): ?>
                                                    <a href="search.php?q=<?php echo urlencode($kategori); ?>" 
                                                       class="kategori-item text-decoration-none">
                                                        <?php echo htmlspecialchars($kategori); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Suggestion Events -->
                            <?php if (!empty($suggestion_events)): ?>
                                <div class="mt-5">
                                    <h4 class="mb-4">
                                        <i class="fas fa-calendar-check me-2"></i>Mungkin Anda Tertarik:
                                    </h4>
                                    <div class="row">
                                        <?php foreach ($suggestion_events as $event): ?>
                                            <div class="col-md-4">
                                                <div class="suggestion-card">
                                                    <h5><?php echo htmlspecialchars($event['judul']); ?></h5>
                                                    <p class="text-muted mb-2">
                                                        <i class="far fa-calendar-alt me-1"></i>
                                                        <?php echo date('d F Y', strtotime($event['tanggal'])); ?>
                                                    </p>
                                                    <p class="mb-3">
                                                        <?php echo substr(strip_tags($event['deskripsi']), 0, 100) . '...'; ?>
                                                    </p>
                                                    <a href="detail_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        Lihat Detail
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-5">
                                <a href="index.php" class="btn btn-primary me-2">
                                    <i class="fas fa-home me-1"></i> Kembali ke Beranda
                                </a>
                                <a href="berita.php" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-newspaper me-1"></i> Lihat Semua Berita
                                </a>
                                <a href="event.php" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-alt me-1"></i> Lihat Semua Event
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- Jika tidak ada keyword -->
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="mb-3">Masukkan kata kunci pencarian</h3>
                        <p class="text-muted mb-4">Gunakan form di atas untuk mencari berita, event, dan informasi kampus.</p>
                        
                        <!-- Daftar Kategori Populer -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h4 class="mb-4 text-center">Kategori Populer</h4>
                                <div class="row">
                                    <div class="col-md-3 col-6 mb-3">
                                        <a href="search.php?q=beasiswa" class="text-decoration-none">
                                            <div class="card border-0 shadow-sm h-100 text-center hover-shadow">
                                                <div class="card-body">
                                                    <i class="fas fa-graduation-cap fa-2x text-warning mb-3"></i>
                                                    <h6>Beasiswa</h6>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <a href="search.php?q=seminar" class="text-decoration-none">
                                            <div class="card border-0 shadow-sm h-100 text-center hover-shadow">
                                                <div class="card-body">
                                                    <i class="fas fa-chalkboard-teacher fa-2x text-primary mb-3"></i>
                                                    <h6>Seminar</h6>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <a href="search.php?q=olahraga" class="text-decoration-none">
                                            <div class="card border-0 shadow-sm h-100 text-center hover-shadow">
                                                <div class="card-body">
                                                    <i class="fas fa-futbol fa-2x text-success mb-3"></i>
                                                    <h6>Olahraga</h6>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <a href="search.php?q=akademik" class="text-decoration-none">
                                            <div class="card border-0 shadow-sm h-100 text-center hover-shadow">
                                                <div class="card-body">
                                                    <i class="fas fa-book fa-2x text-info mb-3"></i>
                                                    <h6>Akademik</h6>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Politeknik Negeri Batam</h5>
                    <p>Jl. Ahmad Yani, Batam Kota, Batam 29461</p>
                    <p>Kepulauan Riau, Indonesia</p>
                    <p>Telp: (0778) 469856</p>
                    <p>Email: info@polibatam.ac.id</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="berita.php">Berita Kampus</a></li>
                        <li><a href="event.php">Event & Kegiatan</a></li>
                        <li><a href="admin/login.php">Panel Admin</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Ikuti Kami</h5>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <li><a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2025 Portal Informasi Kampus - Politeknik Negeri Batam</p>
                <small>
                    Halaman Pencarian | 
                    <?php echo date('d/m/Y H:i:s'); ?>
                </small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-focus pada input pencarian
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-hero-input');
            if (searchInput) {
                // Fokus dan pilih semua teks yang ada
                searchInput.focus();
                searchInput.select();
            }
            
            // Highlight animation untuk search box
            const searchBox = document.querySelector('.search-box');
            if (searchBox) {
                setTimeout(() => {
                    searchBox.style.transform = 'translateY(0)';
                    searchBox.style.opacity = '1';
                }, 300);
            }
            
            // Animasi untuk kategori item
            const kategoriItems = document.querySelectorAll('.kategori-item');
            kategoriItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Update title dengan keyword
        const urlParams = new URLSearchParams(window.location.search);
        const keyword = urlParams.get('q');
        if (keyword) {
            document.title = `"${keyword}" - Hasil Pencarian - Portal Kampus`;
        }
        
        // Hover effect untuk cards
        document.querySelectorAll('.hover-shadow').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
            });
        });
    </script>
</body>
</html>

<?php
// Tutup koneksi database
if (isset($conn)) {
    mysqli_close($conn);
}
?>
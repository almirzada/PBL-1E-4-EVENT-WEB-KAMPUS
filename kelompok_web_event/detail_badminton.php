<?php
// detail_badminton.php - VERSI DINAMIS

// 1. KONEKSI DATABASE
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 2. AMBIL DATA LOMBA BADMINTON
$sql = "SELECT * FROM lomba_details WHERE jenis_lomba = 'Badminton'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $judul_halaman = "Lomba Badminton Mahasiswa";
    $konten_html = "<h2>Data lomba badminton belum tersedia</h2><p>Admin sedang menyiapkan konten...</p>";
    $gambar_lomba = null;
} else {
    $detail = $result->fetch_assoc();
    $judul_halaman = $detail['judul_halaman'];
    $konten_html = $detail['konten_html'];
    $gambar_lomba = $detail['gambar_lomba'] ?? null; // Ambil nama file gambar dari database
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($judul_halaman); ?> - Politeknik Negeri Batam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset dan Base Styles */
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
 
        /* Header & Navbar - SAMA dengan basket */
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
 
        /* Main Content */
        .detail-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
 
        .detail-container h2 {
            text-align: center;
            color: #004aad;
            margin-bottom: 20px;
        }
 
        .detail-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }
 
        .detail-info h3 {
            color: #004aad;
            margin-top: 15px;
        }
 
        .detail-info p {
            color: #333;
            line-height: 1.6;
            margin-bottom: 10px;
        }
 
        /* ====== Rules (Aturan) Section ====== */
        .rules-section {
            margin-top: 20px;
            background: #fff;
            padding: 18px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
 
        .rules-section h3 {
            color: #c0392b;
            margin-bottom: 10px;
        }
 
        .rules-content ol {
            margin-left: 18px;
            color: #333;
            line-height: 1.6;
        }
 
        .rules-content li {
            margin-bottom: 8px;
            font-size: 15px;
        }
 
        .rules-content strong {
            color: #004aad;
        }
 
        /* Schedule & Contact */
        .schedule-contact {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
 
        .schedule-box,
        .contact-box {
            flex: 1;
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
 
        .schedule-box h3,
        .contact-box h3 {
            color: #004aad;
            font-size: 1.3rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #004aad;
        }
 
        .schedule-box p,
        .contact-box p {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
 
        .contact-box i,
        .schedule-box i {
            color: #004aad;
            margin-right: 10px;
            width: 20px;
        }
 
        /* Button Group */
        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 25px;
        }
 
        .button-group button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
 
        .button-group button:hover {
            background-color: #0056b3;
        }
 
        .button-group button:last-child {
            background-color: #6c757d;
        }
 
        .button-group button:last-child:hover {
            background-color: #545b62;
        }
 
        /* Footer */
        footer {
            background-color: #004aad;
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 50px;
        }

        .footer-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .footer-info {
            flex: 1;
            min-width: 300px;
        }

        .footer-info h3 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .footer-info p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .footer-links {
            flex: 0 0 200px;
        }

        .footer-links h4 {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .footer-links ul {
            list-style: none;
            padding-left: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }

        .footer-separator {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 0 auto;
            width: 100%;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
 
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
 
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
 
        .detail-container>* {
            animation: fadeIn 0.8s ease forwards;
        }
 
        /* List styling */
        .detail-info ul {
            margin-left: 20px;
            margin-bottom: 20px;
            color: #333;
        }
 
        .detail-info li {
            margin-bottom: 8px;
            font-size: 1rem;
        }
 
        /* Responsif untuk HP */
        @media (max-width: 768px) {
            .detail-container {
                margin: 30px 15px;
                padding: 20px;
            }
 
            .schedule-contact {
                flex-direction: column;
            }
 
            .button-group {
                flex-direction: column;
                align-items: center;
            }
 
            .button-group button {
                width: 100%;
                justify-content: center;
            }
 
            .rules-section {
                padding: 14px;
            }
 
            .rules-content li {
                font-size: 14px;
            }

            .footer-container {
                padding: 0 20px;
            }

            .footer-top {
                flex-direction: column;
                gap: 30px;
            }

            .footer-info, .footer-links {
                width: 100%;
            }

            .footer-links {
                margin-top: 10px;
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
                        <a class="nav-link" href="daftar.php">Pendaftaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
 
    <!-- ========== GAMBAR DARI DATABASE ========== -->
    <div class="detail-container">
        <h2><?php echo htmlspecialchars($judul_halaman); ?></h2>
        
        <!-- TAMPILKAN GAMBAR DARI DATABASE -->
        <div style="text-align:center; padding:10px;">
            <?php if (!empty($gambar_lomba) && file_exists("uploads/" . $gambar_lomba)): ?>
                <!-- Jika ada gambar di database dan file-nya ada di folder uploads -->
                <img src="uploads/<?php echo htmlspecialchars($gambar_lomba); ?>" 
                     alt="<?php echo htmlspecialchars($judul_halaman); ?>"
                     class="detail-img">
            <?php else: ?>
                <!-- Gambar default jika tidak ada di database -->
                <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                     alt="Lomba Badminton"
                     class="detail-img">
            <?php endif; ?>
        </div>
        
        <!-- KONTEN HTML DARI DATABASE -->
        <?php echo $konten_html; ?>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-info">
                    <h3>Politeknik Negeri Batam</h3>
                    <p>Jl. Ahmad Yani, Batam Kota, Batam 29461</p>
                    <p>Kepulauan Riau, Indonesia</p>
                    <p>Telp: (0778) 469856</p>
                    <p>Email: info@polibatam.ac.id</p>
                </div>
                
                <div class="footer-links">
                    <h4>Tautan Cepat</h4>
                    <ul>
                        <li><a href="index.php" style="color: white;">Beranda</a></li>
                        <li><a href="daftar.php" style="color: white;">Pendaftaran</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="footer-separator">
            
            <div class="footer-bottom">
                <p>&copy; 2025 Politeknik Negeri Batam. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            const buttons = document.querySelectorAll('.button-group button');
            buttons.forEach(button => {
                button.addEventListener('click', function () {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
                    this.disabled = true;

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 1000);
                });
            });
        });

        function goBack() {
            window.history.back();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
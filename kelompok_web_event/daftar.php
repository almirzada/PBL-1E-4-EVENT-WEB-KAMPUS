<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulir Pendaftaran Lomba</title>
 <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    :root {
      --primary: #0056b3; /* SAMA DENGAN INDEX.PHP #0056b3 */
      --secondary: #e63946;
      --success: #28a745;
      --danger: #dc3545;
      --warning: #ffc107;
      --light-color: #fff;
      --gray-light: #f5f7fa;
      --dark: #343a40;
      --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      --hover-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }

    /* HAPUS ONLY NAVBAR CUSTOM STYLE (baris 30-85) */
    /* TETAPKAN BODY BACKGROUND BIAR SAMA DENGAN INDEX.PHP */
    body {
      background: linear-gradient(to bottom, #f0f1f3ff 80%, #ffffff 100%);
      color: #333;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
 

    /* CONTAINER FORM (BUKAN .container BIAR GAK TABRAK BOOTSTRAP) */
   .form-container {
  background-color: white;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  padding: 40px;
  width: 100%;
  max-width: 900px;
  margin: 60px auto 30px auto; /* Tambah margin-top jadi 60px */
  transition: box-shadow 0.3s ease;
}

    .form-container:hover {
      box-shadow: var(--hover-shadow);
    }

   h2 {
  text-align: center;
  color: var(--primary);
  margin: 50px 0 30px 0; /* Tambahkan margin atas 40px */
  font-size: 3.4rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  position: relative;
  padding-bottom: 15px;
}
    h2:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 4px;
      background: linear-gradient(to right, var(--primary), var(--secondary));
      border-radius: 2px;
    }

    /* LABEL WARNA BIRU */
.form-group label {
  color: #1565c0 !important; /* Biru */
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 8px;
  display: block;
}

/* Icon dalam label juga biru */
.form-group label i {
  color: #1565c0;
  margin-right: 8px;
}

/* Input field tetap standar */
.input-with-icon {
  position: relative;
  margin-top: 5px;
}

.input-with-icon i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #777;
}

.input-with-icon input {
  width: 100%;
  padding: 14px 14px 14px 45px;
  border: 2px solid #ddd;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.input-with-icon input:focus {
  border-color: #1e88e5;
  box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.2);
  outline: none;
}

    .form-header {
      text-align: center;
      margin-bottom: 60px;
    }

    .form-header p {
      color: #666;
      font-size: 1.1rem;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    .form-row {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
    }

    .form-group {
      flex: 1;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #444;
      font-size: 1.7rem;
    }

    .input-with-icon {
      position: relative;
    }

    .input-with-icon i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
    }

    .input-with-icon input,
    .input-with-icon select {
      padding-left: 45px;
    }

    input,
    select {
      width: 100%;
      padding: 14px;
      border: 2px solid #e1e5eb;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background-color: #f9f9f9;
    }

    input:focus,
    select:focus {
      border-color: var(--primary);
      outline: none;
      background-color: #fff;
      box-shadow: 0 0 0 3px rgba(0, 74, 173, 0.1);
    }

    .button-group {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      border-radius: 40px;
    }

    button {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      border-radius: 30px;
    }

    button[type="submit"] {
      background: linear-gradient(135deg, var(--primary), #0066cc);
      color: white;
      border-radius: 30px;
      padding: 12px;
    }

    button[type="submit"]:hover {
      background: linear-gradient(135deg, #0066cc, var(--primary));
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(0, 74, 173, 0.4);
    }

    .back-button {
      background-color: #6c757d;
      color: white;
    }

    .back-button:hover {
      background-color: #5a6268;
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(108, 117, 125, 0.4);
    }

    .lomba-section {
      margin-top: 20px;
      padding: 25px;
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-radius: 12px;
      border-left: 5px solid var(--primary);
      margin-bottom: 25px;
    }

    .lomba-section h4 {
      color: var(--primary);
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .lomba-cards {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }

    .lomba-card {
      flex: 1;
      background: white;
      border-radius: 10px;
      padding: 10px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .lomba-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .lomba-card.active {
      border-color: var(--primary);
      background: linear-gradient(135deg, #f0f7ff, #e1eeff);
    }

    .lomba-icon {
      font-size: 2.3rem;
      margin-bottom: 10px;
      color: var(--primary);
    }

    .lomba-card h5 {
      color: var(--primary);
      margin-bottom: 1px;
    }

    .lomba-card p {
      color: #666;
      font-size: 0.9rem;
    }

    .anggota-section {
      margin-top: 20px;
      padding: 25px;
      background-color: white;
      border-radius: 12px;
      border: 2px solid #e1e5eb;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .anggota-section h4 {
      color: var(--primary);
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .add-anggota {
      background: linear-gradient(135deg, var(--success), #20c997);
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 600;
      margin-top: 10px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
      justify-content: center;
    }

    .add-anggota:hover {
      background: linear-gradient(135deg, #20c997, var(--success));
      transform: translateY(-3px);
      box-shadow: 0 7px 15px rgba(40, 167, 69, 0.3);
    }

    .anggota-item {
      background: linear-gradient(135deg, #f8f9fa, #ffffff);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      border: 2px solid #e9ecef;
      position: relative;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .anggota-item:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
    }

    .anggota-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f1f3f4;
    }

    .anggota-title {
      color: var(--primary);
      font-weight: 600;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .remove-anggota {
      background: linear-gradient(135deg, var(--danger), #e52d27);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 8px 8px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 3px;
    }

    .remove-anggota:hover {
      background: linear-gradient(135deg, #e52d27, var(--danger));
      transform: scale(1.05);
    }

    .anggota-fields {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .anggota-fields .form-group {
      margin-bottom: 0;
    }

    .counter-info {
      text-align: center;
      margin: 15px 0;
      color: #666;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      background: #f8f9fa;
      padding: 10px;
      border-radius: 8px;
    }

    .max-warning {
      color: var(--danger);
      font-weight: 600;
    }

    .lomba-info {
      background: linear-gradient(135deg, #e7f3ff, #d4e7ff);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      border-left: 5px solid var(--primary);
      display: none;
    }

    .lomba-info h4 {
      color: var(--primary);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .lomba-info p {
      color: #555;
      margin-bottom: 5px;
    }

    footer {
      text-align: center;
      margin-top: 30px;
      padding: 20px;
      color: #666;
      width: 100%;
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

    .form-container {
      animation: fadeIn 0.8s ease forwards;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .anggota-item {
      animation: slideIn 0.3s ease forwards;
    }

    /* Responsif untuk HP */
    @media (max-width: 768px) {
      .form-container {
        padding: 25px;
        margin: 10px;
      }

      h2 {
        font-size: 1.8rem;
      }

      .button-group {
        flex-direction: column;
      }

      .anggota-fields {
        grid-template-columns: 1fr;
      }

      .anggota-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
      }

      .form-row {
        flex-direction: column;
        gap: 0;
      }

      .lomba-cards {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
 <!-- Navbar Bootstrap Sama dengan Index.php -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0056b3;">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <img src="https://www.polibatam.ac.id/wp-content/uploads/2022/01/poltek.png" 
           height="50" alt="Politeknik Negeri Batam">
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
          <a class="nav-link active" href="daftar.php">Pendaftaran</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="admin/login.php">Admin</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

  <div class="container">
    <h2><i class="fas fa-trophy"></i> Formulir Pendaftaran</h2>
    <div class="form-header">
      <p>Daftarkan tim Anda untuk mengikuti Lomba Antar Jurusan Politeknik Negeri Batam</p>
    </div>

    <form id="formPendaftaran" method="POST" action="proses_daftar.php">
      <div class="form-row">
        <div class="form-group">
          <label for="nim"><i class="fas fa-id-card"></i> NIM:</label>
          <div class="input-with-icon">
            <i class="fas fa-id-card"></i>
            <input type="text" id="nim" name="nim" required placeholder="Masukkan NIM Anda">
          </div>
        </div>

        <div class="form-group">
          <label for="nama"><i class="fas fa-user"></i> Nama Ketua:</label>
          <div class="input-with-icon">
            <i class="fas fa-user"></i>
            <input type="text" id="nama" name="nama" required placeholder="Masukkan nama anda">
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="prodi"><i class="fas fa-graduation-cap"></i> Program Studi:</label>
          <div class="input-with-icon">
            <i class="fas fa-graduation-cap"></i>
            <input type="text" id="prodi" name="prodi" required placeholder="Masukkan program studi Anda">
          </div>
        </div>

        <div class="form-group">
          <label for="wa"><i class="fas fa-phone"></i> Nomor WA Aktif:</label>
          <div class="input-with-icon">
            <i class="fas fa-phone"></i>
            <input type="tel" id="wa" name="wa" required placeholder="Contoh: 081234567890">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="ketua"><i class="fas fa-crown"></i> Nama Tim:</label>
        <div class="input-with-icon">
          <i class="fas fa-crown"></i>
          <input type="text" id="ketua" name="ketua" required placeholder="Masukkan Nama tim anda">
        </div>
      </div>

      <!-- TAMBAHKAN INPUT UNTUK TAHUN ANGKATAN KETUA -->
      <div class="form-group">
        <label for="tahun"><i class="fas fa-calendar-alt"></i> Tahun Angkatan (Ketua):</label>
        <div class="input-with-icon">
          <i class="fas fa-calendar-alt"></i>
          <select id="tahun" name="tahun" required>
            <option value="">Pilih Tahun</option>
            <option value="2022">2022</option>
            <option value="2023">2023</option>
            <option value="2024">2024</option>
            <option value="2025">2025</option>
            <option value="2026">2026</option>
          </select>
        </div>
      </div>

      <div class="lomba-section">
        <h4><i class="fas fa-running"></i> Pilih Jenis Lomba</h4>
        <div class="lomba-cards">
          <div class="lomba-card" data-lomba="Futsal">
            <div class="lomba-icon">
              <i class="fas fa-futbol"></i>
            </div>
            <h5>Futsal</h5>
            <p>Maks. 10 Pemain</p>
          </div>
          <div class="lomba-card" data-lomba="Basket">
            <div class="lomba-icon">
              <i class="fas fa-basketball-ball"></i>
            </div>
            <h5>Basket</h5>
            <p>Maks. 12 Pemain</p>
          </div>
          <div class="lomba-card" data-lomba="Badminton">
            <div class="lomba-icon">
              <i class="fas fa-table-tennis"></i>
            </div>
            <h5>Badminton</h5>
            <p>Maks. 2 Pemain</p>
          </div>
        </div>
        <input type="hidden" id="lomba" name="jenis_lomba" required>
      </div>

      <div class="lomba-info" id="lombaInfo">
        <h4><i class="fas fa-info-circle"></i> Informasi Lomba</h4>
        <p id="infoText">Pilih jenis lomba untuk melihat informasi detail</p>
      </div>

      <div class="anggota-section">
        <h4><i class="fas fa-users"></i> Anggota Tim</h4>
        <div class="counter-info">
          <i class="fas fa-user-friends"></i> Jumlah anggota: <span id="anggotaCount">0</span>
          <span id="maxAnggotaText">/10</span>
        </div>
        <div id="anggotaContainer">
          <!-- Anggota tim akan ditambahkan di sini -->
        </div>
        <button type="button" class="add-anggota" id="tambahAnggota">
          <i class="fas fa-plus"></i> Tambah Anggota
        </button>
      </div>

      <div class="button-group">
        <!-- HAPUS FORM YANG SALAH DAN GUNAKAN TOMBOL SUBMIT YANG BENAR -->
        <button type="submit"><i class="fas fa-paper-plane"></i> Daftar</button>
        <button type="button" class="back-button" onclick="history.back()">
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
      </div>
    </form>
  </div>
  <footer>
    <p>© 2025 Politeknik Negeri Batam - Turnamen Olahraga Antar Jurusan</p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const anggotaContainer = document.getElementById('anggotaContainer');
      const tambahAnggotaBtn = document.getElementById('tambahAnggota');
      const anggotaCountSpan = document.getElementById('anggotaCount');
      const maxAnggotaText = document.getElementById('maxAnggotaText');
      const lombaCards = document.querySelectorAll('.lomba-card');
      const lombaInput = document.getElementById('lomba');
      const lombaInfo = document.getElementById('lombaInfo');
      const infoText = document.getElementById('infoText');

      let anggotaCount = 0;
      let maxAnggota = 10;
      let currentLomba = '';

      // Informasi untuk setiap lomba
      const lombaDetails = {
        'Futsal': {
          maxAnggota: 10,
          info: 'Futsal: Maksimal 10 pemain (5 pemain utama + 5 cadangan) Minimal 7 anggota. Durasi pertandingan 2x20 menit waktu kotor.',
          icon: 'futbol',
          color: '#004aad'
        },
        'Basket': {
          maxAnggota: 12,
          info: 'Basket: Maksimal 12 pemain (5 pemain utama + 7 cadangan) Minimal 7 anggota. Durasi pertandingan 3x10 menit.',
          icon: 'basketball-ball',
          color: '#e63946'
        },
        'Badminton': {
          maxAnggota: 2,
          info: 'Badminton Ganda: Maksimal 2 pemain per tim. Sistem gugur dengan best of three sets.',
          icon: 'TableTennisPaddleBall',
          color: '#28a745'
        }
      };

      // Event listener untuk kartu lomba
      lombaCards.forEach(card => {
        card.addEventListener('click', function () {
          const selectedLomba = this.getAttribute('data-lomba');

          // Hapus kelas active dari semua kartu
          lombaCards.forEach(c => c.classList.remove('active'));

          // Tambah kelas active ke kartu yang dipilih
          this.classList.add('active');

          // Set nilai input hidden
          lombaInput.value = selectedLomba;
          currentLomba = selectedLomba;

          // Tampilkan informasi lomba
          if (currentLomba && lombaDetails[currentLomba]) {
            maxAnggota = lombaDetails[currentLomba].maxAnggota;
            infoText.textContent = lombaDetails[currentLomba].info;
            maxAnggotaText.textContent = `/${maxAnggota}`;
            lombaInfo.style.display = 'block';

            // Reset anggota jika melebihi batas baru
            if (anggotaCount > maxAnggota) {
              const anggotaItems = anggotaContainer.querySelectorAll('.anggota-item');
              for (let i = maxAnggota; i < anggotaItems.length; i++) {
                anggotaItems[i].remove();
              }
              anggotaCount = maxAnggota;
              updateCounter();
              updateAnggotaNumbers();
            }

            updateTambahButton();
          }
        });
      });

      // Fungsi untuk menambah anggota
      function tambahAnggota() {
        if (!currentLomba) {
          alert('Pilih jenis lomba terlebih dahulu!');
          return;
        }

        if (anggotaCount >= maxAnggota) {
          alert(`Maksimal ${maxAnggota} anggota untuk lomba ${currentLomba}!`);
          return;
        }

        anggotaCount++;
        updateCounter();

        const anggotaItem = document.createElement('div');
        anggotaItem.className = 'anggota-item';
        anggotaItem.innerHTML = `
          <div class="anggota-header">
            <span class="anggota-title"><i class="fas fa-user"></i> Anggota ${anggotaCount}</span>
            ${anggotaCount > 1 ? '<button type="button" class="remove-anggota"><i class="fas fa-times"></i> Hapus</button>' : ''}
          </div>
          <div class="anggota-fields">
            <div class="form-group">
              <label for="anggota_nama_${anggotaCount}">Nama Lengkap</label>
              <input type="text" id="anggota_nama_${anggotaCount}" name="anggota_nama[]" required placeholder="Nama lengkap anggota">
            </div>
            <div class="form-group">
              <label for="anggota_nim_${anggotaCount}">NIM</label>
              <input type="number" id="anggota_nim_${anggotaCount}" name="anggota_nim[]" required placeholder="NIM anggota">
            </div>
            <div class="form-group">
              <label for="anggota_prodi_${anggotaCount}">Program Studi</label>
              <input type="text" id="anggota_prodi_${anggotaCount}" name="anggota_prodi[]" required placeholder="Program studi anggota">
            </div>
            <div class="form-group">
              <label for="anggota_posisi_${anggotaCount}">Tahun Angkatan</label>
              <select id="anggota_posisi_${anggotaCount}" name="anggota_posisi[]" required>
                ${getPosisiOptions(currentLomba)}
              </select>
            </div>
          </div>
        `;

        anggotaContainer.appendChild(anggotaItem);

        // Tambah event listener untuk tombol hapus
        const removeBtn = anggotaItem.querySelector('.remove-anggota');
        if (removeBtn) {
          removeBtn.addEventListener('click', function () {
            hapusAnggota(anggotaItem);
          });
        }

        updateTambahButton();
      }

      // Fungsi untuk mendapatkan opsi posisi berdasarkan lomba
     // Fungsi untuk mendapatkan opsi tahun angkatan
function getPosisiOptions(lomba) {
  // KINI MENJADI TAHUN ANGKATAN untuk semua lomba
  return `
    <option value="2022">2022</option>
    <option value="2023">2023</option>
    <option value="2024">2024</option>
    <option value="2025">2025</option>
    <option value="2026">2026</option>
  `;
}

      // Fungsi untuk menghapus anggota
      function hapusAnggota(anggotaItem) {
        anggotaItem.remove();
        anggotaCount--;
        updateCounter();
        updateAnggotaNumbers();
        updateTambahButton();
      }

      // Fungsi untuk memperbarui nomor anggota
      function updateAnggotaNumbers() {
        const anggotaItems = anggotaContainer.querySelectorAll('.anggota-item');
        anggotaItems.forEach((item, index) => {
          const title = item.querySelector('.anggota-title');
          title.innerHTML = `<i class="fas fa-user"></i> Anggota ${index + 1}`;

          // Update input IDs dan names
          const inputs = item.querySelectorAll('input, select');
          inputs.forEach(input => {
            const baseName = input.name.replace(/\[\]$/, '');
            input.name = `${baseName}[]`;
            input.id = `${baseName}_${index + 1}`;
          });
        });
      }

      // Fungsi untuk memperbarui counter
      function updateCounter() {
        anggotaCountSpan.textContent = anggotaCount;
        if (anggotaCount >= maxAnggota) {
          anggotaCountSpan.classList.add('max-warning');
        } else {
          anggotaCountSpan.classList.remove('max-warning');
        }
      }

      // Fungsi untuk update status tombol tambah
      function updateTambahButton() {
        if (anggotaCount >= maxAnggota) {
          tambahAnggotaBtn.disabled = true;
          tambahAnggotaBtn.style.background = 'linear-gradient(135deg, #6c757d, #5a6268)';
          tambahAnggotaBtn.innerHTML = '<i class="fas fa-ban"></i> Maksimal Anggota';
        } else {
          tambahAnggotaBtn.disabled = false;
          tambahAnggotaBtn.style.background = '';
          tambahAnggotaBtn.innerHTML = '<i class="fas fa-plus"></i> Tambah Anggota';
        }
      }

      // Event listener untuk tombol tambah anggota
      tambahAnggotaBtn.addEventListener('click', tambahAnggota);

      // ================================================
      // BAGIAN SUBMIT YANG BARU - GANTI DARI SINI KE BAWAH
      // ================================================
      
      // Event listener untuk tombol Daftar
      const btnDaftar = document.querySelector('#formPendaftaran button[type="submit"]');
      
      if (btnDaftar) {
        btnDaftar.addEventListener('click', function(e) {
          e.preventDefault();
          console.log("Tombol Daftar diklik!");
          
          // 1. Validasi lomba dipilih
          if (!currentLomba) {
            alert('❌ Pilih jenis lomba terlebih dahulu!');
            return;
          }
          
          // 2. Pastikan input hidden lomba terisi
          document.getElementById('lomba').value = currentLomba;
          
          // 3. Validasi tahun angkatan ketua
          const tahunKetua = document.getElementById('tahun');
          if (!tahunKetua || !tahunKetua.value) {
            alert('❌ Pilih tahun angkatan ketua tim!');
            tahunKetua?.focus();
            return;
          }
          
          // 4. Validasi minimal anggota
          const minAnggota = currentLomba === 'Badminton' ? 2 : 1;
          if (anggotaCount < minAnggota) {
            alert(`❌ Untuk lomba ${currentLomba}, minimal ${minAnggota} anggota tim!`);
            return;
          }
          
          // 5. Validasi semua input required
          const requiredInputs = document.querySelectorAll('#formPendaftaran input[required], #formPendaftaran select[required]');
          let semuaValid = true;
          let firstInvalid = null;
          
          requiredInputs.forEach(input => {
            if (!input.value.trim()) {
              semuaValid = false;
              input.style.borderColor = 'red';
              
              if (!firstInvalid) {
                firstInvalid = input;
              }
            }
          });
          
          if (!semuaValid) {
            alert('❌ Harap isi semua data yang diperlukan!');
            if (firstInvalid) {
              firstInvalid.focus();
            }
            return;
          }
          
          // 6. Tampilkan loading
          const originalText = btnDaftar.innerHTML;
          btnDaftar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
          btnDaftar.disabled = true;
          
          // 7. Submit form secara AJAX
          submitFormAJAX(btnDaftar, originalText);
        });
      }
      
      // Fungsi untuk submit form via AJAX
      function submitFormAJAX(submitBtn, originalBtnText) {
        // Buat FormData dari form
        const form = document.getElementById('formPendaftaran');
        const formData = new FormData(form);
        
        // Pastikan jenis lomba terkirim
        formData.append('jenis_lomba', currentLomba);
        
        // Debug: lihat data yang akan dikirim
        console.log('Data yang akan dikirim:');
        for (let [key, value] of formData.entries()) {
          console.log(`${key}: ${value}`);
        }
        
        // Kirim via AJAX
        fetch('proses_daftar.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.text();
        })
        .then(responseText => {
          console.log('Response dari server:', responseText);
          
          // Coba parse JSON jika response adalah JSON
          try {
            const data = JSON.parse(responseText);
            
            if (data.success) {
              // Jika menggunakan JSON response
              alert(`✅ Pendaftaran Berhasil!\n\nID Tim: ${data.id_tim}\nNama Tim: ${data.nama_tim}\nStatus: Menunggu Verifikasi`);
              
              // Redirect ke konfirmasi
              setTimeout(() => {
                window.location.href = 'konfirmasi.php?id=' + data.id_tim;
              }, 1500);
              
            } else {
              throw new Error(data.message || 'Gagal mendaftar');
            }
          } catch (e) {
            // Jika response bukan JSON (mungkin langsung redirect)
            console.log('Response bukan JSON, kemungkinan langsung redirect');
            
            // Tampilkan pesan sukses
            alert('✅ Pendaftaran berhasil! Data sedang diproses...');
            
            // Submit form normal untuk redirect
            setTimeout(() => {
              form.submit();
            }, 1000);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('❌ Gagal mendaftar: ' + error.message);
          
          // Reset tombol
          submitBtn.innerHTML = originalBtnText;
          submitBtn.disabled = false;
        });
      }
      
      // Backup: Jika AJAX gagal, form bisa submit normal
      document.getElementById('formPendaftaran').addEventListener('submit', function(e) {
        // Hanya prevent default jika tombol diklik via event listener di atas
        // Biarkan form submit normal jika tidak
      });
      
    });
  </script>
  <!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
# 🌐 EVENT KAMPUS WEBSITE - PHP NATIVE
Website informasi dan pendaftaran event kampus dengan sistem admin panel lengkap.

## 🎯 FITUR UTAMA
1. Pendaftaran Event (Individu & Tim)
2. Upload Bukti Pembayaran
3. Verifikasi Pembayaran oleh Admin
4. Multi-level Admin (Superadmin, Admin, Panitia)
5. Laporan dan Statistik
6. Responsive Design (Bootstrap 5)

## 🚀 CARA INSTALASI LENGKAP

### 📌 PERSIAPAN AWAL
1. **Install XAMPP atau Laragon** 
2. **Jalankan** → Start Apache & MySQL
3. **Download project** dari GitHub (Clone atau Download ZIP)

### 📂 LANGKAH 1: LETAKKAN FOLDER
Copy folder `kelompok_web_event` ke: C:\xampp\htdocs\event-kampus\ (XAMPP) / c:/Laragon/www/event-kampus


### 🗄️ LANGKAH 2: BUAT DATABASE
1. Buka browser, akses: **http://localhost/phpmyadmin**
2. Klik **New** di sidebar kiri
3. Buat database dengan nama: `event_kampus`
4. Klik **Create**

### 📥 LANGKAH 3: IMPORT DATABASE
1. Di phpMyAdmin, pilih database `event_kampus`
2. Klik tab **Import**
3. Klik tombol **Choose File**
4. Navigasi ke folder: `C:\xampp\htdocs\event-kampus\sql\`
5. Pilih file: `01_structure.sql`
6. Klik **Go** di bagian bawah

### ⚙️ LANGKAH 4: KONFIGURASI KONEKSI
1. Buka folder: `C:\xampp\htdocs\event-kampus\`
2. **Copy** file `koneksi.php.example`
3. **Rename** copy-an menjadi `koneksi.php`
4. **Edit** file `koneksi.php` dengan text editor (Notepad++/VSCode)
5. Pastikan kode seperti ini:

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "event_kampus";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>


### 📁 LANGKAH 5: BUAT FOLDER UPLOADS
Di folder C:\xampp\htdocs\event-kampus\
1. Buat folder baru bernama uploads
2. Di dalam folder uploads, buat folder: events, berita ,bukti_pembayaran

### 🌐 LANGKAH 6: AKSES WEBSITE
http://localhost/event-kampus/



##🔐 LOGIN UNTUK TESTING
##👤 LOGIN ADMIN PANEL
Buka: http://localhost/event-kampus/admin/

Gunakan salah satu akun:

PERAN	     USERNAME	PASSWORD	  HAK AKSES
SuperAdmin	 superadmin	password123	  Full access
Admin Biasa	 admin	    password123	  Manage events
Panitia	     panitia	password123	  Verifikasi pembayaran


##👥 REGISTRASI USER BIASA
Buka homepage: http://localhost/event-kampus/
1. Klik menu Events
2. Pilih event yang ingin diikuti
3. Klik Daftar Sekarang
4. Isi form pendaftaran
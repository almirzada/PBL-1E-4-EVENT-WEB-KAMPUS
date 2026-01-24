-- phpMyAdmin SQL Dump
-- version 5.2.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `event_kampus`
--

CREATE DATABASE IF NOT EXISTS `event_kampus`;
USE `event_kampus`;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `level` enum('superadmin','admin','panitia') COLLATE utf8mb4_unicode_520_ci DEFAULT 'panitia',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_520_ci DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_event`
--

CREATE TABLE `admin_event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT 'Administrator',
  `email` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `alasan_daftar` text COLLATE utf8mb4_unicode_520_ci,
  `level` enum('superadmin','admin','panitia') COLLATE utf8mb4_unicode_520_ci DEFAULT 'panitia',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_520_ci DEFAULT 'inactive',
  `foto` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `berita`
--

CREATE TABLE `berita` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `konten` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `excerpt` varchar(300) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `kategori_berita` enum('informasi','pengumuman','beasiswa','akademik','kemahasiswaan') COLLATE utf8mb4_unicode_520_ci DEFAULT 'informasi',
  `status` enum('draft','publik') COLLATE utf8mb4_unicode_520_ci DEFAULT 'draft',
  `views` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `deskripsi_singkat` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `kategori_id` int DEFAULT NULL,
  `tanggal` date NOT NULL,
  `batas_pendaftaran` date DEFAULT NULL,
  `waktu` time DEFAULT NULL,
  `lokasi` varchar(200) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `alamat_lengkap` text COLLATE utf8mb4_unicode_520_ci,
  `poster` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `kuota_peserta` int DEFAULT '0',
  `total_pendaftar` int NOT NULL DEFAULT '0',
  `biaya_pendaftaran` decimal(10,2) DEFAULT '0.00',
  `link_pendaftaran` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `contact_wa` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` enum('draft','publik','selesai','batal') COLLATE utf8mb4_unicode_520_ci DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tipe_pendaftaran` enum('individu','tim') COLLATE utf8mb4_unicode_520_ci DEFAULT 'individu',
  `min_anggota` int DEFAULT '1',
  `max_anggota` int DEFAULT '1',
  `perlu_pendaftaran` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `warna` varchar(7) COLLATE utf8mb4_unicode_520_ci DEFAULT '#0056b3',
  `ikon` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'fas fa-calendar',
  `deskripsi` text COLLATE utf8mb4_unicode_520_ci,
  `urutan` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta`
--

CREATE TABLE `peserta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `tim_id` int DEFAULT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `npm` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `jurusan` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `kode_pendaftaran` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `bukti_pembayaran` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status_pembayaran` enum('menunggu_verifikasi','terverifikasi','ditolak','gratis') COLLATE utf8mb4_unicode_520_ci DEFAULT 'menunggu_verifikasi',
  `status_anggota` enum('ketua','anggota','individu') COLLATE utf8mb4_unicode_520_ci DEFAULT 'individu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_tim_id` (`tim_id`),
  KEY `idx_kode` (`kode_pendaftaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_520_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tim_event`
--

CREATE TABLE `tim_event` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `nama_tim` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `kode_pendaftaran` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `jumlah_anggota` int DEFAULT '1',
  `bukti_pembayaran` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status_pembayaran` enum('menunggu_verifikasi','terverifikasi','ditolak','gratis') COLLATE utf8mb4_unicode_520_ci DEFAULT 'menunggu_verifikasi',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_pendaftaran` (`kode_pendaftaran`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Struktur untuk view `laporan_pendaftaran`
--
CREATE VIEW `laporan_pendaftaran` AS
SELECT 
    e.id AS event_id,
    e.judul AS judul,
    e.tanggal AS tanggal,
    e.biaya_pendaftaran AS biaya_pendaftaran,
    e.tipe_pendaftaran AS tipe_pendaftaran,
    COUNT(DISTINCT CASE 
        WHEN e.tipe_pendaftaran = 'tim' THEN p.tim_id 
        ELSE p.id 
    END) AS total_pendaftar,
    SUM(CASE 
        WHEN e.tipe_pendaftaran = 'tim' AND t.status_pembayaran = 'terverifikasi' THEN 1
        WHEN e.tipe_pendaftaran != 'tim' AND p.status_pembayaran = 'terverifikasi' THEN 1
        ELSE 0 
    END) AS terverifikasi,
    SUM(CASE 
        WHEN e.tipe_pendaftaran = 'tim' AND t.status_pembayaran = 'menunggu_verifikasi' THEN 1
        WHEN e.tipe_pendaftaran != 'tim' AND p.status_pembayaran = 'menunggu_verifikasi' THEN 1
        ELSE 0 
    END) AS menunggu_verifikasi,
    SUM(e.biaya_pendaftaran) AS total_pendapatan_potensial
FROM events e
LEFT JOIN peserta p ON e.id = p.event_id
LEFT JOIN tim_event t ON p.tim_id = t.id
GROUP BY e.id, e.judul, e.tanggal, e.biaya_pendaftaran, e.tipe_pendaftaran
ORDER BY e.tanggal DESC;

-- --------------------------------------------------------

--
-- Struktur untuk view `pembayaran_menunggu_verifikasi`
--
CREATE VIEW `pembayaran_menunggu_verifikasi` AS
SELECT 
    'individu' AS tipe,
    p.id AS id,
    p.nama AS nama,
    p.npm AS npm,
    p.email AS email,
    p.kode_pendaftaran AS kode_pendaftaran,
    p.bukti_pembayaran AS bukti_pembayaran,
    p.status_pembayaran AS status_pembayaran,
    p.created_at AS created_at,
    e.judul AS event_judul,
    e.biaya_pendaftaran AS biaya_pendaftaran,
    NULL AS nama_tim
FROM peserta p
JOIN events e ON p.event_id = e.id
WHERE p.status_pembayaran = 'menunggu_verifikasi'
    AND e.biaya_pendaftaran > 0
    AND p.tim_id IS NULL

UNION ALL

SELECT 
    'tim' AS tipe,
    NULL AS id,
    NULL AS nama,
    NULL AS npm,
    NULL AS email,
    t.kode_pendaftaran AS kode_pendaftaran,
    t.bukti_pembayaran AS bukti_pembayaran,
    t.status_pembayaran AS status_pembayaran,
    t.created_at AS created_at,
    e.judul AS event_judul,
    e.biaya_pendaftaran AS biaya_pendaftaran,
    t.nama_tim AS nama_tim
FROM tim_event t
JOIN events e ON t.event_id = e.id
WHERE t.status_pembayaran = 'menunggu_verifikasi'
    AND e.biaya_pendaftaran > 0;

-- --------------------------------------------------------

--
-- Triggers `peserta`
--
DELIMITER $$
CREATE TRIGGER `after_peserta_insert` AFTER INSERT ON `peserta` FOR EACH ROW BEGIN
    DECLARE v_total INT;
    
    SELECT COUNT(DISTINCT CASE 
        WHEN e.tipe_pendaftaran = 'tim' THEN p.tim_id 
        ELSE p.id 
    END) INTO v_total
    FROM peserta p
    JOIN events e ON p.event_id = e.id
    WHERE p.event_id = NEW.event_id;
    
    UPDATE events 
    SET total_pendaftar = v_total
    WHERE id = NEW.event_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  ADD CONSTRAINT `log_pembayaran_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `log_pembayaran_ibfk_2` FOREIGN KEY (`tim_id`) REFERENCES `tim_event` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `log_pembayaran_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `log_verifikasi`
--
ALTER TABLE `log_verifikasi`
  ADD CONSTRAINT `log_verifikasi_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_verifikasi_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin_event` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `peserta`
--
ALTER TABLE `peserta`
  ADD CONSTRAINT `peserta_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peserta_ibfk_2` FOREIGN KEY (`tim_id`) REFERENCES `tim_event` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tim_event`
--
ALTER TABLE `tim_event`
  ADD CONSTRAINT `tim_event_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 21 Jan 2026 pada 12.08
-- Versi server: 8.4.3
-- Versi PHP: 8.3.16

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

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_registration_report` (IN `p_start_date` DATE, IN `p_end_date` DATE)   BEGIN
    SELECT 
        e.judul AS event_name,
        e.tanggal AS event_date,
        e.biaya_pendaftaran AS fee,
        COUNT(DISTINCT CASE WHEN e.tipe_pendaftaran = 'tim' THEN p.tim_id ELSE p.id END) AS total_registrations,
        SUM(CASE WHEN p.status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END) AS verified_payments,
        SUM(CASE WHEN p.status_pembayaran = 'menunggu_verifikasi' THEN 1 ELSE 0 END) AS pending_payments,
        SUM(e.biaya_pendaftaran * 
            CASE WHEN e.tipe_pendaftaran = 'tim' THEN 
                (CASE WHEN t.status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END)
            ELSE
                (CASE WHEN p.status_pembayaran = 'terverifikasi' THEN 1 ELSE 0 END)
            END) AS total_verified_revenue
    FROM events e
    LEFT JOIN peserta p ON e.id = p.event_id
    LEFT JOIN tim_event t ON p.tim_id = t.id
    WHERE DATE(p.created_at) BETWEEN p_start_date AND p_end_date
       OR DATE(t.created_at) BETWEEN p_start_date AND p_end_date
    GROUP BY e.id, e.judul, e.tanggal, e.biaya_pendaftaran
    ORDER BY e.tanggal DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `verifikasi_pembayaran` (IN `p_id` INT, IN `p_tipe` VARCHAR(10), IN `p_admin_id` INT, IN `p_status_baru` VARCHAR(20), IN `p_catatan` TEXT)   BEGIN
    DECLARE v_peserta_id INT;
    DECLARE v_tim_id INT;
    DECLARE v_status_sebelum VARCHAR(20);
    
    IF p_tipe = 'individu' THEN
        -- Get current status
        SELECT id, status_pembayaran INTO v_peserta_id, v_status_sebelum
        FROM peserta WHERE id = p_id;
        
        -- Update status
        UPDATE peserta 
        SET status_pembayaran = p_status_baru,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_id;
        
        -- Insert log
        INSERT INTO log_pembayaran (peserta_id, admin_id, status_sebelum, status_sesudah, catatan)
        VALUES (v_peserta_id, p_admin_id, v_status_sebelum, p_status_baru, p_catatan);
        
    ELSEIF p_tipe = 'tim' THEN
        -- Get current status
        SELECT id, status_pembayaran INTO v_tim_id, v_status_sebelum
        FROM tim_event WHERE id = p_id;
        
        -- Update team status
        UPDATE tim_event 
        SET status_pembayaran = p_status_baru,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = p_id;
        
        -- Also update all team members
        UPDATE peserta 
        SET status_pembayaran = p_status_baru,
            updated_at = CURRENT_TIMESTAMP
        WHERE tim_id = p_id;
        
        -- Insert log
        INSERT INTO log_pembayaran (tim_id, admin_id, status_sebelum, status_sesudah, catatan)
        VALUES (v_tim_id, p_admin_id, v_status_sebelum, p_status_baru, p_catatan);
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `level` enum('superadmin','admin','panitia') COLLATE utf8mb4_unicode_520_ci DEFAULT 'panitia',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_520_ci DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama_lengkap`, `email`, `level`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', '$2y$10$YourHashedPasswordHere', 'Super Admin', 'superadmin@eventkampus.ac.id', 'superadmin', 'active', NULL, '2026-01-09 12:15:29', '2026-01-09 12:15:29'),
(2, 'admin', '$2y$10$YourHashedPasswordHere', 'Admin Biasa', 'admin@eventkampus.ac.id', 'admin', 'active', NULL, '2026-01-09 12:15:29', '2026-01-09 12:15:29'),
(3, 'panitia1', '$2y$10$YourHashedPasswordHere', 'Panitia Event', 'panitia@eventkampus.ac.id', 'panitia', 'active', NULL, '2026-01-09 12:15:29', '2026-01-09 12:15:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_event`
--

CREATE TABLE `admin_event` (
  `id` int NOT NULL,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `admin_event`
--

INSERT INTO `admin_event` (`id`, `username`, `password`, `nama_lengkap`, `nama`, `email`, `no_wa`, `alasan_daftar`, `level`, `status`, `foto`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$uNCTb/c0Ao35th6i33bpQu1M.4JHv1JNNqXGDtuuMhFjnZQw/JkQq', '', 'Admin Utama', NULL, NULL, NULL, 'superadmin', 'active', NULL, NULL, '2026-01-08 02:01:55'),
(4, 'superadmin', '$2y$10$GS.ei2qDyQ7iwhvuUzxiLe8HMCBfbEA8vzkFJHocYD9OVbEyV1GqO', 'Super Admin', 'Super Admin', 'super@admin.com', '081234567890', NULL, 'superadmin', 'active', NULL, '2026-01-15 20:37:51', '2026-01-15 08:00:45'),
(6, 'rafie', '$2y$10$KlKdsC8SrQsJqG/s4wbnmuI4RwZpFLUxXUlcaWdPdrCabV5VtrN72', 'rafi almirzada', 'rafi almirzada', 'rafi@gmail.com', '022564164', NULL, 'panitia', 'active', NULL, '2026-01-15 16:33:23', '2026-01-15 09:28:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_event_backup_2024`
--

CREATE TABLE `admin_event_backup_2024` (
  `id` int NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT 'Administrator',
  `email` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `no_wa` varchar(20) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `alasan_daftar` text COLLATE utf8mb4_unicode_520_ci,
  `level` enum('superadmin','admin') COLLATE utf8mb4_unicode_520_ci DEFAULT 'admin',
  `foto` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `admin_event_backup_2024`
--

INSERT INTO `admin_event_backup_2024` (`id`, `username`, `password`, `nama_lengkap`, `nama`, `email`, `no_wa`, `alasan_daftar`, `level`, `foto`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', 'Admin Utama', NULL, NULL, NULL, 'superadmin', NULL, NULL, '2026-01-08 02:01:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `account_name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `branch` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `bank_name`, `account_number`, `account_name`, `branch`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(2, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(3, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(4, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:20:11', '2026-01-09 12:20:11'),
(5, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:20:11', '2026-01-09 12:20:11'),
(6, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:20:11', '2026-01-09 12:20:11'),
(7, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:20:44', '2026-01-09 12:20:44'),
(8, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:20:44', '2026-01-09 12:20:44'),
(9, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:20:44', '2026-01-09 12:20:44'),
(10, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:20:54', '2026-01-09 12:20:54'),
(11, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:20:54', '2026-01-09 12:20:54'),
(12, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:20:54', '2026-01-09 12:20:54'),
(13, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:21:35', '2026-01-09 12:21:35'),
(14, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:21:35', '2026-01-09 12:21:35'),
(15, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:21:35', '2026-01-09 12:21:35'),
(16, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:21:58', '2026-01-09 12:21:58'),
(17, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:21:58', '2026-01-09 12:21:58'),
(18, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:21:58', '2026-01-09 12:21:58'),
(19, 'Bank Mandiri', '1234567890', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:22:14', '2026-01-09 12:22:14'),
(20, 'Bank BCA', '0987654321', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:22:14', '2026-01-09 12:22:14'),
(21, 'Bank BRI', '1122334455', 'Panitia Event Kampus', 'Kantor Cabang Pusat', 1, '2026-01-09 12:22:14', '2026-01-09 12:22:14'),
(22, 'Bank Mandiri', '1234-5678-9012-3456', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:25:48', '2026-01-09 12:25:48'),
(23, 'Bank BCA', '9876-5432-1098-7654', 'Panitia Event Kampus', 'Kantor Cabang Utama', 1, '2026-01-09 12:25:48', '2026-01-09 12:25:48');

-- --------------------------------------------------------

--
-- Struktur dari tabel `berita`
--

CREATE TABLE `berita` (
  `id` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `berita`
--

INSERT INTO `berita` (`id`, `judul`, `slug`, `konten`, `excerpt`, `gambar`, `kategori_berita`, `status`, `views`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Penerimaan Mahasiswa Baru 2025/2026 Dibuka', NULL, '<div class=\"OZ9ddf WAUd4\"><div id=\"_lNhoaYHpGpex4-EPg8zDmAg_44-header\" style=\"display:contents\"><div class=\"nk9vdc GYaNDc\" style=\"flex-grow:1\"><br></div></div></div><div data-ve-view=\"\" data-hveid=\"CC4QCw\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQ2b4KegQILhAL\"><div class=\"Pqkn2e rNSxBe\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQ274KegQILhAM\"><div class=\"jloFI GkDqAd\"><div></div><div data-hveid=\"CC4QDQ\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQ7uAMegQILhAN\"><div><div><div style=\"position:relative\"><div class=\"LT6XE\"><div class=\"pOOWX f5cPye\" data-rl=\"id\"><div><div class=\"WaaZC\">            <div class=\"rPeykc\" data-hveid=\"CEIQAQ\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQo_EKegQIQhAB\"><span data-huuid=\"13023643542164473757\"><span>Pendaftaran di Politeknik Negeri Batam (Polibatam) umumnya dilakukan secara online melalui portal registrasi di <a class=\"uVhVib\" href=\"https://registrasi.polibatam.ac.id/\" data-sb=\"/url?sa=t&amp;source=web&amp;rct=j&amp;opi=89978449&amp;url=https://registrasi.polibatam.ac.id/&amp;ved=2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQjJEMegQIMxAB&amp;usg=AOvVaw1WaotEtIYotdD9F7VEdURd\">registrasi.polibatam.ac.id</a>,\r\n dengan berbagai jalur masuk seperti Ujian Masuk (UMPB), SNBP/SNBT, dan \r\njalur mandiri lainnya, syaratnya mencakup kelulusan SMA/SMK, kesehatan \r\njasmani-rohani, dan NISN, serta ada biaya pendaftaran, jadi cek langsung\r\n laman resminya untuk jadwal dan detail terbaru.</span><span>&nbsp;</span></span><span data-huuid=\"13023643542164473779\"><span class=\"pjBG2e\" data-cid=\"aa9da45e-19dc-4034-a1ca-6fe5138d2914\"><span class=\"UV3uM\">&nbsp;</span></span></span></div> </div><div class=\"WaaZC\">            <div class=\"rPeykc uP58nb\" data-hveid=\"CC8QAQ\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQo_EKegQILxAB\"> <span data-huuid=\"13023643542164473801\"><span aria-level=\"2\" role=\"heading\">Cara Mendaftar di Polibatam:</span><span> </span></span>  </div> </div><div class=\"WaaZC\"><ol data-hveid=\"CEUQAQ\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQnPYKegQIRRAB\"><li value=\"1\"><span data-huuid=\"13023643542164473834\"><span><strong>Kunjungi Situs Resmi</strong>:</span><span> </span></span><span data-huuid=\"13023643542164473845\"><span>Buka portal PMB Polibatam: <a class=\"uVhVib\" href=\"https://registrasi.polibatam.ac.id/jalur-masuk/umpb/\" data-sb=\"/url?sa=t&amp;source=web&amp;rct=j&amp;opi=89978449&amp;url=https://registrasi.polibatam.ac.id/jalur-masuk/umpb/&amp;ved=2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQjJEMegQIQRAB&amp;usg=AOvVaw2GoCiq80cC3F6zHi4IEIyD\">registrasi.polibatam.ac.id</a>.</span><span class=\"pjBG2e\" data-cid=\"7b9a02e7-949d-4448-bebc-d0d872cdf11a\"><span class=\"UV3uM\">&nbsp;</span></span></span></li><li value=\"2\"><span data-huuid=\"13023643542164473867\"><span><strong>Pilih Jalur Masuk</strong>:</span><span> </span></span><span data-huuid=\"13023643542164473878\"><span>Tentukan jalur yang tersedia, seperti Ujian Masuk (UMPB) untuk Mandiri.</span><span class=\"pjBG2e\" data-cid=\"0e0e7487-46d5-4d82-a776-35253dc55206\"><span class=\"UV3uM\">&nbsp;</span></span></span></li><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__83\" role=\"none\"><li value=\"3\"><span data-huuid=\"13023643542164473900\"><span><strong>Penuhi Persyaratan</strong>:</span><span> </span></span><span data-huuid=\"13023643542164473911\"><span>Siapkan dokumen seperti NISN, KTP, KK, dan pastikan sehat.</span><span class=\"pjBG2e\" data-cid=\"5ef6ae8b-f14a-42f4-8af0-e277acacda46\"><span class=\"UV3uM\">&nbsp;</span></span></span></li></div><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__87\" role=\"none\"><li value=\"4\"><span data-huuid=\"13023643542164473933\"><span><strong>Daftar Online</strong>:</span><span> </span></span><span data-huuid=\"13023643542164473944\"><span>Ikuti proses pendaftaran di portal dan lakukan pembayaran biaya pendaftaran (misal, Rp200.000 untuk UMPB).</span><span class=\"pjBG2e\" data-cid=\"0cc3eed0-e979-469b-9b27-e4c18aea11f7\"><span class=\"UV3uM\">&nbsp;</span></span></span></li></div><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__91\" role=\"none\"><li value=\"5\"><span data-huuid=\"13023643542164473966\"><span><strong>Ikuti Ujian/Seleksi</strong>:</span><span> </span></span><span data-huuid=\"13023643542164473977\"><span>Ikuti jadwal ujian atau seleksi sesuai jalur yang dipilih.</span><span class=\"pjBG2e\" data-cid=\"feff811d-477b-4331-94c6-501015514192\"><span class=\"UV3uM\">&nbsp;</span></span></span></li></div></ol></div><div class=\"WaaZC\"><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__88\" role=\"none\">            <div class=\"rPeykc uP58nb\" data-hveid=\"CIoBEAE\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQo_EKegUIigEQAQ\"> <span data-huuid=\"13023643542164473999\"><span aria-level=\"2\" role=\"heading\">Jalur Masuk yang Tersedia (Contoh):</span><span class=\"pjBG2e\" data-cid=\"829436a7-df06-4737-884c-441d738d9833\"><span class=\"UV3uM\">&nbsp;</span></span></span></div> </div></div><div class=\"WaaZC\"><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__51\" role=\"none\"><ul data-hveid=\"CHsQAQ\" data-ved=\"2ahUKEwiBg_yVwY2SAxWX2DgGHQPmEIMQm_YKegQIexAB\"><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__53\" role=\"none\"><li><span data-huuid=\"13023643542164474032\"><span>SNBP (Seleksi Nasional Berdasarkan Prestasi)</span><span> </span></span></li></div><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__58\" role=\"none\"><li><span data-huuid=\"13023643542164474054\"><span>SNBT (Seleksi Nasional Berdasarkan Tes)</span><span> </span></span></li></div><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__60\" role=\"none\"><li><span data-huuid=\"13023643542164474076\"><span>UMPB (Ujian Masuk Polibatam) / Mandiri</span><span> </span></span></li></div><div class=\"bsmXxe\" id=\"lNhoaYHpGpex4-EPg8zDmAg__62\" role=\"none\"><li><span data-huuid=\"13023643542164474098\"><span>PMDK, Tahfiz, Afirmasi, RPL, D2 Jalur Cepat (Informasi lebih detail di situsnya).</span></span></li></div></ul></div></div></div></div></div></div></div></div></div></div></div></div>.', 'Pendaftaran mahasiswa baru tahun akademik 2025/2026 telah dibuka.', 'uploads/berita/berita_1768479380_6968da94c2a6c.jpg', 'pengumuman', 'publik', 12, NULL, '2026-01-08 02:43:53', '2026-01-15 12:16:20'),
(3, 'Workshop Kewirausahaan untuk Mahasiswa', NULL, 'UKM Kewirausahaan akan mengadakan workshop kewirausahaan bagi mahasiswa yang ingin memulai bisnis.', 'Workshop kewirausahaan untuk mahasiswa.', NULL, 'kemahasiswaan', 'publik', 0, NULL, '2026-01-08 02:43:53', '2026-01-08 02:43:53'),
(4, 'Dorong Kolaborasi Kampus dan Industri Berbasis AI, NVIDIA Hadir di Batam dengan GeForce RTX 50 Series', NULL, '<p>Dalam upaya nyata mempercepat pengembangan talenta digital di Indonesia, raksasa teknologi NVIDIA resmi menyambangi Kota Batam. NVIDIA menggelar agenda bertajuk GeForce Press Briefing NVIDIA GeForce RTX 50 Series &amp; Holiday Campaign yang bertempat di Hall/Auditorium Politeknik Negeri Batam (Polibatam), Jumat (9/1/2026). Acara yang dirangkaikan dengan workshop mahasiswa ini menjadi panggung kolaborasi strategis antara industri teknologi global dan dunia pendidikan tinggi. Fokus utama diskusi ini adalah membedah peran vital kecerdasan buatan (Artificial Intelligence/AI), komputasi tinggi, dan grafis mutakhir dalam mencetak kompetensi talenta digital masa depan.<br><br>Artikel ini telah tayang di Batamnews.co.id dengan judul \"Dorong Kolaborasi Kampus dan Industri Berbasis AI, NVIDIA Hadir di Batam dengan GeForce RTX 50 Series\", Klik untuk baca: https://batamnews.co.id/berita-124837-dorong-kolaborasi-kampus-dan-industri-berbasis-ai-nvidia-hadir-di-batam-dengan-geforce-rtx-50-series.html</p>', '...', 'uploads/berita/berita_1768478831_6968d86fc1a2e.jpg', 'akademik', 'publik', 7, NULL, '2026-01-09 10:42:41', '2026-01-15 12:10:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `events`
--

CREATE TABLE `events` (
  `id` int NOT NULL,
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
  `total_pendaftar` int NOT NULL,
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
  `perlu_pendaftaran` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `events`
--

INSERT INTO `events` (`id`, `judul`, `slug`, `deskripsi`, `deskripsi_singkat`, `kategori_id`, `tanggal`, `batas_pendaftaran`, `waktu`, `lokasi`, `alamat_lengkap`, `poster`, `kuota_peserta`, `total_pendaftar`, `biaya_pendaftaran`, `link_pendaftaran`, `contact_person`, `contact_wa`, `status`, `featured`, `views`, `created_by`, `created_at`, `updated_at`, `tipe_pendaftaran`, `min_anggota`, `max_anggota`, `perlu_pendaftaran`) VALUES
(1, 'Lomba manicng', 'lomba-manicng', '<p>mantap mantiiiinggg</p>', 'Acara Lomba manicng akan diselenggarakan di kampus. Jangan lewatkan kesempatan ini!', 2, '2026-01-09', NULL, '21:22:00', 'Politeknik negeri batam', 'Politeknik', 'uploads/events/event_1767841709_695f1fad2399b.png', 32, 0, 50000.00, NULL, 'amadeo', '087813559019', 'publik', 0, 23, 1, '2026-01-08 03:08:29', '2026-01-15 11:04:49', 'individu', 1, 1, 1),
(2, 'riset apaya', 'riset-apaya', '<p data-start=\"82\" data-end=\"348\">Ini adalah <strong data-start=\"93\" data-end=\"113\">Turnamen E-Sport</strong> yang diselenggarakan oleh <strong data-start=\"140\" data-end=\"164\">Aldenaire &amp; Partners</strong>, bertempat di <strong data-start=\"179\" data-end=\"206\">Politeknik Batam Center</strong>, dan berlangsung selama <strong data-start=\"231\" data-end=\"253\">20–25 Januari 2025</strong>. Target acaranya jelas: kompetitif, serius, dan berorientasi prestasi, bukan sekadar fun game.</p>\r\n<p data-start=\"350\" data-end=\"704\">Turnamen ini dibuka untuk tim yang siap bersaing dengan <strong data-start=\"406\" data-end=\"445\">biaya pendaftaran Rp100.000 per tim</strong>, angka yang realistis dan terjangkau untuk skala kampus tapi tetap memfilter peserta yang niat. Total hadiah <strong data-start=\"555\" data-end=\"570\">Rp5.000.000</strong>, ditambah <strong data-start=\"581\" data-end=\"591\">trophy</strong> dan <strong data-start=\"596\" data-end=\"619\">fasilitas Free WiFi</strong>, menandakan event ini disiapkan dengan standar profesional, bukan acara asal-asalan.</p><p></p>', 'Turnamen esport', 19, '2026-01-26', '2026-01-24', '13:00:00', 'centre park', 'centre park', 'uploads/events/event_1768479249_6968da115ec6d.jpg', 32, 0, 50000.00, NULL, 'reyavndto', '087813559019', 'publik', 1, 19, 1, '2026-01-08 03:20:37', '2026-01-15 12:14:09', 'tim', 5, 6, 1),
(8, 'gfgfgfg', 'gfgfgfg', '<p>fdfdfdfdfdfdff</p>', 'Acara gfgfgfg akan diselenggarakan di kampus. Jangan lewatkan kesempatan ini untuk belajar dan berjejaring!', 36, '2026-01-22', NULL, NULL, '6666', 'ffffff', 'uploads/events/event_1768391692_6967840c9815a.jpg', 0, 0, 0.00, NULL, 'vfvfvfvf', 'ff66666666', 'draft', 1, 0, 1, '2026-01-14 11:54:52', '2026-01-14 11:54:52', 'individu', 1, 1, 1),
(11, 'LOMBA BADMINTON ANTAR MAHASISWA', 'lomba-badminton-antar-mahasiswa', 'MERDEKAAAAAAA', 'Acara lomba akan diselenggarakan di kampus. Jangan lewatkan kesempatan ini untuk belajar dan berjejaring!', 2, '2026-01-26', '2026-01-19', NULL, 'Gor Garuda', 'Ikan daun', 'uploads/events/event_1768393554_69678b5279d3c.jpg', 0, 3, 600000.00, NULL, 'Reyvadnito', '087813559', 'publik', 1, 92, 1, '2026-01-14 12:25:54', '2026-01-15 11:47:10', 'tim', 2, 12, 1),
(12, 'Konser music Polibatam', 'konser-music-polibatam', '<p data-start=\"976\" data-end=\"1061\">Acara ini diproyeksikan menjadi <strong data-start=\"1008\" data-end=\"1041\">festival musik tahunan kampus</strong> yang menggabungkan:</p>\r\n<ol data-start=\"1063\" data-end=\"2031\"><li data-start=\"1063\" data-end=\"1266\">\r\n<p data-start=\"1066\" data-end=\"1266\"><strong data-start=\"1066\" data-end=\"1114\">Penampilan Band Mahasiswa dan Komunitas Seni</strong><br data-start=\"1114\" data-end=\"1117\">\r\nGrup musik internal kampus dari berbagai jurusan tampil di panggung utama — fokus pada ekspresi kreatif mahasiswa, bukan sekadar hiburan ringan.</p>\r\n</li><li data-start=\"1267\" data-end=\"1667\">\r\n<p data-start=\"1270\" data-end=\"1667\"><strong data-start=\"1270\" data-end=\"1323\">Guest Performance / Bintang Tamu Lokal / Nasional</strong><br data-start=\"1323\" data-end=\"1326\">\r\nSama seperti <em data-start=\"1342\" data-end=\"1363\">Polibatam Fair 2025</em> yang menghadirkan hiburan dan kemungkinan bintang tamu di malam puncak, acara musik 2026 kemungkinan menampilkan artis tamu untuk menarik minat luas publik. Polibatam Fair sebelumnya punya <strong data-start=\"1553\" data-end=\"1614\">mini stage hiburan dan Malam Puncak dengan artis undangan</strong> yang meriah. <span class=\"\" data-state=\"closed\"></span></p>\r\n</li><li data-start=\"1668\" data-end=\"1849\">\r\n<p data-start=\"1671\" data-end=\"1849\"><strong data-start=\"1671\" data-end=\"1710\">Kolaborasi Seni &amp; Komunitas Kreatif</strong><br data-start=\"1710\" data-end=\"1713\">\r\nStand bazar UMKM mahasiswa, workshop musik, dan area komunitas kreatif bisa dibuka bersamaan untuk membangun engagement pengunjung.</p>\r\n</li><li data-start=\"1850\" data-end=\"2031\">\r\n<p data-start=\"1853\" data-end=\"2031\"><strong data-start=\"1853\" data-end=\"1888\">Atmosfer Kampus yang Interaktif</strong><br data-start=\"1888\" data-end=\"1891\">\r\nEvent musik jadi ruang sosial utama kampus, memperkuat kebersamaan sivitas akademika sambil memamerkan talenta bidang seni dan teknologi.</p>\r\n</li></ol>\r\n<h3 data-start=\"2033\" data-end=\"2054\">🎯 Tujuan Utama</h3>\r\n<ul data-start=\"2055\" data-end=\"2294\"><li data-start=\"2055\" data-end=\"2107\">\r\n<p data-start=\"2057\" data-end=\"2107\"><strong data-start=\"2057\" data-end=\"2105\">Menjadi wadah ekspresi bakat seni mahasiswa.</strong></p>\r\n</li><li data-start=\"2108\" data-end=\"2210\">\r\n<p data-start=\"2110\" data-end=\"2210\"><strong data-start=\"2110\" data-end=\"2165\">Menghubungkan mahasiswa dengan komunitas seni Batam</strong> (band lokal, seniman, dan penggiat musik).</p>\r\n</li><li data-start=\"2211\" data-end=\"2294\">\r\n<p data-start=\"2213\" data-end=\"2294\"><strong data-start=\"2213\" data-end=\"2294\">Memperkuat branding kampus sebagai ruang kreativitas, bukan sekadar akademik.</strong></p></li></ul><p><br></p>', 'ada tenxi ada travisscott ada billie eilish ada justin bieber', 6, '2026-01-22', NULL, NULL, 'Lapangan politeknik batam', ' Jl. Ahmad Yani, Tlk. Tering, Kec. Batam Kota, Kota Batam, Kepulauan Riau 29461', 'uploads/events/event_1768472574_6968bffe30756.jpg', 0, 0, 0.00, NULL, 'Rafie', '087813559019', 'publik', 1, 18, 4, '2026-01-15 10:21:52', '2026-01-15 11:04:25', 'individu', 1, 1, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `warna` varchar(7) COLLATE utf8mb4_unicode_520_ci DEFAULT '#0056b3',
  `ikon` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT 'fas fa-calendar',
  `deskripsi` text COLLATE utf8mb4_unicode_520_ci,
  `urutan` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `warna`, `ikon`, `deskripsi`, `urutan`, `created_at`) VALUES
(1, 'Seminar & Workshop', '#0056b3', 'fas fa-chalkboard-teacher', NULL, 0, '2026-01-08 02:01:55'),
(2, 'Lomba Olahraga', '#28a745', 'fas fa-running', NULL, 0, '2026-01-08 02:01:55'),
(3, 'Seni & Budaya', '#dc3545', 'fas fa-palette', NULL, 0, '2026-01-08 02:01:55'),
(4, 'Sosial & Kemahasiswaan', '#ffc107', 'fas fa-users', NULL, 0, '2026-01-08 02:01:55'),
(5, 'Akademik & Riset', '#17a2b8', 'fas fa-graduation-cap', NULL, 0, '2026-01-08 02:01:55'),
(6, 'Pameran & Expo', '#6f42c1', 'fas fa-bullhorn', NULL, 0, '2026-01-08 02:01:55'),
(19, 'Kompetisi', '#f72585', 'fas fa-calendar', 'Lomba atau kompetisi', 0, '2026-01-09 12:15:04'),
(36, 'Bootcamp', '#7209b7', 'fas fa-calendar', 'Pelatihan intensif', 0, '2026-01-09 12:18:06'),
(37, 'Webinar', '#4361ee', 'fas fa-calendar', 'Event berbentuk seminar online', 0, '2026-01-09 12:19:04');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `laporan_pendaftaran`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `laporan_pendaftaran` (
`biaya_pendaftaran` decimal(10,2)
,`event_id` int
,`judul` varchar(200)
,`menunggu_verifikasi` decimal(23,0)
,`tanggal` date
,`terverifikasi` decimal(23,0)
,`tipe_pendaftaran` enum('individu','tim')
,`total_pendaftar` bigint
,`total_pendapatan_potensial` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_pembayaran`
--

CREATE TABLE `log_pembayaran` (
  `id` int NOT NULL,
  `peserta_id` int DEFAULT NULL,
  `tim_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `status_sebelum` enum('menunggu_verifikasi','terverifikasi','ditolak','gratis') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status_sesudah` enum('menunggu_verifikasi','terverifikasi','ditolak','gratis') COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `log_pembayaran`
--

INSERT INTO `log_pembayaran` (`id`, `peserta_id`, `tim_id`, `admin_id`, `status_sebelum`, `status_sesudah`, `catatan`, `created_at`) VALUES
(1, 5, NULL, 1, NULL, 'terverifikasi', '', '2026-01-10 12:27:53'),
(2, NULL, NULL, 1, NULL, 'ditolak', '', '2026-01-10 12:28:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_verifikasi`
--

CREATE TABLE `log_verifikasi` (
  `id` int NOT NULL,
  `peserta_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `log_verifikasi`
--

INSERT INTO `log_verifikasi` (`id`, `peserta_id`, `admin_id`, `status`, `catatan`, `created_at`) VALUES
(1, 11, 4, 'terverifikasi', '', '2026-01-15 10:12:15');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `pembayaran_menunggu_verifikasi`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `pembayaran_menunggu_verifikasi` (
`biaya_pendaftaran` decimal(10,2)
,`bukti_pembayaran` varchar(255)
,`created_at` timestamp
,`email` varchar(100)
,`event_judul` varchar(200)
,`id` int
,`kode_pendaftaran` varchar(50)
,`nama` varchar(100)
,`nama_tim` varchar(100)
,`npm` varchar(20)
,`status_pembayaran` varchar(19)
,`tipe` varchar(8)
);

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta`
--

CREATE TABLE `peserta` (
  `id` int NOT NULL,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `peserta`
--

INSERT INTO `peserta` (`id`, `event_id`, `tim_id`, `nama`, `npm`, `email`, `no_wa`, `jurusan`, `kode_pendaftaran`, `bukti_pembayaran`, `status_pembayaran`, `status_anggota`, `created_at`) VALUES
(9, 11, 6, 'reyvan', '3312511137', 'ditorevan55@gmail.com', '6287813559019', 'teknik informatika', NULL, NULL, 'terverifikasi', 'ketua', '2026-01-15 09:48:24'),
(10, 11, 6, 'rafie', '3312511136', 'rafi@gmail.com', '6281364444814', 'teknik informatika', NULL, NULL, 'menunggu_verifikasi', 'anggota', '2026-01-15 09:48:24'),
(11, 11, 7, 'amadeo', '3312511135', 'ditorevan55@gmail.com', '4561545415', 'teknik informatika', NULL, NULL, 'terverifikasi', 'ketua', '2026-01-15 09:51:50'),
(12, 11, 7, 'enting', '3312511133', 'enting@gmail.com', '6289865453265', 'rekayasa keamanan siber', NULL, NULL, 'menunggu_verifikasi', 'anggota', '2026-01-15 09:51:50');

--
-- Trigger `peserta`
--
DELIMITER $$
CREATE TRIGGER `after_peserta_insert` AFTER INSERT ON `peserta` FOR EACH ROW BEGIN
    DECLARE v_total INT;
    
    -- Count unique registrations based on event type
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
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_520_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'max_upload_size', '5242880', 'Maximum upload file size in bytes (5MB)', '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(2, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf', 'Allowed file extensions for proof of payment', '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(3, 'payment_verification_time', '24', 'Maximum verification time in hours', '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(4, 'contact_email', 'info@portalkampus.com', 'Contact email for inquiries', '2026-01-09 12:15:30', '2026-01-14 09:52:21'),
(5, 'whatsapp_contact', '6281234567890', 'WhatsApp contact number', '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(6, 'admin_fee_percentage', '0', 'Additional admin fee percentage', '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(7, 'auto_verify_free_events', '1', 'Auto verify registration for free events', '2026-01-09 12:15:30', '2026-01-09 12:15:30'),
(9, 'site_title', 'PortalKampus', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(10, 'site_description', 'Portal informasi event kampus terbaru', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(11, 'site_keywords', 'event, kampus, mahasiswa, seminar, workshop', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(12, 'admin_email', 'admin@portalkampus.com', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(13, 'phone_number', '(021) 1234-5678', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(14, 'address', 'Jl. Kampus No. 123, Jakarta', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(15, 'facebook_url', 'https://facebook.com/portalkampus', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(16, 'instagram_url', 'https://instagram.com/portalkampus', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(17, 'twitter_url', 'https://twitter.com/portalkampus', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(18, 'contact_title', 'Hubungi Kami', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(19, 'contact_description', 'Silakan hubungi kami untuk informasi lebih lanjut mengenai event kampus.', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(20, 'contact_phone', '(021) 8765-4321', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(21, 'contact_address', 'Gedung Student Center Lt. 3\nJl. Kampus Barat No. 45\nJakarta 12345', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(22, 'contact_hours', 'Senin - Jumat: 08:00 - 17:00\nSabtu: 08:00 - 12:00\nMinggu: Tutup', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(23, 'about_title', 'Tentang PortalKampus', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(24, 'about_content', 'PortalKampus adalah platform informasi event kampus terdepan yang menyediakan berbagai kegiatan untuk pengembangan mahasiswa. Kami berkomitmen untuk menghubungkan mahasiswa dengan berbagai kesempatan pengembangan diri melalui seminar, workshop, kompetisi, dan kegiatan kampus lainnya.', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(25, 'vision', 'Menjadi platform utama informasi event kampus yang menghubungkan mahasiswa dengan berbagai kesempatan pengembangan diri dan karir.', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(26, 'mission', '1. Menyediakan informasi event kampus terkini\n2. Memfasilitasi pendaftaran event secara online\n3. Membangun komunitas mahasiswa yang aktif\n4. Mendukung pengembangan soft skill mahasiswa', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(27, 'footer_copyright', '© 2024 PortalKampus. All rights reserved.', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(28, 'footer_col1_title', 'Tentang Kami', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(29, 'footer_col1_content', 'PortalKampus adalah platform informasi event kampus terdepan yang menyediakan berbagai kegiatan untuk pengembangan mahasiswa.', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(30, 'footer_col2_title', 'Tautan Cepat', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21'),
(31, 'footer_col2_content', 'index.php|Beranda\nevents.php|Event\nberita.php|Berita\nabout.php|Tentang\ncontact.php|Kontak', NULL, '2026-01-14 09:52:21', '2026-01-14 09:52:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tim_event`
--

CREATE TABLE `tim_event` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `nama_tim` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `kode_pendaftaran` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `jumlah_anggota` int DEFAULT '1',
  `bukti_pembayaran` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status_pembayaran` enum('menunggu_verifikasi','terverifikasi','ditolak','gratis') COLLATE utf8mb4_unicode_520_ci DEFAULT 'menunggu_verifikasi',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data untuk tabel `tim_event`
--

INSERT INTO `tim_event` (`id`, `event_id`, `nama_tim`, `kode_pendaftaran`, `jumlah_anggota`, `bukti_pembayaran`, `status_pembayaran`, `created_at`) VALUES
(6, 11, 'tim jaya', 'REG-LOM-20260115-4029', 2, '', 'terverifikasi', '2026-01-15 09:48:24'),
(7, 11, 'tim semangat', 'REG-LOM-20260115-1676', 2, 'PAY-20260115095150-6968b8b6019fb.jpeg', 'terverifikasi', '2026-01-15 09:51:50');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `admin_event`
--
ALTER TABLE `admin_event`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `tim_id` (`tim_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `log_verifikasi`
--
ALTER TABLE `log_verifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_tim_id` (`tim_id`),
  ADD KEY `idx_kode` (`kode_pendaftaran`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `tim_event`
--
ALTER TABLE `tim_event`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pendaftaran` (`kode_pendaftaran`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `admin_event`
--
ALTER TABLE `admin_event`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `berita`
--
ALTER TABLE `berita`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `events`
--
ALTER TABLE `events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT untuk tabel `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `log_verifikasi`
--
ALTER TABLE `log_verifikasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `tim_event`
--
ALTER TABLE `tim_event`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- --------------------------------------------------------

--
-- Struktur untuk view `laporan_pendaftaran`
--
DROP TABLE IF EXISTS `laporan_pendaftaran`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `laporan_pendaftaran`  AS SELECT `e`.`id` AS `event_id`, `e`.`judul` AS `judul`, `e`.`tanggal` AS `tanggal`, `e`.`biaya_pendaftaran` AS `biaya_pendaftaran`, `e`.`tipe_pendaftaran` AS `tipe_pendaftaran`, count(distinct (case when (`e`.`tipe_pendaftaran` = 'tim') then `p`.`tim_id` else `p`.`id` end)) AS `total_pendaftar`, sum((case when ((`e`.`tipe_pendaftaran` = 'tim') and (`t`.`status_pembayaran` = 'terverifikasi')) then 1 when ((`e`.`tipe_pendaftaran` <> 'tim') and (`p`.`status_pembayaran` = 'terverifikasi')) then 1 else 0 end)) AS `terverifikasi`, sum((case when ((`e`.`tipe_pendaftaran` = 'tim') and (`t`.`status_pembayaran` = 'menunggu_verifikasi')) then 1 when ((`e`.`tipe_pendaftaran` <> 'tim') and (`p`.`status_pembayaran` = 'menunggu_verifikasi')) then 1 else 0 end)) AS `menunggu_verifikasi`, sum(`e`.`biaya_pendaftaran`) AS `total_pendapatan_potensial` FROM ((`events` `e` left join `peserta` `p` on((`e`.`id` = `p`.`event_id`))) left join `tim_event` `t` on((`p`.`tim_id` = `t`.`id`))) GROUP BY `e`.`id`, `e`.`judul`, `e`.`tanggal`, `e`.`biaya_pendaftaran`, `e`.`tipe_pendaftaran` ORDER BY `e`.`tanggal` DESC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `pembayaran_menunggu_verifikasi`
--
DROP TABLE IF EXISTS `pembayaran_menunggu_verifikasi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pembayaran_menunggu_verifikasi`  AS SELECT 'individu' AS `tipe`, `p`.`id` AS `id`, `p`.`nama` AS `nama`, `p`.`npm` AS `npm`, `p`.`email` AS `email`, `p`.`kode_pendaftaran` AS `kode_pendaftaran`, `p`.`bukti_pembayaran` AS `bukti_pembayaran`, `p`.`status_pembayaran` AS `status_pembayaran`, `p`.`created_at` AS `created_at`, `e`.`judul` AS `event_judul`, `e`.`biaya_pendaftaran` AS `biaya_pendaftaran`, NULL AS `nama_tim` FROM (`peserta` `p` join `events` `e` on((`p`.`event_id` = `e`.`id`))) WHERE ((`p`.`status_pembayaran` = 'menunggu_verifikasi') AND (`e`.`biaya_pendaftaran` > 0) AND (`p`.`tim_id` is null))union all select 'tim' AS `tipe`,NULL AS `id`,NULL AS `nama`,NULL AS `npm`,NULL AS `email`,`t`.`kode_pendaftaran` AS `kode_pendaftaran`,`t`.`bukti_pembayaran` AS `bukti_pembayaran`,`t`.`status_pembayaran` AS `status_pembayaran`,`t`.`created_at` AS `created_at`,`e`.`judul` AS `event_judul`,`e`.`biaya_pendaftaran` AS `biaya_pendaftaran`,`t`.`nama_tim` AS `nama_tim` from (`tim_event` `t` join `events` `e` on((`t`.`event_id` = `e`.`id`))) where ((`t`.`status_pembayaran` = 'menunggu_verifikasi') and (`e`.`biaya_pendaftaran` > 0))  ;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `log_pembayaran`
--
ALTER TABLE `log_pembayaran`
  ADD CONSTRAINT `log_pembayaran_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `log_pembayaran_ibfk_2` FOREIGN KEY (`tim_id`) REFERENCES `tim_event` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `log_pembayaran_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `log_verifikasi`
--
ALTER TABLE `log_verifikasi`
  ADD CONSTRAINT `log_verifikasi_ibfk_1` FOREIGN KEY (`peserta_id`) REFERENCES `peserta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_verifikasi_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin_event` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD CONSTRAINT `peserta_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peserta_ibfk_2` FOREIGN KEY (`tim_id`) REFERENCES `tim_event` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tim_event`
--
ALTER TABLE `tim_event`
  ADD CONSTRAINT `tim_event_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

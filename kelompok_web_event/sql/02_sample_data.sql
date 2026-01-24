-- Data dummy untuk development
-- Password untuk semua user: password123

-- Admin accounts
INSERT INTO `admin` (`username`, `password`, `nama_lengkap`, `email`, `level`, `status`) VALUES
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'superadmin@kampus.ac.id', 'superadmin', 'active'),
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Biasa', 'admin@kampus.ac.id', 'admin', 'active'),
('panitia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Panitia Event', 'panitia@kampus.ac.id', 'panitia', 'active');

-- Kategori event
INSERT INTO `kategori` (`nama`, `warna`, `ikon`, `deskripsi`) VALUES
('Seminar & Workshop', '#0056b3', 'fas fa-chalkboard-teacher', 'Seminar dan workshop pengembangan diri'),
('Lomba Olahraga', '#28a745', 'fas fa-running', 'Kompetisi olahraga antar mahasiswa'),
('Seni & Budaya', '#dc3545', 'fas fa-palette', 'Kegiatan seni dan budaya'),
('Kompetisi', '#f72585', 'fas fa-trophy', 'Lomba dan kompetisi'),
('Webinar', '#4361ee', 'fas fa-video', 'Event online');

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_title', 'Portal Event Kampus', 'Judul website'),
('contact_email', 'info@eventkampus.ac.id', 'Email kontak'),
('max_upload_size', '5242880', 'Ukuran maksimal upload file (5MB)'),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf', 'Tipe file yang diizinkan'),
('payment_verification_time', '24', 'Waktu verifikasi pembayaran (jam)');

-- Bank accounts
INSERT INTO `bank_accounts` (`bank_name`, `account_number`, `account_name`, `branch`) VALUES
('Bank Mandiri', '123-456-7890', 'Panitia Event Kampus', 'Cabang Utama'),
('Bank BCA', '987-654-3210', 'Panitia Event Kampus', 'Cabang Kampus');

-- Sample events (gratis dan berbayar)
INSERT INTO `events` (`judul`, `deskripsi`, `kategori_id`, `tanggal`, `lokasi`, `biaya_pendaftaran`, `status`) VALUES
('Workshop Public Speaking', 'Pelatihan public speaking untuk mahasiswa', 1, '2026-01-25', 'Aula Utama', 0.00, 'publik'),
('Lomba Futsal Antar Fakultas', 'Kompetisi futsal tahunan', 2, '2026-02-10', 'Gedung Olahraga', 50000.00, 'publik'),
('Webinar AI untuk Pemula', 'Pengenalan AI untuk mahasiswa', 5, '2026-01-30', 'Online', 0.00, 'publik');
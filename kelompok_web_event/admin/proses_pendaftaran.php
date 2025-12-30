<?php
// Simpan ke tabel PENDING
$sql = "INSERT INTO pendaftaran_pending (nim, nama_lengkap, prodi, no_wa, nama_ketua) 
        VALUES ('$nim', '$nama', '$prodi', '$wa', '$ketua')";

// Redirect ke halaman sukses
echo "Pendaftaran berhasil! Menunggu verifikasi admin.";
?>
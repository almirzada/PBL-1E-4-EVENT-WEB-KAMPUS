<?php
// FILE: update_table.php
// FUNGSI: Tambah kolom 'status' ke tabel pendaftaran jika belum ada

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_lomba";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

echo "<h2>🔄 Updating Database Structure</h2>";
echo "<pre>";

// 1. CEK APAKAH KOLOM 'status' SUDAH ADA
$check = $conn->query("SHOW COLUMNS FROM pendaftaran LIKE 'status'");
if ($check->num_rows == 0) {
    // 2. TAMBAH KOLOM 'status'
    $sql = "ALTER TABLE pendaftaran 
            ADD COLUMN status VARCHAR(20) DEFAULT 'pending' 
            AFTER nama_ketua";
    
    if ($conn->query($sql)) {
        echo "✅ Kolom 'status' berhasil ditambahkan!\n";
    } else {
        echo "❌ Gagal tambah kolom: " . $conn->error . "\n";
    }
} else {
    echo "⏭️ Kolom 'status' sudah ada, skip.\n";
}

// 3. UPDATE DATA LAMA (jika status NULL, set jadi 'verified')
$update = $conn->query("UPDATE pendaftaran SET status='verified' WHERE status IS NULL OR status=''");
echo "📊 Data lama diupdate: " . $conn->affected_rows . " row(s)\n";

// 4. CEK HASIL
$result = $conn->query("SELECT status, COUNT(*) as jumlah FROM pendaftaran GROUP BY status");
echo "\n📈 Status Data Saat Ini:\n";
while ($row = $result->fetch_assoc()) {
    echo "- {$row['status']}: {$row['jumlah']} data\n";
}

$conn->close();

echo "\n🎉 Update selesai!";
echo "</pre>";
echo '<br><a href="dashboard.php">← Kembali ke Dashboard</a>';
?>
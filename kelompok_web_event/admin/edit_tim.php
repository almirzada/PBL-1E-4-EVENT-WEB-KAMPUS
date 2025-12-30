<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    exit("Akses ditolak!");
}

$conn = new mysqli("localhost", "root", "", "db_lomba");
$id_tim = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // PROSES UPDATE DATA TIM
    $nama_tim = $_POST['nama_tim'];
    $jenis_lomba = $_POST['jenis_lomba'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE tim SET nama_tim=?, jenis_lomba=?, status=? WHERE id_tim=?");
    $stmt->bind_param("sssi", $nama_tim, $jenis_lomba, $status, $id_tim);
    $stmt->execute();
    
    // PROSES UPDATE DATA ANGGOTA
    if (isset($_POST['anggota'])) {
        foreach ($_POST['anggota'] as $id_anggota => $data) {
            $nama = $data['nama'];
            $nim = $data['nim'];
            $prodi = $data['program_studi'];
            $tahun = $data['tahun_angkatan'];
            $peran = $data['peran'];
            $no_wa = $data['no_wa'] ?? '';
            
            $stmt2 = $conn->prepare("UPDATE anggota SET nama=?, nim=?, program_studi=?, tahun_angkatan=?, peran=?, no_wa=? WHERE id_anggota=?");
            $stmt2->bind_param("ssssssi", $nama, $nim, $prodi, $tahun, $peran, $no_wa, $id_anggota);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    
    $stmt->close();
    $conn->close();
    
    // ⬇️⬇️⬇️ KODE UNTUK REFRESH DASHBOARD ⬇️⬇️⬇️
    echo "<script>
        alert('✅ Data berhasil diupdate!');
        window.parent.location.reload(); // REFRESH DASHBOARD
        window.parent.closeModalEdit();  // TUTUP MODAL
    </script>";
    exit();
    // ⬆️⬆️⬆️ KODE UNTUK REFRESH DASHBOARD ⬆️⬆️⬆️
}

$tim = $conn->query("SELECT * FROM tim WHERE id_tim = $id_tim")->fetch_assoc();
$anggota = $conn->query("SELECT * FROM anggota WHERE id_tim = $id_tim ORDER BY peran DESC");
?>
<div style="padding:20px; max-width:800px; background:white; border-radius:10px;">
    <h2 style="color:#2c3e50; margin-bottom:20px;">✏️ Edit Tim: <?php echo htmlspecialchars($tim['nama_tim']); ?></h2>
    
    <form method="POST" id="formEdit" style="display:flex; flex-direction:column; gap:20px;">
        <!-- DATA TIM -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <div>
                <label style="display:block; color:#1e88e5; font-weight:600; margin-bottom:8px;">Nama Tim:</label>
                <input type="text" name="nama_tim" value="<?php echo htmlspecialchars($tim['nama_tim']); ?>" 
                       style="width:100%; padding:10px; border:2px solid #ddd; border-radius:8px;" required>
            </div>
            
            <div>
                <label style="display:block; color:#1e88e5; font-weight:600; margin-bottom:8px;">Jenis Lomba:</label>
                <select name="jenis_lomba" style="width:100%; padding:10px; border:2px solid #ddd; border-radius:8px;" required>
                    <option value="Futsal" <?php echo $tim['jenis_lomba']=='Futsal'?'selected':''; ?>>Futsal</option>
                    <option value="Basket" <?php echo $tim['jenis_lomba']=='Basket'?'selected':''; ?>>Basket</option>
                    <option value="Badminton" <?php echo $tim['jenis_lomba']=='Badminton'?'selected':''; ?>>Badminton</option>
                </select>
            </div>
        </div>
        
        <div>
            <label style="display:block; color:#1e88e5; font-weight:600; margin-bottom:8px;">Status:</label>
            <select name="status" style="width:100%; padding:10px; border:2px solid #ddd; border-radius:8px;" required>
                <option value="pending" <?php echo $tim['status']=='pending'?'selected':''; ?>>⏳ Pending</option>
                <option value="verified" <?php echo $tim['status']=='verified'?'selected':''; ?>>✅ Verified</option>
                <option value="rejected" <?php echo $tim['status']=='rejected'?'selected':''; ?>>❌ Rejected</option>
            </select>
        </div>
        
        <hr style="border:none; border-top:2px solid #eee; margin:20px 0;">
        
        <!-- ANGGOTA -->
        <h3 style="color:#2c3e50;">👥 Data Anggota</h3>
        <?php while($row = $anggota->fetch_assoc()): ?>
        <div style="background:#f8f9fa; padding:20px; border-radius:10px; margin-bottom:15px; border-left:5px solid <?php echo $row['peran']=='ketua'?'#ffc107':'#1e88e5'; ?>">
            <?php if($row['peran']=='ketua'): ?>
            <div style="background:#ffeaa7; padding:5px 15px; border-radius:20px; font-weight:bold; display:inline-block; margin-bottom:10px;">👑 Ketua Tim</div>
            <?php endif; ?>
            
            <input type="hidden" name="anggota[<?php echo $row['id_anggota']; ?>][id]" value="<?php echo $row['id_anggota']; ?>">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; color:#666; font-size:0.9rem; margin-bottom:5px;">Nama Lengkap:</label>
                    <input type="text" name="anggota[<?php echo $row['id_anggota']; ?>][nama]" 
                           value="<?php echo htmlspecialchars($row['nama']); ?>" 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" required>
                </div>
                <div>
                    <label style="display:block; color:#666; font-size:0.9rem; margin-bottom:5px;">NIM:</label>
                    <input type="text" name="anggota[<?php echo $row['id_anggota']; ?>][nim]" 
                           value="<?php echo htmlspecialchars($row['nim']); ?>" 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" required>
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div>
                    <label style="display:block; color:#666; font-size:0.9rem; margin-bottom:5px;">Program Studi:</label>
                    <input type="text" name="anggota[<?php echo $row['id_anggota']; ?>][program_studi]" 
                           value="<?php echo htmlspecialchars($row['program_studi']); ?>" 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" required>
                </div>
                <div>
                    <label style="display:block; color:#666; font-size:0.9rem; margin-bottom:5px;">Tahun Angkatan:</label>
                    <input type="number" name="anggota[<?php echo $row['id_anggota']; ?>][tahun_angkatan]" 
                           value="<?php echo $row['tahun_angkatan']; ?>" min="2000" max="2100"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;" required>
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; color:#666; font-size:0.9rem; margin-bottom:5px;">Peran:</label>
                    <select name="anggota[<?php echo $row['id_anggota']; ?>][peran]" 
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                        <option value="anggota" <?php echo $row['peran']=='anggota'?'selected':''; ?>>Anggota</option>
                        <option value="ketua" <?php echo $row['peran']=='ketua'?'selected':''; ?>>Ketua</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; color:#666; font-size:0.9rem; margin-bottom:5px;">No. WhatsApp:</label>
                    <input type="tel" name="anggota[<?php echo $row['id_anggota']; ?>][no_wa]" 
                           value="<?php echo htmlspecialchars($row['no_wa']??''); ?>" 
                           placeholder="081234567890"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <div style="display:flex; gap:15px; margin-top:30px;">
            <button type="submit" style="flex:1; background:#28a745; color:white; padding:15px; border:none; border-radius:10px; font-weight:bold; cursor:pointer;">
                💾 Simpan Perubahan
            </button>
            <button type="button" onclick="window.parent.closeModalEdit()" 
                    style="flex:1; background:#6c757d; color:white; padding:15px; border:none; border-radius:10px; font-weight:bold; cursor:pointer;">
                ✖️ Batal
            </button>
        </div>
    </form>
</div>

<script>
// Submit form dengan AJAX biar gak reload halaman
document.getElementById('formEdit').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Response dari PHP akan eksekusi script refresh
        document.body.innerHTML = data;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data!');
    });
});
</script>

<?php $conn->close(); ?>
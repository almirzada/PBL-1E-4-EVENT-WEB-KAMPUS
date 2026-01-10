<?php
// test_delete.php
echo json_encode([
    'success' => true,
    'message' => 'Test AJAX berhasil!',
    'received_data' => $_POST,
    'server_time' => date('Y-m-d H:i:s'),
    'test' => 'File test_delete.php berhasil diakses'
]);
?>
<?php
include "../conn/conn.php";

header('Content-Type: application/json');

$username = isset($_GET['username']) ? mysqli_real_escape_string($conn, trim($_GET['username'])) : '';

if (empty($username)) {
    echo json_encode(['tersedia' => false, 'pesan' => 'Username kosong.']);
    exit;
}

$cek_user = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'"));
$cek_pest = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM peserta WHERE username = '$username'"));

if ($cek_user > 0 || $cek_pest > 0) {
    echo json_encode(['tersedia' => false, 'pesan' => 'Username sudah digunakan.']);
} else {
    echo json_encode(['tersedia' => true, 'pesan' => 'Username tersedia.']);
}

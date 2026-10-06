<?php
// ============================================================
// AJAX ENDPOINT: Cek apakah sertifikat sudah dibuat di DB
// URL: ajax_cek_sertifikat.php?laporan_id=X
// Response: JSON
// Letakkan file ini di folder yang SAMA dengan cetak_sertifikat.php
// ============================================================
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'sdm') {
    echo json_encode(array('found' => false, 'error' => 'Akses ditolak'));
    exit;
}

include "../conn/conn.php";

$laporan_id = isset($_GET['laporan_id']) ? (int)$_GET['laporan_id'] : 0;

if (!$laporan_id) {
    echo json_encode(array('found' => false));
    exit;
}

$q = mysqli_query($conn, "
    SELECT sm.id AS sertif_id, sm.nomor_surat, sm.tgl_cetak, sm.status,
           p.nama
    FROM sertifikat_magang sm
    JOIN laporan_magang lm ON sm.username = lm.username
    JOIN peserta p ON sm.username = p.username
    WHERE lm.id = $laporan_id
    LIMIT 1
");

if ($q && mysqli_num_rows($q) > 0) {
    $row = mysqli_fetch_assoc($q);
    echo json_encode(array(
        'found'       => true,
        'sertif_id'   => $row['sertif_id'],
        'nomor_surat' => $row['nomor_surat'],
        'tgl_cetak'   => $row['tgl_cetak'],
        'nama'        => $row['nama'],
        'status'      => $row['status']
    ));
} else {
    echo json_encode(array('found' => false));
}

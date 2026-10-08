<?php
// get_peserta.php
header('Content-Type: application/json');
include __DIR__ . "/../conn/conn.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$id = (int)$_GET['id'];

$query = "SELECT * FROM peserta WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Peserta tidak ditemukan']);
}

mysqli_close($conn);
?>
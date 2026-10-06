<?php
// search_peserta.php
header('Content-Type: application/json');
include __DIR__ . "/../conn/conn.php";

if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    echo json_encode([]);
    exit;
}

$search = mysqli_real_escape_string($conn, $_GET['q']);

$query = "SELECT id, nama, asal_sekolah, status_magang 
          FROM peserta 
          WHERE nama LIKE '%$search%' 
          ORDER BY 
              CASE 
                  WHEN nama LIKE '$search%' THEN 1
                  WHEN nama LIKE '%$search%' THEN 2
                  ELSE 3
              END,
              nama
          LIMIT 10";

$result = mysqli_query($conn, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'nama' => $row['nama'],
        'asal_sekolah' => $row['asal_sekolah'],
        'status_magang' => $row['status_magang']
    ];
}

echo json_encode($data);
mysqli_close($conn);
?>
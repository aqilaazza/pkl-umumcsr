<?php
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../../conn/conn.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Terima dari params terpisah (bulan_dari + tahun_dari) atau format gabungan YYYY-MM
$b_dari   = isset($_GET['bulan_dari']) && $_GET['bulan_dari'] ? str_pad($_GET['bulan_dari'], 2, '0', STR_PAD_LEFT) : '';
$t_dari   = isset($_GET['tahun_dari']) && $_GET['tahun_dari'] ? (int) $_GET['tahun_dari'] : 0;
$b_sampai = isset($_GET['bulan_sampai']) && $_GET['bulan_sampai'] ? str_pad($_GET['bulan_sampai'], 2, '0', STR_PAD_LEFT) : '';
$t_sampai = isset($_GET['tahun_sampai']) && $_GET['tahun_sampai'] ? (int) $_GET['tahun_sampai'] : 0;
$bulan_dari   = ($b_dari && $t_dari) ? $t_dari . '-' . $b_dari : null;
$bulan_sampai = ($b_sampai && $t_sampai) ? $t_sampai . '-' . $b_sampai : null;

$where = "WHERE 1=1";
if ($search)        $where .= " AND (p.nama LIKE '%$search%' OR p.username LIKE '%$search%' OR p.asal_sekolah LIKE '%$search%')";
if ($status_filter) $where .= " AND p.status_magang = '" . mysqli_real_escape_string($conn, $status_filter) . "'";

if ($bulan_dari && $bulan_sampai) {
    try {
        $start = new DateTime($bulan_dari . '-01');
        $end = new DateTime($bulan_sampai . '-01');
        $end->modify('last day of this month');
        $where .= " AND NOT (p.tgl_keluar < '" . $start->format('Y-m-d') . "' OR p.tgl_masuk > '" . $end->format('Y-m-d') . "')";
    } catch (Exception $e) {}
} elseif ($bulan_dari) {
    try {
        $start = new DateTime($bulan_dari . '-01');
        $end   = clone $start;
        $end->modify('last day of this month');
        $where .= " AND NOT (p.tgl_keluar < '" . $start->format('Y-m-d') . "' OR p.tgl_masuk > '" . $end->format('Y-m-d') . "')";
    } catch (Exception $e) {}
} elseif ($bulan_sampai) {
    try {
        $start = new DateTime($bulan_sampai . '-01');
        $end   = clone $start;
        $end->modify('last day of this month');
        $where .= " AND NOT (p.tgl_keluar < '" . $start->format('Y-m-d') . "' OR p.tgl_masuk > '" . $end->format('Y-m-d') . "')";
    } catch (Exception $e) {}
}

$sql = "
    SELECT p.*, b.bidang AS nama_bidang
    FROM peserta p
    LEFT JOIN bidang b ON p.bidang_id = b.id
    $where
    ORDER BY p.id DESC
";

$res = mysqli_query($conn, $sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Peserta');

$headers = ['No','Username','Nama','Status Peserta','Status Magang','Asal Sekolah','Bidang','Unit','Tgl Masuk','Tgl Keluar','Durasi(hari)','Keterangan'];
$cols = ['A','B','C','D','E','F','G','H','I','J','K','L'];

foreach ($cols as $i => $col) {
    $sheet->setCellValue($col . '1', $headers[$i]);
}

$rowNum = 2;
$no = 1;
while ($r = mysqli_fetch_assoc($res)) {
    $masuk  = $r['tgl_masuk'];
    $keluar = $r['tgl_keluar'];
    $durasi = '';
    if ($masuk && $keluar) {
        $d1 = new DateTime($masuk);
        $d2 = new DateTime($keluar);
        $durasi = $d1->diff($d2)->days;
    }

    $sheet->setCellValue('A' . $rowNum, $no++);
    $sheet->setCellValue('B' . $rowNum, $r['username']);
    $sheet->setCellValue('C' . $rowNum, $r['nama']);
    $sheet->setCellValue('D' . $rowNum, $r['status_peserta']);
    $sheet->setCellValue('E' . $rowNum, $r['status_magang']);
    $sheet->setCellValue('F' . $rowNum, $r['asal_sekolah']);
    $sheet->setCellValue('G' . $rowNum, $r['nama_bidang']);
    $sheet->setCellValue('H' . $rowNum, $r['unit']);
    $sheet->setCellValue('I' . $rowNum, $masuk ? date('Y-m-d', strtotime($masuk)) : '');
    $sheet->setCellValue('J' . $rowNum, $keluar ? date('Y-m-d', strtotime($keluar)) : '');
    $sheet->setCellValue('K' . $rowNum, $durasi);
    $sheet->setCellValue('L' . $rowNum, $r['keterangan']);

    $rowNum++;
}

foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$filename = 'peserta_export_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;

?>

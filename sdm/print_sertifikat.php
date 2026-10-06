<?php
// ============================================================
// AKSES: SDM (dipanggil via target="_blank")
// URL: print_sertifikat.php?laporan_id=X&username=Y
// ============================================================
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'sdm') {
    die('Akses ditolak.');
}

include "../conn/conn.php";

$laporan_id = isset($_GET['laporan_id']) ? (int)$_GET['laporan_id'] : 0;
$username   = isset($_GET['username'])   ? mysqli_real_escape_string($conn, trim($_GET['username'])) : '';

if (!$laporan_id || !$username) {
    die('Parameter tidak valid.');
}

$qPeserta = mysqli_query($conn, "
    SELECT p.*, b.bidang AS nama_bidang, lm.id AS laporan_id, lm.status AS status_laporan, lm.sdm_status, lm.manager_status
    FROM peserta p
    LEFT JOIN bidang b ON p.bidang_id = b.id
    JOIN laporan_magang lm ON lm.username = p.username AND lm.id = $laporan_id
    WHERE p.username = '$username'
    LIMIT 1
");

if (!$qPeserta) {
    die('Query error: ' . mysqli_error($conn));
}

$peserta = mysqli_fetch_assoc($qPeserta);

if (!$peserta) {
    die('Data peserta tidak ditemukan.');
}

$bolehCetak = ($peserta['status_laporan'] === 'Disetujui') ||
              ($peserta['status_laporan'] === 'Menunggu Manager' && $peserta['sdm_status'] === 'Disetujui');
if (!$bolehCetak) {
    echo '<script>alert("Laporan belum Disetujui!"); window.close();</script>';
    exit;
}

// ============================================================
// FUNGSI
// ============================================================
function getNomorSuratTerakhir($conn) {
    $res = mysqli_query($conn, "SELECT nomor_surat FROM nomor WHERE id=1");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return $row['nomor_surat'];
    }
    $tahun = date('Y');
    $init  = '000/PKL/I/PTN/' . $tahun;
    mysqli_query($conn, "INSERT INTO nomor (id, nomor_surat) VALUES (1, '$init') ON DUPLICATE KEY UPDATE id=id");
    return $init;
}

function getNomorSuratBerikutnya($nomorSuratTerakhir) {
    $bln_arr       = array(1=>"I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII");
    $bln           = $bln_arr[(int)date('n')];
    $tahunSekarang = (int)date('Y');
    $pecah         = explode('/', $nomorSuratTerakhir);
    $nomorTerakhir = (int)$pecah[0];
    $tahunTerakhir = isset($pecah[4]) ? (int)$pecah[4] : 0;
    if ($tahunTerakhir != $tahunSekarang) {
        return "001/PKL/{$bln}/PTN/{$tahunSekarang}";
    } else {
        $nomorTerakhir++;
        return sprintf("%03d", $nomorTerakhir) . "/PKL/{$bln}/PTN/{$tahunSekarang}";
    }
}

function getTanggalIndonesia($tanggal) {
    $namaBulan = array(
        1=>'Januari', 2=>'Februari', 3=>'Maret',    4=>'April',
        5=>'Mei',     6=>'Juni',     7=>'Juli',      8=>'Agustus',
        9=>'September',10=>'Oktober',11=>'November', 12=>'Desember'
    );
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $namaBulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function adjustNomorSurat($nomorSurat, $tanggal = null) {
    if (!$tanggal) {
        $tanggal = date('Y-m-d');
    }
    $ts = strtotime($tanggal);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    
    $bln_arr = array(1=>"I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII");
    $bln = $bln_arr[$m];
    
    $pecah = explode('/', $nomorSurat);
    if (count($pecah) >= 5) {
        $pecah[2] = $bln;
        $pecah[4] = $y;
        return implode('/', $pecah);
    }
    return $nomorSurat;
}

function formatRangeTanggal($tglMulai, $tglSelesai) {
    $namaBulan = array(
        1=>'Januari', 2=>'Februari', 3=>'Maret',    4=>'April',
        5=>'Mei',     6=>'Juni',     7=>'Juli',      8=>'Agustus',
        9=>'September',10=>'Oktober',11=>'November', 12=>'Desember'
    );
    
    $ts1 = strtotime($tglMulai);
    $ts2 = strtotime($tglSelesai);
    
    $d1 = (int)date('d', $ts1);
    $m1 = (int)date('n', $ts1);
    $y1 = (int)date('Y', $ts1);
    
    $d2 = (int)date('d', $ts2);
    $m2 = (int)date('n', $ts2);
    $y2 = (int)date('Y', $ts2);
    
    if ($y1 === $y2) {
        if ($m1 === $m2) {
            if ($d1 === $d2) {
                return "$d1 " . $namaBulan[$m1] . " $y1";
            }
            return "$d1 - $d2 " . $namaBulan[$m1] . " $y1";
        } else {
            return "$d1 " . $namaBulan[$m1] . " - $d2 " . $namaBulan[$m2] . " $y1";
        }
    } else {
        return "$d1 " . $namaBulan[$m1] . " $y1 - $d2 " . $namaBulan[$m2] . " $y2";
    }
}

// ============================================================
// NOMOR SURAT
// ============================================================
$qSertifExist = mysqli_query($conn, "SELECT id, nomor_surat FROM sertifikat_magang WHERE username = '$username' LIMIT 1");
$sertifExist  = $qSertifExist ? mysqli_fetch_assoc($qSertifExist) : null;

if ($sertifExist && $sertifExist['nomor_surat']) {
    $nomorSuratBerikutnya = $sertifExist['nomor_surat'];
    $nomorSuratBerikutnya = adjustNomorSurat($nomorSuratBerikutnya);
    $esc_nomor            = mysqli_real_escape_string($conn, $nomorSuratBerikutnya);

    mysqli_query($conn, "UPDATE sertifikat_magang SET
        laporan_id  = $laporan_id,
        nomor_surat = '$esc_nomor',
        status      = 'Belum Upload',
        tgl_cetak   = NOW()
        WHERE username = '$username'");
} else {
    $nomorSuratTerakhir   = getNomorSuratTerakhir($conn);
    $nomorSuratBerikutnya = getNomorSuratBerikutnya($nomorSuratTerakhir);
    $esc_nomor            = mysqli_real_escape_string($conn, $nomorSuratBerikutnya);

    $qUp = mysqli_query($conn, "UPDATE nomor SET nomor_surat = '$esc_nomor' WHERE id = 1");
    if (!$qUp) {
        mysqli_query($conn, "INSERT INTO nomor (id, nomor_surat) VALUES (1, '$esc_nomor')");
    }

    if ($sertifExist) {
        mysqli_query($conn, "UPDATE sertifikat_magang SET
            laporan_id  = $laporan_id,
            nomor_surat = '$esc_nomor',
            status      = 'Belum Upload',
            tgl_cetak   = NOW()
            WHERE username = '$username'");
    } else {
        mysqli_query($conn, "INSERT INTO sertifikat_magang (username, laporan_id, nomor_surat, status, tgl_cetak)
            VALUES ('$username', $laporan_id, '$esc_nomor', 'Belum Upload', NOW())");
    }
}

// ============================================================
// CARI PATH FPDF OTOMATIS
// ============================================================
$fpdf_paths = array(
    '../fpdf/fpdf.php',
    '../fpdf17/fpdf.php',
    'fpdf/fpdf.php',
    'fpdf17/fpdf.php',
    '../../fpdf/fpdf.php',
    '../../fpdf17/fpdf.php',
);
$fpdf_found = '';
foreach ($fpdf_paths as $path) {
    if (file_exists($path)) {
        $fpdf_found = $path;
        break;
    }
}
if (!$fpdf_found) {
    die('<b>FPDF tidak ditemukan.</b><br>
         Letakkan folder <b>fpdf</b> (berisi fpdf.php) di dalam folder <b>pkl/</b><br>
         Atau sesuaikan path secara manual di baris kode require di file ini.<br>
         Download: <a href="http://www.fpdf.org">http://www.fpdf.org</a>');
}
require($fpdf_found);

// ============================================================
// KONFIGURASI TTD - BACA DARI DATABASE
// ============================================================
$ttd_cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_ttd WHERE id = 1"));
$namaTandaTangan = $ttd_cfg['nama_ttd'] ?? 'Sukarno';
$jabatan         = $ttd_cfg['jabatan_ttd'] ?? 'Manager Business Support';

// Cari background sertifikat otomatis
$bg_paths = array(
    'sertifikat.jpg',
    '../sertifikat.jpg',
    'images/sertifikat.jpg',
    '../images/sertifikat.jpg',
);
$bg_sertifikat = '';
foreach ($bg_paths as $p) {
    if (file_exists($p)) { $bg_sertifikat = $p; break; }
}

// ============================================================
// CLASS PDF
// ============================================================
class PDF extends FPDF
{
    var $bgImage = '';

    function Header()
    {
        if ($this->bgImage && file_exists($this->bgImage)) {
            $this->Image($this->bgImage, 0, 0, 297, 210);
        }
    }

    function BuatSertifikat($nomorSurat, $nama, $sekolah, $bidang, $unit, $tglMulai, $tglSelesai, $namaTTD, $jabatanTTD)
    {
        $this->Cell(0, 20, '', 0, 1);
        $this->SetFont('Arial', '', 13);
        $this->Cell(0, 15, "No : $nomorSurat", 0, 1, 'C');
        $this->SetFont('Arial', '', 15);
        $this->Cell(0, 15, "Diberikan kepada:", 0, 1, 'C');
        $this->SetFont('Arial', 'B', 30);
        $this->Cell(0, 20, $nama, 0, 1, 'C');
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, $sekolah, 0, 1, 'C');
        $this->SetFont('Arial', '', 13);
        
        $bagian_str = "";
        if ($bidang || $unit) {
            $parts = [];
            if ($bidang) $parts[] = $bidang;
            if ($unit)   $parts[] = $unit;
            $bagian_str = " di Bagian " . implode(' ', $parts);
        } else {
            $bagian_str = " di";
        }
        $ket = "Telah Melaksanakan Praktek Kerja Lapangan" . $bagian_str . " PT PLN Nusantara Power UP Paiton";
        
        $this->Cell(0, 35, $ket, 0, 1, 'C');
        $this->SetY($this->GetY() - 10);
        $this->SetFont('Arial', '', 13);
        
        $periode = formatRangeTanggal($tglMulai, $tglSelesai);
        $this->Cell(0, 0, "Tanggal $periode", 0, 1, 'C');
        
        $tglSkrg = getTanggalIndonesia(date('Y-m-d'));
        $this->Cell(0, 40, "Paiton, " . $tglSkrg, 0, 1, 'C');
        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 30, $namaTTD, 0, 1, 'C');
        $this->SetY($this->GetY() - 10);
        $this->Cell(0, 10, $jabatanTTD, 0, 1, 'C');
    }
}

// ============================================================
// OUTPUT PDF
// ============================================================
$tglMulai   = $peserta['tgl_masuk'];
$tglSelesai = $peserta['tgl_keluar'];
$namaBidang = isset($peserta['nama_bidang']) ? $peserta['nama_bidang'] : '';
$namaUnit   = isset($peserta['unit'])        ? $peserta['unit']        : '';

ob_clean();

$pdf          = new PDF('L', 'mm', 'A4');
$pdf->bgImage = $bg_sertifikat;
$pdf->AddPage();
$pdf->BuatSertifikat(
    $nomorSuratBerikutnya,
    $peserta['nama'],
    $peserta['asal_sekolah'],
    $namaBidang,
    $namaUnit,
    $tglMulai,
    $tglSelesai,
    $namaTandaTangan,
    $jabatan
);

$namaFile = 'Sertifikat_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $peserta['nama']) . '.pdf';
$pdf->Output($namaFile, 'D');
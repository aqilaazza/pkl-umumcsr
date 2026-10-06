<?php
include "../conn/conn.php";
require_once '../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

$message = '';
$reviewer = $_SESSION['username'];
$esc_reviewer = mysqli_real_escape_string($conn, $reviewer);

// --- Generate QR Token ---
function generateToken($len = 40) {
    return bin2hex(random_bytes($len / 2));
}

// --- Generate QR Code Image ---
function generateQrImage($data, $outputPath) {
    $options = new QROptions([
        'outputInterface' => QRGdImagePNG::class,
        'eccLevel'        => 'M',
        'scale'           => 6,
        'outputBase64'    => false,
    ]);
    $qrcode = new QRCode($options);
    $qrcode->render($data, $outputPath);
}

// --- Nomor Surat functions ---
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
    $bln_arr       = [1=>"I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII"];
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

function adjustNomorSurat($nomorSurat, $tanggal = null) {
    if (!$tanggal) {
        $tanggal = date('Y-m-d');
    }
    $ts = strtotime($tanggal);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    
    $bln_arr = [1=>"I","II","III","IV","V","VI","VII","VIII","IX","X","XI","XII"];
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
    $namaBulan = [
        1=>'Januari', 2=>'Februari', 3=>'Maret',    4=>'April',
        5=>'Mei',     6=>'Juni',     7=>'Juli',      8=>'Agustus',
        9=>'September',10=>'Oktober',11=>'November', 12=>'Desember'
    ];
    
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

// --- Generate Digital Certificate PDF ---
function generateDigitalCert($conn, $username, $laporan_id, $qrImagePath, $nomorSurat) {
    $qPeserta = mysqli_query($conn, "
        SELECT p.*, b.bidang AS nama_bidang
        FROM peserta p
        LEFT JOIN bidang b ON p.bidang_id = b.id
        JOIN laporan_magang lm ON lm.username = p.username AND lm.id = $laporan_id
        WHERE p.username = '$username'
        LIMIT 1
    ");
    $peserta = mysqli_fetch_assoc($qPeserta);
    if (!$peserta) return null;

    // TTD config
    $ttd_cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_ttd WHERE id = 1"));
    $namaTTD = $ttd_cfg['nama_ttd'] ?? 'Sukarno';
    $jabatan = $ttd_cfg['jabatan_ttd'] ?? 'Manager Business Support';

    // Background
    $bg_paths = ['../sertifikat.jpg', '../sdm/sertifikat.jpg', 'sertifikat.jpg'];
    $bg_sertifikat = '';
    foreach ($bg_paths as $p) { if (file_exists($p)) { $bg_sertifikat = $p; break; } }

    // FPDF
    $fpdf_paths = ['../sdm/fpdf/fpdf.php','../fpdf/fpdf.php','fpdf/fpdf.php','../fpdf17/fpdf.php'];
    $fpdf_found = '';
    foreach ($fpdf_paths as $p) { if (file_exists($p)) { $fpdf_found = $p; break; } }
    if (!$fpdf_found) return null;
    require($fpdf_found);

    function getTanggalIndonesia($tanggal) {
        $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $ts = strtotime($tanggal);
        return date('d', $ts) . ' ' . $namaBulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    $tglMulai   = $peserta['tgl_masuk'];
    $tglSelesai = $peserta['tgl_keluar'];

    class SertifikatPDF extends FPDF {
        var $bgImage = '';
        function Header() {
            if ($this->bgImage && file_exists($this->bgImage)) {
                $this->Image($this->bgImage, 0, 0, 297, 210);
            }
        }
        function BuatSertifikat($nomorSurat, $nama, $sekolah, $bidang, $unit, $tglMulai, $tglSelesai, $namaTTD, $jabatanTTD, $qrImage) {
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
            $this->Cell(0, 20, "Paiton, " . $tglSkrg, 0, 1, 'C');

            $yAfterDate = $this->GetY();

            // QR code centered di antara tgl dan ttd
            if ($qrImage && file_exists($qrImage)) {
                $qrSize = 30;
                $qrX    = ($this->GetPageWidth() - $qrSize) / 2;
                $qrY    = $yAfterDate + 3;
                $this->Image($qrImage, $qrX, $qrY, $qrSize, $qrSize);
                $this->SetXY($qrX, $qrY + $qrSize + 1);
                $this->SetFont('Arial', '', 6);
                $this->Cell($qrSize, 2, 'Scan QR verifikasi', 0, 0, 'C');
            }

            // TTD rapat di bawah QR
            $ttdY = $yAfterDate + 3 + 30 + 1 + 2 + 3;
            $this->SetY($ttdY);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 6, $namaTTD, 0, 1, 'C');
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 5, $jabatanTTD, 0, 1, 'C');
        }
    }

    $pdf = new SertifikatPDF('L', 'mm', 'A4');
    $pdf->bgImage = $bg_sertifikat;
    $pdf->AddPage();
    $pdf->BuatSertifikat(
        $nomorSurat,
        $peserta['nama'],
        $peserta['asal_sekolah'],
        $peserta['nama_bidang'] ?? '',
        $peserta['unit'] ?? '',
        $tglMulai,
        $tglSelesai,
        $namaTTD,
        $jabatan,
        $qrImagePath
    );

    $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $peserta['nama']);
    $fileName = 'digital_' . $safeName . '_' . time() . '.pdf';
    $filePath = '../uploads/sertifikat/' . $fileName;
    $pdf->Output('F', $filePath);

    return $fileName;
}

// --- SETUJUI ---
if (isset($_POST['submit_setuju'])) {
    $laporan_id = (int) $_POST['laporan_id'];
    $username   = mysqli_real_escape_string($conn, trim($_POST['username']));

    // Cek status masih menunggu manager
    $cek = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id, sdm_status, manager_status FROM laporan_magang WHERE id = $laporan_id AND username = '$username'"));
    if (!$cek || $cek['sdm_status'] !== 'Disetujui' || $cek['manager_status'] !== 'Menunggu') {
        $message = '<div class="alert alert-danger">Laporan tidak valid atau sudah diproses.</div>';
    } else {
        // Generate token & QR
        $qrToken   = generateToken();
        $protocol  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'];
        $basePath  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $verifyUrl = "$protocol://$host$basePath/ceklogin/verifikasi.php?token=$qrToken";

        $qrDir = '../uploads/temp/';
        if (!is_dir($qrDir)) mkdir($qrDir, 0755, true);
        $qrFile = $qrDir . 'qr_' . $qrToken . '.png';
        generateQrImage($verifyUrl, $qrFile);

        // Cek / buat nomor surat
        $qSertifNo = mysqli_query($conn,
            "SELECT id, nomor_surat FROM sertifikat_magang WHERE username = '$username' AND laporan_id = $laporan_id LIMIT 1");
        $sertifNo  = mysqli_fetch_assoc($qSertifNo);

        if ($sertifNo && !empty($sertifNo['nomor_surat'])) {
            $nomorSurat = $sertifNo['nomor_surat'];
            $nomorSurat = adjustNomorSurat($nomorSurat);
        } else {
            $lastNo    = getNomorSuratTerakhir($conn);
            $nomorSurat = getNomorSuratBerikutnya($lastNo);
            $escNo     = mysqli_real_escape_string($conn, $nomorSurat);
            mysqli_query($conn, "UPDATE nomor SET nomor_surat = '$escNo' WHERE id = 1");
        }

        // Generate sertifikat PDF
        $digitalFile = generateDigitalCert($conn, $username, $laporan_id, $qrFile, $nomorSurat);

        // Hapus QR temp setelah PDF jadi
        if (file_exists($qrFile)) unlink($qrFile);

        if (!$digitalFile) {
            $message = '<div class="alert alert-danger">Gagal generate sertifikat digital.</div>';
        } else {
            // Update laporan
            mysqli_query($conn, "UPDATE laporan_magang SET
                manager_status     = 'Disetujui',
                manager_reviewed_by = '$esc_reviewer',
                manager_tgl_review  = NOW(),
                status             = 'Disetujui'
                WHERE id = $laporan_id");

            // Cek apakah sudah ada record sertifikat
            $exist = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id FROM sertifikat_magang WHERE username = '$username' AND laporan_id = $laporan_id LIMIT 1"));
            $esc_qr   = mysqli_real_escape_string($conn, $qrToken);
            $esc_file = mysqli_real_escape_string($conn, $digitalFile);

            $esc_no   = mysqli_real_escape_string($conn, $nomorSurat);

            if ($exist) {
                mysqli_query($conn, "UPDATE sertifikat_magang SET
                    qr_token          = '$esc_qr',
                    digital_file      = '$esc_file',
                    nomor_surat       = '$esc_no',
                    status            = 'Digital',
                    generated_at      = NOW(),
                    manager_approved_at = NOW()
                    WHERE id = {$exist['id']}");
            } else {
                mysqli_query($conn, "INSERT INTO sertifikat_magang
                    (laporan_id, username, nomor_surat, qr_token, digital_file, status, generated_at, manager_approved_at)
                    VALUES ($laporan_id, '$username', '$esc_no', '$esc_qr', '$esc_file', 'Digital', NOW(), NOW())");
            }

            $message = '<div class="alert alert-success">
                <i class="bx bxs-check-circle me-2"></i> Laporan <strong>Disetujui</strong>.
                Sertifikat digital dengan QR Code berhasil diterbitkan.
            </div>';
        }
    }
}

// --- TOLAK ---
if (isset($_POST['submit_tolak'])) {
    $laporan_id       = (int) $_POST['laporan_id'];
    $keterangan_tolak = mysqli_real_escape_string($conn, trim($_POST['keterangan_tolak']));

    if (empty($keterangan_tolak)) {
        $message = '<div class="alert alert-danger">Keterangan penolakan wajib diisi.</div>';
    } else {
        mysqli_query($conn, "UPDATE laporan_magang SET
            manager_status        = 'Ditolak',
            manager_reviewed_by   = '$esc_reviewer',
            manager_tgl_review    = NOW(),
            manager_keterangan_tolak = '$keterangan_tolak',
            status                = 'Ditolak'
            WHERE id = $laporan_id");

        $message = '<div class="alert alert-warning">
            <i class="bx bxs-x-circle me-2"></i> Laporan berhasil <strong>Ditolak</strong>.
        </div>';
    }
}

// --- FILTER & PAGINATION ---
$search   = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$per_page = 15;
$page     = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset   = ($page - 1) * $per_page;

$where = "WHERE lm.sdm_status = 'Disetujui' AND lm.manager_status = 'Menunggu'";
if ($search) $where .= " AND (p.nama LIKE '%$search%' OR lm.username LIKE '%$search%' OR p.asal_sekolah LIKE '%$search%')";

$total_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM laporan_magang lm
     JOIN peserta p ON lm.username = p.username $where"));
$total      = $total_row['total'];
$total_page = ceil($total / $per_page);

$data = mysqli_query($conn, "
    SELECT lm.*, p.nama, p.asal_sekolah, p.jurusan, p.status_peserta,
           p.tgl_masuk, p.tgl_keluar,
           b.bidang AS nama_bidang,
           u.nama AS sdm_reviewer_nama
    FROM laporan_magang lm
    JOIN peserta p ON lm.username = p.username
    LEFT JOIN bidang b ON p.bidang_id = b.id
    LEFT JOIN users u ON lm.sdm_reviewed_by = u.username
    $where
    ORDER BY lm.sdm_tgl_review ASC
    LIMIT $per_page OFFSET $offset
");

// Summary
$sum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu,
        SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui,
        SUM(manager_status='Ditolak') as ditolak
    FROM laporan_magang
"));
?>
<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Approval</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Laporan Manager</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?= number_format($sum['total'], 0, ',', '.') ?></div>
                <div class="small text-muted">Total Laporan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= number_format($sum['menunggu'], 0, ',', '.') ?></div>
                <div class="small text-muted">Menunggu Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-success border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= number_format($sum['disetujui'], 0, ',', '.') ?></div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger"><?= number_format($sum['ditolak'], 0, ',', '.') ?></div>
                <div class="small text-muted">Ditolak</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-check-shield me-1"></i> Approval Laporan Manager</h5>
        </div>

        <form method="GET" action="index.php" class="row g-2 mb-3">
            <input type="hidden" name="page" value="approval_laporan">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama / username..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="index.php?page=approval_laporan" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Periode Magang</th>
                        <th>Bidang</th>
                        <th>File Laporan</th>
                        <th>Di-Review SDM</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $offset + 1;
                while ($row = mysqli_fetch_assoc($data)):
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama']) ?></strong>
                            <small class="text-muted d-block"><code><?= htmlspecialchars($row['username']) ?></code></small>
                        </td>
                        <td>
                            <small><?= htmlspecialchars($row['asal_sekolah']) ?></small><br>
                            <small class="text-muted"><?= htmlspecialchars($row['jurusan']) ?></small>
                        </td>
                        <td class="text-nowrap">
                            <small>
                                <?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?><br>
                                <span class="text-muted">s/d</span>
                                <?= date('d/m/Y', strtotime($row['tgl_keluar'])) ?>
                            </small>
                        </td>
                        <td>
                            <?= $row['nama_bidang']
                                ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($row['nama_bidang']) . '</span>'
                                : '<em class="text-muted">-</em>' ?>
                        </td>
                        <td>
                            <a href="../uploads/laporan/<?= htmlspecialchars($row['file_laporan']) ?>"
                               target="_blank" class="btn btn-sm btn-outline-primary" title="Buka laporan">
                                <i class="bx bxs-file-pdf"></i> Lihat
                            </a>
                        </td>
                        <td class="text-nowrap">
                            <small><?= date('d/m/Y H:i', strtotime($row['sdm_tgl_review'])) ?></small>
                            <small class="d-block text-muted">oleh: <?= htmlspecialchars($row['sdm_reviewer_nama'] ?? '-') ?></small>
                        </td>
                        <td class="text-center text-nowrap">
                            <form method="POST" action="index.php?page=approval_laporan" class="d-inline"
                                  onsubmit="return confirm('Setujui laporan <?= htmlspecialchars(addslashes($row['nama'])) ?>?\nSertifikat digital akan langsung diterbitkan.')">
                                <input type="hidden" name="laporan_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']) ?>">
                                <button type="submit" name="submit_setuju" class="btn btn-sm btn-success me-1" title="Setujui & Terbitkan Sertifikat">
                                    <i class="bx bx-check"></i> Setujui
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger btnTolak"
                                    data-id="<?= $row['id'] ?>"
                                    data-nama="<?= htmlspecialchars($row['nama']) ?>">
                                <i class="bx bx-x"></i> Tolak
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada laporan yang menunggu approval manager.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_page > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">Menampilkan <?= $offset + 1 ?>-<?= min($offset + $per_page, $total) ?> dari <?= $total ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=approval_laporan&p=<?= $page-1 ?>&q=<?= urlencode($search) ?>"><i class="bx bx-chevron-left"></i></a>
                    </li>
                    <?php for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++): ?>
                        <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=approval_laporan&p=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_page ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=approval_laporan&p=<?= $page+1 ?>&q=<?= urlencode($search) ?>"><i class="bx bx-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL TOLAK -->
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=approval_laporan">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bx bxs-x-circle me-2"></i>Tolak Laporan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="laporan_id" id="modalLaporanId">
                    <input type="hidden" name="username" id="modalUsername">
                    <p class="mb-3">Tolak laporan dari: <strong id="modalNamaPeserta"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Keterangan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="keterangan_tolak" class="form-control" rows="4" placeholder="Jelaskan alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_tolak" class="btn btn-danger"><i class="bx bx-x me-1"></i> Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btnTolak').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modalLaporanId').value = this.dataset.id;
        document.getElementById('modalNamaPeserta').textContent = this.dataset.nama;
        var modal = new bootstrap.Modal(document.getElementById('modalTolak'));
        modal.show();
    });
});
</script>

<?php
// ============================================================
// AKSES: PUSAT
// page: cetak_sertifikat
// ============================================================
include "../conn/conn.php";

$message  = '';
$uploader = $_SESSION['username'];
$esc_up   = mysqli_real_escape_string($conn, $uploader);

// --- UPLOAD SERTIFIKAT SCAN ---
if (isset($_POST['submit_upload_scan'])) {
    $laporan_id = (int)$_POST['laporan_id'];
    $sertif_id  = (int)$_POST['sertifikat_id'];
    $upload_dir = '../uploads/sertifikat/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $errors = array();

    if (!isset($_FILES['file_sertifikat']) || $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Pilih file sertifikat terlebih dahulu.';
    } else {
        $file      = $_FILES['file_sertifikat'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp  = $file['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Cek MIME type
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $allowed_ext  = array('pdf', 'jpg', 'jpeg', 'png');
        $allowed_mime = array('application/pdf', 'image/jpeg', 'image/png');

        if (!in_array($file_ext, $allowed_ext) || !in_array($mime_type, $allowed_mime)) {
            $errors[] = 'File harus berformat <strong>PDF, JPG, atau PNG</strong>.';
        }

        if ($file_size > 10 * 1024 * 1024) {
            $errors[] = 'Ukuran file maksimal <strong>10 MB</strong>.';
        }
    }

    if (!empty($errors)) {
        $error_items = '';
        foreach ($errors as $e) { $error_items .= "<li>$e</li>"; }
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i>
            <ul class="mb-0 ps-3">' . $error_items . '</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        // Ambil username dari tabel sertifikat
        $qSertif   = mysqli_query($conn, "SELECT username FROM sertifikat_magang WHERE id = $sertif_id");
        $sertif_row = $qSertif ? mysqli_fetch_assoc($qSertif) : null;

        if (!$sertif_row) {
            $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                <i class="bx bxs-error-circle me-2"></i> Data sertifikat tidak ditemukan. Pastikan sudah mencetak sertifikat terlebih dahulu.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } else {
            $esc_suser  = mysqli_real_escape_string($conn, $sertif_row['username']);
            $saved_name = 'sertif_' . $esc_suser . '_' . time() . '.' . $file_ext;
            $saved_path = $upload_dir . $saved_name;

            if (move_uploaded_file($file_tmp, $saved_path)) {
                $esc_saved = mysqli_real_escape_string($conn, $saved_name);
                $esc_ori   = mysqli_real_escape_string($conn, $file_name);

                $qUpd = mysqli_query($conn, "UPDATE sertifikat_magang SET
                    file_sertifikat  = '$esc_saved',
                    nama_file_scan   = '$esc_ori',
                    ukuran_file_scan = $file_size,
                    status           = 'Sudah Upload',
                    tgl_upload_scan  = NOW(),
                    uploaded_by      = '$esc_up'
                    WHERE id = $sertif_id");

                if ($qUpd) {
                    $message = '<div class="alert alert-success alert-dismissible fade show py-2">
                        <i class="bx bxs-check-circle me-2"></i> Sertifikat scan berhasil <strong>diupload</strong>. Peserta dapat mengunduhnya sekarang.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                } else {
                    $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                        <i class="bx bxs-error-circle me-2"></i> Gagal update database: ' . mysqli_error($conn) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                }
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                    <i class="bx bxs-error-circle me-2"></i> Gagal menyimpan file. Periksa permission folder uploads/sertifikat/.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }
}

// --- FILTER & PAGINATION ---
$search   = isset($_GET['q'])      ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$filter   = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$per_page = 15;
$page     = isset($_GET['p'])      ? max(1, (int)$_GET['p']) : 1;
$offset   = ($page - 1) * $per_page;

$where = "WHERE lm.status = 'Disetujui'";
if ($search) $where .= " AND (p.nama LIKE '%$search%' OR lm.username LIKE '%$search%' OR p.asal_sekolah LIKE '%$search%')";
if ($filter === 'belum') $where .= " AND (sm.id IS NULL OR sm.status = 'Belum Upload')";
if ($filter === 'sudah') $where .= " AND sm.status = 'Sudah Upload'";

$total_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total
     FROM laporan_magang lm
     JOIN peserta p ON lm.username = p.username
     LEFT JOIN sertifikat_magang sm ON lm.username = sm.username
     $where"));
$total      = $total_row['total'];
$total_page = ceil($total / $per_page);

$data = mysqli_query($conn, "
    SELECT lm.id AS laporan_id, lm.username, lm.tgl_review,
           p.nama, p.asal_sekolah, p.jurusan, p.status_peserta,
           p.tgl_masuk, p.tgl_keluar, p.unit,
           b.bidang AS nama_bidang,
           sm.id AS sertif_id, sm.nomor_surat, sm.status AS status_sertif,
           sm.file_sertifikat, sm.nama_file_scan, sm.ukuran_file_scan,
           sm.tgl_cetak, sm.tgl_upload_scan
    FROM laporan_magang lm
    JOIN peserta p ON lm.username = p.username
    LEFT JOIN bidang b ON p.bidang_id = b.id
    LEFT JOIN sertifikat_magang sm ON lm.username = sm.username
    $where
    ORDER BY lm.tgl_review DESC
    LIMIT $per_page OFFSET $offset
");

// Ringkasan
$sum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(DISTINCT lm.username) as total_disetujui,
        SUM(CASE WHEN sm.status='Sudah Upload' THEN 1 ELSE 0 END) as sudah_upload,
        SUM(CASE WHEN sm.id IS NULL OR sm.status='Belum Upload' THEN 1 ELSE 0 END) as belum_upload
    FROM laporan_magang lm
    LEFT JOIN sertifikat_magang sm ON lm.username = sm.username
    WHERE lm.status = 'Disetujui'
"));
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Sertifikat</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Cetak & Kelola Sertifikat</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?= $sum['total_disetujui'] ?></div>
                <div class="small text-muted">Laporan Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger"><?= $sum['belum_upload'] ?></div>
                <div class="small text-muted">Belum Upload Scan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= $sum['sudah_upload'] ?></div>
                <div class="small text-muted">Sudah Upload Scan</div>
            </div>
        </div>
    </div>
</div>

<!-- ALUR INFO -->
<div class="alert alert-info d-flex gap-3 align-items-start mb-4">
    <i class="bx bx-info-circle fs-4 mt-1 flex-shrink-0"></i>
    <div class="small">
        <strong>Alur Kerja Sertifikat:</strong>
        <span class="badge bg-primary mx-1">1</span> Cetak sertifikat PDF &rarr;
        <span class="badge bg-primary mx-1">2</span> Minta tanda tangan pejabat &rarr;
        <span class="badge bg-primary mx-1">3</span> Scan sertifikat &rarr;
        <span class="badge bg-primary mx-1">4</span> Upload scan di halaman ini &rarr;
        <span class="badge bg-primary mx-1">5</span> Peserta dapat mengunduh.
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="bx bxs-award me-1"></i> Daftar Peserta – Laporan Disetujui</h5>
        </div>

        <!-- SEARCH & FILTER -->
        <form method="GET" action="index.php" class="row g-2 mb-3">
            <input type="hidden" name="page" value="cetak_sertifikat">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control"
                           placeholder="Cari nama / username / asal sekolah..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-auto">
                <select name="filter" class="form-select form-select-sm">
                    <option value="semua" <?= $filter==='semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="belum" <?= $filter==='belum' ? 'selected' : '' ?>>Belum Upload Scan</option>
                    <option value="sudah" <?= $filter==='sudah' ? 'selected' : '' ?>>Sudah Upload Scan</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="index.php?page=cetak_sertifikat" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Bidang / Unit</th>
                        <th>Periode Magang</th>
                        <th>Status Sertifikat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $offset + 1;
                while ($row = mysqli_fetch_assoc($data)):
                    $has_sertif   = !empty($row['sertif_id']);
                    $sudah_upload = $has_sertif && $row['status_sertif'] === 'Sudah Upload';
                    $size_label   = '';
                    if ($sudah_upload && $row['ukuran_file_scan']) {
                        $size_label = $row['ukuran_file_scan'] > 1024*1024
                            ? round($row['ukuran_file_scan']/1024/1024, 2) . ' MB'
                            : round($row['ukuran_file_scan']/1024, 1) . ' KB';
                    }
                    $nama_bidang = isset($row['nama_bidang']) ? $row['nama_bidang'] : '';
                    $unit        = isset($row['unit'])        ? $row['unit']        : '';
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama']) ?></strong>
                            <small class="text-muted d-block"><code><?= htmlspecialchars($row['username']) ?></code></small>
                            <span class="badge bg-<?= $row['status_peserta']=='Siswa' ? 'info' : 'primary' ?> mt-1">
                                <?= $row['status_peserta'] ?>
                            </span>
                        </td>
                        <td>
                            <small><?= htmlspecialchars($row['asal_sekolah']) ?></small><br>
                            <small class="text-muted"><?= htmlspecialchars($row['jurusan']) ?></small>
                        </td>
                        <td>
                            <?= $nama_bidang ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($nama_bidang) . '</span>' : '<em class="text-muted">-</em>' ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($unit) ?></small>
                        </td>
                        <td class="text-nowrap">
                            <small>
                                <?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?><br>
                                <span class="text-muted">s/d</span>
                                <?= date('d/m/Y', strtotime($row['tgl_keluar'])) ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($sudah_upload): ?>
                                <span class="badge bg-success"><i class="bx bxs-check-circle me-1"></i>Sudah Upload</span>
                                <small class="text-muted d-block mt-1">
                                    <?= date('d/m/Y', strtotime($row['tgl_upload_scan'])) ?>
                                    <?= $size_label ? '&mdash; ' . $size_label : '' ?>
                                </small>
                                <?php if ($row['nomor_surat']): ?>
                                    <small class="text-muted d-block">No: <?= htmlspecialchars($row['nomor_surat']) ?></small>
                                <?php endif; ?>
                            <?php elseif ($has_sertif): ?>
                                <span class="badge bg-warning text-dark"><i class="bx bx-time-five me-1"></i>Menunggu Scan</span>
                                <small class="text-muted d-block mt-1">Dicetak: <?= date('d/m/Y', strtotime($row['tgl_cetak'])) ?></small>
                                <?php if ($row['nomor_surat']): ?>
                                    <small class="text-muted d-block">No: <?= htmlspecialchars($row['nomor_surat']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bx bx-printer me-1"></i>Belum Cetak</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <!-- Tombol Cetak -->
                            <a href="print_sertifikat.php?laporan_id=<?= $row['laporan_id'] ?>&username=<?= urlencode($row['username']) ?>"
                               target="_blank"
                               class="btn btn-sm btn-outline-primary me-1"
                               onclick="return confirmCetak('<?= htmlspecialchars(addslashes($row['nama'])) ?>', <?= $has_sertif ? 'true' : 'false' ?>)">
                                <i class="bx bx-printer me-1"></i>Cetak
                            </a>

                            <!-- Tombol Upload Scan -->
                            <?php if ($has_sertif): ?>
                                <button type="button"
                                        class="btn btn-sm <?= $sudah_upload ? 'btn-outline-success' : 'btn-warning' ?> btnUploadScan"
                                        data-id="<?= $row['sertif_id'] ?>"
                                        data-laporan="<?= $row['laporan_id'] ?>"
                                        data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                        data-file="<?= $sudah_upload ? htmlspecialchars($row['nama_file_scan']) : '' ?>">
                                    <i class="bx <?= $sudah_upload ? 'bx-refresh' : 'bx-upload' ?> me-1"></i>
                                    <?= $sudah_upload ? 'Ganti' : 'Upload Scan' ?>
                                </button>
                                <?php if ($sudah_upload): ?>
                                    <a href="../uploads/sertifikat/<?= htmlspecialchars($row['file_sertifikat']) ?>"
                                       target="_blank" class="btn btn-sm btn-success ms-1" title="Lihat">
                                        <i class="bx bx-show"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary ms-1" disabled title="Cetak sertifikat dulu">
                                    <i class="bx bx-upload me-1"></i>Upload Scan
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada data yang sesuai.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_page > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> dari <?= $total ?> peserta
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=cetak_sertifikat&p=<?= $page-1 ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>">
                            <i class="bx bx-chevron-left"></i></a>
                    </li>
                    <?php for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++): ?>
                        <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=cetak_sertifikat&p=<?= $i ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_page ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=cetak_sertifikat&p=<?= $page+1 ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>">
                            <i class="bx bx-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL UPLOAD SCAN -->
<div class="modal fade" id="modalUploadScan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- PENTING: enctype multipart/form-data wajib ada untuk upload file -->
            <form method="POST" action="index.php?page=cetak_sertifikat" enctype="multipart/form-data" id="formUploadScan">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bx bx-upload me-2"></i>Upload Sertifikat Scan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sertifikat_id" id="modalSertifId">
                    <input type="hidden" name="laporan_id"    id="modalLaporanId">

                    <p class="mb-3">Upload sertifikat untuk: <strong id="modalNamaPeserta"></strong></p>

                    <div id="infoSertifLama" class="alert alert-warning py-2 small d-none mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        File saat ini: <strong id="namaFileLama"></strong>.
                        Upload baru akan <strong>menggantikan</strong> file lama.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Sertifikat yang Sudah Ditandatangani <span class="text-danger">*</span></label>
                        <input type="file"
                               name="file_sertifikat"
                               id="fileSertifikat"
                               class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png"
                               required>
                        <div class="form-text">
                            <i class="bx bx-info-circle"></i>
                            Format: <strong>PDF, JPG, atau PNG</strong> &mdash; Maksimal: <strong>10 MB</strong>
                        </div>
                    </div>

                    <div id="scanFileInfo" class="alert alert-light border py-2 d-none">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bx bxs-file-pdf text-danger fs-4" id="scanFileIcon"></i>
                            <div>
                                <div id="scanFileName" class="fw-semibold small"></div>
                                <div id="scanFileSize" class="text-muted small"></div>
                            </div>
                        </div>
                        <div id="scanSizeWarn" class="text-danger small mt-1 d-none">
                            <i class="bx bxs-error-circle"></i> Ukuran melebihi 10 MB!
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_upload_scan" id="btnSubmitScan" class="btn btn-primary">
                        <i class="bx bx-cloud-upload me-1"></i> Upload Sertifikat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================================
// KONFIRMASI CETAK
// ============================================================
function confirmCetak(nama, sudahCetak) {
    if (sudahCetak) {
        return confirm('Sertifikat ' + nama + ' sudah pernah dicetak.\nMencetak ulang akan menghasilkan nomor surat BARU.\n\nLanjutkan?');
    }
    return true;
}

// ============================================================
// EVENT DELEGATION untuk tombol Upload Scan
// Dipasang di document supaya tetap jalan setelah reload
// ============================================================
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btnUploadScan');
    if (!btn) return;

    var sertifId = btn.getAttribute('data-id');
    var laporanId = btn.getAttribute('data-laporan');
    var nama = btn.getAttribute('data-nama');
    var namaFile = btn.getAttribute('data-file');

    document.getElementById('modalSertifId').value = sertifId;
    document.getElementById('modalLaporanId').value = laporanId;
    document.getElementById('modalNamaPeserta').textContent = nama;

    var infoLama = document.getElementById('infoSertifLama');
    if (namaFile && namaFile !== '') {
        document.getElementById('namaFileLama').textContent = namaFile;
        infoLama.classList.remove('d-none');
    } else {
        infoLama.classList.add('d-none');
    }

    // Reset form
    document.getElementById('fileSertifikat').value = '';
    document.getElementById('scanFileInfo').classList.add('d-none');
    document.getElementById('btnSubmitScan').disabled = false;

    var modal = new bootstrap.Modal(document.getElementById('modalUploadScan'));
    modal.show();
});

// ============================================================
// PREVIEW FILE SCAN
// ============================================================
document.addEventListener('change', function(e) {
    if (e.target.id !== 'fileSertifikat') return;
    var file    = e.target.files[0];
    var info    = document.getElementById('scanFileInfo');
    var icon    = document.getElementById('scanFileIcon');
    var name    = document.getElementById('scanFileName');
    var size    = document.getElementById('scanFileSize');
    var warn    = document.getElementById('scanSizeWarn');
    var btn     = document.getElementById('btnSubmitScan');
    var maxSize = 10 * 1024 * 1024;

    if (file) {
        info.classList.remove('d-none');
        name.textContent = file.name;
        size.textContent = file.size > 1024*1024
            ? (file.size/1024/1024).toFixed(2) + ' MB'
            : (file.size/1024).toFixed(1) + ' KB';
        var ext = file.name.split('.').pop().toLowerCase();
        icon.className = (ext === 'pdf') ? 'bx bxs-file-pdf text-danger fs-4' : 'bx bxs-image text-primary fs-4';
        if (file.size > maxSize) {
            warn.classList.remove('d-none');
            info.classList.add('border-danger');
            btn.disabled = true;
        } else {
            warn.classList.add('d-none');
            info.classList.remove('border-danger');
            btn.disabled = false;
        }
    } else {
        info.classList.add('d-none');
    }
});

// ============================================================
// AUTO REFRESH setelah cetak - pakai sessionStorage
// Supaya hanya reload SEKALI, bukan setiap kali focus
// ============================================================
document.addEventListener('click', function(e) {
    var link = e.target.closest('a[href*="print_sertifikat.php"]');
    if (link) {
        sessionStorage.setItem('cetakDiklik', '1');
    }
});

window.addEventListener('focus', function() {
    if (sessionStorage.getItem('cetakDiklik') === '1') {
        sessionStorage.removeItem('cetakDiklik');
        setTimeout(function() {
            window.location.reload();
        }, 1200);
    }
});
</script>
<?php
// ============================================================
// AKSES: SDM
// Asumsi: $_SESSION['username'] berisi username user sdm yang login
// ============================================================
include "../conn/conn.php";

$message      = '';
$reviewer     = $_SESSION['username'];
$esc_reviewer = mysqli_real_escape_string($conn, $reviewer);

// ── Data libur untuk hitung hari kerja ──
$libur_pekan = [];
$q_lp = mysqli_query($conn, "SELECT hari_index FROM libur_pekan");
while ($r_lp = mysqli_fetch_assoc($q_lp)) $libur_pekan[] = (int)$r_lp['hari_index'];

$hari_libur = [];
$q_hl = mysqli_query($conn, "SELECT tanggal FROM hari_libur");
while ($r_hl = mysqli_fetch_assoc($q_hl)) $hari_libur[] = $r_hl['tanggal'];

function hitungHariKerja($tgl_masuk, $tgl_keluar, $libur_pekan, $hari_libur) {
    if (empty($tgl_masuk) || empty($tgl_keluar) || $tgl_masuk == '0000-00-00' || $tgl_keluar == '0000-00-00') return 0;
    $start = new DateTime($tgl_masuk);
    $end   = new DateTime($tgl_keluar);
    $end->modify('+1 day');
    $total = 0;
    foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $date) {
        if (in_array((int)$date->format('w'), $libur_pekan)) continue;
        if (in_array($date->format('Y-m-d'), $hari_libur)) continue;
        $total++;
    }
    return $total;
}

// --- SETUJUI ---
if (isset($_POST['submit_setuju'])) {
    $laporan_id = (int) $_POST['laporan_id'];
    mysqli_query($conn, "UPDATE laporan_magang SET
        sdm_status       = 'Disetujui',
        sdm_reviewed_by  = '$esc_reviewer',
        sdm_tgl_review   = NOW(),
        sdm_keterangan_tolak = NULL,
        status             = 'Menunggu Manager'
        WHERE id = $laporan_id");

    $message = '<div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bx bxs-check-circle me-2"></i> Laporan berhasil <strong>Disetujui</strong> (menunggu approval Manager).
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// --- TOLAK ---
if (isset($_POST['submit_tolak'])) {
    $laporan_id       = (int) $_POST['laporan_id'];
    $keterangan_tolak = mysqli_real_escape_string($conn, trim($_POST['keterangan_tolak']));

    if (empty($keterangan_tolak)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Keterangan penolakan <strong>wajib diisi</strong>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        mysqli_query($conn, "UPDATE laporan_magang SET
            sdm_status           = 'Ditolak',
            sdm_keterangan_tolak = '$keterangan_tolak',
            sdm_tgl_review       = NOW(),
            sdm_reviewed_by      = '$esc_reviewer',
            status                 = 'Ditolak'
            WHERE id = $laporan_id");

        $message = '<div class="alert alert-warning alert-dismissible fade show py-2">
            <i class="bx bxs-x-circle me-2"></i> Laporan berhasil <strong>Ditolak</strong>. Peserta akan melihat keterangan penolakan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// --- FILTER & PAGINATION ---
// Hanya tampilkan laporan yang BELUM direview (status Menunggu)
$search   = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$per_page = 15;
$page     = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset   = ($page - 1) * $per_page;

$where = "WHERE lm.sdm_status = 'Menunggu'";
if ($search) $where .= " AND (p.nama LIKE '%$search%' OR lm.username LIKE '%$search%' OR p.asal_sekolah LIKE '%$search%')";

$total_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM laporan_magang lm
     JOIN peserta p ON lm.username = p.username
     $where"
));
$total      = $total_row['total'];
$total_page = ceil($total / $per_page);

$data = mysqli_query($conn, "
    SELECT lm.*, p.nama, p.asal_sekolah, p.jurusan, p.tgl_masuk, p.tgl_keluar,
           p.status_peserta, b.bidang AS nama_bidang,
           u.nama AS reviewer_nama,
           (SELECT COUNT(*) FROM absensi_peserta ap
            WHERE ap.username = lm.username
              AND ap.status = 'Hadir'
              AND ap.tanggal BETWEEN p.tgl_masuk AND p.tgl_keluar) as total_hadir
    FROM laporan_magang lm
    JOIN peserta p ON lm.username = p.username
    LEFT JOIN bidang b ON p.bidang_id = b.id
    LEFT JOIN users u ON lm.sdm_reviewed_by = u.username
    $where
    ORDER BY lm.tgl_upload ASC
    LIMIT $per_page OFFSET $offset
");

// Ringkasan status
$sum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(sdm_status='Menunggu') as menunggu,
        SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu_manager,
        SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui,
        SUM(sdm_status='Ditolak' OR manager_status='Ditolak') as ditolak
    FROM laporan_magang
"));
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Laporan Magang</li>
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
                <div class="fs-2 fw-bold text-primary"><?= $sum['total'] ?></div>
                <div class="small text-muted">Total Laporan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= $sum['menunggu'] ?></div>
                <div class="small text-muted">Menunggu Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-info border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-info"><?= $sum['menunggu_manager'] ?></div>
                <div class="small text-muted">Menunggu Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= $sum['disetujui'] ?></div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-task me-1"></i> Laporan Menunggu Review</h5>
        </div>

        <!-- SEARCH -->
        <form method="GET" action="index.php" class="row g-2 mb-3">
            <input type="hidden" name="page" value="approval_laporan">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control"
                           placeholder="Cari nama / username / asal sekolah..."
                           value="<?= htmlspecialchars($search) ?>">
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
                        <th>Tgl Upload</th>
                        <th>Kehadiran</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $offset + 1;
                $st_badge = ['Menunggu' => 'warning', 'Menunggu Manager' => 'info', 'Disetujui' => 'success', 'Ditolak' => 'danger'];
                while ($row = mysqli_fetch_assoc($data)):
                    $badge      = $st_badge[$row['status']] ?? 'secondary';
                    $size_label = $row['ukuran_file'] > 1024*1024
                        ? round($row['ukuran_file']/1024/1024, 2) . ' MB'
                        : round($row['ukuran_file']/1024, 1) . ' KB';
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
                                <br>
                            <small class="text-muted"><?= htmlspecialchars($row['tgl_keluar']) ?></small>
                        </td>
                        <td>
                            <a href="../uploads/laporan/<?= htmlspecialchars($row['file_laporan']) ?>"
                               target="_blank" class="btn btn-sm btn-outline-primary" title="Buka laporan">
                                <i class="bx bxs-file-pdf me-1"></i><?= $size_label ?>
                            </a>
                            <small class="text-muted d-block mt-1">
                                <?= htmlspecialchars(mb_strimwidth($row['nama_file'], 0, 28, '...')) ?>
                            </small>
                        </td>
                        <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($row['tgl_upload'])) ?></td>
                        <td class="text-nowrap" style="min-width:120px;">
                            <?php
                            $total_hadir    = (int)($row['total_hadir'] ?? 0);
                            $total_hari_kerja = hitungHariKerja($row['tgl_masuk'], $row['tgl_keluar'], $libur_pekan, $hari_libur);
                            if ($total_hari_kerja > 0):
                                $pct     = min(100, round(($total_hadir / $total_hari_kerja) * 100));
                                $pct_cls = $pct >= 80 ? 'bg-success' : ($pct >= 60 ? 'bg-warning' : 'bg-danger');
                            ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-weight:600; font-size:14px;"><?= $pct ?>%</span>
                                    <div class="progress" style="width:55px; height:6px; margin:0;">
                                        <div class="progress-bar <?= $pct_cls ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </div>
                                <small class="text-muted">Hadir: <?= $total_hadir ?>/<?= $total_hari_kerja ?> hari</small>
                            <?php else: ?>
                                <small class="text-muted"><em>--</em></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <form method="POST" action="index.php?page=approval_laporan" class="d-inline"
                                  onsubmit="return confirm('Setujui laporan <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                <input type="hidden" name="laporan_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="submit_setuju" class="btn btn-sm btn-success me-1" title="Setujui">
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
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada laporan yang menunggu review.
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
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> dari <?= $total ?> laporan
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=approval_laporan&p=<?= $page-1 ?>&q=<?= urlencode($search) ?>">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++): ?>
                        <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=approval_laporan&p=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_page ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=approval_laporan&p=<?= $page+1 ?>&q=<?= urlencode($search) ?>">
                            <i class="bx bx-chevron-right"></i>
                        </a>
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
                    <p class="mb-3">Tolak laporan dari: <strong id="modalNamaPeserta"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Keterangan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="keterangan_tolak"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Jelaskan alasan penolakan (akan tampil ke peserta)..."
                                  required></textarea>
                        <div class="form-text"><i class="bx bx-info-circle"></i> Keterangan ini akan ditampilkan kepada peserta.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_tolak" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Tolak Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btnTolak').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modalLaporanId').value        = this.dataset.id;
        document.getElementById('modalNamaPeserta').textContent = this.dataset.nama;
        document.querySelector('#modalTolak textarea[name="keterangan_tolak"]').value = '';
        var modal = new bootstrap.Modal(document.getElementById('modalTolak'));
        modal.show();
    });
});
</script>
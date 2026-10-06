<?php
include "../conn/conn.php";

// --- FILTER & PAGINATION ---
$search   = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$filter   = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$per_page = 15;
$page     = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset   = ($page - 1) * $per_page;

$where = "WHERE lm.sdm_status = 'Disetujui'";
if ($filter === 'menunggu') $where .= " AND lm.manager_status = 'Menunggu'";
if ($filter === 'disetujui') $where .= " AND lm.manager_status = 'Disetujui'";
if ($filter === 'ditolak') $where .= " AND lm.manager_status = 'Ditolak'";
if ($search) $where .= " AND (p.nama LIKE '%$search%' OR lm.username LIKE '%$search%')";

$total_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM laporan_magang lm
     JOIN peserta p ON lm.username = p.username $where"));
$total      = $total_row['total'];
$total_page = ceil($total / $per_page);

$data = mysqli_query($conn, "
    SELECT lm.*, p.nama, p.asal_sekolah, p.jurusan, p.tgl_masuk, p.tgl_keluar,
           u1.nama AS sdm_reviewer_nama,
           u2.nama AS manager_reviewer_nama
    FROM laporan_magang lm
    JOIN peserta p ON lm.username = p.username
    LEFT JOIN users u1 ON lm.sdm_reviewed_by = u1.username
    LEFT JOIN users u2 ON lm.manager_reviewed_by = u2.username
    $where
    ORDER BY lm.sdm_tgl_review DESC
    LIMIT $per_page OFFSET $offset
");

$sum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(manager_status='Menunggu') as menunggu,
        SUM(manager_status='Disetujui') as disetujui,
        SUM(manager_status='Ditolak') as ditolak
    FROM laporan_magang WHERE sdm_status='Disetujui'
"));
?>
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Manager</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Status Approval Manager</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary"><?= $sum['total'] ?></div>
                <div class="small text-muted">Total ke Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= $sum['menunggu'] ?></div>
                <div class="small text-muted">Menunggu Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= $sum['disetujui'] ?></div>
                <div class="small text-muted">Disetujui Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger"><?= $sum['ditolak'] ?></div>
                <div class="small text-muted">Ditolak Manager</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-task me-1"></i> Status Laporan di Manager</h5>
        </div>

        <form method="GET" action="index.php" class="row g-2 mb-3">
            <input type="hidden" name="page" value="approval_laporan_manager">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama / username..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-auto">
                <select name="filter" class="form-select form-select-sm">
                    <option value="semua" <?= $filter==='semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="menunggu" <?= $filter==='menunggu' ? 'selected' : '' ?>>Menunggu</option>
                    <option value="disetujui" <?= $filter==='disetujui' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="ditolak" <?= $filter==='ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="index.php?page=approval_laporan_manager" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
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
                        <th>Review SDM</th>
                        <th>Status Manager</th>
                        <th>Review Manager</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $offset + 1;
                while ($row = mysqli_fetch_assoc($data)):
                    $st_badge = ['Menunggu' => 'warning', 'Disetujui' => 'success', 'Ditolak' => 'danger'];
                    $badge = $st_badge[$row['manager_status']] ?? 'secondary';
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama']) ?></strong>
                            <small class="text-muted d-block"><code><?= htmlspecialchars($row['username']) ?></code></small>
                        </td>
                        <td><small><?= htmlspecialchars($row['asal_sekolah']) ?></small></td>
                        <td class="text-nowrap">
                            <small>
                                <?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?><br>
                                <span class="text-muted">s/d</span>
                                <?= date('d/m/Y', strtotime($row['tgl_keluar'])) ?>
                            </small>
                        </td>
                        <td class="text-nowrap">
                            <small><?= date('d/m/Y H:i', strtotime($row['sdm_tgl_review'])) ?></small>
                            <small class="d-block text-muted">oleh: <?= htmlspecialchars($row['sdm_reviewer_nama'] ?? '-') ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?php if ($row['manager_status'] === 'Disetujui'): ?>
                                    <i class="bx bxs-check-circle me-1"></i>
                                <?php elseif ($row['manager_status'] === 'Ditolak'): ?>
                                    <i class="bx bxs-x-circle me-1"></i>
                                <?php else: ?>
                                    <i class="bx bx-time-five me-1"></i>
                                <?php endif; ?>
                                <?= $row['manager_status'] ?>
                            </span>
                            <?php if ($row['manager_keterangan_tolak']): ?>
                                <small class="d-block text-danger mt-1" title="<?= htmlspecialchars($row['manager_keterangan_tolak']) ?>">
                                    <i class="bx bx-info-circle"></i> <?= htmlspecialchars(mb_strimwidth($row['manager_keterangan_tolak'], 0, 30, '...')) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($row['manager_tgl_review']): ?>
                                <small><?= date('d/m/Y H:i', strtotime($row['manager_tgl_review'])) ?></small>
                                <small class="d-block text-muted">oleh: <?= htmlspecialchars($row['manager_reviewer_nama'] ?? '-') ?></small>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total == 0): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_page > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Menampilkan <?= $offset + 1 ?>-<?= min($offset + $per_page, $total) ?> dari <?= $total ?></small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=approval_laporan_manager&p=<?= $page-1 ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>"><i class="bx bx-chevron-left"></i></a>
                    </li>
                    <?php for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++): ?>
                        <li class="page-item <?= $i==$page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=approval_laporan_manager&p=<?= $i ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_page ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=approval_laporan_manager&p=<?= $page+1 ?>&q=<?= urlencode($search) ?>&filter=<?= $filter ?>"><i class="bx bx-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

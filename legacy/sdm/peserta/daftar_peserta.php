<?php
include "../conn/conn.php";

$message = '';

// --- HAPUS ---
if (isset($_GET['hapus'])) {
    $id  = (int) $_GET['hapus'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username, nama FROM peserta WHERE id = $id"));
    if ($row) {
        $u = mysqli_real_escape_string($conn, $row['username']);
        mysqli_query($conn, "DELETE FROM peserta WHERE id = $id");
        mysqli_query($conn, "DELETE FROM users WHERE username = '$u' AND role = 'peserta'");
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Peserta <strong>' . htmlspecialchars($row['nama']) . '</strong> berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// --- PAGINATION & SEARCH ---
$search        = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$bulan_dari    = isset($_GET['bulan_dari']) && $_GET['bulan_dari'] ? str_pad($_GET['bulan_dari'], 2, '0', STR_PAD_LEFT) : '';
$tahun_dari    = isset($_GET['tahun_dari']) && $_GET['tahun_dari'] ? (int) $_GET['tahun_dari'] : 0;
$bulan_sampai  = isset($_GET['bulan_sampai']) && $_GET['bulan_sampai'] ? str_pad($_GET['bulan_sampai'], 2, '0', STR_PAD_LEFT) : '';
$tahun_sampai  = isset($_GET['tahun_sampai']) && $_GET['tahun_sampai'] ? (int) $_GET['tahun_sampai'] : 0;
$per_page     = 15;
$page         = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset       = ($page - 1) * $per_page;

$where = "WHERE 1=1";
if ($search)        $where .= " AND (p.nama LIKE '%$search%' OR p.username LIKE '%$search%' OR p.asal_sekolah LIKE '%$search%')";
if ($status_filter) $where .= " AND p.status_magang = '$status_filter'";

// Gabung bulan + tahun jadi format YYYY-MM untuk filter tanggal
$from_date = ($bulan_dari && $tahun_dari) ? $tahun_dari . '-' . $bulan_dari : '';
$to_date   = ($bulan_sampai && $tahun_sampai) ? $tahun_sampai . '-' . $bulan_sampai : '';

if ($from_date && $to_date) {
    try {
        $start = new DateTime($from_date . '-01');
        $end   = new DateTime($to_date . '-01');
        $end->modify('last day of this month');
        $where .= " AND NOT (p.tgl_keluar < '" . $start->format('Y-m-d') . "' OR p.tgl_masuk > '" . $end->format('Y-m-d') . "')";
    } catch (Exception $e) {}
} elseif ($from_date) {
    try {
        $start = new DateTime($from_date . '-01');
        $end   = clone $start;
        $end->modify('last day of this month');
        $where .= " AND NOT (p.tgl_keluar < '" . $start->format('Y-m-d') . "' OR p.tgl_masuk > '" . $end->format('Y-m-d') . "')";
    } catch (Exception $e) {}
} elseif ($to_date) {
    try {
        $start = new DateTime($to_date . '-01');
        $end   = clone $start;
        $end->modify('last day of this month');
        $where .= " AND NOT (p.tgl_keluar < '" . $start->format('Y-m-d') . "' OR p.tgl_masuk > '" . $end->format('Y-m-d') . "')";
    } catch (Exception $e) {}
}

$total_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peserta p $where"));
$total      = $total_row['total'];
$total_page = ceil($total / $per_page);

$data = mysqli_query($conn, "
    SELECT p.*, b.bidang AS nama_bidang
    FROM peserta p
    LEFT JOIN bidang b ON p.bidang_id = b.id
    $where
    ORDER BY p.id DESC
    LIMIT $per_page OFFSET $offset
");

$status_badge = ['Aktif' => 'success', 'Menunggu' => 'warning', 'Selesai' => 'secondary'];

// Hitung ringkasan
$sum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(status_magang='Aktif') as aktif,
        SUM(status_magang='Menunggu') as menunggu,
        SUM(status_magang='Selesai') as selesai
    FROM peserta
"));
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Daftar Peserta</li>
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
                <div class="small text-muted">Total Peserta</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= $sum['aktif'] ?></div>
                <div class="small text-muted">Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= $sum['menunggu'] ?></div>
                <div class="small text-muted">Menunggu</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-secondary"><?= $sum['selesai'] ?></div>
                <div class="small text-muted">Selesai</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- TOOLBAR -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="card-title mb-0"><i class="bx bxs-group me-1"></i> Daftar Peserta</h5>
            <a href="index.php?page=tambah_peserta" class="btn btn-primary btn-sm">
                <i class="bx bx-user-plus me-1"></i> Tambah Peserta
            </a>
        </div>

        <!-- FILTER & SEARCH -->
        <form method="GET" action="index.php" class="row g-2 mb-3">
            <input type="hidden" name="page" value="daftar_peserta">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama, username, asal sekolah..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    <?php foreach (['Aktif', 'Menunggu', 'Selesai'] as $st): ?>
                        <option value="<?= $st ?>" <?= $status_filter == $st ? 'selected' : '' ?>><?= $st ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-1">
                    <small class="text-muted text-nowrap">Dari</small>
                    <select name="bulan_dari" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $bulan_dari == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= date('M', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun_dari" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Tahun</option>
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun_dari == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <small class="text-muted text-nowrap">s/d</small>
                    <select name="bulan_sampai" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $bulan_sampai == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= date('M', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun_sampai" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Tahun</option>
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun_sampai == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                <a href="index.php?page=daftar_peserta" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
                <a href="peserta/export_excel.php?bulan_dari=<?= urlencode($bulan_dari) ?>&tahun_dari=<?= urlencode($tahun_dari ?: '') ?>&bulan_sampai=<?= urlencode($bulan_sampai) ?>&tahun_sampai=<?= urlencode($tahun_sampai ?: '') ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>" class="btn btn-sm btn-success ms-2">
                    <i class="bx bx-file"></i> Export Excel
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Asal Sekolah</th>
                        <th>Bidang</th>
                        <th>Unit</th>
                        <th>Periode</th>
                        <th>Durasi</th>
                        <th>Magang</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $offset + 1;
                while ($row = mysqli_fetch_assoc($data)):
                    $badge   = $status_badge[$row['status_magang']] ?? 'secondary';
                    $masuk   = new DateTime($row['tgl_masuk']);
                    $keluar  = new DateTime($row['tgl_keluar']);
                    $durasi  = $masuk->diff($keluar)->days;
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><code><?= htmlspecialchars($row['username']) ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama']) ?></strong>
                            <small class="text-muted d-block"><?= $row['jurusan'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $row['status_peserta'] == 'Siswa' ? 'info' : 'primary' ?>">
                                <?= $row['status_peserta'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['asal_sekolah']) ?></td>
                        <td>
                            <?= $row['nama_bidang']
                                ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($row['nama_bidang']) . '</span>'
                                : '<em class="text-muted">-</em>' ?>
                        </td>
                        <td><?= htmlspecialchars($row['unit']) ?></td>
                        <td class="text-nowrap">
                            <?= date('d/m/Y', strtotime($row['tgl_masuk'])) ?><br>
                            <small class="text-muted">s/d <?= date('d/m/Y', strtotime($row['tgl_keluar'])) ?></small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= $durasi ?> hari</span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>"><?= $row['status_magang'] ?></span>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="index.php?page=tambah_peserta&amp;copy_id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-info me-1" title="Salin data peserta ini"
                               onclick="return confirm('Salin data <?= htmlspecialchars(addslashes($row['nama'])) ?> sebagai data baru?')">
                                <i class="bx bx-copy"></i>
                            </a>
                            <a href="index.php?page=edit_peserta&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning me-1" title="Edit">
                                <i class="bx bx-edit"></i>
                            </a>
                            <a href="index.php?page=daftar_peserta&hapus=<?= $row['id'] ?>"
                               class="btn btn-sm btn-danger" title="Hapus"
                               onclick="return confirm('Yakin hapus peserta \'<?= htmlspecialchars(addslashes($row['nama'])) ?>\'?\nAkun login juga akan ikut terhapus.')">
                                <i class="bx bx-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total == 0): ?>
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bx bx-user-x fs-3 d-block mb-1"></i>
                            Tidak ada data peserta<?= $search ? ' untuk pencarian "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>.
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
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> dari <?= $total ?> data
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=daftar_peserta&p=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&bulan_dari=<?= urlencode($bulan_dari) ?>&tahun_dari=<?= urlencode($tahun_dari ?: '') ?>&bulan_sampai=<?= urlencode($bulan_sampai) ?>&tahun_sampai=<?= urlencode($tahun_sampai ?: '') ?>">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_page, $page + 2); $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=daftar_peserta&p=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&bulan_dari=<?= urlencode($bulan_dari) ?>&tahun_dari=<?= urlencode($tahun_dari ?: '') ?>&bulan_sampai=<?= urlencode($bulan_sampai) ?>&tahun_sampai=<?= urlencode($tahun_sampai ?: '') ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_page ? 'disabled' : '' ?>">
                        <a class="page-link" href="index.php?page=daftar_peserta&p=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&bulan_dari=<?= urlencode($bulan_dari) ?>&tahun_dari=<?= urlencode($tahun_dari ?: '') ?>&bulan_sampai=<?= urlencode($bulan_sampai) ?>&tahun_sampai=<?= urlencode($tahun_sampai ?: '') ?>">
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

    </div>
</div>
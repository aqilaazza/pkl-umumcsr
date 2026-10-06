<?php
// ============================================================
// PUSAT - Rekap Absensi Peserta (Desktop)
// ============================================================
include "../conn/conn.php";

// AJAX Detail handler
if (isset($_GET['ajax_detail'])) {
    $detail_user = mysqli_real_escape_string($conn, $_GET['user']);
    $detail_bulan = preg_replace('/[^0-9\-]/', '', $_GET['bulan']);
    $detail_awal = $detail_bulan . '-01';
    $detail_akhir = date('Y-m-d', strtotime($detail_awal . ' +1 month'));
    
    $q_detail = mysqli_query($conn, "SELECT * FROM absensi_peserta 
        WHERE username='$detail_user' AND tanggal >= '$detail_awal' AND tanggal < '$detail_akhir'
        ORDER BY tanggal ASC");

    $hari_arr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    
    echo '<table class="table table-bordered table-striped table-hover align-middle mb-0" style="font-size: 13px;">';
    echo '<thead class="table-dark">';
    echo '<tr>';
    echo '<th class="text-center" style="width: 100px;">Tanggal</th>';
    echo '<th class="text-center" style="width: 85px;">Hari</th>';
    echo '<th class="text-center" style="width: 80px;">Masuk</th>';
    echo '<th class="text-center" style="width: 80px;">Keluar</th>';
    echo '<th class="text-center" style="width: 90px;">Status</th>';
    echo '<th>Keterangan</th>';
    echo '<th class="text-center" style="width: 70px;">Surat</th>';
    echo '<th>Lokasi GPS</th>';
    echo '</tr>';
    echo '</thead><tbody>';
    
    while ($d = mysqli_fetch_assoc($q_detail)) {
        $day = $hari_arr[date('w', strtotime($d['tanggal']))];
        $badge = match($d['status']){'Hadir'=>'success','Izin'=>'warning','Sakit'=>'danger',default=>'secondary'};
        $surat = $d['file_surat'] ? '<a href="../uploads/surat_absensi/'.htmlspecialchars($d['file_surat']).'" target="_blank" class="btn btn-sm btn-outline-info" title="Buka Surat"><i class="bx bx-file"></i></a>' : '-';
        
        $status_sub = '';
        if (in_array($d['status'], ['Izin', 'Sakit']) && isset($d['approval_status'])) {
            if ($d['approval_status'] === 'Pending') {
                $status_sub = '<br><span class="badge bg-light text-warning border border-warning mt-1" style="font-size: 9px; padding: 2px 4px;">Pending</span>';
            } elseif ($d['approval_status'] === 'Ditolak') {
                $status_sub = '<br><span class="badge bg-light text-danger border border-danger mt-1" style="font-size: 9px; padding: 2px 4px;">Ditolak</span>';
            } else {
                $status_sub = '<br><span class="badge bg-light text-success border border-success mt-1" style="font-size: 9px; padding: 2px 4px;">Disetujui</span>';
            }
        }

        $gps_masuk = $d['lat_masuk'] ? '<a href="https://maps.google.com/?q='.$d['lat_masuk'].','.$d['lng_masuk'].'" target="_blank" class="badge bg-success text-white" style="font-size: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><i class="bx bx-map-pin"></i> Masuk: '.round($d['lat_masuk'], 5).','.round($d['lng_masuk'], 5).'</a>' : '';
        $gps_keluar = $d['lat_keluar'] ? '<a href="https://maps.google.com/?q='.$d['lat_keluar'].','.$d['lng_keluar'].'" target="_blank" class="badge bg-danger text-white mt-1 d-block" style="font-size: 10px; text-decoration: none; width: fit-content; display: inline-flex; align-items: center; gap: 4px;"><i class="bx bx-map-pin"></i> Keluar: '.round($d['lat_keluar'], 5).','.round($d['lng_keluar'], 5).'</a>' : '';
        $gps = ($gps_masuk || $gps_keluar) ? $gps_masuk . $gps_keluar : '-';
        
        echo '<tr>';
        echo '<td class="text-center">'.date('d/m/Y', strtotime($d['tanggal'])).'</td>';
        echo '<td class="text-center"><span class="badge bg-light text-dark border">'.$day.'</span></td>';
        echo '<td class="text-center">'.($d['jam_masuk'] ? '<strong class="text-success">'.substr($d['jam_masuk'],0,5).'</strong>' : '-').'</td>';
        echo '<td class="text-center">'.($d['jam_keluar'] ? '<strong class="text-danger">'.substr($d['jam_keluar'],0,5).'</strong>' : '-').'</td>';
        echo '<td class="text-center"><span class="badge bg-'.$badge.'">'.$d['status'].'</span>'.$status_sub.'</td>';
        echo '<td>'.htmlspecialchars($d['keterangan'] ?? '-').'</td>';
        echo '<td class="text-center">'.$surat.'</td>';
        echo '<td>'.$gps.'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    exit;
}

// Filter
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$filter_unit = isset($_GET['unit']) ? mysqli_real_escape_string($conn, $_GET['unit']) : '';
$filter_bidang = isset($_GET['bidang']) ? intval($_GET['bidang']) : 0;
$filter_search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$page_num = isset($_GET['hal']) ? max(1, intval($_GET['hal'])) : 1;
$per_page = 25;
$offset = ($page_num - 1) * $per_page;

// Date range
$tgl_awal = $filter_bulan . '-01';
$tgl_akhir = date('Y-m-d', strtotime($tgl_awal . ' +1 month'));

// Ambil daftar bidang untuk filter
$bidang_list = mysqli_query($conn, "SELECT * FROM bidang ORDER BY bidang");

// Build WHERE
$where = "WHERE 1=1";
if ($filter_unit) $where .= " AND p.unit = '$filter_unit'";
if ($filter_bidang) $where .= " AND p.bidang_id = $filter_bidang";
if ($filter_search) $where .= " AND (p.nama LIKE '%$filter_search%' OR p.asal_sekolah LIKE '%$filter_search%' OR p.username LIKE '%$filter_search%')";

// Total peserta (untuk pagination)
$total_p = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) n FROM peserta p $where
"))['n'];
$total_halaman = max(1, ceil($total_p / $per_page));

// Ambil data peserta + agregat absensi (1 query, bukan N+1)
$peserta_list = mysqli_query($conn, "
    SELECT p.*, b.bidang as nama_bidang, u.nama as nama_user,
        COALESCE(h.Hadir, 0) as hadir_count,
        COALESCE(h.Izin, 0) as izin_count,
        COALESCE(h.Sakit, 0) as sakit_count,
        COALESCE(h.Alpha, 0) as alpha_count,
        COALESCE(h.total, 0) as total_absensi
    FROM peserta p
    LEFT JOIN bidang b ON p.bidang_id = b.id
    LEFT JOIN users u ON p.username = u.username
    LEFT JOIN (
        SELECT username,
            SUM(status='Hadir') as Hadir,
            SUM(status='Izin') as Izin,
            SUM(status='Sakit') as Sakit,
            SUM(status='Alpha') as Alpha,
            COUNT(*) as total
        FROM absensi_peserta
        WHERE tanggal >= '$tgl_awal' AND tanggal < '$tgl_akhir'
        GROUP BY username
    ) h ON p.username = h.username
    $where
    ORDER BY p.nama ASC
    LIMIT $per_page OFFSET $offset
");

$bulan_names = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$bln_parts = explode('-', $filter_bulan);
$bulan_label = ($bulan_names[(int)$bln_parts[1]] ?? '') . ' ' . $bln_parts[0];

// Hitung hari libur bulan ini (pakai date range)
$libur_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) n FROM hari_libur WHERE tanggal >= '$tgl_awal' AND tanggal < '$tgl_akhir'"))['n'];

// Summary stats based on filter (pakai date range)
$sum_rekap = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(status='Hadir') as hadir,
        SUM(status='Izin') as izin,
        SUM(status='Sakit') as sakit,
        SUM(status='Alpha') as alpha
    FROM absensi_peserta ap
    JOIN peserta p ON ap.username = p.username
    $where AND ap.tanggal >= '$tgl_awal' AND ap.tanggal < '$tgl_akhir'
"));
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Rekap Absensi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active">Rekap Absensi Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-primary"><?= number_format($total_p, 0, ',', '.') ?></div>
                <div class="small text-muted">Total Peserta</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-success border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-success"><?= number_format($sum_rekap['hadir'] ?? 0, 0, ',', '.') ?></div>
                <div class="small text-muted">Hadir</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-warning"><?= number_format($sum_rekap['izin'] ?? 0, 0, ',', '.') ?></div>
                <div class="small text-muted">Izin</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-danger"><?= number_format($sum_rekap['sakit'] ?? 0, 0, ',', '.') ?></div>
                <div class="small text-muted">Sakit</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-secondary border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-secondary"><?= number_format($sum_rekap['alpha'] ?? 0, 0, ',', '.') ?></div>
                <div class="small text-muted">Alpha</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-dark"><?= $libur_count ?></div>
                <div class="small text-muted">Libur</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-calendar-check me-1"></i> Rekap Absensi Peserta</h5>
            <span class="badge bg-light text-dark border">Periode: <?= $bulan_label ?></span>
        </div>
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="rekap_absensi">
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $filter_bulan ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Unit</label>
                <select name="unit" class="form-select form-select-sm">
                    <option value="">Semua Unit</option>
                    <option value="Unit 1-2" <?= $filter_unit==='Unit 1-2'?'selected':'' ?>>Unit 1-2</option>
                    <option value="Unit 9" <?= $filter_unit==='Unit 9'?'selected':'' ?>>Unit 9</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bidang</label>
                <select name="bidang" class="form-select form-select-sm">
                    <option value="">Semua Bidang</option>
                    <?php
                    mysqli_data_seek($bidang_list, 0);
                    while ($b = mysqli_fetch_assoc($bidang_list)):
                    ?>
                    <option value="<?= $b['id'] ?>" <?= $filter_bidang==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['bidang']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / Sekolah / Username" value="<?= htmlspecialchars($filter_search) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                <a href="index.php?page=rekap_absensi" class="btn btn-outline-secondary btn-sm ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Rekap -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0" id="tabelRekap">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Sekolah</th>
                        <th>Unit</th>
                        <th>Bidang</th>
                        <th class="text-center" style="background:#198754;">Hadir</th>
                        <th class="text-center" style="background:#ffc107; color:#333;">Izin</th>
                        <th class="text-center" style="background:#dc3545;">Sakit</th>
                        <th class="text-center" style="background:#6c757d;">Alpha</th>
                        <th class="text-center">%</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $offset + 1;
                while ($p = mysqli_fetch_assoc($peserta_list)):
                    $total_rec = $p['total_absensi'];
                    $pct = $total_rec > 0 ? round(($p['hadir_count'] / $total_rec) * 100) : 0;
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($p['nama']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($p['username']) ?></small></td>
                    <td><small><?= htmlspecialchars($p['asal_sekolah'] ?? '-') ?></small></td>
                    <td><?= htmlspecialchars($p['unit'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['nama_bidang'] ?? '-') ?></td>
                    <td class="text-center fw-bold text-success"><?= $p['hadir_count'] ?></td>
                    <td class="text-center fw-bold text-warning"><?= $p['izin_count'] ?></td>
                    <td class="text-center fw-bold text-danger"><?= $p['sakit_count'] ?></td>
                    <td class="text-center fw-bold text-secondary"><?= $p['alpha_count'] ?></td>
                    <td class="text-center">
                        <div class="progress" style="height:6px; width:60px; display:inline-block; vertical-align:middle;">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                        <small class="ms-1"><?= $pct ?>%</small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="showDetail('<?= $p['username'] ?>', '<?= htmlspecialchars($p['nama']) ?>')">
                            <i class="bx bx-list-ul"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_halaman > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php $url_base = "?page=rekap_absensi&bulan=$filter_bulan&unit=$filter_unit&bidang=$filter_bidang&q=" . urlencode($filter_search) . "&hal="; ?>
        <!-- First -->
        <li class="page-item <?= $page_num <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $url_base ?>1">«</a>
        </li>
        <!-- Prev -->
        <li class="page-item <?= $page_num <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $url_base . ($page_num - 1) ?>">‹</a>
        </li>
        <!-- Pages with ellipsis -->
        <?php
        $start = max(1, $page_num - 2);
        $end = min($total_halaman, $page_num + 2);
        if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $start; $i <= $end; $i++):
        ?>
        <li class="page-item <?= $i === $page_num ? 'active' : '' ?>">
            <a class="page-link" href="<?= $url_base . $i ?>"><?= $i ?></a>
        </li>
        <?php endfor;
        if ($end < $total_halaman) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        ?>
        <!-- Next -->
        <li class="page-item <?= $page_num >= $total_halaman ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $url_base . ($page_num + 1) ?>">›</a>
        </li>
        <!-- Last -->
        <li class="page-item <?= $page_num >= $total_halaman ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $url_base . $total_halaman ?>">»</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetailTitle">Detail Absensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(username, nama) {
    const bulan = '<?= $filter_bulan ?>';
    document.getElementById('modalDetailTitle').textContent = 'Detail Absensi - ' + nama;
    document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1"></i></div>';
    
    var modal = new bootstrap.Modal(document.getElementById('modalDetail'));
    modal.show();

    // Load detail via inline query
    fetch('index.php?page=rekap_absensi&ajax_detail=1&user=' + username + '&bulan=' + bulan)
    .then(r => r.text())
    .then(html => {
        document.getElementById('modalDetailBody').innerHTML = html;
    });
}
</script>

<?php
include "../conn/conn.php";

$username = $_SESSION['username'];
$esc_user = mysqli_real_escape_string($conn, $username);
$today    = date('Y-m-d');

function tgl_indo($t) {
    $b = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $p = explode('-', $t);
    return $p[2] . ' ' . $b[(int)$p[1]] . ' ' . $p[0];
}

$laporan_stat = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*) as total,
        SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu,
        SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui,
        SUM(manager_status='Ditolak' OR (sdm_status='Ditolak' AND manager_status='Menunggu')) as ditolak,
        SUM(sdm_status='Menunggu') as menunggu_sdm
    FROM laporan_magang
"));

$total_peserta   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM peserta"))['n'];
$peserta_aktif   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM peserta WHERE status_magang='Aktif'"))['n'];
$peserta_menunggu = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM peserta WHERE status_magang='Menunggu'"))['n'];
$peserta_selesai  = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM peserta WHERE status_magang='Selesai'"))['n'];
$unit1_count      = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM peserta WHERE unit='Unit 1-2'"))['n'];
$unit9_count      = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM peserta WHERE unit='Unit 9'"))['n'];

$sertif_digital = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM sertifikat_magang WHERE status='Digital'"))['n'];

// Data Absensi Hari Ini
$absensi_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN status='Hadir' AND jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status='Izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN status='Sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN status='Alpha' THEN 1 ELSE 0 END) as alpha,
        COUNT(*) as total_absen
    FROM absensi_peserta WHERE tanggal='$today'
"));
$absen_hadir = (int)($absensi_hari_ini['hadir'] ?? 0);
$absen_izin  = (int)($absensi_hari_ini['izin'] ?? 0);
$absen_sakit = (int)($absensi_hari_ini['sakit'] ?? 0);
$absen_total = (int)($absensi_hari_ini['total_absen'] ?? 0);
$belum_absen = max(0, $peserta_aktif - $absen_total);

$q_bidang = mysqli_query($conn, "
    SELECT b.bidang, COUNT(p.id) as jumlah
    FROM bidang b LEFT JOIN peserta p ON b.id = p.bidang_id
    GROUP BY b.id, b.bidang ORDER BY jumlah DESC
");
$bidang_data = [];
while ($row = mysqli_fetch_assoc($q_bidang)) $bidang_data[] = $row;

$menunggu = (int)$laporan_stat['menunggu'];
$disetujui = (int)$laporan_stat['disetujui'];
$ditolak = (int)$laporan_stat['ditolak'];
$total  = (int)$laporan_stat['total'];
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark"><i class='bx bxs-dashboard me-2 text-primary'></i>Dashboard Manager</h4>
        <p class="text-muted mb-0 small"><?= tgl_indo($today) ?> &middot; Overview Persetujuan Laporan</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="d-flex flex-wrap gap-2 justify-content-md-end">
            <?php if ($menunggu > 0): ?>
            <a href="index.php?page=approval_laporan" class="btn btn-sm btn-light border-0 shadow-sm px-3">
                <i class="bx bxs-bell-ring text-warning me-1"></i> <?= $menunggu ?> Perlu Approval
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SECTION 1: STATISTIK UTAMA -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-primary text-primary me-3">
                        <i class="bx bx-group fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Total Peserta</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0"><?= number_format($total_peserta,0,',','.') ?></h3>
                </div>
            </div>
            <div style="height:4px; background: #0d6efd; width: 100%;"></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-success text-success me-3">
                        <i class="bx bx-user-check fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Peserta Aktif</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0"><?= number_format($peserta_aktif,0,',','.') ?></h3>
                    <small class="text-muted">U1: <?= $unit1_count ?> &middot; U9: <?= $unit9_count ?></small>
                </div>
            </div>
            <div style="height:4px; background: #198754; width: 100%;"></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-warning text-warning me-3">
                        <i class="bx bx-time-five fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Menunggu</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0"><?= number_format($peserta_menunggu,0,',','.') ?></h3>
                </div>
            </div>
            <div style="height:4px; background: #ffc107; width: 100%;"></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-info text-info me-3">
                        <i class="bx bx-check-double fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Selesai Magang</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0"><?= number_format($peserta_selesai,0,',','.') ?></h3>
                </div>
            </div>
            <div style="height:4px; background: #0dcaf0; width: 100%;"></div>
        </div>
    </div>
</div>

<!-- SECTION: ANALYTICS VISUALIZATION -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Peserta per Bidang</h6>
                <div id="chart-bidang" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <h6 class="fw-bold mb-4 text-start"><i class="bx bx-pie-chart-alt me-2 text-primary"></i>Status Laporan</h6>
                <div id="chart-laporan" style="min-height: 250px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <h6 class="fw-bold mb-4 text-start"><i class="bx bx-doughnut-chart me-2 text-primary"></i>Absensi Hari Ini</h6>
                <div id="chart-absen" style="min-height: 250px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 2: STATUS APPROVAL -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0"><i class="bx bx-task me-2 text-primary"></i>Statistik Persetujuan Laporan</h6>
                    <span class="badge bg-light text-dark fw-normal border">Total Laporan: <?= $total ?></span>
                </div>
                <div class="row g-4 text-center">
                    <div class="col-4">
                        <div class="p-3 rounded-4 bg-light-warning-subtle">
                            <h4 class="fw-bold text-warning mb-1"><?= $menunggu ?></h4>
                            <div class="text-muted small">Menunggu Review</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-4 bg-light-success-subtle">
                            <h4 class="fw-bold text-success mb-1"><?= $disetujui ?></h4>
                            <div class="text-muted small">Telah Disetujui</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-4 bg-light-danger-subtle">
                            <h4 class="fw-bold text-danger mb-1"><?= $ditolak ?></h4>
                            <div class="text-muted small">Telah Ditolak</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-2">
                    <?php
                    $div_rep = max(1, $total);
                    $pct_selesai = round((($disetujui + $ditolak) / $div_rep) * 100);
                    ?>
                    <div class="d-flex justify-content-between small mb-2 text-muted">
                        <span>Penyelesaian Review Laporan</span>
                        <span class="fw-bold"><?= $pct_selesai ?>%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct_selesai ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-award me-2 text-primary"></i>Sertifikasi</h6>
                <div class="d-grid gap-3">
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="d-flex align-items-center">
                            <i class="bx bxs-certification text-info fs-1 me-3"></i>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $sertif_digital ?></h3>
                                <div class="text-muted small">Sertifikat Digital Terbit</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="small text-muted mb-2">Alur Saat Ini:</div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px;">
                            <span class="badge bg-white text-dark border fw-normal">SDM Review</span>
                            <i class='bx bx-right-arrow-alt text-muted'></i>
                            <span class="badge bg-primary text-white border-0 fw-normal">Manager</span>
                            <i class='bx bx-right-arrow-alt text-muted'></i>
                            <span class="badge bg-white text-dark border fw-normal">Digital</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: ANALYTICS -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-bar-chart-alt-2 me-2 text-primary"></i>Persentase Laporan</h6>
                <div class="d-grid gap-3">
                    <?php
                    $div = max(1, $total);
                    $bars = [
                        ['label'=>'Menunggu Approval', 'val'=>$menunggu, 'cls'=>'bg-warning'],
                        ['label'=>'Telah Disetujui',    'val'=>$disetujui, 'cls'=>'bg-success'],
                        ['label'=>'Telah Ditolak',      'val'=>$ditolak, 'cls'=>'bg-danger'],
                    ];
                    foreach ($bars as $b):
                        $pct = round($b['val'] / $div * 100);
                    ?>
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted"><?= $b['label'] ?></span>
                            <span class="fw-bold"><?= $b['val'] ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar <?= $b['cls'] ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-pie-chart-alt-2 me-2 text-primary"></i>Distribusi Bidang</h6>
                <?php if (empty($bidang_data)): ?>
                    <div class="text-muted small py-4 text-center">Belum ada data bidang.</div>
                <?php else: 
                    $max_bidang = max(array_column($bidang_data, 'jumlah')) ?: 1;
                    foreach (array_slice($bidang_data, 0, 5) as $row):
                        $pct = round(($row['jumlah']/$max_bidang)*100);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted text-truncate me-2"><?= htmlspecialchars($row['bidang'] ?: 'Lainnya') ?></span>
                        <span class="fw-bold"><?= $row['jumlah'] ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary opacity-75" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.bg-light-primary { background-color: #e7f1ff; }
.bg-light-success { background-color: #e1f2e9; }
.bg-light-warning { background-color: #fff8e1; }
.bg-light-info    { background-color: #e0f7fa; }
.bg-light-danger  { background-color: #fce4ec; }

.bg-light-success-subtle { background-color: rgba(25, 135, 84, 0.08); }
.bg-light-warning-subtle { background-color: rgba(255, 193, 7, 0.08); }
.bg-light-danger-subtle  { background-color: rgba(220, 53, 69, 0.08); }

.card { transition: transform 0.2s ease; }
.card:hover { transform: translateY(-3px); }
</style>

<script>
window.addEventListener('load', function() {
    // 1. Chart Peserta per Bidang
    var optionsBidang = {
        series: [{
            name: 'Jumlah Peserta',
            data: [<?php foreach($bidang_data as $b) echo $b['jumlah'] . ','; ?>]
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
                distributed: true
            }
        },
        dataLabels: { enabled: false },
        colors: ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#d63384', '#6610f2', '#6f42c1'],
        xaxis: {
            categories: [<?php foreach($bidang_data as $b) echo "'" . addslashes($b['bidang']) . "',"; ?>],
        },
        legend: { show: false }
    };
    var chartBidang = new ApexCharts(document.querySelector("#chart-bidang"), optionsBidang);
    chartBidang.render();

    // 2. Chart Laporan
    var optionsLaporan = {
        series: [<?= $disetujui ?>, <?= $menunggu ?>, <?= $ditolak ?>],
        chart: {
            type: 'pie',
            height: 250,
        },
        labels: ['Disetujui', 'Menunggu', 'Ditolak'],
        colors: ['#198754', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' }
    };
    var chartLaporan = new ApexCharts(document.querySelector("#chart-laporan"), optionsLaporan);
    chartLaporan.render();

    // 3. Chart Absensi Hari Ini
    var optionsAbsen = {
        series: [<?= $absen_hadir ?>, <?= $absen_izin ?>, <?= $absen_sakit ?>, <?= $belum_absen ?>],
        chart: {
            type: 'donut',
            height: 250,
        },
        labels: ['Hadir', 'Izin', 'Sakit', 'Belum'],
        colors: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
        legend: { position: 'bottom' },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: function (w) {
                                return <?= $peserta_aktif ?>
                            }
                        }
                    }
                }
            }
        }
    };
    var chartAbsen = new ApexCharts(document.querySelector("#chart-absen"), optionsAbsen);
    chartAbsen.render();
});
</script>

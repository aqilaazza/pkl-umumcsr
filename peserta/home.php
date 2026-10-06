<?php
// ============================================================
// PESERTA - Dashboard Home (Mobile)
// ============================================================
include "../conn/conn.php";

if (($_SESSION['role'] ?? '') !== 'peserta') {
    echo '<div class="m-card" style="background:#f8d7da;color:#842029;">Akses ditolak.</div>';
    exit;
}

$username = $_SESSION['username'] ?? '';
$esc_user = mysqli_real_escape_string($conn, $username);

// ── Data peserta ──
$peserta = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, b.bidang as nama_bidang 
     FROM peserta p LEFT JOIN bidang b ON p.bidang_id = b.id
     WHERE p.username = '$esc_user'"));

// ── Absensi hari ini ──
$today = date('Y-m-d');
$absensi_today = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM absensi_peserta WHERE username='$esc_user' AND tanggal='$today'"));

// Cek hari libur
$is_libur = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM hari_libur WHERE tanggal='$today'"));
if (!$is_libur) {
    $day_idx = date('w'); // 0-6
    $is_libur_pekan = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM libur_pekan WHERE hari_index='$day_idx'"));
    if ($is_libur_pekan) {
        $is_libur = ['keterangan' => 'Libur Pekan (' . $is_libur_pekan['nama_hari'] . ')'];
    }
}

// ── Statistik kehadiran bulan ini ──
$bulan_ini = date('Y-m');
$stat_hadir = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM absensi_peserta WHERE username='$esc_user' AND DATE_FORMAT(tanggal,'%Y-%m')='$bulan_ini' AND status='Hadir'"))['n'];
$stat_izin = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM absensi_peserta WHERE username='$esc_user' AND DATE_FORMAT(tanggal,'%Y-%m')='$bulan_ini' AND status='Izin'"))['n'];
$stat_sakit = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM absensi_peserta WHERE username='$esc_user' AND DATE_FORMAT(tanggal,'%Y-%m')='$bulan_ini' AND status='Sakit'"))['n'];

// ── Status laporan ──
$laporan_aktif = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM laporan_magang WHERE username='$esc_user' ORDER BY id DESC LIMIT 1"));
$jumlah_laporan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM laporan_magang WHERE username='$esc_user'"))['n'];

// ── Status sertifikat ──
$sertifikat = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM sertifikat_magang WHERE username='$esc_user' ORDER BY id DESC LIMIT 1"));

// ── Helper ──
function tgl_indo_home($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $p = explode('-', $tanggal);
    return $p[2] . ' ' . $bulan[(int)$p[1]] . ' ' . $p[0];
}

$hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulan_full = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$today_label = $hari[date('w')] . ', ' . date('d') . ' ' . $bulan_full[(int)date('m')] . ' ' . date('Y');
?>

<!-- ═══ Greeting Card ═══ -->


<!-- ═══ Status Absensi Hari Ini ═══ -->
<div class="m-card border-left-status" style="border-left-color: <?php
    if ($is_libur) echo '#0d6efd';
    elseif ($absensi_today && $absensi_today['jam_keluar']) echo '#198754';
    elseif ($absensi_today && $absensi_today['jam_masuk']) echo '#ffc107';
    else echo '#dc3545';
?>;">
    <div class="d-flex align-center justify-between">
        <div>
            <div class="absensi-label">ABSENSI HARI INI</div>
            <div class="absensi-status-text">
                <?php
                if ($is_libur) {
                    echo '<i class="bx bx-calendar me-1"></i>' . htmlspecialchars($is_libur['keterangan'] ?? 'Hari Libur');
                } elseif ($absensi_today) {
                    if ($absensi_today['status'] === 'Izin') echo '<i class="bx bx-edit me-1"></i>Izin';
                    elseif ($absensi_today['status'] === 'Sakit') echo '<i class="bx bx-plus-medical me-1"></i>Sakit';
                    elseif ($absensi_today['jam_keluar']) echo '<i class="bx bx-check-circle me-1"></i>Selesai';
                    else echo '<i class="bx bx-circle me-1"></i>Sudah Masuk';
                } else {
                    echo '<i class="bx bx-time me-1"></i>Belum Absen';
                }
                ?>
            </div>
        </div>
        <div class="text-right">
            <?php if ($absensi_today && $absensi_today['jam_masuk']): ?>
            <div class="absensi-time-label">Masuk</div>
            <div class="absensi-time-value"><?= substr($absensi_today['jam_masuk'],0,5) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!$is_libur && (!$absensi_today || !$absensi_today['jam_masuk'])): ?>
    <a href="index.php?page=absensi" class="action-btn action-btn-primary action-btn-block mt-12 text-deco-none">
        <i class='bx bx-log-in'></i> Absen Masuk
    </a>
    <?php elseif ($absensi_today && $absensi_today['jam_masuk'] && !$absensi_today['jam_keluar'] && $absensi_today['status'] === 'Hadir'): ?>
    <a href="index.php?page=absensi" class="action-btn action-btn-danger action-btn-block mt-12 text-deco-none">
        <i class='bx bx-log-out'></i> Absen Pulang
    </a>
    <?php endif; ?>
</div>

<!-- ═══ Quick Stats ═══ -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon bg-stat-hadir">
            <i class='bx bx-check-circle'></i>
        </div>
        <div class="stat-value"><?= $stat_hadir ?></div>
        <div class="stat-label">Hadir (Bulan Ini)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-stat-izin">
            <i class='bx bx-envelope'></i>
        </div>
        <div class="stat-value"><?= $stat_izin + $stat_sakit ?></div>
        <div class="stat-label">Izin/Sakit</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-stat-laporan">
            <i class='bx bx-file'></i>
        </div>
        <div class="stat-value"><?= $jumlah_laporan ?></div>
        <div class="stat-label">Laporan Upload</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-stat-sertifikat <?= $sertifikat && ($sertifikat['status']==='Sudah Upload' || $sertifikat['status']==='Digital') ? 'bg-stat-hadir' : 'bg-stat-dim' ?>">
            <i class='bx bx-award'></i>
        </div>
        <div class="stat-value fs-14">
            <?php
            if ($sertifikat && ($sertifikat['status'] === 'Sudah Upload' || $sertifikat['status'] === 'Digital')) echo '<i class="bx bx-check-circle text-success me-1"></i>Terbit';
            elseif ($sertifikat) echo '<i class="bx bx-time text-warning me-1"></i>Proses';
            else echo '<i class="bx bx-lock text-muted me-1"></i>Belum';
            ?>
        </div>
        <div class="stat-label">Sertifikat</div>
    </div>
</div>

<!-- ═══ Status Laporan Terakhir ═══ -->
<?php if ($laporan_aktif): ?>
<div class="m-section-title"><i class='bx bx-file'></i> Laporan Terakhir</div>
<div class="m-card">
    <div class="d-flex align-center justify-between">
        <div class="flex-1">
            <div class="laporan-file-name text-truncate">
                <?= htmlspecialchars($laporan_aktif['nama_file'] ?? '-') ?>
            </div>
            <div class="laporan-upload-date">
                Upload: <?= date('d/m/Y H:i', strtotime($laporan_aktif['tgl_upload'])) ?>
            </div>
        </div>
        <span class="m-badge m-badge-<?= strtolower($laporan_aktif['status']) ?>">
            <?php
            $icons = ['Menunggu'=>'bx-time','Disetujui'=>'bx-check-circle','Ditolak'=>'bx-x-circle'];
            $ic = $icons[$laporan_aktif['status']] ?? 'bx-question-mark';
            ?>
            <i class='bx <?= $ic ?>'></i> <?= $laporan_aktif['status'] ?>
        </span>
    </div>
    <?php if ($laporan_aktif['status'] === 'Ditolak' && $laporan_aktif['keterangan_tolak']): ?>
    <div class="rejection-box">
        <i class='bx bx-message-error'></i> <?= htmlspecialchars(mb_strimwidth($laporan_aktif['keterangan_tolak'], 0, 80, '...')) ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ═══ Quick Actions ═══ -->
<div class="m-section-title"><i class='bx bx-zap'></i> Aksi Cepat</div>
<div class="quick-actions-grid">
    <a href="index.php?page=absensi" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-calendar-check'></i></div>
        <div class="quick-action-text">Absensi</div>
    </a>
    <a href="index.php?page=upload_laporan" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-upload'></i></div>
        <div class="quick-action-text">Upload Laporan</div>
    </a>
    <a href="index.php?page=dokumen" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-folder-open'></i></div>
        <div class="quick-action-text">Dokumen SDM</div>
    </a>
    <a href="index.php?page=sertifikat" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-award'></i></div>
        <div class="quick-action-text">Sertifikat</div>
    </a>
    <a href="index.php?page=referensi_laporan" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-book'></i></div>
        <div class="quick-action-text">Referensi</div>
    </a>
</div>

<!-- ═══ Info Periode ═══ -->
<?php if ($peserta && $peserta['tgl_masuk'] && $peserta['tgl_keluar']): ?>
<div class="m-card" style="border-left: 4px solid var(--info);">
    <div class="periode-label">PERIODE MAGANG</div>
    <div class="periode-date">
        <?= tgl_indo_home($peserta['tgl_masuk']) ?> — <?= tgl_indo_home($peserta['tgl_keluar']) ?>
    </div>
    <?php
    $sisa = (strtotime($peserta['tgl_keluar']) - time()) / 86400;
    if ($sisa > 0):
    ?>
    <div class="mt-8">
        <div class="progress-info">
            <span>Progress</span>
            <span><?= max(0, round($sisa)) ?> hari lagi</span>
        </div>
        <?php
        $total_days = (strtotime($peserta['tgl_keluar']) - strtotime($peserta['tgl_masuk'])) / 86400;
        $elapsed = $total_days - $sisa;
        $pct = min(100, max(0, ($elapsed / $total_days) * 100));
        ?>
        <div class="m-progress">
            <div class="m-progress-bar" style="width:<?= round($pct) ?>%; background:var(--primary-gradient);"></div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
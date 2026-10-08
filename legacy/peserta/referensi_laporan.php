<?php
// ============================================================
// PESERTA - Referensi Laporan (Mobile)
// ============================================================
include "../conn/conn.php";

$username = $_SESSION['username'];
$esc_user = mysqli_real_escape_string($conn, $username);

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$per_page = 10;
$page_num = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page_num - 1) * $per_page;

$where = "WHERE (lm.status = 'Disetujui' OR (lm.sdm_status = 'Disetujui' AND lm.manager_status = 'Disetujui'))";
if ($search) $where .= " AND (p.nama LIKE '%$search%' OR p.asal_sekolah LIKE '%$search%')";

$total = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM laporan_magang lm JOIN peserta p ON lm.username=p.username $where"))['total'];
$total_page = ceil($total / $per_page);

$data = mysqli_query($conn, "
    SELECT lm.*, p.nama, p.asal_sekolah, p.jurusan, b.bidang AS nama_bidang
    FROM laporan_magang lm JOIN peserta p ON lm.username=p.username
    LEFT JOIN bidang b ON p.bidang_id = b.id
    $where ORDER BY lm.tgl_upload DESC LIMIT $per_page OFFSET $offset");
?>

<!-- ═══ Tabs ═══ -->
<div class="m-tabs" style="margin-top:4px; margin-bottom:12px;">
    <button class="m-tab" onclick="location.href='index.php?page=upload_laporan'">Upload</button>
    <button class="m-tab" onclick="location.href='index.php?page=upload_laporan&tab=riwayat'">Riwayat</button>
    <button class="m-tab active" onclick="location.href='index.php?page=referensi_laporan'">Referensi</button>
</div>

<div class="m-section-title" style="margin-top:4px;"><i class='bx bx-book'></i> Referensi Laporan</div>

<div class="m-card" style="padding:8px 12px; margin-bottom:12px;">
    <form method="GET" action="index.php" style="display:flex; gap:8px;">
        <input type="hidden" name="page" value="referensi_laporan">
        <input type="text" name="q" class="m-form-input" placeholder="Cari nama / sekolah..." value="<?= htmlspecialchars($search) ?>" style="flex:1; padding:8px 12px; font-size:13px;">
        <button type="submit" class="action-btn action-btn-primary" style="padding:8px 12px;"><i class='bx bx-search'></i></button>
    </form>
</div>

<div style="font-size:11px; color:var(--text-secondary); margin-bottom:8px;"><?= $total ?> laporan ditemukan</div>

<div class="m-card" style="padding:4px 16px;">
    <?php
    $has = false;
    while ($row = mysqli_fetch_assoc($data)):
        $has = true;
        $sz = $row['ukuran_file'] > 1024*1024 ? round($row['ukuran_file']/1024/1024,2).' MB' : round($row['ukuran_file']/1024,1).' KB';
    ?>
    <div class="m-list-item" style="padding:14px 0;">
        <div class="m-list-icon" style="background:#d1e7dd; color:#0f5132;">
            <i class='bx bxs-file-pdf'></i>
        </div>
        <div class="m-list-content">
            <div class="m-list-title" style="font-weight:600;"><?= htmlspecialchars($row['nama']) ?></div>
            <div class="m-list-subtitle" style="margin-top:4px; font-size:11px; line-height:1.4;">
                <span style="font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($row['asal_sekolah']) ?></span><br>
                Bidang: <span style="color:var(--primary); font-weight:600;"><?= htmlspecialchars($row['nama_bidang'] ?? '-') ?></span> · Jurusan: <?= htmlspecialchars($row['jurusan'] ?? '-') ?><br>
                <small class="text-muted"><?= $sz ?> · <?= date('d/m/Y', strtotime($row['tgl_upload'])) ?></small>
            </div>
        </div>
        <div class="m-list-action" style="display:flex; gap:6px; align-items:center;">
            <a href="../uploads/laporan/<?= htmlspecialchars($row['file_laporan']) ?>" target="_blank" style="color:var(--primary); font-size:20px; display:inline-flex;" title="Lihat">
                <i class='bx bx-show-alt'></i>
            </a>
            <a href="../uploads/laporan/<?= htmlspecialchars($row['file_laporan']) ?>" download="<?= htmlspecialchars('Laporan_'.$row['nama'].'.pdf') ?>" style="color:#198754; font-size:20px; display:inline-flex;" title="Download">
                <i class='bx bx-download'></i>
            </a>
        </div>
    </div>
    <?php endwhile; ?>
    <?php if (!$has): ?>
    <div class="m-empty">
        <i class='bx bx-book'></i>
        <div class="m-empty-text">Belum ada referensi laporan</div>
    </div>
    <?php endif; ?>
</div>

<?php if ($total_page > 1): ?>
<div style="display:flex; justify-content:center; gap:4px; margin-top:12px; margin-bottom:16px;">
    <?php if ($page_num > 1): ?>
    <a href="index.php?page=referensi_laporan&p=<?= $page_num-1 ?>&q=<?= urlencode($search) ?>" class="action-btn action-btn-outline" style="padding:6px 12px; font-size:12px;">
        <i class='bx bx-chevron-left'></i>
    </a>
    <?php endif; ?>
    <span style="padding:6px 12px; font-size:12px; color:var(--text-secondary);"><?= $page_num ?> / <?= $total_page ?></span>
    <?php if ($page_num < $total_page): ?>
    <a href="index.php?page=referensi_laporan&p=<?= $page_num+1 ?>&q=<?= urlencode($search) ?>" class="action-btn action-btn-outline" style="padding:6px 12px; font-size:12px;">
        <i class='bx bx-chevron-right'></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<a href="index.php?page=upload_laporan" class="action-btn action-btn-outline action-btn-block" style="margin-bottom:16px;">
    <i class='bx bx-arrow-back'></i> Kembali ke Laporan
</a>

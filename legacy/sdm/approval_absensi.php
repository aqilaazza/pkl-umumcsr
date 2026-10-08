<?php
// ============================================================
// PUSAT - Approval Izin & Sakit Peserta
// ============================================================
include "../conn/conn.php";

$message = '';

// Proses Approve
if (isset($_POST['action_approve'])) {
    $absen_id = intval($_POST['absen_id']);
    $q = mysqli_query($conn, "UPDATE absensi_peserta SET approval_status='Disetujui' WHERE id=$absen_id");
    if ($q) {
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Pengajuan berhasil <strong>Disetujui</strong>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Gagal menyetujui pengajuan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// Proses Reject
if (isset($_POST['action_reject'])) {
    $absen_id = intval($_POST['absen_id']);
    $q = mysqli_query($conn, "UPDATE absensi_peserta SET approval_status='Ditolak' WHERE id=$absen_id");
    if ($q) {
        $message = '<div class="alert alert-warning alert-dismissible fade show py-2">
            <i class="bx bxs-x-circle me-2"></i> Pengajuan berhasil <strong>Ditolak</strong>.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Gagal menolak pengajuan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// Query Ringkasan
$sum_absensi = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(approval_status='Pending') as pending,
        SUM(approval_status='Disetujui') as disetujui,
        SUM(approval_status='Ditolak') as ditolak
    FROM absensi_peserta 
    WHERE status IN ('Izin', 'Sakit')
"));

// Query Data Pending untuk Tabel
$data_pending = mysqli_query($conn, "
    SELECT ap.*, p.nama, p.asal_sekolah, p.unit, b.bidang as nama_bidang
    FROM absensi_peserta ap
    JOIN peserta p ON ap.username = p.username
    LEFT JOIN bidang b ON p.bidang_id = b.id
    WHERE ap.status IN ('Izin', 'Sakit') AND ap.approval_status = 'Pending'
    ORDER BY ap.tanggal DESC
");

$count_pending = (int)($sum_absensi['pending'] ?? 0);
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Absensi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Izin & Sakit</li>
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
                <div class="fs-2 fw-bold text-primary"><?= number_format($sum_absensi['total'], 0, ',', '.') ?></div>
                <div class="small text-muted">Total Pengajuan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning"><?= number_format($sum_absensi['pending'], 0, ',', '.') ?></div>
                <div class="small text-muted">Menunggu Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-success border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success"><?= number_format($sum_absensi['disetujui'], 0, ',', '.') ?></div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger"><?= number_format($sum_absensi['ditolak'], 0, ',', '.') ?></div>
                <div class="small text-muted">Ditolak</div>
            </div>
        </div>
    </div>
</div>

<!-- List Table -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-check-shield me-1"></i> Approval Izin & Sakit</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0" id="tabelApprovalIzin">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Peserta</th>
                        <th>Sekolah / Unit / Bidang</th>
                        <th style="width: 110px;" class="text-center">Tanggal</th>
                        <th style="width: 90px;" class="text-center">Jenis</th>
                        <th>Keterangan</th>
                        <th style="width: 80px;" class="text-center">Bukti</th>
                        <th style="width: 160px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                $bulan_names = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                while ($r = mysqli_fetch_assoc($data_pending)):
                    $tgl_parts = explode('-', $r['tanggal']);
                    $tgl_formatted = $tgl_parts[2] . ' ' . ($bulan_names[(int)$tgl_parts[1]] ?? '') . ' ' . $tgl_parts[0];
                    
                    $badge = $r['status'] === 'Izin' ? 'warning' : 'danger';
                    $surat = $r['file_surat'] 
                        ? '<a href="../uploads/surat_absensi/' . htmlspecialchars($r['file_surat']) . '" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat Surat"><i class="bx bx-file"></i></a>' 
                        : '-';
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['nama']) ?></strong><br>
                        <small class="text-muted">@<?= htmlspecialchars($r['username']) ?></small>
                    </td>
                    <td>
                        <small><?= htmlspecialchars($r['asal_sekolah']) ?></small><br>
                        <small class="text-muted"><?= htmlspecialchars($r['unit'] ?? '-') ?> · <?= htmlspecialchars($r['nama_bidang'] ?? '-') ?></small>
                    </td>
                    <td class="text-center fw-bold"><?= $tgl_formatted ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $badge ?>"><?= $r['status'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                    <td class="text-center"><?= $surat ?></td>
                    <td class="text-center">
                        <form method="POST" action="index.php?page=approval_absensi" class="d-inline" onsubmit="return confirm('Setujui pengajuan ini?')">
                            <input type="hidden" name="absen_id" value="<?= $r['id'] ?>">
                            <button type="submit" name="action_approve" class="btn btn-sm btn-success me-1">
                                <i class="bx bx-check"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="index.php?page=approval_absensi" class="d-inline" onsubmit="return confirm('Tolak pengajuan ini?')">
                            <input type="hidden" name="absen_id" value="<?= $r['id'] ?>">
                            <button type="submit" name="action_reject" class="btn btn-sm btn-danger">
                                <i class="bx bx-x"></i> Tolak
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($no === 1): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bx bx-check-double fs-3 d-block mb-1 text-success"></i>
                        Tidak ada pengajuan izin/sakit yang perlu ditinjau.
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#tabelApprovalIzin').DataTable({
        paging: true,
        pageLength: 15,
        language: { search: "Cari:", lengthMenu: "Tampilkan _MENU_ data" }
    });
});
</script>

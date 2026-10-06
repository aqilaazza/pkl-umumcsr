<?php
include "../conn/conn.php";

$message = '';

if (isset($_POST['save_ttd'])) {
    $nama    = mysqli_real_escape_string($conn, $_POST['nama_ttd']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan_ttd']);

    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM pengaturan_ttd WHERE id = 1"));
    if ($cek) {
        $q = mysqli_query($conn, "UPDATE pengaturan_ttd SET nama_ttd = '$nama', jabatan_ttd = '$jabatan' WHERE id = 1");
    } else {
        $q = mysqli_query($conn, "INSERT INTO pengaturan_ttd (id, nama_ttd, jabatan_ttd) VALUES (1, '$nama', '$jabatan')");
    }

    if ($q) {
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Pengaturan tanda tangan berhasil disimpan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Gagal menyimpan: ' . mysqli_error($conn) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_ttd WHERE id = 1"));
$nama_val    = $cfg['nama_ttd'] ?? 'Sukarno';
$jabatan_val = $cfg['jabatan_ttd'] ?? 'Manager Business Support';
?>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Tanda Tangan Sertifikat</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<div class="row">
    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-success">
            <div class="card-body p-4">
                <div class="card-title d-flex align-items-center gap-2">
                    <i class="bx bxs-certification text-success font-24"></i>
                    <h5 class="mb-0">Atur Nama & Jabatan Penandatangan</h5>
                </div>
                <hr />
                <form action="index.php?page=pengaturan_ttd" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_ttd" class="form-control" value="<?= htmlspecialchars($nama_val) ?>" required>
                        <div class="form-text">Nama pejabat yang menandatangani sertifikat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jabatan</label>
                        <input type="text" name="jabatan_ttd" class="form-control" value="<?= htmlspecialchars($jabatan_val) ?>" required>
                        <div class="form-text">Contoh: Manager Business Support, Kepala Unit, dll.</div>
                    </div>

                    <button type="submit" name="save_ttd" class="btn btn-success px-4">
                        <i class="bx bx-save me-1"></i>Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-info">
            <div class="card-body p-4">
                <h5 class="card-title mb-3 text-info"><i class="bx bx-info-circle me-1"></i> Preview Tanda Tangan</h5>
                <div class="text-center p-4 border rounded-3 bg-light">
                    <div style="font-family: 'Brush Script MT', cursive; font-size: 28px; color: #1a1a2e;">
                        <?= htmlspecialchars($nama_val) ?>
                    </div>
                    <div class="text-muted mt-2" style="font-size: 13px;">
                        <?= htmlspecialchars($jabatan_val) ?>
                    </div>
                </div>
                <hr>
                <div class="alert alert-info mb-0 small">
                    <i class="bx bx-printer me-1"></i>
                    Data ini akan digunakan saat mencetak sertifikat peserta melalui menu
                    <strong>Cetak Sertifikat</strong>.
                </div>
            </div>
        </div>
    </div>
</div>
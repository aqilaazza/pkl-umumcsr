<?php
include "../conn/conn.php";

$message = '';

// --- AKTIFKAN TAHUN (set satu aktif, nonaktifkan yang lain) ---
if (isset($_GET['aktifkan'])) {
    $id = (int) $_GET['aktifkan'];
    mysqli_query($conn, "UPDATE tahun_aktif SET status = 'nonaktif'");
    mysqli_query($conn, "UPDATE tahun_aktif SET status = 'aktif' WHERE id = $id");
    $message = '<div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bx bxs-check-circle me-2"></i> Tahun aktif berhasil diperbarui.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// --- SIMPAN (INSERT) ---
if (isset($_POST['submit_add'])) {
    $tahun      = (int) $_POST['tahun'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    $cek = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM tahun_aktif WHERE tahun = '$tahun'"));
    if ($cek > 0) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Tahun <strong>' . $tahun . '</strong> sudah terdaftar.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        mysqli_query($conn, "INSERT INTO tahun_aktif (tahun, keterangan) VALUES ('$tahun', '$keterangan')");
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Tahun <strong>' . $tahun . '</strong> berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// --- HAPUS ---
if (isset($_GET['hapus'])) {
    $id  = (int) $_GET['hapus'];
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM tahun_aktif WHERE id = $id"));
    if ($cek && $cek['status'] === 'aktif') {
        $message = '<div class="alert alert-warning alert-dismissible fade show py-2">
            <i class="bx bxs-error me-2"></i> Tahun aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        mysqli_query($conn, "DELETE FROM tahun_aktif WHERE id = $id");
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Data tahun berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// --- AMBIL TAHUN AKTIF SAAT INI ---
$tahun_aktif_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tahun_aktif WHERE status = 'aktif' LIMIT 1"));

// --- LIST DATA ---
$data = mysqli_query($conn, "SELECT * FROM tahun_aktif ORDER BY tahun DESC");
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Tahun Aktif</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<!-- INFO TAHUN AKTIF -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center gap-3 mb-0 py-3">
            <i class="bx bxs-calendar-check fs-3"></i>
            <div>
                <strong>Tahun Aktif Saat Ini:</strong>
                <?php if ($tahun_aktif_row): ?>
                    <span class="badge bg-success fs-6 ms-2"><?= $tahun_aktif_row['tahun'] ?></span>
                    <?php if ($tahun_aktif_row['keterangan']): ?>
                        <small class="text-muted ms-2">&mdash; <?= htmlspecialchars($tahun_aktif_row['keterangan']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-secondary ms-2">Belum Ada Tahun Aktif</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <!-- FORM TAMBAH -->
    <div class="col-xl-4">
        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body">
                <div class="border p-4 rounded">
                    <div class="card-title d-flex align-items-center gap-2">
                        <i class="bx bxs-calendar-plus font-22"></i>
                        <h5 class="mb-0">Tambah Tahun</h5>
                    </div>
                    <hr />
                    <form action="index.php?page=tahun_aktif" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Tahun</label>
                            <input type="number"
                                   name="tahun"
                                   class="form-control"
                                   placeholder="Contoh: 2025"
                                   min="2000"
                                   max="2100"
                                   value="<?= date('Y') ?>"
                                   required>
                            <div class="form-text">Masukkan tahun 4 digit (2000–2100).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan <span class="text-muted">(opsional)</span></label>
                            <input type="text"
                                   name="keterangan"
                                   class="form-control"
                                   placeholder="Misal: Tahun Anggaran 2025">
                        </div>
                        <button type="submit" name="submit_add" class="btn btn-primary px-5">
                            <i class="bx bx-calendar-plus me-1"></i>Tambahkan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- PANDUAN -->
        <div class="card border-top border-0 border-4 border-warning mt-3">
            <div class="card-body p-4">
                <h6 class="card-title d-flex align-items-center gap-2">
                    <i class="bx bxs-info-circle text-warning"></i> Panduan
                </h6>
                <ul class="mb-0 ps-3 small text-muted">
                    <li>Hanya <strong>satu</strong> tahun yang dapat berstatus <span class="badge bg-success">Aktif</span> pada satu waktu.</li>
                    <li>Klik tombol <span class="badge bg-success"><i class="bx bx-check"></i></span> untuk mengaktifkan tahun yang diinginkan.</li>
                    <li>Tahun berstatus <span class="badge bg-success">Aktif</span> tidak dapat dihapus.</li>
                    <li>Tahun aktif akan digunakan sebagai referensi di seluruh modul aplikasi.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- LIST DATA -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="bx bxs-calendar me-1"></i> Daftar Tahun
                    </h5>
                    <span class="badge bg-primary"><?= mysqli_num_rows($data) ?> Tahun</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Tahun</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                                <th>Ditambahkan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        mysqli_data_seek($data, 0);
                        while ($row = mysqli_fetch_assoc($data)):
                            $is_aktif = ($row['status'] === 'aktif');
                        ?>
                            <tr class="<?= $is_aktif ? 'table-success' : '' ?>">
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= $row['tahun'] ?></strong>
                                    <?php if ($is_aktif): ?>
                                        <i class="bx bxs-star text-warning ms-1" title="Tahun Aktif"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $row['keterangan'] ? htmlspecialchars($row['keterangan']) : '<em>-</em>' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $is_aktif ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $is_aktif ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td class="small"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="text-center">
                                    <?php if (!$is_aktif): ?>
                                        <!-- Tombol Aktifkan -->
                                        <a href="index.php?page=tahun_aktif&aktifkan=<?= $row['id'] ?>"
                                           class="btn btn-sm btn-success me-1"
                                           title="Jadikan Tahun Aktif"
                                           onclick="return confirm('Aktifkan tahun <?= $row['tahun'] ?> sebagai tahun aktif?')">
                                            <i class="bx bx-check"></i>
                                        </a>
                                        <!-- Tombol Hapus -->
                                        <a href="index.php?page=tahun_aktif&hapus=<?= $row['id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           title="Hapus"
                                           onclick="return confirm('Yakin ingin menghapus tahun <?= $row['tahun'] ?>?')">
                                            <i class="bx bx-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <!-- Tahun aktif: tombol disabled -->
                                        <button class="btn btn-sm btn-success me-1 disabled" title="Tahun ini sedang aktif">
                                            <i class="bx bx-check-double"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger disabled" title="Tidak dapat menghapus tahun aktif">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($data) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bx bx-calendar-x fs-3 d-block mb-1"></i>
                                    Belum ada data tahun.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
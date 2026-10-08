<?php
// ============================================================
// PUSAT - Kelola Hari Libur / Tanggal Merah & Libur Pekan
// ============================================================
include "../conn/conn.php";

$message = '';

// Update Libur Pekan
if (isset($_POST['update_libur_pekan'])) {
    $libur_pekan_input = $_POST['hari_libur_pekan'] ?? []; // Array of indexes
    
    // Clear old data
    mysqli_query($conn, "DELETE FROM libur_pekan");
    
    $success = true;
    foreach ($libur_pekan_input as $idx) {
        $idx = intval($idx);
        $nama_hari = match($idx) {
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => ''
        };
        if ($nama_hari !== '') {
            $q = mysqli_query($conn, "INSERT INTO libur_pekan (hari_index, nama_hari) VALUES ($idx, '$nama_hari')");
            if (!$q) $success = false;
        }
    }
    
    if ($success) {
        $message = '<div class="alert alert-success"><i class="bx bx-check me-1"></i>Konfigurasi libur pekan berhasil disimpan!</div>';
    } else {
        $message = '<div class="alert alert-danger"><i class="bx bx-error me-1"></i>Gagal menyimpan konfigurasi libur pekan.</div>';
    }
}

// Tambah Hari Libur Khusus / Tanggal Merah
if (isset($_POST['tambah_libur'])) {
    $tanggal = $_POST['tanggal'];
    $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan']));
    
    if (empty($tanggal) || empty($keterangan)) {
        $message = '<div class="alert alert-danger"><i class="bx bx-error me-1"></i>Tanggal dan keterangan wajib diisi!</div>';
    } else {
        $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hari_libur WHERE tanggal='$tanggal'"));
        if ($cek) {
            $message = '<div class="alert alert-warning"><i class="bx bx-info-circle me-1"></i>Tanggal ini sudah terdaftar sebagai hari libur.</div>';
        } else {
            $q = mysqli_query($conn, "INSERT INTO hari_libur (tanggal, keterangan) VALUES ('$tanggal', '$keterangan')");
            $message = $q
                ? '<div class="alert alert-success"><i class="bx bx-check me-1"></i>Hari libur khusus berhasil ditambahkan!</div>'
                : '<div class="alert alert-danger"><i class="bx bx-error me-1"></i>Gagal menambahkan.</div>';
        }
    }
}

// Hapus Hari Libur Khusus
if (isset($_GET['hapus_libur'])) {
    $id = intval($_GET['hapus_libur']);
    mysqli_query($conn, "DELETE FROM hari_libur WHERE id=$id");
    echo "<script>window.location.href='index.php?page=hari_libur&msg=deleted';</script>";
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $message = '<div class="alert alert-success"><i class="bx bx-check me-1"></i>Hari libur berhasil dihapus.</div>';
}

// Ambil data libur pekan terdaftar
$libur_pekan_db = [];
$q_lp = mysqli_query($conn, "SELECT hari_index FROM libur_pekan");
while ($r_lp = mysqli_fetch_assoc($q_lp)) {
    $libur_pekan_db[] = intval($r_lp['hari_index']);
}

// Filter tahun
$filter_tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$data_libur = mysqli_query($conn, "SELECT * FROM hari_libur WHERE YEAR(tanggal)=$filter_tahun ORDER BY tanggal ASC");

$bulan_names = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hari_names = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active">Hari Libur & Pekan</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<div class="row">
    <!-- Kolom Kiri: Pengaturan Libur Pekan & Form Tambah -->
    <div class="col-lg-4">
        <!-- Default Libur Pekan -->
        <div class="card border-top border-0 border-4 border-primary mb-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-3"><i class="bx bx-time me-1 text-primary"></i> Default Libur Pekan</h5>
                <form method="POST" action="index.php?page=hari_libur">
                    <div class="mb-3">
                        <label class="form-label d-block fw-bold text-muted small">Pilih Hari Libur Pekan:</label>
                        <?php
                        $hari_names_indo = [
                            0 => 'Minggu (Ahad)',
                            1 => 'Senin',
                            2 => 'Selasa',
                            3 => 'Rabu',
                            4 => 'Kamis',
                            5 => 'Jumat',
                            6 => 'Sabtu'
                        ];
                        foreach ($hari_names_indo as $idx => $nama):
                            $checked = in_array($idx, $libur_pekan_db) ? 'checked' : '';
                        ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hari_libur_pekan[]" value="<?= $idx ?>" id="hari_<?= $idx ?>" <?= $checked ?>>
                            <label class="form-check-label small" for="hari_<?= $idx ?>">
                                <?= $nama ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="update_libur_pekan" class="btn btn-primary w-100 btn-sm">
                        <i class="bx bx-save me-1"></i>Simpan Libur Pekan
                    </button>
                </form>
            </div>
        </div>

        <!-- Tambah Hari Libur Khusus -->
        <div class="card border-top border-0 border-4 border-danger">
            <div class="card-body p-4">
                <h5 class="card-title mb-3"><i class="bx bx-calendar-plus me-1 text-danger"></i> Tambah Libur Khusus</h5>
                <form method="POST" action="index.php?page=hari_libur">
                    <div class="mb-3">
                        <label class="form-label small">Tanggal Libur</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Keterangan / Nama Libur</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Misal: Tahun Baru" required>
                    </div>
                    <button type="submit" name="tambah_libur" class="btn btn-danger w-100 btn-sm">
                        <i class="bx bx-plus me-1"></i>Tambah Libur Khusus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar Hari Libur Khusus -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bx bx-calendar-x me-1"></i> Daftar Libur Khusus <?= $filter_tahun ?></h5>
                    <div>
                        <a href="index.php?page=hari_libur&tahun=<?= $filter_tahun-1 ?>" class="btn btn-sm btn-outline-secondary"><i class="bx bx-chevron-left"></i></a>
                        <span class="mx-2 fw-bold small"><?= $filter_tahun ?></span>
                        <a href="index.php?page=hari_libur&tahun=<?= $filter_tahun+1 ?>" class="btn btn-sm btn-outline-secondary"><i class="bx bx-chevron-right"></i></a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Keterangan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        while ($r = mysqli_fetch_assoc($data_libur)):
                            $day = $hari_names[date('w', strtotime($r['tanggal']))];
                            $tgl = date('d', strtotime($r['tanggal'])) . ' ' . $bulan_names[(int)date('m', strtotime($r['tanggal']))] . ' ' . date('Y', strtotime($r['tanggal']));
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $tgl ?></td>
                            <td><span class="badge bg-info text-dark"><?= $day ?></span></td>
                            <td><?= htmlspecialchars($r['keterangan']) ?></td>
                            <td class="text-center">
                                <a href="index.php?page=hari_libur&hapus_libur=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus hari libur ini?')">
                                    <i class="bx bx-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($no === 1): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bx bx-calendar-x fs-3 d-block mb-1"></i>
                                Belum ada hari libur khusus di tahun <?= $filter_tahun ?>
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

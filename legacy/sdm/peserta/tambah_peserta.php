<?php
include "../conn/conn.php";

$message = '';
$default_password = '$2y$10$NEw7BdY0IR9GGEuiJjCOIOT/gxSFOH8j54MnRLk9G3yOuCASPeFre';

// --- GENERATE USERNAME OTOMATIS (dipanggil saat submit) ---
// Query username terakhir dari peserta, increment, lalu pastikan belum ada di users/peserta
function generateUsername($conn) {
    $last = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT username FROM peserta ORDER BY id DESC LIMIT 1"
    ));
    if ($last && preg_match('/(\d+)$/', $last['username'], $m)) {
        $num = (int)$m[1] + 1;
    } else {
        $num = 1;
    }
    // Loop sampai dapat username yang benar-benar belum dipakai
    do {
        $username = str_pad($num, 4, '0', STR_PAD_LEFT);
        $cek_u = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'"));
        $cek_p = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM peserta WHERE username = '$username'"));
        if ($cek_u > 0 || $cek_p > 0) $num++;
    } while ($cek_u > 0 || $cek_p > 0);
    return $username;
}

// --- SIMPAN ---
if (isset($_POST['submit_add'])) {
    // Generate username saat submit — bukan dari input form
    $username       = generateUsername($conn);
    $nama           = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $status_peserta = $_POST['status_peserta'];
    $asal_sekolah   = mysqli_real_escape_string($conn, trim($_POST['asal_sekolah']));
    $jurusan        = mysqli_real_escape_string($conn, trim($_POST['jurusan']));
    $tgl_masuk      = $_POST['tgl_masuk'];
    $tgl_keluar     = $_POST['tgl_keluar'];
    $bidang_id      = !empty($_POST['bidang_id']) ? (int) $_POST['bidang_id'] : null;
    $unit           = mysqli_real_escape_string($conn, $_POST['unit']);
    $status_magang  = $_POST['status_magang'];
    $keterangan     = mysqli_real_escape_string($conn, trim($_POST['keterangan']));

    $errors = [];

    if (empty($nama))          $errors[] = 'Nama lengkap tidak boleh kosong.';
    if (empty($asal_sekolah))  $errors[] = 'Asal sekolah tidak boleh kosong.';
    if (empty($jurusan))       $errors[] = 'Jurusan tidak boleh kosong.';
    if (empty($tgl_masuk))     $errors[] = 'Tanggal masuk tidak boleh kosong.';
    if (empty($tgl_keluar))    $errors[] = 'Tanggal keluar tidak boleh kosong.';
    if (!empty($tgl_masuk) && !empty($tgl_keluar) && $tgl_keluar <= $tgl_masuk)
        $errors[] = 'Tanggal keluar harus setelah tanggal masuk.';

    if (!empty($errors)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i>
            <ul class="mb-0 ps-3">' . implode('', array_map(function($e) { return "<li>$e</li>"; }, $errors)) . '</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $bidang_sql = $bidang_id ? $bidang_id : 'NULL';
        mysqli_query($conn, "INSERT INTO peserta (username, nama, status_peserta, asal_sekolah, jurusan, tgl_masuk, tgl_keluar, bidang_id, unit, status_magang, keterangan)
            VALUES ('$username','$nama','$status_peserta','$asal_sekolah','$jurusan','$tgl_masuk','$tgl_keluar',$bidang_sql,'$unit','$status_magang','$keterangan')");

        mysqli_query($conn, "INSERT INTO users (username, nama, password, role)
            VALUES ('$username','$nama','$default_password','peserta')");

        echo "<script>window.location.href='index.php?page=daftar_peserta&msg=added';</script>";
        exit;
    }
}

$list_bidang = mysqli_query($conn, "SELECT * FROM bidang ORDER BY bidang ASC");

// --- PREFILL DATA DARI COPY (jika ada copy_id di GET) ---
$copy = null;
if (!isset($_POST["submit_add"]) && isset($_GET["copy_id"])) {
    $copy_id = (int) $_GET["copy_id"];
    $copy    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM peserta WHERE id = $copy_id"));
}

// --- AUTOCOMPLETE DATA (DISTINCT) ---
$list_sekolah = array();
$res_sekolah = mysqli_query($conn, "SELECT DISTINCT asal_sekolah FROM peserta WHERE asal_sekolah != '' ORDER BY asal_sekolah ASC");
while ($r = mysqli_fetch_assoc($res_sekolah)) $list_sekolah[] = $r['asal_sekolah'];

$list_jurusan = array();
$res_jurusan = mysqli_query($conn, "SELECT DISTINCT jurusan FROM peserta WHERE jurusan != '' ORDER BY jurusan ASC");
while ($r = mysqli_fetch_assoc($res_jurusan)) $list_jurusan[] = $r['jurusan'];
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="index.php?page=daftar_peserta">Daftar Peserta</a></li>
                <li class="breadcrumb-item active" aria-current="page">Tambah Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<!-- Datalist autocomplete -->
<datalist id="listSekolah">
    <?php foreach ($list_sekolah as $s): ?>
        <option value="<?= htmlspecialchars($s) ?>">
    <?php endforeach; ?>
</datalist>
<datalist id="listJurusan">
    <?php foreach ($list_jurusan as $j): ?>
        <option value="<?= htmlspecialchars($j) ?>">
    <?php endforeach; ?>
</datalist>

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">
        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bx <?= $copy ? 'bxs-copy-alt' : 'bxs-user-plus' ?> fs-4 text-primary"></i>
                    <h5 class="mb-0"><?= $copy ? 'Salin Data Peserta' : 'Tambah Peserta Baru' ?></h5>
                    <?php if ($copy): ?>
                        <span class="badge bg-info ms-2">
                            <i class="bx bx-copy me-1"></i>Disalin dari: <?= htmlspecialchars($copy['nama']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <hr class="mt-0">

                <form action="index.php?page=tambah_peserta" method="POST" id="formTambah">

                    <!-- SEKSI: Akun -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">
                            <i class="bx bx-lock-alt me-1"></i> Informasi Akun
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control bg-light" value="Dibuat otomatis" readonly>
                                <div class="form-text"><i class="bx bx-info-circle"></i> Username akan dibuat otomatis saat data disimpan.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password Default</label>
                                <input type="text" class="form-control bg-light" value="12345" readonly>
                                <div class="form-text">Password dapat diubah peserta setelah login.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI: Data Pribadi -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">
                            <i class="bx bx-user me-1"></i> Data Pribadi
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control"
                                       placeholder="Nama lengkap peserta"
                                       value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ($copy ? htmlspecialchars($copy['nama']) : '') ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Peserta <span class="text-danger">*</span></label>
                                <select name="status_peserta" class="form-select" required>
                                    <option value="">-- Pilih --</option>
                                    <?php
                                    $sel_status = isset($_POST['status_peserta']) ? $_POST['status_peserta'] : ($copy ? $copy['status_peserta'] : '');
                                    foreach (['Siswa', 'Mahasiswa'] as $sp): ?>
                                        <option value="<?= $sp ?>" <?= $sel_status == $sp ? 'selected' : '' ?>><?= $sp ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asal Sekolah / Universitas <span class="text-danger">*</span></label>
                                <input type="text" name="asal_sekolah" class="form-control"
                                       list="listSekolah"
                                       placeholder="Ketik atau pilih dari daftar..."
                                       value="<?= isset($_POST['asal_sekolah']) ? htmlspecialchars($_POST['asal_sekolah']) : ($copy ? htmlspecialchars($copy['asal_sekolah']) : '') ?>"
                                       autocomplete="off"
                                       required>
                                <div class="form-text"><i class="bx bx-bulb"></i> Pilih dari daftar atau ketik manual jika belum ada.</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Jurusan <span class="text-danger">*</span></label>
                                <input type="text" name="jurusan" class="form-control"
                                       list="listJurusan"
                                       placeholder="Ketik atau pilih dari daftar..."
                                       value="<?= isset($_POST['jurusan']) ? htmlspecialchars($_POST['jurusan']) : ($copy ? htmlspecialchars($copy['jurusan']) : '') ?>"
                                       autocomplete="off"
                                       required>
                                <div class="form-text"><i class="bx bx-bulb"></i> Pilih dari daftar atau ketik manual jika belum ada.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI: Data Magang -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">
                            <i class="bx bx-briefcase me-1"></i> Data Magang
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" name="tgl_masuk" id="tglMasuk" class="form-control"
                                       value="<?= isset($_POST['tgl_masuk']) ? $_POST['tgl_masuk'] : ($copy ? $copy['tgl_masuk'] : '') ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Keluar <span class="text-danger">*</span></label>
                                <input type="date" name="tgl_keluar" id="tglKeluar" class="form-control"
                                       value="<?= isset($_POST['tgl_keluar']) ? $_POST['tgl_keluar'] : ($copy ? $copy['tgl_keluar'] : '') ?>"
                                       required>
                            </div>
                            <div class="col-12">
                                <div id="selisihHari" class="alert alert-info py-2 d-none">
                                    <i class="bx bx-calendar-check me-1"></i>
                                    Durasi magang: <strong id="jumlahHari">-</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bidang</label>
                                <select name="bidang_id" class="form-select">
                                    <option value="">-- Pilih Bidang --</option>
                                    <?php
                                    $sel_bidang = isset($_POST['bidang_id']) ? $_POST['bidang_id'] : ($copy ? $copy['bidang_id'] : '');
                                    while ($b = mysqli_fetch_assoc($list_bidang)): ?>
                                        <option value="<?= $b['id'] ?>" <?= $sel_bidang == $b['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['bidang']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select" required>
                                    <option value="">-- Pilih Unit --</option>
                                    <?php
                                    $sel_unit = isset($_POST['unit']) ? $_POST['unit'] : ($copy ? $copy['unit'] : '');
                                    foreach (['Unit 1-2', 'Unit 9'] as $u): ?>
                                        <option value="<?= $u ?>" <?= $sel_unit == $u ? 'selected' : '' ?>><?= $u ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Magang <span class="text-danger">*</span></label>
                                <select name="status_magang" class="form-select" required>
                                    <option value="">-- Pilih Status --</option>
                                    <?php
                                    $sel_magang = isset($_POST['status_magang']) ? $_POST['status_magang'] : ($copy ? $copy['status_magang'] : '');
                                    foreach (['Aktif', 'Menunggu', 'Selesai'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $sel_magang == $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Keterangan tambahan (opsional)"><?= isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ($copy ? htmlspecialchars($copy['keterangan']) : '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex gap-2">
                        <button type="submit" name="submit_add" class="btn btn-primary px-5">
                            <i class="bx bx-save me-1"></i> Simpan Peserta
                        </button>
                        <a href="index.php?page=daftar_peserta" class="btn btn-outline-secondary px-4">
                            <i class="bx bx-arrow-back me-1"></i> Kembali
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// =============================================
// SELISIH HARI
// =============================================
function hitungSelisih() {
    var masuk  = document.getElementById('tglMasuk').value;
    var keluar = document.getElementById('tglKeluar').value;
    var box    = document.getElementById('selisihHari');
    var label  = document.getElementById('jumlahHari');

    if (masuk && keluar) {
        var d1   = new Date(masuk);
        var d2   = new Date(keluar);
        var diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));

        box.classList.remove('d-none', 'alert-info', 'alert-danger');

        if (diff > 0) {
            var bulan = Math.floor(diff / 30);
            var sisa  = diff % 30;
            var teks  = diff + ' hari';
            if (bulan > 0) teks += ' (' + bulan + ' bulan' + (sisa > 0 ? ' ' + sisa + ' hari' : '') + ')';
            label.textContent = teks;
            box.classList.add('alert-info');
        } else {
            label.textContent = 'Tanggal keluar harus setelah tanggal masuk!';
            box.classList.add('alert-danger');
        }
    } else {
        box.classList.add('d-none');
    }
}
document.getElementById('tglMasuk').addEventListener('change', hitungSelisih);
document.getElementById('tglKeluar').addEventListener('change', hitungSelisih);
hitungSelisih();

</script>
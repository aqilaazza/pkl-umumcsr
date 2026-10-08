<?php
include "../conn/conn.php";

$message = '';
$search_result = null;

// --- PROSES PENCARIAN ---
if (isset($_POST['search'])) {
    $keyword = mysqli_real_escape_string($conn, trim($_POST['keyword']));
    // Query lengkap dengan JOIN ke tabel bidang
    $query = "SELECT p.*, b.bidang as nama_bidang 
              FROM peserta p 
              LEFT JOIN bidang b ON p.bidang_id = b.id
              JOIN users u ON p.username = u.username 
              WHERE p.nama LIKE '%$keyword%' OR p.username = '$keyword'";
    $search_result = mysqli_query($conn, $query);
}

// --- PROSES RESET PASSWORD ---
if (isset($_POST['confirm_reset'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    // Hash password default: 123456
    $new_password = password_hash("123456", PASSWORD_BCRYPT);

    $update = mysqli_query($conn, "UPDATE users SET password = '$new_password' WHERE username = '$username'");

    if ($update) {
        $message = '<div class="alert alert-success alert-dismissible fade show border-0 border-start border-5 border-success">
            <div class="d-flex align-items-center">
                <div class="font-35 text-success"><i class="bx bxs-check-circle"></i></div>
                <div class="ms-3">
                    <h6 class="mb-0 text-success">Berhasil!</h6>
                    <div>Password untuk <strong>' . $username . '</strong> telah direset menjadi: <strong>123456</strong></div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}
?>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Admin</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Reset Password Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-3"><i class="bx bx-search-alt me-2"></i>Cari Peserta</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="keyword" class="form-control" 
                               placeholder="Masukkan Nama Lengkap atau Username Peserta..." 
                               value="<?= isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '' ?>" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" name="search" class="btn btn-primary">
                            <i class="bx bx-search"></i> Cari Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    <?php if ($search_result && mysqli_num_rows($search_result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($search_result)): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-bottom border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="user-avatar bg-light-info text-info p-3 rounded-circle me-3">
                                <i class="bx bxs-user-detail fs-3"></i>
                            </div>
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($row['nama']) ?></h6>
                                <small class="text-muted">Username: <strong><?= $row['username'] ?></strong></small>
                            </div>
                        </div>
                        
                        <table class="table table-sm table-borderless small mb-3">
                            <tr>
                                <td width="100">Instansi/Sekolah</td>
                                <td>: <?= htmlspecialchars($row['asal_sekolah']) ?></td>
                            </tr>
                            <tr>
                                <td>Jurusan</td>
                                <td>: <?= htmlspecialchars($row['jurusan']) ?></td>
                            </tr>
                            <tr>
                                <td>Bidang</td>
                                <td>: <span class="badge bg-light-primary text-primary"><?= $row['nama_bidang'] ?? 'Belum Ditentukan' ?></span></td>
                            </tr>
                            <tr>
                                <td>Unit</td>
                                <td>: <?= $row['unit'] ?></td>
                            </tr>
                            <tr>
                                <td>Periode</td>
                                <td>: <?= date('d M Y', strtotime($row['tgl_masuk'])) ?> s/d <?= date('d M Y', strtotime($row['tgl_keluar'])) ?></td>
                            </tr>
                            <tr>
                                <td>Status Magang</td>
                                <td>: 
                                    <?php 
                                        $color = ($row['status_magang'] == 'Aktif') ? 'success' : (($row['status_magang'] == 'Menunggu') ? 'warning' : 'secondary');
                                        echo "<span class='badge bg-$color'>".$row['status_magang']."</span>";
                                    ?>
                                </td>
                            </tr>
                        </table>

                        <div class="d-grid mt-2">
                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset password akun ini?')">
                                <input type="hidden" name="username" value="<?= $row['username'] ?>">
                                <button type="submit" name="confirm_reset" class="btn btn-danger btn-sm w-100">
                                    <i class="bx bx-refresh"></i> Reset Password ke "123456"
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php elseif (isset($_POST['search'])): ?>
        <div class="col-12 text-center py-5">
            <i class="bx bx-user-x text-muted" style="font-size: 5rem;"></i>
            <h5 class="mt-3 text-muted">Peserta tidak ditemukan</h5>
            <p>Pastikan nama atau username yang Anda masukkan sudah benar.</p>
        </div>
    <?php endif; ?>
</div>
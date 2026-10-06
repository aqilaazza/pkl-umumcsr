<?php
include "../conn/conn.php";

$message = '';

// --- SIMPAN (INSERT) ---
if (isset($_POST['submit_add'])) {
    $nid      = mysqli_real_escape_string($conn, $_POST['nid']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role     = $_POST['role'];

    $cek = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE nid = '$nid'"));
    if ($cek > 0) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> NID <strong>' . htmlspecialchars($nid) . '</strong> sudah digunakan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (nid, nama, password, role) VALUES ('$nid','$nama','$hash','$role')");
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> User berhasil ditambahkan.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// --- LIST DATA ---
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Users</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Manajemen Users</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<div class="row">

    <!-- FORM TAMBAH -->
    <div class="col-xl-4">
        <div class="card border-top border-0 border-4 border-success">
            <div class="card-body">
                <div class="border p-4 rounded">
                    <div class="card-title d-flex align-items-center gap-2">
                        <i class="bx bxs-user-plus font-22"></i>
                        <h5 class="mb-0">Tambah User</h5>
                    </div>
                    <hr />
                    <form action="index.php?page=users" method="POST">
                        <div class="mb-3">
                            <label class="form-label">NID</label>
                            <input type="text" name="nid" class="form-control" placeholder="Masukkan NID" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group" id="show_hide_password">
                                <input type="password" name="password" class="form-control border-end-0" placeholder="Password" required>
                                <a href="javascript:;" class="input-group-text bg-transparent toggle-pass" data-target="#show_hide_password input"><i class="bx bx-hide"></i></a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="unit">Unit</option>
                                <option value="sdm">SDM</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        <button type="submit" name="submit_add" class="btn btn-success px-5">
                            <i class="bx bx-user-plus me-1"></i>Daftarkan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- LIST DATA -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bx bxs-group me-1"></i> Daftar Users</h5>
                    <span class="badge bg-primary"><?= mysqli_num_rows($users) ?> User</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>NID</th>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Dibuat</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($users)):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nid']) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td>
                                    <span class="badge <?= $row['role'] === 'sdm' ? 'bg-danger' : ($row['role'] === 'manager' ? 'bg-warning text-dark' : 'bg-info') ?>">
                                        <?= ucfirst($row['role']) ?>
                                    </span>
                                </td>
                                <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="text-center">
                                    <a href="index.php?page=edit_users&id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-warning me-1" title="Edit">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <a href="users/delete_users.php?id=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Yakin ingin menghapus user <?= htmlspecialchars(addslashes($row['nama'])) ?>?')">
                                       <i class="bx bx-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($users) == 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data user.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('.toggle-pass').forEach(function(btn) {
    btn.addEventListener('click', function () {
        var target = document.querySelector(this.getAttribute('data-target'));
        var icon   = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('bx-hide', 'bx-show');
        } else {
            target.type = 'password';
            icon.classList.replace('bx-show', 'bx-hide');
        }
    });
});
</script>

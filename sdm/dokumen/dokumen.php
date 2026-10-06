<?php
if (!isset($conn)) {
    if (file_exists("../conn/conn.php")) {
        include "../conn/conn.php";
    } elseif (file_exists("conn/conn.php")) {
        include "conn/conn.php";
    }
}

// Auto Create Table dokumen jika belum ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `dokumen` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_dokumen` VARCHAR(255) NOT NULL,
  `file_dokumen` VARCHAR(255) NOT NULL,
  `tipe_file` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$target_dir = "../uploads/dokumen/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$message = '';
$edit_data = null;

// --- EDIT: Ambil data untuk diedit ---
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM dokumen WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $edit_data = mysqli_fetch_assoc($res);
    }
}

// --- HAPUS ---
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    $res = mysqli_query($conn, "SELECT * FROM dokumen WHERE id = $id");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $file_path = $target_dir . $row['file_dokumen'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        mysqli_query($conn, "DELETE FROM dokumen WHERE id = $id");
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Dokumen <strong>' . htmlspecialchars($row['nama_dokumen']) . '</strong> berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// --- SIMPAN (INSERT) ---
if (isset($_POST['submit_add'])) {
    $nama_dokumen = mysqli_real_escape_string($conn, trim($_POST['nama_dokumen']));

    if (empty($nama_dokumen)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Nama dokumen tidak boleh kosong.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif (!isset($_FILES['file_dokumen']) || $_FILES['file_dokumen']['error'] !== UPLOAD_ERR_OK) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Silakan pilih file dokumen untuk diupload.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $file_name = $_FILES['file_dokumen']['name'];
        $file_tmp  = $_FILES['file_dokumen']['tmp_name'];
        $file_size = $_FILES['file_dokumen']['size'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed_ext)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                <i class="bx bxs-error-circle me-2"></i> Format file tidak didukung. Hany file <strong>PDF, JPG, JPEG, PNG, WEBP</strong> yang diperbolehkan.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } elseif ($file_size > 15 * 1024 * 1024) { // 15MB
            $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                <i class="bx bxs-error-circle me-2"></i> Ukuran file terlalu besar. Maksimal 15 MB.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } else {
            $new_filename = 'doc_' . date('YmdHis') . '_' . rand(100, 999) . '.' . $ext;
            $destination  = $target_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $destination)) {
                mysqli_query($conn, "INSERT INTO dokumen (nama_dokumen, file_dokumen, tipe_file) VALUES ('$nama_dokumen', '$new_filename', '$ext')");
                $message = '<div class="alert alert-success alert-dismissible fade show py-2">
                    <i class="bx bxs-check-circle me-2"></i> Dokumen <strong>' . htmlspecialchars($nama_dokumen) . '</strong> berhasil ditambahkan.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                    <i class="bx bxs-error-circle me-2"></i> Gagal mengupload file dokumen ke server.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }
}

// --- UPDATE ---
if (isset($_POST['submit_edit'])) {
    $id           = (int) $_POST['id'];
    $nama_dokumen = mysqli_real_escape_string($conn, trim($_POST['nama_dokumen']));
    $old_file     = $_POST['old_file'];

    if (empty($nama_dokumen)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Nama dokumen tidak boleh kosong.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
        $edit_data = ['id' => $id, 'nama_dokumen' => $_POST['nama_dokumen'], 'file_dokumen' => $old_file];
    } else {
        // Cek apakah ada file baru yang diupload
        if (isset($_FILES['file_dokumen']) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['file_dokumen']['name'];
            $file_tmp  = $_FILES['file_dokumen']['tmp_name'];
            $file_size = $_FILES['file_dokumen']['size'];
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed_ext)) {
                $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                    <i class="bx bxs-error-circle me-2"></i> Format file tidak didukung. Hanya file <strong>PDF, JPG, JPEG, PNG, WEBP</strong> yang diperbolehkan.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                $edit_data = ['id' => $id, 'nama_dokumen' => $_POST['nama_dokumen'], 'file_dokumen' => $old_file];
            } elseif ($file_size > 15 * 1024 * 1024) {
                $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                    <i class="bx bxs-error-circle me-2"></i> Ukuran file terlalu besar. Maksimal 15 MB.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
                $edit_data = ['id' => $id, 'nama_dokumen' => $_POST['nama_dokumen'], 'file_dokumen' => $old_file];
            } else {
                $new_filename = 'doc_' . date('YmdHis') . '_' . rand(100, 999) . '.' . $ext;
                $destination  = $target_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Hapus file lama jika ada
                    if ($old_file && file_exists($target_dir . $old_file)) {
                        @unlink($target_dir . $old_file);
                    }
                    mysqli_query($conn, "UPDATE dokumen SET nama_dokumen = '$nama_dokumen', file_dokumen = '$new_filename', tipe_file = '$ext' WHERE id = $id");
                    $message = '<div class="alert alert-success alert-dismissible fade show py-2">
                        <i class="bx bxs-check-circle me-2"></i> Dokumen dan file berhasil diperbarui.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                    $edit_data = null;
                } else {
                    $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
                        <i class="bx bxs-error-circle me-2"></i> Gagal mengupload file dokumen baru.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                    $edit_data = ['id' => $id, 'nama_dokumen' => $_POST['nama_dokumen'], 'file_dokumen' => $old_file];
                }
            }
        } else {
            // Update nama dokumen saja
            mysqli_query($conn, "UPDATE dokumen SET nama_dokumen = '$nama_dokumen' WHERE id = $id");
            $message = '<div class="alert alert-success alert-dismissible fade show py-2">
                <i class="bx bxs-check-circle me-2"></i> Nama dokumen berhasil diperbarui.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            $edit_data = null;
        }
    }
}

// --- LIST DATA ---
$data = mysqli_query($conn, "SELECT * FROM dokumen ORDER BY id DESC");
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Dokumen</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<div class="row">

    <!-- FORM TAMBAH / EDIT -->
    <div class="col-xl-4">
        <div class="card border-top border-0 border-4 <?= $edit_data ? 'border-warning' : 'border-primary' ?>">
            <div class="card-body">
                <div class="border p-4 rounded">
                    <div class="card-title d-flex align-items-center gap-2">
                        <i class="bx <?= $edit_data ? 'bxs-edit' : 'bxs-file-plus' ?> font-22"></i>
                        <h5 class="mb-0"><?= $edit_data ? 'Edit Dokumen' : 'Tambah Dokumen' ?></h5>
                    </div>
                    <hr />

                    <?php if ($edit_data): ?>
                    <!-- FORM EDIT -->
                    <form action="index.php?page=dokumen" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <input type="hidden" name="old_file" value="<?= htmlspecialchars($edit_data['file_dokumen']) ?>">

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Nama Dokumen</label>
                            <input type="text"
                                   name="nama_dokumen"
                                   class="form-control"
                                   placeholder="Masukkan nama dokumen"
                                   value="<?= htmlspecialchars($edit_data['nama_dokumen']) ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">File (Opsional, jika ingin diganti)</label>
                            <input type="file"
                                   name="file_dokumen"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div class="form-text mt-2">
                                File saat ini: 
                                <a href="../uploads/dokumen/<?= htmlspecialchars($edit_data['file_dokumen']) ?>" target="_blank" class="text-primary text-decoration-none">
                                    <i class="bx bx-file me-1"></i><?= htmlspecialchars($edit_data['file_dokumen']) ?>
                                </a>
                            </div>
                            <div class="form-text">Format: PDF, JPG, JPEG, PNG, WEBP (Maks 15MB).</div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="submit_edit" class="btn btn-warning px-4">
                                <i class="bx bx-save me-1"></i>Simpan Perubahan
                            </button>
                            <a href="index.php?page=dokumen" class="btn btn-secondary px-4">
                                <i class="bx bx-x me-1"></i>Batal
                            </a>
                        </div>
                    </form>

                    <?php else: ?>
                    <!-- FORM TAMBAH -->
                    <form action="index.php?page=dokumen" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Nama Dokumen</label>
                            <input type="text"
                                   name="nama_dokumen"
                                   class="form-control"
                                   placeholder="Contoh: SK Pengangkatan, SOP Magang"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">File Dokumen (Gambar / PDF)</label>
                            <input type="file"
                                   name="file_dokumen"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp"
                                   required>
                            <div class="form-text mt-1">Format yang diperbolehkan: <strong>PDF, JPG, JPEG, PNG, WEBP</strong> (Maks 15MB).</div>
                        </div>

                        <button type="submit" name="submit_add" class="btn btn-primary px-4 mt-2">
                            <i class="bx bx-upload me-1"></i>Upload Dokumen
                        </button>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- PANDUAN -->
        <div class="card border-top border-0 border-4 border-info mt-3">
            <div class="card-body p-4">
                <h6 class="card-title d-flex align-items-center gap-2">
                    <i class="bx bxs-info-circle text-info"></i> Panduan Upload Dokumen
                </h6>
                <ul class="mb-0 ps-3 small text-muted">
                    <li>Isi <strong>Nama Dokumen</strong> secara jelas dan tepat.</li>
                    <li>Pilih file berformat <strong>Gambar (JPG, PNG, WEBP)</strong> atau <strong>PDF</strong>.</li>
                    <li>Klik tombol <span class="badge bg-info text-white"><i class="bx bx-show"></i></span> untuk melihat / mengunduh file dokumen.</li>
                    <li>Klik tombol <span class="badge bg-warning text-dark"><i class="bx bx-edit"></i></span> untuk mengubah nama atau mengganti file dokumen.</li>
                    <li>Klik tombol <span class="badge bg-danger"><i class="bx bx-trash"></i></span> untuk menghapus dokumen.</li>
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
                        <i class="bx bxs-file-doc me-1"></i> Daftar Dokumen
                    </h5>
                    <span class="badge bg-primary fs-6"><?= mysqli_num_rows($data) ?> Dokumen</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0" id="tabelDokumen">
                        <thead class="table-dark">
                            <tr>
                                <th width="50">#</th>
                                <th>Nama Dokumen</th>
                                <th>Tipe / Preview</th>
                                <th>Tgl Upload</th>
                                <th class="text-center" width="130">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = 1;
                        mysqli_data_seek($data, 0);
                        while ($row = mysqli_fetch_assoc($data)):
                            $is_editing = ($edit_data && $edit_data['id'] == $row['id']);
                            $ext = strtolower(pathinfo($row['file_dokumen'], PATHINFO_EXTENSION));
                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                            $file_url = "../uploads/dokumen/" . htmlspecialchars($row['file_dokumen']);
                        ?>
                            <tr class="<?= $is_editing ? 'table-warning' : '' ?>">
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['nama_dokumen']) ?></strong>
                                    <?php if ($is_editing): ?>
                                        <span class="badge bg-warning text-dark ms-2">Sedang Diedit</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_image): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="<?= $file_url ?>" target="_blank" title="Lihat Gambar">
                                                <img src="<?= $file_url ?>" alt="Preview" class="rounded border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                                            </a>
                                            <span class="badge bg-success"><i class="bx bxs-image me-1"></i>Gambar (<?= strtoupper($ext) ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger"><i class="bx bxs-file-pdf me-1"></i>PDF Dokumen</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><i class="bx bx-time-five me-1"></i><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td class="text-center">
                                    <!-- Lihat / Download -->
                                    <a href="<?= $file_url ?>"
                                       target="_blank"
                                       class="btn btn-sm btn-info text-white me-1"
                                       title="Lihat / Download">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <!-- Edit -->
                                    <a href="index.php?page=dokumen&edit=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-warning me-1"
                                       title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <!-- Hapus -->
                                    <a href="index.php?page=dokumen&hapus=<?= $row['id'] ?>"
                                       class="btn btn-sm btn-danger"
                                       title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus dokumen \'<?= htmlspecialchars(addslashes($row['nama_dokumen'])) ?>\'?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($data) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bx bx-file-blank fs-2 d-block mb-1"></i>
                                    Belum ada data dokumen.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $.fn.DataTable && $('#tabelDokumen tbody tr').length > 0 && !$('#tabelDokumen tbody td').hasClass('text-center')) {
        $('#tabelDokumen').DataTable({
            paging: true,
            pageLength: 10,
            language: {
                search: "Cari Dokumen:",
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Tidak ada dokumen yang sesuai",
                info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ dokumen",
                infoEmpty: "Dokumen kosong",
                infoFiltered: "(disaring dari _MAX_ total dokumen)"
            }
        });
    }
});
</script>

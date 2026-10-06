<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Profile</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
    </div>
</div>
<div class="container">
    <?php
    include "../conn/conn.php";
    if (!isset($_SESSION['id'])) { header("location:../index.php"); exit(); }

    $id = $_SESSION['id'];
    $query_profile = mysqli_query($conn, "SELECT * FROM users WHERE username = '$id'");
    $data_profile = mysqli_fetch_array($query_profile);

    if (isset($_POST['submit'])) {
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];

        if (empty($password_baru) || empty($konfirmasi_password)) {
            echo '<div class="alert alert-danger">Password tidak boleh kosong!</div>';
        } elseif ($password_baru !== $konfirmasi_password) {
            echo '<div class="alert alert-danger">Konfirmasi password tidak sesuai!</div>';
        } elseif (strlen($password_baru) < 6) {
            echo '<div class="alert alert-danger">Password minimal 6 karakter!</div>';
        } else {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $update = mysqli_query($conn, "UPDATE users SET password = '$hashed_password' WHERE username = '$id'");
            if ($update) {
                echo '<div class="alert alert-success">Berhasil Merubah Password Baru</div>';
                $query_profile = mysqli_query($conn, "SELECT * FROM users WHERE username = '$id'");
                $data_profile = mysqli_fetch_array($query_profile);
            } else {
                echo '<div class="alert alert-danger">Gagal merubah password!</div>';
            }
        }
    }
    ?>

    <div class="main-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column align-items-center text-center">
                            <img src="../assets/images/team.png" alt="Admin" class="rounded-circle p-1 bg-warning" width="110">
                            <div class="mt-3">
                                <h4><?= ucwords($data_profile['nama']) ?></h4>
                                <p class="text-secondary mb-1"><b><?= $data_profile['username'] ?></b></p>
                                <p class="text-muted font-size-sm"><?= strtoupper($data_profile['role']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="row mb-3">
                                <div class="col-sm-3"><h6 class="mb-0">Status Password</h6></div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="text" value="******** (Tersimpan dengan aman)" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><h6 class="mb-0">Password Baru</h6></div>
                                <div class="col-sm-9 text-secondary">
                                    <div class="input-group" id="show_hide_password1">
                                        <input type="password" name="password_baru" class="form-control border-end-0" id="password_baru" placeholder="Masukkan password baru" minlength="5" required>
                                        <a href="javascript:;" class="input-group-text bg-transparent toggle-password" data-target="password_baru"><i class='bx bx-hide'></i></a>
                                    </div>
                                    <small class="text-muted">Minimal 5 karakter</small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3"><h6 class="mb-0">Konfirmasi Password</h6></div>
                                <div class="col-sm-9 text-secondary">
                                    <div class="input-group" id="show_hide_password2">
                                        <input type="password" name="konfirmasi_password" class="form-control border-end-0" id="konfirmasi_password" placeholder="Ketik ulang password baru" required>
                                        <a href="javascript:;" class="input-group-text bg-transparent toggle-password" data-target="konfirmasi_password"><i class='bx bx-hide'></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-3"></div>
                                <div class="col-sm-9 text-secondary">
                                    <input type="submit" name="submit" class="btn btn-primary px-4" value="Ubah Password" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $(".toggle-password").on('click', function(event) {
        event.preventDefault();
        var targetId = $(this).data('target');
        var input = $('#' + targetId);
        var icon = $(this).find('i');
        if (input.attr("type") == "text") {
            input.attr('type', 'password');
            icon.removeClass("bx-show").addClass("bx-hide");
        } else {
            input.attr('type', 'text');
            icon.removeClass("bx-hide").addClass("bx-show");
        }
    });
    window.setTimeout(function() { $(".alert").fadeTo(1000, 0).slideUp(1000, function() { $(this).remove(); }); }, 3000);
});
</script>

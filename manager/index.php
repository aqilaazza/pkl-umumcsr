<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}
if ($_SESSION['role'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

date_default_timezone_set('Asia/Jakarta');

include "../conn/conn.php";

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$need_datatables = $page === 'approval_laporan';
$need_apexcharts = $page === 'dashboard';

// Badge count untuk sidebar
$pending_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM laporan_magang WHERE sdm_status='Disetujui' AND manager_status='Menunggu'"
))['n'];
?>
<!doctype html>
<html lang="id" class="color-sidebar sidebarcolor3 color-header headercolor4">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
    <link href="../assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
    <link href="../assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
    <link href="../assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
    <?php if ($need_apexcharts): ?>
    <link href="../assets/plugins/apexcharts-bundle/css/apexcharts.css" rel="stylesheet" />
    <?php endif; ?>
    <?php if ($need_datatables): ?>
    <link href="../assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <?php endif; ?>
    <link href="../assets/css/pace.min.css" rel="stylesheet" />
    <script src="../assets/js/pace.min.js"></script>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="../assets/css/app.css" rel="stylesheet">
    <link href="../assets/css/icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dark-theme.css" />
    <link rel="stylesheet" href="../assets/css/semi-dark.css" />
    <link rel="stylesheet" href="../assets/css/header-colors.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Website PKL - Manager</title>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <div>
                    <img src="../assets/images/logo.png" width="60px" height="20px" alt="logo icon">
                </div>
                <div>
                    <h4 class="logo-text">Manager</h4>
                </div>
                <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i></div>
            </div>
            <ul class="metismenu" id="menu">
                <li>
                    <a href="index.php?page=dashboard">
                        <div class="parent-icon"><i class='bx bxs-dashboard'></i></div>
                        <div class="menu-title">Dashboard</div>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=approval_laporan">
                        <div class="parent-icon"><i class='bx bx-check-shield'></i></div>
                        <div class="menu-title">Approval Laporan</div>
                        <?php if ($pending_count > 0): ?>
                        <span class="badge bg-warning text-dark rounded-pill ms-auto me-2"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="index.php?page=profile">
                        <div class="parent-icon"><i class='bx bxs-user-detail'></i></div>
                        <div class="menu-title">Profile</div>
                    </a>
                </li>
            </ul>
        </div>
        <header>
            <div class="topbar d-flex align-items-center">
                <nav class="navbar navbar-expand">
                    <div class="mobile-toggle-menu"><i class='bx bx-menu'></i></div>
                    <div class="user-box dropdown ms-auto">
                        <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="../assets/images/team.png" class="user-img" alt="user avatar">
                            <div class="user-info ps-3">
                                <p class="user-name mb-0"><?= ucwords($_SESSION['nama']); ?></p>
                                <p class="designattion mb-0"><?= ucwords($_SESSION['role']); ?></p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=profile"><i class="bx bx-user"></i><span>Profile</span></a></li>
                            <li><div class="dropdown-divider mb-0"></div></li>
                            <li><a class="dropdown-item" href="logout.php"><i class='bx bx-log-out-circle'></i><span>Logout</span></a></li>
                        </ul>
                    </div>
                </nav>
            </div>
        </header>
        <div class="page-wrapper">
            <div class="page-content">
                <?php
                    switch ($page) {
                        case 'dashboard':
                            include "home.php";
                            break;
                        case 'approval_laporan':
                            include "approval_laporan.php";
                            break;
                        case 'profile':
                            include "profile.php";
                            break;
                        default:
                            include "home.php";
                            break;
                    }
                ?>
            </div>
        </div>
        <div class="overlay"></div>
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <footer class="page-footer">
            <p class="mb-0">Copyright &copy; 2026. Manager Portal</p>
        </footer>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/plugins/simplebar/js/simplebar.min.js"></script>
    <script src="../assets/plugins/metismenu/js/metisMenu.min.js"></script>
    <script src="../assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
    <?php if ($need_datatables): ?>
    <script src="../assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="../assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <?php endif; ?>
    <?php if ($need_apexcharts): ?>
    <script src="../assets/plugins/apexcharts-bundle/js/apexcharts.min.js"></script>
    <?php endif; ?>
    <script src="../assets/js/app.js"></script>
    <script>
        window.showToast = function(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: type,
                title: message
            });
        };
    </script>
</body>
</html>

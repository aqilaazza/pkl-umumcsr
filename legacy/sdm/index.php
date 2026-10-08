<?php
session_start();
if (!isset($_SESSION['id'])) {
	header("Location: ../index.php");
	exit();
}

// Tambahkan ini - cek apakah role-nya memang 'sdm'
if ($_SESSION['role'] !== 'sdm') {
    header("Location: ../sdm/logout.php");
}

if (isset($_GET['ajax_detail'])) {
    include "rekap_absensi.php";
    exit;
}
?>

<?php
date_default_timezone_set('Asia/Jakarta');

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$need_datatables  = in_array($page, ['rekap_absensi','daftar_peserta','bidang','dokumen','hari_libur','cetak_sertifikat','approval_laporan','approval_laporan_manager','approval_absensi','reset_password','users','tambah_peserta','edit_peserta']);
$need_apexcharts  = $page === 'dashboard';
$need_tinymce     = $page === 'cetak_sertifikat';
$need_fancyupload = in_array($page, ['tambah_peserta','edit_peserta','cetak_sertifikat']);
?>
<!doctype html>
<html lang="id" class="color-sidebar sidebarcolor3 color-header headercolor4">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!--favicon-->
	<link rel="icon" href="../favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
	<!-- Core CSS -->
	<link href="../assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
	<link href="../assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
	<link href="../assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
	<?php if ($need_datatables): ?>
	<link href="../assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
	<?php endif; ?>
	<?php if ($need_fancyupload): ?>
	<link href="../assets/plugins/fancy-file-uploader/fancy_fileupload.css" rel="stylesheet" />
	<link href="../assets/plugins/Drag-And-Drop/dist/imageuploadify.min.css" rel="stylesheet" />
	<?php endif; ?>
	<?php if ($need_apexcharts): ?>
	<link href="../assets/plugins/apexcharts-bundle/css/apexcharts.css" rel="stylesheet" />
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
	<title>Website PKL</title>
</head>

<body>
	<!--wrapper-->
	<div class="wrapper">
		<!--sidebar wrapper -->
		<div class="sidebar-wrapper" data-simplebar="true">
			<div class="sidebar-header">
				<div>
					<img src="../assets/images/logo.png" width="60px" height="20px" alt="logo icon">
				</div>
				<div>
					<h4 class="logo-text">Dashboard</h4>
				</div>
				<div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i>
				</div>
			</div>
			<!--navigation-->
			<ul class="metismenu" id="menu">
			    <li>
			        <a href="index.php?page=dashboard">
			            <div class="parent-icon"><i class='bx bxs-dashboard'></i>
			            </div>
			            <div class="menu-title">Dashboard</div>
			        </a>
			    </li>
			    <li>
				    <a href="index.php?page=profile">
				        <div class="parent-icon"><i class='bx bxs-user-detail'></i>
				        </div>
				        <div class="menu-title">Profile</div>
				    </a>
				    <a href="index.php?page=approval_laporan">
					    <div class="parent-icon"><i class='bx bx-check-shield'></i>
					    </div>
					    <div class="menu-title">Approval Laporan</div>
					</a>
					<a href="index.php?page=approval_laporan_manager">
					    <div class="parent-icon"><i class='bx bx-user-check'></i>
					    </div>
					    <div class="menu-title">Approval Manager</div>
					</a>
				    <a href="index.php?page=approval_absensi">
					    <div class="parent-icon"><i class='bx bx-calendar-edit'></i>
					    </div>
					    <div class="menu-title">Approval Izin/Sakit</div>
					</a>
				    <a href="index.php?page=cetak_sertifikat">
				        <div class="parent-icon"><i class='bx bxs-certification'></i>
				        </div>
				        <div class="menu-title">Cetak Sertifikat</div>
				    </a>
				    <a href="index.php?page=rekap_absensi">
				        <div class="parent-icon"><i class='bx bx-calendar-check'></i>
				        </div>
				        <div class="menu-title">Rekap Absensi</div>
				    </a>
				    <a href="index.php?page=reset_password">
				        <div class="parent-icon"><i class='bx bxs-certification'></i>
				        </div>
				        <div class="menu-title">Reset Password</div>
				    </a>
				</li>
			    <li class="menu-label">Master Data</li>
			    <li>
			        <a href="javascript:;" class="has-arrow">
			            <div class="parent-icon"><i class='bx bxs-folder-open'></i></div>
			            <div class="menu-title">Master Data</div>
			        </a>
			        <ul>
			        	<li> <a href="index.php?page=bidang"><i class="bx bxs-group"></i>Data Bidang</a></li>
			        	<li> <a href="index.php?page=daftar_peserta"><i class="bx bxs-group"></i>Data Peserta</a></li>
			        	<li> <a href="index.php?page=dokumen"><i class="bx bxs-file-doc"></i>Data Dokumen</a></li>
			        </ul>
			    </li>
			    <li class="menu-label">Pengaturan</li>
			    <li>
			        <a href="javascript:;" class="has-arrow">
			            <div class="parent-icon"><i class='bx bxs-cog'></i>
			            </div>
			            <div class="menu-title">Pengaturan</div>
			        </a>
			        <ul>
			            <li>
			                <a href="index.php?page=hari_libur"><i class="bx bx-calendar-x"></i>Hari Libur</a>
			            </li>
			            <li>
			                <a href="index.php?page=pengaturan_absensi"><i class="bx bx-map-pin"></i>Lokasi & Radius</a>
			            </li>
			            <li>
			                <a href="index.php?page=pengaturan_ttd"><i class="bx bxs-certification"></i>Tanda Tangan Sertifikat</a>
			            </li>
			        </ul>
			    </li>
			</ul>
			<!--end navigation-->
		</div>
		<!--end sidebar wrapper -->
		<!--start header -->
		<header>
			<div class="topbar d-flex align-items-center">
				<nav class="navbar navbar-expand">
					<div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
					</div>
					<div class="user-box dropdown ms-auto">
						<a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<img src="../assets/images/team.png" class="user-img" alt="user avatar">
							<div class="user-info ps-3">
								<p class="user-name mb-0"><?= ucwords($_SESSION['nama']); ?></p>
								<p class="designattion mb-0"><?= ucwords($_SESSION['role']); ?></p>
							</div>
						</a>
						<ul class="dropdown-menu dropdown-menu-end">
							<li><a class="dropdown-item" href="index.php?page=profile"><i class="bx bx-user"></i><span>Profile</span></a>
							</li>
							<li>
								<div class="dropdown-divider mb-0"></div>
							</li>
							<li><a class="dropdown-item" href="logout.php"><i class='bx bx-log-out-circle'></i><span>Logout</span></a>
							</li>
						</ul>
					</div>
				</nav>
			</div>
		</header>
		<!--end header -->

		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<?php
					switch ($page) {
						case 'dashboard':
							include "home.php";
							break;
						case 'reset_password':
							include "reset_password.php";
							break;
						case 'cetak_sertifikat':
							include "cetak_sertifikat.php";
							break;
						case 'approval_laporan':
							include "approval_laporan.php";
							break;
						case 'approval_absensi':
							include "approval_absensi.php";
							break;
						case 'approval_laporan_manager':
							include "approval_laporan_manager.php";
							break;
						case 'profile':
							include "profile.php";
							break;
						case 'daftar_peserta':
						    include "peserta/daftar_peserta.php";
						    break;
						case 'tambah_peserta':
						    include "peserta/tambah_peserta.php";
						    break;
						case 'edit_peserta':
						    include "peserta/edit_peserta.php";
						    break;
						case 'cek_username':
						    include "peserta/cek_username.php";
						    break;
						case 'bidang':
						    include "bidang/bidang.php";
						    break;
						case 'dokumen':
						    include "dokumen/dokumen.php";
						    break;
						case 'tahun_aktif':
						    include "pengaturan/tahun_aktif.php";
						    break;
						case 'hari_libur':
						    include "pengaturan/hari_libur.php";
						    break;
						case 'pengaturan_absensi':
						    include "pengaturan/absensi_setting.php";
						    break;
						case 'pengaturan_ttd':
						    include "pengaturan/ttd_setting.php";
						    break;
						case 'rekap_absensi':
						    include "rekap_absensi.php";
						    break;
						case 'users':
						    include "users/users.php";
						    break;
						case 'edit_users':
						    include "users/edit_users.php";
						    break;
						case 'delete_users':
						    include "users/delete_users.php";
						    break;
							echo "<center><h3>Maaf. Halaman tidak di temukan !</h3></center>";
							break;
					}

				?>
			</div>
		</div>
		<!--end page wrapper -->
		<!--start overlay-->
		<div class="overlay"></div>
		<!--end overlay-->
		<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<!--End Back To Top Button-->
		<footer class="page-footer">
			<p class="mb-0">Sistem PKL v1.0 &copy; <?= date('Y') ?></p>
		</footer>
	</div>
	<!--end wrapper-->
	<script src="../assets/js/bootstrap.bundle.min.js"></script>
	<script src="../assets/js/jquery.min.js"></script>
	<script src="../assets/plugins/simplebar/js/simplebar.min.js"></script>
	<script src="../assets/plugins/metismenu/js/metisMenu.min.js"></script>
	<script src="../assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
	<?php if ($need_datatables): ?>
	<script src="../assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
	<script src="../assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
	<?php endif; ?>
	<?php if ($need_tinymce): ?>
	<script src='https://cdn.tiny.cloud/1/vdqx2klew412up5bcbpwivg1th6nrh3murc6maz8bukgos4v/tinymce/5/tinymce.min.js' referrerpolicy="origin"></script>
	<?php endif; ?>
	<?php if ($need_fancyupload): ?>
	<script src="../assets/plugins/fancy-file-uploader/jquery.ui.widget.js"></script>
	<script src="../assets/plugins/fancy-file-uploader/jquery.fileupload.js"></script>
	<script src="../assets/plugins/fancy-file-uploader/jquery.iframe-transport.js"></script>
	<script src="../assets/plugins/fancy-file-uploader/jquery.fancy-fileupload.js"></script>
	<script src="../assets/plugins/Drag-And-Drop/dist/imageuploadify.min.js"></script>
	<?php endif; ?>
	<?php if ($need_apexcharts): ?>
	<script src="../assets/plugins/apexcharts-bundle/js/apexcharts.min.js"></script>
	<?php endif; ?>
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

		window.setTimeout(function() {
			$(".alert").fadeTo(1000, 0).slideUp(1000, function() {
				$(this).remove();
			});
		}, 2000);
	</script>
	<!--app JS-->
	<script src="../assets/js/app.js"></script>
</body>

</html>
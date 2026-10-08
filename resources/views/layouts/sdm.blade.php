<!doctype html>
<html lang="id" class="color-sidebar sidebarcolor3 color-header headercolor4">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />
	<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon" />
	<link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
	<link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
	<link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
	@if ($need_datatables ?? false)
	<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
	@endif
	@if ($need_fancyupload ?? false)
	<link href="{{ asset('assets/plugins/fancy-file-uploader/fancy_fileupload.css') }}" rel="stylesheet" />
	<link href="{{ asset('assets/plugins/Drag-And-Drop/dist/imageuploadify.min.css') }}" rel="stylesheet" />
	@endif
	@if ($need_apexcharts ?? false)
	<link href="{{ asset('assets/plugins/apexcharts-bundle/css/apexcharts.css') }}" rel="stylesheet" />
	@endif
	<link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />
	<script src="{{ asset('assets/js/pace.min.js') }}"></script>
	<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
	<link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<title>Website PKL</title>
</head>

<body>
	<div class="wrapper">
		<div class="sidebar-wrapper" data-simplebar="true">
			<div class="sidebar-header">
				<div>
					<img src="{{ asset('assets/images/logo.png') }}" width="60px" height="20px" alt="logo icon">
				</div>
				<div>
					<h4 class="logo-text">Dashboard</h4>
				</div>
				<div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i>
				</div>
			</div>
			<ul class="metismenu" id="menu">
				<li>
					<a href="{{ url('sdm') }}">
						<div class="parent-icon"><i class='bx bxs-dashboard'></i></div>
						<div class="menu-title">Dashboard</div>
					</a>
				</li>
				<li>
					<a href="{{ url('sdm/profile') }}">
						<div class="parent-icon"><i class='bx bxs-user-detail'></i></div>
						<div class="menu-title">Profile</div>
					</a>
					<a href="{{ url('sdm/approval-laporan') }}">
						<div class="parent-icon"><i class='bx bx-check-shield'></i></div>
						<div class="menu-title">Approval Laporan</div>
					</a>
					<a href="{{ url('sdm/approval-manager') }}">
						<div class="parent-icon"><i class='bx bx-user-check'></i></div>
						<div class="menu-title">Approval Manager</div>
					</a>
					<a href="{{ url('sdm/approval-absensi') }}">
						<div class="parent-icon"><i class='bx bx-calendar-edit'></i></div>
						<div class="menu-title">Approval Izin/Sakit</div>
					</a>
					<a href="{{ url('sdm/cetak-sertifikat') }}">
						<div class="parent-icon"><i class='bx bxs-certification'></i></div>
						<div class="menu-title">Cetak Sertifikat</div>
					</a>
					<a href="{{ url('sdm/rekap-absensi') }}">
						<div class="parent-icon"><i class='bx bx-calendar-check'></i></div>
						<div class="menu-title">Rekap Absensi</div>
					</a>
					<a href="{{ url('sdm/reset-password') }}">
						<div class="parent-icon"><i class='bx bxs-certification'></i></div>
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
						<li> <a href="{{ url('sdm/bidang') }}"><i class="bx bxs-group"></i>Data Bidang</a></li>
						<li> <a href="{{ url('sdm/peserta') }}"><i class="bx bxs-group"></i>Data Peserta</a></li>
						<li> <a href="{{ url('sdm/dokumen') }}"><i class="bx bxs-file-doc"></i>Data Dokumen</a></li>
					</ul>
				</li>
				<li class="menu-label">Pengaturan</li>
				<li>
					<a href="javascript:;" class="has-arrow">
						<div class="parent-icon"><i class='bx bxs-cog'></i></div>
						<div class="menu-title">Pengaturan</div>
					</a>
					<ul>
						<li>
							<a href="{{ url('sdm/pengaturan/hari-libur') }}"><i class="bx bx-calendar-x"></i>Hari Libur</a>
						</li>
						<li>
							<a href="{{ url('sdm/pengaturan/absensi') }}"><i class="bx bx-map-pin"></i>Lokasi & Radius</a>
						</li>
						<li>
							<a href="{{ url('sdm/pengaturan/ttd') }}"><i class="bx bxs-certification"></i>Tanda Tangan Sertifikat</a>
						</li>
					</ul>
				</li>
			</ul>
		</div>
		<header>
			<div class="topbar d-flex align-items-center">
				<nav class="navbar navbar-expand">
					<div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
					</div>
					<div class="user-box dropdown ms-auto">
						<a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<img src="{{ asset('assets/images/team.png') }}" class="user-img" alt="user avatar">
							<div class="user-info ps-3">
								<p class="user-name mb-0">{{ ucwords(auth()->user()->nama ?? auth()->user()->username) }}</p>
								<p class="designattion mb-0">{{ ucwords(auth()->user()->role) }}</p>
							</div>
						</a>
						<ul class="dropdown-menu dropdown-menu-end">
							<li><a class="dropdown-item" href="{{ url('sdm/profile') }}"><i class="bx bx-user"></i><span>Profile</span></a></li>
							<li><div class="dropdown-divider mb-0"></div></li>
							<li><a class="dropdown-item" href="{{ route('logout') }}"><i class='bx bx-log-out-circle'></i><span>Logout</span></a></li>
						</ul>
					</div>
				</nav>
			</div>
		</header>

		<div class="page-wrapper">
			<div class="page-content">
				@include($pageView)
			</div>
		</div>
		<div class="overlay"></div>
		<a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<footer class="page-footer">
			<p class="mb-0">Sistem PKL v1.0 &copy; {{ date('Y') }}</p>
		</footer>
	</div>
	<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
	<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
	@if ($need_datatables ?? false)
	<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
	@endif
	@if ($need_tinymce ?? false)
	<script src='https://cdn.tiny.cloud/1/vdqx2klew412up5bcbpwivg1th6nrh3murc6maz8bukgos4v/tinymce/5/tinymce.min.js' referrerpolicy="origin"></script>
	@endif
	@if ($need_fancyupload ?? false)
	<script src="{{ asset('assets/plugins/fancy-file-uploader/jquery.ui.widget.js') }}"></script>
	<script src="{{ asset('assets/plugins/fancy-file-uploader/jquery.fileupload.js') }}"></script>
	<script src="{{ asset('assets/plugins/fancy-file-uploader/jquery.iframe-transport.js') }}"></script>
	<script src="{{ asset('assets/plugins/fancy-file-uploader/jquery.fancy-fileupload.js') }}"></script>
	<script src="{{ asset('assets/plugins/Drag-And-Drop/dist/imageuploadify.min.js') }}"></script>
	@endif
	@if ($need_apexcharts ?? false)
	<script src="{{ asset('assets/plugins/apexcharts-bundle/js/apexcharts.min.js') }}"></script>
	@endif
	<script>
		window.showToast = function(message, type = 'success') {
			const Toast = Swal.mixin({
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timer: 3000,
				timerProgressBar: true
			});
			Toast.fire({ icon: type, title: message });
		};

		window.setTimeout(function() {
			$(".alert").fadeTo(1000, 0).slideUp(1000, function() {
				$(this).remove();
			});
		}, 2000);

		@if (session('success'))
			showToast(@json(session('success')), 'success');
		@endif
		@if (session('error'))
			showToast(@json(session('error')), 'error');
		@endif
	</script>
	<script src="{{ asset('assets/js/app.js') }}"></script>
	@yield('scripts')
</body>

</html>

<!doctype html>
<html lang="id">

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
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
	<link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/pln-theme.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/ui-kit.css') }}" />
	<link rel="stylesheet" href="{{ asset('assets/css/pkl-hud.css') }}" />
	@if ($need_profile_css ?? false)
	<link rel="stylesheet" href="{{ asset('assets/css/pkl-profile.css') }}" />
	@endif
	<style>
	/* Sidebar PKL: skin navy berwarna (hanya berlaku untuk .sidebar-wrapper.pkl-sb) */
	.sidebar-wrapper.pkl-sb {
		--sb-text: #b9c8e2;
		--sb-muted: #6f86ab;
		--sb-line: rgba(255, 255, 255, .08);
		--sb-blue-1: #2f9bff;
		--sb-blue-2: #0b6bcb;
		--sb-cyan: #35d4f0;
		--sb-yellow: #ffd400;
		background:
			radial-gradient(120% 55% at 100% 0%, rgba(47, 155, 255, .24) 0%, rgba(47, 155, 255, 0) 60%),
			radial-gradient(90% 40% at 0% 100%, rgba(53, 212, 240, .10) 0%, rgba(53, 212, 240, 0) 60%),
			linear-gradient(180deg, #0a1a33 0%, #081426 100%) !important;
		border-right: 1px solid rgba(255, 255, 255, .06) !important;
		box-shadow: 8px 0 30px rgba(5, 15, 35, .35);
	}
	.sidebar-wrapper.pkl-sb .simplebar-content { display: flex; flex-direction: column; min-height: 100%; }
	.sidebar-wrapper.pkl-sb .simplebar-scrollbar:before { background: rgba(255, 255, 255, .28) !important; }

	/* Header & logo */
	.sidebar-wrapper.pkl-sb .sidebar-header { position: fixed; top: 0; bottom: auto; z-index: 6; background: rgba(10, 26, 51, .92) !important; -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); border-bottom: 1px solid var(--sb-line) !important; height: auto; min-height: 68px; padding: 14px 14px !important; gap: 10px; }
	.sidebar-wrapper.pkl-sb .pkl-brand-logo { width: 44px; height: 44px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; padding: 0; overflow: hidden; background: #fff; border-radius: 12px; box-shadow: 0 6px 16px rgba(0, 0, 0, .28), 0 0 0 3px rgba(255, 255, 255, .08); }
	.sidebar-wrapper.pkl-sb .pkl-brand-logo img { width: 100% !important; height: 100% !important; max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
	.sidebar-wrapper.pkl-sb .pkl-brand-text { display: flex; flex-direction: column; flex: 1 1 auto; min-width: 0; }
	.sidebar-wrapper.pkl-sb .pkl-brand-title { display: flex; align-items: center; gap: 7px; color: #fff !important; font-family: 'Outfit', 'Inter', sans-serif; font-size: 15px; font-weight: 700; letter-spacing: .9px; text-transform: uppercase; line-height: 1.1; }
	.sidebar-wrapper.pkl-sb .pkl-brand-title::after { content: ''; width: 7px; height: 7px; border-radius: 50%; background: #2be28f; box-shadow: 0 0 8px #2be28f; animation: pklSbPulse 2s infinite; }
	.sidebar-wrapper.pkl-sb .pkl-brand-sub { display: block; margin-top: 4px; color: var(--sb-cyan) !important; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 9.5px; font-weight: 600; letter-spacing: 1.6px; text-transform: uppercase; }
	.sidebar-wrapper.pkl-sb .toggle-icon { color: #8fa5c8 !important; }
	.sidebar-wrapper.pkl-sb .toggle-icon:hover { color: #fff !important; }

	/* Daftar menu */
	.sidebar-wrapper.pkl-sb .metismenu { background: transparent !important; padding: 12px 12px 8px; }
	.sidebar-wrapper.pkl-sb .metismenu a { color: var(--sb-text) !important; border: 0 !important; border-left: 0 !important; border-radius: 12px !important; padding: 8px 10px !important; margin: 2px 0; font-size: 13.5px; font-weight: 600; transition: background .18s ease, color .18s ease, box-shadow .18s ease; }
	.sidebar-wrapper.pkl-sb .metismenu a:hover { background: rgba(255, 255, 255, .07) !important; color: #fff !important; }
	.sidebar-wrapper.pkl-sb .metismenu .parent-icon { width: 32px; height: 32px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px !important; border-radius: 10px; font-size: 17px !important; color: var(--ic, #5db8ff) !important; background: var(--icbg, rgba(93, 184, 255, .16)) !important; transition: background .18s ease, color .18s ease; }
	.sidebar-wrapper.pkl-sb .metismenu > li:nth-child(6n+1) { --ic: #5db8ff; --icbg: rgba(93, 184, 255, .16); }
	.sidebar-wrapper.pkl-sb .metismenu > li:nth-child(6n+2) { --ic: #ffc24d; --icbg: rgba(255, 194, 77, .16); }
	.sidebar-wrapper.pkl-sb .metismenu > li:nth-child(6n+3) { --ic: #3ddc97; --icbg: rgba(61, 220, 151, .16); }
	.sidebar-wrapper.pkl-sb .metismenu > li:nth-child(6n+4) { --ic: #b08cff; --icbg: rgba(176, 140, 255, .18); }
	.sidebar-wrapper.pkl-sb .metismenu > li:nth-child(6n+5) { --ic: #ff7eb6; --icbg: rgba(255, 126, 182, .16); }
	.sidebar-wrapper.pkl-sb .metismenu > li:nth-child(6n+6) { --ic: #2fd3d3; --icbg: rgba(47, 211, 211, .16); }

	/* Menu aktif */
	.sidebar-wrapper.pkl-sb .metismenu > li.mm-active > a { background: linear-gradient(135deg, var(--sb-blue-1), var(--sb-blue-2)) !important; color: #fff !important; box-shadow: 0 8px 20px rgba(11, 107, 203, .45); }
	.sidebar-wrapper.pkl-sb .metismenu > li.mm-active > a .parent-icon { background: rgba(255, 255, 255, .2) !important; color: #fff !important; }
	.sidebar-wrapper.pkl-sb .metismenu > li.mm-active > a.has-arrow { background: rgba(47, 155, 255, .16) !important; box-shadow: inset 0 0 0 1px rgba(47, 155, 255, .35); }
	.sidebar-wrapper.pkl-sb .metismenu > li.mm-active > a.has-arrow .parent-icon { background: var(--icbg) !important; color: var(--ic) !important; }
	.sidebar-wrapper.pkl-sb .metismenu .has-arrow::after { border-color: #7f95b8 !important; }
	.sidebar-wrapper.pkl-sb .metismenu li.mm-active > .has-arrow::after,
	.sidebar-wrapper.pkl-sb .metismenu .has-arrow:hover::after { border-color: #fff !important; }

	/* Badge jumlah */
	.sidebar-wrapper.pkl-sb .pkl-count-badge { background: var(--sb-yellow) !important; color: #1b1b1b !important; font-size: 10.5px; font-weight: 800; line-height: 1; padding: 4px 8px; border-radius: 999px; box-shadow: 0 0 0 3px rgba(255, 212, 0, .18); }

	/* Label grup */
	.sidebar-wrapper.pkl-sb .metismenu .menu-label { color: var(--sb-muted) !important; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 9.5px; font-weight: 600; letter-spacing: 1.6px; text-transform: uppercase; margin-top: 12px; padding: 16px 10px 6px !important; border-top: 1px solid var(--sb-line); }

	/* Submenu */
	.sidebar-wrapper.pkl-sb .metismenu ul { background: transparent !important; margin: 2px 0 4px 26px; padding: 2px 0 2px 12px; border-left: 1px dashed rgba(255, 255, 255, .16); }
	.sidebar-wrapper.pkl-sb .metismenu ul a { padding: 7px 10px !important; font-size: 12.5px; font-weight: 500; color: #9db0cf !important; }
	.sidebar-wrapper.pkl-sb .metismenu ul a i { margin-right: 8px; font-size: 15px; color: #5d7aa6; transition: color .18s ease; }
	.sidebar-wrapper.pkl-sb .metismenu ul li.mm-active > a { background: rgba(53, 212, 240, .12) !important; color: var(--sb-cyan) !important; font-weight: 700; }
	.sidebar-wrapper.pkl-sb .metismenu ul li.mm-active > a i,
	.sidebar-wrapper.pkl-sb .metismenu ul a:hover i { color: var(--sb-cyan); }

	/* Footer */
	.sidebar-wrapper.pkl-sb .pkl-sb-foot { margin-top: auto; padding: 14px 18px 16px; display: flex; align-items: center; gap: 8px; border-top: 1px solid var(--sb-line); color: var(--sb-muted); font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 10px; font-weight: 500; letter-spacing: .6px; white-space: nowrap; }
	.sidebar-wrapper.pkl-sb .pkl-sb-foot i { font-size: 15px; color: var(--sb-cyan); }

	/* Sidebar dilipat */
	.wrapper.toggled:not(.sidebar-hovered) .sidebar-wrapper.pkl-sb .metismenu .parent-icon { margin-right: 0 !important; }
	.wrapper.toggled:not(.sidebar-hovered) .sidebar-wrapper.pkl-sb .metismenu a { padding: 8px 6px !important; justify-content: center; }
	.wrapper.toggled:not(.sidebar-hovered) .sidebar-wrapper.pkl-sb .metismenu .menu-label,
	.wrapper.toggled:not(.sidebar-hovered) .sidebar-wrapper.pkl-sb .pkl-sb-foot { display: none; }
	.wrapper.toggled:not(.sidebar-hovered) .sidebar-wrapper.pkl-sb .pkl-brand-logo { width: 40px; height: 40px; }

	/* Badge angka saat sidebar dilipat: tempel di pojok ikon agar ikon tidak terpotong */
	.wrapper.toggled:not(.sidebar-hovered) .sidebar-wrapper.pkl-sb .metismenu .pkl-count-badge {
		position: absolute !important;
		top: 2px;
		right: 2px;
		margin: 0 !important;
		min-width: 16px;
		height: 16px;
		padding: 0 4px;
		font-size: 9px;
		line-height: 16px;
		text-align: center;
		box-shadow: 0 0 0 2px #0a1a33;
		z-index: 1;
	}

	@@keyframes pklSbPulse {
		0%   { box-shadow: 0 0 0 0 rgba(43, 226, 143, .6); }
		70%  { box-shadow: 0 0 0 7px rgba(43, 226, 143, 0); }
		100% { box-shadow: 0 0 0 0 rgba(43, 226, 143, 0); }
	}
	@@media (prefers-reduced-motion: reduce) {
		.sidebar-wrapper.pkl-sb .pkl-brand-title::after { animation: none; }
		.sidebar-wrapper.pkl-sb .metismenu a { transition: none; }
	}
	</style>
	<title>Website PKL</title>
</head>

<body>
	@php
		// Jumlah pengajuan izin/sakit yang menunggu persetujuan (badge menu & topbar)
		$pend_izin = (int) \Illuminate\Support\Facades\DB::table('absensi_peserta')->where('approval_status', 'Pending')->count();
	@endphp
	<div class="wrapper">
		<div class="sidebar-wrapper pkl-sb" data-simplebar="true">
			<div class="sidebar-header">
				<div class="pkl-brand-logo">
					<img src="{{ asset('assets/images/logo-2.png') }}" alt="Logo PLN Nusantara Power">
				</div>
				<div class="logo-text pkl-brand-text">
					<span class="pkl-brand-title">Sistem PKL</span>
					<span class="pkl-brand-sub">Nusantara Power</span>
				</div>
				<div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i>
				</div>
			</div>
			@php
				// Penanda menu aktif: cocokkan alamat halaman yang sedang dibuka
				$on = fn (...$p) => request()->is(...$p);
				$grpLaporan = $on('sdm/approval-laporan*', 'sdm/approval-manager*', 'sdm/backup-laporan*', 'sdm/hapus-laporan*');
				$grpMaster  = $on('sdm/bidang*', 'sdm/peserta*', 'sdm/dokumen*');
				$grpSetting = $on('sdm/pengaturan*');
			@endphp
			<ul class="metismenu" id="menu">
				<li class="{{ $on('sdm') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm') }}">
						<div class="parent-icon"><i class='bx bxs-dashboard'></i></div>
						<div class="menu-title">Dashboard</div>
					</a>
				</li>
				<li class="{{ $on('sdm/profile*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/profile') }}">
						<div class="parent-icon"><i class='bx bxs-user-detail'></i></div>
						<div class="menu-title">Profile</div>
					</a>
				</li>
				<li class="{{ $on('sdm/approval-absensi*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/approval-absensi') }}">
						<div class="parent-icon"><i class='bx bx-calendar-edit'></i></div>
						<div class="menu-title">Approval Izin/Sakit</div>
						@if ($pend_izin > 0)
						<span class="badge pkl-count-badge ms-auto">{{ $pend_izin }}</span>
						@endif
					</a>
				</li>
				<li class="{{ $on('sdm/cetak-sertifikat*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/cetak-sertifikat') }}">
						<div class="parent-icon"><i class='bx bxs-certification'></i></div>
						<div class="menu-title">Cetak Sertifikat</div>
					</a>
				</li>
				<li class="{{ $on('sdm/edit-sertifikat*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/edit-sertifikat') }}">
						<div class="parent-icon"><i class='bx bx-edit'></i></div>
						<div class="menu-title">Edit Sertifikat</div>
					</a>
				</li>
				<li class="{{ $on('sdm/rekap-absensi*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/rekap-absensi') }}">
						<div class="parent-icon"><i class='bx bx-calendar-check'></i></div>
						<div class="menu-title">Rekap Absensi</div>
					</a>
				</li>
				<li class="{{ $on('sdm/kuota*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/kuota') }}">
						<div class="parent-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
						<div class="menu-title">Lihat Kuota</div>
					</a>
				</li>
				<li class="{{ $on('sdm/reset-password*') ? 'mm-active' : '' }}">
					<a href="{{ url('sdm/reset-password') }}">
						<div class="parent-icon"><i class='bx bxs-certification'></i></div>
						<div class="menu-title">Reset Password</div>
					</a>
				</li>

				<li class="menu-label">Laporan</li>
				<li class="{{ $grpLaporan ? 'mm-active' : '' }}">
					<a href="javascript:;" class="has-arrow" aria-expanded="{{ $grpLaporan ? 'true' : 'false' }}">
						<div class="parent-icon"><i class='bx bxs-report'></i></div>
						<div class="menu-title">Laporan</div>
					</a>
					<ul class="{{ $grpLaporan ? 'mm-show' : '' }}">
						<li class="{{ $on('sdm/approval-laporan*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/approval-laporan') }}"><i class="bx bx-check-shield"></i>Approval Laporan</a>
						</li>
						<li class="{{ $on('sdm/approval-manager*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/approval-manager') }}"><i class="bx bx-user-check"></i>Approval Manager</a>
						</li>
						<li class="{{ $on('sdm/backup-laporan*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/backup-laporan') }}"><i class="bx bx-download"></i>Backup Laporan</a>
						</li>
						<li class="{{ $on('sdm/hapus-laporan*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/hapus-laporan') }}"><i class="bx bx-trash"></i>Hapus Laporan</a>
						</li>
					</ul>
				</li>

				<li class="menu-label">Master Data</li>
				<li class="{{ $grpMaster ? 'mm-active' : '' }}">
					<a href="javascript:;" class="has-arrow" aria-expanded="{{ $grpMaster ? 'true' : 'false' }}">
						<div class="parent-icon"><i class='bx bxs-folder-open'></i></div>
						<div class="menu-title">Master Data</div>
					</a>
					<ul class="{{ $grpMaster ? 'mm-show' : '' }}">
						<li class="{{ $on('sdm/bidang*') ? 'mm-active' : '' }}"> <a href="{{ url('sdm/bidang') }}"><i class="bx bxs-group"></i>Data Bidang</a></li>
						<li class="{{ $on('sdm/peserta*') ? 'mm-active' : '' }}"> <a href="{{ url('sdm/peserta') }}"><i class="bx bxs-group"></i>Data Peserta</a></li>
						<li class="{{ $on('sdm/dokumen*') ? 'mm-active' : '' }}"> <a href="{{ url('sdm/dokumen') }}"><i class="bx bxs-file-doc"></i>Data Dokumen</a></li>
					</ul>
				</li>

				<li class="menu-label">Pengaturan</li>
				<li class="{{ $grpSetting ? 'mm-active' : '' }}">
					<a href="javascript:;" class="has-arrow" aria-expanded="{{ $grpSetting ? 'true' : 'false' }}">
						<div class="parent-icon"><i class='bx bxs-cog'></i></div>
						<div class="menu-title">Pengaturan</div>
					</a>
					<ul class="{{ $grpSetting ? 'mm-show' : '' }}">
						<li class="{{ $on('sdm/pengaturan/hari-libur*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/pengaturan/hari-libur') }}"><i class="bx bx-calendar-x"></i>Hari Libur</a>
						</li>
						<li class="{{ $on('sdm/pengaturan/absensi*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/pengaturan/absensi') }}"><i class="bx bx-map-pin"></i>Lokasi & Radius</a>
						</li>
						<li class="{{ $on('sdm/pengaturan/ttd*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/pengaturan/ttd') }}"><i class="bx bxs-certification"></i>Tanda Tangan Sertifikat</a>
						</li>
						<li class="{{ $on('sdm/pengaturan/laporan*') ? 'mm-active' : '' }}">
							<a href="{{ url('sdm/pengaturan/laporan') }}"><i class="bx bx-file"></i>Laporan</a>
						</li>
					</ul>
				</li>
			</ul>
			<div class="pkl-sb-foot"><i class='bx bx-buildings'></i><span>PT PLN Nusantara Power</span></div>
		</div>
		<header>
			<div class="topbar d-flex align-items-center">
				<nav class="navbar navbar-expand">
					<div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
					</div>
					<div class="pkl-sys-pill d-none d-md-inline-flex"><span class="pkl-dot"></span><span>SYSTEM ONLINE &bull; <span id="pklClock">--:--:--</span> WIB</span></div>
					<div class="ms-auto d-flex align-items-center gap-3">
					@if ($pend_izin > 0)
					<a href="{{ url('sdm/approval-absensi') }}" class="pkl-notif" title="Pengajuan izin/sakit menunggu">
						<i class="bx bx-bell"></i><span class="pkl-notif-cnt">{{ $pend_izin }}</span><span class="d-none d-lg-inline">Pengajuan</span>
					</a>
					@endif
					<div class="user-box dropdown">
						<a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<img src="{{ auth()->user()->avatar_url }}" class="user-img" alt="user avatar">
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

		@if (session('success') && !session('approval_result'))
			showToast(@json(session('success')), 'success');
		@endif
		@if (session('error') && !session('approval_result'))
			showToast(@json(session('error')), 'error');
		@endif
	</script>
	<script>
		(function () {
			var el = document.getElementById('pklClock');
			if (!el) return;
			var fmt = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
			function tick() { el.textContent = fmt.format(new Date()); }
			tick();
			setInterval(tick, 1000);
		})();
	</script>
	<script src="{{ asset('assets/js/app.js') }}"></script>
	@include('partials.approval_ui')
	@include('partials.ui_kit')
	@yield('scripts')
</body>

</html>
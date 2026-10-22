@php
    $pend_count = $pending_count ?? (int) \Illuminate\Support\Facades\DB::table('laporan_magang')
        ->where('sdm_status', 'Disetujui')
        ->where('manager_status', 'Menunggu')
        ->count();
@endphp
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
    @if ($need_apexcharts ?? false)
    <link href="{{ asset('assets/plugins/apexcharts-bundle/css/apexcharts.css') }}" rel="stylesheet" />
    @endif
    @if ($need_datatables ?? false)
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
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
    <link rel="stylesheet" href="{{ asset('assets/css/pkl-hud.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Website PKL - Manager</title>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <div class="pkl-brand-logo">
                    <img src="{{ asset('assets/images/logo.png') }}" width="60px" height="20px" alt="logo icon">
                </div>
                <div class="logo-text pkl-brand-text">
                    <span class="pkl-brand-title">Sistem PKL</span>
                    <span class="pkl-brand-sub">Manager Portal</span>
                </div>
                <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i></div>
            </div>
            <ul class="metismenu" id="menu">
                <li class="{{ request()->is('manager') ? 'mm-active' : '' }}">
                    <a href="{{ url('manager') }}">
                        <div class="parent-icon"><i class='bx bxs-dashboard'></i></div>
                        <div class="menu-title">Dashboard</div>
                    </a>
                </li>
                <li class="{{ request()->is('manager/approval-laporan*') ? 'mm-active' : '' }}">
                    <a href="{{ url('manager/approval-laporan') }}">
                        <div class="parent-icon"><i class='bx bx-check-shield'></i></div>
                        <div class="menu-title">Approval Laporan</div>
                        @if ($pend_count > 0)
                        <span class="badge pkl-count-badge pkl-count-badge--warn ms-auto me-2">{{ $pend_count }}</span>
                        @endif
                    </a>
                </li>
                <li class="{{ request()->is('manager/kuota*') ? 'mm-active' : '' }}">
                    <a href="{{ url('manager/kuota') }}">
                        <div class="parent-icon"><i class='bx bx-bar-chart-alt-2'></i></div>
                        <div class="menu-title">Lihat Kuota</div>
                    </a>
                </li>
                <li class="{{ request()->is('manager/profile*') ? 'mm-active' : '' }}">
                    <a href="{{ url('manager/profile') }}">
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
                    <div class="pkl-sys-pill d-none d-md-inline-flex"><span class="pkl-dot"></span><span>SYSTEM ONLINE &bull; <span id="pklClock">--:--:--</span> WIB</span></div>
					<div class="ms-auto d-flex align-items-center gap-3">
                    @if ($pend_count > 0)
                    <a href="{{ url('manager/approval-laporan') }}" class="pkl-notif" title="Laporan menunggu persetujuan">
                        <i class="bx bx-bell"></i><span class="pkl-notif-cnt">{{ $pend_count }}</span><span class="d-none d-lg-inline">Laporan</span>
                    </a>
                    @endif
					<div class="user-box dropdown">
                        <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('assets/images/team.png') }}" class="user-img" alt="user avatar">
                            <div class="user-info ps-3">
                                <p class="user-name mb-0">{{ ucwords(auth()->user()->nama ?? auth()->user()->username) }}</p>
                                <p class="designattion mb-0">{{ ucwords(auth()->user()->role) }}</p>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ url('manager/profile') }}"><i class="bx bx-user"></i><span>Profile</span></a></li>
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
            <p class="mb-0">Copyright &copy; 2026. Manager Portal</p>
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
    @if ($need_apexcharts ?? false)
    <script src="{{ asset('assets/plugins/apexcharts-bundle/js/apexcharts.min.js') }}"></script>
    @endif
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

        @if (session('success') && !session('approval_result'))
            showToast(@json(session('success')), 'success');
        @endif
        @if (session('error') && !session('approval_result'))
            showToast(@json(session('error')), 'error');
        @endif
    </script>
    @include('partials.approval_ui')
    @yield('scripts')
</body>
</html>
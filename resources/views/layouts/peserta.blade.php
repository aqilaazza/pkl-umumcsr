<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0b4fb3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="Portal Peserta PKL - Absensi, Laporan & Sertifikat Magang">
    <title>PKL Magang</title>

    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="/assets/images/logo.png">
    <link rel="manifest" href="/manifest.json">

    <!-- CSS -->
    <link href="/assets/css/mobile-app.css" rel="stylesheet">
    <link href="/assets/css/peserta-theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Page body reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior: none;
            background-color: #f1f5fb;
            background-image: radial-gradient(#d3dcec 1px, transparent 1px);
            background-size: 18px 18px;
        }

        /* Hide scrollbar but allow scroll */
        body::-webkit-scrollbar { width: 0; height: 0; }

        /* Prevent iOS bounce */
        html { overflow: hidden; height: 100%; }
        body { overflow-y: auto; height: 100%; -webkit-overflow-scrolling: touch; }

        /* ───────── Layout baru ───────── */
        .pk-shell { max-width: 480px; margin: 0 auto; position: relative; }

        /* Top bar (sticky card) */
        .pk-topbar {
            position: sticky; top: 0; z-index: 40;
            padding: calc(10px + env(safe-area-inset-top)) 12px 6px;
            background: linear-gradient(#f1f5fb 70%, rgba(241,245,251,0));
        }
        .pk-topbar-card {
            display: flex; align-items: center; gap: 10px;
            background: #fff; border-radius: 18px; padding: 10px 12px;
            box-shadow: 0 6px 20px rgba(24, 60, 120, .08);
            border: 1px solid rgba(230, 236, 245, .9);
        }
        .pk-logo {
            width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0;
            background: #e8f0ff; color: #0b4fb3;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .pk-logo img { width: 100%; height: 100%; object-fit: contain; }
        .pk-logo i { font-size: 22px; }
        .pk-topbar-text { flex: 1; min-width: 0; }
        .pk-topbar-eyebrow { font-size: 9.5px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase; color: #0b4fb3; }
        .pk-topbar-title {
            font-size: 17px; font-weight: 800; color: #16233b; line-height: 1.15;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .pk-topbar-btn {
            position: relative; width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #1e6be0, #0b4fb3); color: #fff;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 20px; flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(11, 79, 179, .3);
        }
        .pk-topbar-btn::after {
            content: ''; position: absolute; right: 0; bottom: 0;
            width: 10px; height: 10px; border-radius: 50%;
            background: #22c55e; border: 2px solid #fff;
        }

        /* Area konten (menimpa padding bawaan agar pas dengan header & nav baru) */
        .app-content.pk-content {
            padding: 6px 12px 110px !important;
            margin: 0 !important;
            max-width: none !important;
        }

        /* ───────── Bottom navigation: pil melayang ───────── */
        .pk-nav {
            position: fixed; left: 50%; transform: translateX(-50%);
            bottom: calc(12px + env(safe-area-inset-bottom));
            width: calc(100% - 24px); max-width: 456px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(255, 255, 255, .96);
            -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px);
            border-radius: 28px; padding: 7px;
            box-shadow: 0 12px 32px rgba(20, 60, 130, .20);
            border: 1px solid #e6ecf5; z-index: 50;
        }
        .pk-nav-ind { display: none; }

        .pk-nav-item {
            position: relative; z-index: 1;
            display: flex; align-items: center; justify-content: center;
            padding: 0; text-decoration: none; color: #7b8aa0;
            font-weight: 700; -webkit-tap-highlight-color: transparent;
        }
        .pk-nav-item i {
            width: 24px; font-size: 23px; line-height: 1; text-align: center; flex-shrink: 0;
            transition: transform .2s ease;
        }
        .pk-lbl { white-space: nowrap; }
        .pk-nav-item:focus-visible { outline: 3px solid rgba(47, 124, 240, .5); outline-offset: 2px; }

        /* Ikon melonjak saat item baru dipilih */
        @@keyframes pkPop {
            0%   { transform: scale(.7); }
            55%  { transform: scale(1.22); }
            100% { transform: scale(1); }
        }
        .pk-nav-item.pop i { animation: pkPop .5s cubic-bezier(.34, 1.4, .64, 1); }

        /* Brand di sidebar (hanya tampil di desktop) */
        .pk-nav-brand { display: none; }

        /* ── Mode HP & tablet: ikon saja, label muncul di item aktif ── */
        @@media (max-width: 991.98px) {
            .pk-nav-ind {
                display: block; position: absolute; top: 0; left: 0; z-index: 0;
                border-radius: 22px; background: #e3eeff; opacity: 0; pointer-events: none;
                box-shadow: inset 0 0 0 1px rgba(11, 79, 179, .06);
                will-change: transform, width;
            }
            .pk-nav-item {
                flex: 1 1 0; min-width: 0; height: 50px; border-radius: 22px;
                transition: flex-grow .4s cubic-bezier(.4, 0, .2, 1), color .2s ease;
            }
            .pk-nav-item.active { flex-grow: 2.7; color: #0b4fb3; }
            .pk-nav-item:active i { transform: scale(.8); }

            .pk-lbl {
                max-width: 0; margin-left: 0; opacity: 0; overflow: hidden;
                font-size: 12px; transform: translateX(-6px);
                transition: max-width .4s cubic-bezier(.4, 0, .2, 1), margin .4s cubic-bezier(.4, 0, .2, 1), opacity .15s ease, transform .15s ease;
            }
            .pk-nav-item.active .pk-lbl {
                max-width: 80px; margin-left: 6px; opacity: 1; transform: none;
                transition-delay: 0s, 0s, .1s, .1s;
                transition-duration: .4s, .4s, .3s, .35s;
            }

            /* Gelembung keterangan saat ikon ditekan (tahan) */
            .pk-nav-item:not(.active)::after {
                content: attr(data-label);
                position: absolute; bottom: calc(100% + 12px); left: 50%;
                transform: translate(-50%, 6px) scale(.85);
                background: #16233b; color: #fff; font-size: 11px; font-weight: 700;
                padding: 5px 11px; border-radius: 10px; white-space: nowrap;
                opacity: 0; pointer-events: none;
                box-shadow: 0 6px 16px rgba(22, 35, 59, .25);
                transition: opacity .15s ease, transform .2s cubic-bezier(.34, 1.4, .64, 1);
            }
            .pk-nav-item:not(.active):active::after { opacity: 1; transform: translate(-50%, 0) scale(1); }
        }

        /* Responsif: layar kecil */
        @@media (max-width: 360px) {
            .pk-topbar { padding-left: 8px; padding-right: 8px; }
            .pk-topbar-title { font-size: 15px; }
            .app-content.pk-content { padding-left: 8px !important; padding-right: 8px !important; }
            .pk-nav { width: calc(100% - 16px); padding: 5px; border-radius: 24px; }
            .pk-nav-item i { width: 22px; font-size: 21px; }
            .pk-lbl { font-size: 11px; }
        }

        /* Tablet (>=768px): konten melebar, menu bawah tetap */
        @@media (min-width: 768px) {
            .pk-shell { max-width: none; }
            .pk-topbar { padding-left: 20px; padding-right: 20px; }
            .pk-topbar-card { max-width: 960px; margin: 0 auto; }
            .app-content.pk-content {
                max-width: 960px !important;
                margin: 0 auto !important;
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
            .pk-nav { max-width: 560px; }
        }

        /* Desktop (>=992px): sidebar kiri, label selalu tampil */
        @@media (min-width: 992px) {
            .pk-shell { margin-left: 240px; }
            .pk-topbar { padding: 18px 28px 8px; }
            .pk-topbar-card { max-width: 1240px; }
            .pk-topbar .pk-logo { display: none; }
            .pk-topbar-title { font-size: 20px; }
            .app-content.pk-content {
                max-width: 1240px !important;
                padding: 8px 28px 40px !important;
            }

            .pk-nav {
                left: 0; top: 0; bottom: 0; transform: none;
                width: 240px; max-width: none;
                flex-direction: column; align-items: stretch; justify-content: flex-start; gap: 6px;
                padding: 20px 14px;
                border-radius: 0 28px 28px 0;
                border: 0; border-right: 1px solid #e6ecf5;
                box-shadow: 6px 0 28px rgba(20, 60, 130, .08);
            }
            .pk-nav-brand {
                display: flex; align-items: center; gap: 10px;
                padding: 4px 8px 18px; margin-bottom: 8px;
                border-bottom: 1px dashed #e6ecf5;
            }
            .pk-nav-brand-sub { font-size: 15px; font-weight: 800; color: #16233b; line-height: 1.2; }
            .pk-nav-item {
                justify-content: flex-start; gap: 12px; font-size: 14px;
                padding: 12px 14px; border-radius: 14px;
                transition: background .2s ease, color .2s ease;
            }
            .pk-nav-item i { font-size: 22px; }
            .pk-nav-item.active { background: #e3eeff; color: #0b4fb3; }
            .pk-nav-item:hover:not(.active) { background: #f3f6fb; }
        }

        /* Layar pendek (mode landscape) */
        @@media (max-height: 480px) and (max-width: 991.98px) {
            .pk-nav { bottom: 6px; padding: 4px; }
            .pk-nav-item { height: 42px; }
            .app-content.pk-content { padding-bottom: 84px !important; }
        }

        @@media (prefers-reduced-motion: reduce) {
            .pk-nav-item, .pk-nav-item i, .pk-lbl, .pk-nav-item::after { transition: none !important; }
            .pk-nav-item.pop i { animation: none; }
        }
    </style>
</head>
<body class="mobile-app">
    @php
        $nav_now = $active_nav ?? '';
        $nama_user = ucwords(auth()->user()->nama ?? 'Peserta');

        // Menu bawah: [kunci, route, label, ikon biasa, ikon aktif]
        $nav_items = [
            ['home',       'peserta.home',       'Home',       'bx-home',           'bxs-home'],
            ['absensi',    'peserta.absensi',    'Presensi',   'bx-calendar-check', 'bxs-calendar-check'],
            ['laporan',    'peserta.laporan',    'Laporan',    'bx-file',           'bxs-file'],
            ['dokumen',    'peserta.dokumen',    'Dokumen',    'bx-folder-open',    'bxs-folder-open'],
            ['sertifikat', 'peserta.sertifikat', 'Sertifikat', 'bx-award',          'bxs-award'],
            ['profil',     'peserta.profile',    'Profil',     'bx-user',           'bxs-user'],
        ];
    @endphp

    <div class="pk-shell">

        <!-- ═══ Top App Bar ═══ -->
        <div class="pk-topbar">
            <div class="pk-topbar-card">
                <div class="pk-logo">
                    <img src="/assets/images/logo.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <i class='bx bxs-briefcase-alt-2' style="display:none;"></i>
                </div>
                <div class="pk-topbar-text">
                    <div class="pk-topbar-eyebrow">Sinfo PKL</div>
                    <div class="pk-topbar-title">{{ $nama_user }}</div>
                </div>
                <a href="{{ route('peserta.profile') }}" class="pk-topbar-btn" title="Profil">
                    <i class='bx bxs-user'></i>
                </a>
            </div>
        </div>

        <!-- ═══ Main Content ═══ -->
        <div class="app-content pk-content">
            @yield('content')
        </div>
    </div>

    <!-- ═══ Bottom Navigation ═══ -->
    <nav class="pk-nav" id="pkNav" data-active="{{ $nav_now }}" aria-label="Menu utama">
        <span class="pk-nav-ind" id="pkNavInd" aria-hidden="true"></span>
        <div class="pk-nav-brand">
            <div class="pk-logo">
                <img src="/assets/images/logo.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <i class='bx bxs-briefcase-alt-2' style="display:none;"></i>
            </div>
            <div>
                <div class="pk-topbar-eyebrow">Sinfo PKL</div>
                <div class="pk-nav-brand-sub">Portal Peserta</div>
            </div>
        </div>
        @foreach ($nav_items as $n)
        <a href="{{ route($n[1]) }}"
           class="pk-nav-item {{ $nav_now === $n[0] ? 'active' : '' }}"
           data-key="{{ $n[0] }}" data-label="{{ $n[2] }}" data-off="{{ $n[3] }}" data-on="{{ $n[4] }}"
           aria-label="{{ $n[2] }}" @if ($nav_now === $n[0]) aria-current="page" @endif>
            <i class='bx {{ $nav_now === $n[0] ? $n[4] : $n[3] }}'></i>
            <span class="pk-lbl">{{ $n[2] }}</span>
        </a>
        @endforeach
    </nav>

    <!-- ═══ Toast Container ═══ -->
    <div id="toast-container"></div>

    <!-- ═══ Scripts ═══ -->
    <script>
    // Ripple effect
    document.querySelectorAll('.ripple, .action-btn, .clock-btn').forEach(el => {
        el.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Toast notification using SweetAlert2
    function showToast(message, type = 'success', duration = 3000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    // Confirmation Alert
    function showAlert(title, text, icon = 'info', confirmButtonText = 'OK') {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonText: confirmButtonText,
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });
    }

    // ═══ Bottom nav: ikon saja, label + gelembung geser ke item yang dipilih ═══
    (function () {
        const nav   = document.getElementById('pkNav');
        const ind   = document.getElementById('pkNavInd');
        if (!nav || !ind) return;

        const items  = Array.from(nav.querySelectorAll('.pk-nav-item'));
        const mobile = window.matchMedia('(max-width: 991.98px)');
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
        const serverKey = nav.dataset.active;

        // Letakkan gelembung tepat di bawah item aktif
        function place() {
            const a = nav.querySelector('.pk-nav-item.active');
            if (!a || !mobile.matches) { ind.style.opacity = 0; return; }
            const n = nav.getBoundingClientRect(), r = a.getBoundingClientRect();
            ind.style.width     = r.width + 'px';
            ind.style.height    = r.height + 'px';
            ind.style.transform = 'translate(' + (r.left - n.left - nav.clientLeft) + 'px,' + (r.top - n.top - nav.clientTop) + 'px)';
            ind.style.opacity   = 1;
        }

        // Ikuti perubahan lebar item selama animasi berlangsung
        function follow(ms) {
            const t0 = performance.now();
            (function loop(t) {
                place();
                if (t - t0 < ms) requestAnimationFrame(loop);
            })(t0);
        }

        function setActive(key, animate) {
            items.forEach(function (i) {
                const on = i.dataset.key === key;
                i.classList.toggle('active', on);
                i.classList.remove('pop');
                if (on) i.setAttribute('aria-current', 'page'); else i.removeAttribute('aria-current');
                const ico = i.querySelector('i');
                ico.classList.remove(i.dataset.on, i.dataset.off);
                ico.classList.add(on ? i.dataset.on : i.dataset.off);
                if (on && animate) { void i.offsetWidth; i.classList.add('pop'); }
            });
        }

        // Posisi awal tanpa animasi
        place();
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(place);
        window.addEventListener('load', place);
        window.addEventListener('resize', place);
        if (mobile.addEventListener) mobile.addEventListener('change', place);

        // Kembali lewat tombol "back" (cache browser): pulihkan menu ke halaman ini
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { setActive(serverKey, false); place(); }
        });

        // Ketuk item lain: animasikan dulu, baru pindah halaman
        items.forEach(function (a) {
            a.addEventListener('click', function (e) {
                if (!mobile.matches || e.defaultPrevented || e.button !== 0 ||
                    e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.target === '_blank') return;
                if (a.classList.contains('active')) return;   // sudah di halaman ini

                e.preventDefault();
                setActive(a.dataset.key, true);

                if (reduce.matches) { window.location.href = a.href; return; }
                follow(500);
                setTimeout(function () { window.location.href = a.href; }, 330);
            });
        });
    })();
    </script>
    @yield('scripts')
</body>
</html>
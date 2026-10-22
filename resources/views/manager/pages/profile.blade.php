@php
    $namaBulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $bulanPendek = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    // Sumber data sama seperti halaman lama: $data_profile, dengan cadangan auth()->user()
    $au       = auth()->user();
    $dp       = $data_profile ?? $au;
    $nama     = $dp->nama ?? $au->nama ?? '';
    $username = $dp->username ?? $au->username ?? '';
    $role     = $dp->role ?? $au->role ?? 'manager';

    // Ubah nilai tanggal apa pun (Carbon / string) menjadi Carbon, atau null
    $toCarbon = function ($v) {
        if (! $v) return null;
        try {
            return $v instanceof \Carbon\CarbonInterface ? $v : \Carbon\Carbon::parse($v);
        } catch (\Throwable $e) {
            return null;
        }
    };

    // Tanggal Bahasa Indonesia
    $tglIndo = function ($date) use ($namaBulan) {
        if (! $date) {
            return '-';
        }
        return $date->format('d') . ' ' . $namaBulan[(int) $date->format('n')] . ' ' . $date->format('Y');
    };

    // Stempel waktu untuk tabel log (tanggal + jam, WIB)
    $stempel = function ($date) use ($bulanPendek) {
        if (! $date) {
            return ['-', ''];
        }
        return [
            $date->format('d') . ' ' . $bulanPendek[(int) $date->format('n')] . ' ' . $date->format('Y'),
            $date->format('H:i:s') . ' WIB',
        ];
    };

    $dibuat = $toCarbon($dp->created_at ?? $au->created_at ?? null);

    // Info password terakhir diubah (hanya tampil bila kolomnya ada & terisi)
    $pwUpdated = $toCarbon($dp->password_updated_at ?? $au->password_updated_at ?? null);
    if ($pwUpdated) {
        $selisihHari = (int) $pwUpdated->diffInDays(now());
        if ($selisihHari > 0) {
            $pwTerakhir = $selisihHari . ' hari yang lalu';
        } elseif ((int) $pwUpdated->diffInHours(now()) > 0) {
            $pwTerakhir = (int) $pwUpdated->diffInHours(now()) . ' jam yang lalu';
        } else {
            $pwTerakhir = 'baru saja diubah';
        }
    } else {
        $pwTerakhir = null;
    }

    // Deteksi browser & OS dari user agent (untuk sesi saat ini)
    $ua = request()->userAgent() ?? '';
    $browser = 'Browser tidak dikenal';
    foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $kunci => $nm) {
        if (stripos($ua, $kunci) !== false) { $browser = $nm; break; }
    }
    $os = 'OS tidak dikenal';
    foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $kunci => $nm) {
        if (stripos($ua, $kunci) !== false) { $os = $nm; break; }
    }
    $perangkatSaatIni = $browser . ' di ' . $os;

    // LOG: pakai $logs dari controller bila ada, jika tidak susun dari data akun.
    // Format tiap baris: ['icon','aktivitas','sub','perangkat','ip','waktu' (Carbon),'metode','status','tone']
    //   tone: ok | live | warn | muted
    if (! isset($logs)) {
        $logs = [];

        if ($pwUpdated) {
            $logs[] = [
                'icon' => 'bx-key', 'aktivitas' => 'Perubahan Password', 'sub' => 'Kredensial diperbarui',
                'perangkat' => 'Panel Manager', 'ip' => request()->ip(),
                'waktu' => $pwUpdated, 'metode' => 'Username + password',
                'status' => 'Berhasil', 'tone' => 'ok',
            ];
        }

        $loginTerakhir = $toCarbon($dp->last_login_at ?? $au->last_login_at ?? null);
        if ($loginTerakhir) {
            $logs[] = [
                'icon' => 'bx-log-in-circle', 'aktivitas' => 'Login Terakhir', 'sub' => 'Autentikasi berhasil',
                'perangkat' => $dp->last_login_device ?? $au->last_login_device ?? 'Perangkat tercatat',
                'ip' => $dp->last_login_ip ?? $au->last_login_ip ?? '-',
                'waktu' => $loginTerakhir, 'metode' => 'Username + password',
                'status' => 'Terverifikasi', 'tone' => 'ok',
            ];
        }

        $logs[] = [
            'icon' => 'bx-desktop', 'aktivitas' => 'Sesi Aktif Saat Ini', 'sub' => 'Sedang digunakan',
            'perangkat' => $perangkatSaatIni, 'ip' => request()->ip(),
            'waktu' => now(), 'metode' => 'Cookie sesi',
            'status' => 'Aktif', 'tone' => 'live',
        ];
    }
@endphp

<style>
    /* Variabel warna (sama dengan tema SDM) — didefinisikan di sini agar halaman mandiri */
    .pkl-page {
        --pkl-primary: #0891b2; --pkl-primary-dark: #0e7490; --pkl-primary-50: #ecfeff; --pkl-primary-100: #cffafe;
        --pkl-ink: #0f172a; --pkl-text: #334155; --pkl-muted: #64748b; --pkl-soft: #94a3b8;
        --pkl-line: #e2e8f0; --pkl-line-soft: #f1f5f9; --pkl-bg: #f8fafc; --pkl-hairline: #e2e8f0;
        --pkl-font-head: inherit;
        --pkl-font-mono: ui-monospace, 'SFMono-Regular', Menlo, Consolas, 'Liberation Mono', monospace;
    }

    .pkl-mono { font-family: var(--pkl-font-mono); letter-spacing: 0.02em; }

    /* ---------- Mission header ---------- */
    .pkl-mission { margin-bottom: 1.5rem; }
    .pkl-mission__eyebrow { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem; }
    .pkl-crumb { display: flex; align-items: center; gap: 0.25rem; font-size: 12px; font-weight: 600; letter-spacing: 0.03em; color: var(--pkl-muted); }
    .pkl-crumb a { color: var(--pkl-muted); text-decoration: none; transition: color 0.15s; }
    .pkl-crumb a:hover { color: var(--pkl-primary); }
    .pkl-crumb__sep { font-size: 15px; line-height: 1; color: var(--pkl-soft); }
    .pkl-crumb__current { color: var(--pkl-primary-dark); font-weight: 700; }

    .pkl-chip {
        display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.35rem 0.75rem; border-radius: 999px;
        background: var(--pkl-line-soft); border: 1px solid var(--pkl-line); color: var(--pkl-muted);
        font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; white-space: nowrap;
    }
    .pkl-chip--ok { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .pkl-chip--primary { background: var(--pkl-primary-50); border-color: rgba(8, 145, 178, 0.25); color: var(--pkl-primary-dark); }
    .pkl-chip__dot { width: 8px; height: 8px; border-radius: 50%; background: var(--pkl-primary); flex: none; }
    .pkl-chip__dot--live { background: #10b981; animation: pklProfilePulse 2s ease-in-out infinite; }
    @@keyframes pklProfilePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

    .pkl-mission__title { font-family: var(--pkl-font-head); font-size: 28px; font-weight: 700; letter-spacing: -0.015em; line-height: 1.2; color: #000; margin: 0; }
    .pkl-mission__desc { margin: 0.35rem 0 0; font-size: 14px; line-height: 1.45; color: #000; white-space: nowrap; }
    .pkl-mission__text { min-width: 0; }
    @@media (max-width: 991.98px) { .pkl-mission__desc { max-width: 100%; overflow: hidden; text-overflow: ellipsis; } }
    @@media (max-width: 575.98px) { .pkl-mission__title { font-size: 22px; } .pkl-mission__desc { font-size: 13px; } }

    /* ---------- Panel ---------- */
    .pkl-panel {
        position: relative; display: flex; flex-direction: column; background: #fff;
        border: 1px solid var(--pkl-hairline); border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04); overflow: hidden;
    }
    .pkl-panel--accent { border-top: 2px solid transparent; border-image: linear-gradient(90deg, var(--pkl-primary), #10b981 60%, transparent) 1 0 0 0; }
    .pkl-panel--accent::before, .pkl-panel--accent::after { content: ""; position: absolute; top: 0; width: 12px; height: 12px; background: rgba(8, 145, 178, 0.28); pointer-events: none; }
    .pkl-panel--accent::before { left: 0; }
    .pkl-panel--accent::after { right: 0; }
    .pkl-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 1.25rem 1.25rem 0.9rem; background: var(--pkl-line-soft); border-bottom: 1px solid var(--pkl-line-soft); }
    .pkl-panel__icon {
        width: 34px; height: 34px; flex: none; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.5rem;
        background: var(--pkl-primary-50); border: 1px solid rgba(8, 145, 178, 0.25); box-shadow: inset 0 0 0 3px #fff;
        color: var(--pkl-primary-dark); font-size: 20px;
    }
    .pkl-panel__title { font-family: var(--pkl-font-head); font-size: 16px; font-weight: 600; line-height: 1.2; color: var(--pkl-ink); margin: 0; }
    .pkl-panel__sub { display: block; margin-top: 2px; font-size: 10px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; color: var(--pkl-soft); }
    .pkl-panel__body { display: flex; flex-direction: column; gap: 1.25rem; padding: 1.25rem; }

    /* ---------- Avatar ---------- */
    .pkl-avatar { position: relative; display: inline-flex; align-items: center; justify-content: center; }
    .pkl-avatar__glow {
        position: absolute; inset: -6px; border-radius: 999px; pointer-events: none;
        background: conic-gradient(from 0deg, var(--pkl-primary), transparent 30%, #10b981 55%, transparent 80%, var(--pkl-primary));
        opacity: 1; animation: pklRingSpin 8s linear infinite;
    }
    @@keyframes pklRingSpin { to { transform: rotate(360deg); } }
    .pkl-avatar__frame { position: relative; z-index: 1; width: 128px; height: 128px; border-radius: 999px; background: #fff; padding: 4px; box-shadow: 0 0 0 1px var(--pkl-line), 0 14px 30px -14px rgba(8, 145, 178, 0.55); }
    .pkl-avatar__img { width: 100%; height: 100%; border-radius: 999px; object-fit: cover; background: var(--pkl-line-soft); display: block; }
    .pkl-avatar__status {
        position: absolute; right: 4px; bottom: 4px; display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px; border-radius: 999px;
        background: #fff; border: 1px solid var(--pkl-line); box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        font-size: 9px; font-weight: 700; letter-spacing: 0.08em; color: #047857;
    }
    .pkl-avatar__status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #10b981; animation: pklProfilePulse 2s ease-in-out infinite; }

    /* ---------- Daftar identitas ---------- */
    .pkl-kv { display: flex; flex-direction: column; gap: 0.25rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: var(--pkl-bg); border: 1px solid var(--pkl-line-soft); }
    .pkl-kv + .pkl-kv { margin-top: 0.25rem; }
    .pkl-kv__label { font-size: 11px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--pkl-soft); }
    .pkl-kv__value { font-size: 14px; font-weight: 600; color: var(--pkl-ink); word-break: break-word; }
    .pkl-kv__value--mono { font-family: var(--pkl-font-mono); font-size: 13px; color: var(--pkl-primary-dark); }
    .pkl-kv__hint { display: block; margin-top: 2px; font-family: var(--pkl-font-mono); font-size: 10px; color: var(--pkl-primary); }

    /* ---------- Field ---------- */
    .pkl-field { display: flex; flex-direction: column; gap: 0.35rem; }
    .pkl-field__label { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-size: 12px; font-weight: 600; color: var(--pkl-text); }
    .pkl-field__label label { margin: 0; }
    .pkl-field__label small { font-size: 10px; font-weight: 600; letter-spacing: 0.04em; color: var(--pkl-soft); }
    .pkl-field__control { position: relative; display: flex; align-items: center; }
    .pkl-field__icon { position: absolute; left: 0.9rem; display: inline-flex; align-items: center; font-size: 18px; color: var(--pkl-soft); pointer-events: none; }
    .pkl-field__control input {
        width: 100%; padding: 0.7rem 2.9rem 0.7rem 2.6rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #fff;
        color: var(--pkl-ink); font-size: 14px; transition: border-color 0.15s, box-shadow 0.15s;
    }
    .pkl-field__control input::placeholder { color: var(--pkl-soft); }
    .pkl-field__control input:focus { outline: none; border-color: var(--pkl-primary); box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15); }
    .pkl-field__control:focus-within .pkl-field__icon { color: var(--pkl-primary); }
    .pkl-field__toggle {
        position: absolute; right: 0.75rem; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px;
        border: 0; border-radius: 0.375rem; background: transparent; color: var(--pkl-soft); font-size: 18px; cursor: pointer; transition: color 0.15s, background 0.15s;
    }
    .pkl-field__toggle:hover { color: var(--pkl-primary); background: var(--pkl-primary-50); }
    .pkl-field__help { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--pkl-soft); margin: 0; }

    /* ---------- Status password tersimpan ---------- */
    .pkl-stored { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.7rem 1rem; border-radius: 0.5rem; background: var(--pkl-bg); border: 1px solid var(--pkl-line-soft); }
    .pkl-stored__dots { font-family: var(--pkl-font-mono); font-size: 15px; letter-spacing: 0.2em; color: var(--pkl-primary-dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pkl-stored__state { display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #047857; white-space: nowrap; }
    .pkl-stored__state i { font-size: 16px; }

    /* ---------- Strength meter ---------- */
    .pkl-strength { display: flex; flex-direction: column; gap: 0.4rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: var(--pkl-bg); border: 1px solid var(--pkl-line-soft); }
    .pkl-strength__head { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--pkl-soft); }
    .pkl-strength__label { font-family: var(--pkl-font-mono); color: var(--pkl-soft); }
    .pkl-strength__label[data-level="1"] { color: #dc2626; }
    .pkl-strength__label[data-level="2"] { color: #d97706; }
    .pkl-strength__label[data-level="3"] { color: #0284c7; }
    .pkl-strength__label[data-level="4"] { color: #047857; }
    .pkl-strength__track { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; height: 6px; }
    .pkl-strength__seg { border-radius: 999px; background: var(--pkl-line); transition: background 0.25s; }
    .pkl-strength__seg.is-on-1 { background: #dc2626; }
    .pkl-strength__seg.is-on-2 { background: #d97706; }
    .pkl-strength__seg.is-on-3 { background: #0284c7; }
    .pkl-strength__seg.is-on-4 { background: #059669; }

    /* ---------- Pill ---------- */
    .pkl-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; white-space: nowrap; }
    .pkl-pill--ok { background: rgba(5, 150, 105, 0.12); color: #047857; border: 1px solid rgba(5, 150, 105, 0.25); }
    .pkl-pill--warn { background: rgba(220, 38, 38, 0.1); color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.22); }
    .pkl-pill--muted { background: var(--pkl-line-soft); color: var(--pkl-muted); border: 1px solid var(--pkl-line); }
    .pkl-pill--primary { background: var(--pkl-primary-50); color: var(--pkl-primary-dark); border: 1px solid rgba(8, 145, 178, 0.25); }

    /* ---------- Note ---------- */
    .pkl-note { display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.85rem 1rem; border-radius: 0.75rem; background: var(--pkl-primary-50); border: 1px solid rgba(8, 145, 178, 0.2); font-size: 12px; line-height: 1.5; color: var(--pkl-text); }
    .pkl-note > i { font-size: 20px; color: var(--pkl-primary); flex: none; margin-top: 1px; }
    .pkl-note strong { display: block; color: var(--pkl-ink); }

    /* ---------- Tombol ---------- */
    .pkl-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem; padding: 0.7rem 1.25rem; border-radius: 0.5rem; border: 1px solid transparent; font-size: 14px; font-weight: 600; line-height: 1.2; cursor: pointer; text-decoration: none; transition: box-shadow 0.15s, background 0.15s, color 0.15s, border-color 0.15s; }
    .pkl-btn i { font-size: 18px; }
    .pkl-btn--primary { background: linear-gradient(90deg, var(--pkl-primary), var(--pkl-primary-dark)); color: #fff; box-shadow: 0 6px 18px -8px rgba(8, 145, 178, 0.6); }
    .pkl-btn--primary:hover, .pkl-btn--primary:focus { color: #fff; box-shadow: 0 10px 24px -8px rgba(8, 145, 178, 0.75); }
    .pkl-btn--ghost { background: var(--pkl-line-soft); border-color: var(--pkl-line); color: var(--pkl-text); }
    .pkl-btn--ghost:hover, .pkl-btn--ghost:focus { background: var(--pkl-line); color: var(--pkl-ink); border-color: #cbd5e1; }
    .pkl-icon-spin { color: var(--pkl-primary); animation: pklSpin 1.6s linear infinite; }
    @@keyframes pklSpin { to { transform: rotate(360deg); } }

    /* ---------- Log integritas & sesi ---------- */
    .pkl-log { margin-top: 1.5rem; }
    .pkl-log__sync { display: inline-flex; align-items: center; gap: 0.45rem; font-family: var(--pkl-font-mono); font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #047857; }
    .pkl-log__wrap { overflow-x: auto; }
    .pkl-log table { width: 100%; min-width: 760px; border-collapse: collapse; margin: 0; }
    .pkl-log th { padding: 0.8rem 1.25rem; text-align: left; white-space: nowrap; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--pkl-soft); background: var(--pkl-bg); border-bottom: 1px solid var(--pkl-line); }
    .pkl-log td { padding: 0.95rem 1.25rem; vertical-align: middle; font-size: 13px; color: var(--pkl-text); border-bottom: 1px solid var(--pkl-line-soft); }
    .pkl-log th:last-child, .pkl-log td:last-child { text-align: right; }
    .pkl-log tbody tr { transition: background 0.15s; }
    .pkl-log tbody tr:hover { background: var(--pkl-primary-50); }
    .pkl-log tbody tr:last-child td { border-bottom: 0; }
    .pkl-log__act { display: flex; align-items: center; gap: 0.75rem; text-align: left; }
    .pkl-log__act > i { flex: none; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: var(--pkl-primary-dark); background: var(--pkl-primary-50); border: 1px solid rgba(8, 145, 178, 0.22); border-radius: 0.5rem; }
    .pkl-log__act b { display: block; font-weight: 600; color: var(--pkl-ink); }
    .pkl-log__act small, .pkl-log__sub { display: block; margin-top: 1px; font-size: 11px; color: var(--pkl-soft); }
    .pkl-log__mono { display: block; font-family: var(--pkl-font-mono); font-size: 12px; color: var(--pkl-ink); }
    .pkl-log__ip { display: block; margin-top: 1px; font-family: var(--pkl-font-mono); font-size: 11px; color: var(--pkl-primary); }
    .pkl-log__method { font-size: 12px; color: var(--pkl-text); }
    .pkl-log__empty { padding: 1.75rem 1.25rem; text-align: center; font-size: 13px; color: var(--pkl-soft); }
    .pkl-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; border: 1px solid; font-family: var(--pkl-font-mono); font-size: 10px; font-weight: 700; letter-spacing: 0.05em; white-space: nowrap; }
    .pkl-badge--ok { color: #047857; background: rgba(5, 150, 105, 0.1); border-color: rgba(5, 150, 105, 0.28); }
    .pkl-badge--live { color: var(--pkl-primary-dark); background: var(--pkl-primary-50); border-color: rgba(8, 145, 178, 0.35); }
    .pkl-badge--warn { color: #b91c1c; background: rgba(220, 38, 38, 0.1); border-color: rgba(220, 38, 38, 0.25); }
    .pkl-badge--muted { color: var(--pkl-muted); background: var(--pkl-line-soft); border-color: var(--pkl-line); }
    @@media (max-width: 575.98px) { .pkl-log th, .pkl-log td { padding-left: 1rem; padding-right: 1rem; } }

    @@media (prefers-reduced-motion: reduce) {
        .pkl-chip__dot--live, .pkl-avatar__status::before, .pkl-icon-spin, .pkl-avatar__glow { animation: none; }
    }
</style>

<div class="pkl-page">

{{-- ================= MISSION HEADER ================= --}}
<div class="pkl-mission">
    <div class="pkl-mission__eyebrow">
        <nav class="pkl-crumb" aria-label="breadcrumb">
            <a href="{{ url('manager') }}">Beranda</a>
            <span class="pkl-crumb__sep"><i class="bx bx-chevron-right"></i></span>
            <a href="{{ url('manager/profile') }}">Profil</a>
            <span class="pkl-crumb__sep"><i class="bx bx-chevron-right"></i></span>
            <span class="pkl-crumb__current">Keamanan Akun</span>
        </nav>
        <span class="pkl-chip pkl-chip--ok">
            <span class="pkl-chip__dot pkl-chip__dot--live"></span>
            Akun {{ strtoupper($role) }} Aktif
        </span>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div class="pkl-mission__text">
            <h1 class="pkl-mission__title">Profil Administrator {{ strtoupper($role) }}</h1>
            <p class="pkl-mission__desc">
                Kelola identitas akun operasional dan kredensial akses sistem PKL PLN Nusantara Power.
            </p>
        </div>
        <span class="pkl-chip pkl-chip--primary align-self-start align-self-md-auto">
            <i class="bx bx-shield-quarter"></i>
            Akses Terverifikasi
        </span>
    </div>
</div>

<div class="row g-4 align-items-start">

    {{-- ================= KARTU KIRI: PROFIL PENGGUNA ================= --}}
    <div class="col-12 col-lg-5">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class="bx bx-user-pin"></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Profil Pengguna</h2>
                        <span class="pkl-panel__sub">Kredensial {{ strtoupper($role) }} Terdaftar</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--muted pkl-mono">ID: USR-{{ $au->id ?? '-' }}</span>
            </div>

            <div class="pkl-panel__body">

                {{-- Avatar --}}
                <div class="d-flex flex-column align-items-center">
                    <div class="pkl-avatar">
                        <span class="pkl-avatar__glow"></span>
                        <div class="pkl-avatar__frame">
                            <img src="{{ asset('assets/images/team.png') }}" alt="Foto profil {{ $nama }}" class="pkl-avatar__img">
                            <span class="pkl-avatar__status">Aktif</span>
                        </div>
                    </div>
                </div>

                {{-- Identitas --}}
                <div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Nama Lengkap</span>
                        <span class="pkl-kv__value">{{ ucwords($nama ?: $username) }}</span>
                        <span class="pkl-kv__hint">Display: {{ strtoupper($role) }}</span>
                    </div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Username Akun</span>
                        <span class="pkl-kv__value pkl-kv__value--mono">{{ '@' . $username }}</span>
                    </div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Role / Divisi</span>
                        <span class="pkl-kv__value">{{ ucfirst($role) }}</span>
                    </div>
                    @if ($dibuat)
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Akun Terdaftar</span>
                        <span class="pkl-kv__value">{{ $tglIndo($dibuat) }}</span>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ================= KARTU KANAN: PEMBARUAN KATA SANDI ================= --}}
    <div class="col-12 col-lg-7">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class="bx bx-lock-alt"></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Pembaruan Kata Sandi</h2>
                        <span class="pkl-panel__sub">Password Terenkripsi</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--primary">Min. 6 Karakter</span>
            </div>

            <form class="pkl-panel__body" action="{{ url('manager/profile') }}" method="post" id="passwordForm">
                @csrf

                {{-- Status password saat ini --}}
                <div class="pkl-field">
                    <div class="pkl-field__label">
                        <span>Status Password Saat Ini</span>
                    </div>
                    <div class="pkl-stored">
                        <span class="pkl-stored__dots">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span>
                        <span class="pkl-stored__state"><i class="bx bx-check-circle"></i> Tersimpan Aman</span>
                    </div>
                    @if ($pwTerakhir)
                    <p class="pkl-field__help">
                        <i class="bx bx-time"></i>
                        Terakhir diperbarui {{ $pwTerakhir }}
                    </p>
                    @endif
                </div>

                {{-- Password baru --}}
                <div class="pkl-field">
                    <div class="pkl-field__label">
                        <label for="password_baru">Password Baru</label>
                        <small style="color:#dc2626">* Wajib diisi</small>
                    </div>
                    <div class="pkl-field__control">
                        <span class="pkl-field__icon"><i class="bx bx-key"></i></span>
                        <input type="password" name="password_baru" id="password_baru" placeholder="Masukkan password baru" minlength="6" maxlength="100" autocomplete="new-password" required>
                        <button type="button" class="pkl-field__toggle" data-toggle-password="password_baru" title="Tampilkan password" aria-label="Tampilkan password baru">
                            <i class="bx bx-hide"></i>
                        </button>
                    </div>

                    <div class="pkl-strength">
                        <div class="pkl-strength__head">
                            <span>Kekuatan Password Baru</span>
                            <span class="pkl-strength__label" id="strengthLabel" data-level="0">Belum diisi</span>
                        </div>
                        <div class="pkl-strength__track">
                            <span class="pkl-strength__seg" data-seg="1"></span>
                            <span class="pkl-strength__seg" data-seg="2"></span>
                            <span class="pkl-strength__seg" data-seg="3"></span>
                            <span class="pkl-strength__seg" data-seg="4"></span>
                        </div>
                        <p class="pkl-field__help">
                            <i class="bx bx-info-circle"></i>
                            Minimal 6 karakter (disarankan kombinasi huruf kapital, angka, dan simbol).
                        </p>
                    </div>
                </div>

                {{-- Konfirmasi password --}}
                <div class="pkl-field">
                    <div class="pkl-field__label">
                        <label for="konfirmasi_password">Konfirmasi Password</label>
                        <span class="pkl-pill pkl-pill--muted" id="matchPill">Menunggu input</span>
                    </div>
                    <div class="pkl-field__control">
                        <span class="pkl-field__icon"><i class="bx bx-lock-open-alt"></i></span>
                        <input type="password" name="konfirmasi_password" id="konfirmasi_password" placeholder="Ketik ulang password baru" maxlength="100" autocomplete="new-password" required>
                        <button type="button" class="pkl-field__toggle" data-toggle-password="konfirmasi_password" title="Tampilkan password" aria-label="Tampilkan konfirmasi password">
                            <i class="bx bx-hide"></i>
                        </button>
                    </div>
                </div>

                {{-- Kebijakan keamanan --}}
                <div class="pkl-note">
                    <i class="bx bx-info-circle"></i>
                    <div>
                        <strong>Kebijakan Keamanan PLN Nusantara Power:</strong>
                        Kata sandi baru minimal 6 karakter. Gunakan kata sandi yang unik dan jangan dibagikan kepada siapa pun.
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="d-flex flex-wrap gap-3 pt-1">
                    <button type="submit" class="pkl-btn pkl-btn--primary flex-grow-1" style="min-width:220px">
                        <i class="bx bx-lock"></i>
                        Ubah Password
                    </button>
                    <button type="reset" class="pkl-btn pkl-btn--ghost">
                        <i class="bx bx-sync pkl-icon-spin"></i>
                        Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= LOG INTEGRITAS & SESI TERAKHIR ================= --}}
<div class="pkl-panel pkl-panel--accent pkl-log">
    <div class="pkl-panel__head">
        <div class="d-flex align-items-center gap-2">
            <span class="pkl-panel__icon"><i class="bx bx-shield-alt-2"></i></span>
            <div>
                <h2 class="pkl-panel__title">Log Integritas &amp; Sesi Terakhir</h2>
                <span class="pkl-panel__sub">Audit trail otentikasi akun</span>
            </div>
        </div>
        <span class="pkl-log__sync">
            <span class="pkl-chip__dot pkl-chip__dot--live"></span>
            Tercatat otomatis
        </span>
    </div>

    @if (count($logs))
    <div class="pkl-log__wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Aktivitas Keamanan</th>
                    <th scope="col">Perangkat &amp; Alamat IP</th>
                    <th scope="col">Waktu Stempel</th>
                    <th scope="col">Metode Verifikasi</th>
                    <th scope="col">Status Hasil</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                @php [$tglLog, $jamLog] = $stempel($log['waktu'] ?? null); @endphp
                <tr>
                    <td>
                        <div class="pkl-log__act">
                            <i class="bx {{ $log['icon'] ?? 'bx-shield' }}"></i>
                            <div>
                                <b>{{ $log['aktivitas'] }}</b>
                                @if (! empty($log['sub']))<small>{{ $log['sub'] }}</small>@endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="pkl-log__mono">{{ $log['perangkat'] }}</span>
                        <span class="pkl-log__ip">IP {{ $log['ip'] }}</span>
                    </td>
                    <td>
                        <span class="pkl-log__mono">{{ $tglLog }}</span>
                        <span class="pkl-log__sub pkl-mono">{{ $jamLog }}</span>
                    </td>
                    <td><span class="pkl-log__method">{{ $log['metode'] }}</span></td>
                    <td><span class="pkl-badge pkl-badge--{{ $log['tone'] ?? 'muted' }}">{{ strtoupper($log['status']) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="pkl-log__empty">Belum ada aktivitas keamanan yang tercatat untuk akun ini.</div>
    @endif
</div>

</div>

@section('scripts')
<script>
$(document).ready(function () {

    // --- Toggle tampilan password ---
    $("[data-toggle-password]").on('click', function () {
        var input = $('#' + $(this).data('toggle-password'));
        var icon = $(this).find('i');
        var isText = input.attr('type') === 'text';

        input.attr('type', isText ? 'password' : 'text');
        icon.removeClass(isText ? 'bx-show' : 'bx-hide').addClass(isText ? 'bx-hide' : 'bx-show');
    });

    // --- Meter kekuatan password ---
    var passBaru = $('#password_baru');
    var konfirmasi = $('#konfirmasi_password');
    var labelStrength = $('#strengthLabel');
    var matchPill = $('#matchPill');

    function strengthLevel(value) {
        if (!value) return 0;

        var skor = 0;
        if (value.length >= 6) skor++;
        if (value.length >= 12) skor++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) skor++;
        if (/[0-9]/.test(value)) skor++;
        if (/[^A-Za-z0-9]/.test(value)) skor++;

        if (skor <= 1) return 1;   // Lemah
        if (skor === 2) return 2;  // Cukup
        if (skor === 3) return 3;  // Baik
        return 4;                  // Kuat
    }

    var labelText = { 0: 'Belum diisi', 1: 'Lemah', 2: 'Cukup', 3: 'Baik', 4: 'Kuat' };

    function renderStrength() {
        var level = strengthLevel(passBaru.val());

        labelStrength.text(labelText[level]).attr('data-level', level);

        $('.pkl-strength__seg').each(function (i) {
            var seg = i + 1;
            $(this).removeClass('is-on-1 is-on-2 is-on-3 is-on-4');
            if (seg <= level) {
                $(this).addClass('is-on-' + level);
            }
        });
    }

    function renderMatch() {
        var a = passBaru.val();
        var b = konfirmasi.val();

        if (!a && !b) {
            matchPill.attr('class', 'pkl-pill pkl-pill--muted').text('Menunggu input');
            return;
        }

        if (a && b && a === b) {
            matchPill.attr('class', 'pkl-pill pkl-pill--ok').html('<i class="bx bx-check-double"></i> Cocok');
        } else {
            matchPill.attr('class', 'pkl-pill pkl-pill--warn').html('<i class="bx bx-x"></i> Tidak cocok');
        }
    }

    passBaru.on('input', function () {
        renderStrength();
        renderMatch();
    });

    konfirmasi.on('input', renderMatch);

    $('#passwordForm').on('reset', function () {
        setTimeout(function () {
            renderStrength();
            renderMatch();
        }, 0);
    });

    renderStrength();
    renderMatch();
});
</script>
@endsection
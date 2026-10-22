@extends('layouts.peserta')

@section('content')
@php
    $hari_arr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan_arr = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $today_label = $hari_arr[date('w')] . ', ' . date('d') . ' ' . $bulan_arr[(int)date('m')] . ' ' . date('Y');

    // Status singkat hari ini (untuk chip "Status Saat Ini")
    $st = ['label' => 'Belum Absen', 'color' => '#dc3545'];
    if ($is_libur) {
        $st = ['label' => 'Libur', 'color' => '#0d6efd'];
    } elseif ($absensi_today && $absensi_today->status === 'Izin') {
        $st = ['label' => 'Izin', 'color' => '#b58105'];
    } elseif ($absensi_today && $absensi_today->status === 'Sakit') {
        $st = ['label' => 'Sakit', 'color' => '#dc3545'];
    } elseif ($absensi_today && $absensi_today->jam_masuk && ! $absensi_today->jam_keluar) {
        $st = ['label' => 'Masuk ' . substr($absensi_today->jam_masuk, 0, 5), 'color' => '#198754'];
    } elseif ($absensi_today && $absensi_today->jam_masuk && $absensi_today->jam_keluar) {
        $st = ['label' => 'Selesai', 'color' => '#198754'];
    }

    // Statistik bulan ini
    $s_hadir = (int) ($stat['Hadir'] ?? 0);
    $s_izin  = (int) ($stat['Izin'] ?? 0);
    $s_sakit = (int) ($stat['Sakit'] ?? 0);
    $s_alpha = (int) ($stat['Alpha'] ?? 0);
    $s_total = $s_hadir + $s_izin + $s_sakit + $s_alpha;
    $s_pct   = $s_total > 0 ? round($s_hadir / $s_total * 100) : 0;

    // Data sapaan (sama dengan sumber data topbar lama)
    $nama_user = ucwords(auth()->user()->nama ?? 'Peserta');
    $peserta_info = \App\Models\Peserta::with('bidang')
        ->where('username', auth()->user()->username)
        ->first();
    $sub_parts = [];
    if ($peserta_info && $peserta_info->asal_sekolah) $sub_parts[] = $peserta_info->asal_sekolah;
    if ($peserta_info && $peserta_info->bidang) $sub_parts[] = $peserta_info->bidang->bidang;
    $sub_text = implode(' • ', $sub_parts);
    $unit_text = $peserta_info->unit ?? null;
@endphp

<style>
    :root {
        --pk-blue: #0b4fb3;
        --pk-blue-dark: #083a85;
        --pk-ink: #16233b;
        --pk-muted: #6b7a90;
        --pk-line: #e6ecf5;
        --pk-bg-soft: #f4f7fc;
    }

    .pk-card {
        background: #fff;
        border-radius: 20px;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
        border: 1px solid rgba(230, 236, 245, .9);
    }
    .pk-eyebrow {
        font-size: 10px; font-weight: 700; letter-spacing: .8px;
        text-transform: uppercase; color: var(--pk-muted);
        display: flex; align-items: center; gap: 6px;
    }
    .pk-eyebrow i { font-size: 14px; color: var(--pk-blue); }

    /* ── Sapaan ── */
    .pk-hello { display: flex; align-items: center; gap: 12px; }
    .pk-hello-ava {
        width: 46px; height: 46px; border-radius: 50%;
        background: linear-gradient(135deg, #1e6be0, #0b4fb3);
        color: #fff; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 18px; flex-shrink: 0;
        border: 2px solid #fff; box-shadow: 0 2px 8px rgba(11, 79, 179, .3);
    }
    .pk-hello-text { flex: 1; min-width: 0; }
    .pk-hello-name { font-size: 16px; font-weight: 800; color: var(--pk-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pk-hello-sub { font-size: 11px; color: var(--pk-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
    .pk-hello-side { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
    .pk-unit-badge {
        font-size: 10px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase;
        color: var(--pk-blue); background: #e8f0ff; padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .pk-chip-live {
        font-size: 10px; font-weight: 700; color: #0f5132;
        background: #d1e7dd; padding: 4px 10px; border-radius: 999px;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .pk-chip-live::before {
        content: ''; width: 6px; height: 6px; border-radius: 50%; background: #198754;
        animation: pkBlink 1.6s infinite;
    }

    /* ── Jam ── */
    .pk-clock { text-align: center; }
    .pk-clock-time {
        font-size: 46px; font-weight: 800; letter-spacing: 1px; line-height: 1.1;
        color: var(--pk-blue); margin: 10px 0 4px;
        font-variant-numeric: tabular-nums;
    }
    .pk-clock-date { font-size: 12px; color: var(--pk-muted); }
    .pk-clock-date .pk-tz {
        background: #e8f0ff; color: var(--pk-blue); font-weight: 700;
        padding: 2px 8px; border-radius: 6px; margin-left: 6px; font-size: 10px;
    }
    .pk-clock-meta {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--pk-line);
        font-size: 11px; text-align: left;
    }
    .pk-meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: .6px; color: var(--pk-muted); font-weight: 700; }
    .pk-meta-value { font-size: 12px; font-weight: 700; color: var(--pk-ink); display: flex; align-items: center; gap: 6px; margin-top: 2px; }
    .pk-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

    /* ── Peta geofence ── */
    .pk-geo-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 10px; }
    .pk-pill {
        font-size: 10px; font-weight: 700; padding: 5px 10px; border-radius: 999px;
        background: var(--pk-bg-soft); color: var(--pk-muted); white-space: nowrap;
    }
    .pk-pill.in  { background: #d1e7dd; color: #0f5132; }
    .pk-pill.out { background: #f8d7da; color: #842029; }
    #map { height: 190px; border-radius: 14px; overflow: hidden; z-index: 0; }
    .pk-geo-foot {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 10px; font-size: 10.5px; color: var(--pk-muted);
    }
    .pk-geo-foot i { color: var(--pk-blue); font-size: 13px; vertical-align: middle; }
    .pk-geo-foot b { color: var(--pk-ink); font-variant-numeric: tabular-nums; }

    /* ── Tombol utama (bulat) ── */
    .pk-action { text-align: center; padding: 8px 0 4px; }
    .pk-btn-main {
        width: 138px; height: 138px; border-radius: 50%; border: 0; cursor: pointer;
        color: #fff; display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 2px; font-family: inherit; position: relative;
        background: radial-gradient(circle at 30% 25%, #2f7cf0 0%, var(--pk-blue) 55%, var(--pk-blue-dark) 100%);
        box-shadow: 0 0 0 10px rgba(11, 79, 179, .09), 0 0 0 20px rgba(11, 79, 179, .05), 0 14px 28px rgba(11, 79, 179, .35);
        transition: transform .15s ease;
    }
    .pk-btn-main:active { transform: scale(.96); }
    .pk-btn-main i { font-size: 40px; line-height: 1; }
    .pk-btn-main span { font-size: 15px; font-weight: 800; }
    .pk-btn-main.out {
        background: radial-gradient(circle at 30% 25%, #ff9a5c 0%, #e8590c 55%, #b84300 100%);
        box-shadow: 0 0 0 10px rgba(232, 89, 12, .10), 0 0 0 20px rgba(232, 89, 12, .05), 0 14px 28px rgba(232, 89, 12, .35);
    }
    .pk-btn-main.off {
        background: radial-gradient(circle at 30% 25%, #c9d1dc 0%, #9aa5b4 100%);
        box-shadow: 0 0 0 10px rgba(154, 165, 180, .15), 0 8px 18px rgba(100, 110, 125, .25);
        cursor: default;
    }
    .pk-btn-main:disabled { opacity: .85; }
    .pk-hint { margin-top: 22px; font-size: 11.5px; color: var(--pk-muted); }
    .pk-hint i { color: #198754; vertical-align: middle; }
    .pk-info-box {
        margin-top: 12px; display: inline-block; padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 600;
    }
    .pk-badges { margin-top: 20px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }

    /* ── Izin / Sakit ── */
    .pk-izin-row { display: flex; gap: 10px; margin-bottom: 12px; }
    .pk-izin-btn {
        flex: 1; border: 0; border-radius: 16px; padding: 14px 10px;
        font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .pk-izin-btn i { font-size: 18px; }
    .pk-izin-btn.izin  { background: #fde6bd; color: #8a4b00; }
    .pk-izin-btn.sakit { background: #fde0e0; color: #a01c1c; }
    .pk-izin-btn:active { transform: scale(.97); }

    /* ── Statistik ── */
    .pk-stat-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .pk-stat-title { font-size: 13px; font-weight: 800; color: var(--pk-ink); }
    .pk-stat-pct { font-size: 11px; font-weight: 700; color: var(--pk-blue); background: #e8f0ff; padding: 4px 10px; border-radius: 999px; }
    .pk-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .pk-stat {
        background: var(--pk-bg-soft); border-radius: 14px; padding: 12px 4px; text-align: center;
        border: 1px solid var(--pk-line);
    }
    .pk-stat-num { font-size: 24px; font-weight: 800; line-height: 1.1; }
    .pk-stat-lbl { font-size: 10px; color: var(--pk-muted); font-weight: 600; margin-top: 4px; }

    /* ── Section title ── */
    .pk-section-title {
        font-size: 13px; font-weight: 800; color: var(--pk-ink);
        display: flex; align-items: center; gap: 6px; margin: 16px 2px 10px;
    }
    .pk-section-title i { color: var(--pk-blue); font-size: 18px; }

    /* ── Grid halaman: 1 kolom di HP, 2 kolom di layar lebar ── */
    .pk-grid { display: block; }
    .pk-col { min-width: 0; }
    @@media (min-width: 768px) {
        .pk-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        #riwayatList { max-height: 60vh !important; }
    }
    @@media (min-width: 1200px) {
        .pk-grid { grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 20px; }
        #map { height: 300px; }
        .pk-clock-time { font-size: 60px; }
    }

    .pk-geo-head { flex-wrap: wrap; }
    .pk-pill { max-width: 100%; }

    /* ── Responsif: layar kecil ── */
    @@media (max-width: 360px) {
        .pk-card { padding: 14px 12px; border-radius: 18px; }
        .pk-clock-time { font-size: 38px; }
        .pk-btn-main { width: 120px; height: 120px; box-shadow: 0 0 0 8px rgba(11,79,179,.09), 0 0 0 16px rgba(11,79,179,.05), 0 12px 24px rgba(11,79,179,.35); }
        .pk-btn-main i { font-size: 34px; }
        .pk-btn-main span { font-size: 13px; }
        .pk-hello-name { font-size: 15px; }
        .pk-stat-grid { grid-template-columns: repeat(2, 1fr); }
        .pk-stat-num { font-size: 22px; }
        .pk-izin-btn { font-size: 13px; padding: 12px 6px; gap: 6px; }
        #map { height: 170px; }
        .pk-geo-foot { flex-direction: column; align-items: flex-start; gap: 4px; }
        .pk-clock-meta { gap: 8px; }
    }

    /* ── Responsif: tablet & desktop ── */
    @@media (min-width: 600px) {
        .pk-clock-time { font-size: 56px; }
        .pk-btn-main { width: 156px; height: 156px; }
        .pk-btn-main i { font-size: 46px; }
        .pk-btn-main span { font-size: 17px; }
        #map { height: 260px; }
        .pk-stat-num { font-size: 28px; }
        .pk-hello-name { font-size: 18px; }
    }

    @@keyframes pkBlink { 0%,100% { opacity: 1; } 50% { opacity: .25; } }
    @@keyframes mapPulse {
        0% { box-shadow: 0 0 0 0 rgba(13,110,253,.55); }
        70% { box-shadow: 0 0 0 12px rgba(13,110,253,0); }
        100% { box-shadow: 0 0 0 0 rgba(13,110,253,0); }
    }
</style>

<div class="pk-grid">
<div class="pk-col">

<!-- ═══ Sapaan ═══ -->
<div class="pk-card">
    <div class="pk-hello">
        <div class="pk-hello-ava">{{ mb_strtoupper(mb_substr($nama_user, 0, 1)) }}</div>
        <div class="pk-hello-text">
            <div class="pk-hello-name">Selamat Datang</div>
            @if ($sub_text)
                <div class="pk-hello-sub">{{ $sub_text }}</div>
            @endif
        </div>
        <div class="pk-hello-side">
            @if ($unit_text)
                <span class="pk-unit-badge">{{ $unit_text }}</span>
            @endif
            <span class="pk-chip-live">LIVE</span>
        </div>
    </div>
</div>

<!-- ═══ Jam & Status ═══ -->
<div class="pk-card pk-clock">
    <div class="pk-eyebrow" style="justify-content:center;"><i class='bx bx-time-five'></i> Waktu Sinkronisasi (WIB)</div>
    <div class="pk-clock-time" id="liveClock">{{ date('H:i:s') }}</div>
    <div class="pk-clock-date">{{ $today_label }}<span class="pk-tz">UTC+7</span></div>

    <div class="pk-clock-meta">
        <div>
            <div class="pk-meta-label">Radius Absen</div>
            <div class="pk-meta-value"><i class='bx bx-radar' style="color:var(--pk-blue);"></i> {{ number_format($radius_limit, 0, ',', '.') }} m</div>
        </div>
        <div style="text-align:right;">
            <div class="pk-meta-label">Status Saat Ini</div>
            <div class="pk-meta-value" style="justify-content:flex-end; color:{{ $st['color'] }};">
                <span class="pk-dot" style="background:{{ $st['color'] }};"></span> {{ $st['label'] }}
            </div>
        </div>
    </div>
</div>

<!-- ═══ Geofence Lokasi ═══ -->
@if (! $is_libur && (! $absensi_today || ($absensi_today->status === 'Hadir' && ! $absensi_today->jam_keluar)))
<div class="pk-card" id="map-container">
    <div class="pk-geo-head">
        <div class="pk-eyebrow"><i class='bx bx-target-lock'></i> Geofence Lokasi Presensi</div>
        <div class="pk-pill" id="distanceStatus">Mencari...</div>
    </div>
    <div id="map"></div>
    <div class="pk-geo-foot">
        <div><i class='bx bx-current-location'></i> <b id="userCoords">Mencari GPS...</b></div>
        <div><i class='bx bx-check-shield'></i> Batas Maks: <b>{{ number_format($radius_limit, 0, ',', '.') }}m</b></div>
    </div>
</div>
@endif

<!-- ═══ Tombol Absen ═══ -->
<div class="pk-card">
    <div class="pk-action">
        @if ($is_libur)
            <!-- Hari Libur -->
            <div class="pk-btn-main off">
                <i class='bx bx-calendar-x'></i>
                <span>Libur</span>
            </div>
            <div><div class="pk-info-box" style="background:#cfe2ff; color:#084298; margin-top:22px;">📅 {{ $is_libur }}</div></div>

        @elseif (! $absensi_today || (! $absensi_today->jam_masuk && $absensi_today->status === 'Hadir'))
            <!-- Belum Absen Masuk -->
            <button class="pk-btn-main" id="btnClockIn" onclick="doClockIn()">
                <i class='bx bx-log-in-circle'></i>
                <span>Absen Masuk</span>
            </button>
            <div class="pk-hint"><i class='bx bx-check-shield'></i> Tekan untuk absen masuk (GPS aktif)</div>

        @elseif ($absensi_today && $absensi_today->jam_masuk && ! $absensi_today->jam_keluar && $absensi_today->status === 'Hadir')
            <!-- Sudah Absen Masuk, belum Pulang -->
            <button class="pk-btn-main out" id="btnClockOut" onclick="doClockOut()">
                <i class='bx bx-log-out-circle'></i>
                <span>Absen Pulang</span>
            </button>
            <div class="pk-badges">
                <span class="m-badge m-badge-hadir"><i class='bx bx-check'></i> Masuk: {{ substr($absensi_today->jam_masuk,0,5) }}</span>
            </div>

        @elseif ($absensi_today && $absensi_today->status === 'Izin')
            <div class="pk-btn-main off">
                <i class='bx bx-envelope'></i>
                <span>Izin</span>
            </div>
            <div><div class="pk-info-box" style="background:#fff3cd; color:#856404; margin-top:22px;">📝 Anda izin hari ini</div></div>

        @elseif ($absensi_today && $absensi_today->status === 'Sakit')
            <div class="pk-btn-main off">
                <i class='bx bx-plus-medical'></i>
                <span>Sakit</span>
            </div>
            <div><div class="pk-info-box" style="background:#f8d7da; color:#842029; margin-top:22px;">🏥 Anda sakit hari ini</div></div>

        @else
            <!-- Sudah Selesai -->
            <div class="pk-btn-main off">
                <i class='bx bx-check-double'></i>
                <span>Selesai</span>
            </div>
            <div class="pk-badges">
                <span class="m-badge m-badge-hadir"><i class='bx bx-log-in'></i> {{ substr($absensi_today->jam_masuk,0,5) }}</span>
                <span class="m-badge m-badge-ditolak" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-log-out'></i> {{ substr($absensi_today->jam_keluar,0,5) }}</span>
            </div>
        @endif
    </div>
</div>

<!-- ═══ Tombol Izin / Sakit ═══ -->
@if (! $absensi_today || (! $absensi_today->jam_masuk && ! in_array($absensi_today->status ?? '', ['Izin','Sakit'])))
<div class="pk-izin-row">
    <button class="pk-izin-btn izin" onclick="openIzinModal('Izin')">
        <i class='bx bx-envelope'></i> Ajukan Izin
    </button>
    <button class="pk-izin-btn sakit" onclick="openIzinModal('Sakit')">
        <i class='bx bx-plus-medical'></i> Lapor Sakit
    </button>
</div>
@endif

</div><!-- /kolom kiri -->
<div class="pk-col">

<!-- ═══ Statistik Bulan Ini ═══ -->
<div class="pk-card">
    <div class="pk-stat-head">
        <div class="pk-stat-title">Statistik Kehadiran {{ $bulan_arr[(int)date('m')] }}</div>
        <div class="pk-stat-pct">{{ $s_pct }}% Hadir</div>
    </div>
    <div class="pk-stat-grid">
        <div class="pk-stat">
            <div class="pk-stat-num" style="color:#198754;">{{ $s_hadir }}</div>
            <div class="pk-stat-lbl">Hadir</div>
        </div>
        <div class="pk-stat">
            <div class="pk-stat-num" style="color:#e0a800;">{{ $s_izin }}</div>
            <div class="pk-stat-lbl">Izin</div>
        </div>
        <div class="pk-stat">
            <div class="pk-stat-num" style="color:#dc3545;">{{ $s_sakit }}</div>
            <div class="pk-stat-lbl">Sakit</div>
        </div>
        <div class="pk-stat">
            <div class="pk-stat-num" style="color:#6c757d;">{{ $s_alpha }}</div>
            <div class="pk-stat-lbl">Alpha</div>
        </div>
    </div>
</div>

<!-- ═══ Kalender Mini ═══ -->
<div class="mini-calendar" style="margin-bottom:12px; border-radius:20px;">
    <div class="mini-calendar-header">
        <div class="mini-calendar-title" id="calTitle">{{ $bulan_arr[(int)date('m')] . ' ' . date('Y') }}</div>
        <div class="mini-calendar-nav">
            <button onclick="changeMonth(-1)"><i class='bx bx-chevron-left'></i></button>
            <button onclick="changeMonth(1)"><i class='bx bx-chevron-right'></i></button>
        </div>
    </div>
    <div class="mini-calendar-grid" id="calendarGrid">
        <!-- Day labels -->
        <div class="mini-calendar-day-label">Min</div>
        <div class="mini-calendar-day-label">Sen</div>
        <div class="mini-calendar-day-label">Sel</div>
        <div class="mini-calendar-day-label">Rab</div>
        <div class="mini-calendar-day-label">Kam</div>
        <div class="mini-calendar-day-label">Jum</div>
        <div class="mini-calendar-day-label">Sab</div>
    </div>
    <div class="calendar-legend">
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#d1e7dd;"></div> Hadir</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#fff3cd;"></div> Izin</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#f8d7da;"></div> Sakit</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#e2e3e5;"></div> Alpha</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#cfe2ff;"></div> Libur</div>
    </div>
</div>

<!-- ═══ Riwayat Absensi ═══ -->
@php
    $riwayat_bulan = collect($riwayat)->map(fn($r) => date('Y-m', strtotime($r->tanggal)))->unique()->sortDesc()->values();
    $riwayat_total = collect($riwayat)->count();
    $prev_bulan = null;
@endphp
<div class="pk-section-title"><i class='bx bx-history'></i> Riwayat Absensi</div>

<div style="display:flex; gap:8px; margin-bottom:8px;">
    <select id="filterBulan" class="m-form-input" style="flex:1; padding:8px 12px; font-size:13px; border-radius:12px;" onchange="filterRiwayat()">
        <option value="">Semua Bulan</option>
        @foreach ($riwayat_bulan as $ym)
            <option value="{{ $ym }}">{{ $bulan_arr[(int) substr($ym, 5, 2)] . ' ' . substr($ym, 0, 4) }}</option>
        @endforeach
    </select>
    <select id="filterStatus" class="m-form-input" style="flex:1; padding:8px 12px; font-size:13px; border-radius:12px;" onchange="filterRiwayat()">
        <option value="">Semua Status</option>
        <option value="Hadir">Hadir</option>
        <option value="Izin">Izin</option>
        <option value="Sakit">Sakit</option>
        <option value="Alpha">Alpha</option>
    </select>
</div>
<div id="riwayatCount" style="font-size:11px; color:var(--pk-muted); margin:0 2px 8px;">Menampilkan {{ $riwayat_total }} dari {{ $riwayat_total }} data</div>

<div class="pk-card" id="riwayatList" style="padding:4px 16px; max-height:65vh; overflow-y:auto;">
    @php $has_data = false; @endphp
    @foreach ($riwayat as $r)
        @php
            $has_data = true;
            $ym_row = date('Y-m', strtotime($r->tanggal));
            $d = date('d/m', strtotime($r->tanggal));
            $day_name = $hari_arr[date('w', strtotime($r->tanggal))];
            $badge_class = strtolower($r->status);
            $icons_map = ['Hadir'=>'bx-check-circle','Izin'=>'bx-envelope','Sakit'=>'bx-plus-medical','Alpha'=>'bx-x-circle'];
        @endphp
    @if ($ym_row !== $prev_bulan)
        @php $prev_bulan = $ym_row; @endphp
    <div class="riwayat-bulan" data-bulan="{{ $ym_row }}" style="position:sticky; top:0; z-index:1; background:#fff; padding:10px 0 4px; font-size:11px; font-weight:700; letter-spacing:.4px; text-transform:uppercase; color:var(--pk-muted);">
        {{ $bulan_arr[(int) substr($ym_row, 5, 2)] . ' ' . substr($ym_row, 0, 4) }}
    </div>
    @endif
    <div class="m-list-item riwayat-item" data-bulan="{{ $ym_row }}" data-status="{{ $r->status }}">
        <div class="m-list-icon" style="background:{{ match($r->status) { 'Hadir'=>'#d1e7dd','Izin'=>'#fff3cd','Sakit'=>'#f8d7da', default=>'#e2e3e5' } }}; color:{{ match($r->status) { 'Hadir'=>'#0f5132','Izin'=>'#856404','Sakit'=>'#842029', default=>'#41464b' } }};">
            <i class='bx {{ $icons_map[$r->status] ?? 'bx-circle' }}'></i>
        </div>
        <div class="m-list-content">
            <div class="m-list-title">{{ $day_name }}, {{ $d }}</div>
            <div class="m-list-subtitle">
                @if ($r->jam_masuk)
                    Masuk: {{ substr($r->jam_masuk,0,5) }}
                    {{ $r->jam_keluar ? ' — Keluar: '.substr($r->jam_keluar,0,5) : '' }}
                @else
                    {{ $r->keterangan ? mb_strimwidth($r->keterangan,0,40,'...') : $r->status }}
                @endif
            </div>
        </div>
        <div class="m-list-action" style="display:flex; flex-direction:column; align-items:flex-end; gap:2px;">
            <span class="m-badge m-badge-{{ $badge_class }}">{{ $r->status }}</span>
            @if (in_array($r->status, ['Izin', 'Sakit']))
                @if ($r->approval_status === 'Pending')
                    <span style="font-size:9px; color:#ffc107; font-weight:600;"><i class='bx bx-time' style="vertical-align:middle;"></i> Pending</span>
                @elseif ($r->approval_status === 'Ditolak')
                    <span style="font-size:9px; color:#dc3545; font-weight:600;"><i class='bx bx-x-circle' style="vertical-align:middle;"></i> Ditolak</span>
                @else
                    <span style="font-size:9px; color:#198754; font-weight:600;"><i class='bx bx-check-circle' style="vertical-align:middle;"></i> Disetujui</span>
                @endif
            @endif
        </div>
    </div>
    @endforeach
    @if (! $has_data)
    <div class="m-empty">
        <i class='bx bx-calendar'></i>
        <div class="m-empty-text">Belum ada riwayat absensi</div>
    </div>
    @endif
    <div class="m-empty" id="riwayatKosong" style="display:none;">
        <i class='bx bx-filter-alt'></i>
        <div class="m-empty-text">Tidak ada data untuk filter ini</div>
    </div>
</div>

</div><!-- /kolom kanan -->
</div><!-- /pk-grid -->

<!-- ═══ Modal Izin/Sakit ═══ -->
<div class="m-modal-overlay" id="modalIzin">
    <div class="m-bottom-sheet">
        <div class="m-bottom-sheet-handle"></div>
        <div class="m-bottom-sheet-title" id="modalIzinTitle">Pengajuan Izin</div>
        <form id="formIzin" enctype="multipart/form-data">
            <input type="hidden" name="action" value="izin_sakit">
            <input type="hidden" name="status" id="izinStatus" value="Izin">

            <div style="display:flex; gap:10px; margin-bottom:12px;">
                <div style="flex:1;">
                    <label class="m-form-label">Mulai Tanggal</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="m-form-input" required value="{{ date('Y-m-d') }}">
                </div>
                <div style="flex:1;">
                    <label class="m-form-label">Selesai Tanggal</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="m-form-input" required value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="m-form-group">
                <label class="m-form-label">Keterangan</label>
                <textarea name="keterangan" class="m-form-input" rows="3" placeholder="Tulis alasan izin/sakit..." required style="resize:none;"></textarea>
            </div>

            <div class="m-form-group">
                <label class="m-form-label">Upload Surat <span style="color:var(--text-muted); font-weight:400;">(opsional)</span></label>
                <input type="file" name="file_surat" class="m-form-input" accept=".jpg,.jpeg,.png,.pdf" style="padding:8px;">
                <div class="m-form-hint">Format: JPG, PNG, PDF — Maks 2MB</div>
            </div>

            <button type="submit" class="action-btn action-btn-primary action-btn-block" id="btnSubmitIzin">
                <i class='bx bx-send'></i> Kirim Pengajuan
            </button>
            <button type="button" class="action-btn action-btn-outline action-btn-block" style="margin-top:8px;" onclick="closeIzinModal()">
                Batal
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Live Clock ──
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('liveClock').textContent = h + ':' + m + ':' + s;
}
setInterval(updateClock, 1000);

// ── Calendar ──
let calYear = {{ date('Y') }};
let calMonth = {{ date('n') }};

const bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function changeMonth(dir) {
    calMonth += dir;
    if (calMonth > 12) { calMonth = 1; calYear++; }
    if (calMonth < 1) { calMonth = 12; calYear--; }
    loadCalendar();
}

function loadCalendar() {
    document.getElementById('calTitle').textContent = bulanNames[calMonth] + ' ' + calYear;
    const ym = calYear + '-' + String(calMonth).padStart(2,'0');

    fetch('{{ route('peserta.absensi.proses') }}?action=get_calendar&bulan=' + ym)
    .then(r => r.json())
    .then(data => {
        renderCalendar(data);
    });
}

function renderCalendar(data) {
    const grid = document.getElementById('calendarGrid');
    // Keep day labels
    const labels = grid.querySelectorAll('.mini-calendar-day-label');
    grid.innerHTML = '';
    labels.forEach(l => grid.appendChild(l));

    const firstDay = new Date(calYear, calMonth - 1, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth, 0).getDate();
    const todayStr = new Date().toISOString().split('T')[0];

    // Empty cells
    for (let i = 0; i < firstDay; i++) {
        const el = document.createElement('div');
        el.className = 'mini-calendar-day empty';
        grid.appendChild(el);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = calYear + '-' + String(calMonth).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const el = document.createElement('div');
        el.className = 'mini-calendar-day';
        el.textContent = d;

        const dateObj = new Date(calYear, calMonth - 1, d);
        const dayOfWeek = dateObj.getDay(); // 0 = Minggu, 6 = Sabtu

        if (dateStr === todayStr) el.classList.add('today');
        if (data.absensi && data.absensi[dateStr]) {
            el.classList.add(data.absensi[dateStr].toLowerCase());
        }
        if (data.libur && data.libur.includes(dateStr)) {
            el.classList.add('libur');
        }
        if (data.libur_pekan && data.libur_pekan.includes(dayOfWeek)) {
            el.classList.add('libur');
        }

        grid.appendChild(el);
    }
}

// Load initial calendar
loadCalendar();

// ── GPS Helper ──
function getLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('GPS tidak tersedia di browser ini');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            err => {
                let msg = 'Gagal mendapatkan lokasi';
                if (err.code === 1) msg = 'Izin lokasi ditolak. Mohon aktifkan GPS dan izinkan akses lokasi.';
                else if (err.code === 2) msg = 'Lokasi tidak tersedia';
                else if (err.code === 3) msg = 'Waktu mendapatkan lokasi habis';
                reject(msg);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

// ── Geofencing Map Logic ──
let map = null;
let userMarker = null;
let officeMarker = null;
let geofenceCircle = null;

const officeLat = {{ $office_lat }};
const officeLng = {{ $office_lng }};
const geofenceRadius = {{ $radius_limit }};

function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function initAttendanceMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;

    // Initialize Leaflet Map
    map = L.map('map', {
        zoomControl: false,
        attributionControl: false
    }).setView([officeLat, officeLng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Marker Icon untuk Kantor (Warna Hijau)
    const officeIcon = L.divIcon({
        className: 'custom-div-icon',
        html: "<div style='background-color:#198754; width:12px; height:12px; border-radius:50%; border:2px solid white; box-shadow:0 0 4px rgba(0,0,0,0.5);'></div>",
        iconSize: [12, 12],
        iconAnchor: [6, 6]
    });

    officeMarker = L.marker([officeLat, officeLng], { icon: officeIcon })
        .addTo(map)
        .bindPopup('<b>Titik Absensi (Kantor)</b>');

    // Lingkaran Radius Geofence (Default: Kuning sebelum GPS terdeteksi)
    geofenceCircle = L.circle([officeLat, officeLng], {
        radius: geofenceRadius,
        color: '#ffc107',
        fillColor: '#fff3cd',
        fillOpacity: 0.15,
        weight: 1.5
    }).addTo(map);

    // Ambil Lokasi User
    getLocation().then(loc => {
        const userLat = loc.lat;
        const userLng = loc.lng;

        document.getElementById('userCoords').textContent = userLat.toFixed(6) + ', ' + userLng.toFixed(6);

        const dist = getDistance(userLat, userLng, officeLat, officeLng);
        const isInside = dist <= geofenceRadius;

        const statusEl = document.getElementById('distanceStatus');
        if (isInside) {
            statusEl.className = 'pk-pill in';
            statusEl.innerHTML = '<i class="bx bxs-check-circle"></i> Dalam Radius (' + Math.round(dist) + 'm)';
            geofenceCircle.setStyle({
                color: '#198754',
                fillColor: '#d1e7dd'
            });
        } else {
            statusEl.className = 'pk-pill out';
            statusEl.innerHTML = '<i class="bx bxs-x-circle"></i> Luar Radius (' + Math.round(dist) + 'm)';
            geofenceCircle.setStyle({
                color: '#dc3545',
                fillColor: '#f8d7da'
            });
        }

        // Marker Icon untuk User (Lingkaran biru pulsing)
        const userIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color:#0d6efd; width:14px; height:14px; border-radius:50%; border:2px solid white; box-shadow:0 0 6px rgba(13,110,253,0.8); animation: mapPulse 1.8s infinite;'></div>",
            iconSize: [14, 14],
            iconAnchor: [7, 7]
        });

        userMarker = L.marker([userLat, userLng], { icon: userIcon })
            .addTo(map)
            .bindPopup('<b>Posisi Anda</b>');

        // Zoom otomatis agar muat marker Kantor & User
        const group = new L.featureGroup([officeMarker, userMarker]);
        map.fitBounds(group.getBounds().pad(0.35));

    }).catch(err => {
        document.getElementById('userCoords').textContent = 'Gagal mengakses GPS';
        const statusEl = document.getElementById('distanceStatus');
        statusEl.className = 'pk-pill out';
        statusEl.textContent = 'GPS Tidak Aktif';
        console.warn(err);
    });
}

// Jalankan Inisialisasi Peta
initAttendanceMap();

// ── Absen Masuk ──
async function doClockIn() {
    const btn = document.getElementById('btnClockIn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>Mencari GPS...</span>';

    try {
        const loc = await getLocation();
        const fd = new FormData();
        fd.append('action', 'clock_in');
        fd.append('lat', loc.lat);
        fd.append('lng', loc.lng);
        fd.append('_token', '{{ csrf_token() }}');

        const resp = await fetch('{{ route('peserta.absensi.proses') }}', { method: 'POST', body: fd });
        const result = await resp.json();

        if (result.success) {
            showToast('Absen Masuk berhasil! ' + result.jam, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-log-in-circle"></i><span>Absen Masuk</span>';
        }
    } catch (e) {
        showToast(e, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-log-in-circle"></i><span>Absen Masuk</span>';
    }
}

// ── Absen Pulang ──
async function doClockOut() {
    const btn = document.getElementById('btnClockOut');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>Mencari GPS...</span>';

    try {
        const loc = await getLocation();
        const fd = new FormData();
        fd.append('action', 'clock_out');
        fd.append('lat', loc.lat);
        fd.append('lng', loc.lng);
        fd.append('_token', '{{ csrf_token() }}');

        const resp = await fetch('{{ route('peserta.absensi.proses') }}', { method: 'POST', body: fd });
        const result = await resp.json();

        if (result.success) {
            showToast('Absen Pulang berhasil! ' + result.jam, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-log-out-circle"></i><span>Absen Pulang</span>';
        }
    } catch (e) {
        showToast(e, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-log-out-circle"></i><span>Absen Pulang</span>';
    }
}

// ── Modal Izin/Sakit ──
function openIzinModal(status) {
    document.getElementById('izinStatus').value = status;
    document.getElementById('modalIzinTitle').textContent = 'Pengajuan ' + status;
    document.getElementById('modalIzin').classList.add('show');
}

function closeIzinModal() {
    document.getElementById('modalIzin').classList.remove('show');
}

// Close modal on overlay click
document.getElementById('modalIzin').addEventListener('click', function(e) {
    if (e.target === this) closeIzinModal();
});

// ── Submit Izin/Sakit ──
document.getElementById('formIzin').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitIzin');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Mengirim...';

    const fd = new FormData(this);
    fd.append('_token', '{{ csrf_token() }}');

    try {
        const resp = await fetch('{{ route('peserta.absensi.proses') }}', { method: 'POST', body: fd });
        const result = await resp.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeIzinModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bx bx-send"></i> Kirim Pengajuan';
});

// ── Filter Riwayat Absensi (Bulan & Status) ──
function filterRiwayat() {
    const bulan = document.getElementById('filterBulan').value;
    const status = document.getElementById('filterStatus').value;
    const items = document.querySelectorAll('#riwayatList .riwayat-item');
    let tampil = 0;

    items.forEach(el => {
        const ok = (!bulan || el.dataset.bulan === bulan) && (!status || el.dataset.status === status);
        el.style.display = ok ? '' : 'none';
        if (ok) tampil++;
    });

    // Sembunyikan judul bulan yang tidak punya data tampil
    document.querySelectorAll('#riwayatList .riwayat-bulan').forEach(h => {
        let ada = false, n = h.nextElementSibling;
        while (n && !n.classList.contains('riwayat-bulan')) {
            if (n.classList.contains('riwayat-item') && n.style.display !== 'none') { ada = true; break; }
            n = n.nextElementSibling;
        }
        h.style.display = ada ? '' : 'none';
    });

    const kosong = document.getElementById('riwayatKosong');
    if (kosong) kosong.style.display = (items.length && !tampil) ? '' : 'none';
    document.getElementById('riwayatCount').textContent = 'Menampilkan ' + tampil + ' dari ' + items.length + ' data';
}
</script>
@endsection
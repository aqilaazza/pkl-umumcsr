@extends('layouts.peserta')

@section('content')
@php
    $tgl_indo_home = function ($t) {
        if (empty($t) || $t == '0000-00-00') return '-';
        $b = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $p = explode('-', $t);
        return $p[2] . ' ' . $b[(int)$p[1]] . ' ' . $p[0];
    };
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan_full = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $today_label = $hari[date('w')] . ', ' . date('d') . ' ' . $bulan_full[(int)date('m')] . ' ' . date('Y');

    // Warna aksen kartu status (logika sama seperti sebelumnya)
    if ($is_libur) {
        $st_color = '#0d6efd';
    } elseif ($absensi_today && $absensi_today->jam_keluar) {
        $st_color = '#198754';
    } elseif ($absensi_today && $absensi_today->jam_masuk) {
        $st_color = '#ffc107';
    } else {
        $st_color = '#dc3545';
    }

    $nama_home = ucwords(auth()->user()->nama ?? 'Peserta');
@endphp

<style>
    :root {
        --hm-blue: #0b4fb3;
        --hm-ink: #16233b;
        --hm-muted: #6b7a90;
        --hm-line: #e6ecf5;
        --hm-soft: #f4f7fc;
    }

    .hm-card {
        background: #fff; border-radius: 20px; padding: 16px; margin-bottom: 12px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
        border: 1px solid rgba(230, 236, 245, .9);
    }
    .hm-eyebrow {
        font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
        color: var(--hm-muted); display: flex; align-items: center; gap: 6px;
    }
    .hm-eyebrow i { font-size: 14px; color: var(--hm-blue); }
    .hm-section-title {
        font-size: 13px; font-weight: 800; color: var(--hm-ink);
        display: flex; align-items: center; gap: 6px; margin: 4px 2px 10px;
    }
    .hm-section-title i { color: var(--hm-blue); font-size: 18px; }

    .hm-flow { width: 100%; }


    /* ── Status absensi ── */
    .hm-status { border-left: 5px solid var(--st, #dc3545); }
    .hm-status-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .hm-status-text { font-size: 20px; font-weight: 800; color: var(--hm-ink); margin-top: 6px; display: flex; align-items: center; gap: 6px; }
    .hm-status-text i { font-size: 22px; color: var(--st, #dc3545); }
    .hm-time { text-align: right; background: var(--hm-soft); border: 1px solid var(--hm-line); border-radius: 14px; padding: 8px 14px; }
    .hm-time-label { font-size: 9px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--hm-muted); }
    .hm-time-value { font-size: 20px; font-weight: 800; color: var(--hm-blue); font-variant-numeric: tabular-nums; }

    .hm-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        border: 0; border-radius: 16px; padding: 14px 16px; margin-top: 14px;
        font-family: inherit; font-size: 14px; font-weight: 800; text-decoration: none; cursor: pointer; color: #fff;
        transition: transform .15s ease;
    }
    .hm-btn:active { transform: scale(.98); }
    .hm-btn i { font-size: 20px; }
    .hm-btn-in  { background: linear-gradient(135deg, #2f7cf0, var(--hm-blue)); box-shadow: 0 8px 18px rgba(11, 79, 179, .30); }
    .hm-btn-out { background: linear-gradient(135deg, #ff9a5c, #e8590c); box-shadow: 0 8px 18px rgba(232, 89, 12, .30); }
    .hm-btn-off { background: #e2e3e5; color: #6c757d; cursor: not-allowed; box-shadow: none; }
    .hm-note { margin-top: 10px; font-size: 11px; color: var(--hm-muted); display: flex; gap: 6px; align-items: flex-start; }
    .hm-note i { font-size: 14px; margin-top: 1px; }

    /* ── Statistik ── */
    .hm-stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .hm-stat-grid.row { grid-template-columns: repeat(12, minmax(0, 1fr)); }
    .hm-stat {
        background: #fff; border-radius: 18px; padding: 14px 6px; text-align: center;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07); border: 1px solid rgba(230, 236, 245, .9);
        height: 100%;
    }
    .hm-stat-ico {
        width: 44px; height: 44px; border-radius: 14px; margin: 0 auto 8px;
        display: flex; align-items: center; justify-content: center; font-size: 23px;
    }
    .hm-stat-value { font-size: 28px; font-weight: 800; line-height: 1.1; color: var(--hm-ink); }
    .hm-stat-value.sm { font-size: 18px; display: flex; align-items: center; justify-content: center; gap: 6px; }
    .hm-stat-sub { font-size: 11.5px; color: var(--hm-muted); margin-top: 4px; font-weight: 700; line-height: 1.2; }

    /* ── Laporan terakhir ── */
    .hm-lap { display: flex; align-items: center; gap: 12px; }
    .hm-lap-ico { width: 42px; height: 42px; border-radius: 14px; background: #e8f0ff; color: var(--hm-blue); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .hm-lap-body { flex: 1; min-width: 0; }
    .hm-lap-name { font-size: 13px; font-weight: 700; color: var(--hm-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hm-lap-date { font-size: 11px; color: var(--hm-muted); margin-top: 2px; }
    .hm-reject {
        margin-top: 12px; padding: 10px 12px; border-radius: 12px; font-size: 12px;
        background: #f8d7da; color: #842029; display: flex; gap: 6px; align-items: flex-start;
    }
    .hm-reject i { font-size: 16px; margin-top: 1px; }

    /* ── Aksi cepat ── */
    .hm-quick { margin-bottom: 12px; }
    .hm-quick > [class*="col-"] { display: flex; }
    .hm-q {
        background: #fff; border-radius: 18px; padding: 14px 6px; text-align: center; text-decoration: none;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07); border: 1px solid rgba(230, 236, 245, .9);
        transition: transform .15s ease, box-shadow .15s ease; width: 100%; height: 100%;
    }
    .hm-q:active { transform: scale(.97); }
    .hm-q-ico { width: 44px; height: 44px; border-radius: 14px; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; font-size: 23px; }
    .hm-q-text { font-size: 11.5px; font-weight: 700; color: var(--hm-ink); line-height: 1.2; }

    /* ── Periode magang ── */
    .hm-periode { border-left: 5px solid var(--info, #0dcaf0); }
    .hm-periode-date { font-size: 15px; font-weight: 800; color: var(--hm-ink); margin-top: 6px; }
    .hm-prog-info { display: flex; justify-content: space-between; font-size: 11px; font-weight: 600; color: var(--hm-muted); margin: 14px 0 6px; }
    .hm-prog-info b { color: var(--hm-blue); }
    .hm-prog-track { height: 8px; border-radius: 999px; background: var(--hm-line); overflow: hidden; }
    .hm-prog-bar { height: 100%; border-radius: 999px; background: var(--primary-gradient, linear-gradient(90deg, #2f7cf0, #0b4fb3)); }

    /* ── Responsif ── */
    @@media (max-width: 360px) {
        .hm-card { padding: 14px 12px; }
        .hm-stat { padding: 12px 6px; }
        .hm-stat-value { font-size: 24px; }
        .hm-status-text { font-size: 17px; }
        .hm-time-value { font-size: 17px; }
        .hm-q-text { font-size: 10.5px; }
    }
    @@media (min-width: 768px) {
        .hm-q:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(24, 60, 120, .12); }
    }
</style>

<div class="hm-flow w-100">

<!-- ═══ Status Absensi Hari Ini ═══ -->
<div class="hm-card hm-status" style="--st: {{ $st_color }};">
    <div class="hm-status-row">
        <div>
            <div class="hm-eyebrow"><i class='bx bx-time-five'></i> Absensi Hari Ini</div>
            <div class="hm-status-text">
                @if ($is_libur)
                    <i class="bx bx-calendar me-1"></i>{{ $is_libur }}
                @elseif ($absensi_today)
                    @if ($absensi_today->status === 'Izin')
                        <i class="bx bx-edit me-1"></i>Izin
                    @elseif ($absensi_today->status === 'Sakit')
                        <i class="bx bx-plus-medical me-1"></i>Sakit
                    @elseif ($absensi_today->jam_keluar)
                        <i class="bx bx-check-circle me-1"></i>Selesai
                    @else
                        <i class="bx bx-circle me-1"></i>Sudah Masuk
                    @endif
                @else
                    <i class="bx bx-time me-1"></i>Belum Absen
                @endif
            </div>
        </div>
        @if ($absensi_today && $absensi_today->jam_masuk)
        <div class="hm-time">
            <div class="hm-time-label">Masuk</div>
            <div class="hm-time-value">{{ substr($absensi_today->jam_masuk,0,5) }}</div>
        </div>
        @endif
    </div>

    @if (! $is_libur && (! $absensi_today || ! $absensi_today->jam_masuk))
    <a href="{{ route('peserta.absensi') }}" class="hm-btn hm-btn-in">
        <i class='bx bx-log-in'></i> Absen Masuk
    </a>
    @elseif ($absensi_today && $absensi_today->jam_masuk && ! $absensi_today->jam_keluar && $absensi_today->status === 'Hadir')
        @if ($boleh_pulang)
        <a href="{{ route('peserta.absensi') }}" class="hm-btn hm-btn-out">
            <i class='bx bx-log-out'></i> Absen Pulang
        </a>
        @else
        <button type="button" class="hm-btn hm-btn-off" disabled>
            <i class='bx bx-time'></i> Absen Pulang {{ $min_keluar_jam }}
        </button>
        <div class="hm-note">
            <i class='bx bx-info-circle'></i>
            <span>Belum waktunya pulang 😊 Dibuka pukul {{ $min_keluar_jam }} (± {{ $menit_tersisa }} menit lagi)</span>
        </div>
        @endif
    @endif
</div>

<!-- ═══ Quick Stats ═══ -->
<div class="hm-stat-grid row g-3">
    <div class="hm-stat col-6 col-sm-4 col-lg-3">
        <div class="hm-stat-ico" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-check-circle'></i></div>
        <div class="hm-stat-value">{{ $stat_hadir }}</div>
        <div class="hm-stat-sub">Hadir (Bulan Ini)</div>
    </div>
    <div class="hm-stat col-6 col-sm-4 col-lg-3">
        <div class="hm-stat-ico" style="background:#fff3cd; color:#856404;"><i class='bx bx-envelope'></i></div>
        <div class="hm-stat-value">{{ $stat_izin + $stat_sakit }}</div>
        <div class="hm-stat-sub">Izin/Sakit</div>
    </div>
    <div class="hm-stat col-6 col-sm-4 col-lg-3">
        <div class="hm-stat-ico" style="background:#e3eeff; color:#0b4fb3;"><i class='bx bx-file'></i></div>
        <div class="hm-stat-value">{{ $jumlah_laporan }}</div>
        <div class="hm-stat-sub">Laporan Upload</div>
    </div>
    <div class="hm-stat col-6 col-sm-4 col-lg-3">
        <div class="hm-stat-ico" style="background:#efe6ff; color:#6f42c1;"><i class='bx bx-award'></i></div>
        <div class="hm-stat-value sm">
            @if ($sertifikat && ($sertifikat->status === 'Sudah Upload' || $sertifikat->status === 'Digital'))
                <i class="bx bx-check-circle text-success me-1" style="color:#198754;"></i>Terbit
            @elseif ($sertifikat)
                <i class="bx bx-time text-warning me-1" style="color:#e0a800;"></i>Proses
            @else
                <i class="bx bx-lock text-muted me-1" style="color:#6c757d;"></i>Belum
            @endif
        </div>
        <div class="hm-stat-sub">Sertifikat</div>
    </div>
</div>

<!-- ═══ Status Laporan Terakhir ═══ -->
@if ($laporan_aktif)
<div class="hm-section-title"><i class='bx bx-file'></i> Laporan Terakhir</div>
<div class="hm-card">
    <div class="hm-lap">
        <div class="hm-lap-ico"><i class='bx bx-file'></i></div>
        <div class="hm-lap-body">
            <div class="hm-lap-name">
                {{ $laporan_aktif->nama_file ?? '-' }}
            </div>
            <div class="hm-lap-date">
                Upload: {{ date('d/m/Y H:i', strtotime($laporan_aktif->tgl_upload)) }}
            </div>
        </div>
        <span class="m-badge m-badge-{{ strtolower($laporan_aktif->status) }}">
            @php
                $icons = ['Menunggu'=>'bx-time','Disetujui'=>'bx-check-circle','Ditolak'=>'bx-x-circle'];
                $ic = $icons[$laporan_aktif->status] ?? 'bx-question-mark';
            @endphp
            <i class='bx {{ $ic }}'></i> {{ $laporan_aktif->status }}
        </span>
    </div>
    @if ($laporan_aktif->status === 'Ditolak' && $laporan_aktif->keterangan_tolak)
    <div class="hm-reject">
        <i class='bx bx-message-error'></i>
        <span>{{ mb_strimwidth($laporan_aktif->keterangan_tolak, 0, 80, '...') }}</span>
    </div>
    @endif
</div>
@endif

<!-- ═══ Quick Actions ═══ -->
<div class="hm-section-title"><i class='bx bx-zap'></i> Aksi Cepat</div>
<div class="hm-quick row g-3">
    <div class="col-6 col-sm-4 col-lg-3">
        <a href="{{ route('peserta.absensi') }}" class="hm-q">
            <div class="hm-q-ico" style="background:#e3eeff; color:#0b4fb3;"><i class='bx bx-calendar-check'></i></div>
            <div class="hm-q-text">Absensi</div>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-lg-3">
        <a href="{{ route('peserta.laporan') }}" class="hm-q">
            <div class="hm-q-ico" style="background:#dff5e8; color:#198754;"><i class='bx bx-upload'></i></div>
            <div class="hm-q-text">Upload Laporan</div>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-lg-3">
        <a href="{{ route('peserta.dokumen') }}" class="hm-q">
            <div class="hm-q-ico" style="background:#fff2d6; color:#b7791f;"><i class='bx bx-folder-open'></i></div>
            <div class="hm-q-text">Dokumen SDM</div>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-lg-3">
        <a href="{{ route('peserta.sertifikat') }}" class="hm-q">
            <div class="hm-q-ico" style="background:#efe6ff; color:#6f42c1;"><i class='bx bx-award'></i></div>
            <div class="hm-q-text">Sertifikat</div>
        </a>
    </div>
    <div class="col-6 col-sm-4 col-lg-3">
        <a href="{{ route('peserta.laporan.referensi') }}" class="hm-q">
            <div class="hm-q-ico" style="background:#dff4f7; color:#0d8a9b;"><i class='bx bx-book'></i></div>
            <div class="hm-q-text">Referensi</div>
        </a>
    </div>
</div>


@if ($peserta && $peserta->tgl_masuk && $peserta->tgl_keluar)
<div class="hm-card hm-periode">
    <div class="hm-eyebrow"><i class='bx bx-calendar-event'></i> Periode Magang</div>
    <div class="hm-periode-date">
        {{ $tgl_indo_home($peserta->tgl_masuk) }} — {{ $tgl_indo_home($peserta->tgl_keluar) }}
    </div>
    @php
        $sisa = (strtotime($peserta->tgl_keluar) - time()) / 86400;
    @endphp
    @if ($sisa > 0)
    <div>
        <div class="hm-prog-info">
            <span>Progress</span>
            <b>{{ max(0, round($sisa)) }} hari lagi</b>
        </div>
        @php
            $total_days = (strtotime($peserta->tgl_keluar) - strtotime($peserta->tgl_masuk)) / 86400;
            $elapsed = $total_days - $sisa;
            $pct = min(100, max(0, ($elapsed / $total_days) * 100));
        @endphp
        <div class="hm-prog-track">
            <div class="hm-prog-bar" style="width:{{ round($pct) }}%;"></div>
        </div>
    </div>
    @endif
</div>
@endif

</div><!-- /hm-flow -->
@endsection

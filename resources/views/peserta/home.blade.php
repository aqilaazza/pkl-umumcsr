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
@endphp

<!-- ═══ Status Absensi Hari Ini ═══ -->
<div class="m-card border-left-status" style="border-left-color: @if ($is_libur) #0d6efd @elseif ($absensi_today && $absensi_today->jam_keluar) #198754 @elseif ($absensi_today && $absensi_today->jam_masuk) #ffc107 @else #dc3545 @endif;">
    <div class="d-flex align-center justify-between">
        <div>
            <div class="absensi-label">ABSENSI HARI INI</div>
            <div class="absensi-status-text">
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
        <div class="text-right">
            @if ($absensi_today && $absensi_today->jam_masuk)
            <div class="absensi-time-label">Masuk</div>
            <div class="absensi-time-value">{{ substr($absensi_today->jam_masuk,0,5) }}</div>
            @endif
        </div>
    </div>
    @if (! $is_libur && (! $absensi_today || ! $absensi_today->jam_masuk))
    <a href="{{ route('peserta.absensi') }}" class="action-btn action-btn-primary action-btn-block mt-12 text-deco-none">
        <i class='bx bx-log-in'></i> Absen Masuk
    </a>
    @elseif ($absensi_today && $absensi_today->jam_masuk && ! $absensi_today->jam_keluar && $absensi_today->status === 'Hadir')
    <a href="{{ route('peserta.absensi') }}" class="action-btn action-btn-danger action-btn-block mt-12 text-deco-none">
        <i class='bx bx-log-out'></i> Absen Pulang
    </a>
    @endif
</div>

<!-- ═══ Quick Stats ═══ -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon bg-stat-hadir">
            <i class='bx bx-check-circle'></i>
        </div>
        <div class="stat-value">{{ $stat_hadir }}</div>
        <div class="stat-label">Hadir (Bulan Ini)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-stat-izin">
            <i class='bx bx-envelope'></i>
        </div>
        <div class="stat-value">{{ $stat_izin + $stat_sakit }}</div>
        <div class="stat-label">Izin/Sakit</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-stat-laporan">
            <i class='bx bx-file'></i>
        </div>
        <div class="stat-value">{{ $jumlah_laporan }}</div>
        <div class="stat-label">Laporan Upload</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-stat-sertifikat {{ $sertifikat && ($sertifikat->status === 'Sudah Upload' || $sertifikat->status === 'Digital') ? 'bg-stat-hadir' : 'bg-stat-dim' }}">
            <i class='bx bx-award'></i>
        </div>
        <div class="stat-value fs-14">
            @if ($sertifikat && ($sertifikat->status === 'Sudah Upload' || $sertifikat->status === 'Digital'))
                <i class="bx bx-check-circle text-success me-1"></i>Terbit
            @elseif ($sertifikat)
                <i class="bx bx-time text-warning me-1"></i>Proses
            @else
                <i class="bx bx-lock text-muted me-1"></i>Belum
            @endif
        </div>
        <div class="stat-label">Sertifikat</div>
    </div>
</div>

<!-- ═══ Status Laporan Terakhir ═══ -->
@if ($laporan_aktif)
<div class="m-section-title"><i class='bx bx-file'></i> Laporan Terakhir</div>
<div class="m-card">
    <div class="d-flex align-center justify-between">
        <div class="flex-1">
            <div class="laporan-file-name text-truncate">
                {{ $laporan_aktif->nama_file ?? '-' }}
            </div>
            <div class="laporan-upload-date">
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
    <div class="rejection-box">
        <i class='bx bx-message-error'></i> {{ mb_strimwidth($laporan_aktif->keterangan_tolak, 0, 80, '...') }}
    </div>
    @endif
</div>
@endif

<!-- ═══ Quick Actions ═══ -->
<div class="m-section-title"><i class='bx bx-zap'></i> Aksi Cepat</div>
<div class="quick-actions-grid">
    <a href="{{ route('peserta.absensi') }}" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-calendar-check'></i></div>
        <div class="quick-action-text">Absensi</div>
    </a>
    <a href="{{ route('peserta.laporan') }}" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-upload'></i></div>
        <div class="quick-action-text">Upload Laporan</div>
    </a>
    <a href="{{ route('peserta.dokumen') }}" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-folder-open'></i></div>
        <div class="quick-action-text">Dokumen SDM</div>
    </a>
    <a href="{{ route('peserta.sertifikat') }}" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-award'></i></div>
        <div class="quick-action-text">Sertifikat</div>
    </a>
    <a href="{{ route('peserta.laporan.referensi') }}" class="m-card quick-action-card">
        <div class="quick-action-icon"><i class='bx bx-book'></i></div>
        <div class="quick-action-text">Referensi</div>
    </a>
</div>

<!-- ═══ Info Periode ═══ -->
@if ($peserta && $peserta->tgl_masuk && $peserta->tgl_keluar)
<div class="m-card" style="border-left: 4px solid var(--info);">
    <div class="periode-label">PERIODE MAGANG</div>
    <div class="periode-date">
        {{ $tgl_indo_home($peserta->tgl_masuk) }} — {{ $tgl_indo_home($peserta->tgl_keluar) }}
    </div>
    @php
        $sisa = (strtotime($peserta->tgl_keluar) - time()) / 86400;
    @endphp
    @if ($sisa > 0)
    <div class="mt-8">
        <div class="progress-info">
            <span>Progress</span>
            <span>{{ max(0, round($sisa)) }} hari lagi</span>
        </div>
        @php
            $total_days = (strtotime($peserta->tgl_keluar) - strtotime($peserta->tgl_masuk)) / 86400;
            $elapsed = $total_days - $sisa;
            $pct = min(100, max(0, ($elapsed / $total_days) * 100));
        @endphp
        <div class="m-progress">
            <div class="m-progress-bar" style="width:{{ round($pct) }}%; background:var(--primary-gradient);"></div>
        </div>
    </div>
    @endif
</div>
@endif
@endsection

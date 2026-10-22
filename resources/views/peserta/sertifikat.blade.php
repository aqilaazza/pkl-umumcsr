@extends('layouts.peserta')

@section('content')
@php
    $tgl_indo_sert = function ($t) {
        if (empty($t) || $t == '0000-00-00') return '-';
        $b = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $p = explode('-', $t);
        return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
    };

    // Warna nada: [latar, teks, aksen]
    $tone = [
        'green' => ['#d9f0e3', '#0f5132', '#198754'],
        'amber' => ['#fff1c9', '#7a5800', '#e0a800'],
        'info'  => ['#d5f1f8', '#055160', '#0aa2c0'],
        'red'   => ['#fbdcdf', '#842029', '#dc3545'],
        'grey'  => ['#e9edf3', '#5b6b82', '#8a98ad'],
    ];

    // Render linimasa dari daftar [judul, state, keterangan]
    $timeline = function (array $steps) {
        $icons = ['done' => 'bx-check', 'current' => 'bx-loader-alt bx-spin', 'pending' => 'bx-time-five', 'rejected' => 'bx-x'];
        $h = '<div class="sf-tl">';
        foreach ($steps as $s) {
            $h .= '<div class="sf-step ' . e($s[1]) . '">'
                . '<div class="sf-dot"><i class="bx ' . $icons[$s[1]] . '"></i></div>'
                . '<div class="sf-step-body">'
                . '<div class="sf-step-title">' . e($s[0]) . '</div>'
                . (! empty($s[2]) ? '<div class="sf-step-desc">' . e($s[2]) . '</div>' : '')
                . '</div></div>';
        }
        return new \Illuminate\Support\HtmlString($h . '</div>');
    };

    // Tentukan tampilan (urutan kondisi sama seperti sebelumnya)
    $mode = 'status';
    $cfg  = [];

    if ($is_digital) {
        $mode = 'file';
        $cfg = [
            'file'       => $sertifikat->digital_file,
            'badge'      => 'Sertifikat Digital',
            'title'      => 'Sertifikat Digital Terbit',
            'text'       => 'Sertifikat digital Anda sudah diterbitkan dengan QR Code',
            'note'       => 'Dilengkapi QR Code',
            'view_label' => 'Lihat Sertifikat',
            'dl_label'   => 'Download Sertifikat Digital',
            'rows'       => [
                ['bx-qr', '#d1e7dd', '#0f5132', 'QR Code', 'Terdapat QR Code untuk verifikasi'],
                ['bx-calendar', '#cfe2ff', '#084298', 'Diterbitkan', $tgl_indo_sert(date('Y-m-d', strtotime($sertifikat->generated_at ?? '')))],
            ],
        ];
    } elseif ($is_uploaded) {
        $mode = 'file';
        $cfg = [
            'file'       => $sertifikat->file_sertifikat,
            'badge'      => 'Sertifikat Resmi',
            'title'      => 'Sertifikat Tersedia',
            'text'       => 'Sertifikat Anda sudah ditandatangani dan siap diunduh',
            'note'       => 'Sudah ditandatangani',
            'view_label' => 'Lihat Sertifikat',
            'dl_label'   => 'Download Sertifikat',
            'rows'       => [
                ['bx-hash', '#d1e7dd', '#0f5132', 'Nomor Surat', $sertifikat->nomor_surat ?? '-'],
                ['bx-calendar', '#cfe2ff', '#084298', 'Tersedia Sejak', $tgl_indo_sert(date('Y-m-d', strtotime($sertifikat->tgl_upload_scan ?? '')))],
            ],
        ];
    } elseif ($manager_ok) {
        $cfg = [
            'tone' => 'green', 'icon' => 'bx-badge-check', 'badge' => 'Disetujui',
            'title' => 'Laporan Disetujui',
            'text'  => 'Sertifikat digital sedang diproses',
            'steps' => [
                ['Laporan Diupload', 'done', ''],
                ['Disetujui SDM', 'done', ''],
                ['Disetujui Manager', 'done', ''],
                ['Terbit Sertifikat Digital', 'current', 'Sedang diproses'],
            ],
        ];
    } elseif ($sdm_ok && $laporan_aktif->manager_status === 'Menunggu') {
        $cfg = [
            'tone' => 'amber', 'icon' => 'bx-time-five', 'badge' => 'Menunggu Manager',
            'title' => 'Menunggu Approval Manager',
            'text'  => 'Laporan sudah disetujui SDM, menunggu approval Manager',
            'steps' => [
                ['Laporan Diupload', 'done', ''],
                ['Disetujui SDM', 'done', ''],
                ['Menunggu Manager', 'current', 'Sedang ditinjau'],
                ['Sertifikat Digital', 'pending', ''],
            ],
        ];
    } elseif ($laporan_aktif && $laporan_aktif->status === 'Disetujui') {
        $cfg = [
            'tone' => 'info', 'icon' => 'bx-list-ul', 'badge' => 'Antrian Petugas',
            'title' => 'Dalam Antrian',
            'text'  => 'Sertifikat dalam antrian pembuatan oleh petugas',
            'steps' => [
                ['Laporan Disetujui', 'done', ''],
                ['Cetak Sertifikat', 'pending', ''],
                ['Proses Tanda Tangan', 'pending', ''],
                ['Siap Diunduh', 'pending', ''],
            ],
        ];
    } elseif ($laporan_aktif && $laporan_aktif->status === 'Menunggu Manager') {
        $cfg = [
            'tone' => 'amber', 'icon' => 'bx-user-check', 'badge' => 'Menunggu Manager',
            'title' => 'Menunggu Persetujuan Manager',
            'text'  => 'Laporan Anda sudah direview SDM dan menunggu approval Manager',
            'steps' => [
                ['Laporan Diupload', 'done', ''],
                ['Disetujui SDM', 'done', ''],
                ['Approval Manager', 'current', 'Sedang ditinjau'],
                ['Sertifikat Digital', 'pending', ''],
            ],
        ];
    } elseif ($laporan_aktif && $laporan_aktif->status === 'Ditolak') {
        $alasan = $laporan_aktif->manager_keterangan_tolak ?: $laporan_aktif->sdm_keterangan_tolak ?: 'Silakan hubungi petugas';
        $cfg = [
            'tone' => 'red', 'icon' => 'bx-x-circle', 'badge' => 'Ditolak',
            'title' => 'Laporan Ditolak',
            'text'  => 'Alasan: ' . $alasan,
            'steps' => [],
            'action_label' => 'Upload Ulang', 'action_icon' => 'bx-upload',
        ];
    } else {
        $cfg = [
            'tone' => 'grey', 'icon' => 'bx-lock-alt', 'badge' => 'Belum Tersedia',
            'title' => 'Sertifikat Belum Tersedia',
            'text'  => 'Sertifikat akan tersedia setelah laporan magang Anda disetujui oleh SDM dan Manager',
            'steps' => [],
            'action_label' => 'Upload Laporan', 'action_icon' => 'bx-upload',
        ];
    }

    $t = $tone[$cfg['tone'] ?? 'green'];

    // Validasi otentikasi (hanya sertifikat digital)
    // $qr_url = isi QR. Saat ini membuka file sertifikat digital.
    // Kalau sistem punya halaman verifikasi resmi, ganti nilainya ke URL halaman tersebut.
    $sf_hash = null;
    $qr_url  = null;
    if ($is_digital) {
        $qr_url = url('/uploads/sertifikat/' . $sertifikat->digital_file);
        $sf_path = public_path('uploads/sertifikat/' . $sertifikat->digital_file);
        if (is_file($sf_path)) {
            $sf_hash = hash_file('sha256', $sf_path);
        }
    }
@endphp

<style>
    :root {
        --sf-blue: #0b4fb3;
        --sf-ink: #16233b;
        --sf-muted: #6b7a90;
        --sf-line: #e6ecf5;
        --sf-soft: #f4f7fc;
    }

    .sf-card {
        background: #fff; border-radius: 20px; padding: 16px; margin-bottom: 12px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
        border: 1px solid rgba(230, 236, 245, .9);
    }
    .sf-grid { display: block; }
    .sf-col { min-width: 0; }
    .sf-eyebrow {
        font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
        color: var(--sf-muted); display: flex; align-items: center; gap: 6px;
    }
    .sf-eyebrow i { font-size: 14px; color: var(--sf-blue); }

    /* ── Hero ── */
    .sf-hero {
        text-align: center; padding: 28px 20px 22px;
        background: linear-gradient(180deg, var(--tint, #e8f0ff) 0%, #fff 68%);
    }
    .sf-hero-ico {
        width: 68px; height: 68px; border-radius: 22px; margin: 0 auto 14px;
        display: flex; align-items: center; justify-content: center; font-size: 34px;
        background: #fff; color: var(--fg, var(--sf-blue));
        box-shadow: 0 0 0 6px rgba(255, 255, 255, .7), 0 10px 22px rgba(24, 60, 120, .12);
    }
    .sf-badge {
        display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 999px;
        font-size: 10.5px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase;
        background: rgba(255, 255, 255, .85); color: var(--fg, var(--sf-blue));
        border: 1px solid rgba(0, 0, 0, .05);
    }
    .sf-hero-title { font-size: 18px; font-weight: 800; color: var(--sf-ink); margin-top: 10px; line-height: 1.25; }
    .sf-hero-text { font-size: 12.5px; color: var(--sf-muted); margin: 6px auto 0; line-height: 1.55; max-width: 360px; }

    /* ── Tombol ── */
    .sf-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        border: 0; border-radius: 16px; padding: 15px 16px; text-decoration: none; cursor: pointer;
        font-family: inherit; font-size: 14px; font-weight: 800; transition: transform .15s ease;
    }
    .sf-btn:active { transform: scale(.98); }
    .sf-btn i { font-size: 20px; }
    .sf-btn.primary { background: linear-gradient(135deg, #2f7cf0, var(--sf-blue)); color: #fff; box-shadow: 0 8px 18px rgba(11, 79, 179, .30); }
    .sf-btn.outline { background: #fff; color: var(--sf-blue); border: 1px solid var(--sf-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06); }
    .sf-hero .sf-btn { margin-top: 18px; width: auto; display: inline-flex; padding: 13px 22px; }
    .sf-actions { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }

    /* ── Kartu sertifikat ── */
    .sf-cert { position: relative; text-align: center; padding: 26px 20px; }
    .sf-cert::before {
        content: ''; position: absolute; inset: 8px; border-radius: 14px; pointer-events: none;
        border: 1.5px solid #c9d8ef;
    }
    .sf-cert::after {
        content: ''; position: absolute; inset: 13px; border-radius: 10px; pointer-events: none;
        border: 1px dashed #dde7f6;
    }
    .sf-cert-ico {
        width: 46px; height: 46px; border-radius: 50%; margin: 0 auto 10px;
        display: flex; align-items: center; justify-content: center; font-size: 24px;
        background: #e8f0ff; color: var(--sf-blue);
    }
    .sf-cert-kicker { font-size: 10px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: var(--sf-blue); }
    .sf-cert-sub { font-size: 11px; color: var(--sf-muted); margin-top: 12px; }
    .sf-cert-name { font-size: 20px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--sf-ink); margin-top: 4px; line-height: 1.2; word-break: break-word; }
    .sf-cert-line { width: 56px; height: 3px; border-radius: 3px; background: linear-gradient(90deg, #2f7cf0, var(--sf-blue)); margin: 14px auto; }
    .sf-cert-no { font-size: 11px; color: var(--sf-muted); font-weight: 600; }
    .sf-cert-note {
        display: inline-flex; align-items: center; gap: 5px; margin-top: 14px; padding: 5px 12px; border-radius: 999px;
        background: var(--sf-soft); border: 1px solid var(--sf-line); font-size: 10.5px; font-weight: 700; color: #4a5a72;
    }
    .sf-cert-note i { color: #198754; font-size: 14px; }

    /* ── Rincian ── */
    .sf-head { font-size: 14px; font-weight: 800; color: var(--sf-ink); display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .sf-head i { color: var(--sf-blue); font-size: 19px; }
    .sf-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-top: 1px dashed var(--sf-line); }
    .sf-row:first-of-type { border-top: 0; padding-top: 2px; }
    .sf-row-ico { width: 40px; height: 40px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .sf-row-label { font-size: 10px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--sf-muted); }
    .sf-row-value { font-size: 13px; font-weight: 700; color: var(--sf-ink); margin-top: 1px; word-break: break-word; }

    /* ── Linimasa ── */
    .sf-tl { margin-top: 4px; }
    .sf-step { display: flex; gap: 12px; position: relative; padding-bottom: 18px; }
    .sf-step:last-child { padding-bottom: 0; }
    .sf-step::before { content: ''; position: absolute; left: 13px; top: 28px; bottom: 0; width: 2px; background: var(--sf-line); }
    .sf-step:last-child::before { display: none; }
    .sf-step.done::before { background: #198754; }
    .sf-dot {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; z-index: 1;
        display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff;
    }
    .sf-step.done .sf-dot { background: #198754; }
    .sf-step.current .sf-dot { background: var(--sf-blue); animation: sfPulse 1.8s infinite; }
    .sf-step.rejected .sf-dot { background: #dc3545; }
    .sf-step.pending .sf-dot { background: #fff; border: 2px solid #cfd8e6; color: #9aa8bb; }
    .sf-step-body { flex: 1; min-width: 0; padding-top: 3px; }
    .sf-step-title { font-size: 13px; font-weight: 700; color: var(--sf-ink); }
    .sf-step.current .sf-step-title { color: var(--sf-blue); }
    .sf-step.pending .sf-step-title { color: var(--sf-muted); }
    .sf-step-desc { font-size: 11.5px; color: var(--sf-muted); margin-top: 2px; }

    /* ── Validasi otentikasi ── */
    .sf-val-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .sf-val-ico {
        width: 42px; height: 42px; border-radius: 14px; flex-shrink: 0; font-size: 22px;
        display: flex; align-items: center; justify-content: center; background: #e8f0ff; color: var(--sf-blue);
    }
    .sf-val-ttl { flex: 1; min-width: 0; }
    .sf-val-sub { font-size: 11px; color: var(--sf-muted); margin-top: 2px; line-height: 1.35; opacity: 0; visibility: hidden; transition: opacity .45s ease; }
    .sf-val-sub.show { opacity: 1; visibility: visible; }
    .sf-pill { font-size: 10px; font-weight: 800; padding: 5px 10px; border-radius: 999px; background: #d5f1f8; color: #055160; white-space: nowrap; }
    .sf-val-box {
        display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 16px;
        background: var(--sf-soft); border: 1px solid var(--sf-line); margin-bottom: 12px;
    }
    .sf-qr-frame {
        position: relative; padding: 14px; flex-shrink: 0; line-height: 0; cursor: pointer;
        background:
            linear-gradient(var(--sf-blue), var(--sf-blue)) top left / 20px 3px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) top left / 3px 20px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) top right / 20px 3px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) top right / 3px 20px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) bottom left / 20px 3px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) bottom left / 3px 20px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) bottom right / 20px 3px no-repeat,
            linear-gradient(var(--sf-blue), var(--sf-blue)) bottom right / 3px 20px no-repeat;
    }
    #sfQr { background: #fff; padding: 6px; border-radius: 8px; min-width: 120px; min-height: 120px; line-height: 1.3; font-size: 11px; color: var(--sf-muted); text-align: center; transition: opacity .3s ease; }
    #sfQr img, #sfQr canvas { width: 108px; height: 108px; display: block; }

    /* Garis pemindai */
    .sf-scanline {
        position: absolute; left: 14px; right: 14px; top: 14px; height: 3px; border-radius: 3px;
        background: linear-gradient(90deg, transparent, #2f7cf0, transparent);
        box-shadow: 0 0 14px 4px rgba(47, 124, 240, .55);
        opacity: 0; pointer-events: none; z-index: 2;
    }
    .sf-val-box.scanning .sf-scanline { opacity: 1; animation: sfScan 1.1s ease-in-out infinite alternate; }
    .sf-val-box.scanning #sfQr { opacity: .85; }

    /* Teks: "memindai" dulu, lalu hasil */
    .sf-val-info { flex: 1; min-width: 0; position: relative; min-height: 90px; display: flex; flex-direction: column; justify-content: center; }
    .sf-val-result { opacity: 0; visibility: hidden; transform: translateY(8px); transition: opacity .45s ease, transform .45s ease, visibility 0s linear .45s; }
    .sf-val-box.done .sf-val-result { opacity: 1; visibility: visible; transform: none; transition-delay: 0s; }

    .sf-val-label { font-size: 10px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--sf-blue); display: flex; align-items: center; gap: 5px; }
    .sf-val-text { font-size: 12px; color: #3d4c63; margin-top: 6px; line-height: 1.5; }
    .sf-kv {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 12px 14px; border-radius: 14px; background: var(--sf-soft); border: 1px solid var(--sf-line); margin-top: 8px;
    }
    .sf-kv-l { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #4a5a72; font-weight: 600; }
    .sf-kv-l i { font-size: 18px; color: var(--sf-blue); }
    .sf-kv-v { font-size: 12px; font-weight: 800; color: var(--sf-ink); text-align: right; display: flex; align-items: center; gap: 6px; word-break: break-all; }
    .sf-copy { border: 0; background: transparent; color: var(--sf-blue); font-size: 17px; cursor: pointer; padding: 2px; display: inline-flex; }

    @@keyframes sfPulse {
        0% { box-shadow: 0 0 0 0 rgba(11, 79, 179, .45); }
        70% { box-shadow: 0 0 0 10px rgba(11, 79, 179, 0); }
        100% { box-shadow: 0 0 0 0 rgba(11, 79, 179, 0); }
    }
    @@keyframes sfScan {
        from { top: 14px; }
        to   { top: calc(100% - 17px); }
    }

    @@media (prefers-reduced-motion: reduce) {
        .sf-val-box.scanning .sf-scanline { animation: none; }
    }

    /* ── Responsif ── */
    @@media (max-width: 360px) {
        .sf-card { padding: 14px 12px; }
        .sf-hero { padding: 24px 14px 18px; }
        .sf-hero-title { font-size: 16px; }
        .sf-cert-name { font-size: 17px; }
    }
    @@media (min-width: 992px) {
        .sf-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        .sf-grid.sf-single { display: block; }
    }
    @@media (min-width: 1200px) {
        .sf-grid { grid-template-columns: minmax(0, 6fr) minmax(0, 5fr); gap: 20px; }
    }
</style>

@if ($mode === 'file')
<!-- ═══ Sertifikat tersedia (digital / hasil upload) ═══ -->
<div class="sf-grid w-100">
    <div class="sf-col">
        <div class="sf-card sf-hero" style="--tint: {{ $t[0] }}; --fg: {{ $t[1] }};">
            <div class="sf-hero-ico"><i class='bx bxs-badge-check'></i></div>
            <span class="sf-badge">{{ $cfg['badge'] }}</span>
            <div class="sf-hero-title">{{ $cfg['title'] }}</div>
            <div class="sf-hero-text">{{ $cfg['text'] }}</div>
        </div>

        <div class="sf-card sf-cert">
            <div class="sf-cert-ico"><i class='bx bx-award'></i></div>
            <div class="sf-cert-kicker">Sertifikat Magang</div>
            <div class="sf-cert-sub">Diberikan kepada</div>
            <div class="sf-cert-name">{{ $peserta->nama ?? 'Peserta Magang' }}</div>
            <div class="sf-cert-line"></div>
            @if (! empty($sertifikat->nomor_surat))
                <div class="sf-cert-no">No. {{ $sertifikat->nomor_surat }}</div>
            @endif
            <span class="sf-cert-note"><i class='bx bx-check-shield'></i> {{ $cfg['note'] }}</span>
        </div>
    </div>

    <div class="sf-col">
        @if ($is_digital)
        <!-- Validasi Otentikasi -->
        <div class="sf-card">
            <div class="sf-val-head">
                <div class="sf-val-ico"><i class='bx bx-qr-scan'></i></div>
                <div class="sf-val-ttl">
                    <div class="sf-head" style="margin:0;">Validasi Otentikasi</div>
                    <div class="sf-val-sub">Pindai untuk membuka sertifikat digital</div>
                </div>
            </div>

            <div class="sf-val-box" id="sfBox">
                <div class="sf-qr-frame" id="sfFrame" title="Ketuk untuk pindai ulang">
                    <div id="sfQr" data-url="{{ $qr_url }}"></div>
                    <div class="sf-scanline"></div>
                </div>
                <div class="sf-val-info">
                    <div class="sf-val-result">
                        <div class="sf-val-label"><i class='bx bx-link-alt'></i> Tautan Dokumen</div>
                        <div class="sf-val-text">Arahkan kamera smartphone ke kode QR untuk membuka dokumen sertifikat digital ini.</div>
                    </div>
                </div>
            </div>

            <div class="sf-kv">
                <div class="sf-kv-l"><i class='bx bx-calendar'></i> Tanggal Diterbitkan</div>
                <div class="sf-kv-v">{{ $cfg['rows'][1][4] }}</div>
            </div>
            @if (! empty($sertifikat->nomor_surat))
            <div class="sf-kv">
                <div class="sf-kv-l"><i class='bx bx-hash'></i> Nomor Surat</div>
                <div class="sf-kv-v">{{ $sertifikat->nomor_surat }}</div>
            </div>
            @endif
        </div>
        @else
        <div class="sf-card">
            <div class="sf-head"><i class='bx bx-info-circle'></i> Detail Sertifikat</div>
            @foreach ($cfg['rows'] as $row)
            <div class="sf-row">
                <div class="sf-row-ico" style="background:{{ $row[1] }}; color:{{ $row[2] }};"><i class='bx {{ $row[0] }}'></i></div>
                <div>
                    <div class="sf-row-label">{{ $row[3] }}</div>
                    <div class="sf-row-value">{{ $row[4] }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="sf-actions">
            <a href="/uploads/sertifikat/{{ $cfg['file'] }}" target="_blank" class="sf-btn primary">
                <i class='bx bx-show'></i> {{ $cfg['view_label'] }}
            </a>
            <a href="/uploads/sertifikat/{{ $cfg['file'] }}" download="{{ 'Sertifikat_' . ($peserta->nama ?? 'Magang') }}" class="sf-btn outline">
                <i class='bx bx-download'></i> {{ $cfg['dl_label'] }}
            </a>
        </div>
    </div>
</div>

@else
<!-- ═══ Status proses sertifikat ═══ -->
<div class="sf-grid w-100 {{ empty($cfg['steps']) ? 'sf-single' : '' }}">
    <div class="sf-col">
        <div class="sf-card sf-hero" style="--tint: {{ $t[0] }}; --fg: {{ $t[1] }};">
            <div class="sf-hero-ico"><i class='bx {{ $cfg['icon'] }}'></i></div>
            <span class="sf-badge">{{ $cfg['badge'] }}</span>
            <div class="sf-hero-title">{{ $cfg['title'] }}</div>
            <div class="sf-hero-text">{{ $cfg['text'] }}</div>
            @if (! empty($cfg['action_label']))
            <a href="{{ route('peserta.laporan') }}" class="sf-btn primary">
                <i class='bx {{ $cfg['action_icon'] }}'></i> {{ $cfg['action_label'] }}
            </a>
            @endif
        </div>
    </div>

    @if (! empty($cfg['steps']))
    <div class="sf-col">
        <div class="sf-card">
            <div class="sf-head"><i class='bx bx-git-merge'></i> Progress</div>
            {{ $timeline($cfg['steps']) }}
        </div>
    </div>
    @endif
</div>
@endif
@endsection

@if ($is_digital)
@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// QR code dibuat di browser, lalu "dipindai" dulu sebelum deskripsi muncul
(function () {
    const el  = document.getElementById('sfQr');
    const box = document.getElementById('sfBox');
    const sub = document.querySelector('.sf-val-sub');
    if (!el || !box) return;

    function sfScan() {
        clearTimeout(box._t);
        box.classList.remove('done');
        box.classList.add('scanning');
        if (sub) sub.classList.remove('show');
        box._t = setTimeout(function () {
            box.classList.remove('scanning');
            box.classList.add('done');
            if (sub) sub.classList.add('show');
        }, 2400); // lama pemindaian (ms)
    }

    if (typeof QRCode === 'undefined') {
        el.textContent = 'QR tidak dapat dimuat. Periksa koneksi internet.';
        return;
    }

    new QRCode(el, {
        text: el.dataset.url,
        width: 216,
        height: 216,
        colorDark: '#16233b',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });

    // Pindai otomatis saat halaman dibuka (deskripsi muncul setelah selesai)
    sfScan();
    document.getElementById('sfFrame').addEventListener('click', sfScan); // ketuk lagi = pindai ulang
})();

</script>
@endsection
@endif
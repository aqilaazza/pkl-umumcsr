@extends('layouts.peserta')

@section('content')
@php
    $message = session('message');

    // Warna status: [latar badge, teks badge, aksen, ikon]
    $st_map = [
        'Menunggu'         => ['#fff3cd', '#856404', '#ffc107', 'bx-time'],
        'Menunggu Manager' => ['#cff4fc', '#055160', '#0dcaf0', 'bx-user-check'],
        'Disetujui'        => ['#d1e7dd', '#0f5132', '#198754', 'bx-check-circle'],
        'Ditolak'          => ['#f8d7da', '#842029', '#dc3545', 'bx-x-circle'],
    ];
    $st_default = ['#e2e3e5', '#41464b', '#6c757d', 'bx-circle'];

    // Linimasa verifikasi: hanya diturunkan dari status yang sudah ada
    if ($laporan_aktif) {
        $ls = $laporan_aktif->status;
        $s_sdm = $ls === 'Menunggu' ? 'current'
               : (in_array($ls, ['Menunggu Manager', 'Disetujui']) ? 'done'
               : ($ls === 'Ditolak' ? 'rejected' : 'pending'));
        $s_mgr = $ls === 'Menunggu Manager' ? 'current' : ($ls === 'Disetujui' ? 'done' : 'pending');

        // true = sertifikat sudah ada, false = masih diproses, null = belum diketahui
        // ($sertifikat_ada opsional: kalau controller belum mengirimnya, langkah ini tampil netral "Cek")
        $sert_ada = $sertifikat_ada ?? null;
        $s_ser = $ls !== 'Disetujui' ? 'pending'
               : ($sert_ada === true ? 'done' : ($sert_ada === false ? 'current' : 'ready'));

        $fase  = $ls === 'Disetujui' ? 4 : ($ls === 'Menunggu Manager' ? 3 : 2);

        $steps = [
            [
                'title' => 'Laporan Diunggah',
                'desc'  => 'Dokumen PDF diterima',
                'state' => 'done',
                'tag'   => date('d/m/Y H:i', strtotime($laporan_aktif->tgl_upload)),
            ],
            [
                'title' => 'Review SDM / HRD',
                'desc'  => ['current' => 'Sedang diperiksa admin SDM', 'done' => 'Laporan disetujui SDM', 'rejected' => 'Perlu dikoreksi, lalu unggah ulang', 'pending' => 'Belum dimulai'][$s_sdm],
                'state' => $s_sdm,
                'tag'   => ['current' => 'Proses', 'done' => 'Selesai', 'rejected' => 'Koreksi', 'pending' => ''][$s_sdm],
            ],
            [
                'title' => 'Approval Manager Unit',
                'desc'  => ['current' => 'Menunggu persetujuan Manager', 'done' => 'Disetujui Manager', 'pending' => 'Belum dimulai'][$s_mgr],
                'state' => $s_mgr,
                'tag'   => ['current' => 'Proses', 'done' => 'Selesai', 'pending' => ''][$s_mgr],
            ],
            [
                'title' => 'Terbit Sertifikat Digital',
                'desc'  => ['done' => 'Sertifikat sudah terbit, buka di menu Sertifikat', 'current' => 'Sertifikat sedang diproses petugas', 'ready' => 'Cek status dan unduh di menu Sertifikat', 'pending' => 'Belum dimulai'][$s_ser],
                'state' => $s_ser,
                'tag'   => ['done' => 'Selesai', 'current' => 'Proses', 'ready' => 'Cek', 'pending' => ''][$s_ser],
            ],
        ];
    }
@endphp

<!-- Toast dari server -->
@if ($message)
    @php
        $parts = explode(':', $message, 2);
        $mtype = $parts[0]; $mmsg = $parts[1] ?? '';
    @endphp
<script>document.addEventListener('DOMContentLoaded', () => showToast('{{ addslashes($mmsg) }}', '{{ $mtype }}'));</script>
@endif

<style>
    :root {
        --lp-blue: #0b4fb3;
        --lp-ink: #16233b;
        --lp-muted: #6b7a90;
        --lp-line: #e6ecf5;
        --lp-soft: #f4f7fc;
    }

    .lp-card {
        background: #fff; border-radius: 20px; padding: 16px; margin-bottom: 12px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
        border: 1px solid rgba(230, 236, 245, .9);
    }
    .lp-eyebrow {
        font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
        color: var(--lp-muted); display: flex; align-items: center; gap: 6px;
    }
    .lp-eyebrow i { font-size: 14px; color: var(--lp-blue); }
    .lp-pill {
        font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 999px;
        background: #e8f0ff; color: var(--lp-blue); white-space: nowrap;
    }
    .lp-badge {
        font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 4px;
    }

    /* ── Tabs ── */
    .lp-tabs {
        display: flex; gap: 4px; padding: 5px; margin-bottom: 14px;
        background: #fff; border-radius: 18px; border: 1px solid var(--lp-line);
        box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .lp-tab {
        flex: 1; border: 0; background: transparent; cursor: pointer; font-family: inherit;
        padding: 10px 6px; border-radius: 14px; font-size: 13px; font-weight: 700; color: var(--lp-muted);
        transition: background .2s ease, color .2s ease;
    }
    .lp-tab.active {
        background: linear-gradient(135deg, #2f7cf0, var(--lp-blue)); color: #fff;
        box-shadow: 0 6px 14px rgba(11, 79, 179, .30);
    }

    /* ── Grid halaman ── */
    .lp-grid { display: block; }
    .lp-col { min-width: 0; }

    /* ── Kartu status ── */
    .lp-status { border-left: 5px solid var(--ac, #6c757d); }
    .lp-status-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .lp-file {
        display: flex; align-items: center; gap: 12px; margin-top: 12px;
        padding: 10px 12px; border-radius: 14px; background: var(--lp-soft); border: 1px solid var(--lp-line);
    }
    .lp-file-ico {
        width: 40px; height: 40px; border-radius: 12px; background: #fde0e0; color: #dc3545;
        display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
    }
    .lp-file-body { flex: 1; min-width: 0; }
    .lp-file-name { font-size: 12.5px; font-weight: 700; color: var(--lp-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lp-file-date { font-size: 11px; color: var(--lp-muted); margin-top: 2px; }
    .lp-eye {
        width: 38px; height: 38px; border-radius: 12px; background: #e8f0ff; color: var(--lp-blue);
        display: flex; align-items: center; justify-content: center; font-size: 20px; text-decoration: none; flex-shrink: 0;
    }
    .lp-reject {
        margin-top: 12px; padding: 10px 12px; border-radius: 12px; font-size: 12px;
        background: #f8d7da; color: #842029; border: 1px solid #f5c2c7;
    }
    .lp-reject b { display: block; margin-bottom: 2px; }

    /* ── Linimasa ── */
    .lp-tl-head { display: flex; align-items: center; justify-content: space-between; }
    .lp-tl-title { font-size: 15px; font-weight: 800; color: var(--lp-ink); display: flex; align-items: center; gap: 8px; }
    .lp-tl-title i { color: var(--lp-blue); font-size: 20px; }
    .lp-tl { margin-top: 16px; }
    .lp-step { display: flex; gap: 12px; position: relative; padding-bottom: 18px; }
    .lp-step:last-child { padding-bottom: 0; }
    .lp-step::before {
        content: ''; position: absolute; left: 13px; top: 28px; bottom: 0; width: 2px; background: var(--lp-line);
    }
    .lp-step:last-child::before { display: none; }
    .lp-step.done::before { background: #198754; }
    .lp-dot {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; z-index: 1;
        display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff;
    }
    .lp-step.done .lp-dot { background: #198754; }
    .lp-step.current .lp-dot { background: var(--lp-blue); animation: lpPulse 1.8s infinite; }
    .lp-step.rejected .lp-dot { background: #dc3545; }
    .lp-step.ready .lp-dot { background: var(--lp-blue); }
    .lp-step.pending .lp-dot { background: #fff; border: 2px solid #cfd8e6; color: #9aa8bb; }
    .lp-step-body { flex: 1; min-width: 0; }
    .lp-step-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
    .lp-step-title { font-size: 13px; font-weight: 700; color: var(--lp-ink); }
    .lp-step.current .lp-step-title { color: var(--lp-blue); }
    .lp-step.ready .lp-step-title { color: var(--lp-blue); }
    .lp-step.pending .lp-step-title { color: var(--lp-muted); }
    .lp-step-desc { font-size: 11.5px; color: var(--lp-muted); margin-top: 2px; }
    .lp-step-tag { font-size: 10px; font-weight: 700; white-space: nowrap; color: var(--lp-muted); }
    .lp-step.done .lp-step-tag { color: #198754; }
    .lp-step.current .lp-step-tag { background: #e8f0ff; color: var(--lp-blue); padding: 3px 9px; border-radius: 999px; }
    .lp-step.ready .lp-step-tag { background: #e8f0ff; color: var(--lp-blue); padding: 3px 9px; border-radius: 999px; }
    .lp-step.rejected .lp-step-tag { background: #f8d7da; color: #842029; padding: 3px 9px; border-radius: 999px; }

    /* ── Form upload ── */
    .lp-up-head { margin-bottom: 14px; }
    .lp-up-head h3 { font-size: 16px; font-weight: 800; color: var(--lp-ink); margin: 0; }
    .lp-up-head p { font-size: 11.5px; color: var(--lp-muted); margin: 3px 0 0; }
    .lp-zone {
        display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
        border: 2px dashed var(--lp-blue); border-radius: 18px; padding: 30px 16px;
        background: var(--lp-soft); cursor: pointer; transition: all .25s ease;
    }
    .lp-zone:hover { background: #eaf1fd; }
    .lp-zone-ico {
        width: 52px; height: 52px; border-radius: 16px; background: #fff; color: var(--lp-blue);
        display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(11, 79, 179, .15);
    }
    .lp-zone .upload-text { font-size: 14px; font-weight: 800; color: var(--lp-ink); }
    .lp-zone .upload-hint { font-size: 11px; color: var(--lp-muted); margin-top: 3px; }
    .lp-preview {
        display: none; margin-top: 12px; padding: 12px; background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 14px;
    }
    .lp-preview-row { display: flex; align-items: center; gap: 10px; }
    .lp-preview-row i { font-size: 22px; color: #2e7d32; }
    #fpName { font-size: 12px; font-weight: 700; color: #1b5e20; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #fpSize { font-size: 11px; color: #2e7d32; }
    .lp-info-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
    .lp-info {
        display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 14px;
        background: var(--lp-soft); border: 1px solid var(--lp-line);
    }
    .lp-info i { font-size: 20px; color: var(--lp-blue); }
    .lp-info-l { font-size: 9px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--lp-muted); }
    .lp-info-v { font-size: 12.5px; font-weight: 800; color: var(--lp-ink); }
    .lp-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        margin-top: 14px; border: 0; border-radius: 16px; padding: 15px 16px; cursor: pointer;
        font-family: inherit; font-size: 14px; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, #2f7cf0, var(--lp-blue)); box-shadow: 0 8px 18px rgba(11, 79, 179, .30);
        transition: transform .15s ease;
    }
    .lp-btn:active { transform: scale(.98); }
    .lp-btn:disabled { background: #c9d1dc; box-shadow: none; cursor: not-allowed; }
    .lp-btn i { font-size: 20px; }
    .lp-tip {
        display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 16px;
        background: #e8f0ff; color: #28477a; font-size: 11.5px; line-height: 1.4; margin-bottom: 12px;
    }
    .lp-tip i { font-size: 18px; color: var(--lp-blue); margin-top: 1px; }

    /* ── Kartu terblokir ── */
    .lp-blocked {
        text-align: center; padding: 28px 20px 22px;
        background: linear-gradient(180deg, var(--tint, #e8f0ff) 0%, #fff 62%);
    }
    .lp-blocked-ico {
        width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 14px;
        display: flex; align-items: center; justify-content: center; font-size: 32px;
        background: #fff; color: var(--fg, var(--lp-blue));
        box-shadow: 0 0 0 6px rgba(255, 255, 255, .7), 0 10px 22px rgba(24, 60, 120, .12);
    }
    .lp-blocked-title { font-size: 16px; font-weight: 800; color: var(--lp-ink); margin-top: 10px; }
    .lp-blocked-text { font-size: 12.5px; color: var(--lp-muted); margin: 6px auto 0; line-height: 1.55; max-width: 340px; }
    .lp-blocked .lp-btn { margin-top: 18px; text-decoration: none; }

    /* ── Riwayat ── */
    .lp-riwayat { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 10px; }
    .lp-rw-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 2px 2px 12px; }
    .lp-chips { display: flex; gap: 8px; overflow-x: auto; padding: 2px 2px 14px; scrollbar-width: none; }
    .lp-chips::-webkit-scrollbar { display: none; }
    .lp-chip {
        flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
        padding: 8px 13px; border-radius: 999px; border: 1px solid var(--lp-line); background: #fff;
        font-family: inherit; font-size: 12px; font-weight: 700; color: var(--lp-muted);
        box-shadow: 0 4px 12px rgba(24, 60, 120, .05); transition: all .2s ease;
    }
    .lp-chip b { font-size: 10.5px; background: var(--lp-soft); color: var(--lp-ink); padding: 1px 7px; border-radius: 999px; }
    .lp-chip-dot { width: 8px; height: 8px; border-radius: 50%; }
    .lp-chip.active {
        background: linear-gradient(135deg, #2f7cf0, var(--lp-blue)); border-color: transparent; color: #fff;
        box-shadow: 0 6px 14px rgba(11, 79, 179, .28);
    }
    .lp-chip.active b { background: rgba(255, 255, 255, .22); color: #fff; }

    .lp-group {
        grid-column: 1 / -1; margin-top: 6px; font-size: 11px; font-weight: 800; letter-spacing: .6px;
        text-transform: uppercase; color: var(--lp-muted); display: flex; align-items: center; gap: 10px;
    }
    .lp-group:first-child { margin-top: 0; }
    .lp-group::after { content: ''; flex: 1; height: 1px; background: var(--lp-line); }

    .lp-item {
        display: flex; flex-direction: column; gap: 10px; background: #fff; border-radius: 18px; padding: 14px;
        border-left: 5px solid var(--ac, #6c757d);
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07); border-top: 1px solid rgba(230, 236, 245, .9);
        border-right: 1px solid rgba(230, 236, 245, .9); border-bottom: 1px solid rgba(230, 236, 245, .9);
    }
    .lp-item-main { display: flex; align-items: center; gap: 12px; }
    .lp-item-ico { width: 42px; height: 42px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .lp-item-body { flex: 1; min-width: 0; }
    .lp-item-name { font-size: 13px; font-weight: 700; color: var(--lp-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lp-item-sub { font-size: 11px; color: var(--lp-muted); margin-top: 2px; }
    .lp-item-eye {
        width: 36px; height: 36px; border-radius: 12px; background: #e8f0ff; color: var(--lp-blue);
        display: flex; align-items: center; justify-content: center; font-size: 19px; text-decoration: none; flex-shrink: 0;
    }
    .lp-item-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        padding-top: 10px; border-top: 1px dashed var(--lp-line);
    }
    .lp-item-time { font-size: 11px; color: var(--lp-muted); display: inline-flex; align-items: center; gap: 4px; }
    .lp-item-time i { font-size: 14px; }
    .lp-item-note {
        padding: 9px 11px; border-radius: 12px; font-size: 11.5px; line-height: 1.4;
        background: #f8d7da; color: #842029; border: 1px solid #f5c2c7;
    }
    .lp-item-note b { display: block; margin-bottom: 1px; }
    .lp-empty { text-align: center; padding: 36px 16px; color: var(--lp-muted); background: #fff; border-radius: 20px; border: 1px solid var(--lp-line); }
    .lp-empty i { font-size: 40px; color: #b8c4d6; }
    .lp-empty div { font-size: 13px; margin-top: 6px; font-weight: 600; }

    @@keyframes lpPulse {
        0% { box-shadow: 0 0 0 0 rgba(11, 79, 179, .45); }
        70% { box-shadow: 0 0 0 10px rgba(11, 79, 179, 0); }
        100% { box-shadow: 0 0 0 0 rgba(11, 79, 179, 0); }
    }

    /* ── Responsif ── */
    @@media (max-width: 360px) {
        .lp-card { padding: 14px 12px; }
        .lp-tab { font-size: 12px; }
        .lp-zone { padding: 24px 12px; }
        .lp-info-row { grid-template-columns: 1fr; }
        .lp-riwayat { grid-template-columns: 1fr; }
    }
    @@media (min-width: 768px) {
        .lp-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
        .lp-grid.lp-single { display: block; max-width: 560px; margin: 0 auto; }
        .lp-tabs { max-width: 520px; }
    }
    @@media (min-width: 1200px) {
        .lp-grid { grid-template-columns: minmax(0, 6fr) minmax(0, 5fr); gap: 20px; }
    }
</style>

<!-- ═══ Tabs ═══ -->
<div class="lp-tabs">
    <button class="lp-tab m-tab {{ $init_tab === 'upload' ? 'active' : '' }}" onclick="showTab('upload')">Upload</button>
    <button class="lp-tab m-tab {{ $init_tab === 'riwayat' ? 'active' : '' }}" onclick="showTab('riwayat')">Riwayat</button>
    <button class="lp-tab m-tab" onclick="location.href='{{ route('peserta.laporan.referensi') }}'">Referensi</button>
</div>

<!-- ═══ Tab Upload ═══ -->
<div id="tab-upload" style="display: {{ $init_tab === 'upload' ? 'block' : 'none' }};">
<div class="lp-grid {{ $laporan_aktif ? '' : 'lp-single' }}">

    @if ($laporan_aktif)
    @php $c = $st_map[$laporan_aktif->status] ?? $st_default; @endphp
    <div class="lp-col">
        <!-- Status Card -->
        <div class="lp-card lp-status" style="--ac: {{ $c[2] }};">
            <div class="lp-status-head">
                <div class="lp-eyebrow"><i class='bx bx-radio-circle-marked'></i> Status Laporan Terakhir</div>
                <span class="lp-badge" style="background:{{ $c[0] }}; color:{{ $c[1] }};">
                    <i class='bx {{ $c[3] }}'></i> {{ $laporan_aktif->status }}
                </span>
            </div>
            <div class="lp-file">
                <div class="lp-file-ico"><i class='bx bxs-file-pdf'></i></div>
                <div class="lp-file-body">
                    <div class="lp-file-name">{{ $laporan_aktif->nama_file }}</div>
                    <div class="lp-file-date">{{ date('d/m/Y H:i', strtotime($laporan_aktif->tgl_upload)) }}</div>
                </div>
                <a href="/uploads/laporan/{{ $laporan_aktif->file_laporan }}" target="_blank" class="lp-eye" title="Lihat file">
                    <i class='bx bx-show-alt'></i>
                </a>
            </div>
            @if ($laporan_aktif->status === 'Ditolak' && $laporan_aktif->keterangan_tolak)
            <div class="lp-reject">
                <b>Catatan Koreksi:</b>
                {{ $laporan_aktif->keterangan_tolak }}
            </div>
            @endif
        </div>

        <!-- Progress Verifikasi -->
        <div class="lp-card">
            <div class="lp-tl-head">
                <div class="lp-tl-title"><i class='bx bx-list-check'></i> Progress Verifikasi</div>
                <span class="lp-pill">Fase {{ $fase }} / 4</span>
            </div>
            <div class="lp-tl">
                @foreach ($steps as $s)
                <div class="lp-step {{ $s['state'] }}">
                    <div class="lp-dot">
                        @if ($s['state'] === 'done')
                            <i class='bx bx-check'></i>
                        @elseif ($s['state'] === 'rejected')
                            <i class='bx bx-x'></i>
                        @elseif ($s['state'] === 'ready')
                            <i class='bx bx-award'></i>
                        @elseif ($s['state'] === 'current')
                            <i class='bx bx-loader-alt bx-spin'></i>
                        @else
                            <i class='bx bx-time-five'></i>
                        @endif
                    </div>
                    <div class="lp-step-body">
                        <div class="lp-step-row">
                            <div class="lp-step-title">{{ $s['title'] }}</div>
                            @if ($s['tag'])
                                <div class="lp-step-tag">{{ $s['tag'] }}</div>
                            @endif
                        </div>
                        <div class="lp-step-desc">{{ $s['desc'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="lp-col">
    @if ($boleh_upload)
        <!-- Upload Form Card -->
        <div class="lp-card" style="padding:20px;">
            <div class="lp-up-head">
                <h3>{{ $laporan_aktif && $laporan_aktif->status === 'Ditolak' ? 'Upload Laporan Koreksi' : 'Unggah Laporan Magang' }}</h3>
                <p>Laporan akhir kegiatan magang PKL</p>
            </div>

            <form action="{{ route('peserta.laporan.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label class="lp-zone" id="uploadZone" for="fileLaporan">
                    <div class="lp-zone-ico"><i class='bx bx-upload'></i></div>
                    <div class="upload-text" id="uploadText">Pilih file PDF laporan</div>
                    <div class="upload-hint">Sentuh untuk menelusuri berkas • Maksimal {{ $max_ukuran_mb }} MB</div>
                </label>
                <input type="file" name="file_laporan" id="fileLaporan" accept=".pdf,application/pdf" required style="display:none;">

                <!-- Preview File Terpilih -->
                <div id="filePreview" class="lp-preview">
                    <div class="lp-preview-row">
                        <i class='bx bxs-check-circle'></i>
                        <div style="flex:1; min-width:0;">
                            <div id="fpName"></div>
                            <div id="fpSize"></div>
                        </div>
                    </div>
                </div>

                <div class="lp-info-row">
                    <div class="lp-info">
                        <i class='bx bxs-file-pdf'></i>
                        <div>
                            <div class="lp-info-l">Format</div>
                            <div class="lp-info-v">Wajib PDF</div>
                        </div>
                    </div>
                    <div class="lp-info">
                        <i class='bx bx-hdd'></i>
                        <div>
                            <div class="lp-info-l">Ukuran Maks</div>
                            <div class="lp-info-v">{{ $max_ukuran_mb }} Megabyte</div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit_upload" class="lp-btn" id="btnUpload">
                    <i class='bx bx-cloud-upload'></i> Mulai Upload Laporan
                </button>
            </form>
        </div>

        <div class="lp-tip">
            <i class='bx bx-info-circle'></i>
            <span>Setelah laporan disetujui, pantau proses penerbitan sertifikat digital di menu Sertifikat.</span>
        </div>
    @else
        <!-- Blocked Card -->
        @php
            $bc = $st_map[$laporan_aktif->status] ?? $st_default;
            if ($laporan_aktif->status === 'Menunggu') {
                $b_icon  = 'bx-time-five';
                $b_title = 'Laporan Menunggu Review SDM';
                $b_text  = 'Laporan Anda sedang diperiksa oleh admin SDM. Anda dapat mengunggah ulang jika laporan ditolak.';
                $b_link  = false;
            } elseif ($laporan_aktif->status === 'Menunggu Manager') {
                $b_icon  = 'bx-user-check';
                $b_title = 'Laporan Disetujui SDM';
                $b_text  = 'Laporan Anda sudah disetujui SDM dan sedang menunggu approval Manager. Pantau progresnya di menu Sertifikat.';
                $b_link  = true;
            } else {
                $b_icon  = 'bx-check-double';
                $b_title = 'Laporan Telah Disetujui';
                $b_text  = ($sertifikat_ada ?? null) === true
                    ? 'Selamat, laporan Anda disetujui dan sertifikat Anda sudah terbit. Buka menu Sertifikat untuk melihat dan mengunduhnya.'
                    : 'Selamat, laporan magang Anda telah disetujui. Pantau proses penerbitan sertifikat digital Anda di menu Sertifikat.';
                $b_link  = true;
            }
        @endphp
        <div class="lp-card lp-blocked" style="--tint: {{ $bc[0] }}; --fg: {{ $bc[1] }};">
            <div class="lp-blocked-ico"><i class='bx {{ $b_icon }}'></i></div>
            <span class="lp-badge" style="background:{{ $bc[0] }}; color:{{ $bc[1] }};">
                <i class='bx {{ $bc[3] }}'></i> {{ $laporan_aktif->status }}
            </span>
            <div class="lp-blocked-title">{{ $b_title }}</div>
            <div class="lp-blocked-text">{{ $b_text }}</div>
            @if ($b_link)
            <a href="{{ route('peserta.sertifikat') }}" class="lp-btn">
                <i class='bx bx-award'></i> Buka Menu Sertifikat
            </a>
            @endif
        </div>
    @endif
    </div>

</div>
</div>

<!-- ═══ Tab Riwayat ═══ -->
<div id="tab-riwayat" style="display: {{ $init_tab === 'riwayat' ? 'block' : 'none' }};">
    @php
        $bulan_lp = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $rw_total = collect($riwayat)->count();
        $rw_by_status = collect($riwayat)->groupBy('status')->map(fn($g) => $g->count());
        $has = false;
        $prev_ym = null;
    @endphp

    @if ($rw_total > 0)
    <!-- Ringkasan & filter status -->
    <div class="lp-rw-head">
        <div class="lp-tl-title"><i class='bx bx-history'></i> Riwayat Laporan</div>
        <span class="lp-pill" id="rwCount">{{ $rw_total }} laporan</span>
    </div>
    <div class="lp-chips">
        <button type="button" class="lp-chip active" data-filter="" onclick="filterRiwayatLaporan(this)">Semua <b>{{ $rw_total }}</b></button>
        @foreach ($rw_by_status as $sname => $scount)
            @php $cc = $st_map[$sname] ?? $st_default; @endphp
            <button type="button" class="lp-chip" data-filter="{{ $sname }}" onclick="filterRiwayatLaporan(this)">
                <span class="lp-chip-dot" style="background:{{ $cc[2] }};"></span>{{ $sname }} <b>{{ $scount }}</b>
            </button>
        @endforeach
    </div>
    @endif

    <div class="lp-riwayat" id="lpRiwayatList">
        @foreach ($riwayat as $r)
            @php
                $has = true;
                $rc = $st_map[$r->status] ?? $st_default;
                $sz = $r->ukuran_file > 1024*1024 ? round($r->ukuran_file/1024/1024,2).' MB' : round($r->ukuran_file/1024,1).' KB';
                $ym = date('Y-m', strtotime($r->tgl_upload));
            @endphp
        @if ($ym !== $prev_ym)
            @php $prev_ym = $ym; @endphp
            <div class="lp-group">{{ $bulan_lp[(int) substr($ym, 5, 2)] . ' ' . substr($ym, 0, 4) }}</div>
        @endif
        <div class="lp-item" data-status="{{ $r->status }}" style="--ac: {{ $rc[2] }};">
            <div class="lp-item-main">
                <div class="lp-item-ico" style="background:{{ $rc[0] }}; color:{{ $rc[1] }};">
                    <i class='bx bxs-file-pdf'></i>
                </div>
                <div class="lp-item-body">
                    <div class="lp-item-name">{{ $r->nama_file }}</div>
                    <div class="lp-item-sub">{{ $sz }}</div>
                </div>
                <a href="/uploads/laporan/{{ $r->file_laporan }}" target="_blank" class="lp-item-eye" title="Lihat file">
                    <i class='bx bx-show-alt'></i>
                </a>
            </div>
            <div class="lp-item-foot">
                <span class="lp-badge" style="background:{{ $rc[0] }}; color:{{ $rc[1] }};">
                    <i class='bx {{ $rc[3] }}'></i> {{ $r->status }}
                </span>
                <span class="lp-item-time"><i class='bx bx-time-five'></i> {{ date('d/m/Y H:i', strtotime($r->tgl_upload)) }}</span>
            </div>
            @if ($r->status === 'Ditolak' && ! empty($r->keterangan_tolak))
            <div class="lp-item-note">
                <b>Catatan Koreksi:</b>
                {{ $r->keterangan_tolak }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="lp-empty" id="rwKosong" style="display:none;">
        <i class='bx bx-filter-alt'></i>
        <div>Tidak ada laporan untuk filter ini</div>
    </div>
    @if (! $has)
    <div class="lp-empty">
        <i class='bx bx-file'></i>
        <div>Belum ada riwayat laporan</div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
const maxUploadMb = {{ $max_ukuran_mb }};
// Tab switching
function showTab(tab) {
    document.getElementById('tab-upload').style.display = tab === 'upload' ? 'block' : 'none';
    document.getElementById('tab-riwayat').style.display = tab === 'riwayat' ? 'block' : 'none';
    document.querySelectorAll('.lp-tab').forEach((t, i) => {
        t.classList.toggle('active', (i === 0 && tab === 'upload') || (i === 1 && tab === 'riwayat'));
    });
}

// Filter riwayat berdasarkan status
function filterRiwayatLaporan(btn) {
    const status = btn.dataset.filter;
    document.querySelectorAll('.lp-chip').forEach(c => c.classList.toggle('active', c === btn));

    const items = document.querySelectorAll('#lpRiwayatList .lp-item');
    let tampil = 0;
    items.forEach(el => {
        const ok = !status || el.dataset.status === status;
        el.style.display = ok ? '' : 'none';
        if (ok) tampil++;
    });

    // Sembunyikan judul bulan yang tidak punya data tampil
    document.querySelectorAll('#lpRiwayatList .lp-group').forEach(h => {
        let ada = false, n = h.nextElementSibling;
        while (n && !n.classList.contains('lp-group')) {
            if (n.classList.contains('lp-item') && n.style.display !== 'none') { ada = true; break; }
            n = n.nextElementSibling;
        }
        h.style.display = ada ? '' : 'none';
    });

    document.getElementById('rwCount').textContent = tampil + ' laporan';
    const kosong = document.getElementById('rwKosong');
    if (kosong) kosong.style.display = (items.length && !tampil) ? '' : 'none';
}

// File input
const fileInput = document.getElementById('fileLaporan');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const f = this.files[0];
        if (f) {
            const overLimit = f.size > maxUploadMb*1024*1024;
            document.getElementById('filePreview').style.display = 'block';
            document.getElementById('fpName').textContent = f.name;
            document.getElementById('fpSize').textContent = f.size > 1024*1024 ? (f.size/1024/1024).toFixed(2)+' MB' : (f.size/1024).toFixed(1)+' KB';
            document.getElementById('uploadText').textContent = overLimit ? 'File terlalu besar' : 'File dipilih ✓';
            document.getElementById('uploadZone').style.borderColor = overLimit ? '#dc3545' : 'var(--lp-blue)';
            document.getElementById('uploadZone').style.background = overLimit ? '#f8d7da' : '#e8f5e9';
            document.getElementById('btnUpload').disabled = overLimit;
            if (overLimit) {
                showToast('File yang kamu masukkan melebihi batas ' + maxUploadMb + ' MB. Silakan pilih file yang lebih kecil.', 'error', 4000);
            }
        }
    });
}
</script>
@endsection
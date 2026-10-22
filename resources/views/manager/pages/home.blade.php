@php
    use Illuminate\Support\Facades\DB;

    $tgl_indo = function ($t) {
        $b = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $p = explode('-', $t);
        return $p[2] . ' ' . $b[(int)$p[1]] . ' ' . $p[0];
    };

    $today = date('Y-m-d');

    $laporan_stat = DB::table('laporan_magang')
        ->selectRaw("COUNT(*) as total")
        ->selectRaw("SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu")
        ->selectRaw("SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui")
        ->selectRaw("SUM(manager_status='Ditolak' OR (sdm_status='Ditolak' AND manager_status='Menunggu')) as ditolak")
        ->selectRaw("SUM(sdm_status='Menunggu') as menunggu_sdm")
        ->first();

    $total_peserta    = (int) DB::table('peserta')->count();
    $peserta_aktif    = (int) DB::table('peserta')->where('status_magang', 'Aktif')->count();
    $peserta_menunggu = (int) DB::table('peserta')->where('status_magang', 'Menunggu')->count();
    $peserta_selesai  = (int) DB::table('peserta')->where('status_magang', 'Selesai')->count();
    $unit1_count      = (int) DB::table('peserta')->where('unit', 'Unit 1-2')->count();
    $unit9_count      = (int) DB::table('peserta')->where('unit', 'Unit 9')->count();

    $sertif_digital = (int) DB::table('sertifikat_magang')->where('status', 'Digital')->count();

    $absensi_hari_ini = DB::table('absensi_peserta')
        ->where('tanggal', $today)
        ->selectRaw("SUM(CASE WHEN status='Hadir' AND jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir")
        ->selectRaw("SUM(CASE WHEN status='Izin' THEN 1 ELSE 0 END) as izin")
        ->selectRaw("SUM(CASE WHEN status='Sakit' THEN 1 ELSE 0 END) as sakit")
        ->selectRaw("SUM(CASE WHEN status='Alpha' THEN 1 ELSE 0 END) as alpha")
        ->selectRaw("COUNT(*) as total_absen")
        ->first();
    $absen_hadir = (int) ($absensi_hari_ini->hadir ?? 0);
    $absen_izin  = (int) ($absensi_hari_ini->izin ?? 0);
    $absen_sakit = (int) ($absensi_hari_ini->sakit ?? 0);
    $absen_total = (int) ($absensi_hari_ini->total_absen ?? 0);
    $belum_absen = max(0, $peserta_aktif - $absen_total);

    $bidang_data = DB::table('bidang as b')
        ->leftJoin('peserta as p', 'b.id', '=', 'p.bidang_id')
        ->groupBy('b.id', 'b.bidang')
        ->orderByRaw('COUNT(p.id) DESC')
        ->get([DB::raw('b.bidang as bidang'), DB::raw('COUNT(p.id) as jumlah')]);

    $menunggu  = (int) ($laporan_stat->menunggu ?? 0);
    $disetujui = (int) ($laporan_stat->disetujui ?? 0);
    $ditolak   = (int) ($laporan_stat->ditolak ?? 0);
    $total     = (int) ($laporan_stat->total ?? 0);

    $instansi_raw = DB::table('peserta')
        ->selectRaw("TRIM(asal_sekolah) as instansi, status_peserta, status_magang, unit, COUNT(*) as jumlah")
        ->whereRaw("TRIM(asal_sekolah) <> ''")
        ->groupByRaw("TRIM(asal_sekolah), status_peserta, status_magang, unit")
        ->get();
@endphp

@php
    // Turunan untuk tampilan saja (dihitung dari variabel di atas, tanpa query baru)
    $mg_pt         = max(1, $total_peserta);
    $mg_pct_aktif  = round($peserta_aktif / $mg_pt * 100, 1);
    $mg_pct_tunggu = round($peserta_menunggu / $mg_pt * 100, 1);
    $mg_pct_selesai_p = round($peserta_selesai / $mg_pt * 100, 1);

    $mg_div       = max(1, $total);
    $mg_sdm       = (int) ($laporan_stat->menunggu_sdm ?? 0);
    $mg_review    = round((($disetujui + $ditolak) / $mg_div) * 100);
    // Palet untuk daftar peringkat: [warna, latar]
    $mg_pal = [['#0d6efd', '#e7f1ff'], ['#198754', '#e1f2e9'], ['#0aa2c0', '#d5f1f8'], ['#6f42c1', '#ece4f7'], ['#d63384', '#fbe3ee']];
    $mg_seg = [
        ['Disetujui',        $disetujui, '#198754'],
        ['Menunggu Manager', $menunggu,  '#ffc107'],
        ['Ditolak',          $ditolak,   '#dc3545'],
        ['Review SDM',       $mg_sdm,    '#9aa8bb'],
    ];
@endphp

<style>
    .mg { --mg-blue: #0d6efd; --mg-ink: #16233b; --mg-muted: #6b7a90; --mg-line: #e6ecf5; --mg-soft: #f4f7fc; color: var(--mg-ink); }

    /* ── Judul ── */
    .mg-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
    .mg-head-l { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .mg-head-ico {
        width: 46px; height: 46px; border-radius: 15px; flex-shrink: 0; font-size: 25px;
        display: flex; align-items: center; justify-content: center; background: #e7f1ff; color: var(--mg-blue);
    }
    .mg-title { font-size: 22px; font-weight: 800; line-height: 1.2; margin: 0; }
    .mg-sub { font-size: 12.5px; color: var(--mg-muted); margin: 3px 0 0; }
    .mg-link-btn {
        display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 16px; border-radius: 12px;
        background: var(--mg-blue); color: #fff; font-size: 13px; font-weight: 800; text-decoration: none;
        box-shadow: 0 6px 14px rgba(13, 110, 253, .25);
    }
    .mg-link-btn:hover { color: #fff; background: #0b5ed7; }
    .mg-link-btn i { font-size: 18px; }

    /* ── Banner approval ── */
    .mg-banner {
        display: flex; align-items: center; gap: 14px; flex-wrap: wrap; padding: 14px 16px; margin-bottom: 18px;
        border-radius: 18px; border: 1px solid #ffe08a; background: linear-gradient(90deg, #fff8e1, #fffdf5);
    }
    .mg-banner.ok { border-color: #bfe3cf; background: linear-gradient(90deg, #e8f6ee, #fbfefc); }
    .mg-banner-ico { width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0; font-size: 24px; display: flex; align-items: center; justify-content: center; background: #fff; color: #e0a800; box-shadow: 0 4px 12px rgba(0,0,0,.06); }
    .mg-banner.ok .mg-banner-ico { color: #198754; }
    .mg-banner-b { flex: 1 1 220px; min-width: 0; }
    .mg-banner-t { font-size: 14px; font-weight: 800; }
    .mg-banner-s { font-size: 12px; color: var(--mg-muted); margin-top: 2px; }

    /* ── Kartu umum ── */
    .mg-card { background: #fff; border-radius: 20px; border: 1px solid var(--mg-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06); height: 100%; padding: 18px; }
    .mg-card-h { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
    .mg-card-t { font-size: 14.5px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0; }
    .mg-card-t i { color: var(--mg-blue); font-size: 20px; }
    .mg-card-s { font-size: 11.5px; color: var(--mg-muted); margin: 3px 0 0; }
    .mg-pill { font-size: 11px; font-weight: 800; padding: 4px 11px; border-radius: 999px; background: var(--mg-soft); color: #4a5a72; border: 1px solid var(--mg-line); white-space: nowrap; }

    /* ── Kartu statistik ── */
    .mg-stat {
        position: relative; overflow: hidden; height: 100%; padding: 16px 16px 14px; border-radius: 20px; background: #fff;
        border: 1px solid var(--mg-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .mg-stat::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: var(--c); }
    .mg-stat-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .mg-stat-l { font-size: 12.5px; font-weight: 700; color: var(--mg-muted); }
    .mg-ico { width: 36px; height: 36px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; background: var(--bg); color: var(--c); flex-shrink: 0; }
    .mg-stat-n { font-size: 34px; font-weight: 800; line-height: 1.1; margin: 10px 0 8px; color: var(--c); }
    .mg-stat-f { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; font-size: 11.5px; color: var(--mg-muted); font-weight: 600; }
    .mg-chip { font-size: 10.5px; font-weight: 800; padding: 3px 9px; border-radius: 999px; background: var(--bg); color: var(--c); }

    /* ── Statistik approval ── */
    .mg-tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .mg-tile { padding: 14px 12px; border-radius: 16px; text-align: center; background: var(--bg); }
    .mg-tile-n { font-size: 26px; font-weight: 800; line-height: 1.1; color: var(--c); }
    .mg-tile-l { font-size: 11.5px; font-weight: 600; color: #4a5a72; margin-top: 4px; }
    .mg-seg { display: flex; height: 12px; border-radius: 999px; overflow: hidden; background: #edf1f7; margin-top: 18px; }
    .mg-seg > span { display: block; height: 100%; }
    .mg-legend { display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 12px; }
    .mg-legend span { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--mg-muted); font-weight: 600; }
    .mg-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
    .mg-legend b { color: var(--mg-ink); }
    .mg-prog-l { display: flex; justify-content: space-between; font-size: 12px; color: var(--mg-muted); font-weight: 600; margin: 18px 0 6px; }
    .mg-prog-l b { color: var(--mg-ink); }
    .mg-prog { height: 8px; border-radius: 999px; background: #edf1f7; overflow: hidden; }
    .mg-prog > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #4d94ff, var(--mg-blue)); }

    /* ── Sertifikasi ── */
    .mg-cert { display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 16px; background: var(--mg-soft); border: 1px solid var(--mg-line); }
    .mg-cert-ico { width: 52px; height: 52px; border-radius: 16px; flex-shrink: 0; font-size: 28px; display: flex; align-items: center; justify-content: center; background: #d5f1f8; color: #0aa2c0; }
    .mg-cert-n { font-size: 30px; font-weight: 800; line-height: 1.1; }
    .mg-cert-l { font-size: 12px; color: var(--mg-muted); font-weight: 600; margin-top: 2px; }
    .mg-flow-t { font-size: 11px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--mg-muted); margin: 18px 0 12px; }
    .mg-flow { display: flex; align-items: flex-start; }
    .mg-flow-s { flex: 1; text-align: center; position: relative; min-width: 0; }
    .mg-flow-s::before { content: ''; position: absolute; top: 13px; left: -50%; width: 100%; height: 2px; background: var(--mg-line); }
    .mg-flow-s:first-child::before { display: none; }
    .mg-flow-d { position: relative; z-index: 1; width: 28px; height: 28px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 15px; background: #fff; border: 2px solid #cfd8e6; color: #9aa8bb; }
    .mg-flow-s.done .mg-flow-d { background: #198754; border-color: #198754; color: #fff; }
    .mg-flow-s.done::before { background: #198754; }
    .mg-flow-s.me .mg-flow-d { background: var(--mg-blue); border-color: var(--mg-blue); color: #fff; box-shadow: 0 0 0 5px rgba(13, 110, 253, .15); }
    .mg-flow-s.me::before { background: #198754; }
    .mg-flow-x { font-size: 11.5px; font-weight: 700; margin-top: 7px; color: var(--mg-muted); }
    .mg-flow-s.me .mg-flow-x { color: var(--mg-blue); }

    /* ── Filter instansi ── */
    .mg-tools { display: flex; flex-wrap: wrap; gap: 8px; }
    .mg-tools .form-control, .mg-tools .form-select { border-radius: 11px; border-color: var(--mg-line); font-size: 12.5px; }
    .mg-tools #ins-q { flex: 1 1 150px; min-width: 0; width: auto !important; }
    .mg-foot { font-size: 11px; color: var(--mg-muted); margin-top: 10px; }

    /* Daftar peringkat (bidang & instansi) */
    .mg-rows { display: flex; flex-direction: column; gap: 2px; }
    .mg-row {
        display: flex; align-items: center; gap: 12px; width: 100%; padding: 10px 8px; border: 0; border-radius: 14px;
        background: transparent; text-align: left; font-family: inherit; color: inherit; transition: background .15s ease, opacity .2s ease;
    }
    button.mg-row { cursor: pointer; }
    button.mg-row:hover { background: #f7f9fd; }
    button.mg-row:focus-visible { outline: 3px solid rgba(13, 110, 253, .35); outline-offset: 1px; }
    .mg-row.sel { background: #eef4ff; }
    .mg-row.dim { opacity: .45; }
    .mg-rank { width: 22px; flex-shrink: 0; text-align: right; font-size: 11px; font-weight: 700; color: #9aa8bb; }
    .mg-ab {
        width: 36px; height: 36px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800; background: var(--bg); color: var(--c);
    }
    .mg-row-b { flex: 1; min-width: 0; }
    .mg-row-top { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; }
    .mg-row-n { font-size: 13px; font-weight: 600; color: var(--mg-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
    .mg-row-v { font-size: 13px; font-weight: 800; color: var(--mg-ink); white-space: nowrap; }
    .mg-row-v small { font-size: 11px; font-weight: 500; color: var(--mg-muted); margin-left: 3px; }
    .mg-track { height: 6px; border-radius: 999px; background: #edf1f7; margin-top: 7px; overflow: hidden; }
    .mg-track > span { display: block; height: 100%; border-radius: inherit; background: var(--c); transition: width .5s cubic-bezier(.4, 0, .2, 1); }

    /* Chart: tinggi area seragam */
    .mg-scroll { max-height: 340px; overflow-y: auto; overflow-x: hidden; scrollbar-width: thin; }
    .mg-donut { min-height: 310px; display: flex; align-items: center; justify-content: center; }
    .mg-donut > div { width: 100%; }

    @media (max-width: 575.98px) {
        .mg-title { font-size: 19px; }
        .mg-stat-n { font-size: 28px; }
        .mg-tiles { gap: 8px; }
        .mg-tile { padding: 12px 6px; }
        .mg-tile-n { font-size: 22px; }
        .mg-card { padding: 14px; }
    }
</style>

<div class="mg">

<!-- Judul -->
<div class="mg-head">
    <div class="mg-head-l">
        <div class="mg-head-ico"><i class='bx bxs-dashboard'></i></div>
        <div style="min-width:0;">
            <h1 class="mg-title">Dashboard Manager</h1>
            <p class="mg-sub"><i class="bx bx-calendar"></i> {{ $tgl_indo($today) }} &middot; Overview persetujuan laporan magang</p>
        </div>
    </div>
    <a href="{{ url('manager/approval-laporan') }}" class="mg-link-btn"><i class="bx bx-check-shield"></i> Buka Approval Laporan</a>
</div>

<!-- Banner prioritas -->
@if ($menunggu > 0)
<div class="mg-banner">
    <div class="mg-banner-ico"><i class="bx bxs-bell-ring"></i></div>
    <div class="mg-banner-b">
        <div class="mg-banner-t">{{ $menunggu }} laporan menunggu approval Anda</div>
        <div class="mg-banner-s">Sudah direview SDM, tinggal persetujuan Manager.</div>
    </div>
    <a href="{{ url('manager/approval-laporan') }}" class="mg-link-btn"><i class="bx bx-right-arrow-alt"></i> Tinjau Sekarang</a>
</div>
@else
<div class="mg-banner ok">
    <div class="mg-banner-ico"><i class="bx bx-check-double"></i></div>
    <div class="mg-banner-b">
        <div class="mg-banner-t">Tidak ada laporan yang menunggu</div>
        <div class="mg-banner-s">Semua laporan dari SDM sudah Anda tinjau.</div>
    </div>
</div>
@endif

<!-- STATISTIK UTAMA -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="mg-stat" style="--c:#0d6efd; --bg:#e7f1ff;">
            <div class="mg-stat-top"><span class="mg-stat-l">Total Peserta</span><span class="mg-ico"><i class="bx bx-group"></i></span></div>
            <div class="mg-stat-n">{{ number_format($total_peserta,0,',','.') }}</div>
            <div class="mg-stat-f"><span>Seluruh peserta terdaftar</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mg-stat" style="--c:#198754; --bg:#e1f2e9;">
            <div class="mg-stat-top"><span class="mg-stat-l">Peserta Aktif</span><span class="mg-ico"><i class="bx bx-user-check"></i></span></div>
            <div class="mg-stat-n">{{ number_format($peserta_aktif,0,',','.') }}</div>
            <div class="mg-stat-f"><span class="mg-chip">{{ $mg_pct_aktif }}% aktif</span><span>U1-2: {{ $unit1_count }} &middot; U9: {{ $unit9_count }}</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mg-stat" style="--c:#e0a800; --bg:#fff3cd;">
            <div class="mg-stat-top"><span class="mg-stat-l">Menunggu</span><span class="mg-ico"><i class="bx bx-time-five"></i></span></div>
            <div class="mg-stat-n">{{ number_format($peserta_menunggu,0,',','.') }}</div>
            <div class="mg-stat-f"><span class="mg-chip">{{ $mg_pct_tunggu }}%</span><span>dari total peserta</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mg-stat" style="--c:#0aa2c0; --bg:#d5f1f8;">
            <div class="mg-stat-top"><span class="mg-stat-l">Selesai Magang</span><span class="mg-ico"><i class="bx bx-check-double"></i></span></div>
            <div class="mg-stat-n">{{ number_format($peserta_selesai,0,',','.') }}</div>
            <div class="mg-stat-f"><span class="mg-chip">{{ $mg_pct_selesai_p }}%</span><span>dari total peserta</span></div>
        </div>
    </div>
</div>

<!-- STATISTIK PERSETUJUAN + SERTIFIKASI -->
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="mg-card">
            <div class="mg-card-h">
                <div>
                    <h6 class="mg-card-t"><i class="bx bx-task"></i> Statistik Persetujuan Laporan</h6>
                </div>
                <span class="mg-pill">Total laporan: {{ $total }}</span>
            </div>

            <div class="mg-tiles">
                <div class="mg-tile" style="--c:#b88600; --bg:#fff8e1;">
                    <div class="mg-tile-n">{{ $menunggu }}</div>
                    <div class="mg-tile-l">Menunggu Review</div>
                </div>
                <div class="mg-tile" style="--c:#198754; --bg:#e8f6ee;">
                    <div class="mg-tile-n">{{ $disetujui }}</div>
                    <div class="mg-tile-l">Telah Disetujui</div>
                </div>
                <div class="mg-tile" style="--c:#dc3545; --bg:#fdecee;">
                    <div class="mg-tile-n">{{ $ditolak }}</div>
                    <div class="mg-tile-l">Telah Ditolak</div>
                </div>
            </div>

            <div class="mg-seg" role="img" aria-label="Komposisi status laporan">
                @foreach ($mg_seg as $sg)
                    @if ($sg[1] > 0)
                    <span style="width: {{ round($sg[1] / $mg_div * 100, 2) }}%; background: {{ $sg[2] }};" title="{{ $sg[0] }}: {{ $sg[1] }}"></span>
                    @endif
                @endforeach
            </div>
            <div class="mg-legend">
                @foreach ($mg_seg as $sg)
                <span><i style="background: {{ $sg[2] }};"></i>{{ $sg[0] }} <b>{{ $sg[1] }}</b></span>
                @endforeach
            </div>

            <div class="mg-prog-l"><span>Penyelesaian review laporan</span><b>{{ $mg_review }}%</b></div>
            <div class="mg-prog"><span style="width: {{ $mg_review }}%;"></span></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="mg-card">
            <div class="mg-card-h">
                <h6 class="mg-card-t"><i class="bx bxs-award"></i> Sertifikasi</h6>
            </div>
            <div class="mg-cert">
                <div class="mg-cert-ico"><i class="bx bxs-certification"></i></div>
                <div>
                    <div class="mg-cert-n">{{ $sertif_digital }}</div>
                    <div class="mg-cert-l">Sertifikat Digital Terbit</div>
                </div>
            </div>
            <div class="mg-flow-t">Alur saat ini</div>
            <div class="mg-flow">
                <div class="mg-flow-s done"><div class="mg-flow-d"><i class="bx bx-check"></i></div><div class="mg-flow-x">SDM Review</div></div>
                <div class="mg-flow-s me"><div class="mg-flow-d"><i class="bx bx-user-check"></i></div><div class="mg-flow-x">Manager</div></div>
                <div class="mg-flow-s"><div class="mg-flow-d"><i class="bx bx-award"></i></div><div class="mg-flow-x">Digital</div></div>
            </div>
        </div>
    </div>
</div>

<!-- GRAFIK UTAMA -->
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="mg-card">
            <div class="mg-card-h">
                <div>
                    <h6 class="mg-card-t"><i class="bx bx-bar-chart-alt-2"></i> Peserta per Bidang</h6>
                    <p class="mg-card-s">Jumlah peserta di setiap bidang</p>
                </div>
            </div>
            <div class="mg-scroll">
            @if ($bidang_data->isEmpty())
                <div class="text-muted small py-5 text-center">Belum ada data bidang.</div>
            @else
                @php $mg_max_b = max(1, (int) $bidang_data->max('jumlah')); @endphp
                <div class="mg-rows">
                @foreach ($bidang_data as $i => $row)
                    @php
                        $nm = $row->bidang ?: 'Lainnya';
                        $wd = preg_split('/\s+/', trim($nm));
                        $ab = mb_strtoupper(mb_substr($wd[0] ?? '', 0, 1) . (isset($wd[1]) ? mb_substr($wd[1], 0, 1) : ''));
                        $pc = $mg_pal[$i % count($mg_pal)];
                    @endphp
                    <div class="mg-row" style="--c: {{ $pc[0] }}; --bg: {{ $pc[1] }};">
                        <span class="mg-rank">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="mg-ab">{{ $ab }}</span>
                        <div class="mg-row-b">
                            <div class="mg-row-top">
                                <span class="mg-row-n" title="{{ $nm }}">{{ $nm }}</span>
                                <span class="mg-row-v">{{ $row->jumlah }}<small>peserta</small></span>
                            </div>
                            <div class="mg-track"><span style="width: {{ round($row->jumlah / $mg_max_b * 100) }}%;"></span></div>
                        </div>
                    </div>
                @endforeach
                </div>
            @endif
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="mg-card">
            <div class="mg-card-h">
                <div>
                    <h6 class="mg-card-t"><i class="bx bx-pie-chart-alt"></i> Status Laporan</h6>
                    <p class="mg-card-s">Semua laporan masuk</p>
                </div>
            </div>
            <div id="chart-laporan" class="mg-donut"></div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="mg-card">
            <div class="mg-card-h">
                <div>
                    <h6 class="mg-card-t"><i class="bx bx-doughnut-chart"></i> Absensi Hari Ini</h6>
                    <p class="mg-card-s">Dari {{ $peserta_aktif }} peserta aktif</p>
                </div>
            </div>
            <div id="chart-absen" class="mg-donut"></div>
        </div>
    </div>
</div>

<!-- PESERTA PER INSTANSI -->
<div class="row g-3 mb-2">
    <div class="col-lg-8">
        <div class="mg-card">
            <div class="mg-card-h">
                <div>
                    <h6 class="mg-card-t"><i class="bx bx-buildings"></i> Peserta per Instansi</h6>
                    <p class="mg-card-s">Klik salah satu batang untuk melihat komposisi statusnya</p>
                </div>
            </div>
            <div class="mg-tools mb-3">
                <input type="text" id="ins-q" class="form-control form-control-sm" placeholder="Cari instansi...">
                <select id="ins-status" class="form-select form-select-sm w-auto">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Menunggu">Menunggu</option>
                    <option value="Selesai">Selesai</option>
                </select>
                <select id="ins-jenis" class="form-select form-select-sm w-auto">
                    <option value="">Siswa &amp; Mhs</option>
                    <option value="Siswa">Siswa</option>
                    <option value="Mahasiswa">Mahasiswa</option>
                </select>
                <select id="ins-top" class="form-select form-select-sm w-auto">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="0">Semua</option>
                </select>
            </div>
            <div id="ins-empty" class="text-muted small py-5 text-center d-none">Tidak ada instansi yang cocok.</div>
            <div id="ins-scroll" class="mg-scroll">
                <div id="chart-instansi"></div>
            </div>
            <div class="mg-foot"><span id="ins-info"></span></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="mg-card">
            <div class="mg-card-h" style="margin-bottom: 4px;">
                <h6 class="mg-card-t"><i class="bx bx-doughnut-chart"></i> Status Peserta</h6>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3" style="min-height: 22px;">
                <span id="ins-sub" class="text-muted small text-truncate">Semua instansi</span>
                <a href="#" id="ins-clear" class="small text-decoration-none d-none ms-2 flex-shrink-0">Reset</a>
            </div>
            <div id="chart-instansi-status" class="mg-donut"></div>
        </div>
    </div>
</div>

</div>

@section('scripts')
<script>
window.addEventListener('load', function() {

    var INK = '#16233b', MUTED = '#6b7a90';
    var FONT = 'inherit';
    var sum = function (a) { return a.reduce(function (x, y) { return x + y; }, 0); };

    // Donut: dipakai untuk semua grafik lingkaran (satu gaya)
    function donutOptions(series, labels, colors, totalFn) {
        return {
            series: series,
            labels: labels,
            colors: colors,
            chart: { type: 'donut', height: 300, fontFamily: FONT, foreColor: MUTED, toolbar: { show: false }, animations: { speed: 500 } },
            stroke: { width: 3, colors: ['#fff'] },
            dataLabels: { enabled: false },
            noData: { text: 'Tidak ada data', style: { color: MUTED, fontSize: '13px' } },
            legend: {
                position: 'bottom', fontSize: '12px', fontWeight: 600,
                labels: { colors: INK },
                markers: { width: 9, height: 9, radius: 9 },
                itemMargin: { horizontal: 8, vertical: 3 },
                formatter: function (name, o) { return name + '  ' + o.w.globals.series[o.seriesIndex]; }
            },
            plotOptions: { pie: { donut: { size: '72%', labels: {
                show: true,
                name:  { show: true, fontSize: '12px', fontWeight: 600, color: MUTED, offsetY: -4 },
                value: { show: true, fontSize: '26px', fontWeight: 800, color: INK, offsetY: 4 },
                total: { show: true, label: 'Total', fontSize: '12px', fontWeight: 600, color: MUTED, formatter: totalFn }
            } } } },
            tooltip: { theme: 'light', y: { formatter: function (v) { return v + ' orang'; } } },
            states: { active: { filter: { type: 'none' } } }
        };
    }

    /* ═══ Status Laporan ═══ */
    new ApexCharts(
        document.querySelector('#chart-laporan'),
        donutOptions([{{ $disetujui }}, {{ $menunggu }}, {{ $ditolak }}], ['Disetujui', 'Menunggu', 'Ditolak'],
            ['#198754', '#ffc107', '#dc3545'], function (w) { return sum(w.globals.seriesTotals); })
    ).render();

    /* ═══ Absensi Hari Ini ═══ */
    new ApexCharts(
        document.querySelector('#chart-absen'),
        donutOptions([{{ $absen_hadir }}, {{ $absen_izin }}, {{ $absen_sakit }}, {{ $belum_absen }}],
            ['Hadir', 'Izin', 'Sakit', 'Belum'], ['#198754', '#ffc107', '#dc3545', '#9aa8bb'],
            function () { return {{ $peserta_aktif }}; })
    ).render();

    /* ═══ Peserta per Instansi + Status Peserta ═══ */
    (function () {
        var RAW = @json($instansi_raw).map(function (r) {
            return { n: r.instansi, j: r.status_peserta, s: r.status_magang, c: Number(r.jumlah) };
        });
        var $ = function (id) { return document.getElementById(id); };
        if (!$('chart-instansi')) return;

        var PAL = [['#0d6efd', '#e7f1ff'], ['#198754', '#e1f2e9'], ['#0aa2c0', '#d5f1f8'], ['#6f42c1', '#ece4f7'], ['#d63384', '#fbe3ee']];
        var STATUS = ['Aktif', 'Menunggu', 'Selesai'];
        var shown = [], selected = null, donut = null;

        var esc = function (t) {
            return String(t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
        };
        var singkat = function (nama) {
            var w = nama.trim().split(/\s+/);
            return ((w[0] || '').charAt(0) + (w[1] ? w[1].charAt(0) : '')).toUpperCase();
        };

        function listInstansi() {
            var q = $('ins-q').value.trim().toLowerCase(), st = $('ins-status').value, jn = $('ins-jenis').value, map = {};
            RAW.forEach(function (r) {
                if (st && r.s !== st) return;
                if (jn && r.j !== jn) return;
                if (q && r.n.toLowerCase().indexOf(q) === -1) return;
                map[r.n] = (map[r.n] || 0) + r.c;
            });
            return Object.keys(map).map(function (k) { return { n: k, total: map[k] }; })
                .sort(function (a, b) { return b.total - a.total || a.n.localeCompare(b.n); });
        }

        function statusTotals() {
            var q = $('ins-q').value.trim().toLowerCase(), jn = $('ins-jenis').value, t = { Aktif: 0, Menunggu: 0, Selesai: 0 };
            RAW.forEach(function (r) {
                if (jn && r.j !== jn) return;
                if (selected ? r.n !== selected : (q && r.n.toLowerCase().indexOf(q) === -1)) return;
                t[r.s] += r.c;
            });
            return STATUS.map(function (k) { return t[k]; });
        }

        function drawDonut() {
            var s = statusTotals(), total = sum(s);
            $('ins-sub').textContent = selected || 'Semua instansi';
            $('ins-sub').title = selected || '';
            $('ins-clear').classList.toggle('d-none', !selected);
            if (donut) { donut.updateSeries(total ? s : [0, 0, 0]); return; }
            donut = new ApexCharts($('chart-instansi-status'),
                donutOptions(s, STATUS, ['#198754', '#ffc107', '#0dcaf0'],
                    function (w) { return sum(w.globals.seriesTotals); }));
            donut.render();
        }

        // Daftar peringkat (menggantikan grafik batang)
        function drawList() {
            var box = $('chart-instansi'), max = shown.length ? shown[0].total : 1, html = '';
            shown.forEach(function (o, i) {
                var pc = PAL[i % PAL.length];
                var cls = 'mg-row' + (selected ? (o.n === selected ? ' sel' : ' dim') : '');
                html += '<button type="button" class="' + cls + '" data-i="' + i + '" style="--c:' + pc[0] + ';--bg:' + pc[1] + ';"' +
                    ' aria-pressed="' + (o.n === selected) + '">' +
                    '<span class="mg-rank">' + String(i + 1).padStart(2, '0') + '</span>' +
                    '<span class="mg-ab">' + esc(singkat(o.n)) + '</span>' +
                    '<span class="mg-row-b">' +
                        '<span class="mg-row-top" style="display:flex;">' +
                            '<span class="mg-row-n" title="' + esc(o.n) + '">' + esc(o.n) + '</span>' +
                            '<span class="mg-row-v">' + o.total.toLocaleString('id-ID') + '<small>peserta</small></span>' +
                        '</span>' +
                        '<span class="mg-track" style="display:block;"><span data-w="' + Math.round(o.total / max * 100) + '" style="width:0;"></span></span>' +
                    '</span></button>';
            });
            box.innerHTML = '<div class="mg-rows">' + html + '</div>';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    box.querySelectorAll('[data-w]').forEach(function (b) { b.style.width = b.getAttribute('data-w') + '%'; });
                });
            });
        }

        function render() {
            var list = listInstansi(), top = parseInt($('ins-top').value, 10);
            shown = top > 0 ? list.slice(0, top) : list;
            if (selected && !shown.some(function (o) { return o.n === selected; })) selected = null;
            var total = list.reduce(function (a, o) { return a + o.total; }, 0);
            $('ins-info').textContent = 'Menampilkan ' + shown.length + ' dari ' + list.length + ' instansi · ' + total.toLocaleString('id-ID') + ' peserta';
            $('ins-empty').classList.toggle('d-none', shown.length > 0);
            $('ins-scroll').classList.toggle('d-none', shown.length === 0);
            if (shown.length) drawList(); else $('chart-instansi').innerHTML = '';
            drawDonut();
        }

        // Klik baris = pilih / batal pilih instansi
        $('chart-instansi').addEventListener('click', function (e) {
            var btn = e.target.closest('.mg-row'); if (!btn) return;
            var o = shown[parseInt(btn.dataset.i, 10)]; if (!o) return;
            selected = selected === o.n ? null : o.n;
            drawList(); drawDonut();
        });

        var timer;
        $('ins-q').addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(render, 180); });
        ['ins-status', 'ins-jenis', 'ins-top'].forEach(function (id) { $(id).addEventListener('change', render); });
        $('ins-clear').addEventListener('click', function (e) { e.preventDefault(); selected = null; drawList(); drawDonut(); });
        render();
    })();
});
</script>
@endsection
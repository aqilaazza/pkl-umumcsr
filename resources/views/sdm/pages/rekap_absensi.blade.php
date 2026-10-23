@php
    use Illuminate\Support\Facades\DB;

    // Opsi status magang (enum di kolom peserta.status_magang)
    $status_opsi = ['Aktif' => 'Aktif', 'Menunggu' => 'Menunggu', 'Selesai' => 'Selesai'];

    $filter_bulan  = (string) request('bulan', date('Y-m'));
    $filter_unit   = (string) request('unit', '');
    $filter_bidang = (int) request('bidang', 0);
    $filter_search = trim((string) request('q', ''));

    // Default: Aktif. 'semua' = tampilkan semua status.
    $filter_status = request()->has('status') ? (string) request('status') : 'Aktif';
    if ($filter_status !== 'semua' && !isset($status_opsi[$filter_status])) {
        $filter_status = 'Aktif';
    }

    $page_num      = max(1, (int) request('hal', 1));
    $per_page      = 25;
    $offset        = ($page_num - 1) * $per_page;

    // Date range
    $tgl_awal  = $filter_bulan . '-01';
    $tgl_akhir = date('Y-m-d', strtotime($tgl_awal . ' +1 month'));

    // Ambil daftar bidang untuk filter
    $bidang_list = DB::table('bidang')->orderBy('bidang')->get();

    // Build WHERE
    $applyFilter = function ($q) use ($filter_unit, $filter_bidang, $filter_search, $filter_status) {
        if ($filter_status !== 'semua') {
            $q->where('p.status_magang', $filter_status);
        }
        if ($filter_unit !== '') {
            $q->where('p.unit', $filter_unit);
        }
        if ($filter_bidang) {
            $q->where('p.bidang_id', $filter_bidang);
        }
        if ($filter_search !== '') {
            $like = '%' . $filter_search . '%';
            $q->where(function ($w) use ($like) {
                $w->where('p.nama', 'like', $like)
                    ->orWhere('p.asal_sekolah', 'like', $like)
                    ->orWhere('p.username', 'like', $like);
            });
        }

        return $q;
    };

    // Total peserta (untuk pagination)
    $total_p        = (int) $applyFilter(DB::table('peserta as p'))->count();
    $total_halaman  = max(1, (int) ceil($total_p / $per_page));

    // Ambil data peserta + agregat absensi (1 query, bukan N+1)
    $absensi_sub = DB::table('absensi_peserta')
        ->where('tanggal', '>=', $tgl_awal)
        ->where('tanggal', '<', $tgl_akhir)
        ->groupBy('username')
        ->selectRaw("username, SUM(status='Hadir') as Hadir, SUM(status='Izin') as Izin, SUM(status='Sakit') as Sakit, SUM(status='Alpha') as Alpha, COUNT(*) as total");

    $peserta_list = $applyFilter(
        DB::table('peserta as p')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->leftJoin('users as u', 'p.username', '=', 'u.username')
            ->leftJoinSub($absensi_sub, 'h', 'p.username', '=', 'h.username')
            ->selectRaw('p.*, b.bidang as nama_bidang, u.nama as nama_user,
                COALESCE(h.Hadir, 0) as hadir_count,
                COALESCE(h.Izin, 0) as izin_count,
                COALESCE(h.Sakit, 0) as sakit_count,
                COALESCE(h.Alpha, 0) as alpha_count,
                COALESCE(h.total, 0) as total_absensi')
    )->orderBy('p.nama', 'asc')->limit($per_page)->offset($offset)->get();

    $bulan_names = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $bln_parts = explode('-', $filter_bulan);
    $bulan_label = ($bulan_names[(int) ($bln_parts[1] ?? 0)] ?? '') . ' ' . $bln_parts[0];

    // Hari libur bulan ini, diambil dari Pengaturan > Hari Libur (tabel hari_libur)
    $libur_list = DB::table('hari_libur')
        ->where('tanggal', '>=', $tgl_awal)
        ->where('tanggal', '<', $tgl_akhir)
        ->orderBy('tanggal')
        ->get(['tanggal', 'keterangan']);
    $libur_count = $libur_list->count();
    $libur_sub = $libur_list->isEmpty()
        ? 'Tidak ada libur khusus'
        : $libur_list->map(function ($l) use ($bulan_names) {
            $ts = strtotime($l->tanggal);
            return (int) date('j', $ts) . ' ' . mb_substr($bulan_names[(int) date('n', $ts)], 0, 3) . ': ' . $l->keterangan;
        })->implode(', ');

    // Summary stats based on filter (pakai date range)
    $sum_rekap = $applyFilter(
        DB::table('absensi_peserta as ap')
            ->join('peserta as p', 'ap.username', '=', 'p.username')
            ->where('ap.tanggal', '>=', $tgl_awal)
            ->where('ap.tanggal', '<', $tgl_akhir)
    )->selectRaw("SUM(ap.status='Hadir') as hadir, SUM(ap.status='Izin') as izin, SUM(ap.status='Sakit') as sakit, SUM(ap.status='Alpha') as alpha")->first();

    $sum_h   = (int) ($sum_rekap->hadir ?? 0);
    $sum_i   = (int) ($sum_rekap->izin ?? 0);
    $sum_s   = (int) ($sum_rekap->sakit ?? 0);
    $sum_a   = (int) ($sum_rekap->alpha ?? 0);
    $sum_all = $sum_h + $sum_i + $sum_s + $sum_a;
    $persen  = function ($n) use ($sum_all) {
        return $sum_all > 0 ? number_format(($n / $sum_all) * 100, 1, ',', '.') . '%' : '0%';
    };

    $url_base = url('sdm/rekap-absensi')
        . '?bulan=' . urlencode($filter_bulan)
        . '&status=' . urlencode($filter_status)
        . '&unit=' . urlencode($filter_unit)
        . '&bidang=' . $filter_bidang
        . '&q=' . urlencode($filter_search)
        . '&hal=';

    // Rentang cepat (pindah bulan, filter lain tetap)
    $url_bulan = function ($ym) use ($filter_status, $filter_unit, $filter_bidang, $filter_search) {
        return url('sdm/rekap-absensi')
            . '?bulan=' . urlencode($ym)
            . '&status=' . urlencode($filter_status)
            . '&unit=' . urlencode($filter_unit)
            . '&bidang=' . $filter_bidang
            . '&q=' . urlencode($filter_search);
    };
    $quick_ranges = [
        'Bulan Ini'    => date('Y-m'),
        'Bulan Lalu'   => date('Y-m', strtotime('first day of -1 month')),
        '2 Bulan Lalu' => date('Y-m', strtotime('first day of -2 month')),
        '3 Bulan Lalu' => date('Y-m', strtotime('first day of -3 month')),
    ];

    // Kartu KPI (dirender lewat partials.stat_card_v2)
    $kpis = [
        ['color' => 'primary',   'label' => 'Total Peserta', 'icon' => 'bx bx-group',        'val' => $total_p,     'sub' => ($filter_status === 'semua' ? 'Semua status' : 'Status ' . $filter_status) . ' · ' . $bulan_label],
        ['color' => 'success',   'label' => 'Hadir',         'icon' => 'bx bx-user-check',   'val' => $sum_h,       'sub' => $persen($sum_h) . ' dari total log'],
        ['color' => 'info',      'label' => 'Izin',          'icon' => 'bx bx-time-five',    'val' => $sum_i,       'sub' => $persen($sum_i) . ' dari total log'],
        ['color' => 'danger',    'label' => 'Sakit',         'icon' => 'bx bx-plus-medical', 'val' => $sum_s,       'sub' => $persen($sum_s) . ' dari total log'],
        ['color' => 'secondary', 'label' => 'Alpha',         'icon' => 'bx bx-user-x',       'val' => $sum_a,       'sub' => $persen($sum_a) . ' tanpa keterangan'],
        ['color' => 'dark',      'label' => 'Hari Libur',    'icon' => 'bx bx-calendar-x',   'val' => $libur_count, 'sub' => $libur_sub],
    ];

    $tampil_dari   = $total_p > 0 ? $offset + 1 : 0;
    $tampil_sampai = $offset + count($peserta_list);
@endphp

<style>
    .rk {
        --rk-ink: #0f172a;
        --rk-panel: #ffffff;
        --rk-panel-2: #f8fafc;
        --rk-line: #e2e8f0;
        --rk-text: #1e293b;
        --rk-muted: #64748b;
        --rk-faint: #b6c0d0;
        --rk-accent: #0891b2;
        --rk-hadir: #059669;
        --rk-izin: #2563eb;
        --rk-sakit: #e11d48;
        --rk-alpha: #64748b;
        --rk-warn: #d97706;
        --rk-mono: var(--pkl-font-body, 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif);
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        color: var(--rk-text);
    }
    .rk a { text-decoration: none; }

    /* Header */
    .rk-crumb { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; font-family: var(--rk-mono); font-size: 10.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--rk-muted); margin-bottom: .75rem; }
    .rk-crumb a { color: var(--rk-muted); }
    .rk-crumb a:hover { color: var(--rk-ink); }
    .rk-crumb strong { color: var(--rk-ink); font-weight: 600; }
    .rk-crumb .rk-pill { margin-left: .25rem; }
    .rk-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem 2rem; align-items: flex-start; margin-bottom: 1.5rem; }
    .rk-head__main { flex: 1 1 auto; min-width: 0; }
    .rk .rk-title { display: flex; align-items: center; flex-wrap: wrap; gap: .75rem; margin: 0 0 .5rem; background: none; font-family: var(--pkl-font-head); font-size: 22px; font-weight: 800; letter-spacing: -.015em; line-height: 1.2; color: #000; }
    .rk .rk-sub { margin: 0; max-width: none; white-space: normal; color: var(--rk-muted); font-size: 12.5px; font-weight: 400; line-height: 1.45; }
    .rk-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .2rem .6rem; border-radius: 999px; font-family: var(--rk-mono); font-size: 10.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--rk-izin); background: rgba(37, 99, 235, .08); border: 1px solid rgba(37, 99, 235, .25); }
    .rk-pill--live { color: var(--rk-hadir); background: rgba(5, 150, 105, .08); border-color: rgba(5, 150, 105, .25); }
    .rk-pill--live::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .rk-actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: flex-end; }
    .rk-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .6rem 1rem; border-radius: 8px; border: 1px solid var(--rk-line); background: var(--rk-panel); color: var(--rk-ink); font-size: 13.5px; font-weight: 800; cursor: pointer; transition: background .15s, border-color .15s; }
    .rk-btn:hover { background: #f1f5f9; border-color: #cbd5e1; color: var(--rk-ink); }
    .rk-btn--accent { background: var(--rk-accent); border-color: var(--rk-accent); color: #fff; }
    .rk-btn--accent:hover { background: #0e7490; border-color: #0e7490; color: #fff; }
    .rk-btn:focus-visible, .rk-chip:focus-visible, .rk-iconbtn:focus-visible { outline: 2px solid var(--rk-accent); outline-offset: 2px; }

    /* KPI (override tampilan partials.stat_card_v2 khusus halaman ini) */
    .rk-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .9rem; margin-bottom: 1.5rem; }
    .rk-kpis__item { min-width: 0; display: flex; }
    .rk .ui-stat-card { --tone: var(--rk-izin); position: relative; overflow: hidden; width: 100%; min-height: 128px; padding: 1rem 1.1rem; border-radius: 12px; border: 1px solid var(--rk-line); background: var(--rk-panel); box-shadow: 0 1px 2px rgba(15, 23, 42, .04); display: flex; flex-direction: column; color: var(--rk-text); }
    .rk .ui-stat-card::before { content: ''; position: absolute; top: -50px; right: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--tone); opacity: .08; }
    .rk .ui-stat-card[data-stat-color="primary"]   { --tone: var(--rk-accent); }
    .rk .ui-stat-card[data-stat-color="success"]   { --tone: var(--rk-hadir); }
    .rk .ui-stat-card[data-stat-color="info"]      { --tone: var(--rk-izin); }
    .rk .ui-stat-card[data-stat-color="danger"]    { --tone: var(--rk-sakit); }
    .rk .ui-stat-card[data-stat-color="secondary"],
    .rk .ui-stat-card[data-stat-color="dark"]      { --tone: var(--rk-alpha); }
    .rk .ui-stat-card__head { position: absolute; top: 1rem; right: 1.1rem; margin: 0; padding: 0; }
    .rk .ui-stat-card__badge { display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; border-radius: 8px; background: rgba(15, 23, 42, .05); color: var(--tone); font-size: 16px; }
    .rk .ui-stat-card__menu { display: none; }
    .rk .ui-stat-card__body { position: relative; padding: 0; flex: 1; display: flex; }
    .rk .ui-stat-card__meta { display: flex; flex-direction: column; width: 100%; padding-right: 2rem; }
    .rk .ui-stat-card__label { order: -1; margin-bottom: .5rem; font-family: var(--pkl-font-body); font-size: 12px; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--tone); }
    .rk .ui-stat-card__value { margin: 0 0 .5rem; font-family: var(--pkl-font-body); font-size: 32px; font-weight: 800; line-height: 1.1; color: var(--rk-ink); font-variant-numeric: tabular-nums; }
    .rk .ui-stat-card[data-stat-color="success"] .ui-stat-card__value { color: var(--rk-hadir); }
    .rk .ui-stat-card__sub { margin-top: auto; font-family: var(--pkl-font-body); font-size: 12.5px; font-weight: 400; line-height: 1.45; color: var(--rk-muted); white-space: normal; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    /* Filter */
    .rk-filter { padding: 1rem 1.1rem 1.1rem; border-radius: 12px; border: 1px solid var(--rk-line); background: var(--rk-panel-2); margin-bottom: 1.5rem; }
    .rk-filter__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)) auto auto; gap: .75rem; align-items: end; }
    .rk-field label { display: flex; align-items: center; gap: .35rem; margin-bottom: .35rem; font-family: var(--pkl-font-body); font-size: 12px; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--rk-muted); }
    .rk .rk-input { width: 100%; padding: .55rem .75rem; border-radius: 8px; border: 1px solid var(--rk-line); background: #fff; color: var(--rk-ink); font-size: 13.5px; }
    .rk .rk-input:focus { outline: none; border-color: var(--rk-accent); box-shadow: 0 0 0 3px rgba(8, 145, 178, .15); }
    .rk .rk-input::placeholder { color: #94a3b8; }
    .rk-filter__row2 { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1.5rem; margin-top: .9rem; }
    .rk-search { position: relative; flex: 1 1 320px; max-width: 460px; }
    .rk-search i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: var(--rk-muted); font-size: 16px; }
    .rk-search .rk-input { padding-left: 2.3rem; }
    .rk-quick { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .rk-quick__label { font-family: var(--pkl-font-body); font-size: 12px; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--rk-muted); margin-right: .25rem; }
    .rk-chip { padding: .3rem .8rem; border-radius: 999px; border: 1px solid var(--rk-line); background: #fff; color: var(--rk-text); font-size: 12px; font-weight: 700; }
    .rk-chip:hover { border-color: rgba(8, 145, 178, .5); color: var(--rk-ink); }
    .rk-chip.is-active { background: rgba(8, 145, 178, .1); border-color: rgba(8, 145, 178, .5); color: var(--rk-accent); }
    .rk-iconbtn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 38px; border-radius: 8px; border: 1px solid var(--rk-line); background: #fff; color: var(--rk-muted); }
    .rk-iconbtn:hover { color: var(--rk-ink); border-color: #cbd5e1; }

    /* Tabel */
    .rk-table-card { border-radius: 12px; border: 1px solid var(--rk-line); background: var(--rk-panel); overflow: hidden; margin-bottom: 1.5rem; }
    .rk-table-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .6rem 1.5rem; padding: .85rem 1.1rem; background: var(--rk-panel-2); border-bottom: 1px solid var(--rk-line); }
    .rk-table-bar h2 { display: flex; align-items: center; gap: .6rem; margin: 0; font-family: var(--pkl-font-head); font-size: 14.5px; font-weight: 800; color: var(--rk-ink); }
    .rk-table-bar h2 em { font-style: normal; font-family: var(--rk-mono); font-size: 11px; font-weight: 700; color: var(--rk-izin); padding-left: .75rem; border-left: 1px solid var(--rk-line); }
    .rk-legend { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 11.5px; font-weight: 500; color: var(--rk-muted); }
    .rk-legend span::before { content: ''; display: inline-block; width: 8px; height: 8px; margin-right: .4rem; border-radius: 50%; background: var(--c); }
    .rk-wrap { overflow-x: auto; }
    #tabelRekap { width: 100%; min-width: 1100px; table-layout: fixed; border-collapse: collapse; margin: 0; color: var(--rk-text); }
    #tabelRekap thead th { padding: .85rem .75rem; background: #f1f5f9; border-bottom: 1px solid var(--rk-line); font-family: var(--rk-mono); font-size: 10.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--rk-muted); white-space: nowrap; text-align: left; vertical-align: middle; }
    #tabelRekap thead th.c { text-align: center; }
    #tabelRekap thead th.h { color: var(--rk-hadir); }
    #tabelRekap thead th.i { color: var(--rk-izin); }
    #tabelRekap thead th.s { color: var(--rk-sakit); }
    #tabelRekap tbody td { padding: .85rem .75rem; border-bottom: 1px solid var(--rk-line); background: transparent; color: var(--rk-text); vertical-align: middle; white-space: normal; overflow-wrap: anywhere; font-size: 13px; font-weight: 400; }
    #tabelRekap tbody tr:last-child td { border-bottom: 0; }
    #tabelRekap tbody tr:hover td { background: rgba(37, 99, 235, .04); }
    #tabelRekap td.c { text-align: center; }
    .rk-no { font-family: var(--rk-mono); color: var(--rk-muted); font-size: 12px; font-weight: 600; }
    .rk-person { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .rk-avatar { flex: none; display: inline-flex; width: 36px; height: 36px; align-items: center; justify-content: center; border-radius: 50%; background: #eef2f8; border: 1px solid var(--rk-line); font-size: 11px; font-weight: 700; color: #334155; }
    .rk-person b { display: block; font-size: 13px; font-weight: 800; text-transform: none; line-height: 1.25; color: var(--rk-ink); }
    .rk-person small { display: block; margin-top: .15rem; font-family: var(--rk-mono); font-size: 11.5px; font-weight: 500; color: var(--rk-izin); }
    .rk-unit { font-family: var(--rk-mono); font-size: 11.5px; font-weight: 600; color: var(--rk-izin); display: block; margin-bottom: .15rem; }
    .rk-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .18rem .65rem; border-radius: 999px; font-size: 10.5px; font-weight: 800; }
    .rk-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .rk-badge--success { color: var(--rk-hadir); background: rgba(5, 150, 105, .1); }
    .rk-badge--warning { color: var(--rk-warn); background: rgba(217, 119, 6, .1); }
    .rk-badge--secondary { color: var(--rk-alpha); background: rgba(100, 116, 139, .12); }
    .rk-badge--light { color: var(--rk-muted); background: rgba(15, 23, 42, .05); }
    .rk-n { font-family: var(--rk-mono); font-size: 13px; font-weight: 700; color: var(--rk-faint); font-variant-numeric: tabular-nums; }
    .rk-n.on.h { color: var(--rk-hadir); }
    .rk-n.on.i { color: var(--rk-izin); }
    .rk-n.on.s { color: var(--rk-sakit); }
    .rk-n.on.a { color: var(--rk-alpha); }
    .rk-pct { font-family: var(--rk-mono); font-size: 11px; font-weight: 700; color: var(--rk-ink); font-variant-numeric: tabular-nums; }
    .rk-bar { height: 5px; width: 64px; max-width: 100%; margin: .3rem auto 0; border-radius: 99px; background: rgba(15, 23, 42, .08); overflow: hidden; }
    .rk-bar i { display: block; height: 100%; border-radius: 99px; background: var(--bar, var(--rk-hadir)); }
    .rk-row-actions { display: flex; justify-content: center; gap: .4rem; }
    .rk-act { display: inline-flex; width: 32px; height: 32px; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--rk-line); background: #fff; color: var(--rk-izin); cursor: pointer; transition: background .15s; }
    .rk-act:hover { background: #eff6ff; }
    .rk-act--pdf { color: var(--rk-sakit); }
    .rk-act--pdf:hover { background: #fff1f2; }
    .rk-act:disabled { opacity: .5; cursor: wait; }
    .rk-act:focus-visible { outline: 2px solid var(--rk-accent); outline-offset: 2px; }
    .rk-empty { padding: 3rem 1rem; text-align: center; color: var(--rk-muted); }
    .rk-empty i { display: block; font-size: 2.2rem; margin-bottom: .5rem; color: var(--rk-faint); }
    .rk-empty b { display: block; color: var(--rk-ink); margin-bottom: .25rem; }

    /* Footer tabel + pagination */
    .rk-table-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; padding: .85rem 1.1rem; background: var(--rk-panel-2); border-top: 1px solid var(--rk-line); }
    .rk-showing { font-family: var(--rk-mono); font-size: 11.5px; font-weight: 500; color: var(--rk-muted); }
    .rk-showing b { color: var(--rk-ink); }
    .rk .pagination { margin: 0; gap: .3rem; }
    .rk .pagination .page-link { min-width: 34px; text-align: center; border: 1px solid var(--rk-line); border-radius: 8px !important; background: #fff; color: var(--rk-text); font-size: 13px; font-weight: 800; box-shadow: none; }
    .rk .pagination .page-link:hover { background: #f1f5f9; color: var(--rk-ink); }
    .rk .pagination .page-item.active .page-link { background: var(--rk-ink); border-color: var(--rk-ink); color: #fff; }
    .rk .pagination .page-item.disabled .page-link { background: transparent; color: var(--rk-faint); }

    /* Kartu info bawah */
    .rk-info { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .9rem; }
    .rk-info__card { display: flex; gap: 1rem; padding: 1.1rem; border-radius: 12px; border: 1px solid var(--rk-line); background: var(--rk-panel-2); }
    .rk-info__icon { flex: none; display: inline-flex; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 10px; background: #fff; border: 1px solid var(--rk-line); color: var(--rk-ink); font-size: 20px; }
    .rk-info__card h3 { margin: 0 0 .3rem; font-family: var(--pkl-font-head); font-size: 14.5px; font-weight: 800; color: var(--rk-ink); }
    .rk-info__card p { margin: 0 0 .4rem; font-size: 12.5px; font-weight: 400; line-height: 1.45; color: var(--rk-muted); }
    .rk-info__card .rk-tag { font-family: var(--rk-mono); font-size: 10.5px; font-weight: 800; color: var(--rk-hadir); }
    .rk-info__card a.rk-tag { color: var(--rk-izin); }
    .rk-info__card a.rk-tag:hover { text-decoration: underline; }

    @media (max-width: 1199px) {
        .rk-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .rk-filter__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rk-info { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .rk .rk-sub { white-space: normal; }
    }
    @media (max-width: 575px) {
        .rk-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rk-filter__grid { grid-template-columns: 1fr; }
        .rk-actions { justify-content: flex-start; }
    }
    @media print {
        .rk-actions, .rk-filter, .rk-table-foot, .rk-info, .rk-act { display: none !important; }
    }

    /* ===== Modal Detail Absensi ===== */
    .rkm { --m-ink:#0f172a; --m-text:#1e293b; --m-muted:#64748b; --m-faint:#b6c0d0; --m-line:#e2e8f0; --m-panel2:#f8fafc;
           --m-accent:#0891b2; --m-hadir:#059669; --m-izin:#2563eb; --m-sakit:#e11d48; --m-warn:#d97706;
           --m-mono: ui-monospace, 'JetBrains Mono', SFMono-Regular, Menlo, Consolas, monospace; }
    .rkm .modal-content { position:relative; border:1px solid var(--m-line); border-radius:22px; overflow:hidden; background:#fff; box-shadow:0 30px 80px rgba(15,23,42,.25); color:var(--m-text); }
    .rkm .modal-content::before { content:''; position:absolute; inset:0 0 auto 0; height:5px; background:linear-gradient(90deg,#10b981,#0891b2,#0ea5e9); z-index:2; }
    /* Lebar modal: dilebarkan supaya tabel muat tanpa geser di desktop */
    .rkm .rkm-dialog { max-width: min(1280px, 96vw); }

    .rkm-head { display:flex; align-items:center; gap:1.1rem; padding:2rem 2rem 1.4rem; background:var(--m-panel2); border-bottom:1px solid #eef2f7; }
    .rkm-avatar { position:relative; flex:none; display:inline-flex; width:70px; height:70px; align-items:center; justify-content:center; border-radius:20px; background:linear-gradient(135deg,#0d9488,#0891b2); color:#fff; font-size:22px; font-weight:800; letter-spacing:.02em; box-shadow:0 8px 20px rgba(8,145,178,.3); }
    .rkm-avatar__dot { position:absolute; right:-4px; bottom:-4px; width:16px; height:16px; border-radius:50%; background:#10b981; border:3px solid #fff; }
    .rkm-head__main { flex:1 1 auto; min-width:0; }
    .rkm-tags { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.45rem; }
    .rkm-tag { display:inline-flex; align-items:center; gap:.4rem; padding:.2rem .7rem; border-radius:999px; font-family:var(--m-mono); font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#0e7490; background:rgba(8,145,178,.08); border:1px solid rgba(8,145,178,.25); }
    .rkm-tag--ok { text-transform:none; letter-spacing:.02em; color:var(--m-hadir); background:rgba(5,150,105,.08); border-color:rgba(5,150,105,.25); }
    .rkm-tag--ok .rkm-dot { width:7px; height:7px; border-radius:50%; background:currentColor; }
    .rkm-name { margin:0; font-size:26px; font-weight:800; letter-spacing:-.02em; line-height:1.15; text-transform:uppercase; color:var(--m-ink); }
    .rkm-meta { margin:.3rem 0 0; font-size:13px; color:var(--m-muted); display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; }
    .rkm-meta .rkm-sep { width:4px; height:4px; border-radius:50%; background:var(--m-faint); }
    .rkm-close { flex:none; align-self:flex-start; display:inline-flex; width:42px; height:42px; align-items:center; justify-content:center; border-radius:12px; border:1px solid var(--m-line); background:#f1f5f9; color:var(--m-muted); font-size:22px; cursor:pointer; transition:background .15s,color .15s; }
    .rkm-close:hover { background:#e2e8f0; color:var(--m-ink); }

    .rkm .modal-body { padding:1.6rem 1.25rem; background:#fff; }
    .rkm-tablecard { border:1px solid var(--m-line); border-radius:16px; overflow:hidden; }
    .rkm-scroll { overflow-x:auto; }
    .rkm-table { width:100%; min-width:900px; margin:0; border-collapse:collapse; }
    .rkm-table thead th { padding:1rem .75rem; background:var(--m-panel2); border-bottom:1px solid var(--m-line); font-family:var(--m-mono); font-size:11px; font-weight:600; letter-spacing:.18em; text-transform:uppercase; color:#475569; text-align:center; white-space:nowrap; }
    .rkm-table tbody td { padding:.85rem .75rem; border-bottom:1px solid #eef2f7; text-align:center; vertical-align:middle; font-size:13px; background:transparent; }
    .rkm-table tbody tr:last-child td { border-bottom:0; }
    .rkm-table tbody tr:hover td { background:rgba(8,145,178,.03); }
    .rkm-muted { color:var(--m-faint); }

    .rkm-chip { display:inline-block; padding:.3rem .65rem; border-radius:8px; background:#f1f5f9; border:1px solid var(--m-line); font-size:12.5px; font-weight:500; color:var(--m-text); }
    .rkm-chip--date { font-family:var(--m-mono); font-weight:700; color:var(--m-ink); }
    .rkm-time { display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .8rem; border-radius:999px; font-family:var(--m-mono); font-size:13px; font-weight:700; border:1px solid; }
    .rkm-time--in  { color:var(--m-hadir); background:rgba(5,150,105,.07); border-color:rgba(5,150,105,.3); }
    .rkm-time--out { color:var(--m-sakit); background:rgba(225,29,72,.06); border-color:rgba(225,29,72,.25); }
    .rkm-time--none { color:var(--m-faint); background:#f1f5f9; border-color:var(--m-line); padding:.3rem 1rem; }

    .rkm-status { display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .75rem; border-radius:999px; font-size:12.5px; font-weight:700; border:1px solid; }
    .rkm-status::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .rkm-status--hadir { color:var(--m-hadir); background:rgba(5,150,105,.08); border-color:rgba(5,150,105,.25); }
    .rkm-status--izin  { color:var(--m-izin);  background:rgba(37,99,235,.08); border-color:rgba(37,99,235,.25); }
    .rkm-status--sakit { color:var(--m-sakit); background:rgba(225,29,72,.07); border-color:rgba(225,29,72,.25); }
    .rkm-status--alpha { color:#475569; background:rgba(100,116,139,.1); border-color:rgba(100,116,139,.25); }
    .rkm-appr { display:block; margin:.3rem auto 0; width:fit-content; padding:.05rem .45rem; border-radius:6px; font-size:10px; font-weight:700; border:1px solid; background:#fff; }
    .rkm-appr--Pending  { color:var(--m-warn); border-color:var(--m-warn); }
    .rkm-appr--Ditolak  { color:var(--m-sakit); border-color:var(--m-sakit); }
    .rkm-appr--Disetujui{ color:var(--m-hadir); border-color:var(--m-hadir); }

    .rkm-gps { display:flex; flex-direction:column; align-items:center; gap:.4rem; }
    .rkm-gps a { display:inline-flex; align-items:center; gap:.45rem; padding:.3rem .85rem; border-radius:999px; background:#fff; border:1px solid var(--m-line); font-family:var(--m-mono); font-size:11.5px; font-weight:500; color:var(--m-text); text-decoration:none; white-space:nowrap; transition:border-color .15s, background .15s; }
    .rkm-gps a:hover { background:#f8fafc; border-color:#cbd5e1; }
    .rkm-gps a.in i  { color:var(--m-hadir); }
    .rkm-gps a.out i { color:var(--m-sakit); }

    .rkm-surat { display:inline-flex; width:32px; height:32px; align-items:center; justify-content:center; border-radius:8px; border:1px solid var(--m-line); background:#fff; color:var(--m-accent); font-size:16px; }
    .rkm-surat:hover { background:#ecfeff; }

    .rkm-del-wrap { display:flex; justify-content:center; gap:.4rem; }
    .rkm-del { display:inline-flex; width:34px; height:34px; align-items:center; justify-content:center; border-radius:9px; border:1px solid; background:#fff; font-size:16px; cursor:pointer; transition:background .15s, opacity .15s; }
    .rkm-del--masuk  { color:var(--m-hadir); border-color:rgba(5,150,105,.3); }
    .rkm-del--masuk:hover:not(:disabled)  { background:rgba(5,150,105,.08); }
    .rkm-del--keluar { color:var(--m-sakit); border-color:rgba(225,29,72,.3); }
    .rkm-del--keluar:hover:not(:disabled) { background:rgba(225,29,72,.07); }
    .rkm-del:disabled { opacity:.3; cursor:not-allowed; }

    .rkm-state { padding:3rem 1rem; text-align:center; color:var(--m-muted); }
    .rkm-state i { display:block; font-size:2.2rem; margin-bottom:.4rem; color:var(--m-faint); }

    .rkm-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem 1rem; padding:1.1rem 2rem; background:var(--m-panel2); border-top:1px solid #eef2f7; }
    .rkm-foot__info { display:flex; align-items:center; gap:.7rem; font-size:13.5px; color:var(--m-text); }
    .rkm-foot__count { display:inline-flex; min-width:26px; height:26px; padding:0 .45rem; align-items:center; justify-content:center; border-radius:7px; background:#cffafe; color:#0e7490; font-family:var(--m-mono); font-size:12.5px; font-weight:700; }
    .rkm-foot__btns { display:flex; gap:.6rem; }
    .rkm-btn { display:inline-flex; align-items:center; gap:.5rem; padding:.55rem 1.2rem; border-radius:999px; border:1px solid var(--m-line); background:#fff; color:var(--m-ink); font-size:13.5px; font-weight:600; cursor:pointer; transition:background .15s; }
    .rkm-btn:hover { background:#f1f5f9; }
    .rkm-btn--accent { background:var(--m-accent); border-color:var(--m-accent); color:#fff; font-weight:700; }
    .rkm-btn--accent:hover { background:#0e7490; }

    @media (max-width:575px) {
        .rkm-head { padding:1.5rem 1.1rem 1.1rem; }
        .rkm-name { font-size:19px; }
        .rkm .modal-body { padding:1rem; }
        .rkm-foot { padding:1rem; }
    }
</style>

<div class="rk">

    <!-- Breadcrumb -->
    <nav class="sdm-crumb" aria-label="breadcrumb">
        <a href="{{ url('sdm') }}">Beranda</a>
        <span class="sdm-crumb-sep">/</span>
        <span>Sistem PKL</span>
        <span class="sdm-crumb-sep">/</span>
        <span class="sdm-crumb-cur">Rekap Absensi</span>
    </nav>

    <!-- Header -->
    <div class="rk-head">
        <div class="rk-head__main">
            <div class="sdm-title-row">
                <span class="sdm-title-ico"><i class="bx bx-calendar-check"></i></span>
                <h1 class="rk-title">
                    Rekap Absensi Peserta
                    <span class="rk-pill">{{ $bulan_label }}</span>
                </h1>
            </div>
            <p class="rk-sub">Rekap kehadiran bulanan peserta magang: Hadir, Izin, Sakit, dan Alpha, lengkap dengan detail absen dan lokasi GPS.</p>
        </div>
        <div class="rk-actions">
            <a href="{{ url()->full() }}" class="rk-btn"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i> Segarkan Data</a>
            <button type="button" class="rk-btn rk-btn--accent" onclick="window.print()"><i class="bx bx-printer"></i> Cetak Halaman</button>
        </div>
    </div>

    <!-- KPI -->
    <div class="rk-kpis">
        @foreach ($kpis as $k)
            @include('partials.stat_card_v2', [
                'colClass' => 'rk-kpis__item',
                'value'    => number_format((int) $k['val'], 0, ',', '.'),
                'label'    => $k['label'],
                'icon'     => $k['icon'],
                'color'    => $k['color'],
                'sub'      => $k['sub'],
            ])
        @endforeach
    </div>

    <!-- Filter -->
    <div class="rk-filter">
        <form method="GET" action="{{ url('sdm/rekap-absensi') }}" data-live-search>
            <div class="rk-filter__grid">
                <div class="rk-field">
                    <label for="fBulan"><i class="bx bx-calendar"></i> Bulan / Periode</label>
                    <input type="month" id="fBulan" name="bulan" class="rk-input" value="{{ $filter_bulan }}">
                </div>
                <div class="rk-field">
                    <label for="fStatus"><i class="bx bx-check-shield"></i> Status Magang</label>
                    <select id="fStatus" name="status" class="rk-input">
                        <option value="semua" {{ $filter_status === 'semua' ? 'selected' : '' }}>Semua Status</option>
                        @foreach ($status_opsi as $val => $label)
                        <option value="{{ $val }}" {{ $filter_status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rk-field">
                    <label for="fUnit"><i class="bx bx-buildings"></i> Unit Kerja</label>
                    <select id="fUnit" name="unit" class="rk-input">
                        <option value="">Semua Unit</option>
                        <option value="Unit 1-2" {{ $filter_unit === 'Unit 1-2' ? 'selected' : '' }}>Unit 1-2</option>
                        <option value="Unit 9" {{ $filter_unit === 'Unit 9' ? 'selected' : '' }}>Unit 9</option>
                    </select>
                </div>
                <div class="rk-field">
                    <label for="fBidang"><i class="bx bx-sitemap"></i> Bidang / Divisi</label>
                    <select id="fBidang" name="bidang" class="rk-input">
                        <option value="">Semua Bidang</option>
                        @foreach ($bidang_list as $b)
                        <option value="{{ $b->id }}" {{ $filter_bidang == $b->id ? 'selected' : '' }}>{{ $b->bidang }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rk-btn rk-btn--accent"><i class="bx bx-filter-alt"></i> Terapkan</button>
                <a href="{{ url('sdm/rekap-absensi') }}" class="rk-iconbtn" title="Reset (kembali ke status Aktif)" aria-label="Reset filter"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i></a>
            </div>

            <div class="rk-filter__row2">
                <div class="rk-search ui-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="q" class="rk-input" placeholder="Cari Nama, Username, atau Asal Sekolah…" value="{{ $filter_search }}" aria-label="Cari peserta">
                </div>
                <div class="rk-quick">
                    <span class="rk-quick__label">Rentang Cepat</span>
                    @foreach ($quick_ranges as $label => $ym)
                    <a href="{{ $url_bulan($ym) }}" class="rk-chip {{ $filter_bulan === $ym ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Rekap -->
    <div class="rk-table-card">
        <div class="rk-table-bar">
            <h2>
                <i class="bx bx-table"></i> Matriks Presensi Peserta Magang
                <em>Total {{ number_format($total_p, 0, ',', '.') }} peserta</em>
            </h2>
            <div class="rk-legend">
                <span style="--c: var(--rk-hadir)">H: Hadir</span>
                <span style="--c: var(--rk-izin)">I: Izin</span>
                <span style="--c: var(--rk-sakit)">S: Sakit</span>
                <span style="--c: var(--rk-alpha)">A: Alpha</span>
            </div>
        </div>

        <div class="rk-wrap">
            <table id="tabelRekap">
                <colgroup>
                    <col style="width: 4%;">   <!-- No -->
                    <col style="width: 22%;">  <!-- Peserta -->
                    <col style="width: 17%;">  <!-- Sekolah -->
                    <col style="width: 14%;">  <!-- Unit & Bidang -->
                    <col style="width: 8%;">   <!-- Status -->
                    <col style="width: 5.5%;"> <!-- Hadir -->
                    <col style="width: 5.5%;"> <!-- Izin -->
                    <col style="width: 5.5%;"> <!-- Sakit -->
                    <col style="width: 5.5%;"> <!-- Alpha -->
                    <col style="width: 7%;">   <!-- % -->
                    <col style="width: 6%;">   <!-- Aksi -->
                </colgroup>
                <thead>
                    <tr>
                        <th class="c">#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah / Instansi</th>
                        <th>Unit &amp; Bidang</th>
                        <th>Status</th>
                        <th class="c h">Hadir</th>
                        <th class="c i">Izin</th>
                        <th class="c s">Sakit</th>
                        <th class="c">Alpha</th>
                        <th class="c">Tingkat</th>
                        <th class="c">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @php $no = $offset + 1; @endphp
                @forelse ($peserta_list as $p)
                    @php
                        $total_rec = $p->total_absensi;
                        $pct = $total_rec > 0 ? round(($p->hadir_count / $total_rec) * 100) : 0;
                        $bar_color = $pct >= 90 ? 'var(--rk-hadir)' : ($pct >= 70 ? 'var(--rk-izin)' : ($pct > 0 ? 'var(--rk-warn)' : 'transparent'));
                        $badge_status = ['Aktif' => 'success', 'Menunggu' => 'warning', 'Selesai' => 'secondary'][$p->status_magang] ?? 'light';
                        $kata = preg_split('/\s+/', trim((string) $p->nama));
                        $inisial = mb_strtoupper(mb_substr($kata[0] ?? '', 0, 1) . mb_substr($kata[1] ?? '', 0, 1));
                    @endphp
                <tr>
                    <td class="c rk-no">{{ $no++ }}</td>
                    <td>
                        <div class="rk-person">
                            <span class="rk-avatar" aria-hidden="true">{{ $inisial }}</span>
                            <div>
                                <b>{{ $p->nama }}</b>
                                <small>{{ $p->username }}</small>
                            </div>
                        </div>
                    </td>
                    <td title="{{ $p->asal_sekolah ?? '-' }}">{{ $p->asal_sekolah ?? '-' }}</td>
                    <td>
                        <span class="rk-unit">{{ $p->unit ?? '-' }}</span>
                        <span>{{ $p->nama_bidang ?? '-' }}</span>
                    </td>
                    <td><span class="rk-badge rk-badge--{{ $badge_status }}">{{ $p->status_magang ?? '-' }}</span></td>
                    <td class="c"><span class="rk-n h {{ $p->hadir_count > 0 ? 'on' : '' }}">{{ $p->hadir_count }}</span></td>
                    <td class="c"><span class="rk-n i {{ $p->izin_count > 0 ? 'on' : '' }}">{{ $p->izin_count }}</span></td>
                    <td class="c"><span class="rk-n s {{ $p->sakit_count > 0 ? 'on' : '' }}">{{ $p->sakit_count }}</span></td>
                    <td class="c"><span class="rk-n a {{ $p->alpha_count > 0 ? 'on' : '' }}">{{ $p->alpha_count }}</span></td>
                    <td class="c">
                        <span class="rk-pct">{{ $pct }}%</span>
                        <div class="rk-bar"><i style="width: {{ $pct }}%; --bar: {{ $bar_color }};"></i></div>
                    </td>
                    <td class="c">
                        <div class="rk-row-actions">
                            <button type="button" class="rk-act" title="Detail" aria-label="Detail absensi {{ $p->nama }}" onclick="showDetail('{{ $p->username }}', '{{ $p->nama }}')">
                                <i class="bx bx-list-ul"></i>
                            </button>
                            <button type="button" class="rk-act rk-act--pdf" title="Export PDF" aria-label="Export PDF {{ $p->nama }}"
                                data-username="{{ $p->username }}"
                                data-nama="{{ $p->nama }}"
                                data-sekolah="{{ $p->asal_sekolah ?? '-' }}"
                                data-unit="{{ $p->unit ?? '-' }}"
                                data-bidang="{{ $p->nama_bidang ?? '-' }}"
                                onclick="exportPdf(this)">
                                <i class="bx bxs-file-pdf"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11">
                        <div class="rk-empty">
                            <i class="bx bx-search-alt"></i>
                            <b>Tidak ada peserta yang cocok</b>
                            Ubah filter atau kata kunci, lalu terapkan lagi.
                        </div>
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="rk-table-foot">
            <div class="rk-showing">
                Menampilkan <b>{{ $tampil_dari }} - {{ $tampil_sampai }}</b> dari <b>{{ number_format($total_p, 0, ',', '.') }}</b> peserta magang
            </div>

            @if ($total_halaman > 1)
            <nav aria-label="Navigasi halaman">
                <ul class="pagination pagination-sm">
                    <li class="page-item {{ $page_num <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $url_base }}1" aria-label="Halaman pertama">«</a>
                    </li>
                    <li class="page-item {{ $page_num <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $url_base . ($page_num - 1) }}" aria-label="Sebelumnya">‹</a>
                    </li>
                    @php
                        $start = max(1, $page_num - 2);
                        $end = min($total_halaman, $page_num + 2);
                    @endphp
                    @if ($start > 1)<li class="page-item disabled"><span class="page-link">...</span></li>@endif
                    @for ($i = $start; $i <= $end; $i++)
                    <li class="page-item {{ $i === $page_num ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url_base . $i }}">{{ $i }}</a>
                    </li>
                    @endfor
                    @if ($end < $total_halaman)<li class="page-item disabled"><span class="page-link">...</span></li>@endif
                    <li class="page-item {{ $page_num >= $total_halaman ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $url_base . ($page_num + 1) }}" aria-label="Berikutnya">›</a>
                    </li>
                    <li class="page-item {{ $page_num >= $total_halaman ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $url_base . $total_halaman }}" aria-label="Halaman terakhir">»</a>
                    </li>
                </ul>
            </nav>
            @endif
        </div>
    </div>

    <!-- Kartu info bawah -->
    <div class="rk-info">
        <div class="rk-info__card">
            <span class="rk-info__icon"><i class="bx bx-check-circle"></i></span>
            <div>
                <h3>Tingkat Kehadiran</h3>
                <p>{{ $persen($sum_h) }} dari seluruh log absensi {{ $bulan_label }} tercatat Hadir.</p>
                <span class="rk-tag">{{ number_format($sum_h, 0, ',', '.') }} dari {{ number_format($sum_all, 0, ',', '.') }} log</span>
            </div>
        </div>
        <div class="rk-info__card">
            <span class="rk-info__icon"><i class="bx bx-file"></i></span>
            <div>
                <h3>Izin &amp; Sakit</h3>
                <p>Klik ikon detail pada tiap peserta untuk melihat surat, status persetujuan, dan keterangan.</p>
                <span class="rk-tag">{{ number_format($sum_i + $sum_s, 0, ',', '.') }} berkas bulan ini</span>
            </div>
        </div>
        <div class="rk-info__card">
            <span class="rk-info__icon"><i class="bx bx-map-pin"></i></span>
            <div>
                <h3>Lokasi &amp; Koreksi Absen</h3>
                <p>Detail absensi menampilkan koordinat GPS masuk dan keluar. Data absen bisa dihapus per tanggal bila perlu dikoreksi.</p>
                <a href="{{ url('sdm/pengaturan/hari-libur') }}" class="rk-tag">{{ number_format($libur_count, 0, ',', '.') }} hari libur · Kelola di Pengaturan › Hari Libur</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade rkm" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable rkm-dialog">
        <div class="modal-content">
            <div class="rkm-head">
                <div class="rkm-avatar">
                    <span id="rkmInisial">--</span>
                    <i class="rkm-avatar__dot"></i>
                </div>
                <div class="rkm-head__main">
                    <div class="rkm-tags">
                        <span class="rkm-tag"><i class="bx bx-calendar"></i> Detail Absensi</span>
                        <span class="rkm-tag rkm-tag--ok"><i class="rkm-dot"></i> <span id="rkmVerif">Memuat…</span></span>
                    </div>
                    <h5 class="rkm-name" id="modalDetailTitle">Detail Absensi</h5>
                    <p class="rkm-meta">
                        <span>Periode: {{ $bulan_label }}</span>
                        <i class="rkm-sep"></i>
                        <span>ID: <span id="rkmId">-</span></span>
                    </p>
                </div>
                <button type="button" class="rkm-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bx bx-x"></i></button>
            </div>

            <div class="modal-body" id="modalDetailBody">
                <div class="rkm-state"><i class="bx bx-loader-alt bx-spin"></i>Memuat data…</div>
            </div>

            <div class="rkm-foot">
                <div class="rkm-foot__info">
                    <span class="rkm-foot__count" id="rkmCount">0</span>
                    Total data absensi tercatat pada sistem
                </div>
                <div class="rkm-foot__btns">
                    <button type="button" class="rkm-btn" id="rkmCetak"><i class="bx bx-printer"></i> Cetak Rekap</button>
                    <button type="button" class="rkm-btn rkm-btn--accent" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function detailHtmlEsc(v) {
    return String(v)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
function detailTruthy(v) {
    return !(v === null || v === undefined || v === '' || v === '0' || v === 0 || v === false);
}
function detailRound5(v) {
    return Math.round(Number(v) * 100000) / 100000;
}
function detailBtnHapus(username, tglRaw, tipe, ada) {
    var isMasuk = (tipe === 'masuk');
    var label = isMasuk ? 'Hapus Masuk' : 'Hapus Keluar';
    var enabled = ada && tglRaw !== '';
    return '<button type="button" class="rkm-del rkm-del--' + tipe + ' btn-hapus-absen"'
        + ' data-user="' + detailHtmlEsc(username) + '"'
        + ' data-tgl="' + detailHtmlEsc(tglRaw) + '"'
        + ' data-tipe="' + tipe + '"'
        + (enabled ? '' : ' disabled')
        + ' title="' + label + '" aria-label="' + label + '">'
        + '<i class="bx bx-trash"></i></button>';
}
function renderDetailTable(rows, username) {
    var hariArr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var html = '<div class="rkm-tablecard"><div class="rkm-scroll"><table class="rkm-table">';
    html += '<thead><tr>'
        + '<th>Tanggal</th><th>Hari</th><th>Masuk</th><th>Keluar</th><th>Status</th>'
        + '<th>Keterangan</th><th>Surat</th><th>Lokasi GPS</th><th>Aksi</th>'
        + '</tr></thead><tbody>';

    if (rows.length === 0) {
        html += '<tr><td colspan="9"><div class="rkm-state"><i class="bx bx-calendar-x"></i>Tidak ada data absensi pada periode ini.</div></td></tr>';
    }

    rows.forEach(function (d) {
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(d.tanggal));
        var tgl = m ? (m[3] + '/' + m[2] + '/' + m[1]) : String(d.tanggal);
        var day = m ? hariArr[new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])).getUTCDay()] : '';
        var tglRaw = m ? (m[1] + '-' + m[2] + '-' + m[3]) : '';

        var jamMasuk = detailTruthy(d.jam_masuk)
            ? '<span class="rkm-time rkm-time--in"><i class="bx bx-time-five"></i>' + String(d.jam_masuk).substr(0, 5) + '</span>'
            : '<span class="rkm-time rkm-time--none">-</span>';
        var jamKeluar = detailTruthy(d.jam_keluar)
            ? '<span class="rkm-time rkm-time--out"><i class="bx bx-log-out"></i>' + String(d.jam_keluar).substr(0, 5) + '</span>'
            : '<span class="rkm-time rkm-time--none">-</span>';

        var st = String(d.status || '').toLowerCase();
        var stCls = ['hadir','izin','sakit','alpha'].indexOf(st) > -1 ? st : 'alpha';
        var statusSub = '';
        if ((d.status === 'Izin' || d.status === 'Sakit') && d.approval_status !== null && d.approval_status !== undefined) {
            var ap = (d.approval_status === 'Pending' || d.approval_status === 'Ditolak') ? d.approval_status : 'Disetujui';
            statusSub = '<span class="rkm-appr rkm-appr--' + ap + '">' + ap + '</span>';
        }

        var ket = (d.keterangan === null || d.keterangan === undefined || d.keterangan === '')
            ? '<span class="rkm-muted">-</span>' : detailHtmlEsc(d.keterangan);

        var surat = detailTruthy(d.file_surat)
            ? '<a href="{{ asset('uploads/surat_absensi') }}/' + detailHtmlEsc(d.file_surat) + '" target="_blank" class="rkm-surat" title="Buka Surat" data-ui-preview data-preview-title="Surat absensi"><i class="bx bx-file"></i></a>'
            : '<span class="rkm-muted">-</span>';

        var gps = '';
        if (detailTruthy(d.lat_masuk)) {
            gps += '<a class="in" href="https://maps.google.com/?q=' + d.lat_masuk + ',' + d.lng_masuk + '" target="_blank" rel="noopener"><i class="bx bx-map-pin"></i>Masuk: ' + detailRound5(d.lat_masuk) + ',' + detailRound5(d.lng_masuk) + '</a>';
        }
        if (detailTruthy(d.lat_keluar)) {
            gps += '<a class="out" href="https://maps.google.com/?q=' + d.lat_keluar + ',' + d.lng_keluar + '" target="_blank" rel="noopener"><i class="bx bx-map-pin"></i>Keluar: ' + detailRound5(d.lat_keluar) + ',' + detailRound5(d.lng_keluar) + '</a>';
        }
        gps = gps ? '<div class="rkm-gps">' + gps + '</div>' : '<span class="rkm-muted">-</span>';

        var adaMasuk = detailTruthy(d.jam_masuk) || detailTruthy(d.lat_masuk);
        var adaKeluar = detailTruthy(d.jam_keluar) || detailTruthy(d.lat_keluar);
        var aksi = '<div class="rkm-del-wrap">'
            + detailBtnHapus(username, tglRaw, 'masuk', adaMasuk)
            + detailBtnHapus(username, tglRaw, 'keluar', adaKeluar)
            + '</div>';

        html += '<tr>'
            + '<td><span class="rkm-chip rkm-chip--date">' + tgl + '</span></td>'
            + '<td><span class="rkm-chip">' + day + '</span></td>'
            + '<td>' + jamMasuk + '</td>'
            + '<td>' + jamKeluar + '</td>'
            + '<td><span class="rkm-status rkm-status--' + stCls + '">' + detailHtmlEsc(d.status) + '</span>' + statusSub + '</td>'
            + '<td>' + ket + '</td>'
            + '<td>' + surat + '</td>'
            + '<td>' + gps + '</td>'
            + '<td>' + aksi + '</td>'
            + '</tr>';
    });

    html += '</tbody></table></div></div>';
    return html;
}
function exportPdf(btn) {
    var d = btn.dataset;
    var bulan = @json($filter_bulan);
    var bulanLabel = @json($bulan_label);
    var hariArr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var origHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>';

    var loadLogo = new Promise(function (resolve) {
        var img = new Image();
        img.onload = function () { resolve(img); };
        img.onerror = function () { resolve(null); };
        img.src = '{{ asset('assets/images/logo.png') }}';
    });

    Promise.all([
        fetch('{{ url('sdm/rekap-absensi/ajax-detail') }}?user=' + encodeURIComponent(d.username) + '&bulan=' + encodeURIComponent(bulan))
            .then(function (r) { return r.json(); }),
        loadLogo
    ])
    .then(function (res) {
        var rows = res[0];
        var logo = res[1];
        if (!Array.isArray(rows)) rows = [];

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        var pageW = doc.internal.pageSize.getWidth();

        // Hitung ringkasan
        var cnt = { Hadir: 0, Izin: 0, Sakit: 0, Alpha: 0 };
        rows.forEach(function (x) { if (cnt.hasOwnProperty(x.status)) cnt[x.status]++; });
        var total = rows.length;
        var pct = total > 0 ? Math.round((cnt.Hadir / total) * 100) : 0;

        // Logo (pojok kiri atas)
        if (logo) {
            var logoW = 35;   // lebar logo (mm), tinggi mengikuti proporsi
            var maxH = 24;    // batas tinggi supaya tidak keluar dari header
            var ratio = logo.naturalWidth / logo.naturalHeight;
            var lw = logoW, lh = logoW / ratio;
            if (lh > maxH) { lh = maxH; lw = maxH * ratio; }
            // Tengah vertikal = titik tengah antara "REKAP ABSENSI PESERTA" dan "Periode"
            var logoCenterY = 18.5;
            doc.addImage(logo, 'PNG', 14, logoCenterY - (lh / 2), lw, lh);
        }

        // Judul
        doc.setTextColor(0);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(15);
        doc.text('REKAP ABSENSI PESERTA', pageW / 2, 16.5, { align: 'center' });
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Periode: ' + bulanLabel, pageW / 2, 23, { align: 'center' });

        // Garis pemisah di bawah header
        doc.setDrawColor(33, 37, 41);
        doc.setLineWidth(0.6);
        doc.line(14, 32, pageW - 14, 32);

        // Identitas peserta (kiri)
        var y = 42;
        var info = [
            ['Nama', d.nama],
            ['Username', d.username],
            ['Sekolah / Instansi', d.sekolah],
            ['Unit', d.unit],
            ['Bidang', d.bidang]
        ];
        info.forEach(function (it) {
            doc.setFont('helvetica', 'bold');
            doc.text(it[0], 14, y);
            doc.setFont('helvetica', 'normal');
            var lines = doc.splitTextToSize(': ' + it[1], 72);
            doc.text(lines, 50, y);
            y += 5.5 * lines.length;
        });

        // Ringkasan kehadiran (kanan)
        var ry = 42;
        doc.setFont('helvetica', 'bold');
        doc.text('Ringkasan', 138, ry);
        doc.setFont('helvetica', 'normal');
        [
            'Hadir : ' + cnt.Hadir,
            'Izin : ' + cnt.Izin,
            'Sakit : ' + cnt.Sakit,
            'Alpha : ' + cnt.Alpha,
            'Persentase Hadir : ' + pct + '%'
        ].forEach(function (t) {
            ry += 5.5;
            doc.text(t, 138, ry);
        });

        var tableStartY = Math.max(y, ry + 5.5) + 4;

        // Tabel detail
        var body = rows.map(function (x, i) {
            var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(x.tanggal));
            var tgl = m ? (m[3] + '/' + m[2] + '/' + m[1]) : String(x.tanggal);
            var hari = m ? hariArr[new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])).getUTCDay()] : '';
            var status = x.status || '-';
            if ((x.status === 'Izin' || x.status === 'Sakit') && x.approval_status) {
                status += ' (' + x.approval_status + ')';
            }
            return [
                i + 1,
                tgl,
                hari,
                detailTruthy(x.jam_masuk) ? String(x.jam_masuk).substr(0, 5) : '-',
                detailTruthy(x.jam_keluar) ? String(x.jam_keluar).substr(0, 5) : '-',
                status,
                (x.keterangan === null || x.keterangan === undefined || x.keterangan === '') ? '-' : String(x.keterangan)
            ];
        });

        if (body.length === 0) {
            body.push([{ content: 'Tidak ada data absensi pada periode ini.', colSpan: 7, styles: { halign: 'center' } }]);
        }

        doc.autoTable({
            startY: tableStartY,
            margin: { left: 14, right: 14 },
            head: [['No', 'Tanggal', 'Hari', 'Masuk', 'Keluar', 'Status', 'Keterangan']],
            body: body,
            theme: 'grid',
            styles: { fontSize: 9, cellPadding: 2, valign: 'middle' },
            headStyles: { fillColor: [33, 37, 41], textColor: 255, halign: 'center' },
            columnStyles: {
                0: { halign: 'center', cellWidth: 10 },
                1: { halign: 'center', cellWidth: 24 },
                2: { halign: 'center', cellWidth: 20 },
                3: { halign: 'center', cellWidth: 18 },
                4: { halign: 'center', cellWidth: 18 },
                5: { halign: 'center', cellWidth: 36 },
                6: { cellWidth: 'auto' }
            },
            didParseCell: function (data) {
                // Strip "-" di kolom Keterangan dibuat center
                if (data.section === 'body' && data.column.index === 6 && data.cell.raw === '-') {
                    data.cell.styles.halign = 'center';
                }
                if (data.section === 'body' && data.column.index === 5 && typeof data.cell.raw === 'string') {
                    if (data.cell.raw.indexOf('Hadir') === 0) data.cell.styles.textColor = [25, 135, 84];
                    else if (data.cell.raw.indexOf('Izin') === 0) data.cell.styles.textColor = [180, 130, 0];
                    else if (data.cell.raw.indexOf('Sakit') === 0) data.cell.styles.textColor = [220, 53, 69];
                    else if (data.cell.raw.indexOf('Alpha') === 0) data.cell.styles.textColor = [108, 117, 125];
                }
            },
            didDrawPage: function () {
                var pageH = doc.internal.pageSize.getHeight();
                doc.setFontSize(8);
                doc.setTextColor(120);
                doc.text('Dicetak: ' + new Date().toLocaleString('id-ID'), 14, pageH - 8);
                doc.text('Halaman ' + doc.internal.getCurrentPageInfo().pageNumber, pageW - 14, pageH - 8, { align: 'right' });
                doc.setTextColor(0);
            }
        });

        var safeNama = String(d.nama).replace(/[^\w\-]+/g, '_');
        doc.save('Rekap-Absensi_' + safeNama + '_' + bulan + '.pdf');
    })
    .catch(function (err) {
        console.error(err);
        alert('Gagal membuat PDF. Coba lagi.');
    })
    .finally(function () {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    });
}
var detailAdaPerubahan = false;
var detailUsernameAktif = '';

function loadDetailAbsensi(username) {
    const bulan = @json($filter_bulan);
    document.getElementById('modalDetailBody').innerHTML = '<div class="rkm-state"><i class="bx bx-loader-alt bx-spin"></i>Memuat data…</div>';

    // Load detail via ajax endpoint
    return fetch('{{ url('sdm/rekap-absensi/ajax-detail') }}?user=' + encodeURIComponent(username) + '&bulan=' + encodeURIComponent(bulan))
    .then(r => r.json())
    .then(rows => {
        if (!Array.isArray(rows)) rows = [];
        document.getElementById('modalDetailBody').innerHTML = renderDetailTable(rows, username);
        document.getElementById('rkmCount').textContent = rows.length;

        var hadir = rows.filter(function (x) { return x.status === 'Hadir'; }).length;
        document.getElementById('rkmVerif').textContent = rows.length
            ? 'Kehadiran ' + Math.round((hadir / rows.length) * 100) + '%'
            : 'Belum ada data';
    });
}

function showDetail(username, nama) {
    detailAdaPerubahan = false;
    detailUsernameAktif = username;

    var kata = String(nama).trim().split(/\s+/);
    document.getElementById('modalDetailTitle').textContent = nama;
    document.getElementById('rkmInisial').textContent = ((kata[0] || '').charAt(0) + (kata[1] || '').charAt(0)).toUpperCase();
    document.getElementById('rkmId').textContent = username;
    document.getElementById('rkmVerif').textContent = 'Memuat…';
    document.getElementById('rkmCount').textContent = '0';

    var modalEl = document.getElementById('modalDetail');
    var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();

    loadDetailAbsensi(username);
}

function hapusAbsen(btn) {
    var username = btn.dataset.user;
    var tgl = btn.dataset.tgl;
    var tipe = btn.dataset.tipe;
    var label = (tipe === 'masuk') ? 'absen MASUK' : 'absen KELUAR';
    var tglTampil = tgl.split('-').reverse().join('/');

    var doDelete = function () {
        btn.disabled = true;

        fetch('{{ url('sdm/rekap-absensi/hapus-absen') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ username: username, tanggal: tgl, tipe: tipe })
        })
        .then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (j) {
                return { ok: r.ok, body: j };
            });
        })
        .then(function (res) {
            if (!res.ok || !res.body.success) {
                throw new Error(res.body.message || 'Gagal menghapus data.');
            }
            detailAdaPerubahan = true;
            return loadDetailAbsensi(username);
        })
        .catch(function (err) {
            alert(err.message || 'Gagal menghapus data.');
            btn.disabled = false;
        });
    };

    var message = 'Hapus data ' + label + ' tanggal ' + tglTampil + '? Tindakan ini tidak bisa dibatalkan.';
    if (window.LiquidGlass && typeof window.LiquidGlass.confirm === 'function') {
        window.LiquidGlass.confirm({
            title: 'Hapus Data Absensi?',
            message: message,
            okText: 'Hapus',
            theme: 'rejected',
            icon: 'warning'
        }).then(function (res) {
            if (res && res.confirmed) doDelete();
        });
    } else if (window.confirm(message)) {
        doDelete();
    }
}

// Klik tombol hapus di dalam modal detail (event delegation)
document.getElementById('modalDetailBody').addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-hapus-absen');
    if (!btn || btn.disabled) return;
    hapusAbsen(btn);
});

// Tombol "Cetak Rekap" di footer modal = export PDF peserta yang sedang dibuka
document.getElementById('rkmCetak').addEventListener('click', function () {
    var pdfBtn = document.querySelector('.rk-act--pdf[data-username="' + CSS.escape(detailUsernameAktif) + '"]');
    if (pdfBtn) exportPdf(pdfBtn);
});

// Setelah modal ditutup, reload halaman supaya angka rekap ikut ter-update
document.getElementById('modalDetail').addEventListener('hidden.bs.modal', function () {
    if (detailAdaPerubahan) {
        location.reload();
    }
});
</script>
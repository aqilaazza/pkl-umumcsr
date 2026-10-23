@php
    use Illuminate\Support\Facades\DB;

    $search     = $search ?? '';
    $page       = (int) ($page ?? 1);
    $total      = (int) ($total ?? 0);
    $total_page = (int) ($total_page ?? 1);
    $per_page   = (int) ($per_page ?? 15);
    $offset     = (int) ($offset ?? 0);

    $tgl_indo = function ($t) {
        if (empty($t) || strpos((string) $t, '0000-00-00') === 0 || !($ts = strtotime((string) $t))) return '-';
        return date('d/m/Y', $ts);
    };
    $fmtNum = fn ($v) => number_format((int) $v, 0, ',', '.');
    $fmtPct = fn ($v) => rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',') . '%';

    $inisial = function ($nama) {
        $kata = preg_split('/\s+/', trim((string) $nama));
        $out  = mb_substr($kata[0] ?? '', 0, 1);
        if (count($kata) > 1) {
            $out .= mb_substr($kata[1], 0, 1);
        }
        return mb_strtoupper($out ?: '?');
    };

    // Ringkasan seluruh sertifikat (tidak terpengaruh pencarian)
    $ringkas = DB::table('sertifikat_magang')
        ->selectRaw('COUNT(*) as total')
        ->selectRaw("SUM(CASE WHEN status='Digital' THEN 1 ELSE 0 END) as digital")
        ->selectRaw("SUM(CASE WHEN status='Sudah Upload' THEN 1 ELSE 0 END) as scan")
        ->first();
    $kTotal   = (int) ($ringkas->total ?? 0);
    $kDigital = (int) ($ringkas->digital ?? 0);
    $kScan    = (int) ($ringkas->scan ?? 0);
    $kBelum   = max(0, $kTotal - $kDigital - $kScan);
    $pctOf    = fn ($n) => $kTotal > 0 ? round($n / $kTotal * 100, 1) : 0;

    $kpis = [
        ['tone' => 'cyan',  'icon' => 'bx bx-folder-open',  'chip' => 'Terdata',        'chip_icon' => 'bx bx-check-circle', 'eyebrow' => 'Total Data Terdaftar',   'val' => $kTotal,   'unit' => 'Sertifikat', 'f_label' => 'Sesuai pencarian', 'f_val' => $fmtNum($total) . ' data'],
        ['tone' => 'red',   'icon' => 'bx bx-cloud-upload', 'chip' => 'Perlu lengkap',  'chip_icon' => 'bx bx-error-circle', 'eyebrow' => 'Belum Upload Scan',      'val' => $kBelum,   'unit' => 'Berkas',     'f_label' => 'Rasio tertunda',   'f_val' => $fmtPct($pctOf($kBelum))],
        ['tone' => 'green', 'icon' => 'bx bx-qr',           'chip' => 'Terbit digital', 'chip_icon' => 'bx bx-check-circle', 'eyebrow' => 'Digital Terverifikasi',  'val' => $kDigital, 'unit' => 'Sertifikat', 'f_label' => 'Rasio digital',    'f_val' => $fmtPct($pctOf($kDigital))],
        ['tone' => 'blue',  'icon' => 'bx bx-check-shield', 'chip' => 'Arsip scan',     'chip_icon' => 'bx bx-file',         'eyebrow' => 'Sudah Upload Scan',      'val' => $kScan,    'unit' => 'Sertifikat', 'f_label' => 'Rasio scan TTD',   'f_val' => $fmtPct($pctOf($kScan))],
    ];

    // Hari kerja (exclude libur pekan & tanggal merah) untuk "Durasi"
    $libur_pekan = DB::table('libur_pekan')->pluck('hari_index')->map(fn ($v) => (int) $v)->all();
    $hari_libur  = DB::table('hari_libur')->pluck('tanggal')->all();
    $hitungHariKerja = function ($tgl_masuk, $tgl_keluar) use ($libur_pekan, $hari_libur) {
        if (empty($tgl_masuk) || empty($tgl_keluar) || $tgl_masuk == '0000-00-00' || $tgl_keluar == '0000-00-00') {
            return 0;
        }
        $start = new DateTime($tgl_masuk);
        $end   = new DateTime($tgl_keluar);
        if ($end < $start) {
            return 0;
        }
        $end->modify('+1 day');
        $n = 0;
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $d) {
            if (in_array((int) $d->format('w'), $libur_pekan)) continue;
            if (in_array($d->format('Y-m-d'), $hari_libur)) continue;
            $n++;
        }
        return $n;
    };

    // Sertifikat terbaru (kartu info bawah)
    $terbaru = DB::table('sertifikat_magang as sm')
        ->join('peserta as p', 'sm.username', '=', 'p.username')
        ->orderByDesc('sm.id')
        ->limit(4)
        ->get(['p.nama', 'sm.status', 'sm.nomor_surat', 'sm.tgl_cetak', 'sm.generated_at']);

    $urlHalaman = fn ($p) => url('sdm/edit-sertifikat') . '?' . http_build_query(['p' => $p, 'q' => $search]);

    $halaman = [];
    if ($total_page > 1) {
        $set = array_unique(array_filter([1, $page - 1, $page, $page + 1, $total_page], fn ($n) => $n >= 1 && $n <= $total_page));
        sort($set);
        $prev = 0;
        foreach ($set as $n) {
            if ($prev && $n - $prev > 1) {
                $halaman[] = '...';
            }
            $halaman[] = $n;
            $prev = $n;
        }
    }

    $no = $offset + 1;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">

<style>
/* ===== Edit Data Sertifikat — versi light (scoped .pkl-cs) ===== */
.pkl-cs {
    --cs-primary: var(--pkl-primary, #0891b2);
    --cs-primary-dark: var(--pkl-primary-dark, #0e7490);
    --cs-primary-50: var(--pkl-primary-50, #ecfeff);
    --cs-ink: var(--pkl-ink, #0f172a);
    --cs-text: var(--pkl-text, #334155);
    --cs-muted: var(--pkl-muted, #64748b);
    --cs-soft: var(--pkl-soft, #94a3b8);
    --cs-line: var(--pkl-line, #e2e8f0);
    --cs-line-soft: var(--pkl-line-soft, #f1f5f9);
    --cs-bg: var(--pkl-bg, #f8fafc);
    --cs-head: var(--pkl-font-head, 'Inter', system-ui, sans-serif);
    --cs-body: var(--pkl-font-body, 'Inter', system-ui, sans-serif);
    --cs-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    --cs-ok: #059669;
    --cs-warn: #d97706;
    --cs-bad: #dc2626;
    --cs-blue: #2563eb;
}
.pkl-cs *, .pkl-cs *::before, .pkl-cs *::after { box-sizing: border-box; }
.pkl-cs a { text-decoration: none; }
.pkl-cs :focus-visible { outline: 2px solid var(--cs-primary); outline-offset: 2px; }

/* Header */
.pkl-cs .cs-crumb { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 14px; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--cs-muted); }
.pkl-cs .cs-crumb a { color: var(--cs-muted); }
.pkl-cs .cs-crumb a:hover { color: var(--cs-primary-dark); }
.pkl-cs .cs-crumb strong { color: var(--cs-ink); font-weight: 700; }
.pkl-cs .cs-crumb__now { display: inline-flex; align-items: center; gap: 6px; color: var(--cs-primary-dark); font-weight: 700; }
.pkl-cs .cs-crumb__now::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--cs-primary); }
.pkl-cs .cs-head { margin-bottom: 22px; }
.pkl-cs .cs-head__row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px 24px; }
.pkl-cs .cs-head__line { display: flex; flex-wrap: nowrap; align-items: center; gap: 12px; min-width: 0; }
.pkl-cs .cs-head__icon { flex: none; display: grid; place-items: center; width: 40px; height: 40px; font-size: 21px; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .25); border-radius: 11px; }
.pkl-cs .cs-title { margin: 0; white-space: nowrap; font-family: var(--cs-head); font-size: clamp(22px, 2.6vw, 30px); font-weight: 800; letter-spacing: -.02em; line-height: 1.15; color: var(--cs-ink); }
.pkl-cs .cs-pill { flex: none; display: inline-flex; align-items: center; gap: 6px; padding: 3px 11px; white-space: nowrap; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .3); border-radius: 999px; }
.pkl-cs .cs-desc { margin: 12px 0 0; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 12.5px; line-height: 1.5; color: var(--cs-muted); }
.pkl-cs .cs-head__actions { display: flex; flex-wrap: wrap; gap: 10px; }

/* Tombol */
.pkl-cs .cs-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; padding: 0 18px;
    font-family: var(--cs-body); font-size: 13px; font-weight: 700; color: var(--cs-ink); background: #fff;
    border: 1px solid var(--cs-line); border-radius: 10px; cursor: pointer; white-space: nowrap;
    transition: box-shadow .15s, background .15s, border-color .15s, color .15s;
}
.pkl-cs .cs-btn i { font-size: 17px; }
.pkl-cs .cs-btn:hover { background: var(--cs-bg); border-color: #cbd5e1; color: var(--cs-ink); }
.pkl-cs .cs-btn--primary { color: #fff; background: var(--cs-primary); border-color: var(--cs-primary); box-shadow: 0 6px 16px -8px rgba(8, 145, 178, .8); }
.pkl-cs .cs-btn--primary:hover { color: #fff; background: var(--cs-primary-dark); border-color: var(--cs-primary-dark); }
.pkl-cs .cs-btn--icon { width: 42px; padding: 0; color: var(--cs-muted); }
.pkl-cs .cs-btn--icon:hover { color: var(--cs-primary-dark); border-color: var(--cs-primary); background: var(--cs-primary-50); }

/* KPI */
.pkl-cs .cs-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.pkl-cs .cs-kpi { --t: var(--cs-primary); --t-bg: var(--cs-primary-50); --t-line: rgba(8, 145, 178, .28); position: relative; overflow: hidden; padding: 18px 20px 16px; background: #fff; border: 1px solid var(--cs-line); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
.pkl-cs .cs-kpi::before { content: ''; position: absolute; top: -70px; right: -70px; width: 190px; height: 190px; border-radius: 50%; background: radial-gradient(circle, var(--t) 0%, transparent 70%); opacity: .12; pointer-events: none; }
.pkl-cs .cs-kpi--red   { --t: var(--cs-bad);  --t-bg: #fef2f2; --t-line: #fecaca; }
.pkl-cs .cs-kpi--green { --t: var(--cs-ok);   --t-bg: #ecfdf5; --t-line: #a7f3d0; }
.pkl-cs .cs-kpi--blue  { --t: var(--cs-blue); --t-bg: #eff6ff; --t-line: #bfdbfe; }
.pkl-cs .cs-kpi__top { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 16px; }
.pkl-cs .cs-kpi__icon { display: grid; place-items: center; width: 40px; height: 40px; font-size: 20px; color: var(--t); background: var(--t-bg); border: 1px solid var(--t-line); border-radius: 11px; }
.pkl-cs .cs-kpi__chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; font-size: 10.5px; font-weight: 800; color: var(--t); background: var(--t-bg); border: 1px solid var(--t-line); border-radius: 999px; white-space: nowrap; }
.pkl-cs .cs-kpi__chip i { font-size: 13px; }
.pkl-cs .cs-kpi__eyebrow { position: relative; display: block; font-family: var(--cs-mono); font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--cs-muted); }
.pkl-cs .cs-kpi__val { position: relative; margin-top: 6px; font-size: 34px; font-weight: 800; line-height: 1.1; letter-spacing: -.02em; color: var(--cs-ink); font-variant-numeric: tabular-nums; }
.pkl-cs .cs-kpi--red .cs-kpi__val { color: var(--cs-bad); }
.pkl-cs .cs-kpi__val small { margin-left: 8px; font-size: 13px; font-weight: 500; letter-spacing: 0; color: var(--cs-muted); }
.pkl-cs .cs-kpi__foot { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--cs-line-soft); font-family: var(--cs-mono); font-size: 10.5px; color: var(--cs-muted); }
.pkl-cs .cs-kpi__foot b { font-weight: 700; color: var(--t); }

/* Panel & pencarian */
.pkl-cs .cs-panel { background: #fff; border: 1px solid var(--cs-line); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
.pkl-cs .cs-toolbar { padding: 16px 18px; margin-bottom: 20px; background: var(--cs-bg); }
.pkl-cs .cs-toolbar__row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
.pkl-cs .cs-search { position: relative; flex: 1 1 320px; min-width: 220px; }
.pkl-cs .cs-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--cs-muted); pointer-events: none; }
.pkl-cs .cs-input {
    height: 44px; width: 100%; padding: 0 14px 0 42px; font-family: var(--cs-body); font-size: 13.5px; font-weight: 500; color: var(--cs-ink);
    background: #fff; border: 1px solid var(--cs-line); border-radius: 10px; transition: border-color .15s, box-shadow .15s;
}
.pkl-cs .cs-input::placeholder { color: var(--cs-soft); }
.pkl-cs .cs-input:focus { outline: 0; border-color: var(--cs-primary); box-shadow: 0 0 0 3px rgba(8, 145, 178, .15); }
.pkl-cs .cs-toolbar .cs-btn { height: 44px; }
.pkl-cs .cs-toolbar .cs-btn--icon { width: 44px; }

/* Panel daftar */
.pkl-cs .cs-list__head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 22px; background: var(--cs-bg); border-bottom: 1px solid var(--cs-line); border-radius: 14px 14px 0 0; }
.pkl-cs .cs-list__title { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
.pkl-cs .cs-list__title h2 { margin: 0; font-family: var(--cs-head); font-size: 14.5px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-list__tag { padding: 2px 9px; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 700; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .25); border-radius: 5px; }
.pkl-cs .cs-legend { display: flex; flex-wrap: wrap; gap: 16px; font-size: 12px; color: var(--cs-muted); }
.pkl-cs .cs-legend span { display: inline-flex; align-items: center; gap: 7px; }
.pkl-cs .cs-legend i { width: 8px; height: 8px; border-radius: 50%; }

.pkl-cs .cs-table-wrap { overflow-x: auto; }
.pkl-cs .cs-table { width: 100%; min-width: 980px; border-collapse: collapse; }
.pkl-cs .cs-table th {
    padding: 14px 18px; text-align: center; white-space: nowrap; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 800;
    letter-spacing: .08em; text-transform: uppercase; color: var(--cs-muted); background: #fff; border-bottom: 1px solid var(--cs-line);
}
.pkl-cs .cs-table td { padding: 16px 18px; vertical-align: middle; font-family: var(--cs-body); font-size: 13px; color: var(--cs-text); border-bottom: 1px solid var(--cs-line-soft); }
.pkl-cs .cs-table tbody tr { transition: background .15s; }
.pkl-cs .cs-table tbody tr:hover { background: var(--cs-bg); }
.pkl-cs .cs-table tbody tr:last-child td { border-bottom: 0; }
.pkl-cs .cs-table td { text-align: center; }
.pkl-cs .cs-table td:nth-child(2) { text-align: left; }
.pkl-cs .cs-no { font-family: var(--cs-mono); font-size: 12px; font-weight: 600; color: var(--cs-soft); }

.pkl-cs .cs-person { display: flex; align-items: center; gap: 12px; min-width: 220px; }
.pkl-cs .cs-avatar { flex: none; width: 38px; height: 38px; display: grid; place-items: center; font-size: 12px; font-weight: 800; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .3); border-radius: 50%; }
.pkl-cs .cs-person b { display: block; font-size: 13.5px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-id { display: inline-block; margin-top: 4px; padding: 1px 8px; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 700; border-radius: 5px; border: 1px solid; }
.pkl-cs .cs-id--ok { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
.pkl-cs .cs-id--bad { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
.pkl-cs .cs-sub { display: block; margin-top: 3px; font-size: 12px; color: var(--cs-muted); }
.pkl-cs .cs-inst { min-width: 200px; max-width: 260px; }
.pkl-cs .cs-inst b { font-weight: 700; color: var(--cs-ink); }
.pkl-cs .cs-inst .cs-sub { display: flex; align-items: center; justify-content: center; gap: 6px; }
.pkl-cs .cs-inst .cs-sub i { color: var(--cs-primary); font-size: 14px; }
.pkl-cs .cs-unit { min-width: 160px; }
.pkl-cs .cs-unit__tag { display: inline-block; padding: 2px 9px; font-family: var(--cs-mono); font-size: 11px; font-weight: 600; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .22); border-radius: 5px; }
.pkl-cs .cs-unit__tag--none { color: var(--cs-soft); background: var(--cs-line-soft); border-color: var(--cs-line); }
.pkl-cs .cs-unit .cs-sub { font-family: var(--cs-mono); font-size: 11.5px; color: var(--cs-soft); margin-top: 6px; }
.pkl-cs .cs-date { white-space: nowrap; font-size: 13px; font-weight: 600; color: var(--cs-ink); font-variant-numeric: tabular-nums; }
.pkl-cs .cs-date span { display: block; margin-top: 2px; }
.pkl-cs .cs-date em { display: block; margin-top: 4px; font-style: normal; font-size: 11.5px; font-weight: 400; color: var(--cs-soft); }

.pkl-cs .cs-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 800; letter-spacing: .04em; border-radius: 999px; border: 1px solid; white-space: nowrap; }
.pkl-cs .cs-badge--ok { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
.pkl-cs .cs-badge--digital { color: var(--cs-primary-dark); background: var(--cs-primary-50); border-color: rgba(8, 145, 178, .35); }
.pkl-cs .cs-badge--wait { color: #b45309; background: #fffbeb; border-color: #fde68a; }
.pkl-cs .cs-status .cs-sub { font-size: 11.5px; font-family: var(--cs-mono); }

.pkl-cs .cs-act {
    display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px; font-family: var(--cs-body); font-size: 12.5px; font-weight: 700;
    color: var(--cs-primary-dark); background: #fff; border: 1px solid rgba(8, 145, 178, .35); border-radius: 9px; cursor: pointer; white-space: nowrap;
    transition: background .15s, border-color .15s;
}
.pkl-cs .cs-act i { font-size: 16px; }
.pkl-cs .cs-act:hover { background: var(--cs-primary-50); border-color: var(--cs-primary); }

.pkl-cs .cs-empty { padding: 44px 20px; text-align: center; color: var(--cs-soft); font-size: 13.5px; }
.pkl-cs .cs-empty i { display: block; margin-bottom: 8px; font-size: 34px; }

/* Footer panel + pagination */
.pkl-cs .cs-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding: 14px 22px; border-top: 1px solid var(--cs-line); background: var(--cs-bg); border-radius: 0 0 14px 14px; }
.pkl-cs .cs-foot__info { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; font-family: var(--cs-mono); font-size: 11px; color: var(--cs-muted); }
.pkl-cs .cs-foot__info b { color: var(--cs-ink); }
.pkl-cs .cs-foot__info span + span { padding-left: 14px; border-left: 1px solid var(--cs-line); }
.pkl-cs .cs-pager { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
.pkl-cs .cs-pg {
    min-width: 34px; height: 34px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: var(--cs-ink); background: #fff; border: 1px solid var(--cs-line); border-radius: 8px; transition: border-color .15s, color .15s;
}
.pkl-cs .cs-pg i { font-size: 17px; }
.pkl-cs a.cs-pg:hover { border-color: var(--cs-primary); color: var(--cs-primary-dark); }
.pkl-cs .cs-pg.is-on { color: #fff; background: var(--cs-primary); border-color: var(--cs-primary); }
.pkl-cs .cs-pg.is-off { color: var(--cs-soft); background: var(--cs-bg); pointer-events: none; }
.pkl-cs .cs-pg--gap { border: 0; background: transparent; min-width: 20px; padding: 0; color: var(--cs-soft); }

/* Kartu info bawah */
.pkl-cs .cs-info { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 16px; margin-top: 20px; }
.pkl-cs .cs-card { padding: 20px 22px; background: #fff; border: 1px solid var(--cs-line); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
.pkl-cs .cs-card__head { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.pkl-cs .cs-card__icon { flex: none; display: grid; place-items: center; width: 38px; height: 38px; font-size: 19px; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .25); border-radius: 10px; }
.pkl-cs .cs-card__head h3 { margin: 0; font-family: var(--cs-head); font-size: 14.5px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-card__head p { margin: 2px 0 0; font-size: 12px; color: var(--cs-muted); }
.pkl-cs .cs-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; padding: 14px; background: var(--cs-bg); border: 1px solid var(--cs-line); border-radius: 12px; }
.pkl-cs .cs-step b { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 12.5px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-step b span { display: grid; place-items: center; width: 20px; height: 20px; font-family: var(--cs-mono); font-size: 10.5px; color: #fff; background: var(--cs-primary); border-radius: 50%; }
.pkl-cs .cs-step p { margin: 0; font-size: 12px; line-height: 1.5; color: var(--cs-muted); }
.pkl-cs .cs-note { display: flex; gap: 8px; align-items: flex-start; margin-top: 14px; font-size: 12px; line-height: 1.5; color: var(--cs-muted); }
.pkl-cs .cs-note i { color: var(--cs-primary); font-size: 16px; margin-top: 1px; }
.pkl-cs .cs-feed { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
.pkl-cs .cs-feed li { display: flex; gap: 12px; align-items: flex-start; }
.pkl-cs .cs-feed__ic { flex: none; display: grid; place-items: center; width: 28px; height: 28px; font-size: 15px; color: var(--c); background: var(--cb); border-radius: 8px; }
.pkl-cs .cs-feed b { display: block; font-size: 12.5px; font-weight: 700; color: var(--cs-ink); }
.pkl-cs .cs-feed small { display: block; margin-top: 2px; font-family: var(--cs-mono); font-size: 10.5px; color: var(--cs-muted); overflow-wrap: anywhere; }
.pkl-cs .cs-feed__none { font-size: 12.5px; color: var(--cs-soft); }

/* Modal edit (di luar .pkl-cs karena dipindah Bootstrap ke body) */
#modalEditSertif .modal-content { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
#modalEditSertif .modal-header { background: #0891b2; }
#modalEditSertif .form-control:focus, #modalEditSertif .form-select:focus { border-color: #0891b2; box-shadow: 0 0 0 3px rgba(8, 145, 178, .15); }
#modalEditSertif .btn-cs { color: #fff; background: #0891b2; border-color: #0891b2; font-weight: 700; }
#modalEditSertif .btn-cs:hover { color: #fff; background: #0e7490; border-color: #0e7490; }

@media (max-width: 1199.98px) {
    .pkl-cs .cs-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .pkl-cs .cs-info { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .pkl-cs .cs-steps { grid-template-columns: 1fr; }
    .pkl-cs .cs-head__line { flex-wrap: wrap; }
    .pkl-cs .cs-title { white-space: normal; }
    .pkl-cs .cs-desc { white-space: normal; overflow: visible; }
}
@media (max-width: 575.98px) {
    .pkl-cs .cs-kpis { grid-template-columns: 1fr; }
    .pkl-cs .cs-list__head, .pkl-cs .cs-foot { padding-left: 16px; padding-right: 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .pkl-cs * { transition: none !important; }
}
</style>

<div class="pkl-cs">

    {{-- ================= HEADER ================= --}}
    <nav class="sdm-crumb" aria-label="breadcrumb">
        <a href="{{ url('sdm') }}">Beranda</a>
        <span class="sdm-crumb-sep">/</span>
        <span>Sistem PKL</span>
        <span class="sdm-crumb-sep">/</span>
        <span class="sdm-crumb-cur">Edit Sertifikat</span>
    </nav>

    <div class="cs-head">
        <div class="cs-head__row">
            <div class="cs-head__line">
                <span class="cs-head__icon"><i class="bx bx-edit"></i></span>
                <h1 class="cs-title">Edit Data Sertifikat Peserta</h1>
                <span class="cs-pill">{{ $fmtNum($total) }} Sertifikat</span>
            </div>
            <div class="cs-head__actions">
                <a href="{{ url()->full() }}" class="cs-btn"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i> Segarkan Data</a>
                <a href="{{ url('sdm/cetak-sertifikat') }}" class="cs-btn cs-btn--primary"><i class="bx bx-printer"></i> Cetak Sertifikat</a>
            </div>
        </div>
        <p class="cs-desc" title="Ubah data peserta yang tercetak di sertifikat: nama, kampus, jurusan, bidang, unit, dan periode magang. Setelah disimpan, file PDF sertifikat digital ikut diperbarui otomatis.">
            Ubah data peserta yang tercetak di sertifikat: nama, kampus, jurusan, bidang, unit, dan periode magang. Setelah disimpan, file PDF sertifikat digital ikut diperbarui otomatis.
        </p>
    </div>

    {{-- ================= KARTU RINGKASAN ================= --}}
    <div class="cs-kpis">
        @foreach ($kpis as $k)
        <div class="cs-kpi cs-kpi--{{ $k['tone'] }}">
            <div class="cs-kpi__top">
                <span class="cs-kpi__icon"><i class="{{ $k['icon'] }}"></i></span>
                <span class="cs-kpi__chip"><i class="{{ $k['chip_icon'] }}"></i>{{ $k['chip'] }}</span>
            </div>
            <span class="cs-kpi__eyebrow">{{ $k['eyebrow'] }}</span>
            <div class="cs-kpi__val">{{ $fmtNum($k['val']) }}<small>{{ $k['unit'] }}</small></div>
            <div class="cs-kpi__foot"><span>{{ $k['f_label'] }}</span><b>{{ $k['f_val'] }}</b></div>
        </div>
        @endforeach
    </div>

    {{-- ================= PENCARIAN ================= --}}
    <form method="GET" action="{{ url('sdm/edit-sertifikat') }}" class="cs-panel cs-toolbar" data-live-search>
        <div class="cs-toolbar__row">
            <div class="cs-search">
                <i class="bx bx-search"></i>
                <input type="text" name="q" class="cs-input" placeholder="Cari nama / username / kampus..." value="{{ $search }}" aria-label="Cari sertifikat">
            </div>
            <button type="submit" class="cs-btn cs-btn--primary"><i class="bx bx-filter-alt"></i> Cari</button>
            <a href="{{ url('sdm/edit-sertifikat') }}" class="cs-btn cs-btn--icon" title="Reset pencarian" aria-label="Reset pencarian"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i></a>
        </div>
    </form>

    {{-- ================= DAFTAR SERTIFIKAT ================= --}}
    <div class="cs-panel">
        <div class="cs-list__head">
            <div class="cs-list__title">
                <h2>Daftar Registrasi Sertifikat Peserta</h2>
                <span class="cs-list__tag">Total {{ $fmtNum($total) }} data</span>
            </div>
            <div class="cs-legend">
                <span><i style="background: var(--cs-ok)"></i> Digital</span>
                <span><i style="background: var(--cs-blue)"></i> Sudah Upload Scan</span>
                <span><i style="background: var(--cs-warn)"></i> Belum Upload</span>
            </div>
        </div>

        <div class="cs-table-wrap">
            <table class="cs-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Peserta &amp; ID</th>
                        <th>Kampus &amp; Program Studi</th>
                        <th>Bidang / Unit</th>
                        <th>Periode Magang</th>
                        <th>Status Sertifikat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($data as $row)
                    @php
                        $st        = $row->status_sertif ?? null;
                        $selesai   = in_array($st, ['Digital', 'Sudah Upload'], true);
                        $badgeCls  = $st === 'Digital' ? 'cs-badge--digital' : ($st === 'Sudah Upload' ? 'cs-badge--ok' : 'cs-badge--wait');
                        $badgeIcon = $st === 'Digital' ? 'bx bx-qr' : ($st === 'Sudah Upload' ? 'bx bxs-check-circle' : 'bx bx-time-five');
                        $durasi    = $hitungHariKerja($row->tgl_masuk, $row->tgl_keluar);
                    @endphp
                    <tr>
                        <td class="cs-no">{{ str_pad($no++, 2, '0', STR_PAD_LEFT) }}</td>

                        <td>
                            <div class="cs-person">
                                <span class="cs-avatar">{{ $inisial($row->nama) }}</span>
                                <div>
                                    <b>{{ $row->nama }}</b>
                                    <span class="cs-id {{ $selesai ? 'cs-id--ok' : 'cs-id--bad' }}">ID: {{ $row->username }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="cs-inst">
                            <b>{{ $row->asal_sekolah }}</b>
                            <span class="cs-sub"><i class="bx bx-book-reader"></i>{{ $row->jurusan }}</span>
                        </td>

                        <td class="cs-unit">
                            @if ($row->nama_bidang)
                                <span class="cs-unit__tag">{{ $row->nama_bidang }}</span>
                            @else
                                <span class="cs-unit__tag cs-unit__tag--none">-</span>
                            @endif
                            <span class="cs-sub">{{ $row->unit }}</span>
                        </td>

                        <td>
                            <div class="cs-date">
                                {{ $tgl_indo($row->tgl_masuk) }} s/d
                                <span>{{ $tgl_indo($row->tgl_keluar) }}</span>
                                @if ($durasi > 0)
                                    <em>Durasi: {{ $durasi }} hari kerja</em>
                                @endif
                            </div>
                        </td>

                        <td class="cs-status">
                            <span class="cs-badge {{ $badgeCls }}"><i class="{{ $badgeIcon }}"></i>{{ $st ?? 'Belum Upload' }}</span>
                            @if ($row->nomor_surat)
                                <span class="cs-sub">No: {{ $row->nomor_surat }}</span>
                            @endif
                        </td>

                        <td>
                            <button type="button"
                                    class="cs-act btnEditSertif"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditSertif"
                                    data-username="{{ $row->username }}"
                                    data-nama="{{ $row->nama }}"
                                    data-sekolah="{{ $row->asal_sekolah }}"
                                    data-jurusan="{{ $row->jurusan }}"
                                    data-bidang="{{ $row->bidang_id }}"
                                    data-unit="{{ $row->unit }}"
                                    data-masuk="{{ $row->tgl_masuk }}"
                                    data-keluar="{{ $row->tgl_keluar }}">
                                <i class="bx bx-edit"></i>Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if ($total == 0)
                <div class="cs-empty">
                    <i class="bx bx-file-blank"></i>
                    Tidak ada data sertifikat yang sesuai.
                </div>
            @endif
        </div>

        @if ($total > 0)
        <div class="cs-foot">
            <div class="cs-foot__info">
                <span>Menampilkan <b>{{ $offset + 1 }}-{{ min($offset + $per_page, $total) }}</b> dari <b>{{ $fmtNum($total) }}</b> sertifikat</span>
                <span>Halaman <b>{{ $page }}</b> dari <b>{{ max(1, $total_page) }}</b></span>
            </div>

            @if ($total_page > 1)
            <nav class="cs-pager" aria-label="Halaman">
                <a class="cs-pg {{ $page <= 1 ? 'is-off' : '' }}" href="{{ $urlHalaman(1) }}" aria-label="Halaman pertama"><i class="bx bx-chevrons-left"></i></a>
                <a class="cs-pg {{ $page <= 1 ? 'is-off' : '' }}" href="{{ $urlHalaman(max(1, $page - 1)) }}" aria-label="Sebelumnya"><i class="bx bx-chevron-left"></i></a>
                @foreach ($halaman as $h)
                    @if ($h === '...')
                        <span class="cs-pg cs-pg--gap">&hellip;</span>
                    @else
                        <a class="cs-pg {{ $h == $page ? 'is-on' : '' }}" href="{{ $urlHalaman($h) }}" @if ($h == $page) aria-current="page" @endif>{{ $h }}</a>
                    @endif
                @endforeach
                <a class="cs-pg {{ $page >= $total_page ? 'is-off' : '' }}" href="{{ $urlHalaman(min($total_page, $page + 1)) }}" aria-label="Selanjutnya"><i class="bx bx-chevron-right"></i></a>
                <a class="cs-pg {{ $page >= $total_page ? 'is-off' : '' }}" href="{{ $urlHalaman($total_page) }}" aria-label="Halaman terakhir"><i class="bx bx-chevrons-right"></i></a>
            </nav>
            @endif
        </div>
        @endif
    </div>

    {{-- ================= KARTU INFO BAWAH ================= --}}
    <div class="cs-info">
        <div class="cs-card">
            <div class="cs-card__head">
                <span class="cs-card__icon"><i class="bx bx-edit-alt"></i></span>
                <div>
                    <h3>Cara Mengedit Data Sertifikat</h3>
                    <p>Perubahan langsung tercermin di file sertifikat digital</p>
                </div>
            </div>
            <div class="cs-steps">
                <div class="cs-step">
                    <b><span>1</span>Pilih peserta</b>
                    <p>Cari peserta lewat nama, username, atau kampus, lalu klik tombol Edit.</p>
                </div>
                <div class="cs-step">
                    <b><span>2</span>Ubah data</b>
                    <p>Perbarui nama, kampus, jurusan, bidang, unit, serta tanggal mulai dan selesai.</p>
                </div>
                <div class="cs-step">
                    <b><span>3</span>Simpan</b>
                    <p>File PDF sertifikat digital diperbarui otomatis saat perubahan disimpan.</p>
                </div>
            </div>
            <div class="cs-note">
                <i class="bx bx-info-circle"></i>
                <span>Periksa ejaan nama dan nama kampus sebelum menyimpan, karena data ini yang tercetak di sertifikat.</span>
            </div>
        </div>

        <div class="cs-card">
            <div class="cs-card__head">
                <span class="cs-card__icon"><i class="bx bx-history"></i></span>
                <div>
                    <h3>Sertifikat Terbaru</h3>
                    <p>Empat sertifikat terakhir yang tercatat</p>
                </div>
            </div>
            @if ($terbaru->isEmpty())
                <p class="cs-feed__none">Belum ada sertifikat tercatat.</p>
            @else
            <ul class="cs-feed">
                @foreach ($terbaru as $t)
                    @php
                        if ($t->status === 'Digital') {
                            $fi = 'bx bx-qr'; $fc = 'var(--cs-primary-dark)'; $fb = 'var(--cs-primary-50)';
                            $ft = 'Digital'; $fd = $tgl_indo($t->generated_at);
                        } elseif ($t->status === 'Sudah Upload') {
                            $fi = 'bx bx-check-circle'; $fc = 'var(--cs-ok)'; $fb = '#ecfdf5';
                            $ft = 'Sudah upload scan'; $fd = $tgl_indo($t->tgl_cetak);
                        } else {
                            $fi = 'bx bx-time-five'; $fc = 'var(--cs-warn)'; $fb = '#fffbeb';
                            $ft = 'Menunggu scan'; $fd = $tgl_indo($t->tgl_cetak);
                        }
                    @endphp
                    <li>
                        <span class="cs-feed__ic" style="--c: {{ $fc }}; --cb: {{ $fb }};"><i class="{{ $fi }}"></i></span>
                        <div>
                            <b>{{ $t->nama }}</b>
                            <small>{{ $ft }} &middot; {{ $fd }}{{ $t->nomor_surat ? ' · No: ' . $t->nomor_surat : '' }}</small>
                        </div>
                    </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

</div>

<!-- MODAL EDIT SERTIFIKAT -->
<div class="modal fade" id="modalEditSertif" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ url('sdm/edit-sertifikat/update') }}" id="formEditSertif">
                @csrf
                <input type="hidden" name="username" id="editUsername">
                <div class="modal-header text-white">
                    <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Edit Data Sertifikat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Mengedit data untuk peserta: <strong id="editNamaLabel"></strong></p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" id="editNama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kampus / Asal Sekolah</label>
                            <input type="text" name="asal_sekolah" id="editSekolah" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jurusan</label>
                            <input type="text" name="jurusan" id="editJurusan" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bidang</label>
                            <select name="bidang_id" id="editBidang" class="form-select">
                                <option value="">- Pilih Bidang -</option>
                                @foreach ($bidang_list as $b)
                                    <option value="{{ $b->id }}">{{ $b->bidang }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit</label>
                            <select name="unit" id="editUnit" class="form-select" required>
                                <option value="Unit 1-2">Unit 1-2</option>
                                <option value="Unit 9">Unit 9</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Periode Mulai</label>
                            <input type="date" name="tgl_masuk" id="editMasuk" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Periode Selesai</label>
                            <input type="date" name="tgl_keluar" id="editKeluar" class="form-control" required>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bx bx-info-circle me-1"></i>
                        Perubahan pada data peserta akan langsung memperbarui <strong>file PDF sertifikat digital</strong> secara otomatis saat disimpan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_edit_sertif" class="btn btn-cs">
                        <i class="bx bx-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btnEditSertif').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editUsername').value = this.dataset.username || '';
            document.getElementById('editNamaLabel').textContent = this.dataset.nama || '';
            document.getElementById('editNama').value = this.dataset.nama || '';
            document.getElementById('editSekolah').value = this.dataset.sekolah || '';
            document.getElementById('editJurusan').value = this.dataset.jurusan || '';
            document.getElementById('editBidang').value = this.dataset.bidang || '';
            document.getElementById('editUnit').value = this.dataset.unit || 'Unit 1-2';
            document.getElementById('editMasuk').value = this.dataset.masuk || '';
            document.getElementById('editKeluar').value = this.dataset.keluar || '';
        });
    });
});
</script>
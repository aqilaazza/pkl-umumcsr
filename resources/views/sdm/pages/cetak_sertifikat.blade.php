@php
    use Illuminate\Support\Facades\DB;

    // --- FILTER & PAGINATION ---
    $search   = trim((string) request('q'));
    $filter   = (string) request('filter', 'semua');
    $bidangId = (string) request('bidang', '');
    $per_page = 15;
    $page     = max(1, (int) request('p', 1));
    $offset   = ($page - 1) * $per_page;

    $baseWhere = function ($q) {
        $q->where('lm.status', '=', 'Disetujui')
            ->orWhere(function ($q2) {
                $q2->where('lm.sdm_status', '=', 'Disetujui')
                    ->whereIn('lm.manager_status', ['Menunggu', 'Disetujui']);
            });
    };

    $applyFilters = function ($query) use ($baseWhere, $search, $filter, $bidangId) {
        $query->where($baseWhere);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'like', '%' . $search . '%')
                    ->orWhere('lm.username', 'like', '%' . $search . '%')
                    ->orWhere('p.asal_sekolah', 'like', '%' . $search . '%');
            });
        }

        if ($bidangId !== '') {
            $query->where('p.bidang_id', '=', (int) $bidangId);
        }

        if ($filter === 'belum') {
            $query->where(function ($q) {
                $q->whereNull('sm.id')->orWhere('sm.status', '=', 'Belum Upload');
            });
        }

        if ($filter === 'sudah') {
            $query->where('sm.status', '=', 'Sudah Upload');
        }

        return $query;
    };

    $countQuery = $applyFilters(
        DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('sertifikat_magang as sm', 'lm.username', '=', 'sm.username')
    );

    $total      = (int) $countQuery->count();
    $total_page = (int) ceil($total / $per_page);

    $data = $applyFilters(
        DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->leftJoin('sertifikat_magang as sm', 'lm.username', '=', 'sm.username')
    )
        ->orderByDesc('lm.sdm_tgl_review')
        ->limit($per_page)
        ->offset($offset)
        ->get([
            'lm.id as laporan_id',
            'lm.username',
            'lm.sdm_tgl_review',
            'lm.manager_status',
            'lm.status as laporan_status',
            'p.nama',
            'p.asal_sekolah',
            'p.jurusan',
            'p.status_peserta',
            'p.tgl_masuk',
            'p.tgl_keluar',
            'p.unit',
            'b.bidang as nama_bidang',
            'sm.id as sertif_id',
            'sm.nomor_surat',
            'sm.status as status_sertif',
            'sm.file_sertifikat',
            'sm.nama_file_scan',
            'sm.ukuran_file_scan',
            'sm.tgl_cetak',
            'sm.tgl_upload_scan',
            'sm.digital_file',
            'sm.generated_at',
            DB::raw("(SELECT COUNT(*) FROM absensi_peserta ap
                      WHERE ap.username = lm.username
                        AND ap.status = 'Hadir'
                        AND ap.tanggal BETWEEN p.tgl_masuk AND p.tgl_keluar) as total_hadir"),
        ]);

    // Ringkasan
    $sum = DB::table('laporan_magang as lm')
        ->leftJoin('sertifikat_magang as sm', 'lm.username', '=', 'sm.username')
        ->where($baseWhere)
        ->selectRaw("COUNT(DISTINCT lm.username) as total_disetujui")
        ->selectRaw("SUM(CASE WHEN sm.status='Sudah Upload' OR sm.status='Digital' THEN 1 ELSE 0 END) as sudah_upload")
        ->selectRaw("SUM(CASE WHEN sm.id IS NULL OR sm.status='Belum Upload' THEN 1 ELSE 0 END) as belum_upload")
        ->first();

    $scTotal  = (int) ($sum->total_disetujui ?? 0);
    $scBelum  = (int) ($sum->belum_upload ?? 0);
    $scSudah  = (int) ($sum->sudah_upload ?? 0);
    $pctBelum = $scTotal > 0 ? round($scBelum / $scTotal * 100, 1) : 0;
    $pctSudah = $scTotal > 0 ? round($scSudah / $scTotal * 100, 1) : 0;

    // Daftar bidang untuk filter
    $daftarBidang = DB::table('bidang')->orderBy('bidang')->get(['id', 'bidang']);

    // ── Data libur untuk hitung hari kerja ──
    $libur_pekan = DB::table('libur_pekan')->pluck('hari_index')->map(fn ($v) => (int) $v)->all();
    $hari_libur  = DB::table('hari_libur')->pluck('tanggal')->all();

    /**
     * Hitung jumlah hari kerja (exclude libur pekan & tanggal merah)
     */
    $hitungHariKerja = function ($tgl_masuk, $tgl_keluar, $libur_pekan, $hari_libur) {
        if (empty($tgl_masuk) || empty($tgl_keluar) || $tgl_masuk == '0000-00-00' || $tgl_keluar == '0000-00-00') {
            return 0;
        }
        $start = new DateTime($tgl_masuk);
        $end   = new DateTime($tgl_keluar);
        $end->modify('+1 day');
        $total = 0;
        $interval = new DateInterval('P1D');
        $period   = new DatePeriod($start, $interval, $end);
        foreach ($period as $date) {
            $hari_idx = (int) $date->format('w');
            if (in_array($hari_idx, $libur_pekan)) continue;
            if (in_array($date->format('Y-m-d'), $hari_libur)) continue;
            $total++;
        }
        return $total;
    };

    // Helper tampilan
    $tgl = fn ($d) => ($d && $d != '0000-00-00') ? date('d/m/Y', strtotime($d)) : '-';

    $inisial = function ($nama) {
        $kata = preg_split('/\s+/', trim((string) $nama));
        $out  = mb_substr($kata[0] ?? '', 0, 1);
        if (count($kata) > 1) {
            $out .= mb_substr($kata[1], 0, 1);
        }
        return mb_strtoupper($out ?: '?');
    };

    $urlHalaman = fn ($p, $f = null) => url('sdm/cetak-sertifikat') . '?' . http_build_query([
        'p'      => $p,
        'q'      => $search,
        'filter' => $f ?? $filter,
        'bidang' => $bidangId,
    ]);

    // Nomor halaman dengan elipsis
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

<style>
/* ===== Cetak & Kelola Sertifikat — tampilan "command" terang (scoped .pkl-cs) ===== */
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
    --cs-head: var(--pkl-font-head, inherit);
    --cs-mono: var(--pkl-font-body, 'Inter', sans-serif);
    --cs-ok: #059669;
    --cs-warn: #d97706;
    --cs-bad: #dc2626;
}
.pkl-cs *, .pkl-cs *::before, .pkl-cs *::after { box-sizing: border-box; }
.pkl-cs a { text-decoration: none; }
.pkl-cs :focus-visible { outline: 2px solid var(--cs-primary); outline-offset: 2px; }
.pkl-cs .cs-mono { font-family: var(--cs-mono); }

/* Header */
.pkl-cs .cs-crumb { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 12px; color: var(--cs-muted); }
.pkl-cs .cs-crumb a { color: var(--cs-primary-dark); }
.pkl-cs .cs-crumb a:hover { text-decoration: underline; }
.pkl-cs .cs-crumb__now { font-family: var(--cs-mono); font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--cs-primary); }
.pkl-cs .cs-title { margin: 0; font-family: var(--cs-head); font-size: 22px; font-weight: 800; letter-spacing: -.015em; line-height: 1.2; color: var(--cs-ink); }
.pkl-cs .cs-desc { margin: 8px 0 0; font-size: 12.5px; font-weight: 400; line-height: 1.45; color: var(--cs-muted); }
.pkl-cs .cs-head { margin-bottom: 22px; }

/* Kartu ringkasan */
.pkl-cs .cs-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
.pkl-cs .cs-stat { background: #fff; border: 1px solid var(--cs-line); border-radius: 14px; padding: 20px 22px 18px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
.pkl-cs .cs-stat__top { display: flex; align-items: center; gap: 14px; }
.pkl-cs .cs-stat__icon { flex: none; width: 44px; height: 44px; display: grid; place-items: center; font-size: 22px; border-radius: 12px; border: 1px solid; }
.pkl-cs .cs-stat__eyebrow { display: block; font-family: var(--cs-mono); font-size: 10px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
.pkl-cs .cs-stat__label { display: block; margin-top: 2px; font-family: var(--cs-head); font-size: 14.5px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-stat__mid { display: flex; align-items: flex-end; justify-content: space-between; gap: 10px; margin: 18px 0 14px; }
.pkl-cs .cs-stat__value { font-family: var(--pkl-font-body); font-size: 32px; font-weight: 800; line-height: 1.1; letter-spacing: -.02em; color: var(--cs-ink); font-variant-numeric: tabular-nums; }
.pkl-cs .cs-stat__unit { margin-left: 6px; font-size: 12.5px; font-weight: 500; letter-spacing: 0; color: var(--cs-muted); }
.pkl-cs .cs-stat__chip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; font-size: 10.5px; font-weight: 800; border-radius: 6px; border: 1px solid; white-space: nowrap; }
.pkl-cs .cs-stat__bar { height: 6px; border-radius: 99px; background: var(--cs-line-soft); overflow: hidden; }
.pkl-cs .cs-stat__bar i { display: block; height: 100%; border-radius: 99px; }
.pkl-cs .cs-stat--cyan .cs-stat__icon { color: var(--cs-primary-dark); background: var(--cs-primary-50); border-color: rgba(8, 145, 178, .25); }
.pkl-cs .cs-stat--cyan .cs-stat__eyebrow { color: var(--cs-primary); }
.pkl-cs .cs-stat--cyan .cs-stat__chip { color: var(--cs-primary-dark); background: var(--cs-primary-50); border-color: rgba(8, 145, 178, .25); }
.pkl-cs .cs-stat--cyan .cs-stat__bar i { background: var(--cs-primary); }
.pkl-cs .cs-stat--red .cs-stat__icon { color: var(--cs-bad); background: #fef2f2; border-color: #fecaca; }
.pkl-cs .cs-stat--red .cs-stat__eyebrow { color: var(--cs-bad); }
.pkl-cs .cs-stat--red .cs-stat__value { color: var(--cs-bad); }
.pkl-cs .cs-stat--red .cs-stat__chip { color: var(--cs-bad); background: #fef2f2; border-color: #fecaca; }
.pkl-cs .cs-stat--red .cs-stat__bar i { background: var(--cs-bad); }
.pkl-cs .cs-stat--green .cs-stat__icon { color: var(--cs-ok); background: #ecfdf5; border-color: #a7f3d0; }
.pkl-cs .cs-stat--green .cs-stat__eyebrow { color: var(--cs-ok); }
.pkl-cs .cs-stat--green .cs-stat__chip { color: var(--cs-ok); background: #ecfdf5; border-color: #a7f3d0; }
.pkl-cs .cs-stat--green .cs-stat__bar i { background: var(--cs-ok); }

/* Panel & toolbar */
.pkl-cs .cs-panel { background: #fff; border: 1px solid var(--cs-line); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
.pkl-cs .cs-toolbar { padding: 18px 20px 14px; margin-bottom: 20px; }
.pkl-cs .cs-toolbar__row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
.pkl-cs .cs-search { position: relative; flex: 1 1 280px; min-width: 220px; }
.pkl-cs .cs-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--cs-primary); pointer-events: none; }
.pkl-cs .cs-input, .pkl-cs .cs-select {
    height: 44px; width: 100%; padding: 0 14px; font-family: var(--pkl-font-body); font-size: 13.5px; font-weight: 500; color: var(--cs-ink);
    background: var(--cs-bg); border: 1px solid var(--cs-line); border-radius: 10px; transition: border-color .15s, box-shadow .15s, background .15s;
}
.pkl-cs .cs-search .cs-input { padding-left: 42px; }
.pkl-cs .cs-select { width: auto; min-width: 190px; padding-right: 34px; cursor: pointer; }
.pkl-cs .cs-input:focus, .pkl-cs .cs-select:focus { outline: 0; background: #fff; border-color: var(--cs-primary); box-shadow: 0 0 0 3px rgba(8, 145, 178, .15); }
.pkl-cs .cs-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px; height: 44px; padding: 0 20px;
    font-size: 13.5px; font-weight: 800; border-radius: 10px; border: 1px solid transparent; cursor: pointer; white-space: nowrap;
    transition: box-shadow .15s, background .15s, border-color .15s, color .15s;
}
.pkl-cs .cs-btn i { font-size: 18px; }
.pkl-cs .cs-btn--primary { color: #fff; background: var(--cs-primary); box-shadow: 0 6px 16px -8px rgba(8, 145, 178, .8); }
.pkl-cs .cs-btn--primary:hover { color: #fff; background: var(--cs-primary-dark); }
.pkl-cs .cs-btn--icon { width: 44px; padding: 0; color: var(--cs-muted); background: var(--cs-bg); border-color: var(--cs-line); }
.pkl-cs .cs-btn--icon:hover { color: var(--cs-primary-dark); border-color: var(--cs-primary); background: var(--cs-primary-50); }
.pkl-cs .cs-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--cs-line-soft); }
.pkl-cs .cs-chips__label { font-family: var(--cs-mono); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--cs-soft); margin-right: 4px; }
.pkl-cs .cs-chip { padding: 6px 14px; font-family: var(--pkl-font-body); font-size: 12px; font-weight: 700; color: var(--cs-text); background: #fff; border: 1px solid var(--cs-line); border-radius: 999px; transition: border-color .15s, background .15s, color .15s; }
.pkl-cs .cs-chip:hover { border-color: var(--cs-primary); color: var(--cs-primary-dark); }
.pkl-cs .cs-chip.is-on { color: var(--cs-primary-dark); font-weight: 600; background: var(--cs-primary-50); border-color: var(--cs-primary); }

/* Panel daftar */
.pkl-cs .cs-list__head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 24px; border-bottom: 1px solid var(--cs-line); }
.pkl-cs .cs-list__title { display: flex; align-items: center; gap: 14px; }
.pkl-cs .cs-list__icon { width: 38px; height: 38px; display: grid; place-items: center; font-size: 20px; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .25); border-radius: 10px; }
.pkl-cs .cs-list__title h2 { margin: 0; font-family: var(--cs-head); font-size: 14.5px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-list__title p { margin: 2px 0 0; font-size: 12.5px; color: var(--cs-muted); }
.pkl-cs .cs-legend { display: flex; gap: 18px; font-size: 12.5px; color: var(--cs-muted); }
.pkl-cs .cs-legend span { display: inline-flex; align-items: center; gap: 7px; }
.pkl-cs .cs-legend i { width: 8px; height: 8px; border-radius: 50%; }

.pkl-cs .cs-table-wrap { overflow-x: auto; }
.pkl-cs .cs-table { width: 100%; min-width: 1060px; border-collapse: collapse; }
.pkl-cs .cs-table th {
    padding: 16px 18px; text-align: left; white-space: nowrap; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 800;
    letter-spacing: .6px; text-transform: uppercase; color: var(--cs-muted); border-bottom: 1px solid var(--cs-line);
}
.pkl-cs .cs-table td { padding: 16px 18px; vertical-align: middle; font-family: var(--pkl-font-body); font-size: 13px; font-weight: 400; color: var(--cs-text); border-bottom: 1px solid var(--cs-line-soft); }
.pkl-cs .cs-table tbody tr { transition: background .15s; }
.pkl-cs .cs-table tbody tr:hover { background: var(--cs-bg); }
.pkl-cs .cs-table tbody tr:last-child td { border-bottom: 0; }
.pkl-cs .cs-table th:last-child, .pkl-cs .cs-table td:last-child { text-align: right; }
.pkl-cs .cs-no { font-family: var(--cs-mono); font-size: 12px; font-weight: 600; color: var(--cs-soft); }

.pkl-cs .cs-person { display: flex; align-items: center; gap: 12px; min-width: 210px; }
.pkl-cs .cs-avatar { flex: none; width: 40px; height: 40px; display: grid; place-items: center; font-size: 13px; font-weight: 700; color: var(--cs-primary-dark); background: var(--cs-primary-50); border: 1px solid rgba(8, 145, 178, .3); border-radius: 10px; }
.pkl-cs .cs-person b { display: block; font-family: var(--pkl-font-body); font-size: 13px; font-weight: 800; color: var(--cs-ink); }
.pkl-cs .cs-person__meta { display: flex; align-items: center; gap: 8px; margin-top: 3px; }
.pkl-cs .cs-person__user { font-family: var(--cs-mono); font-size: 11.5px; font-weight: 600; color: var(--cs-primary); }
.pkl-cs .cs-tag { padding: 1px 7px; font-family: var(--cs-mono); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--cs-muted); background: var(--cs-line-soft); border: 1px solid var(--cs-line); border-radius: 5px; }
.pkl-cs .cs-sub { display: block; margin-top: 2px; font-size: 12px; color: var(--cs-muted); }
.pkl-cs .cs-inst { min-width: 200px; max-width: 260px; }
.pkl-cs .cs-inst b { font-weight: 500; color: var(--cs-ink); }
.pkl-cs .cs-unit { min-width: 150px; }
.pkl-cs .cs-unit b { display: flex; align-items: center; gap: 7px; font-weight: 500; color: var(--cs-ink); }
.pkl-cs .cs-unit b i { color: var(--cs-primary); font-size: 16px; }
.pkl-cs .cs-unit .cs-sub { font-family: var(--cs-mono); font-size: 11.5px; font-weight: 500; color: var(--cs-soft); padding-left: 23px; }
.pkl-cs .cs-date { white-space: nowrap; font-size: 13px; font-weight: 600; color: var(--cs-ink); font-variant-numeric: tabular-nums; }
.pkl-cs .cs-date span { display: block; margin-top: 2px; font-size: 11.5px; font-weight: 400; color: var(--cs-soft); }

/* Kehadiran */
.pkl-cs .cs-att { min-width: 150px; }
.pkl-cs .cs-att__top { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; font-family: var(--cs-mono); font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; }
.pkl-cs .cs-att__pct { font-weight: 700; }
.pkl-cs .cs-att__days { color: var(--cs-soft); font-size: 11px; }
.pkl-cs .cs-att__bar { height: 5px; margin-top: 7px; border-radius: 99px; background: var(--cs-line-soft); overflow: hidden; }
.pkl-cs .cs-att__bar i { display: block; height: 100%; border-radius: 99px; }
.pkl-cs .cs-att.is-ok .cs-att__pct { color: var(--cs-ok); } .pkl-cs .cs-att.is-ok .cs-att__bar i { background: var(--cs-ok); }
.pkl-cs .cs-att.is-mid .cs-att__pct { color: var(--cs-warn); } .pkl-cs .cs-att.is-mid .cs-att__bar i { background: var(--cs-warn); }
.pkl-cs .cs-att.is-low .cs-att__pct { color: var(--cs-bad); } .pkl-cs .cs-att.is-low .cs-att__bar i { background: var(--cs-bad); }
.pkl-cs .cs-att__none { font-size: 12px; font-style: italic; color: var(--cs-soft); }

/* Status sertifikat */
.pkl-cs .cs-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; font-family: var(--cs-mono); font-size: 10.5px; font-weight: 800; letter-spacing: .04em; border-radius: 999px; border: 1px solid; white-space: nowrap; }
.pkl-cs .cs-badge--ok { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
.pkl-cs .cs-badge--digital { color: var(--cs-primary-dark); background: var(--cs-primary-50); border-color: rgba(8, 145, 178, .35); }
.pkl-cs .cs-badge--wait { color: #b45309; background: #fffbeb; border-color: #fde68a; }
.pkl-cs .cs-badge--none { color: var(--cs-muted); background: var(--cs-line-soft); border-color: var(--cs-line); }
.pkl-cs .cs-status .cs-sub { font-size: 11.5px; }

/* Aksi */
.pkl-cs .cs-actions { display: inline-flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
.pkl-cs .cs-act {
    display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 13px; font-family: var(--pkl-font-body); font-size: 12.5px; font-weight: 700;
    border-radius: 9px; border: 1px solid; cursor: pointer; white-space: nowrap; background: #fff; transition: background .15s, box-shadow .15s, border-color .15s;
}
.pkl-cs .cs-act i { font-size: 16px; }
.pkl-cs .cs-act--print { color: #fff; background: var(--cs-primary); border-color: var(--cs-primary); }
.pkl-cs .cs-act--print:hover { color: #fff; background: var(--cs-primary-dark); box-shadow: 0 6px 14px -8px rgba(8, 145, 178, .9); }
.pkl-cs .cs-act--line { color: var(--cs-primary-dark); border-color: rgba(8, 145, 178, .35); }
.pkl-cs .cs-act--line:hover { color: var(--cs-primary-dark); background: var(--cs-primary-50); border-color: var(--cs-primary); }
.pkl-cs .cs-act--off { color: var(--cs-soft); background: var(--cs-line-soft); border-color: var(--cs-line); cursor: not-allowed; }
.pkl-cs .cs-act--icon { width: 34px; padding: 0; justify-content: center; }

.pkl-cs .cs-empty { padding: 44px 20px; text-align: center; color: var(--cs-soft); font-size: 13.5px; }
.pkl-cs .cs-empty i { display: block; margin-bottom: 8px; font-size: 34px; }

/* Footer panel + pagination */
.pkl-cs .cs-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding: 16px 24px; border-top: 1px solid var(--cs-line); background: var(--cs-bg); border-radius: 0 0 14px 14px; }
.pkl-cs .cs-foot__info { font-family: var(--cs-mono); font-size: 11.5px; font-weight: 500; line-height: 1.6; color: var(--cs-muted); }
.pkl-cs .cs-foot__info b { color: var(--cs-primary-dark); }
.pkl-cs .cs-pager { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
.pkl-cs .cs-pg {
    min-width: 34px; height: 34px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; gap: 4px;
    font-size: 13px; font-weight: 800; color: var(--cs-ink); background: #fff; border: 1px solid var(--cs-line); border-radius: 8px; transition: border-color .15s, color .15s;
}
.pkl-cs a.cs-pg:hover { border-color: var(--cs-primary); color: var(--cs-primary-dark); }
.pkl-cs .cs-pg.is-on { color: #fff; font-weight: 700; background: var(--cs-primary); border-color: var(--cs-primary); }
.pkl-cs .cs-pg.is-off { color: var(--cs-soft); background: var(--cs-bg); pointer-events: none; }
.pkl-cs .cs-pg--gap { border: 0; background: transparent; min-width: 20px; padding: 0; color: var(--cs-soft); }

@media (max-width: 991.98px) {
    .pkl-cs .cs-stats { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
    .pkl-cs .cs-list__head, .pkl-cs .cs-foot { padding-left: 16px; padding-right: 16px; }
    .pkl-cs .cs-select { flex: 1 1 100%; }
}
@media (prefers-reduced-motion: reduce) {
    .pkl-cs * { transition: none !important; }
}
</style>

<div class="pkl-cs">

    {{-- ================= HEADER ================= --}}
    <div class="cs-head">
        <nav class="sdm-crumb" aria-label="breadcrumb">
            <a href="{{ url('sdm') }}">Beranda</a>
            <span class="sdm-crumb-sep">/</span>
            <span>Sistem PKL</span>
            <span class="sdm-crumb-sep">/</span>
            <span class="sdm-crumb-cur">Cetak Sertifikat</span>
        </nav>
        <div class="sdm-title-row">
            <span class="sdm-title-ico"><i class="bx bxs-certification"></i></span>
            <h1 class="cs-title">Cetak &amp; Kelola Sertifikat</h1>
        </div>
        <p class="cs-desc">
            Cetak sertifikat peserta magang yang laporannya sudah disetujui, lalu unggah scan sertifikat yang sudah
            ditandatangani sebagai arsip.
        </p>
    </div>

    {{-- ================= KARTU RINGKASAN ================= --}}
    <div class="cs-stats">
        <div class="cs-stat cs-stat--cyan">
            <div class="cs-stat__top">
                <span class="cs-stat__icon"><i class="bx bx-file"></i></span>
                <div>
                    <span class="cs-stat__eyebrow">Status siap proses</span>
                    <span class="cs-stat__label">Laporan Disetujui</span>
                </div>
            </div>
            <div class="cs-stat__mid">
                <span class="cs-stat__value">{{ number_format($scTotal, 0, ',', '.') }}<span class="cs-stat__unit">Peserta</span></span>
                <span class="cs-stat__chip"><i class="bx bx-check-circle"></i> Siap diproses</span>
            </div>
            <div class="cs-stat__bar"><i style="width: 100%"></i></div>
        </div>

        <div class="cs-stat cs-stat--red">
            <div class="cs-stat__top">
                <span class="cs-stat__icon"><i class="bx bx-cloud-upload"></i></span>
                <div>
                    <span class="cs-stat__eyebrow">Perlu tindakan tim SDM</span>
                    <span class="cs-stat__label">Belum Upload Scan</span>
                </div>
            </div>
            <div class="cs-stat__mid">
                <span class="cs-stat__value">{{ number_format($scBelum, 0, ',', '.') }}<span class="cs-stat__unit">Berkas pending</span></span>
                <span class="cs-stat__chip"><i class="bx bx-error-circle"></i> {{ rtrim(rtrim(number_format($pctBelum, 1, ',', ''), '0'), ',') }}% dari total</span>
            </div>
            <div class="cs-stat__bar"><i style="width: {{ $pctBelum }}%"></i></div>
        </div>

        <div class="cs-stat cs-stat--green">
            <div class="cs-stat__top">
                <span class="cs-stat__icon"><i class="bx bx-check-shield"></i></span>
                <div>
                    <span class="cs-stat__eyebrow">Arsip terverifikasi</span>
                    <span class="cs-stat__label">Sudah Upload Scan</span>
                </div>
            </div>
            <div class="cs-stat__mid">
                <span class="cs-stat__value">{{ number_format($scSudah, 0, ',', '.') }}<span class="cs-stat__unit">Sertifikat sah</span></span>
                <span class="cs-stat__chip"><i class="bx bx-check-circle"></i> {{ rtrim(rtrim(number_format($pctSudah, 1, ',', ''), '0'), ',') }}% selesai</span>
            </div>
            <div class="cs-stat__bar"><i style="width: {{ $pctSudah }}%"></i></div>
        </div>
    </div>

    {{-- ================= SEARCH & FILTER ================= --}}
    <form method="GET" action="{{ url('sdm/cetak-sertifikat') }}" class="cs-panel cs-toolbar" data-live-search>
        <div class="cs-toolbar__row">
            <div class="cs-search">
                <i class="bx bx-search"></i>
                <input type="text" name="q" class="cs-input" placeholder="Cari nama / username / asal sekolah..." value="{{ $search }}" aria-label="Cari peserta">
            </div>
            <select name="filter" class="cs-select" aria-label="Status sertifikat">
                <option value="semua" {{ $filter === 'semua' ? 'selected' : '' }}>Semua Status Sertifikat</option>
                <option value="belum" {{ $filter === 'belum' ? 'selected' : '' }}>Belum Upload Scan</option>
                <option value="sudah" {{ $filter === 'sudah' ? 'selected' : '' }}>Sudah Upload Scan</option>
            </select>
            <select name="bidang" class="cs-select" aria-label="Bidang">
                <option value="">Semua Bidang</option>
                @foreach ($daftarBidang as $bd)
                    <option value="{{ $bd->id }}" {{ (string) $bidangId === (string) $bd->id ? 'selected' : '' }}>{{ $bd->bidang }}</option>
                @endforeach
            </select>
            <button type="submit" class="cs-btn cs-btn--primary"><i class="bx bx-filter-alt"></i> Cari</button>
            <a href="{{ url('sdm/cetak-sertifikat') }}" class="cs-btn cs-btn--icon" title="Reset filter" aria-label="Reset filter"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i></a>
        </div>
        <div class="cs-chips">
            <span class="cs-chips__label">Filter cepat:</span>
            <a href="{{ $urlHalaman(1, 'semua') }}" class="cs-chip {{ $filter === 'semua' ? 'is-on' : '' }}">Semua ({{ number_format($scTotal, 0, ',', '.') }})</a>
            <a href="{{ $urlHalaman(1, 'belum') }}" class="cs-chip {{ $filter === 'belum' ? 'is-on' : '' }}">Belum Upload Scan ({{ number_format($scBelum, 0, ',', '.') }})</a>
            <a href="{{ $urlHalaman(1, 'sudah') }}" class="cs-chip {{ $filter === 'sudah' ? 'is-on' : '' }}">Sudah Upload Scan ({{ number_format($scSudah, 0, ',', '.') }})</a>
        </div>
    </form>

    {{-- ================= DAFTAR PESERTA ================= --}}
    <div class="cs-panel">
        <div class="cs-list__head">
            <div class="cs-list__title">
                <span class="cs-list__icon"><i class="bx bxs-award"></i></span>
                <div>
                    <h2>Daftar Peserta &mdash; Laporan Disetujui</h2>
                    <p>Antrean aktif pencetakan dan pengarsipan sertifikat magang</p>
                </div>
            </div>
            <div class="cs-legend">
                <span><i style="background: var(--cs-primary)"></i> Siap Cetak</span>
                <span><i style="background: var(--cs-bad)"></i> Perlu Pindaian</span>
            </div>
        </div>

        <div class="cs-table-wrap">
            <table class="cs-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Peserta &amp; Identitas</th>
                        <th>Asal Institusi &amp; Prodi</th>
                        <th>Bidang / Unit Kerja</th>
                        <th>Periode Magang</th>
                        <th>Kehadiran</th>
                        <th>Status Sertifikat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($data as $row)
                    @php
                        $has_sertif    = !empty($row->sertif_id);
                        $sudah_upload  = $has_sertif && ($row->status_sertif === 'Sudah Upload' || $row->status_sertif === 'Digital');
                        $is_digital    = $has_sertif && $row->status_sertif === 'Digital';
                        $is_scanned    = $has_sertif && $row->status_sertif === 'Sudah Upload';
                        $size_label    = '';
                        if ($sudah_upload && $row->ukuran_file_scan) {
                            $size_label = $row->ukuran_file_scan > 1024*1024
                                ? round($row->ukuran_file_scan/1024/1024, 2) . ' MB'
                                : round($row->ukuran_file_scan/1024, 1) . ' KB';
                        }
                        $nama_bidang = $row->nama_bidang ?? '';
                        $unit        = $row->unit ?? '';

                        // Hitung kehadiran
                        $total_hari_kerja = $hitungHariKerja($row->tgl_masuk, $row->tgl_keluar, $libur_pekan, $hari_libur);
                        $total_hadir      = (int) ($row->total_hadir ?? 0);
                        $pct              = $total_hari_kerja > 0 ? min(100, round(($total_hadir / $total_hari_kerja) * 100)) : 0;
                        $attClass         = $pct >= 80 ? 'is-ok' : ($pct >= 60 ? 'is-mid' : 'is-low');
                    @endphp
                    <tr>
                        <td class="cs-no">{{ str_pad($no++, 2, '0', STR_PAD_LEFT) }}</td>

                        <td>
                            <div class="cs-person">
                                <span class="cs-avatar">{{ $inisial($row->nama) }}</span>
                                <div>
                                    <b>{{ $row->nama }}</b>
                                    <div class="cs-person__meta">
                                        <span class="cs-person__user">{{ $row->username }}</span>
                                        <span class="cs-tag">{{ $row->status_peserta }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="cs-inst">
                            <b>{{ $row->asal_sekolah }}</b>
                            <span class="cs-sub">{{ $row->jurusan }}</span>
                        </td>

                        <td class="cs-unit">
                            @if ($nama_bidang)
                                <b><i class="bx bx-briefcase-alt-2"></i>{{ $nama_bidang }}</b>
                            @else
                                <b><em class="text-muted">-</em></b>
                            @endif
                            <span class="cs-sub">{{ $unit }}</span>
                        </td>

                        <td>
                            <div class="cs-date">
                                {{ $tgl($row->tgl_masuk) }}
                                <span>s/d {{ $tgl($row->tgl_keluar) }}</span>
                            </div>
                        </td>

                        <td>
                            @if ($total_hari_kerja > 0)
                                <div class="cs-att {{ $attClass }}">
                                    <div class="cs-att__top">
                                        <span class="cs-att__pct">{{ $pct }}%</span>
                                        <span class="cs-att__days">{{ $total_hadir }}/{{ $total_hari_kerja }} Hari</span>
                                    </div>
                                    <div class="cs-att__bar"><i style="width: {{ $pct }}%"></i></div>
                                </div>
                            @else
                                <span class="cs-att__none">Tidak ada data</span>
                            @endif
                        </td>

                        <td class="cs-status">
                            @if ($is_digital)
                                <span class="cs-badge cs-badge--digital"><i class="bx bx-qr"></i>Digital</span>
                                <span class="cs-sub">Terbit: {{ $tgl($row->generated_at) }}</span>
                                @if ($row->nomor_surat)
                                    <span class="cs-sub">No: {{ $row->nomor_surat }}</span>
                                @endif
                            @elseif ($sudah_upload)
                                <span class="cs-badge cs-badge--ok"><i class="bx bxs-check-circle"></i>Sudah Upload</span>
                                <span class="cs-sub">
                                    {{ $tgl($row->tgl_upload_scan) }}
                                    {!! $size_label ? '&mdash; ' . $size_label : '' !!}
                                </span>
                                @if ($row->nomor_surat)
                                    <span class="cs-sub">No: {{ $row->nomor_surat }}</span>
                                @endif
                            @elseif ($has_sertif)
                                <span class="cs-badge cs-badge--wait"><i class="bx bx-time-five"></i>Menunggu Scan</span>
                                <span class="cs-sub">Dicetak: {{ $tgl($row->tgl_cetak) }}</span>
                                @if ($row->nomor_surat)
                                    <span class="cs-sub">No: {{ $row->nomor_surat }}</span>
                                @endif
                            @else
                                <span class="cs-badge cs-badge--none"><i class="bx bx-printer"></i>Belum Cetak</span>
                            @endif
                        </td>

                        <td>
                            <div class="cs-actions ui-actions">
                                <!-- Tombol Cetak -->
                                <a href="{{ url('sdm/print-sertifikat') }}?laporan_id={{ $row->laporan_id }}&username={{ urlencode($row->username) }}"
                                   target="_blank"
                                   class="cs-act cs-act--print"
                                   @if ($has_sertif) data-ui-confirm="Sertifikat {{ $row->nama }} sudah pernah dicetak. Mencetak ulang TIDAK akan mengubah nomor surat. Lanjutkan?" data-confirm-title="Cetak Ulang Sertifikat?" data-confirm-ok="Lanjutkan" @endif>
                                    <i class="bx bx-printer"></i>Cetak
                                </a>

                                <!-- Digital Certificate Download -->
                                @if ($is_digital && $row->digital_file)
                                    <a href="{{ asset('uploads/sertifikat/' . $row->digital_file) }}"
                                       target="_blank" class="cs-act cs-act--line" title="Download Digital"
                                       data-ui-preview data-preview-title="Sertifikat Digital {{ $row->nama }}">
                                        <i class="bx bx-download"></i>Digital
                                    </a>
                                @endif

                                <!-- Tombol Upload Scan (fallback TTD basah) - hanya untuk yang belum upload -->
                                @if ($has_sertif && !$is_digital && !$is_scanned)
                                    <button type="button"
                                            class="cs-act cs-act--line btnUploadScan"
                                            data-id="{{ $row->sertif_id }}"
                                            data-laporan="{{ $row->laporan_id }}"
                                            data-nama="{{ $row->nama }}"
                                            data-file=""
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalUploadScan">
                                        <i class="bx bx-upload"></i>Upload Scan
                                    </button>
                                @elseif ($is_scanned)
                                    <a href="{{ asset('uploads/sertifikat/' . $row->file_sertifikat) }}"
                                       target="_blank" class="cs-act cs-act--line cs-act--icon" title="Lihat scan" aria-label="Lihat scan"
                                       data-ui-preview data-preview-title="Scan Sertifikat {{ $row->nama }}">
                                        <i class="bx bx-show"></i>
                                    </a>
                                @elseif (!$has_sertif)
                                    <button type="button" class="cs-act cs-act--off" disabled title="Cetak sertifikat dulu">
                                        <i class="bx bx-upload"></i>Upload Scan
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if ($total == 0)
                <div class="cs-empty">
                    <i class="bx bx-file-blank"></i>
                    Tidak ada data yang sesuai dengan filter.
                </div>
            @endif
        </div>

        @if ($total > 0)
        <div class="cs-foot">
            <div class="cs-foot__info">
                Menampilkan <b>{{ $offset + 1 }} - {{ min($offset + $per_page, $total) }}</b> dari <b>{{ number_format($total, 0, ',', '.') }}</b><br>
                peserta magang terdaftar
            </div>

            @if ($total_page > 1)
            <nav class="cs-pager" aria-label="Halaman">
                <a class="cs-pg {{ $page <= 1 ? 'is-off' : '' }}" href="{{ $urlHalaman(max(1, $page - 1)) }}"><i class="bx bx-chevron-left"></i> Sebelumnya</a>
                @foreach ($halaman as $h)
                    @if ($h === '...')
                        <span class="cs-pg cs-pg--gap">&hellip;</span>
                    @else
                        <a class="cs-pg {{ $h == $page ? 'is-on' : '' }}" href="{{ $urlHalaman($h) }}" @if ($h == $page) aria-current="page" @endif>{{ $h }}</a>
                    @endif
                @endforeach
                <a class="cs-pg {{ $page >= $total_page ? 'is-off' : '' }}" href="{{ $urlHalaman(min($total_page, $page + 1)) }}">Selanjutnya <i class="bx bx-chevron-right"></i></a>
            </nav>
            @endif
        </div>
        @endif
    </div>

</div>

<!-- MODAL UPLOAD SCAN -->
<div class="modal fade" id="modalUploadScan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- PENTING: enctype multipart/form-data wajib ada untuk upload file -->
            <form method="POST" action="{{ url('sdm/cetak-sertifikat/upload-scan') }}" enctype="multipart/form-data" id="formUploadScan">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bx bx-upload me-2"></i>Upload Sertifikat Scan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sertifikat_id" id="modalSertifId">
                    <input type="hidden" name="laporan_id"    id="modalLaporanId">

                    <p class="mb-3">Upload sertifikat untuk: <strong id="modalNamaPeserta"></strong></p>

                    <div id="infoSertifLama" class="alert alert-warning py-2 small d-none mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        File saat ini: <strong id="namaFileLama"></strong>.
                        Upload baru akan <strong>menggantikan</strong> file lama.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Sertifikat yang Sudah Ditandatangani <span class="text-danger">*</span></label>
                        <input type="file"
                               name="file_sertifikat"
                               id="fileSertifikat"
                               class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png"
                               required>
                        <div class="form-text">
                            <i class="bx bx-info-circle"></i>
                            Format: <strong>PDF, JPG, atau PNG</strong> &mdash; Maksimal: <strong>10 MB</strong>
                        </div>
                    </div>

                    <div id="scanFileInfo" class="alert alert-light border py-2 d-none">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bx bxs-file-pdf text-danger fs-4" id="scanFileIcon"></i>
                            <div>
                                <div id="scanFileName" class="fw-semibold small"></div>
                                <div id="scanFileSize" class="text-muted small"></div>
                            </div>
                        </div>
                        <div id="scanSizeWarn" class="text-danger small mt-1 d-none">
                            <i class="bx bxs-error-circle"></i> Ukuran melebihi 10 MB!
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_upload_scan" id="btnSubmitScan" class="btn btn-primary">
                        <i class="bx bx-cloud-upload me-1"></i> Upload Sertifikat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================================
// SETUP MODAL UPLOAD SCAN
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Event listener untuk tombol upload scan - menggunakan data-bs-toggle yang sudah ada
    var uploadButtons = document.querySelectorAll('.btnUploadScan');
    uploadButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var sertifId = this.getAttribute('data-id');
            var laporanId = this.getAttribute('data-laporan');
            var nama = this.getAttribute('data-nama');
            var namaFile = this.getAttribute('data-file');

            document.getElementById('modalSertifId').value = sertifId;
            document.getElementById('modalLaporanId').value = laporanId;
            document.getElementById('modalNamaPeserta').textContent = nama;

            var infoLama = document.getElementById('infoSertifLama');
            if (namaFile && namaFile !== '') {
                document.getElementById('namaFileLama').textContent = namaFile;
                infoLama.classList.remove('d-none');
            } else {
                infoLama.classList.add('d-none');
            }

            // Reset form
            document.getElementById('fileSertifikat').value = '';
            document.getElementById('scanFileInfo').classList.add('d-none');
            document.getElementById('btnSubmitScan').disabled = false;
        });
    });

    // ============================================================
    // PREVIEW FILE SCAN
    // ============================================================
    document.getElementById('fileSertifikat').addEventListener('change', function(e) {
        var file    = e.target.files[0];
        var info    = document.getElementById('scanFileInfo');
        var icon    = document.getElementById('scanFileIcon');
        var name    = document.getElementById('scanFileName');
        var size    = document.getElementById('scanFileSize');
        var warn    = document.getElementById('scanSizeWarn');
        var btn     = document.getElementById('btnSubmitScan');
        var maxSize = 10 * 1024 * 1024;

        if (file) {
            info.classList.remove('d-none');
            name.textContent = file.name;
            size.textContent = file.size > 1024*1024
                ? (file.size/1024/1024).toFixed(2) + ' MB'
                : (file.size/1024).toFixed(1) + ' KB';
            var ext = file.name.split('.').pop().toLowerCase();
            icon.className = (ext === 'pdf') ? 'bx bxs-file-pdf text-danger fs-4' : 'bx bxs-image text-primary fs-4';
            if (file.size > maxSize) {
                warn.classList.remove('d-none');
                info.classList.add('border-danger');
                btn.disabled = true;
            } else {
                warn.classList.add('d-none');
                info.classList.remove('border-danger');
                btn.disabled = false;
            }
        } else {
            info.classList.add('d-none');
        }
    });

    // ============================================================
    // AUTO REFRESH setelah cetak - pakai sessionStorage
    // ============================================================
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href*="print-sertifikat"]');
        if (link) {
            sessionStorage.setItem('cetakDiklik', '1');
        }
    });

    window.addEventListener('focus', function() {
        if (sessionStorage.getItem('cetakDiklik') === '1') {
            sessionStorage.removeItem('cetakDiklik');
            setTimeout(function() {
                window.location.reload();
            }, 1200);
        }
    });
});
</script>
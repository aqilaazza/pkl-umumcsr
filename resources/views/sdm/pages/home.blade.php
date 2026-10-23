@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Schema;

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------
    $tgl_indo = function ($tanggal) {
        if (! $tanggal) {
            return '-';
        }
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $p = explode('-', substr((string) $tanggal, 0, 10));

        return ($p[2] ?? '') . ' ' . ($bulan[(int) ($p[1] ?? 0)] ?? '') . ' ' . ($p[0] ?? '');
    };
    $tgl_pendek = fn ($t) => $t ? date('d/m/Y', strtotime($t)) : '-';
    $sisa_hari  = fn ($tgl_keluar) => (int) round((strtotime(substr((string) $tgl_keluar, 0, 10)) - strtotime(date('Y-m-d'))) / 86400);
    $nf         = fn ($n) => number_format((int) $n, 0, ',', '.');
    $inisial    = function ($nama) {
        $w = preg_split('/\s+/', trim(preg_replace('/[^\p{L}\p{N} ]+/u', ' ', (string) $nama)));
        $w = array_values(array_filter($w));
        $s = '';
        foreach (array_slice($w, 0, 2) as $x) {
            $s .= mb_strtoupper(mb_substr($x, 0, 1));
        }

        return $s !== '' ? $s : '?';
    };
    $ago = function ($t) {
        try {
            return \Carbon\Carbon::parse($t)->locale('id')->diffForHumans();
        } catch (\Throwable $e) {
            return '';
        }
    };

    $today = date('Y-m-d');

    // ---------------------------------------------------------------
    // Periode (Tahun Ajaran + Semester)
    //   Ganjil : 1 Jul  – 31 Des (tahun mulai TA)
    //   Genap  : 1 Jan  – 30 Jun (tahun mulai TA + 1)
    // ---------------------------------------------------------------
    $cur_y   = (int) date('Y');
    $cur_m   = (int) date('n');
    $ta_cur  = $cur_m >= 7 ? $cur_y : $cur_y - 1;
    $n0      = $ta_cur * 2 + ($cur_m >= 7 ? 0 : 1);

    $periode_opsi = [];
    for ($off = -3; $off <= 1; $off++) {
        $n   = $n0 + $off;
        $S   = intdiv($n, 2);
        $gj  = ($n % 2) === 0;
        $key = $S . '-' . ($gj ? 'ganjil' : 'genap');
        $periode_opsi[$key] = [
            'label' => 'TA ' . $S . '/' . ($S + 1) . ' ' . ($gj ? 'Ganjil' : 'Genap'),
            'start' => $gj ? ($S . '-07-01') : (($S + 1) . '-01-01'),
            'end'   => $gj ? ($S . '-12-31') : (($S + 1) . '-06-30'),
        ];
    }
    $periode_default = $ta_cur . '-' . ($cur_m >= 7 ? 'ganjil' : 'genap');

    $f_periode = (string) request('periode', $periode_default);
    if ($f_periode !== 'all' && ! isset($periode_opsi[$f_periode])) {
        $f_periode = $periode_default;
    }
    $p_range   = $periode_opsi[$f_periode] ?? null;
    $has_masuk = Schema::hasColumn('peserta', 'tgl_masuk');

    // Peserta masuk periode bila masa magangnya beririsan dengan rentang periode
    $applyPeriode = function ($q) use ($p_range, $has_masuk) {
        if ($p_range) {
            $q->where(function ($w) use ($p_range) {
                $w->whereNull('p.tgl_keluar')->orWhere('p.tgl_keluar', '>=', $p_range['start']);
            });
            if ($has_masuk) {
                $q->where(function ($w) use ($p_range) {
                    $w->whereNull('p.tgl_masuk')->orWhere('p.tgl_masuk', '<=', $p_range['end']);
                });
            }
        }

        return $q;
    };

    // ---------------------------------------------------------------
    // Filter (Periode, Unit & Bidang) — berlaku untuk data peserta di dashboard
    // ---------------------------------------------------------------
    $f_unit = (string) request('unit', '');
    if (! in_array($f_unit, ['Unit 1-2', 'Unit 9'], true)) {
        $f_unit = '';
    }
    $f_bidang     = (int) request('bidang', 0);
    $filter_aktif = $f_unit !== '' || $f_bidang > 0 || $f_periode !== $periode_default;

    $applyPF = function ($q) use ($f_unit, $f_bidang, $applyPeriode) {
        if ($f_unit !== '') {
            $q->where('p.unit', $f_unit);
        }
        if ($f_bidang > 0) {
            $q->where('p.bidang_id', $f_bidang);
        }
        $applyPeriode($q);

        return $q;
    };

    $bidang_opsi = DB::table('bidang')->orderBy('bidang')->get(['id', 'bidang']);

    $url_tambah = url('sdm/peserta');
    foreach (['sdm.peserta.create', 'sdm.peserta.tambah'] as $rn) {
        if (Route::has($rn)) {
            $url_tambah = route($rn);
            break;
        }
    }

    $url_mou = url('sdm/mou');
    foreach (['sdm.mou.index', 'sdm.mou', 'sdm.instansi.mou'] as $rn) {
        if (Route::has($rn)) {
            $url_mou = route($rn);
            break;
        }
    }

    // ---------------------------------------------------------------
    // Statistik peserta
    // ---------------------------------------------------------------
    $c = $applyPF(DB::table('peserta as p'))->selectRaw("
        COUNT(*) as total,
        SUM(p.status_magang = 'Aktif') as aktif,
        SUM(p.status_magang = 'Menunggu') as menunggu,
        SUM(p.status_magang = 'Selesai') as selesai,
        SUM(p.status_peserta = 'Siswa') as siswa,
        SUM(p.status_peserta = 'Mahasiswa') as mhs,
        SUM(p.status_magang = 'Aktif' AND p.unit = 'Unit 1-2') as aktif_u12,
        SUM(p.status_magang = 'Aktif' AND p.unit = 'Unit 9') as aktif_u9
    ")->first();

    $total_peserta    = (int) ($c->total ?? 0);
    $peserta_aktif    = (int) ($c->aktif ?? 0);
    $peserta_menunggu = (int) ($c->menunggu ?? 0);
    $peserta_selesai  = (int) ($c->selesai ?? 0);
    $siswa_count      = (int) ($c->siswa ?? 0);
    $mahasiswa_count  = (int) ($c->mhs ?? 0);
    $aktif_u12        = (int) ($c->aktif_u12 ?? 0);
    $aktif_u9         = (int) ($c->aktif_u9 ?? 0);

    $pct_aktif   = $total_peserta > 0 ? round($peserta_aktif / $total_peserta * 100, 1) : 0;
    $pct_selesai = $total_peserta > 0 ? round($peserta_selesai / $total_peserta * 100, 1) : 0;
    $pct_menunggu = $total_peserta > 0 ? round($peserta_menunggu / $total_peserta * 100, 1) : 0;

    // Ring Status Magang (SVG): urutan Aktif -> Menunggu -> Selesai
    $ring_r = 82; $ring_sw = 16; $ring_gap = 4;
    $ring_c = 2 * M_PI * $ring_r;
    $ring_segs = [];
    $ring_cursor = 0;
    foreach ([
        ['Aktif',    $peserta_aktif,    '#10b981'],
        ['Menunggu', $peserta_menunggu, '#f59e0b'],
        ['Selesai',  $peserta_selesai,  '#0891b2'],
    ] as $sg) {
        $len = $total_peserta > 0 ? $sg[1] / $total_peserta * $ring_c : 0;
        if ($len > 0) {
            if ($len >= $ring_c - 0.5) {
                $dash = $ring_c;
                $off  = 0;
            } else {
                $dash = max(0.01, $len - $ring_gap - $ring_sw);
                $off  = $ring_cursor + ($ring_gap + $ring_sw) / 2;
            }
            $ring_segs[] = ['label' => $sg[0], 'n' => $sg[1], 'color' => $sg[2], 'dash' => round($dash, 2), 'off' => round($off, 2)];
        }
        $ring_cursor += $len;
    }

    // ---------------------------------------------------------------
    // Absensi hari ini
    // ---------------------------------------------------------------
    $a = $applyPF(
        DB::table('absensi_peserta as ap')
            ->join('peserta as p', 'ap.username', '=', 'p.username')
            ->where('ap.tanggal', $today)
    )->selectRaw("
        SUM(ap.status = 'Hadir' AND ap.jam_masuk IS NOT NULL) as hadir,
        SUM(ap.status = 'Izin' AND ap.approval_status = 'Disetujui') as izin,
        SUM(ap.status = 'Sakit' AND ap.approval_status = 'Disetujui') as sakit,
        SUM(ap.status = 'Alpha') as alpha,
        COUNT(*) as total_absen
    ")->first();

    $absen_hadir = (int) ($a->hadir ?? 0);
    $absen_izin  = (int) ($a->izin ?? 0);
    $absen_sakit = (int) ($a->sakit ?? 0);
    $absen_total = (int) ($a->total_absen ?? 0);
    $belum_absen = max(0, $peserta_aktif - $absen_total);
    $pct_absen   = $peserta_aktif > 0 ? min(100, (int) round($absen_total / $peserta_aktif * 100)) : 0;
    if ($peserta_aktif === 0) {
        $abs_status = 'Belum ada peserta aktif';  $abs_color = '#94a3b8';
    } elseif ($absen_total === 0) {
        $abs_status = 'Belum ada yang terlaporkan'; $abs_color = '#dc2626';
    } elseif ($pct_absen >= 100) {
        $abs_status = 'Terlaporkan semua';          $abs_color = '#059669';
    } else {
        $abs_status = 'Terlaporkan sebagian';       $abs_color = '#d97706';
    }

    // ---------------------------------------------------------------
    // Antrean (global, tidak ikut filter)
    // ---------------------------------------------------------------
    $pending_izin = (int) DB::table('absensi_peserta')
        ->whereIn('status', ['Izin', 'Sakit'])
        ->where('approval_status', 'Pending')
        ->count();

    $laporan_stat = [
        'total'            => (int) DB::table('laporan_magang')->count(),
        'menunggu'         => (int) DB::table('laporan_magang')->where('status', 'Menunggu')->count(),
        'menunggu_manager' => (int) DB::table('laporan_magang')->where('sdm_status', 'Disetujui')->where('manager_status', 'Menunggu')->count(),
        'disetujui'        => (int) DB::table('laporan_magang')->where('status', 'Disetujui')->count(),
        'ditolak'          => (int) DB::table('laporan_magang')->where('status', 'Ditolak')->count(),
    ];

    $sertif_belum   = (int) DB::table('sertifikat_magang')->where('status', 'Belum Upload')->count();
    $sertif_upload  = (int) DB::table('sertifikat_magang')->where('status', 'Sudah Upload')->count();
    $sertif_digital = (int) DB::table('sertifikat_magang')->where('status', 'Digital')->count();

    // ---------------------------------------------------------------
    // Akan selesai magang (30 hari ke depan)
    // ---------------------------------------------------------------
    $akan_base = fn () => $applyPF(DB::table('peserta as p'))
        ->where('p.status_magang', 'Aktif')
        ->whereNotNull('p.tgl_keluar')
        ->whereRaw('DATEDIFF(p.tgl_keluar, ?) BETWEEN 0 AND 30', [$today]);

    $akan_total = (int) $akan_base()->count();
    $akan_dekat = (int) $akan_base()->whereRaw('DATEDIFF(p.tgl_keluar, ?) <= 14', [$today])->count();
    $q_akan_selesai = $akan_base()
        ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
        ->orderBy('p.tgl_keluar')
        ->limit(6)
        ->get(['p.nama', 'p.username', 'p.asal_sekolah', 'p.tgl_keluar', 'p.unit', 'b.bidang as bidang_peserta']);

    // ---------------------------------------------------------------
    // Bidang & instansi
    // ---------------------------------------------------------------
    $bidang_q = DB::table('bidang as b')->leftJoin('peserta as p', function ($j) use ($f_unit, $applyPeriode) {
        $j->on('b.id', '=', 'p.bidang_id');
        if ($f_unit !== '') {
            $j->where('p.unit', $f_unit);
        }
        $applyPeriode($j);
    });
    if ($f_bidang > 0) {
        $bidang_q->where('b.id', $f_bidang);
    }
    $bidang_data = $bidang_q->groupBy('b.id', 'b.bidang')
        ->orderByRaw('COUNT(p.id) DESC')
        ->get([DB::raw('b.bidang as bidang'), DB::raw('COUNT(p.id) as jumlah')]);

    $total_bidang = (int) $bidang_data->sum('jumlah');
    $max_bidang   = (int) ($bidang_data->max('jumlah') ?: 1);

    // Peserta per Bidang: semua bidang, tampil 10 baris lalu scroll
    $palet_bidang = ['#0e7490', '#06b6d4', '#0ea5e9', '#22d3ee', '#10b981'];
    $bidang_rows  = [];
    foreach ($bidang_data as $r) {
        $nm = $r->bidang ?: 'Lainnya';
        $bidang_rows[] = ['nama' => $nm, 'full' => $nm, 'jumlah' => (int) $r->jumlah];
    }

    $instansi_raw = $applyPF(DB::table('peserta as p'))
        ->selectRaw('TRIM(p.asal_sekolah) as instansi, p.status_peserta, p.status_magang, p.unit, COUNT(*) as jumlah')
        ->whereRaw("TRIM(p.asal_sekolah) <> ''")
        ->groupByRaw('TRIM(p.asal_sekolah), p.status_peserta, p.status_magang, p.unit')
        ->get();

    // ---------------------------------------------------------------
    // Aktivitas & pengajuan menunggu tindakan (izin/sakit + laporan)
    // ---------------------------------------------------------------
    $izin_rows = DB::table('absensi_peserta as ap')
        ->join('peserta as p', 'ap.username', '=', 'p.username')
        ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
        ->whereIn('ap.status', ['Izin', 'Sakit'])
        ->where('ap.approval_status', 'Pending')
        ->groupBy('ap.username', 'ap.status', 'ap.keterangan', 'ap.file_surat', 'p.nama', 'p.asal_sekolah', 'b.bidang')
        ->selectRaw('ap.username, ap.status, ap.keterangan, p.nama, p.asal_sekolah, b.bidang as bidang, MIN(ap.tanggal) as tgl_awal, MAX(ap.tanggal) as tgl_akhir, COUNT(*) as hari, MAX(ap.created_at) as diajukan')
        ->orderByDesc('diajukan')
        ->limit(6)
        ->get();

    $laporan_rows = DB::table('laporan_magang as lm')
        ->join('peserta as p', 'lm.username', '=', 'p.username')
        ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
        ->where('lm.status', 'Menunggu')
        ->orderByDesc('lm.tgl_upload')
        ->limit(6)
        ->get(['lm.username', 'lm.nama_file', 'lm.tgl_upload', 'p.nama', 'p.asal_sekolah', 'b.bidang as bidang']);

    $aktivitas = [];
    foreach ($izin_rows as $r) {
        $rentang = $r->hari > 1
            ? $tgl_pendek($r->tgl_awal) . ' – ' . $tgl_pendek($r->tgl_akhir) . ' (' . $r->hari . ' hari)'
            : $tgl_pendek($r->tgl_awal);
        $aktivitas[] = [
            'username' => $r->username, 'nama' => $r->nama, 'instansi' => $r->asal_sekolah, 'bidang' => $r->bidang,
            'jenis' => 'Pengajuan ' . $r->status, 'sub' => $rentang . ($r->keterangan ? ' · ' . \Illuminate\Support\Str::limit($r->keterangan, 40) : ''),
            'status' => 'Review SDM', 'cls' => 'pd-pill--amber',
            'waktu' => $r->diajukan ?: $r->tgl_awal, 'url' => url('sdm/approval-absensi'),
        ];
    }
    foreach ($laporan_rows as $r) {
        $aktivitas[] = [
            'username' => $r->username, 'nama' => $r->nama, 'instansi' => $r->asal_sekolah, 'bidang' => $r->bidang,
            'jenis' => 'Laporan Akhir Magang', 'sub' => \Illuminate\Support\Str::limit((string) $r->nama_file, 40),
            'status' => 'Review SDM', 'cls' => 'pd-pill--cyan',
            'waktu' => $r->tgl_upload, 'url' => url('sdm/approval-laporan'),
        ];
    }
    usort($aktivitas, fn ($x, $y) => strtotime((string) $y['waktu']) <=> strtotime((string) $x['waktu']));
    $aktivitas = array_slice($aktivitas, 0, 6);

    $kehadiran_map = collect();
    if ($aktivitas) {
        $kehadiran_map = DB::table('absensi_peserta')
            ->whereIn('username', array_values(array_unique(array_column($aktivitas, 'username'))))
            ->groupBy('username')
            ->selectRaw("username, SUM(status = 'Hadir') as h, COUNT(*) as t")
            ->get()
            ->keyBy('username');
    }
    $total_tertunda = $pending_izin + $laporan_stat['menunggu'];

    // Warna untuk bar
    $palet = ['#0891b2', '#059669', '#0284c7', '#0e7490', '#10b981', '#38bdf8', '#14b8a6', '#6366f1'];

    // Kartu metrik
    $metrics = [
        [
            'accent' => '#0891b2', 'num' => '#0e7490', 'icon' => 'bx bx-group', 'label' => 'Total Peserta', 'value' => $total_peserta,
            'chip' => ['text' => $pct_aktif . '% aktif', 'cls' => 'pd-chip--green', 'icon' => 'bx bx-trending-up'],
            'foot' => [['Siswa', $siswa_count, '#0891b2'], ['Mhs', $mahasiswa_count, '#cbd5e1']],
        ],
        [
            'accent' => '#059669', 'num' => '#059669', 'icon' => 'bx bx-user-check', 'label' => 'Peserta Aktif', 'value' => $peserta_aktif,
            'suffix' => 'Orang On-Site',
            'foot' => [['U1&2', $aktif_u12, '#10b981'], ['U9', $aktif_u9, '#cbd5e1']],
        ],
        [
            'accent' => '#d97706', 'num' => '#ea580c', 'icon' => 'bx bx-time-five', 'label' => 'Menunggu Konfirmasi', 'value' => $peserta_menunggu,
            'chip' => $peserta_menunggu > 0 ? ['text' => 'Memerlukan Aksi', 'cls' => 'pd-chip--amber'] : ['text' => 'Tidak ada antrean', 'cls' => 'pd-chip--slate'],
            'foot_text' => 'Konfirmasi Akun & Verifikasi Berkas',
        ],
        [
            'accent' => '#0e7490', 'num' => '#0e7490', 'icon' => 'bx bx-check-double', 'label' => 'Selesai Magang', 'value' => $peserta_selesai,
            'suffix' => 'Alumni Sukses',
            'foot_text' => 'Total Alumni Magang Bersertifikat',
        ],
    ];
@endphp

<div class="pd-wrap">

    {{-- ============================ HEADER ============================ --}}
    <div class="pd-header mb-3">
        <div>
            <div class="pd-crumb">
                <span>Beranda</span><span class="pd-crumb-sep">/</span>
                <span>Sistem PKL</span><span class="pd-crumb-sep">/</span>
                <span class="pd-crumb-cur">Dashboard SDM</span>
            </div>
            <div class="pd-title-row">
                <div class="pd-title-ico"><i class="bx bxs-dashboard"></i></div>
                <h1 class="pd-title">Dashboard SDM</h1>
            </div>
            <div class="pd-sub"><i class="bx bx-calendar me-1 text-primary"></i><strong class="text-dark">{{ $tgl_indo($today) }}</strong> &middot; Overview monitoring &amp; analitik magang siswa / mahasiswa</div>
        </div>

        @php
            $periode_label = $f_periode === 'all' ? 'Semua Periode' : $periode_opsi[$f_periode]['label'];
            $unit_label    = $f_unit !== '' ? $f_unit : 'Semua Unit (Unit 1-2 & Unit 9)';
            $bidang_label  = 'Semua Bidang Operasional';
            foreach ($bidang_opsi as $bo) {
                if ($f_bidang === (int) $bo->id) {
                    $bidang_label = $bo->bidang;
                }
            }
        @endphp
        <form method="GET" action="{{ url('sdm') }}" class="pd-tools" id="pd-filter-form">
            {{-- Periode --}}
            <div class="pd-dd" data-dd>
                <input type="hidden" name="periode" value="{{ $f_periode }}">
                <button type="button" class="pd-filter pd-dd__btn" aria-haspopup="listbox" aria-expanded="false">
                    <i class="bx bx-calendar"></i>
                    <span class="pd-dd__label">Periode: {{ $periode_label }}</span>
                </button>
                <div class="pd-dd__menu" role="listbox">
                    <button type="button" class="pd-dd__opt {{ $f_periode === 'all' ? 'is-sel' : '' }}" data-value="all">Periode: Semua Periode</button>
                    @foreach ($periode_opsi as $pk => $po)
                    <button type="button" class="pd-dd__opt {{ $f_periode === $pk ? 'is-sel' : '' }}" data-value="{{ $pk }}">Periode: {{ $po['label'] }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Unit --}}
            <div class="pd-dd" data-dd>
                <input type="hidden" name="unit" value="{{ $f_unit }}">
                <button type="button" class="pd-filter pd-filter--chev pd-dd__btn" aria-haspopup="listbox" aria-expanded="false">
                    <i class="bx bx-buildings"></i>
                    <span class="pd-dd__label">{{ $unit_label }}</span>
                    <i class="bx bx-chevron-down pd-filter__chev"></i>
                </button>
                <div class="pd-dd__menu" role="listbox">
                    <button type="button" class="pd-dd__opt {{ $f_unit === '' ? 'is-sel' : '' }}" data-value="">Semua Unit (Unit 1-2 &amp; Unit 9)</button>
                    <button type="button" class="pd-dd__opt {{ $f_unit === 'Unit 1-2' ? 'is-sel' : '' }}" data-value="Unit 1-2">Unit 1-2</button>
                    <button type="button" class="pd-dd__opt {{ $f_unit === 'Unit 9' ? 'is-sel' : '' }}" data-value="Unit 9">Unit 9</button>
                </div>
            </div>

            {{-- Bidang + Tambah Peserta --}}
            <div class="pd-dd" data-dd>
                <input type="hidden" name="bidang" value="{{ $f_bidang }}">
                <button type="button" class="pd-filter pd-dd__btn" aria-haspopup="listbox" aria-expanded="false">
                    <i class="bx bx-slider-alt"></i>
                    <span class="pd-dd__label">{{ $bidang_label }}</span>
                </button>
                <div class="pd-dd__menu pd-dd__menu--scroll" role="listbox">
                    <button type="button" class="pd-dd__opt {{ $f_bidang === 0 ? 'is-sel' : '' }}" data-value="0">Semua Bidang Operasional</button>
                    @foreach ($bidang_opsi as $bo)
                    <button type="button" class="pd-dd__opt {{ $f_bidang === (int) $bo->id ? 'is-sel' : '' }}" data-value="{{ $bo->id }}">{{ $bo->bidang }}</button>
                    @endforeach
                </div>
            </div>
            @if ($filter_aktif)
            <a href="{{ url('sdm') }}" class="pd-btn pd-btn--ghost pd-btn--reset" title="Reset filter"><i class="bx bx-sync"></i></a>
            @endif
            <a href="{{ $url_tambah }}" class="pd-btn pd-btn--primary"><i class="bx bx-user-plus"></i> Tambah Peserta</a>
        </form>
    </div>

    {{-- ============================ 4 KARTU METRIK ============================ --}}
    <div class="row g-3 mb-3">
        @foreach ($metrics as $m)
        <div class="col-sm-6 col-xl-3">
            <div class="pd-metric" style="--pd-accent: {{ $m['accent'] }}; --pd-num: {{ $m['num'] }};">
                <div class="pd-metric__bar"></div>
                <div class="pd-metric__head">
                    <div class="pd-metric__label">{{ $m['label'] }}</div>
                    <div class="pd-tile pd-tile--sm"><i class="{{ $m['icon'] }}"></i></div>
                </div>
                <div class="pd-metric__row {{ isset($m['suffix']) ? 'pd-metric__row--base' : '' }}">
                    <div class="pd-metric__value">{{ $nf($m['value']) }}</div>
                    @if (! empty($m['chip']))
                    <span class="pd-chip {{ $m['chip']['cls'] }}">
                        @if (! empty($m['chip']['icon']))<i class="{{ $m['chip']['icon'] }}"></i>@endif
                        {{ $m['chip']['text'] }}
                    </span>
                    @endif
                    @if (! empty($m['suffix']))
                    <span class="pd-metric__suffix">{{ $m['suffix'] }}</span>
                    @endif
                </div>
                <div class="pd-metric__foot">
                    @if (isset($m['foot']))
                        @foreach ($m['foot'] as $ft)
                        <span class="pd-metric__fi"><i class="pd-dot3" style="background: {{ $ft[2] }};"></i>{{ $ft[0] }}: <b>{{ $nf($ft[1]) }}</b></span>
                        @endforeach
                    @else
                        <span class="pd-metric__ft">{{ $m['foot_text'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ============================ BARIS 2: BIDANG · STATUS · ABSENSI ============================ --}}
    <div class="row g-3 mb-3">
        {{-- Peserta per Bidang --}}
        <div class="col-lg-4">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head pd-card-head--plain">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="pd-head-ico"><i class="bx bx-bar-chart-alt-2"></i></div>
                            <div class="min-w-0">
                                <div class="pd-card-title pd-card-title--lg">Peserta per Bidang</div>
                                <div class="pd-meta pd-meta--bold">Distribusi Kompartemen &amp; Unit Operasi</div>
                            </div>
                        </div>
                        <span class="pd-total-badge">Total: {{ $nf($total_bidang) }}</span>
                    </div>
                    @if (empty($bidang_rows))
                        <div class="text-muted small py-4 text-center">Belum ada data bidang.</div>
                    @else
                    <div class="pd-bidang-scroll flex-grow-1">
                    <div class="pd-list pd-list--bidang">
                        @foreach ($bidang_rows as $i => $row)
                            @php
                                $pct_share = $total_bidang > 0 ? number_format($row['jumlah'] / $total_bidang * 100, 1, '.', '') : '0.0';
                                $w = min(100, round($row['jumlah'] / $max_bidang * 100));
                                $warna = $i < 5 ? $palet_bidang[$i] : '#cbd5e1';
                            @endphp
                        <div class="pd-bar-row pd-bar-row--bidang">
                            <div class="pd-bar-row__top">
                                <span class="pd-bar-row__name" title="{{ $row['full'] }}">{{ $row['nama'] }}</span>
                                <span class="pd-bar-row__val pd-mono">{{ $nf($row['jumlah']) }} <small>({{ $pct_share }}%)</small></span>
                            </div>
                            <div class="pd-track"><div class="pd-fill" style="width: {{ $w }}%; background: {{ $warna }};"></div></div>
                        </div>
                        @endforeach
                    </div>
                    </div>
                    @endif
                    <div class="pd-card-foot mt-auto">
                        <span class="pd-live"><i class="pd-live-dot"></i>Diperbarui secara real-time</span>
                        <a href="{{ url('sdm/bidang') }}" class="pd-foot-link">Detail Lengkap Bidang <i class="bx bx-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Magang --}}
        <div class="col-lg-4">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head pd-card-head--plain">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="pd-head-ico"><i class="bx bx-pie-chart-alt-2"></i></div>
                            <div class="min-w-0">
                                <div class="pd-card-title pd-card-title--lg">Status Magang</div>
                                <div class="pd-meta pd-meta--bold">{{ $filter_aktif ? 'Sesuai filter' : 'Semua peserta' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="pd-status-body flex-grow-1 d-flex flex-column justify-content-center">
                    <div class="pd-status-ring">
                        <svg viewBox="0 0 200 200" role="img" aria-label="Status magang">
                            <circle cx="100" cy="100" r="{{ $ring_r }}" fill="none" stroke="#f1f5f9" stroke-width="{{ $ring_sw }}"></circle>
                            <g transform="rotate(-130 100 100)">
                                @foreach ($ring_segs as $sg)
                                <circle cx="100" cy="100" r="{{ $ring_r }}" fill="none" stroke="{{ $sg['color'] }}" stroke-width="{{ $ring_sw }}" stroke-linecap="round"
                                        stroke-dasharray="{{ $sg['dash'] }} {{ round($ring_c, 2) }}" stroke-dashoffset="-{{ $sg['off'] }}"><title>{{ $sg['label'] }}: {{ $nf($sg['n']) }} peserta</title></circle>
                                @endforeach
                            </g>
                        </svg>
                        <div class="pd-status-ring__center">
                            <span class="pd-status-ring__pct">{{ number_format($pct_aktif, 1, '.', '') }}%</span>
                            <span class="pd-status-ring__cap">Aktif Magang</span>
                            <span class="pd-status-ring__tot pd-mono">{{ $nf($total_peserta) }} Total</span>
                        </div>
                    </div>
                    <div class="pd-legend3">
                        <div class="pd-legend3__item">
                            <div class="pd-legend3__label" style="color:#059669"><span class="pd-dot2" style="background:#10b981"></span>Aktif</div>
                            <div class="pd-legend3__pct">{{ $pct_aktif }}%</div>
                            <div class="pd-legend3__n">{{ $nf($peserta_aktif) }} org</div>
                        </div>
                        <div class="pd-legend3__item">
                            <div class="pd-legend3__label" style="color:#d97706"><span class="pd-dot2" style="background:#f59e0b"></span>Menunggu</div>
                            <div class="pd-legend3__pct">{{ $pct_menunggu }}%</div>
                            <div class="pd-legend3__n">{{ $nf($peserta_menunggu) }} org</div>
                        </div>
                        <div class="pd-legend3__item">
                            <div class="pd-legend3__label" style="color:#0e7490"><span class="pd-dot2" style="background:#0891b2"></span>Selesai</div>
                            <div class="pd-legend3__pct">{{ $pct_selesai }}%</div>
                            <div class="pd-legend3__n">{{ $nf($peserta_selesai) }} org</div>
                        </div>
                    </div>
                    @php
                        $sum_jenis = $siswa_count + $mahasiswa_count;
                        $w_siswa   = $sum_jenis > 0 ? round($siswa_count / $sum_jenis * 100, 1) : 0;
                        $w_mhs     = $sum_jenis > 0 ? round(100 - $w_siswa, 1) : 0;
                        $sum_unit  = $aktif_u12 + $aktif_u9;
                        $w_u12     = $sum_unit > 0 ? round($aktif_u12 / $sum_unit * 100, 1) : 0;
                        $w_u9      = $sum_unit > 0 ? round(100 - $w_u12, 1) : 0;
                    @endphp
                    <div class="pd-status-info">
                        <div class="pd-split">
                            <div class="pd-split__top">
                                <span class="pd-split__t">Komposisi Peserta</span>
                                <span class="pd-split__v">
                                    <span class="pd-split__i"><i class="pd-dot3" style="background:#0891b2"></i>Siswa <b>{{ $nf($siswa_count) }}</b></span>
                                    <span class="pd-split__i"><i class="pd-dot3" style="background:#7dd3fc"></i>Mhs <b>{{ $nf($mahasiswa_count) }}</b></span>
                                </span>
                            </div>
                            <div class="pd-split__bar"><span style="width: {{ $w_siswa }}%; background:#0891b2"></span><span style="width: {{ $w_mhs }}%; background:#7dd3fc"></span></div>
                        </div>
                        <div class="pd-split">
                            <div class="pd-split__top">
                                <span class="pd-split__t">Aktif per Unit</span>
                                <span class="pd-split__v">
                                    <span class="pd-split__i"><i class="pd-dot3" style="background:#10b981"></i>U1&amp;2 <b>{{ $nf($aktif_u12) }}</b></span>
                                    <span class="pd-split__i"><i class="pd-dot3" style="background:#6ee7b7"></i>U9 <b>{{ $nf($aktif_u9) }}</b></span>
                                </span>
                            </div>
                            <div class="pd-split__bar"><span style="width: {{ $w_u12 }}%; background:#10b981"></span><span style="width: {{ $w_u9 }}%; background:#6ee7b7"></span></div>
                        </div>
                        <div class="pd-note">
                            <div class="pd-tile pd-tile--sm"><i class="bx bx-calendar-exclamation"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="pd-note__t">Akan Selesai Magang</div>
                                <div class="pd-meta">Dalam 30 hari ke depan</div>
                            </div>
                            <span class="pd-chip {{ $akan_total > 0 ? 'pd-chip--amber' : 'pd-chip--slate' }}">{{ $nf($akan_total) }} peserta</span>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Absensi Hari Ini --}}
        <div class="col-lg-4">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head pd-card-head--plain">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="pd-head-ico"><i class="bx bx-calendar-check"></i></div>
                            <div class="min-w-0">
                                <div class="pd-card-title pd-card-title--lg">Absensi Hari Ini</div>
                                <div class="pd-meta pd-meta--bold">Presensi Biometrik &amp; Geofence</div>
                            </div>
                        </div>
                        <i class="pd-live-dot pd-live-dot--green" title="Data hari ini"></i>
                    </div>

                    <div class="pd-abs-panel">
                        <div class="pd-gauge">
                            <svg viewBox="0 0 200 106" role="img" aria-label="Kehadiran tercatat {{ $pct_absen }}%">
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#e2e8f0" stroke-width="18" stroke-linecap="round" pathLength="100"></path>
                                @if ($pct_absen > 0)
                                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="#10b981" stroke-width="18" stroke-linecap="round" pathLength="100" stroke-dasharray="{{ $pct_absen }} 100"></path>
                                @endif
                            </svg>
                            <div class="pd-gauge__pct">{{ $pct_absen }}%</div>
                        </div>
                        <div class="pd-abs-status" style="color: {{ $abs_color }}">Status: {{ $abs_status }}</div>
                        <div class="pd-abs-count pd-mono">{{ $nf($absen_total) }} / {{ $nf($peserta_aktif) }} Peserta</div>
                    </div>

                    <div class="pd-abs-list">
                        <div class="pd-abs-row">
                            <i class="bx bx-check-circle" style="color:#059669"></i>
                            <span class="pd-abs-row__t">Hadir (On Site)</span>
                            <b class="pd-mono" style="color:#059669">{{ $nf($absen_hadir) }}</b>
                        </div>
                        <div class="pd-abs-row">
                            <i class="bx bx-info-circle" style="color:#0891b2"></i>
                            <span class="pd-abs-row__t">Izin Disetujui</span>
                            <b class="pd-mono" style="color:#0e7490">{{ $nf($absen_izin) }}</b>
                        </div>
                        <div class="pd-abs-row">
                            <i class="bx bx-plus-medical" style="color:#dc2626"></i>
                            <span class="pd-abs-row__t">Sakit (Surat Dokter)</span>
                            <b class="pd-mono" style="color:#dc2626">{{ $nf($absen_sakit) }}</b>
                        </div>
                        <div class="pd-abs-row pd-abs-row--muted">
                            <i class="bx bx-dots-horizontal-rounded"></i>
                            <span class="pd-abs-row__t">Belum Absen</span>
                            <b class="pd-mono">{{ $nf($belum_absen) }}</b>
                        </div>
                    </div>

                    <a href="{{ url('sdm/rekap-absensi') }}" class="pd-abs-link mt-auto">
                        <span>Buka Rekap Detail Absensi</span>
                        <i class="bx bx-right-arrow-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ BARIS 3: INSTANSI + RINGKAS ============================ --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="pd-head-ico"><i class="bx bx-building-house"></i></div>
                            <div class="min-w-0">
                                <div class="pd-card-title pd-card-title--lg">Peserta per Asal Instansi</div>
                                <div class="pd-meta pd-meta--bold">Klik salah satu instansi untuk melihat komposisi statusnya</div>
                            </div>
                        </div>
                        <div class="pd-ins-tools">
                            <div class="pd-search"><i class="bx bx-search"></i><input type="text" id="ins-q" placeholder="Cari instansi..."></div>
                            <select id="ins-status" class="pd-select">
                                <option value="">Semua Status</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Menunggu">Menunggu</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                            <select id="ins-jenis" class="pd-select">
                                <option value="">Siswa &amp; Mhs</option>
                                <option value="Siswa">Siswa</option>
                                <option value="Mahasiswa">Mahasiswa</option>
                            </select>
                            <select id="ins-top" class="pd-select">
                                <option value="10">Top 10</option>
                                <option value="20">Top 20</option>
                                <option value="0">Semua</option>
                            </select>
                        </div>
                    </div>
                    <div id="ins-empty" class="text-muted small py-5 text-center d-none">Tidak ada instansi yang cocok.</div>
                    <div id="ins-scroll" class="pd-ins-scroll flex-grow-1"><div id="ins-list"></div></div>
                    <div class="pd-card-foot">
                        <span id="ins-info"></span>
                        <span class="d-flex gap-3">
                            <a href="#" id="ins-export" class="pd-foot-link">Unduh Rekap Excel</a>
                            <a href="{{ $url_mou }}" class="pd-foot-link">Kelola MoU Instansi</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="d-flex flex-column gap-3 h-100">
                <div class="card pd-card">
                    <div class="card-body">
                        <div class="pd-card-head">
                            <div class="d-flex align-items-center gap-2 min-w-0"><div class="pd-head-ico"><i class="bx bx-doughnut-chart"></i></div><div class="pd-card-title pd-card-title--lg">Status Peserta</div></div>
                            <a href="#" id="ins-clear" class="small text-decoration-none d-none">Reset</a>
                        </div>
                        <div id="ins-sub" class="pd-meta mb-2 text-truncate">Semua instansi</div>
                        <div class="d-flex align-items-center gap-3">
                            <div id="chart-instansi-status" style="width:130px;min-height:130px;flex-shrink:0;"></div>
                            <div id="ins-legend" class="pd-mini-list flex-grow-1 mt-0"></div>
                        </div>
                    </div>
                </div>

                <a href="{{ url('sdm/approval-laporan') }}" class="card pd-card pd-link-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pd-tile pd-tile--lg" style="--pd-accent:#0891b2"><i class="bx bx-file-find"></i></div>
                            <div>
                                <div class="pd-big">{{ $nf($laporan_stat['menunggu']) }}</div>
                                <div class="pd-link-title">Laporan Baru</div>
                                <div class="pd-meta">Menunggu review SDM</div>
                            </div>
                        </div>
                        <span class="pd-go"><i class="bx bx-chevron-right"></i></span>
                    </div>
                </a>

                <a href="{{ url('sdm/cetak-sertifikat') }}" class="card pd-card pd-link-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="pd-tile pd-tile--lg" style="--pd-accent:#059669"><i class="bx bx-award"></i></div>
                            <div>
                                <div class="pd-big">{{ $nf($sertif_digital) }}</div>
                                <div class="pd-link-title">Sertifikat Digital</div>
                                <div class="pd-meta">Telah terbit (QR Code)</div>
                            </div>
                        </div>
                        <span class="pd-go"><i class="bx bx-chevron-right"></i></span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- ============================ BARIS 4: LAPORAN · BIDANG INTI · SERTIFIKAT ============================ --}}
    <div class="row g-3 mb-3">
        {{-- Status Laporan --}}
        <div class="col-lg-4">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head">
                        <div class="d-flex align-items-center gap-2 min-w-0"><div class="pd-head-ico"><i class="bx bx-line-chart"></i></div><div class="pd-card-title pd-card-title--lg">Status Laporan</div></div>
                        <span class="pd-meta">Total: {{ $nf($laporan_stat['total']) }} berkas</span>
                    </div>
                    @php
                        $total_display = $laporan_stat['total'] ?: 1;
                        $bars = [
                            ['label' => 'Review SDM',   'val' => $laporan_stat['menunggu'],         'color' => '#f59e0b', 'txt' => '#0f172a'],
                            ['label' => 'Ke Manager',   'val' => $laporan_stat['menunggu_manager'], 'color' => '#0284c7', 'txt' => '#0f172a'],
                            ['label' => 'Disetujui',    'val' => $laporan_stat['disetujui'],        'color' => '#059669', 'txt' => '#059669'],
                            ['label' => 'Ditolak',      'val' => $laporan_stat['ditolak'],          'color' => '#dc2626', 'txt' => '#dc2626'],
                        ];
                    @endphp
                    <div class="pd-list flex-grow-1">
                        @foreach ($bars as $b)
                            @php $pct = round($b['val'] / $total_display * 100); @endphp
                        <div class="pd-bar-row">
                            <div class="pd-bar-row__top">
                                <span class="pd-bar-row__name">{{ $b['label'] }}</span>
                                <span class="pd-bar-row__val" style="color: {{ $b['txt'] }}">{{ $nf($b['val']) }}</span>
                            </div>
                            <div class="pd-track"><div class="pd-fill" style="width: {{ $pct }}%; background: {{ $b['color'] }};"></div></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="pd-card-foot">
                        <span>{{ $nf($laporan_stat['menunggu']) }} menunggu review</span>
                        <a href="{{ url('sdm/approval-laporan') }}">Buka Laporan</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Distribusi Bidang Inti --}}
        <div class="col-lg-4">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head">
                        <div class="d-flex align-items-center gap-2 min-w-0"><div class="pd-head-ico"><i class="bx bx-network-chart"></i></div><div class="pd-card-title pd-card-title--lg">Distribusi Bidang Inti</div></div>
                        <span class="pd-meta">5 terbanyak</span>
                    </div>
                    @if ($bidang_data->isEmpty())
                        <div class="text-muted small py-4 text-center">Belum ada data bidang.</div>
                    @else
                    <div class="pd-list flex-grow-1">
                        @foreach ($bidang_data->take(5) as $i => $row)
                            @php $w = round($row->jumlah / $max_bidang * 100); @endphp
                        <div class="pd-bar-row">
                            <div class="pd-bar-row__top">
                                <span class="pd-bar-row__name" title="{{ $row->bidang }}">{{ $row->bidang ?: 'Lainnya' }}</span>
                                <span class="pd-bar-row__val">{{ $nf($row->jumlah) }}</span>
                            </div>
                            <div class="pd-track"><div class="pd-fill" style="width: {{ $w }}%; background: {{ $palet[$i % count($palet)] }};"></div></div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="pd-card-foot">
                        <span>Sesuai kapasitas kuota</span>
                        <a href="{{ url('sdm/kuota') }}">Cek Kuota</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Sertifikat --}}
        <div class="col-lg-4">
            <div class="card pd-card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="pd-card-head pd-card-head--plain">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="pd-head-ico"><i class="bx bx-check-shield"></i></div>
                            <div class="pd-card-title pd-card-title--lg">Status Sertifikat</div>
                        </div>
                        <span class="pd-meta pd-meta--bold">E-Certificate</span>
                    </div>
                    <div class="pd-stack flex-grow-1">
                        <div class="pd-stat-row">
                            <div class="pd-stat-row__ico pd-stat-row__ico--plain"><i class="bx bx-upload"></i></div>
                            <div class="flex-grow-1 min-w-0"><div class="pd-stat-row__t">Belum Upload Scan</div><div class="pd-stat-row__s">Menunggu unggahan fisik</div></div>
                            <div class="pd-stat-row__n">{{ $nf($sertif_belum) }}</div>
                        </div>
                        <div class="pd-stat-row">
                            <div class="pd-stat-row__ico pd-stat-row__ico--plain"><i class="bx bx-cloud-upload"></i></div>
                            <div class="flex-grow-1 min-w-0"><div class="pd-stat-row__t">Sudah Upload Scan</div><div class="pd-stat-row__s">Arsip fisik tersimpan</div></div>
                            <div class="pd-stat-row__n">{{ $nf($sertif_upload) }}</div>
                        </div>
                        <div class="pd-stat-row pd-stat-row--hi">
                            <div class="pd-stat-row__ico"><svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd" aria-hidden="true"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zM3 13h8v8H3v-8zm2 2v4h4v-4H5zM13 3h8v8h-8V3zm2 2v4h4V5h-4z"/><path d="M13 13h2v2h-2zM15 15h2v2h-2zM13 17h2v2h-2zM17 13h2v2h-2zM19 15h2v2h-2zM17 17h2v2h-2zM19 19h2v2h-2z"/></svg></div>
                            <div class="flex-grow-1 min-w-0"><div class="pd-stat-row__t">Digital (QR Code BSrE)</div><div class="pd-stat-row__s pd-stat-row__s--hi">Terbit resmi &amp; legal</div></div>
                            <div class="pd-stat-row__n pd-stat-row__n--hi">{{ $nf($sertif_digital) }}</div>
                        </div>
                    </div>
                    <a href="{{ url('sdm/cetak-sertifikat') }}" class="pd-btn pd-btn--primary pd-btn--block mt-3"><i class="bx bx-printer"></i> Buka Kelola Sertifikat</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ AKAN SELESAI MAGANG ============================ --}}
    <div class="card pd-card mb-3">
        <div class="card-body">
            <div class="pd-card-head pd-card-head--plain pd-as-head flex-wrap">
                <div class="pd-as-head__l">
                    <div class="pd-head-ico"><i class="bx bx-bell"></i></div>
                    <div class="pd-card-title pd-card-title--lg">Akan Selesai Magang (30 Hari Ke Depan)</div>
                    @if ($akan_dekat > 0)
                    <span class="pd-as-badge">{{ $nf($akan_dekat) }} Peserta Mendekati Batas Akhir</span>
                    @else
                    <span class="pd-as-badge pd-as-badge--muted">Belum ada yang mendekati batas akhir</span>
                    @endif
                </div>
                <div class="pd-as-head__r">
                    <span class="pd-as-sort">Urutan: Sisa Masa Tercepat</span>
                    <a href="{{ url('sdm/peserta') }}" class="pd-as-all">Tampilkan Semua {{ $nf($akan_total) }} <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            </div>

            @if ($q_akan_selesai->isEmpty())
                <div class="text-muted small py-3 text-center">Tidak ada peserta yang akan selesai dalam 30 hari.</div>
            @else
            <div class="row g-3">
                @foreach ($q_akan_selesai as $row)
                    @php
                        $sisa = $sisa_hari($row->tgl_keluar);
                        $sisa_cls = $sisa <= 14 ? '' : ($sisa <= 21 ? 'pd-as-days--amber' : 'pd-as-days--cyan');
                    @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="pd-as-card">
                        <div class="pd-as-top">
                            <div class="pd-as-ava"><i class="bx bx-user"></i></div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="pd-as-name text-truncate" title="{{ $row->nama }}">{{ $row->nama }}</div>
                                <div class="pd-as-sub text-truncate" title="{{ ($row->bidang_peserta ?? '-') . ' • ' . ($row->asal_sekolah ?: '-') }}">{{ $row->bidang_peserta ?? '-' }} &bull; {{ $row->asal_sekolah ?: '-' }}</div>
                            </div>
                            <span class="pd-as-days {{ $sisa_cls }}">{{ $sisa }} hari lagi</span>
                        </div>
                        <div class="pd-as-info"><i class="bx bx-calendar"></i><span class="text-truncate">Selesai {{ $tgl_indo($row->tgl_keluar) }}</span></div>
                        <div class="pd-as-act">
                            <a href="{{ url('sdm/approval-laporan') }}" class="pd-as-btn">Review Laporan</a>
                            <a href="{{ url('sdm/cetak-sertifikat') }}" class="pd-as-btn pd-as-btn--cyan">Draft Sertifikat</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ============================ AKTIVITAS & PENGAJUAN ============================ --}}
    <div class="card pd-card">
        <div class="card-body">
            <div class="pd-card-head flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="pd-tile pd-tile--sm" style="--pd-accent:#0891b2"><i class="bx bx-task"></i></div>
                    <div>
                        <div class="pd-card-title">Aktivitas &amp; Pengajuan Menunggu Tindakan</div>
                        <div class="pd-meta">Pengajuan izin/sakit dan laporan akhir yang menunggu review SDM</div>
                    </div>
                </div>
                <div class="pd-search"><i class="bx bx-search"></i><input type="text" id="act-q" placeholder="Filter nama / instansi..."></div>
            </div>

            <div class="table-responsive">
                <table class="table pd-table align-middle mb-0" id="act-table">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>Instansi &amp; Bidang</th>
                            <th class="text-center">Kehadiran</th>
                            <th>Jenis Pengajuan</th>
                            <th>Status Tahapan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aktivitas as $it)
                            @php
                                $km = $kehadiran_map->get($it['username']);
                                $pk = ($km && (int) $km->t > 0) ? (int) round($km->h / $km->t * 100) : null;
                                $pk_cls = $pk === null ? 'pd-chip--slate' : ($pk >= 90 ? 'pd-chip--green' : ($pk >= 75 ? 'pd-chip--amber' : 'pd-chip--red'));
                            @endphp
                        <tr data-search="{{ strtolower($it['nama'] . ' ' . $it['username'] . ' ' . $it['instansi'] . ' ' . $it['bidang']) }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="pd-avatar pd-avatar--sm">{{ $inisial($it['nama']) }}</div>
                                    <div>
                                        <div class="pd-person__name">{{ $it['nama'] }}</div>
                                        <div class="pd-meta">{{ $it['username'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $it['instansi'] ?: '-' }}</div>
                                <div class="pd-meta">{{ $it['bidang'] ?: '-' }}</div>
                            </td>
                            <td class="text-center"><span class="pd-chip {{ $pk_cls }}">{{ $pk === null ? '-' : $pk . '%' }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $it['jenis'] }}</div>
                                <div class="pd-meta">{{ $it['sub'] }}@if ($ago($it['waktu'])) · {{ $ago($it['waktu']) }}@endif</div>
                            </td>
                            <td><span class="pd-pill {{ $it['cls'] }}"><i class="pd-dot2"></i>{{ $it['status'] }}</span></td>
                            <td class="text-center"><a href="{{ $it['url'] }}" class="pd-btn pd-btn--primary pd-btn--sm">Tinjau</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada pengajuan yang menunggu tindakan.</td></tr>
                        @endforelse
                        <tr id="act-empty" class="d-none"><td colspan="6" class="text-center text-muted py-4">Tidak ada hasil yang cocok.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="pd-card-foot mt-3">
                <span>Menampilkan <b>{{ count($aktivitas) }}</b> dari <b>{{ $nf($total_tertunda) }}</b> pengajuan tertunda ({{ $nf($pending_izin) }} izin/sakit · {{ $nf($laporan_stat['menunggu']) }} laporan)</span>
                <span class="d-flex gap-3">
                    <a href="{{ url('sdm/approval-absensi') }}">Approval Izin/Sakit</a>
                    <a href="{{ url('sdm/approval-laporan') }}">Approval Laporan</a>
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    .pd-wrap {
        --pd-ink: #0f172a; --pd-muted: #64748b; --pd-line: #e2e8f0; --pd-cyan: #0891b2; --pd-cyan-d: #0e7490;
        --pd-r: 14px;
        --pd-shadow: 0 1px 2px rgba(15,23,42,.04), 0 8px 24px -14px rgba(15,23,42,.12);
        --pd-shadow-h: 0 2px 4px rgba(15,23,42,.04), 0 16px 32px -16px rgba(8,145,178,.30);
        --pd-tile-bg: linear-gradient(145deg, #f3feff, #e0f7fb);
    }
    .pd-wrap .card { margin-bottom: 0; }
    .pd-wrap .card:hover { transform: none; }
    .pd-wrap .min-w-0 { min-width: 0; }
    .pd-wrap a { text-decoration: none; }
    .pd-wrap a:focus-visible, .pd-wrap button:focus-visible, .pd-wrap select:focus-visible { outline: 2px solid rgba(8,145,178,.45); outline-offset: 2px; }

    /* ---------- Animasi halus ---------- */
    @keyframes pdRise { from { opacity: 0; transform: translateY(10px); } }
    @keyframes pdGrow { from { transform: scaleX(0); } }
    @keyframes pdPop { from { opacity: 0; transform: translateY(-4px) scale(.98); } }
    @keyframes pdPulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
    @keyframes pdSpin { to { transform: rotate(360deg); } }
    .pd-wrap .row > [class*="col-"] { animation: pdRise .55s cubic-bezier(.2,.7,.2,1) backwards; }
    .pd-wrap .row > :nth-child(2) { animation-delay: .06s; }
    .pd-wrap .row > :nth-child(3) { animation-delay: .12s; }
    .pd-wrap .row > :nth-child(4) { animation-delay: .18s; }

    /* ---------- Header ---------- */
    .pd-header { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; }
    .pd-crumb { display: flex; align-items: center; gap: .5rem; font-size: 10.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--pd-muted); }
    .pd-crumb-sep { color: #cbd5e1; }
    .pd-crumb-cur { color: #0e7490; }
    .pd-title-row { display: flex; align-items: center; gap: .6rem; margin: .35rem 0 .3rem; }
    .pd-title-ico { width: 36px; height: 36px; border-radius: .6rem; background: linear-gradient(135deg, rgba(8,145,178,.18), rgba(8,145,178,.06)); border: 1px solid rgba(8,145,178,.2); color: var(--pd-cyan); display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 10px -4px rgba(8,145,178,.35); }
    .pd-title { font-family: var(--pkl-font-head, 'Outfit', sans-serif); font-size: 22px; font-weight: 800; letter-spacing: -.015em; color: var(--pd-ink); margin: 0; line-height: 1.2; }
    .pd-sub { font-size: 12.5px; font-weight: 400; line-height: 1.45; color: var(--pd-muted); }

    /* Filter: 1 baris (Periode · Unit · Bidang · Tambah Peserta) */
    .pd-tools { display: flex; flex-wrap: nowrap; align-items: center; justify-content: flex-end; gap: .6rem; }
    .pd-filter { position: relative; display: flex; align-items: center; gap: .45rem; margin: 0; background: #fff; border: 1px solid var(--pd-line); border-radius: .6rem; padding: .5rem .75rem; color: var(--pd-muted); box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: border-color .15s ease, box-shadow .15s ease; }
    .pd-filter > i:first-child { font-size: 17px; color: var(--pd-cyan); }
    .pd-filter select { appearance: none; -webkit-appearance: none; -moz-appearance: none; border: 0; background: transparent; outline: none; font-size: 13.5px; font-weight: 600; color: var(--pd-ink); cursor: pointer; padding-right: .25rem; }
    .pd-filter--chev select { padding-right: 1.25rem; }
    /* Dropdown custom (menggantikan <select> bawaan browser) */
    .pd-dd { position: relative; }
    .pd-dd__btn { font-family: inherit; font-size: 13px; font-weight: 600; color: var(--pd-ink); cursor: pointer; text-align: left; line-height: 1.2; }
    .pd-dd__btn:hover { border-color: var(--pd-cyan); box-shadow: 0 2px 8px -3px rgba(8,145,178,.3); }
    .pd-dd__btn[aria-expanded="true"] { border-color: var(--pd-cyan); box-shadow: 0 0 0 3px rgba(8,145,178,.14); }
    .pd-dd__label { white-space: nowrap; }
    .pd-filter--chev .pd-dd__label { padding-right: 1.1rem; }
    .pd-filter__chev { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); font-size: 18px; color: var(--pd-muted); pointer-events: none; transition: transform .2s ease, color .15s ease; }
    .pd-dd.is-open .pd-filter__chev { transform: translateY(-50%) rotate(180deg); color: var(--pd-cyan); }
    .pd-dd__menu { display: none; position: absolute; z-index: 1050; top: calc(100% + 6px); left: 0; min-width: 100%; width: max-content; max-width: 340px; background: #fff; border: 1px solid var(--pd-line); border-radius: .75rem; padding: .4rem; box-shadow: 0 16px 32px -10px rgba(15,23,42,.22), 0 2px 6px rgba(15,23,42,.06); transform-origin: top center; }
    .pd-dd.is-open .pd-dd__menu { display: block; animation: pdPop .16s ease; }
    .pd-dd__menu--scroll { max-height: 320px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .pd-dd__opt { display: block; width: 100%; border: 0; background: transparent; text-align: left; padding: .55rem .9rem; border-radius: .45rem; font-family: inherit; font-size: 13px; font-weight: 500; color: #334155; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: background .12s ease, color .12s ease; }
    .pd-dd__opt:hover { background: #f1f5f9; color: var(--pd-ink); }
    .pd-dd__opt.is-sel { background: #ecfeff; color: #0e7490; font-weight: 700; }
    .pd-dd:last-of-type .pd-dd__menu { left: auto; right: 0; }

    /* ---------- Tombol ---------- */
    .pd-btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; border: 1px solid transparent; border-radius: .6rem; padding: .5rem .95rem; font-size: 13.5px; font-weight: 800; line-height: 1.2; cursor: pointer; transition: all .18s ease; white-space: nowrap; }
    .pd-btn i { font-size: 16px; }
    .pd-btn--primary { background: linear-gradient(180deg, #0aa3c7, #0891b2); color: #fff; box-shadow: 0 1px 2px rgba(8,145,178,.3), inset 0 1px 0 rgba(255,255,255,.18); }
    .pd-btn--primary:hover { background: linear-gradient(180deg, #0991b3, #0e7490); color: #fff; box-shadow: 0 6px 16px -4px rgba(8,145,178,.5); transform: translateY(-1px); }
    .pd-btn--primary:active { transform: translateY(0); box-shadow: 0 1px 2px rgba(8,145,178,.3); }
    .pd-btn--soft { background: #fff; color: #0e7490; border-color: var(--pd-line); }
    .pd-btn--soft:hover { background: #ecfeff; border-color: var(--pd-cyan); color: #0e7490; }
    .pd-btn--ghost { background: #fff; color: var(--pd-muted); border-color: var(--pd-line); padding: .5rem .6rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .pd-btn--ghost:hover { color: var(--pd-cyan); border-color: var(--pd-cyan); }
    .pd-btn--reset { color: var(--pd-cyan); }
    .pd-btn--reset i { font-size: 18px; animation: pdSpin 1.6s linear infinite; }
    .pd-btn--sm { padding: .38rem .7rem; font-size: 12.5px; font-weight: 700; }
    .pd-btn--block { display: flex; width: 100%; }

    /* ---------- Kartu metrik ---------- */
    .pd-metric { position: relative; overflow: hidden; height: 100%; background: radial-gradient(130% 90% at 100% 0%, color-mix(in srgb, var(--pd-accent, #0891b2) 9%, transparent), transparent 62%), #fff; border: 1px solid rgba(15,23,42,.07); border-radius: var(--pd-r); padding: 1.05rem 1.1rem .9rem; box-shadow: var(--pd-shadow); transition: box-shadow .25s ease, transform .25s ease, border-color .25s ease; }
    .pd-metric:hover { box-shadow: 0 2px 4px rgba(15,23,42,.04), 0 16px 30px -14px color-mix(in srgb, var(--pd-accent, #0891b2) 45%, transparent); border-color: color-mix(in srgb, var(--pd-accent, #0891b2) 25%, transparent); transform: translateY(-3px); }
    .pd-metric__bar { position: absolute; left: 0; right: 0; top: 0; height: 3px; background: linear-gradient(90deg, var(--pd-accent), transparent 85%); }
    .pd-tile { width: 42px; height: 42px; border-radius: .65rem; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; color: #0891b2; background: var(--pd-tile-bg); border: 1px solid #a5f3fc; box-shadow: inset 0 1px 0 rgba(255,255,255,.85), 0 2px 6px -2px rgba(8,145,178,.25); }
    .pd-tile--lg { width: 50px; height: 50px; font-size: 26px; }
    .pd-tile--sm { width: 34px; height: 34px; font-size: 19px; }
    .pd-metric__head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .45rem; }
    .pd-metric__label { font-size: 12px; font-weight: 700; color: #334155; letter-spacing: 0; }
    .pd-metric__row { display: flex; align-items: center; flex-wrap: wrap; gap: .3rem .55rem; }
    .pd-metric__row--base { align-items: baseline; }
    .pd-metric__value { font-family: var(--pkl-font-body); font-size: 32px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; color: var(--pd-num, var(--pd-ink)); font-variant-numeric: tabular-nums; }
    .pd-metric__suffix { font-size: 11px; font-weight: 600; color: var(--pd-muted); white-space: nowrap; }
    .pd-metric__foot { display: flex; align-items: center; flex-wrap: wrap; gap: .25rem .85rem; margin-top: .7rem; padding-top: .6rem; border-top: 1px dashed #e8edf3; font-size: 11.5px; color: var(--pd-muted); line-height: 1.45; }
    .pd-metric__fi { display: inline-flex; align-items: center; gap: .35rem; }
    .pd-metric__fi b { color: var(--pd-ink); font-weight: 700; }
    .pd-metric__ft { font-size: 12px; }
    .pd-dot3 { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .pd-sep { color: #cbd5e1; }

    /* ---------- Chip & pill ---------- */
    .pd-chip { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; padding: .18rem .6rem; font-size: 10.5px; font-weight: 800; letter-spacing: .02em; white-space: nowrap; }
    .pd-chip i { font-size: 13px; }
    .pd-chip--cyan { background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc; }
    .pd-chip--green { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .pd-chip--amber { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .pd-chip--red { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .pd-chip--slate { background: #f8fafc; color: #475569; border: 1px solid var(--pd-line); }
    .pd-dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; animation: pdPulse 2s ease-in-out infinite; }
    .pd-dot2 { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: .45rem; background: currentColor; }
    .pd-pill { display: inline-flex; align-items: center; border-radius: .45rem; padding: .25rem .6rem; font-size: 11px; font-weight: 600; }
    .pd-pill--cyan { background: #ecfeff; color: #0e7490; box-shadow: inset 0 0 0 1px #cffafe; }
    .pd-pill--amber { background: #fffbeb; color: #b45309; box-shadow: inset 0 0 0 1px #fef3c7; }

    /* ---------- Kartu umum ---------- */
    .pd-wrap .pd-card { background: #fff; border: 1px solid rgba(15,23,42,.07); border-radius: var(--pd-r); box-shadow: var(--pd-shadow); transition: box-shadow .25s ease, border-color .25s ease; }
    .pd-wrap .pd-card:hover { box-shadow: var(--pd-shadow-h); border-color: rgba(8,145,178,.18); }
    .pd-card .card-body { padding: 1.05rem 1.15rem; }
    .pd-card-head { display: flex; align-items: center; justify-content: space-between; padding-bottom: .7rem; margin-bottom: .85rem; border-bottom: 1px solid #f1f5f9; }
    .pd-card-title { display: flex; align-items: center; gap: .45rem; font-family: var(--pkl-font-head, 'Outfit', sans-serif); font-size: 14.5px; font-weight: 800; color: var(--pd-ink); }
    .pd-card-title i { font-size: 19px; }
    .pd-meta { font-size: 11px; color: var(--pd-muted); }
    .pd-card-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; flex-wrap: wrap; margin-top: .9rem; padding-top: .7rem; border-top: 1px solid #f1f5f9; font-size: 11px; color: var(--pd-muted); }
    .pd-card-foot a { color: var(--pd-cyan); font-weight: 600; transition: color .15s ease; }
    .pd-card-foot a:hover { color: #0e7490; text-decoration: underline; text-underline-offset: 3px; }
    .pd-big { font-family: var(--pkl-font-body); font-size: 1.6rem; font-weight: 800; line-height: 1.1; color: var(--pd-ink); font-variant-numeric: tabular-nums; }
    .pd-link-title { font-size: 14px; font-weight: 600; color: #334155; margin-top: .15rem; }
    .pd-link-card { color: inherit; }
    .pd-wrap .pd-link-card:hover { color: inherit; transform: translateY(-2px); }
    .pd-go { width: 34px; height: 34px; border-radius: .55rem; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--pd-cyan); font-size: 20px; transition: all .2s ease; }
    .pd-link-card:hover .pd-go { background: var(--pd-cyan); color: #fff; transform: translateX(2px); box-shadow: 0 6px 12px -4px rgba(8,145,178,.5); }

    /* ---------- Bar list ---------- */
    .pd-list { display: flex; flex-direction: column; gap: .8rem; }
    .pd-bar-row__top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .3rem; font-size: 12.5px; }
    .pd-bar-row__name { font-weight: 500; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pd-bar-row__val { font-weight: 700; color: var(--pd-ink); white-space: nowrap; }
    .pd-bar-row__val small { font-weight: 400; color: #94a3b8; }
    .pd-track { height: 8px; border-radius: 999px; background: #f1f5f9; overflow: hidden; }
    .pd-fill { position: relative; height: 100%; border-radius: 999px; overflow: hidden; transition: width .5s ease; }
    .pd-fill::after { content: ''; position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.3)); }
    .pd-bar-row .pd-fill { transform-origin: left center; animation: pdGrow .85s cubic-bezier(.2,.7,.2,1) backwards; }

    /* ---------- Peserta per Bidang ---------- */
    .pd-mono, .pd-total-badge { font-family: var(--pkl-font-body); font-variant-numeric: tabular-nums; }
    .pd-card-head--plain { border-bottom: 0; padding-bottom: 0; margin-bottom: 1.25rem; gap: .75rem; }
    .pd-head-ico { width: 38px; height: 38px; border-radius: .6rem; background: var(--pd-tile-bg); border: 1px solid #a5f3fc; color: var(--pd-cyan); display: flex; align-items: center; justify-content: center; font-size: 21px; flex-shrink: 0; box-shadow: inset 0 1px 0 rgba(255,255,255,.85), 0 2px 6px -2px rgba(8,145,178,.25); }
    .pd-card-title--lg { font-size: 14.5px; font-weight: 800; line-height: 1.25; }
    .pd-meta--bold { font-weight: 600; margin-top: .15rem; }
    .pd-total-badge { flex-shrink: 0; border: 1px solid #a5f3fc; background: linear-gradient(180deg, #f3feff, #e6fafd); color: #0e7490; border-radius: .4rem; padding: .28rem .6rem; font-size: 10.5px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; box-shadow: 0 1px 2px rgba(8,145,178,.12); }
    .pd-list--bidang { gap: 17px; }
    /* 10 baris terlihat (10 x 31px + 9 x 17px), sisanya scroll */
    .pd-bidang-scroll { max-height: 463px; overflow-y: auto; overflow-x: hidden; padding-right: .5rem; margin-bottom: .9rem; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .pd-bidang-scroll::-webkit-scrollbar { width: 6px; }
    .pd-bidang-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    .pd-bidang-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .pd-bidang-scroll::-webkit-scrollbar-track { background: transparent; }
    .pd-bar-row--bidang { height: 31px; flex-shrink: 0; }
    .pd-bar-row--bidang .pd-bar-row__top, .pd-bar-row--bidang .pd-bar-row__name, .pd-bar-row--bidang .pd-bar-row__val { line-height: 18px; }
    .pd-bar-row--bidang .pd-bar-row__top { margin-bottom: 7px; }
    .pd-bar-row--bidang .pd-bar-row__name { font-size: 13px; }
    .pd-bar-row--bidang .pd-bar-row__val { font-size: 12px; font-weight: 700; }
    .pd-bar-row--bidang .pd-bar-row__val small { font-size: 10.5px; font-weight: 400; color: #94a3b8; }
    .pd-bar-row--bidang .pd-track { height: 6px; background: #f6f8fb; }
    .pd-live { display: inline-flex; align-items: center; gap: .45rem; font-weight: 700; color: #334155; }
    .pd-live-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--pd-cyan); box-shadow: 0 0 0 3px rgba(8,145,178,.15); animation: pdPulse 2s ease-in-out infinite; }
    .pd-card-foot .pd-foot-link { display: inline-flex; align-items: center; font-weight: 700; }
    .pd-foot-link i { font-size: 17px; margin-left: .1rem; transition: transform .18s ease; }
    .pd-foot-link:hover i { transform: translateX(2px); }

    /* ---------- Ring Status Magang ---------- */
    .pd-status-ring { position: relative; width: 190px; max-width: 100%; aspect-ratio: 1 / 1; margin: 0 auto; }
    .pd-status-ring svg { display: block; width: 100%; height: 100%; filter: drop-shadow(0 8px 12px rgba(8,145,178,.14)); }
    .pd-status-ring__center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; pointer-events: none; }
    .pd-status-ring__pct { font-family: var(--pkl-font-body); font-size: 32px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; color: var(--pd-ink); font-variant-numeric: tabular-nums; }
    .pd-status-ring__cap { margin-top: .5rem; font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--pd-muted); }
    .pd-status-ring__tot { margin-top: .2rem; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--pd-cyan); }
    .pd-status-body .pd-legend3 { margin-top: 1.6rem; padding-top: 0; }
    .pd-status-info { margin-top: 1.3rem; padding-top: 1.15rem; border-top: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 1rem; }
    .pd-split__top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .45rem; font-size: 11.5px; }
    .pd-split__t { font-weight: 600; color: #334155; }
    .pd-split__v { display: inline-flex; gap: .8rem; color: var(--pd-muted); }
    .pd-split__i { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; }
    .pd-split__i b { color: var(--pd-ink); font-weight: 700; }
    .pd-split__bar { display: flex; height: 6px; border-radius: 999px; overflow: hidden; background: #f1f5f9; gap: 2px; }
    .pd-split__bar span { display: block; height: 100%; border-radius: 999px; }
    .pd-note { display: flex; align-items: center; gap: .7rem; background: linear-gradient(180deg, #fafcfe, #f4f8fb); border: 1px solid #e9eef4; border-radius: .7rem; padding: .6rem .75rem; transition: border-color .18s ease, box-shadow .18s ease; }
    .pd-note:hover { border-color: #cdeaf2; box-shadow: 0 6px 14px -10px rgba(8,145,178,.4); }
    .pd-note__t { font-size: 12.5px; font-weight: 700; color: #334155; line-height: 1.25; }
    #ins-info { font-weight: 600; color: #334155; }

    /* ---------- Absensi Hari Ini ---------- */
    .pd-live-dot--green { display: inline-block; background: #10b981; width: 9px; height: 9px; flex-shrink: 0; box-shadow: 0 0 0 4px rgba(16,185,129,.16); }
    .pd-abs-panel { background: linear-gradient(180deg, #f9fbfd, #f1f5f9); border: 1px solid #e9eef4; border-radius: .9rem; padding: 1.1rem .8rem .9rem; text-align: center; margin-bottom: .9rem; box-shadow: inset 0 1px 0 rgba(255,255,255,.9); }
    .pd-gauge { position: relative; width: 100%; max-width: 230px; margin: 0 auto; }
    .pd-gauge svg { display: block; width: 100%; height: auto; filter: drop-shadow(0 6px 8px rgba(16,185,129,.18)); }
    .pd-gauge__pct { position: absolute; left: 0; right: 0; bottom: -2px; font-family: var(--pkl-font-body); font-size: 32px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; color: var(--pd-ink); font-variant-numeric: tabular-nums; }
    .pd-abs-status { margin-top: .55rem; font-size: 10.5px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
    .pd-abs-count { margin-top: .15rem; font-size: 10.5px; color: var(--pd-muted); letter-spacing: .02em; }
    .pd-abs-list { display: flex; flex-direction: column; gap: .5rem; margin-bottom: .9rem; }
    .pd-abs-row { display: flex; align-items: center; gap: .6rem; background: #f8fafc; border: 1px solid #e9eef4; border-radius: .65rem; padding: .6rem .75rem; transition: border-color .18s ease, background .18s ease, box-shadow .18s ease; }
    .pd-abs-row:hover { background: #fff; border-color: #d5dfe9; box-shadow: 0 6px 14px -10px rgba(15,23,42,.25); }
    .pd-abs-row > i { font-size: 18px; flex-shrink: 0; }
    .pd-abs-row__t { flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #334155; }
    .pd-abs-row > b { font-size: 12px; font-weight: 700; }
    .pd-abs-row--muted { background: #fcfdfe; }
    .pd-abs-row--muted > i, .pd-abs-row--muted .pd-abs-row__t, .pd-abs-row--muted > b { color: #94a3b8; font-weight: 500; }
    .pd-abs-link { display: flex; align-items: center; justify-content: space-between; gap: .6rem; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: .7rem; padding: .75rem .9rem; font-size: 12.5px; font-weight: 700; color: #1e293b; line-height: 1.3; transition: all .18s ease; }
    .pd-abs-link i { font-size: 20px; color: var(--pd-cyan); flex-shrink: 0; transition: transform .18s ease; }
    .pd-abs-link:hover { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; box-shadow: 0 8px 16px -10px rgba(8,145,178,.5); }
    .pd-abs-link:hover i { transform: translateX(3px); }

    /* ---------- Donut & legend ---------- */
    .pd-donut { min-height: 230px; display: flex; align-items: center; justify-content: center; }
    .pd-legend3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; text-align: center; padding-top: .5rem; }
    .pd-legend3__item { background: #f8fafc; border: 1px solid #eef2f7; border-radius: .6rem; padding: .5rem .3rem; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
    .pd-legend3__item:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 8px 16px -10px rgba(15,23,42,.3); }
    .pd-legend3__label { display: flex; align-items: center; justify-content: center; font-size: 10.5px; font-weight: 700; }
    .pd-legend3__pct { font-family: var(--pkl-font-body); font-size: 1.05rem; font-weight: 800; color: var(--pd-ink); margin-top: .15rem; font-variant-numeric: tabular-nums; }
    .pd-legend3__n { font-size: 10.5px; color: var(--pd-muted); }

    /* ---------- Ring absensi ---------- */
    .pd-ring { position: relative; width: 140px; height: 140px; margin: .25rem auto .8rem; }
    .pd-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .pd-ring__center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .pd-ring__cap { font-size: 9.5px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--pd-muted); }
    .pd-ring__num { font-family: var(--pkl-font-body); font-size: 1.9rem; font-weight: 800; line-height: 1.05; color: var(--pd-ink); font-variant-numeric: tabular-nums; }
    .pd-ring__sub { font-size: 10px; font-weight: 700; color: #059669; }
    .pd-mini-list { display: flex; flex-direction: column; gap: .35rem; margin-top: .3rem; }
    .pd-mini { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #eef2f7; border-radius: .55rem; padding: .4rem .6rem; font-size: 12px; font-weight: 500; color: #334155; }
    .pd-mini b { color: var(--pd-ink); }
    .pd-mini i { vertical-align: middle; }
    .pd-mini--muted { color: var(--pd-muted); }

    /* ---------- Instansi ---------- */
    .pd-ins-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
    .pd-search { position: relative; }
    .pd-search i { position: absolute; left: .55rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; transition: color .15s ease; }
    .pd-search:focus-within i { color: var(--pd-cyan); }
    .pd-search input { border: 1px solid var(--pd-line); border-radius: .55rem; background: #f8fafc; padding: .38rem .6rem .38rem 1.9rem; font-size: 12px; width: 190px; outline: none; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease; }
    .pd-search input:focus { border-color: var(--pd-cyan); box-shadow: 0 0 0 3px rgba(8,145,178,.14); background: #fff; }
    .pd-select { border: 1px solid var(--pd-line); border-radius: .55rem; background: #f8fafc; padding: .38rem .5rem; font-size: 12px; font-weight: 500; color: var(--pd-ink); outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .pd-select:hover { border-color: #b6c4d3; }
    .pd-select:focus { border-color: var(--pd-cyan); box-shadow: 0 0 0 3px rgba(8,145,178,.14); }
    .pd-ins-scroll { max-height: 400px; overflow-y: auto; overflow-x: hidden; padding-right: .25rem; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .pd-ins-scroll::-webkit-scrollbar { width: 6px; }
    .pd-ins-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    .pd-ins-scroll::-webkit-scrollbar-track { background: transparent; }
    .pd-ins-row { display: flex; align-items: center; gap: .7rem; padding: .4rem .5rem; border-radius: .6rem; cursor: pointer; transition: background .15s ease, opacity .15s ease, box-shadow .15s ease; }
    .pd-ins-row:hover { background: #f4f8fb; }
    .pd-ins-row.is-selected { background: #ecfeff; box-shadow: inset 0 0 0 1px rgba(8,145,178,.35), 0 6px 14px -10px rgba(8,145,178,.5); }
    .pd-ins-row.is-dim { opacity: .45; }
    .pd-ins-rank { width: 22px; text-align: right; font-size: 11px; font-weight: 700; color: #94a3b8; flex-shrink: 0; font-variant-numeric: tabular-nums; }
    .pd-ins-badge { width: 38px; height: 32px; border-radius: .55rem; display: flex; align-items: center; justify-content: center; font-size: 10.5px; font-weight: 700; letter-spacing: -.01em; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(255,255,255,.5); }
    .pd-ins-main { flex: 1; min-width: 0; }
    .pd-ins-top { display: flex; justify-content: space-between; gap: .5rem; font-size: 12.5px; margin-bottom: .25rem; }
    .pd-ins-name { font-weight: 500; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pd-ins-n { font-weight: 700; color: var(--pd-ink); white-space: nowrap; }
    .pd-ins-n small { font-weight: 400; color: var(--pd-muted); }

    /* ---------- Sertifikat ---------- */
    .pd-stack { display: flex; flex-direction: column; gap: .65rem; }
    .pd-stat-row { display: flex; align-items: center; gap: .8rem; padding: .8rem .85rem; background: #f3f6fa; border: 1px solid transparent; border-radius: .75rem; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .pd-stat-row:hover { transform: translateY(-2px); box-shadow: 0 10px 18px -12px rgba(15,23,42,.35); border-color: #e2e8f0; }
    .pd-stat-row--hi { background: linear-gradient(180deg, #f0fbfd, #e8f7fb); border-color: #cdeaf2; }
    .pd-stat-row--hi:hover { border-color: #a5dbe9; box-shadow: 0 10px 18px -12px rgba(8,145,178,.5); }
    .pd-stat-row__ico { width: 38px; height: 38px; border-radius: .6rem; background: var(--pd-tile-bg); border: 1px solid #a5f3fc; color: #0891b2; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .pd-stat-row__ico svg { width: 20px; height: 20px; display: block; }
    .pd-stat-row__ico--plain { background: #fff; border-color: #e2e8f0; color: #64748b; }
    .pd-stat-row__t { font-size: 13px; font-weight: 700; color: #1e293b; line-height: 1.25; }
    .pd-stat-row__s { margin-top: .1rem; font-size: 11px; font-weight: 600; color: #64748b; line-height: 1.3; }
    .pd-stat-row__s--hi { color: #0891b2; }
    .pd-stat-row__n { font-family: var(--pkl-font-body); font-size: 1.1rem; font-weight: 800; color: var(--pd-ink); font-variant-numeric: tabular-nums; }
    .pd-stat-row__n--hi { color: #0891b2; }

    /* ---------- Akan selesai (komponen lama) ---------- */
    .pd-person { height: 100%; background: #f8fafc; border-radius: .75rem; padding: .85rem; transition: background .15s ease; }
    .pd-person:hover { background: #f1f5f9; }
    .pd-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(145deg, rgba(8,145,178,.18), rgba(8,145,178,.08)); color: #0e7490; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(8,145,178,.15); }
    .pd-avatar--sm { width: 32px; height: 32px; font-size: 11px; }
    .pd-person__name { font-size: 13.5px; font-weight: 700; color: var(--pd-ink); line-height: 1.25; }
    .pd-person__rows { margin-top: .7rem; padding-top: .55rem; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: .25rem; font-size: 12px; }
    .pd-person__rows > div { display: flex; justify-content: space-between; gap: .75rem; }
    .pd-person__rows span { color: var(--pd-muted); }
    .pd-person__rows b { font-weight: 600; color: var(--pd-ink); max-width: 65%; text-align: right; }

    /* ---------- Akan selesai magang ---------- */
    .pd-as-head { gap: .6rem 1rem; margin-bottom: 1.1rem; align-items: center; }
    .pd-as-head__l { display: flex; align-items: center; flex-wrap: wrap; gap: .6rem; min-width: 0; }
    .pd-as-head__r { display: flex; align-items: center; gap: 1rem; margin-left: auto; }
    .pd-as-badge { background: linear-gradient(180deg, #fff0f2, #ffe4e6); color: #be123c; border-radius: 999px; padding: .22rem .7rem; font-size: 10.5px; font-weight: 700; white-space: nowrap; box-shadow: inset 0 0 0 1px #fecdd3; }
    .pd-as-badge--muted { background: #f1f5f9; color: #64748b; box-shadow: inset 0 0 0 1px #e2e8f0; }
    .pd-as-sort { font-size: 11px; font-weight: 600; color: var(--pd-muted); white-space: nowrap; }
    .pd-as-all { display: inline-flex; align-items: center; gap: .15rem; font-size: 12.5px; font-weight: 700; color: var(--pd-cyan); white-space: nowrap; }
    .pd-as-all i { font-size: 18px; transition: transform .18s ease; }
    .pd-as-all:hover { color: #0e7490; }
    .pd-as-all:hover i { transform: translateX(3px); }
    .pd-as-card { height: 100%; background: linear-gradient(180deg, #fafcfe, #f5f8fb); border: 1px solid #e5ebf1; border-radius: .8rem; padding: .85rem .95rem .7rem; transition: background .2s ease, box-shadow .2s ease, border-color .2s ease, transform .2s ease; }
    .pd-as-card:hover { background: #fff; border-color: #bfe6ef; box-shadow: 0 14px 26px -16px rgba(8,145,178,.45); transform: translateY(-2px); }
    .pd-as-top { display: flex; align-items: flex-start; gap: .65rem; }
    .pd-as-ava { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(145deg, #fff7d6, #fde68a); border: 1px solid #fde68a; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 19px; flex-shrink: 0; box-shadow: inset 0 1px 0 rgba(255,255,255,.7), 0 3px 8px -3px rgba(234,88,12,.35); }
    .pd-as-name { font-size: 15px; font-weight: 700; color: var(--pd-ink); line-height: 1.2; }
    .pd-as-sub { margin-top: .15rem; font-size: 12px; color: #64748b; }
    .pd-as-days { flex-shrink: 0; background: #ffe4e6; color: #be123c; border-radius: .45rem; padding: .2rem .5rem; font-size: 10.5px; font-weight: 700; white-space: nowrap; box-shadow: inset 0 0 0 1px #fecdd3; }
    .pd-as-days--amber { background: #fef3c7; color: #b45309; box-shadow: inset 0 0 0 1px #fde68a; }
    .pd-as-days--cyan { background: #cffafe; color: #0e7490; box-shadow: inset 0 0 0 1px #a5f3fc; }
    .pd-as-info { display: flex; align-items: center; gap: .35rem; margin-top: .55rem; font-size: 11px; font-weight: 600; color: #64748b; }
    .pd-as-info i { font-size: 15px; flex-shrink: 0; }
    .pd-as-act { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; margin-top: .7rem; padding-top: .6rem; border-top: 1px solid #e9eef4; }
    .pd-as-btn { border: 1px solid transparent; background: transparent; border-radius: .45rem; padding: .32rem .6rem; font-size: 11.5px; font-weight: 700; color: #334155; white-space: nowrap; transition: all .15s ease; }
    .pd-as-btn:hover { background: #eef2f6; color: var(--pd-ink); }
    .pd-as-btn--cyan { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; }
    .pd-as-btn--cyan:hover { background: var(--pd-cyan); border-color: var(--pd-cyan); color: #fff; box-shadow: 0 6px 12px -6px rgba(8,145,178,.6); }

    /* ---------- Tabel aktivitas ---------- */
    .pd-wrap .table-responsive { border: 1px solid #e6ecf2; border-radius: .9rem; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.03), 0 8px 20px -16px rgba(15,23,42,.18); scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .pd-wrap .pd-table { --bs-table-bg: transparent; --bs-table-accent-bg: transparent; --bs-table-hover-bg: transparent; --bs-table-striped-bg: transparent; margin: 0; border-collapse: separate; border-spacing: 0; }
    .pd-wrap .pd-table thead th { background: linear-gradient(180deg, #fafcfe, #f1f5f9); color: #475569; font-size: 10.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; padding: .85rem 1rem; border: 0; border-bottom: 1px solid #e3e9f0 !important; white-space: nowrap; }
    .pd-wrap .pd-table tbody td { padding: .8rem 1rem; font-size: 13px; font-weight: 400; vertical-align: middle; background: transparent; border: 0; border-bottom: 1px solid #eef2f6; transition: background .18s ease, box-shadow .18s ease; }
    .pd-wrap .pd-table tbody tr:nth-last-child(2) td { border-bottom: 0; }
    .pd-wrap .pd-table tbody tr:hover td { background: #f4fafc; }
    .pd-wrap .pd-table tbody tr:hover td:first-child { box-shadow: inset 3px 0 0 var(--pd-cyan); }
    .pd-table .pd-avatar--sm { width: 36px; height: 36px; font-size: 11px; background: linear-gradient(145deg, rgba(8,145,178,.2), rgba(8,145,178,.07)); box-shadow: inset 0 0 0 1px rgba(8,145,178,.2), 0 3px 8px -4px rgba(8,145,178,.4); }
    .pd-table .pd-person__name { font-size: 13.5px; font-weight: 700; letter-spacing: -.005em; }
    .pd-table .pd-meta { font-variant-numeric: tabular-nums; line-height: 1.45; }
    .pd-table td:nth-child(2) .fw-semibold { color: #1e293b; }
    .pd-table td:nth-child(3) .pd-chip { min-width: 54px; justify-content: center; }
    .pd-table td:nth-child(4) .fw-semibold { color: #0f172a; }
    .pd-table .pd-pill { border-radius: 999px; padding: .28rem .75rem .28rem .6rem; font-size: 11px; font-weight: 700; }
    .pd-table .pd-pill .pd-dot2 { width: 7px; height: 7px; margin-right: .45rem; box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 18%, transparent); }
    .pd-table .pd-pill--amber { background: #fffbeb; color: #b45309; box-shadow: inset 0 0 0 1px #fde68a; }
    .pd-table .pd-pill--cyan { background: #ecfeff; color: #0e7490; box-shadow: inset 0 0 0 1px #a5f3fc; }
    .pd-table .pd-btn--sm { min-width: 66px; border-radius: .55rem; padding: .42rem .95rem; }

    @media (max-width: 767.98px) {
        .pd-title { font-size: 19px; }
        .pd-search input { width: 150px; }
        .pd-tools { flex-wrap: wrap; justify-content: flex-start; }
    }
    @media (min-width: 768px) and (max-width: 1399.98px) {
        .pd-tools { flex-basis: 100%; justify-content: flex-start; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pd-wrap .row > [class*="col-"], .pd-bar-row .pd-fill { animation: none !important; }
        .pd-dot, .pd-live-dot, .pd-fill, .pd-metric, .pd-btn--reset i { animation: none !important; transition: none; }
        .pd-dd.is-open .pd-dd__menu { animation: none; }
    }
</style>

@section('scripts')
<script>
window.addEventListener('load', function () {
    var nf = function (n) { return Number(n).toLocaleString('id-ID'); };
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
    var $ = function (id) { return document.getElementById(id); };
    var COLORS = { Aktif: '#10b981', Menunggu: '#f59e0b', Selesai: '#0891b2' };

    // ---------- Dropdown filter header ----------
    (function () {
        var form = $('pd-filter-form');
        var dds = document.querySelectorAll('[data-dd]');
        if (!form || !dds.length) return;
        var closeAll = function () {
            dds.forEach(function (d) {
                d.classList.remove('is-open');
                d.querySelector('.pd-dd__btn').setAttribute('aria-expanded', 'false');
            });
        };
        dds.forEach(function (dd) {
            var btn = dd.querySelector('.pd-dd__btn'), input = dd.querySelector('input[type=hidden]');
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = dd.classList.contains('is-open');
                closeAll();
                if (!open) {
                    dd.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                    var sel = dd.querySelector('.pd-dd__opt.is-sel');
                    if (sel) sel.scrollIntoView({ block: 'nearest' });
                }
            });
            dd.querySelectorAll('.pd-dd__opt').forEach(function (opt) {
                opt.addEventListener('click', function () {
                    var v = opt.getAttribute('data-value');
                    if (input.value === v) { closeAll(); return; }
                    input.value = v;
                    form.submit();
                });
            });
        });
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });
    })();

    // ---------- Filter tabel aktivitas ----------
    (function () {
        var q = $('act-q'), tbl = $('act-table');
        if (!q || !tbl) return;
        q.addEventListener('input', function () {
            var term = q.value.trim().toLowerCase(), shown = 0;
            tbl.querySelectorAll('tbody tr[data-search]').forEach(function (tr) {
                var ok = !term || tr.getAttribute('data-search').indexOf(term) !== -1;
                tr.classList.toggle('d-none', !ok);
                if (ok) shown++;
            });
            var empty = $('act-empty');
            if (empty) empty.classList.toggle('d-none', shown > 0 || !term);
        });
    })();

    if (typeof ApexCharts === 'undefined') return;

    // ---------- Peserta per instansi ----------
    (function () {
        var RAW = @json($instansi_raw).map(function (r) {
            return { n: r.instansi, j: r.status_peserta, s: r.status_magang, c: Number(r.jumlah) };
        });
        if (!$('ins-list')) return;

        var PALETTE = ['#0891b2', '#059669', '#0284c7', '#0e7490', '#10b981', '#38bdf8', '#14b8a6', '#6366f1'];
        var STATUS = ['Aktif', 'Menunggu', 'Selesai'];
        var shown = [], selected = null, donut = null;

        var initials = function (name) {
            var w = name.replace(/[^A-Za-z0-9 ]/g, ' ').split(/\s+/).filter(Boolean);
            return (w.slice(0, 3).map(function (x) { return x.charAt(0); }).join('') || '?').toUpperCase();
        };
        var tint = function (hex, a) {
            var n = parseInt(hex.slice(1), 16);
            return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
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
                if (t.hasOwnProperty(r.s)) t[r.s] += r.c;
            });
            return STATUS.map(function (k) { return t[k]; });
        }

        function drawLegend(s) {
            $('ins-legend').innerHTML = STATUS.map(function (k, i) {
                return '<div class="pd-mini"><span><i class="pd-dot2" style="background:' + COLORS[k] + '"></i>' + k + '</span><b>' + nf(s[i]) + '</b></div>';
            }).join('');
        }

        function drawDonut() {
            var s = statusTotals(), total = s.reduce(function (a, b) { return a + b; }, 0);
            $('ins-sub').textContent = selected || 'Semua instansi';
            $('ins-sub').title = selected || '';
            $('ins-clear').classList.toggle('d-none', !selected);
            drawLegend(s);
            if (donut) { donut.updateSeries(total ? s : [0, 0, 0]); return; }
            donut = new ApexCharts($('chart-instansi-status'), {
                series: s,
                chart: { type: 'donut', width: 130, height: 130, fontFamily: 'inherit' },
                labels: STATUS,
                colors: STATUS.map(function (k) { return COLORS[k]; }),
                legend: { show: false },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ['#fff'] },
                noData: { text: 'Tidak ada data' },
                plotOptions: { pie: { donut: { size: '68%', labels: {
                    show: true,
                    name: { show: true, fontSize: '9px', offsetY: -2 },
                    value: { show: true, fontSize: '16px', fontWeight: 700, offsetY: 2, formatter: function (v) { return nf(v); } },
                    total: { show: true, label: 'Total', fontSize: '9px', formatter: function (w) { return nf(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0)); } }
                } } } }
            });
            donut.render();
        }

        function drawList() {
            var max = shown.length ? shown[0].total : 1;
            $('ins-list').innerHTML = shown.map(function (o, i) {
                var color = PALETTE[i % PALETTE.length];
                var cls = 'pd-ins-row' + (selected === o.n ? ' is-selected' : '') + (selected && selected !== o.n ? ' is-dim' : '');
                return '<div class="' + cls + '" data-i="' + i + '" title="' + esc(o.n) + '">'
                    + '<span class="pd-ins-rank">' + String(i + 1).padStart(2, '0') + '</span>'
                    + '<span class="pd-ins-badge" style="background:' + tint(color, 0.12) + ';color:' + color + '">' + esc(initials(o.n)) + '</span>'
                    + '<div class="pd-ins-main"><div class="pd-ins-top"><span class="pd-ins-name">' + esc(o.n) + '</span>'
                    + '<span class="pd-ins-n">' + nf(o.total) + ' <small>peserta</small></span></div>'
                    + '<div class="pd-track"><div class="pd-fill" style="width:' + Math.max(2, Math.round(o.total / max * 100)) + '%;background:' + color + '"></div></div></div>'
                    + '</div>';
            }).join('');
        }

        function render() {
            var list = listInstansi(), top = parseInt($('ins-top').value, 10);
            shown = top > 0 ? list.slice(0, top) : list;
            if (selected && !shown.some(function (o) { return o.n === selected; })) selected = null;
            var total = list.reduce(function (a, o) { return a + o.total; }, 0);
            $('ins-info').textContent = 'Menampilkan ' + shown.length + ' dari ' + nf(list.length) + ' institusi pendidikan terdaftar';
            $('ins-empty').classList.toggle('d-none', shown.length > 0);
            $('ins-scroll').classList.toggle('d-none', shown.length === 0);
            drawList();
            drawDonut();
        }

        $('ins-list').addEventListener('click', function (e) {
            var row = e.target.closest('.pd-ins-row');
            if (!row) return;
            var o = shown[parseInt(row.getAttribute('data-i'), 10)];
            if (!o) return;
            selected = selected === o.n ? null : o.n;
            drawList();
            drawDonut();
        });

        var timer;
        $('ins-q').addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(render, 180); });
        ['ins-status', 'ins-jenis', 'ins-top'].forEach(function (id) { $(id).addEventListener('change', render); });
        $('ins-export').addEventListener('click', function (e) {
            e.preventDefault();
            var m = {};
            RAW.forEach(function (r) {
                var o = m[r.n] || (m[r.n] = { n: r.n, Aktif: 0, Menunggu: 0, Selesai: 0, Siswa: 0, Mahasiswa: 0, t: 0 });
                if (STATUS.indexOf(r.s) !== -1) o[r.s] += r.c;
                if (r.j === 'Siswa' || r.j === 'Mahasiswa') o[r.j] += r.c;
                o.t += r.c;
            });
            var rows = Object.keys(m).map(function (k) { return m[k]; })
                .sort(function (a, b) { return b.t - a.t || a.n.localeCompare(b.n); });
            var q = function (v) { return '"' + String(v).replace(/"/g, '""') + '"'; };
            var csv = ['Asal Instansi', 'Aktif', 'Menunggu', 'Selesai', 'Siswa', 'Mahasiswa', 'Total'].map(q).join(';') + '\r\n'
                + rows.map(function (o) { return [q(o.n), o.Aktif, o.Menunggu, o.Selesai, o.Siswa, o.Mahasiswa, o.t].join(';'); }).join('\r\n');
            var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'rekap-instansi-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
        });
        $('ins-clear').addEventListener('click', function (e) { e.preventDefault(); selected = null; drawList(); drawDonut(); });
        render();
    })();
});
</script>
@endsection
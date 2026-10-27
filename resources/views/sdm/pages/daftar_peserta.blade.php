@php
    use Illuminate\Support\Facades\DB;

    $search        = trim((string) request('q'));
    $status_filter = (string) request('status');
    $bulan_dari    = request('bulan_dari') ? str_pad((string) request('bulan_dari'), 2, '0', STR_PAD_LEFT) : '';
    $tahun_dari    = request('tahun_dari') ? (int) request('tahun_dari') : 0;
    $bulan_sampai  = request('bulan_sampai') ? str_pad((string) request('bulan_sampai'), 2, '0', STR_PAD_LEFT) : '';
    $tahun_sampai  = request('tahun_sampai') ? (int) request('tahun_sampai') : 0;
    $per_page      = 15;
    $page          = max(1, (int) request('p', 1));
    $offset        = ($page - 1) * $per_page;

    $query = DB::table('peserta as p')->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id');

    if ($search !== '') {
        $query->where(function ($w) use ($search) {
            $w->where('p.nama', 'like', '%' . $search . '%')
                ->orWhere('p.username', 'like', '%' . $search . '%')
                ->orWhere('p.asal_sekolah', 'like', '%' . $search . '%')
                ->orWhere('p.jurusan', 'like', '%' . $search . '%')
                ->orWhere('p.unit', 'like', '%' . $search . '%')
                ->orWhere('p.status_peserta', 'like', '%' . $search . '%')
                ->orWhere('p.status_magang', 'like', '%' . $search . '%')
                ->orWhere('p.keterangan', 'like', '%' . $search . '%')
                ->orWhere('b.bidang', 'like', '%' . $search . '%');
        });
    }

    if ($status_filter !== '') {
        $query->where('p.status_magang', $status_filter);
    }

    // Gabung bulan + tahun jadi format YYYY-MM untuk filter tanggal
    $from_date = ($bulan_dari && $tahun_dari) ? $tahun_dari . '-' . $bulan_dari : '';
    $to_date   = ($bulan_sampai && $tahun_sampai) ? $tahun_sampai . '-' . $bulan_sampai : '';

    $start = '';
    $end   = '';

    try {
        if ($from_date && $to_date) {
            $start_dt = new \DateTime($from_date . '-01');
            $end_dt   = new \DateTime($to_date . '-01');
            $end_dt->modify('last day of this month');
            $start = $start_dt->format('Y-m-d');
            $end   = $end_dt->format('Y-m-d');
        } elseif ($from_date) {
            $start_dt = new \DateTime($from_date . '-01');
            $end_dt   = clone $start_dt;
            $end_dt->modify('last day of this month');
            $start = $start_dt->format('Y-m-d');
            $end   = $end_dt->format('Y-m-d');
        } elseif ($to_date) {
            $start_dt = new \DateTime($to_date . '-01');
            $end_dt   = clone $start_dt;
            $end_dt->modify('last day of this month');
            $start = $start_dt->format('Y-m-d');
            $end   = $end_dt->format('Y-m-d');
        }
    } catch (\Exception $e) {
    }

    if ($start && $end) {
        $query->whereRaw("NOT (p.tgl_keluar < ? OR p.tgl_masuk > ?)", [$start, $end]);
    }

    $total      = (int) (clone $query)->count();
    $total_page = (int) ceil($total / $per_page);

    $data = (clone $query)
        ->orderBy('p.id', 'desc')
        ->offset($offset)
        ->limit($per_page)
        ->get(['p.*', DB::raw('b.bidang AS nama_bidang')]);

    $status_badge = ['Aktif' => 'success', 'Menunggu' => 'warning', 'Selesai' => 'secondary'];

    // Hitung ringkasan
    $sum = DB::table('peserta')->selectRaw("
        COUNT(*) as total,
        SUM(status_magang='Aktif') as aktif,
        SUM(status_magang='Menunggu') as menunggu,
        SUM(status_magang='Selesai') as selesai
    ")->first();

    $page_url = function ($p) use ($search, $status_filter, $bulan_dari, $tahun_dari, $bulan_sampai, $tahun_sampai) {
        return url('sdm/peserta') . '?' . http_build_query([
            'p'            => $p,
            'q'            => $search,
            'status'       => $status_filter,
            'bulan_dari'   => $bulan_dari,
            'tahun_dari'   => $tahun_dari ?: '',
            'bulan_sampai' => $bulan_sampai,
            'tahun_sampai' => $tahun_sampai ?: '',
        ]);
    };

    $export_url = url('sdm/peserta/export') . '?' . http_build_query([
        'bulan_dari'   => $bulan_dari,
        'tahun_dari'   => $tahun_dari ?: '',
        'bulan_sampai' => $bulan_sampai,
        'tahun_sampai' => $tahun_sampai ?: '',
        'q'            => $search,
        'status'       => $status_filter,
    ]);
@endphp

<style>
    /* Tombol reset filter / segarkan data: latar putih, tetap putih saat hover/fokus/klik */
    .btn.pkl-reset,
    .btn.pkl-reset:hover,
    .btn.pkl-reset:focus,
    .btn.pkl-reset:focus-visible,
    .btn.pkl-reset:active,
    .btn.pkl-reset.active {
        background: #fff !important;
        background-color: #fff !important;
        box-shadow: none;
    }
    .btn.pkl-reset { border: 1px solid #cbd5e1 !important; color: #475569 !important; }
    .btn.pkl-reset:hover,
    .btn.pkl-reset:focus-visible { border-color: #94a3b8 !important; color: #0f172a !important; }
    .btn.pkl-reset:focus-visible { outline: 2px solid #0891b2; outline-offset: 2px; }
    .btn.pkl-reset i { background: transparent !important; color: inherit !important; }
</style>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3"><span class="sdm-title-ico"><i class="bx bxs-group"></i></span>Daftar Peserta</div>
</div>

<!-- SUMMARY CARDS -->
@php $scTotal = (int) ($sum->total ?? 0); @endphp
<style>
    /* KPI card - desain sama dengan menu Rekap Absensi (override tampilan partials.stat_card_v2) */
    .kpi-rk {
        --rk-ink: #0f172a;
        --rk-panel: #ffffff;
        --rk-line: #e2e8f0;
        --rk-text: #1e293b;
        --rk-muted: #64748b;
        --rk-accent: #0891b2;
        --rk-hadir: #059669;
        --rk-izin: #2563eb;
        --rk-sakit: #e11d48;
        --rk-alpha: #64748b;
        --rk-warn: #d97706;
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; margin-bottom: 1.5rem;
    }
    .kpi-rk--c3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .kpi-rk--c6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .kpi-rk__item { min-width: 0; display: flex; }
    .kpi-rk .ui-stat-card { --tone: var(--rk-izin); position: relative; overflow: hidden; width: 100%; min-height: 128px; padding: 1rem 1.1rem; border-radius: 12px; border: 1px solid var(--rk-line); background: var(--rk-panel); box-shadow: 0 1px 2px rgba(15, 23, 42, .04); display: flex; flex-direction: column; color: var(--rk-text); }
    .kpi-rk .ui-stat-card::before { content: ''; position: absolute; top: -50px; right: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--tone); opacity: .08; }
    .kpi-rk .ui-stat-card[data-stat-color="primary"]   { --tone: var(--rk-accent); }
    .kpi-rk .ui-stat-card[data-stat-color="success"]   { --tone: var(--rk-hadir); }
    .kpi-rk .ui-stat-card[data-stat-color="info"]      { --tone: var(--rk-izin); }
    .kpi-rk .ui-stat-card[data-stat-color="warning"]   { --tone: var(--rk-warn); }
    .kpi-rk .ui-stat-card[data-stat-color="danger"]    { --tone: var(--rk-sakit); }
    .kpi-rk .ui-stat-card[data-stat-color="secondary"],
    .kpi-rk .ui-stat-card[data-stat-color="dark"]      { --tone: var(--rk-alpha); }
    .kpi-rk .ui-stat-card__head { position: absolute; top: 1rem; right: 1.1rem; margin: 0; padding: 0; }
    .kpi-rk .ui-stat-card__badge { display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; border-radius: 8px; background: rgba(15, 23, 42, .05); color: var(--tone); font-size: 16px; }
    .kpi-rk .ui-stat-card__body { position: relative; padding: 0; flex: 1; display: flex; }
    .kpi-rk .ui-stat-card__meta { display: flex; flex-direction: column; width: 100%; padding-right: 2rem; }
    .kpi-rk .ui-stat-card__label { order: -1; margin-bottom: .5rem; font-family: var(--pkl-font-body); font-size: 12px; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--tone); }
    .kpi-rk .ui-stat-card__value { margin: 0 0 .5rem; font-family: var(--pkl-font-body); font-size: 24px; font-weight: 800; line-height: 1.1; color: var(--rk-ink); font-variant-numeric: tabular-nums; }
    .kpi-rk .ui-stat-card[data-stat-color="success"] .ui-stat-card__value { color: var(--rk-hadir); }
    .kpi-rk .ui-stat-card__sub { margin-top: auto; font-family: var(--pkl-font-body); font-size: 12.5px; font-weight: 400; line-height: 1.45; color: var(--rk-muted); white-space: normal; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    @media (max-width: 1199px) {
        .kpi-rk, .kpi-rk--c6 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .kpi-rk:not(.kpi-rk--c3):not(.kpi-rk--c6) { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575px) {
        .kpi-rk, .kpi-rk--c6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kpi-rk--c3 { grid-template-columns: 1fr; }
    }
</style>
<div class="kpi-rk">
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'total', 'value' => number_format($scTotal, 0, ',', '.'), 'label' => 'Total Peserta', 'icon' => 'bx bx-group', 'color' => 'primary', 'sub' => 'Seluruh peserta'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'aktif', 'value' => number_format((int) ($sum->aktif ?? 0), 0, ',', '.'), 'label' => 'Aktif', 'icon' => 'bx bx-user-check', 'color' => 'success', 'sub' => 'Sedang magang'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'menunggu', 'value' => number_format((int) ($sum->menunggu ?? 0), 0, ',', '.'), 'label' => 'Menunggu', 'icon' => 'bx bx-time-five', 'color' => 'warning', 'sub' => 'Belum mulai'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'selesai', 'value' => number_format((int) ($sum->selesai ?? 0), 0, ',', '.'), 'label' => 'Selesai', 'icon' => 'bx bx-check-double', 'color' => 'secondary', 'sub' => 'Magang selesai'])
</div>

<div class="card">
    <div class="card-body">
        <!-- TOOLBAR -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="card-title mb-0"><i class="bx bxs-group me-1"></i> Daftar Peserta</h5>
            <a href="{{ url('sdm/peserta/tambah') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-user-plus me-1"></i> Tambah Peserta
            </a>
        </div>

        <!-- FILTER & SEARCH -->
        <form method="GET" action="{{ url('sdm/peserta') }}" class="ui-toolbar" data-live-search>
            <div class="ui-search">
                <i class="bx bx-search"></i>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama, username, sekolah, jurusan, unit, status, bidang..." value="{{ $search }}">
            </div>
            <select name="status" class="form-select form-select-sm" style="width:auto;">
                <option value="">-- Semua Status --</option>
                @foreach (['Aktif', 'Menunggu', 'Selesai'] as $st)
                    <option value="{{ $st }}" {{ $status_filter == $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
            <div class="d-flex align-items-center gap-1">
                <small class="text-muted text-nowrap">Dari</small>
                <select name="bulan_dari" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Bulan</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $bulan_dari == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>{{ date('M', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
                <select name="tahun_dari" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Tahun</option>
                    @for ($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $tahun_dari == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <small class="text-muted text-nowrap">s/d</small>
                <select name="bulan_sampai" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Bulan</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $bulan_sampai == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>{{ date('M', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
                <select name="tahun_sampai" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Tahun</option>
                    @for ($y = date('Y'); $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $tahun_sampai == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i>Filter</button>
            <a href="{{ url('sdm/peserta') }}" class="btn btn-sm pkl-reset" title="Reset filter / segarkan data" aria-label="Reset filter">
                <i class="bx bx-sync pkl-sync pkl-sync--spin"></i>
            </a>
            <a href="{{ $export_url }}" class="btn btn-sm btn-success" data-ui-loading data-ui-loading-text="Menyiapkan...">
                <i class="bx bx-file"></i> Export Excel
            </a>
        </form>

        <div class="table-responsive ui-table-wrap">
            <table class="table table-striped table-hover align-middle mb-0 small ui-table">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Asal Sekolah</th>
                        <th>Bidang</th>
                        <th>Unit</th>
                        <th>Periode</th>
                        <th>Durasi</th>
                        <th>Magang</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody data-empty-colspan="11" data-empty-text="Tidak ada data peserta.">
                @php $no = $offset + 1; @endphp
                @foreach ($data as $row)
                    @php
                        $badge   = $status_badge[$row->status_magang] ?? 'secondary';
                        $masuk   = new \DateTime($row->tgl_masuk);
                        $keluar  = new \DateTime($row->tgl_keluar);
                        $durasi  = $masuk->diff($keluar)->days;
                        $deltas  = ['total' => -1];
                        if ($row->status_magang === 'Aktif') {
                            $deltas['aktif'] = -1;
                        } elseif ($row->status_magang === 'Menunggu') {
                            $deltas['menunggu'] = -1;
                        } elseif ($row->status_magang === 'Selesai') {
                            $deltas['selesai'] = -1;
                        }
                    @endphp
                    <tr data-lg-row>
                        <td>{{ $no++ }}</td>
                        <td><code>{{ $row->username }}</code></td>
                        <td>
                            <strong>{{ $row->nama }}</strong>
                            <small class="text-muted d-block">{{ $row->jurusan }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $row->status_peserta == 'Siswa' ? 'info' : 'primary' }}">
                                {{ $row->status_peserta }}
                            </span>
                        </td>
                        <td>{{ $row->asal_sekolah }}</td>
                        <td>
                            {!! $row->nama_bidang
                                ? '<span class="badge bg-light text-dark border">' . e($row->nama_bidang) . '</span>'
                                : '<em class="text-muted">-</em>' !!}
                        </td>
                        <td>{{ $row->unit }}</td>
                        <td class="text-nowrap">
                            {{ date('d/m/Y', strtotime($row->tgl_masuk)) }}<br>
                            <small class="text-muted">s/d {{ date('d/m/Y', strtotime($row->tgl_keluar)) }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $durasi }} hari</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $badge }}">{{ $row->status_magang }}</span>
                        </td>
                        <td class="text-center text-nowrap">
                            <div class="ui-actions">
                            <a href="{{ url('sdm/peserta/tambah') }}?copy_id={{ $row->id }}"
                               class="btn btn-sm btn-info" title="Salin data peserta ini"
                               data-ui-confirm="Salin data {{ $row->nama }} sebagai data baru?"
                               data-confirm-title="Salin Data Peserta?"
                               data-confirm-ok="Salin">
                                <i class="bx bx-copy"></i>
                            </a>
                            <a href="{{ url('sdm/peserta/edit/' . $row->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="bx bx-edit"></i>
                            </a>
                            <form method="POST" action="{{ url('sdm/peserta/hapus/' . $row->id) }}" class="d-inline js-approval-form"
                                  data-action="delete"
                                  data-title="Berhasil Dihapus!"
                                  data-confirm-title="Hapus Peserta?"
                                  data-confirm-message="Hapus peserta '{{ $row->nama }}'? Akun login peserta juga akan ikut terhapus."
                                  data-confirm-ok="Hapus"
                                  data-counters='@json($deltas)'>
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr class="pkl-empty">
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bx bx-user-x fs-3 d-block mb-1"></i>
                            Tidak ada data peserta{!! $search ? ' untuk pencarian "<strong>' . e($search) . '</strong>"' : '' !!}.
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if ($total_page > 1)
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan {{ $offset + 1 }}–{{ min($offset + $per_page, $total) }} dari {{ $total }} data
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $page_url($page - 1) }}">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                    </li>
                    @for ($i = max(1, $page - 2); $i <= min($total_page, $page + 2); $i++)
                        <li class="page-item {{ $i == $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ $page_url($i) }}">
                                {{ $i }}
                            </a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $page_url($page + 1) }}">
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif

    </div>
</div>
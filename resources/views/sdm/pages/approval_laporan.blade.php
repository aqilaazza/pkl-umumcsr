@php
    use Illuminate\Support\Facades\DB;

    // ── Data libur untuk hitung hari kerja ──
    $libur_pekan = DB::table('libur_pekan')->pluck('hari_index')->map(fn ($v) => (int) $v)->all();
    $hari_libur  = DB::table('hari_libur')->pluck('tanggal')->all();

    $hitungHariKerja = function ($tgl_masuk, $tgl_keluar, $libur_pekan, $hari_libur) {
        if (empty($tgl_masuk) || empty($tgl_keluar) || $tgl_masuk == '0000-00-00' || $tgl_keluar == '0000-00-00') return 0;
        $start = new DateTime($tgl_masuk);
        $end   = new DateTime($tgl_keluar);
        $end->modify('+1 day');
        $total = 0;
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $date) {
            if (in_array((int) $date->format('w'), $libur_pekan)) continue;
            if (in_array($date->format('Y-m-d'), $hari_libur)) continue;
            $total++;
        }
        return $total;
    };

    // --- FILTER & PAGINATION ---
    // Hanya tampilkan laporan yang BELUM direview (status Menunggu)
    $search   = trim((string) request('q'));
    $per_page = 15;
    $page     = max(1, (int) request('p', 1));
    $offset   = ($page - 1) * $per_page;

    $query = DB::table('laporan_magang as lm')
        ->join('peserta as p', 'lm.username', '=', 'p.username')
        ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
        ->where('lm.sdm_status', 'Menunggu');

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('p.nama', 'like', '%' . $search . '%')
              ->orWhere('lm.username', 'like', '%' . $search . '%')
              ->orWhere('p.asal_sekolah', 'like', '%' . $search . '%')
              ->orWhere('p.jurusan', 'like', '%' . $search . '%')
              ->orWhere('p.status_peserta', 'like', '%' . $search . '%')
              ->orWhere('b.bidang', 'like', '%' . $search . '%')
              ->orWhere('lm.nama_file', 'like', '%' . $search . '%');
        });
    }

    // Dari tombol "Tinjau" di dashboard (?fokus=<id laporan>): hitung laporan itu ada di halaman berapa,
    // lalu buka halaman tersebut. Urutan tampilan: tgl_upload lalu id (naik).
    $fokus_id = (int) request('fokus');
    if ($fokus_id > 0 && ! request()->filled('p')) {
        $target = (clone $query)->where('lm.id', $fokus_id)->first(['lm.id', 'lm.tgl_upload']);
        if ($target) {
            $sebelum = (clone $query)->where(function ($w) use ($target) {
                $w->where('lm.tgl_upload', '<', $target->tgl_upload)
                  ->orWhere(function ($w2) use ($target) {
                      $w2->where('lm.tgl_upload', $target->tgl_upload)->where('lm.id', '<', $target->id);
                  });
            })->count();
            $page   = intdiv($sebelum, $per_page) + 1;
            $offset = ($page - 1) * $per_page;
        }
    }

    $total      = (clone $query)->count();
    $total_page = (int) ceil($total / $per_page);

    $data = $query
        ->leftJoin('users as u', 'lm.sdm_reviewed_by', '=', 'u.username')
        ->orderBy('lm.tgl_upload', 'asc')
        ->orderBy('lm.id', 'asc')
        ->limit($per_page)
        ->offset($offset)
        ->get([
            'lm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', 'p.tgl_masuk', 'p.tgl_keluar',
            'p.status_peserta',
            DB::raw('b.bidang AS nama_bidang'),
            DB::raw('u.nama AS reviewer_nama'),
            DB::raw("(SELECT COUNT(*) FROM absensi_peserta ap
                      WHERE ap.username = lm.username
                        AND ap.status = 'Hadir'
                        AND ap.tanggal BETWEEN p.tgl_masuk AND p.tgl_keluar) as total_hadir"),
        ]);

    // Ringkasan status
    $sum_row = DB::table('laporan_magang')
        ->selectRaw('COUNT(*) as total')
        ->selectRaw("SUM(sdm_status='Menunggu') as menunggu")
        ->selectRaw("SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu_manager")
        ->selectRaw("SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui")
        ->selectRaw("SUM(sdm_status='Ditolak' OR manager_status='Ditolak') as ditolak")
        ->first();

    $sum = [
        'total'            => (int) ($sum_row->total ?? 0),
        'menunggu'         => (int) ($sum_row->menunggu ?? 0),
        'menunggu_manager' => (int) ($sum_row->menunggu_manager ?? 0),
        'disetujui'        => (int) ($sum_row->disetujui ?? 0),
        'ditolak'          => (int) ($sum_row->ditolak ?? 0),
    ];

@endphp

<style>
    /* Tombol reset / segarkan data: latar putih */
    .ui-toolbar .ui-reset-btn,
    .ui-toolbar .ui-reset-btn:focus,
    .ui-toolbar .ui-reset-btn:active {
        background: #fff !important;
        border: 1px solid #cbd5e1 !important;
        color: #0891b2 !important;
    }
    .ui-toolbar .ui-reset-btn:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0e7490 !important;
    }
    .ui-toolbar .ui-reset-btn i { color: inherit; }
</style>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3"><span class="sdm-title-ico"><i class="bx bx-check-shield"></i></span>Approval Laporan Magang</div>
</div>

<!-- SUMMARY CARDS -->
@php $scTotal = (int) ($sum['total'] ?? 0); @endphp
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
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'total', 'value' => number_format($scTotal, 0, ',', '.'), 'label' => 'Total Laporan', 'icon' => 'bx bx-file', 'color' => 'primary', 'sub' => 'Seluruh laporan masuk'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'menunggu_review', 'value' => number_format((int) ($sum['menunggu'] ?? 0), 0, ',', '.'), 'label' => 'Menunggu Review', 'icon' => 'bx bx-time-five', 'color' => 'warning', 'sub' => 'Perlu ditinjau SDM'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'menunggu_manager', 'value' => number_format((int) ($sum['menunggu_manager'] ?? 0), 0, ',', '.'), 'label' => 'Menunggu Manager', 'icon' => 'bx bx-user-check', 'color' => 'info', 'sub' => 'Menunggu keputusan manager'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'disetujui', 'value' => number_format((int) ($sum['disetujui'] ?? 0), 0, ',', '.'), 'label' => 'Disetujui', 'icon' => 'bx bx-check-circle', 'color' => 'success', 'sub' => 'Sudah disetujui'])
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-task me-1"></i> Laporan Menunggu Review</h5>
        </div>

        <!-- SEARCH -->
        <form method="GET" action="{{ url('sdm/approval-laporan') }}" class="ui-toolbar" data-live-search>
            <div class="ui-search">
                <i class="bx bx-search"></i>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Cari nama, username, sekolah, jurusan, status, bidang, nama file..."
                       value="{{ $search }}">
            </div>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
            <a href="{{ url('sdm/approval-laporan') }}" class="btn btn-sm ui-reset-btn" title="Reset / Segarkan data" aria-label="Reset filter"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i></a>
        </form>

        <div class="table-responsive ui-table-wrap">
            <table class="table table-sm table-striped table-hover align-middle small mb-0 ui-table">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 165px;">Peserta</th>
                        <th style="width: 150px;">Asal Sekolah</th>
                        <th style="width: 92px;" class="text-center">Periode Magang</th>
                        <th style="width: 120px;">Bidang</th>
                        <th style="width: 100px;" class="text-center">File Laporan</th>
                        <th style="width: 90px;" class="text-center">Tgl Upload</th>
                        <th style="width: 120px;">Kehadiran</th>
                        <th style="width: 175px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody data-empty-colspan="9" data-empty-text="Tidak ada laporan yang menunggu review.">
                @php $no = $offset + 1; @endphp
                @foreach ($data as $row)
                    @php
                        $total_hadir      = (int) ($row->total_hadir ?? 0);
                        $total_hari_kerja = $hitungHariKerja($row->tgl_masuk, $row->tgl_keluar, $libur_pekan, $hari_libur);
                    @endphp
                    <tr data-laporan-row data-laporan-id="{{ $row->id }}" class="{{ $fokus_id === (int) $row->id ? 'pkl-fokus' : '' }}">
                        <td class="text-center">{{ $no++ }}</td>
                        <td style="white-space: normal;">
                            <strong>{{ $row->nama }}</strong>
                            <small class="text-muted d-block"><code>{{ $row->username }}</code></small>
                            <span class="badge bg-{{ $row->status_peserta=='Siswa' ? 'info' : 'primary' }} mt-1">
                                {{ $row->status_peserta }}
                            </span>
                        </td>
                        <td>
                            <small class="d-block" style="max-width: 200px; white-space: normal; overflow-wrap: anywhere;">{{ $row->asal_sekolah }}</small>
                            <small class="text-muted">{{ $row->jurusan }}</small>
                        </td>
                        <td class="text-center" style="line-height: 1.25;">
                            <small class="d-block">{{ date('d/m/Y', strtotime($row->tgl_masuk)) }}</small>
                            <small class="text-muted" style="font-size: .68rem;">s/d</small>
                            <small class="d-block">{{ date('d/m/Y', strtotime($row->tgl_keluar)) }}</small>
                        </td>
                        <td>
                            @if ($row->nama_bidang)
                                <span class="badge bg-light text-dark border">{{ $row->nama_bidang }}</span>
                            @else
                                <em class="text-muted">-</em>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-nowrap">
                                <a href="{{ asset('uploads/laporan/' . $row->file_laporan) }}"
                                   target="_blank" class="btn btn-sm btn-info d-inline-flex align-items-center gap-1 text-nowrap" title="Buka laporan"
                                   data-ui-preview data-preview-title="Laporan {{ $row->nama }}">
                                    <i class="bx bxs-file-pdf fs-6"></i> <span>Lihat</span>
                                </a>
                            </div>
                        </td>
                        <td class="text-center" style="line-height: 1.25;">
                            <small class="d-block">{{ date('d/m/Y', strtotime($row->tgl_upload)) }}</small>
                            <small class="text-muted" style="font-size: .72rem;">{{ date('H:i', strtotime($row->tgl_upload)) }}</small>
                        </td>
                        <td class="text-nowrap">
                            @if ($total_hari_kerja > 0)
                                @php
                                    $pct     = min(100, round(($total_hadir / $total_hari_kerja) * 100));
                                    $pct_cls = $pct >= 80 ? 'bg-success' : ($pct >= 60 ? 'bg-warning' : 'bg-danger');
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-weight:600; font-size:13px;">{{ $pct }}%</span>
                                    <div class="progress" style="width:45px; height:6px; margin:0;">
                                        <div class="progress-bar {{ $pct_cls }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                                <small class="text-muted">Hadir: {{ $total_hadir }}/{{ $total_hari_kerja }} hari</small>
                            @else
                                <small class="text-muted"><em>--</em></small>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2 flex-nowrap">
                            <form method="POST" action="{{ url('sdm/approval-laporan/setujui') }}" class="d-inline js-approval-form"
                                  data-action="approve" data-nama="{{ $row->nama }}"
                                  data-counters='{"menunggu_review":-1,"menunggu_manager":1}'>
                                @csrf
                                <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                                <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 text-nowrap" title="Setujui">
                                    <i class="bx bx-check fs-6"></i> Setujui
                                </button>
                            </form>
                            <form method="POST" action="{{ url('sdm/approval-laporan/tolak') }}" class="d-inline js-approval-form"
                                  data-action="reject" data-nama="{{ $row->nama }}"
                                  data-counters='{"menunggu_review":-1}'>
                                @csrf
                                <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                                <button type="submit" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 text-nowrap" title="Tolak">
                                    <i class="bx bx-x fs-6"></i> Tolak
                                </button>
                            </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada laporan yang menunggu review.
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
                Menampilkan {{ $offset + 1 }}–{{ min($offset + $per_page, $total) }} dari {{ $total }} laporan
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/approval-laporan') }}?p={{ $page-1 }}&q={{ urlencode($search) }}">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i==$page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('sdm/approval-laporan') }}?p={{ $i }}&q={{ urlencode($search) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/approval-laporan') }}?p={{ $page+1 }}&q={{ urlencode($search) }}">
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>

<style>
    /* Laporan yang dipilih dari tombol "Tinjau" di dashboard */
    body .page-content table.ui-table tbody tr.pkl-fokus > td { background-color: #fff4cc !important; }
    body .page-content table.ui-table tbody tr.pkl-fokus > td:first-child { box-shadow: inset 4px 0 0 #ffc400; }
</style>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.querySelector('tr.pkl-fokus');
    if (el && el.scrollIntoView) {
        setTimeout(function () { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 250);
    }
});
</script>
@endsection
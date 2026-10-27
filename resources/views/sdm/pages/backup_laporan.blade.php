@php
    $st_badge = ['Menunggu' => 'warning', 'Menunggu Manager' => 'info', 'Disetujui' => 'success', 'Ditolak' => 'danger'];

    $search         = trim((string) request('q'));
    $approve_filter = (string) request('approve');
    $tgl_dari       = (string) request('tgl_dari');
    $tgl_sampai     = (string) request('tgl_sampai');

    $query_string = http_build_query(array_filter([
        'q'          => $search,
        'approve'    => $approve_filter,
        'tgl_dari'   => $tgl_dari,
        'tgl_sampai' => $tgl_sampai,
    ], fn ($v) => $v !== null && $v !== ''));

    $formatSize = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes >= 1024 * 1024 * 1024) return round($bytes / 1024 / 1024 / 1024, 2).' GB';
        if ($bytes >= 1024 * 1024) return round($bytes / 1024 / 1024, 2).' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1).' KB';
        return $bytes.' B';
    };
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
    <div class="breadcrumb-title pe-3"><span class="sdm-title-ico"><i class="bx bx-download"></i></span>Backup Laporan</div>
</div>

<!-- SUMMARY CARDS (mengikuti filter yang aktif) -->
@php $scTotal = (int) ($stats->total ?? 0); @endphp
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
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'value' => number_format($scTotal, 0, ',', '.'), 'label' => 'Jumlah File', 'icon' => 'bx bx-file', 'color' => 'primary', 'sub' => 'Sesuai filter aktif'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'value' => number_format((int) ($stats->disetujui ?? 0), 0, ',', '.'), 'label' => 'Disetujui', 'icon' => 'bx bx-check-circle', 'color' => 'success', 'sub' => 'Laporan disetujui'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'value' => number_format((int) ($stats->menunggu ?? 0), 0, ',', '.'), 'label' => 'Menunggu', 'icon' => 'bx bx-time-five', 'color' => 'warning', 'sub' => 'Menunggu review'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'value' => $formatSize($stats->total_size ?? 0), 'label' => 'Total Ukuran File', 'icon' => 'bx bx-hdd', 'color' => 'dark', 'sub' => 'Akumulasi ukuran PDF'])
</div>

<!-- BACKUP ACTION -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="card-title mb-1"><i class="bx bx-download me-1"></i> Unduh Backup</h5>
                <p class="text-muted mb-0 small">
                    Backup mengikuti filter yang aktif. Berisi data laporan (CSV) beserta file PDF laporan dalam satu arsip ZIP.
                    Export Excel berisi data laporan tanpa file.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ url('sdm/backup-laporan/export') }}{{ $query_string ? '?'.$query_string : '' }}"
                   class="btn btn-sm btn-outline-success" data-ui-loading data-ui-loading-text="Menyiapkan Excel...">
                    <i class="bx bxs-file-export"></i> Export Data (Excel)
                </a>
                <a href="{{ url('sdm/backup-laporan/download') }}{{ $query_string ? '?'.$query_string : '' }}"
                   class="btn btn-sm btn-primary" data-ui-loading data-ui-loading-text="Menyiapkan ZIP...">
                    <i class="bx bx-archive"></i> Download Backup (.zip)
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-list-ul me-1"></i> Data Laporan</h5>
        </div>

        <!-- FILTER (satu baris: search, status, tanggal, tombol) -->
        <form method="GET" action="{{ url('sdm/backup-laporan') }}"
              class="ui-toolbar" data-live-search>

            <div class="ui-search">
                <i class="bx bx-search"></i>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Cari nama / username / sekolah / bidang"
                       value="{{ $search }}">
            </div>

            <select name="approve" class="form-select form-select-sm" style="width:auto;">
                <option value="">Semua Status Approval</option>
                <option value="Menunggu" {{ $approve_filter === 'Menunggu' ? 'selected' : '' }}>Menunggu Review</option>
                <option value="Menunggu Manager" {{ $approve_filter === 'Menunggu Manager' ? 'selected' : '' }}>Menunggu Manager</option>
                <option value="Disetujui" {{ $approve_filter === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="Ditolak" {{ $approve_filter === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>

            <div class="ui-daterange" data-daterange>
                <small class="text-muted text-nowrap">Tgl Upload</small>
                <input type="date" name="tgl_dari" class="form-control form-control-sm"
                       value="{{ $tgl_dari }}" title="Tanggal dari">
                <small class="text-muted text-nowrap">s/d</small>
                <input type="date" name="tgl_sampai" class="form-control form-control-sm"
                       value="{{ $tgl_sampai }}" title="Tanggal sampai">
            </div>

            <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                <i class="bx bx-filter-alt me-1"></i> Filter
            </button>
            <a href="{{ url('sdm/backup-laporan') }}" class="btn btn-sm ui-reset-btn" title="Reset / Segarkan data" aria-label="Reset filter">
                <i class="bx bx-sync pkl-sync pkl-sync--spin"></i>
            </a>
        </form>

        <div class="table-responsive ui-table-wrap">
            <table class="table table-striped table-hover align-middle small mb-0 ui-table">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Bidang</th>
                        <th>File Laporan</th>
                        <th>Tgl Upload</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @php $no = $offset + 1; @endphp
                @foreach ($data as $row)
                    @php $badge = $st_badge[$row->status] ?? 'secondary'; @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            <strong>{{ $row->nama }}</strong>
                            <small class="text-muted d-block"><code>{{ $row->username }}</code></small>
                        </td>
                        <td class="text-center">
                            <div>{{ $row->asal_sekolah }}</div>
                            <small class="text-muted">{{ $row->jurusan }}</small>
                        </td>
                        <td class="text-center">
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
                                   data-ui-preview data-preview-title="{{ $row->nama_file }}">
                                    <i class="bx bxs-file-pdf fs-6"></i>
                                    <span>{{ $formatSize($row->ukuran_file) }}</span>
                                </a>
                                <span class="text-truncate text-secondary small" style="max-width: 160px;" title="{{ $row->nama_file }}">
                                    {{ $row->nama_file }}
                                </span>
                            </div>
                        </td>
                        <td class="text-center text-nowrap">{{ date('d/m/Y H:i', strtotime($row->tgl_upload)) }}</td>
                        <td class="text-center"><span class="badge bg-{{ $badge }}">{{ $row->status }}</span></td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada data laporan.
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        @if ($total_page > 1)
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan {{ $offset + 1 }}–{{ min($offset + $per_page, $total) }} dari {{ $total }} laporan
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/backup-laporan') }}?p={{ $page-1 }}&{{ $query_string }}">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i==$page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('sdm/backup-laporan') }}?p={{ $i }}&{{ $query_string }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/backup-laporan') }}?p={{ $page+1 }}&{{ $query_string }}">
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>
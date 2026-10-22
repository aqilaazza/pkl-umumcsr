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

    // Hitung hari libur bulan ini (pakai date range)
    $libur_count = DB::table('hari_libur')
        ->where('tanggal', '>=', $tgl_awal)
        ->where('tanggal', '<', $tgl_akhir)
        ->count();

    // Summary stats based on filter (pakai date range)
    $sum_rekap = $applyFilter(
        DB::table('absensi_peserta as ap')
            ->join('peserta as p', 'ap.username', '=', 'p.username')
            ->where('ap.tanggal', '>=', $tgl_awal)
            ->where('ap.tanggal', '<', $tgl_akhir)
    )->selectRaw("SUM(ap.status='Hadir') as hadir, SUM(ap.status='Izin') as izin, SUM(ap.status='Sakit') as sakit, SUM(ap.status='Alpha') as alpha")->first();

    $url_base = url('sdm/rekap-absensi')
        . '?bulan=' . urlencode($filter_bulan)
        . '&status=' . urlencode($filter_status)
        . '&unit=' . urlencode($filter_unit)
        . '&bidang=' . $filter_bidang
        . '&q=' . urlencode($filter_search)
        . '&hal=';
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Rekap Absensi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active">Rekap Absensi Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
@php $scTotal = (int) $total_p; @endphp
<div class="row g-3 mb-4">
    @include('partials.stat_card_v2', ['colClass' => 'col-6 col-md-2', 'value' => number_format($scTotal, 0, ',', '.'), 'label' => 'Total Peserta', 'icon' => 'bx bx-group', 'color' => 'primary'])
    @include('partials.stat_card_v2', ['colClass' => 'col-6 col-md-2', 'value' => number_format((int) ($sum_rekap->hadir ?? 0), 0, ',', '.'), 'label' => 'Hadir', 'icon' => 'bx bx-check-circle', 'color' => 'success'])
    @include('partials.stat_card_v2', ['colClass' => 'col-6 col-md-2', 'value' => number_format((int) ($sum_rekap->izin ?? 0), 0, ',', '.'), 'label' => 'Izin', 'icon' => 'bx bx-time-five', 'color' => 'warning'])
    @include('partials.stat_card_v2', ['colClass' => 'col-6 col-md-2', 'value' => number_format((int) ($sum_rekap->sakit ?? 0), 0, ',', '.'), 'label' => 'Sakit', 'icon' => 'bx bx-plus-medical', 'color' => 'danger'])
    @include('partials.stat_card_v2', ['colClass' => 'col-6 col-md-2', 'value' => number_format((int) ($sum_rekap->alpha ?? 0), 0, ',', '.'), 'label' => 'Alpha', 'icon' => 'bx bx-x-circle', 'color' => 'secondary'])
    @include('partials.stat_card_v2', ['colClass' => 'col-6 col-md-2', 'value' => number_format((int) $libur_count, 0, ',', '.'), 'label' => 'Libur', 'icon' => 'bx bx-calendar-x', 'color' => 'dark'])
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-calendar-check me-1"></i> Rekap Absensi Peserta</h5>
            <div>
                <span class="badge bg-light text-dark border">Periode: {{ $bulan_label }}</span>
                <span class="badge bg-light text-dark border">Status: {{ $filter_status === 'semua' ? 'Semua' : $filter_status }}</span>
            </div>
        </div>
        <form method="GET" action="{{ url('sdm/rekap-absensi') }}" class="row g-2 align-items-end" data-live-search>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <input type="month" name="bulan" class="form-control form-control-sm" value="{{ $filter_bulan }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Status Magang</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="semua" {{ $filter_status === 'semua' ? 'selected' : '' }}>Semua Status</option>
                    @foreach ($status_opsi as $val => $label)
                    <option value="{{ $val }}" {{ $filter_status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Unit</label>
                <select name="unit" class="form-select form-select-sm">
                    <option value="">Semua Unit</option>
                    <option value="Unit 1-2" {{ $filter_unit === 'Unit 1-2' ? 'selected' : '' }}>Unit 1-2</option>
                    <option value="Unit 9" {{ $filter_unit === 'Unit 9' ? 'selected' : '' }}>Unit 9</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bidang</label>
                <select name="bidang" class="form-select form-select-sm">
                    <option value="">Semua Bidang</option>
                    @foreach ($bidang_list as $b)
                    <option value="{{ $b->id }}" {{ $filter_bidang == $b->id ? 'selected' : '' }}>{{ $b->bidang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Cari</label>
                <div class="ui-search">
                    <i class="bx bx-search"></i>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / Sekolah / Username" value="{{ $filter_search }}">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                <a href="{{ url('sdm/rekap-absensi') }}" class="btn btn-outline-secondary btn-sm ms-1" title="Reset (kembali ke status Aktif)"><i class="bx bx-reset"></i></a>
            </div>
        </form>
    </div>
</div>

<style>
    /* Paksa teks turun baris, bukan melebar & menimpa kolom sebelahnya */
    #tabelRekap th {
        white-space: nowrap !important;   /* judul kolom tidak dipotong di tengah kata */
        vertical-align: middle;
    }
    #tabelRekap td {
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: normal;
        vertical-align: middle;
    }
</style>

<!-- Tabel Rekap -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive ui-table-wrap">
            <table class="table table-striped table-hover align-middle small mb-0 ui-table ui-table--compact" id="tabelRekap" style="table-layout: fixed; width: 100%; min-width: 1100px;">
                <colgroup>
                    <col style="width: 4%;">   <!-- No -->
                    <col style="width: 18%;">  <!-- Nama -->
                    <col style="width: 13%;">  <!-- Sekolah -->
                    <col style="width: 8%;">   <!-- Unit -->
                    <col style="width: 9%;">   <!-- Bidang -->
                    <col style="width: 8%;">   <!-- Status -->
                    <col style="width: 5%;">   <!-- Hadir -->
                    <col style="width: 5%;">   <!-- Izin -->
                    <col style="width: 5%;">   <!-- Sakit -->
                    <col style="width: 5%;">   <!-- Alpha -->
                    <col style="width: 8%;">   <!-- % -->
                    <col style="width: 12%;">  <!-- Detail + PDF -->
                </colgroup>
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Nama</th>
                        <th class="text-center">Sekolah</th>
                        <th class="text-center">Unit</th>
                        <th class="text-center">Bidang</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="background:#198754;">Hadir</th>
                        <th class="text-center" style="background:#ffc107; color:#333;">Izin</th>
                        <th class="text-center" style="background:#dc3545;">Sakit</th>
                        <th class="text-center" style="background:#6c757d;">Alpha</th>
                        <th class="text-center">%</th>
                        <th class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody>
                @php $no = $offset + 1; @endphp
                @foreach ($peserta_list as $p)
                    @php
                        $total_rec = $p->total_absensi;
                        $pct = $total_rec > 0 ? round(($p->hadir_count / $total_rec) * 100) : 0;
                    @endphp
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-center"><strong>{{ $p->nama }}</strong><br><small class="text-muted">{{ $p->username }}</small></td>
                    <td class="text-center"><small class="d-block text-truncate mx-auto" style="max-width: 100%;" title="{{ $p->asal_sekolah ?? '-' }}">{{ $p->asal_sekolah ?? '-' }}</small></td>
                    <td class="text-center">{{ $p->unit ?? '-' }}</td>
                    <td class="text-center"><span class="d-block text-truncate mx-auto" style="max-width: 100%;" title="{{ $p->nama_bidang ?? '-' }}">{{ $p->nama_bidang ?? '-' }}</span></td>
                    <td class="text-center">
                        @php
                            $badge_status = ['Aktif' => 'success', 'Menunggu' => 'warning', 'Selesai' => 'secondary'][$p->status_magang] ?? 'light';
                        @endphp
                        <span class="badge bg-{{ $badge_status }}">{{ $p->status_magang ?? '-' }}</span>
                    </td>
                    <td class="text-center fw-bold text-success">{{ $p->hadir_count }}</td>
                    <td class="text-center fw-bold text-warning">{{ $p->izin_count }}</td>
                    <td class="text-center fw-bold text-danger">{{ $p->sakit_count }}</td>
                    <td class="text-center fw-bold text-secondary">{{ $p->alpha_count }}</td>
                    <td class="text-center">
                        <div class="progress mx-auto mb-1" style="height:6px; width:60px; max-width:90%;">
                            <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                        </div>
                        <small>{{ $pct }}%</small>
                    </td>
                    <td class="text-center">
                        <div class="ui-actions">
                            <button class="btn btn-sm btn-outline-primary" title="Detail" onclick="showDetail('{{ $p->username }}', '{{ $p->nama }}')">
                                <i class="bx bx-list-ul"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Export PDF"
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
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
@if ($total_halaman > 1)
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <!-- First -->
        <li class="page-item {{ $page_num <= 1 ? 'disabled' : '' }}">
            <a class="page-link" href="{{ $url_base }}1">«</a>
        </li>
        <!-- Prev -->
        <li class="page-item {{ $page_num <= 1 ? 'disabled' : '' }}">
            <a class="page-link" href="{{ $url_base . ($page_num - 1) }}">‹</a>
        </li>
        <!-- Pages with ellipsis -->
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
        <!-- Next -->
        <li class="page-item {{ $page_num >= $total_halaman ? 'disabled' : '' }}">
            <a class="page-link" href="{{ $url_base . ($page_num + 1) }}">›</a>
        </li>
        <!-- Last -->
        <li class="page-item {{ $page_num >= $total_halaman ? 'disabled' : '' }}">
            <a class="page-link" href="{{ $url_base . $total_halaman }}">»</a>
        </li>
    </ul>
</nav>
@endif

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetailTitle">Detail Absensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1"></i></div>
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
    var cls = isMasuk ? 'btn-success' : 'btn-danger';   // hijau = masuk, merah = keluar (sama seperti badge GPS)
    var label = isMasuk ? 'Hapus Masuk' : 'Hapus Keluar';
    var enabled = ada && tglRaw !== '';
    return '<button type="button" class="btn btn-sm ' + cls + ' btn-hapus-absen"'
        + ' data-user="' + detailHtmlEsc(username) + '"'
        + ' data-tgl="' + detailHtmlEsc(tglRaw) + '"'
        + ' data-tipe="' + tipe + '"'
        + (enabled ? '' : ' disabled')
        + ' title="' + label + '" aria-label="' + label + '">'
        + '<i class="bx bx-trash"></i></button>';
}
function renderDetailTable(rows, username) {
    var hariArr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var html = '<table class="table table-bordered table-striped table-hover align-middle small mb-0 ui-table--compact">';
    html += '<thead class="table-dark">';
    html += '<tr>';
    html += '<th class="text-center" style="width: 100px;">Tanggal</th>';
    html += '<th class="text-center" style="width: 85px;">Hari</th>';
    html += '<th class="text-center" style="width: 80px;">Masuk</th>';
    html += '<th class="text-center" style="width: 80px;">Keluar</th>';
    html += '<th class="text-center" style="width: 90px;">Status</th>';
    html += '<th class="text-center">Keterangan</th>';
    html += '<th class="text-center" style="width: 70px;">Surat</th>';
    html += '<th class="text-center">Lokasi GPS</th>';
    html += '<th class="text-center" style="width: 130px;">Aksi</th>';
    html += '</tr>';
    html += '</thead><tbody>';

    rows.forEach(function (d) {
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(d.tanggal));
        var tgl = m ? (m[3] + '/' + m[2] + '/' + m[1]) : String(d.tanggal);
        var day = m ? hariArr[new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])).getUTCDay()] : '';

        var badgeMap = { 'Hadir': 'success', 'Izin': 'warning', 'Sakit': 'danger' };
        var badge = badgeMap[d.status] || 'secondary';

        var surat = detailTruthy(d.file_surat)
            ? '<a href="{{ asset('uploads/surat_absensi') }}/' + detailHtmlEsc(d.file_surat) + '" target="_blank" class="btn btn-sm btn-outline-info ui-btn-icon-only" title="Buka Surat" data-ui-preview data-preview-title="Surat absensi"><i class="bx bx-file"></i></a>'
            : '-';

        var statusSub = '';
        if ((d.status === 'Izin' || d.status === 'Sakit') && d.approval_status !== null && d.approval_status !== undefined) {
            if (d.approval_status === 'Pending') {
                statusSub = '<br><span class="badge bg-light text-warning border border-warning mt-1" style="font-size: 9px; padding: 2px 4px;">Pending</span>';
            } else if (d.approval_status === 'Ditolak') {
                statusSub = '<br><span class="badge bg-light text-danger border border-danger mt-1" style="font-size: 9px; padding: 2px 4px;">Ditolak</span>';
            } else {
                statusSub = '<br><span class="badge bg-light text-success border border-success mt-1" style="font-size: 9px; padding: 2px 4px;">Disetujui</span>';
            }
        }

        var gpsMasuk = '';
        if (detailTruthy(d.lat_masuk)) {
            gpsMasuk = '<a href="https://maps.google.com/?q=' + d.lat_masuk + ',' + d.lng_masuk + '" target="_blank" class="badge bg-success text-white d-block mx-auto" style="font-size: 10px; text-decoration: none; width: fit-content;"><i class="bx bx-map-pin"></i> Masuk: ' + detailRound5(d.lat_masuk) + ',' + detailRound5(d.lng_masuk) + '</a>';
        }
        var gpsKeluar = '';
        if (detailTruthy(d.lat_keluar)) {
            gpsKeluar = '<a href="https://maps.google.com/?q=' + d.lat_keluar + ',' + d.lng_keluar + '" target="_blank" class="badge bg-danger text-white mt-1 d-block mx-auto" style="font-size: 10px; text-decoration: none; width: fit-content;"><i class="bx bx-map-pin"></i> Keluar: ' + detailRound5(d.lat_keluar) + ',' + detailRound5(d.lng_keluar) + '</a>';
        }
        var gps = (gpsMasuk || gpsKeluar) ? gpsMasuk + gpsKeluar : '-';

        var tglRaw = m ? (m[1] + '-' + m[2] + '-' + m[3]) : '';
        var adaMasuk = detailTruthy(d.jam_masuk) || detailTruthy(d.lat_masuk);
        var adaKeluar = detailTruthy(d.jam_keluar) || detailTruthy(d.lat_keluar);
        var aksi = '<div class="d-flex flex-column align-items-center gap-1">'
            + detailBtnHapus(username, tglRaw, 'masuk', adaMasuk)
            + detailBtnHapus(username, tglRaw, 'keluar', adaKeluar)
            + '</div>';

        html += '<tr>';
        html += '<td class="text-center">' + tgl + '</td>';
        html += '<td class="text-center"><span class="badge bg-light text-dark border">' + day + '</span></td>';
        html += '<td class="text-center">' + (detailTruthy(d.jam_masuk) ? '<strong class="text-success">' + String(d.jam_masuk).substr(0, 5) + '</strong>' : '-') + '</td>';
        html += '<td class="text-center">' + (detailTruthy(d.jam_keluar) ? '<strong class="text-danger">' + String(d.jam_keluar).substr(0, 5) + '</strong>' : '-') + '</td>';
        html += '<td class="text-center"><span class="badge bg-' + badge + '">' + detailHtmlEsc(d.status) + '</span>' + statusSub + '</td>';
        html += '<td class="text-center">' + detailHtmlEsc(d.keterangan === null || d.keterangan === undefined ? '-' : d.keterangan) + '</td>';
        html += '<td class="text-center">' + surat + '</td>';
        html += '<td class="text-center">' + gps + '</td>';
        html += '<td class="text-center">' + aksi + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
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

function loadDetailAbsensi(username) {
    const bulan = @json($filter_bulan);
    document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1"></i></div>';

    // Load detail via ajax endpoint
    return fetch('{{ url('sdm/rekap-absensi/ajax-detail') }}?user=' + encodeURIComponent(username) + '&bulan=' + encodeURIComponent(bulan))
    .then(r => r.json())
    .then(rows => {
        document.getElementById('modalDetailBody').innerHTML = renderDetailTable(rows, username);
    });
}

function showDetail(username, nama) {
    detailAdaPerubahan = false;
    document.getElementById('modalDetailTitle').textContent = 'Detail Absensi - ' + nama;

    var modal = new bootstrap.Modal(document.getElementById('modalDetail'));
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

// Setelah modal ditutup, reload halaman supaya angka rekap ikut ter-update
document.getElementById('modalDetail').addEventListener('hidden.bs.modal', function () {
    if (detailAdaPerubahan) {
        location.reload();
    }
});
</script>
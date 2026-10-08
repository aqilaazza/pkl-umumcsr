@php
    use Illuminate\Support\Facades\DB;

    $filter_bulan  = (string) request('bulan', date('Y-m'));
    $filter_unit   = (string) request('unit', '');
    $filter_bidang = (int) request('bidang', 0);
    $filter_search = trim((string) request('q', ''));
    $page_num      = max(1, (int) request('hal', 1));
    $per_page      = 25;
    $offset        = ($page_num - 1) * $per_page;

    // Date range
    $tgl_awal  = $filter_bulan . '-01';
    $tgl_akhir = date('Y-m-d', strtotime($tgl_awal . ' +1 month'));

    // Ambil daftar bidang untuk filter
    $bidang_list = DB::table('bidang')->orderBy('bidang')->get();

    // Build WHERE
    $applyFilter = function ($q) use ($filter_unit, $filter_bidang, $filter_search) {
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
    )->selectRaw("SUM(status='Hadir') as hadir, SUM(status='Izin') as izin, SUM(status='Sakit') as sakit, SUM(status='Alpha') as alpha")->first();

    $url_base = url('sdm/rekap-absensi')
        . '?bulan=' . urlencode($filter_bulan)
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
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-primary">{{ number_format($total_p, 0, ',', '.') }}</div>
                <div class="small text-muted">Total Peserta</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-success border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-success">{{ number_format((int) ($sum_rekap->hadir ?? 0), 0, ',', '.') }}</div>
                <div class="small text-muted">Hadir</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-warning">{{ number_format((int) ($sum_rekap->izin ?? 0), 0, ',', '.') }}</div>
                <div class="small text-muted">Izin</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-danger">{{ number_format((int) ($sum_rekap->sakit ?? 0), 0, ',', '.') }}</div>
                <div class="small text-muted">Sakit</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm border border-secondary border-2">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-secondary">{{ number_format((int) ($sum_rekap->alpha ?? 0), 0, ',', '.') }}</div>
                <div class="small text-muted">Alpha</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-4 fw-bold text-dark">{{ $libur_count }}</div>
                <div class="small text-muted">Libur</div>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-calendar-check me-1"></i> Rekap Absensi Peserta</h5>
            <span class="badge bg-light text-dark border">Periode: {{ $bulan_label }}</span>
        </div>
        <form method="GET" action="{{ url('sdm/rekap-absensi') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <input type="month" name="bulan" class="form-control form-control-sm" value="{{ $filter_bulan }}">
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
            <div class="col-md-3">
                <label class="form-label small fw-bold">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Nama / Sekolah / Username" value="{{ $filter_search }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                <a href="{{ url('sdm/rekap-absensi') }}" class="btn btn-outline-secondary btn-sm ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Rekap -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0" id="tabelRekap">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Sekolah</th>
                        <th>Unit</th>
                        <th>Bidang</th>
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
                    <td>{{ $no++ }}</td>
                    <td><strong>{{ $p->nama }}</strong><br><small class="text-muted">{{ $p->username }}</small></td>
                    <td><small>{{ $p->asal_sekolah ?? '-' }}</small></td>
                    <td>{{ $p->unit ?? '-' }}</td>
                    <td>{{ $p->nama_bidang ?? '-' }}</td>
                    <td class="text-center fw-bold text-success">{{ $p->hadir_count }}</td>
                    <td class="text-center fw-bold text-warning">{{ $p->izin_count }}</td>
                    <td class="text-center fw-bold text-danger">{{ $p->sakit_count }}</td>
                    <td class="text-center fw-bold text-secondary">{{ $p->alpha_count }}</td>
                    <td class="text-center">
                        <div class="progress" style="height:6px; width:60px; display:inline-block; vertical-align:middle;">
                            <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                        </div>
                        <small class="ms-1">{{ $pct }}%</small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="showDetail('{{ $p->username }}', '{{ $p->nama }}')">
                            <i class="bx bx-list-ul"></i>
                        </button>
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
function renderDetailTable(rows) {
    var hariArr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var html = '<table class="table table-bordered table-striped table-hover align-middle mb-0" style="font-size: 13px;">';
    html += '<thead class="table-dark">';
    html += '<tr>';
    html += '<th class="text-center" style="width: 100px;">Tanggal</th>';
    html += '<th class="text-center" style="width: 85px;">Hari</th>';
    html += '<th class="text-center" style="width: 80px;">Masuk</th>';
    html += '<th class="text-center" style="width: 80px;">Keluar</th>';
    html += '<th class="text-center" style="width: 90px;">Status</th>';
    html += '<th>Keterangan</th>';
    html += '<th class="text-center" style="width: 70px;">Surat</th>';
    html += '<th>Lokasi GPS</th>';
    html += '</tr>';
    html += '</thead><tbody>';

    rows.forEach(function (d) {
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(d.tanggal));
        var tgl = m ? (m[3] + '/' + m[2] + '/' + m[1]) : String(d.tanggal);
        var day = m ? hariArr[new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])).getUTCDay()] : '';

        var badgeMap = { 'Hadir': 'success', 'Izin': 'warning', 'Sakit': 'danger' };
        var badge = badgeMap[d.status] || 'secondary';

        var surat = detailTruthy(d.file_surat)
            ? '<a href="{{ asset('uploads/surat_absensi') }}/' + detailHtmlEsc(d.file_surat) + '" target="_blank" class="btn btn-sm btn-outline-info" title="Buka Surat"><i class="bx bx-file"></i></a>'
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
            gpsMasuk = '<a href="https://maps.google.com/?q=' + d.lat_masuk + ',' + d.lng_masuk + '" target="_blank" class="badge bg-success text-white" style="font-size: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"><i class="bx bx-map-pin"></i> Masuk: ' + detailRound5(d.lat_masuk) + ',' + detailRound5(d.lng_masuk) + '</a>';
        }
        var gpsKeluar = '';
        if (detailTruthy(d.lat_keluar)) {
            gpsKeluar = '<a href="https://maps.google.com/?q=' + d.lat_keluar + ',' + d.lng_keluar + '" target="_blank" class="badge bg-danger text-white mt-1 d-block" style="font-size: 10px; text-decoration: none; width: fit-content; display: inline-flex; align-items: center; gap: 4px;"><i class="bx bx-map-pin"></i> Keluar: ' + detailRound5(d.lat_keluar) + ',' + detailRound5(d.lng_keluar) + '</a>';
        }
        var gps = (gpsMasuk || gpsKeluar) ? gpsMasuk + gpsKeluar : '-';

        html += '<tr>';
        html += '<td class="text-center">' + tgl + '</td>';
        html += '<td class="text-center"><span class="badge bg-light text-dark border">' + day + '</span></td>';
        html += '<td class="text-center">' + (detailTruthy(d.jam_masuk) ? '<strong class="text-success">' + String(d.jam_masuk).substr(0, 5) + '</strong>' : '-') + '</td>';
        html += '<td class="text-center">' + (detailTruthy(d.jam_keluar) ? '<strong class="text-danger">' + String(d.jam_keluar).substr(0, 5) + '</strong>' : '-') + '</td>';
        html += '<td class="text-center"><span class="badge bg-' + badge + '">' + detailHtmlEsc(d.status) + '</span>' + statusSub + '</td>';
        html += '<td>' + detailHtmlEsc(d.keterangan === null || d.keterangan === undefined ? '-' : d.keterangan) + '</td>';
        html += '<td class="text-center">' + surat + '</td>';
        html += '<td>' + gps + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';
    return html;
}
function showDetail(username, nama) {
    const bulan = @json($filter_bulan);
    document.getElementById('modalDetailTitle').textContent = 'Detail Absensi - ' + nama;
    document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1"></i></div>';

    var modal = new bootstrap.Modal(document.getElementById('modalDetail'));
    modal.show();

    // Load detail via ajax endpoint
    fetch('{{ url('sdm/rekap-absensi/ajax-detail') }}?user=' + username + '&bulan=' + bulan)
    .then(r => r.json())
    .then(rows => {
        document.getElementById('modalDetailBody').innerHTML = renderDetailTable(rows);
    });
}
</script>

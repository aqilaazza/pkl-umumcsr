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
    /* Ukuran font tabel Data Laporan */
    .card .table.tbl-laporan { font-size: 15px; }
    .card .table.tbl-laporan thead th { font-size: 14px; padding-top: .75rem; padding-bottom: .75rem; text-align: center; vertical-align: middle; }
    .card .table.tbl-laporan td { padding-top: .7rem; padding-bottom: .7rem; }
    .card .table.tbl-laporan small,
    .card .table.tbl-laporan .small { font-size: 13px; }
    .card .table.tbl-laporan code { font-size: 13px; }
    .card .table.tbl-laporan .badge { font-size: 12.5px; padding: .4em .7em; font-weight: 600; }
    .card .table.tbl-laporan .btn-sm { font-size: 14px; padding: .3rem .7rem; }
</style>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Hapus Laporan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="bx bx-error-circle fs-4 me-2"></i>
    <div>Menghapus laporan akan menghapus data <strong>dan</strong> file PDF secara permanen. Tindakan ini tidak dapat dibatalkan. Filter hanya mempersempit daftar, tidak menghapus data.</div>
</div>

<!-- SUMMARY CARDS (mengikuti filter yang aktif) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary" data-counter="total">{{ (int) ($stats->total ?? 0) }}</div>
                <div class="small text-muted">Jumlah File</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success" data-counter="disetujui">{{ (int) ($stats->disetujui ?? 0) }}</div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning" data-counter="menunggu">{{ (int) ($stats->menunggu ?? 0) }}</div>
                <div class="small text-muted">Menunggu</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger" data-counter="ditolak">{{ (int) ($stats->ditolak ?? 0) }}</div>
                <div class="small text-muted">Ditolak</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-trash me-1"></i> Data Laporan</h5>
        </div>

        <!-- FILTER (satu baris: search, status, tanggal, tombol filter, hapus terpilih) -->
        <form method="GET" action="{{ url('sdm/hapus-laporan') }}"
              class="d-flex flex-wrap align-items-center gap-2 mb-3" id="formFilter">

            <div class="input-group input-group-sm" style="flex: 0 0 auto; width: 550px;">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" name="q" class="form-control"
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

            <div class="d-flex align-items-center gap-1">
                <small class="text-muted text-nowrap">Tgl Upload</small>
                <input type="date" name="tgl_dari" class="form-control form-control-sm"
                       style="width:auto;" value="{{ $tgl_dari }}" title="Tanggal dari">
                <small class="text-muted text-nowrap">s/d</small>
                <input type="date" name="tgl_sampai" class="form-control form-control-sm"
                       style="width:auto;" value="{{ $tgl_sampai }}" title="Tanggal sampai">
            </div>

            <div class="d-flex align-items-center gap-1">
                <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                    <i class="bx bx-filter-alt me-1"></i> Filter
                </button>
                <a href="{{ url('sdm/hapus-laporan') }}" class="btn btn-sm btn-outline-secondary" title="Reset filter">
                    <i class="bx bx-reset"></i>
                </a>
            </div>

            <!-- Tombol ini ada di baris filter, tapi submit ke form hapus massal lewat atribut form -->
            <button type="submit" form="formHapusMassal" class="btn btn-sm btn-danger text-nowrap ms-auto"
                    id="btnHapusTerpilih" disabled>
                <i class="bx bx-trash me-1"></i> Hapus Terpilih
            </button>
        </form>

        <form method="POST" action="{{ url('sdm/hapus-laporan/hapus-massal') }}" id="formHapusMassal"
              class="js-approval-form"
              data-action="delete-bulk"
              data-title="Berhasil Dihapus!"
              data-confirm-title="Hapus Laporan Terpilih?"
              data-confirm-message="Semua laporan yang dipilih akan dihapus permanen beserta file PDF-nya. Tindakan ini tidak dapat dibatalkan."
              data-confirm-ok="Hapus">
            @csrf

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 tbl-laporan">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" class="form-check-input" id="checkAll">
                            </th>
                            <th>#</th>
                            <th>Peserta</th>
                            <th>Asal Sekolah</th>
                            <th>Bidang</th>
                            <th>File Laporan</th>
                            <th>Tgl Upload</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody data-empty-colspan="9" data-empty-text="Tidak ada data laporan.">
                    @php $no = $offset + 1; @endphp
                    @foreach ($data as $row)
                        @php
                            $badge = $st_badge[$row->status] ?? 'secondary';
                            $deltas = ['total' => -1];
                            if ($row->status === 'Menunggu') {
                                $deltas['menunggu'] = -1;
                            } elseif ($row->status === 'Disetujui') {
                                $deltas['disetujui'] = -1;
                            } elseif ($row->status === 'Ditolak') {
                                $deltas['ditolak'] = -1;
                            }
                        @endphp
                        <tr data-lg-row>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input rowCheck" name="ids[]" value="{{ $row->id }}">
                            </td>
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
                                <a href="{{ asset('uploads/laporan/' . $row->file_laporan) }}"
                                   target="_blank" class="btn btn-sm btn-outline-primary" title="Buka laporan">
                                    <i class="bx bxs-file-pdf me-1"></i>{{ $formatSize($row->ukuran_file) }}
                                </a>
                                <small class="text-muted d-block mt-1">
                                    {{ mb_strimwidth((string) $row->nama_file, 0, 28, '...') }}
                                </small>
                            </td>
                            <td class="text-center text-nowrap">{{ date('d/m/Y H:i', strtotime($row->tgl_upload)) }}</td>
                            <td class="text-center"><span class="badge bg-{{ $badge }}">{{ $row->status }}</span></td>
                            <td class="text-center">
                                <form method="POST" action="{{ url('sdm/hapus-laporan/hapus') }}" class="d-inline js-approval-form"
                                      data-action="delete"
                                      data-title="Berhasil Dihapus!"
                                      data-confirm-title="Hapus Laporan?"
                                      data-confirm-message="Hapus laporan dari {{ $row->nama }}? Data dan file PDF akan dihapus permanen."
                                      data-confirm-ok="Hapus"
                                      data-counters='@json($deltas)'>
                                    @csrf
                                    <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bx bx-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if ($total == 0)
                        <tr class="pkl-empty">
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                                Tidak ada data laporan.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </form>

        @if ($total_page > 1)
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan {{ $offset + 1 }}–{{ min($offset + $per_page, $total) }} dari {{ $total }} laporan
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/hapus-laporan') }}?p={{ $page-1 }}&{{ $query_string }}">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i==$page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('sdm/hapus-laporan') }}?p={{ $i }}&{{ $query_string }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/hapus-laporan') }}?p={{ $page+1 }}&{{ $query_string }}">
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>

<!-- MODAL HAPUS (diganti oleh komponen popup LiquidGlass) -->

@section('scripts')
<script>
    var checkAll = document.getElementById('checkAll');
    var rowChecks = document.querySelectorAll('.rowCheck');
    var btnHapusTerpilih = document.getElementById('btnHapusTerpilih');

    function syncBulkButton() {
        var checked = document.querySelectorAll('.rowCheck:checked').length;
        btnHapusTerpilih.disabled = checked === 0;
        if (checkAll) {
            checkAll.checked = checked > 0 && checked === rowChecks.length;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            rowChecks.forEach(function(cb) { cb.checked = checkAll.checked; });
            syncBulkButton();
        });
    }

    rowChecks.forEach(function(cb) {
        cb.addEventListener('change', syncBulkButton);
    });
</script>
@endsection
@php
    use Illuminate\Support\Facades\DB;

    // --- FILTER & PAGINATION ---
    $search   = trim((string) request('q'));
    $filter   = request('filter', 'semua');
    $per_page = 15;
    $page     = max(1, (int) request('p', 1));
    $offset   = ($page - 1) * $per_page;

    $query = DB::table('laporan_magang as lm')
        ->join('peserta as p', 'lm.username', '=', 'p.username')
        ->where('lm.sdm_status', 'Disetujui');

    if ($filter === 'menunggu')  $query->where('lm.manager_status', 'Menunggu');
    if ($filter === 'disetujui') $query->where('lm.manager_status', 'Disetujui');
    if ($filter === 'ditolak')   $query->where('lm.manager_status', 'Ditolak');
    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('p.nama', 'like', '%' . $search . '%')
              ->orWhere('lm.username', 'like', '%' . $search . '%');
        });
    }

    $total      = (clone $query)->count();
    $total_page = (int) ceil($total / $per_page);

    $data = $query
        ->leftJoin('users as u1', 'lm.sdm_reviewed_by', '=', 'u1.username')
        ->leftJoin('users as u2', 'lm.manager_reviewed_by', '=', 'u2.username')
        ->orderBy('lm.sdm_tgl_review', 'desc')
        ->limit($per_page)
        ->offset($offset)
        ->get([
            'lm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', 'p.tgl_masuk', 'p.tgl_keluar',
            DB::raw('u1.nama AS sdm_reviewer_nama'),
            DB::raw('u2.nama AS manager_reviewer_nama'),
        ]);

    $sum_row = DB::table('laporan_magang')
        ->where('sdm_status', 'Disetujui')
        ->selectRaw('COUNT(*) as total')
        ->selectRaw("SUM(manager_status='Menunggu') as menunggu")
        ->selectRaw("SUM(manager_status='Disetujui') as disetujui")
        ->selectRaw("SUM(manager_status='Ditolak') as ditolak")
        ->first();

    $sum = [
        'total'    => (int) ($sum_row->total ?? 0),
        'menunggu' => (int) ($sum_row->menunggu ?? 0),
        'disetujui'=> (int) ($sum_row->disetujui ?? 0),
        'ditolak'  => (int) ($sum_row->ditolak ?? 0),
    ];

    $st_badge = ['Menunggu' => 'warning', 'Disetujui' => 'success', 'Ditolak' => 'danger'];
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Manager</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Status Approval Manager</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary">{{ $sum['total'] }}</div>
                <div class="small text-muted">Total ke Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning">{{ $sum['menunggu'] }}</div>
                <div class="small text-muted">Menunggu Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success">{{ $sum['disetujui'] }}</div>
                <div class="small text-muted">Disetujui Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger">{{ $sum['ditolak'] }}</div>
                <div class="small text-muted">Ditolak Manager</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-task me-1"></i> Status Laporan di Manager</h5>
        </div>

        <form method="GET" action="{{ url('sdm/approval-manager') }}" class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama / username..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <select name="filter" class="form-select form-select-sm">
                    <option value="semua" {{ $filter==='semua' ? 'selected' : '' }}>Semua</option>
                    <option value="menunggu" {{ $filter==='menunggu' ? 'selected' : '' }}>Menunggu</option>
                    <option value="disetujui" {{ $filter==='disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ $filter==='ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="{{ url('sdm/approval-manager') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Periode Magang</th>
                        <th>Review SDM</th>
                        <th>Status Manager</th>
                        <th>Review Manager</th>
                    </tr>
                </thead>
                <tbody>
                @php $no = $offset + 1; @endphp
                @foreach ($data as $row)
                    @php $badge = $st_badge[$row->manager_status] ?? 'secondary'; @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            <strong>{{ $row->nama }}</strong>
                            <small class="text-muted d-block"><code>{{ $row->username }}</code></small>
                        </td>
                        <td><small>{{ $row->asal_sekolah }}</small></td>
                        <td class="text-nowrap">
                            <small>
                                {{ date('d/m/Y', strtotime($row->tgl_masuk)) }}<br>
                                <span class="text-muted">s/d</span>
                                {{ date('d/m/Y', strtotime($row->tgl_keluar)) }}
                            </small>
                        </td>
                        <td class="text-nowrap">
                            <small>{{ date('d/m/Y H:i', strtotime($row->sdm_tgl_review)) }}</small>
                            <small class="d-block text-muted">oleh: {{ $row->sdm_reviewer_nama ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $badge }}">
                                @if ($row->manager_status === 'Disetujui')
                                    <i class="bx bxs-check-circle me-1"></i>
                                @elseif ($row->manager_status === 'Ditolak')
                                    <i class="bx bxs-x-circle me-1"></i>
                                @else
                                    <i class="bx bx-time-five me-1"></i>
                                @endif
                                {{ $row->manager_status }}
                            </span>
                            @if ($row->manager_keterangan_tolak)
                                <small class="d-block text-danger mt-1" title="{{ $row->manager_keterangan_tolak }}">
                                    <i class="bx bx-info-circle"></i> {{ mb_strimwidth($row->manager_keterangan_tolak, 0, 30, '...') }}
                                </small>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if ($row->manager_tgl_review)
                                <small>{{ date('d/m/Y H:i', strtotime($row->manager_tgl_review)) }}</small>
                                <small class="d-block text-muted">oleh: {{ $row->manager_reviewer_nama ?? '-' }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                @endif
                </tbody>
            </table>
        </div>

        @if ($total_page > 1)
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Menampilkan {{ $offset + 1 }}-{{ min($offset + $per_page, $total) }} dari {{ $total }}</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/approval-manager') }}?p={{ $page-1 }}&q={{ urlencode($search) }}&filter={{ $filter }}"><i class="bx bx-chevron-left"></i></a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i==$page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('sdm/approval-manager') }}?p={{ $i }}&q={{ urlencode($search) }}&filter={{ $filter }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/approval-manager') }}?p={{ $page+1 }}&q={{ urlencode($search) }}&filter={{ $filter }}"><i class="bx bx-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>

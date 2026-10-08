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
                ->orWhere('p.asal_sekolah', 'like', '%' . $search . '%');
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

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Daftar Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary">{{ $sum->total }}</div>
                <div class="small text-muted">Total Peserta</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success">{{ $sum->aktif }}</div>
                <div class="small text-muted">Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning">{{ $sum->menunggu }}</div>
                <div class="small text-muted">Menunggu</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-secondary">{{ $sum->selesai }}</div>
                <div class="small text-muted">Selesai</div>
            </div>
        </div>
    </div>
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
        <form method="GET" action="{{ url('sdm/peserta') }}" class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama, username, asal sekolah..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    @foreach (['Aktif', 'Menunggu', 'Selesai'] as $st)
                        <option value="{{ $st }}" {{ $status_filter == $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
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
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i>Filter</button>
                <a href="{{ url('sdm/peserta') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
                <a href="{{ $export_url }}" class="btn btn-sm btn-success ms-2">
                    <i class="bx bx-file"></i> Export Excel
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0 small">
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
                <tbody>
                @php $no = $offset + 1; @endphp
                @foreach ($data as $row)
                    @php
                        $badge   = $status_badge[$row->status_magang] ?? 'secondary';
                        $masuk   = new \DateTime($row->tgl_masuk);
                        $keluar  = new \DateTime($row->tgl_keluar);
                        $durasi  = $masuk->diff($keluar)->days;
                    @endphp
                    <tr>
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
                            <a href="{{ url('sdm/peserta/tambah') }}?copy_id={{ $row->id }}"
                               class="btn btn-sm btn-info me-1" title="Salin data peserta ini"
                               onclick="return confirm('Salin data {{ addslashes($row->nama) }} sebagai data baru?')">
                                <i class="bx bx-copy"></i>
                            </a>
                            <a href="{{ url('sdm/peserta/edit/' . $row->id) }}" class="btn btn-sm btn-warning me-1" title="Edit">
                                <i class="bx bx-edit"></i>
                            </a>
                            <a href="{{ url('sdm/peserta') }}?hapus={{ $row->id }}"
                               class="btn btn-sm btn-danger" title="Hapus"
                               onclick="return confirm('Yakin hapus peserta \'{{ addslashes($row->nama) }}\'?\nAkun login juga akan ikut terhapus.')">
                                <i class="bx bx-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr>
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

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

    $total      = (clone $query)->count();
    $total_page = (int) ceil($total / $per_page);

    $data = $query
        ->leftJoin('users as u', 'lm.sdm_reviewed_by', '=', 'u.username')
        ->orderBy('lm.tgl_upload', 'asc')
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

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Laporan Magang</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary">{{ $sum['total'] }}</div>
                <div class="small text-muted">Total Laporan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning">{{ $sum['menunggu'] }}</div>
                <div class="small text-muted">Menunggu Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-info border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-info">{{ $sum['menunggu_manager'] }}</div>
                <div class="small text-muted">Menunggu Manager</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success">{{ $sum['disetujui'] }}</div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-task me-1"></i> Laporan Menunggu Review</h5>
        </div>

        <!-- SEARCH -->
        <form method="GET" action="{{ url('sdm/approval-laporan') }}" class="row g-2 mb-3">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control"
                           placeholder="Cari nama, username, sekolah, jurusan, status, bidang, nama file..."
                           value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="{{ url('sdm/approval-laporan') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 170px;">Peserta</th>
                        <th>Asal Sekolah</th>
                        <th style="width: 95px;" class="text-center">Periode Magang</th>
                        <th style="width: 130px;">Bidang</th>
                        <th style="width: 150px;">File Laporan</th>
                        <th style="width: 95px;" class="text-center">Tgl Upload</th>
                        <th style="width: 110px;">Kehadiran</th>
                        <th style="width: 85px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @php $no = $offset + 1; @endphp
                @foreach ($data as $row)
                    @php
                        $size_label = $row->ukuran_file > 1024*1024
                            ? round($row->ukuran_file/1024/1024, 2) . ' MB'
                            : round($row->ukuran_file/1024, 1) . ' KB';
                        $total_hadir      = (int) ($row->total_hadir ?? 0);
                        $total_hari_kerja = $hitungHariKerja($row->tgl_masuk, $row->tgl_keluar, $libur_pekan, $hari_libur);
                    @endphp
                    <tr>
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
                        <td>
                            <a href="{{ asset('uploads/laporan/' . $row->file_laporan) }}"
                               target="_blank" class="file-chip" title="Buka laporan">
                                <span class="file-chip-icon"><i class="bx bxs-file-pdf"></i></span>
                                <span class="file-chip-body">
                                    <span class="file-chip-name">{{ $row->nama_file }}</span>
                                    <span class="file-chip-meta">PDF &middot; {{ $size_label }}</span>
                                </span>
                            </a>
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
                        <td class="text-center text-nowrap">
                            <form method="POST" action="{{ url('sdm/approval-laporan/setujui') }}" class="d-inline"
                                  onsubmit="return confirm('Setujui laporan {{ addslashes($row->nama) }}?')">
                                @csrf
                                <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                                <button type="submit" name="submit_setuju" class="btn btn-sm btn-success me-1" title="Setujui">
                                    <i class="bx bx-check"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger btnTolak"
                                    data-id="{{ $row->id }}"
                                    data-nama="{{ $row->nama }}"
                                    title="Tolak">
                                <i class="bx bx-x"></i>
                            </button>
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

<!-- MODAL TOLAK -->
<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('sdm/approval-laporan/tolak') }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bx bxs-x-circle me-2"></i>Tolak Laporan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="laporan_id" id="modalLaporanId">
                    <p class="mb-3">Tolak laporan dari: <strong id="modalNamaPeserta"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Keterangan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="keterangan_tolak"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Jelaskan alasan penolakan (akan tampil ke peserta)..."
                                  required></textarea>
                        <div class="form-text"><i class="bx bx-info-circle"></i> Keterangan ini akan ditampilkan kepada peserta.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_tolak" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Tolak Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.querySelectorAll('.btnTolak').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modalLaporanId').value        = this.dataset.id;
        document.getElementById('modalNamaPeserta').textContent = this.dataset.nama;
        document.querySelector('#modalTolak textarea[name="keterangan_tolak"]').value = '';
        var modal = new bootstrap.Modal(document.getElementById('modalTolak'));
        modal.show();
    });
});
</script>
@endsection

<style>
    .file-chip {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .35rem .5rem; border: 1px solid #e5e7eb; border-radius: .5rem;
        background: #fff; text-decoration: none; max-width: 100%;
        transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .file-chip:hover {
        background: #f8fafc; border-color: #cbd5e1; box-shadow: 0 1px 2px rgba(16, 24, 40, .06);
    }
    .file-chip-icon {
        display: flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; flex: 0 0 auto;
        border-radius: .4rem; background: #fee2e2; color: #dc2626; font-size: 1rem;
        transition: transform .15s ease;
    }
    .file-chip:hover .file-chip-icon { transform: scale(1.06); }
    .file-chip-body { min-width: 0; }
    .file-chip-name {
        display: block; font-size: .72rem; color: #334155; line-height: 1.2;
        white-space: normal; overflow-wrap: anywhere;
    }
    .file-chip-meta { display: block; font-size: .62rem; color: #94a3b8; }
</style>

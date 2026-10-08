<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Approval</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('manager') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Laporan Manager</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary">{{ number_format($sum->total ?? 0, 0, ',', '.') }}</div>
                <div class="small text-muted">Total Laporan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning">{{ number_format($sum->menunggu ?? 0, 0, ',', '.') }}</div>
                <div class="small text-muted">Menunggu Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-success border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success">{{ number_format($sum->disetujui ?? 0, 0, ',', '.') }}</div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger">{{ number_format($sum->ditolak ?? 0, 0, ',', '.') }}</div>
                <div class="small text-muted">Ditolak</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><i class="bx bx-check-shield me-1"></i> Approval Laporan Manager</h5>
        </div>

        <form method="GET" action="{{ url('manager/approval-laporan') }}" class="row g-2 mb-3">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama / username..." value="{{ htmlspecialchars($search) }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="{{ url('manager/approval-laporan') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
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
                        <th>Bidang</th>
                        <th>File Laporan</th>
                        <th>Di-Review SDM</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @php
                $no = $offset + 1;
                @endphp
                @foreach ($data as $row)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            <strong>{{ htmlspecialchars($row->nama) }}</strong>
                            <small class="text-muted d-block"><code>{{ htmlspecialchars($row->username) }}</code></small>
                        </td>
                        <td>
                            <small>{{ htmlspecialchars($row->asal_sekolah) }}</small><br>
                            <small class="text-muted">{{ htmlspecialchars($row->jurusan) }}</small>
                        </td>
                        <td class="text-nowrap">
                            <small>
                                {{ date('d/m/Y', strtotime($row->tgl_masuk)) }}<br>
                                <span class="text-muted">s/d</span>
                                {{ date('d/m/Y', strtotime($row->tgl_keluar)) }}
                            </small>
                        </td>
                        <td>
                            {!! $row->nama_bidang
                                ? '<span class="badge bg-light text-dark border">' . htmlspecialchars($row->nama_bidang) . '</span>'
                                : '<em class="text-muted">-</em>' !!}
                        </td>
                        <td>
                            <a href="{{ asset('uploads/laporan/' . htmlspecialchars($row->file_laporan)) }}"
                               target="_blank" class="btn btn-sm btn-outline-primary" title="Buka laporan">
                                <i class="bx bxs-file-pdf"></i> Lihat
                            </a>
                        </td>
                        <td class="text-nowrap">
                            <small>{{ date('d/m/Y H:i', strtotime($row->sdm_tgl_review)) }}</small>
                            <small class="d-block text-muted">oleh: {{ htmlspecialchars($row->sdm_reviewer_nama ?? '-') }}</small>
                        </td>
                        <td class="text-center text-nowrap">
                            <form method="POST" action="{{ url('manager/approval-laporan/approve') }}" class="d-inline"
                                  onsubmit="return confirm('Setujui laporan {{ htmlspecialchars(addslashes($row->nama)) }}?\\nSertifikat digital akan langsung diterbitkan.')">
                                @csrf
                                <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                                <input type="hidden" name="username" value="{{ htmlspecialchars($row->username) }}">
                                <button type="submit" class="btn btn-sm btn-success me-1" title="Setujui & Terbitkan Sertifikat">
                                    <i class="bx bx-check"></i> Setujui
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger btnTolak"
                                    data-id="{{ $row->id }}"
                                    data-nama="{{ htmlspecialchars($row->nama) }}">
                                <i class="bx bx-x"></i> Tolak
                            </button>
                        </td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada laporan yang menunggu approval manager.
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        @if ($total_page > 1)
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">Menampilkan {{ $offset + 1 }}-{{ min($offset + $per_page, $total) }} dari {{ $total }}</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('manager/approval-laporan') }}?p={{ $page-1 }}&q={{ urlencode($search) }}"><i class="bx bx-chevron-left"></i></a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i==$page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('manager/approval-laporan') }}?p={{ $i }}&q={{ urlencode($search) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('manager/approval-laporan') }}?p={{ $page+1 }}&q={{ urlencode($search) }}"><i class="bx bx-chevron-right"></i></a>
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
            <form method="POST" action="{{ url('manager/approval-laporan/reject') }}">
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
                        <textarea name="keterangan_tolak" class="form-control" rows="4" placeholder="Jelaskan alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bx bx-x me-1"></i> Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.querySelectorAll('.btnTolak').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modalLaporanId').value = this.dataset.id;
        document.getElementById('modalNamaPeserta').textContent = this.dataset.nama;
        var modal = new bootstrap.Modal(document.getElementById('modalTolak'));
        modal.show();
    });
});
</script>
@endsection

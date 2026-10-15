@php
    $tgl_indo = function ($t) {
        if (empty($t) || $t == '0000-00-00') return '-';
        return date('d/m/Y', strtotime($t));
    };
    $no = ($offset ?? 0) + 1;
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Sertifikat</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Sertifikat</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card border-top border-0 border-4 border-success">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="bx bx-edit me-1"></i> Edit Data Sertifikat Peserta</h5>
            <span class="badge bg-primary">{{ $total ?? 0 }} Sertifikat</span>
        </div>

        <!-- SEARCH -->
        <form method="GET" action="{{ url('sdm/edit-sertifikat') }}" class="row g-2 mb-3">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control"
                           placeholder="Cari nama / username / kampus..."
                           value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="{{ url('sdm/edit-sertifikat') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="50">#</th>
                        <th>Peserta</th>
                        <th>Kampus</th>
                        <th>Bidang / Unit</th>
                        <th>Periode</th>
                        <th>Status Sertifikat</th>
                        <th class="text-center" width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($data as $row)
                    @php
                        $badge = match ($row->status_sertif) {
                            'Digital' => 'bg-success',
                            'Sudah Upload' => 'bg-info text-dark',
                            default => 'bg-warning text-dark',
                        };
                    @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            <strong>{{ $row->nama }}</strong>
                            <small class="text-muted d-block"><code>{{ $row->username }}</code></small>
                        </td>
                        <td>
                            <small>{{ $row->asal_sekolah }}</small><br>
                            <small class="text-muted">{{ $row->jurusan }}</small>
                        </td>
                        <td>
                            {!! $row->nama_bidang ? '<span class="badge bg-light text-dark border">'.e($row->nama_bidang).'</span>' : '<em class="text-muted">-</em>' !!}
                            <small class="text-muted d-block">{{ $row->unit }}</small>
                        </td>
                        <td class="text-nowrap">
                            <small>{{ $tgl_indo($row->tgl_masuk) }} <span class="text-muted">s/d</span> {{ $tgl_indo($row->tgl_keluar) }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $badge }}">{{ $row->status_sertif ?? 'Belum Upload' }}</span>
                            @if ($row->nomor_surat)
                                <small class="text-muted d-block mt-1">No: {{ $row->nomor_surat }}</small>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">
                            <button type="button"
                                    class="btn btn-sm btn-warning btnEditSertif"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditSertif"
                                    data-username="{{ $row->username }}"
                                    data-nama="{{ $row->nama }}"
                                    data-sekolah="{{ $row->asal_sekolah }}"
                                    data-jurusan="{{ $row->jurusan }}"
                                    data-bidang="{{ $row->bidang_id }}"
                                    data-unit="{{ $row->unit }}"
                                    data-masuk="{{ $row->tgl_masuk }}"
                                    data-keluar="{{ $row->tgl_keluar }}">
                                <i class="bx bx-edit me-1"></i>Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
                @if (($total ?? 0) == 0)
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada data sertifikat yang sesuai.
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if (($total_page ?? 1) > 1)
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan {{ ($offset ?? 0) + 1 }}–{{ min(($offset ?? 0) + ($per_page ?? 15), $total) }} dari {{ $total }} sertifikat
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/edit-sertifikat') }}?p={{ $page-1 }}&q={{ urlencode($search) }}"><i class="bx bx-chevron-left"></i></a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i == $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('sdm/edit-sertifikat') }}?p={{ $i }}&q={{ urlencode($search) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/edit-sertifikat') }}?p={{ $page+1 }}&q={{ urlencode($search) }}"><i class="bx bx-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>

<!-- MODAL EDIT SERTIFIKAT -->
<div class="modal fade" id="modalEditSertif" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ url('sdm/edit-sertifikat/update') }}" id="formEditSertif">
                @csrf
                <input type="hidden" name="username" id="editUsername">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Edit Data Sertifikat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Mengedit data untuk peserta: <strong id="editNamaLabel"></strong></p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" id="editNama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kampus / Asal Sekolah</label>
                            <input type="text" name="asal_sekolah" id="editSekolah" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jurusan</label>
                            <input type="text" name="jurusan" id="editJurusan" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bidang</label>
                            <select name="bidang_id" id="editBidang" class="form-select">
                                <option value="">- Pilih Bidang -</option>
                                @foreach ($bidang_list as $b)
                                    <option value="{{ $b->id }}">{{ $b->bidang }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Unit</label>
                            <select name="unit" id="editUnit" class="form-select" required>
                                <option value="Unit 1-2">Unit 1-2</option>
                                <option value="Unit 9">Unit 9</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Periode Mulai</label>
                            <input type="date" name="tgl_masuk" id="editMasuk" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Periode Selesai</label>
                            <input type="date" name="tgl_keluar" id="editKeluar" class="form-control" required>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="bx bx-info-circle me-1"></i>
                        Perubahan pada data peserta akan langsung memperbarui <strong>file PDF sertifikat digital</strong> secara otomatis saat disimpan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_edit_sertif" class="btn btn-success">
                        <i class="bx bx-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btnEditSertif').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editUsername').value = this.dataset.username || '';
            document.getElementById('editNamaLabel').textContent = this.dataset.nama || '';
            document.getElementById('editNama').value = this.dataset.nama || '';
            document.getElementById('editSekolah').value = this.dataset.sekolah || '';
            document.getElementById('editJurusan').value = this.dataset.jurusan || '';
            document.getElementById('editBidang').value = this.dataset.bidang || '';
            document.getElementById('editUnit').value = this.dataset.unit || 'Unit 1-2';
            document.getElementById('editMasuk').value = this.dataset.masuk || '';
            document.getElementById('editKeluar').value = this.dataset.keluar || '';
        });
    });
});
</script>

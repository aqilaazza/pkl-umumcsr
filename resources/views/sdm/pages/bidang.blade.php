@php
    use Illuminate\Support\Facades\DB;

    $edit_data = null;
    if (request()->filled('edit')) {
        $edit_data = DB::table('bidang')->find((int) request('edit'));
    }

    $data = DB::table('bidang')->orderBy('bidang')->get();
    $no = 1;
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Bidang</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">

    <!-- FORM TAMBAH / EDIT -->
    <div class="col-xl-4">
        <div class="card border-top border-0 border-4 {{ $edit_data ? 'border-warning' : 'border-primary' }}">
            <div class="card-body">
                <div class="border p-4 rounded">
                    <div class="card-title d-flex align-items-center gap-2">
                        <i class="bx {{ $edit_data ? 'bxs-edit' : 'bxs-layer-plus' }} font-22"></i>
                        <h5 class="mb-0">{{ $edit_data ? 'Edit Bidang' : 'Tambah Bidang' }}</h5>
                    </div>
                    <hr />

                    @if ($edit_data)
                    <!-- FORM EDIT -->
                    <form action="{{ url('sdm/bidang/update/' . $edit_data->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $edit_data->id }}">
                        <div class="mb-3">
                            <label class="form-label">Nama Bidang</label>
                            <input type="text"
                                   name="bidang"
                                   class="form-control"
                                   placeholder="Masukkan nama bidang"
                                   value="{{ old('bidang', $edit_data->bidang) }}"
                                   required>
                            <div class="form-text">Nama bidang harus unik.</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="submit_edit" class="btn btn-warning px-4">
                                <i class="bx bx-save me-1"></i>Simpan
                            </button>
                            <a href="{{ url('sdm/bidang') }}" class="btn btn-secondary px-4">
                                <i class="bx bx-x me-1"></i>Batal
                            </a>
                        </div>
                    </form>

                    @else
                    <!-- FORM TAMBAH -->
                    <form action="{{ url('sdm/bidang/store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama Bidang</label>
                            <input type="text"
                                   name="bidang"
                                   class="form-control"
                                   placeholder="Masukkan nama bidang"
                                   required>
                            <div class="form-text">Nama bidang harus unik.</div>
                        </div>
                        <button type="submit" name="submit_add" class="btn btn-primary px-5">
                            <i class="bx bx-layer-plus me-1"></i>Tambahkan
                        </button>
                    </form>
                    @endif

                </div>
            </div>
        </div>

        <!-- PANDUAN -->
        <div class="card border-top border-0 border-4 border-info mt-3">
            <div class="card-body p-4">
                <h6 class="card-title d-flex align-items-center gap-2">
                    <i class="bx bxs-info-circle text-info"></i> Panduan
                </h6>
                <ul class="mb-0 ps-3 small text-muted">
                    <li>Nama bidang harus <strong>unik</strong> dan tidak boleh duplikat.</li>
                    <li>Klik tombol <span class="badge bg-warning text-dark"><i class="bx bx-edit"></i></span> untuk mengedit data bidang.</li>
                    <li>Klik tombol <span class="badge bg-danger"><i class="bx bx-trash"></i></span> untuk menghapus data bidang.</li>
                    <li>Data bidang digunakan sebagai referensi di seluruh modul aplikasi.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- LIST DATA -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="bx bxs-layer me-1"></i> Daftar Bidang
                    </h5>
                    <span class="badge bg-primary">{{ $data->count() }} Bidang</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th width="60">#</th>
                                <th>Nama Bidang</th>
                                <th class="text-center" width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($data as $row)
                            @php $is_editing = ($edit_data && $edit_data->id == $row->id); @endphp
                            <tr class="{{ $is_editing ? 'table-warning' : '' }}">
                                <td>{{ $no++ }}</td>
                                <td>
                                    <i class="bx bxs-layer text-primary me-1"></i>
                                    <strong>{{ $row->bidang }}</strong>
                                    @if ($is_editing)
                                        <span class="badge bg-warning text-dark ms-2">Sedang Diedit</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <!-- Tombol Edit -->
                                    <a href="{{ url('sdm/bidang?edit=' . $row->id) }}"
                                       class="btn btn-sm btn-warning me-1"
                                       title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <!-- Tombol Hapus -->
                                    <a href="{{ url('sdm/bidang/hapus/' . $row->id) }}"
                                       class="btn btn-sm btn-danger"
                                       title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus bidang \'{{ addslashes($row->bidang) }}\'?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        @if ($data->count() == 0)
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    <i class="bx bx-layer-minus fs-3 d-block mb-1"></i>
                                    Belum ada data bidang.
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

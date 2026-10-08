@php
    use Illuminate\Support\Facades\DB;

    $edit_data = null;
    if (request()->filled('edit')) {
        $edit_data = DB::table('dokumen')->find((int) request('edit'));
    }

    $data = DB::table('dokumen')->orderBy('id', 'desc')->get();
    $no = 1;
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Master Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Data Dokumen</li>
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
                        <i class="bx {{ $edit_data ? 'bxs-edit' : 'bxs-file-plus' }} font-22"></i>
                        <h5 class="mb-0">{{ $edit_data ? 'Edit Dokumen' : 'Tambah Dokumen' }}</h5>
                    </div>
                    <hr />

                    @if ($edit_data)
                    <!-- FORM EDIT -->
                    <form action="{{ url('sdm/dokumen/update/' . $edit_data->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="{{ $edit_data->id }}">
                        <input type="hidden" name="old_file" value="{{ $edit_data->file_dokumen }}">

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Nama Dokumen</label>
                            <input type="text"
                                   name="nama_dokumen"
                                   class="form-control"
                                   placeholder="Masukkan nama dokumen"
                                   value="{{ old('nama_dokumen', $edit_data->nama_dokumen) }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">File (Opsional, jika ingin diganti)</label>
                            <input type="file"
                                   name="file_dokumen"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div class="form-text mt-2">
                                File saat ini:
                                <a href="{{ asset('uploads/dokumen/' . $edit_data->file_dokumen) }}" target="_blank" class="text-primary text-decoration-none">
                                    <i class="bx bx-file me-1"></i>{{ $edit_data->file_dokumen }}
                                </a>
                            </div>
                            <div class="form-text">Format: PDF, JPG, JPEG, PNG, WEBP (Maks 15MB).</div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" name="submit_edit" class="btn btn-warning px-4">
                                <i class="bx bx-save me-1"></i>Simpan Perubahan
                            </button>
                            <a href="{{ url('sdm/dokumen') }}" class="btn btn-secondary px-4">
                                <i class="bx bx-x me-1"></i>Batal
                            </a>
                        </div>
                    </form>

                    @else
                    <!-- FORM TAMBAH -->
                    <form action="{{ url('sdm/dokumen/store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Nama Dokumen</label>
                            <input type="text"
                                   name="nama_dokumen"
                                   class="form-control"
                                   placeholder="Contoh: SK Pengangkatan, SOP Magang"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">File Dokumen (Gambar / PDF)</label>
                            <input type="file"
                                   name="file_dokumen"
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp"
                                   required>
                            <div class="form-text mt-1">Format yang diperbolehkan: <strong>PDF, JPG, JPEG, PNG, WEBP</strong> (Maks 15MB).</div>
                        </div>

                        <button type="submit" name="submit_add" class="btn btn-primary px-4 mt-2">
                            <i class="bx bx-upload me-1"></i>Upload Dokumen
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
                    <i class="bx bxs-info-circle text-info"></i> Panduan Upload Dokumen
                </h6>
                <ul class="mb-0 ps-3 small text-muted">
                    <li>Isi <strong>Nama Dokumen</strong> secara jelas dan tepat.</li>
                    <li>Pilih file berformat <strong>Gambar (JPG, PNG, WEBP)</strong> atau <strong>PDF</strong>.</li>
                    <li>Klik tombol <span class="badge bg-info text-white"><i class="bx bx-show"></i></span> untuk melihat / mengunduh file dokumen.</li>
                    <li>Klik tombol <span class="badge bg-warning text-dark"><i class="bx bx-edit"></i></span> untuk mengubah nama atau mengganti file dokumen.</li>
                    <li>Klik tombol <span class="badge bg-danger"><i class="bx bx-trash"></i></span> untuk menghapus dokumen.</li>
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
                        <i class="bx bxs-file-doc me-1"></i> Daftar Dokumen
                    </h5>
                    <span class="badge bg-primary fs-6">{{ $data->count() }} Dokumen</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0" id="tabelDokumen">
                        <thead class="table-dark">
                            <tr>
                                <th width="50">#</th>
                                <th>Nama Dokumen</th>
                                <th>Tipe / Preview</th>
                                <th>Tgl Upload</th>
                                <th class="text-center" width="130">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($data as $row)
                            @php
                                $is_editing = ($edit_data && $edit_data->id == $row->id);
                                $ext = strtolower(pathinfo($row->file_dokumen, PATHINFO_EXTENSION));
                                $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                $file_url = asset('uploads/dokumen/' . $row->file_dokumen);
                            @endphp
                            <tr class="{{ $is_editing ? 'table-warning' : '' }}">
                                <td>{{ $no++ }}</td>
                                <td>
                                    <strong>{{ $row->nama_dokumen }}</strong>
                                    @if ($is_editing)
                                        <span class="badge bg-warning text-dark ms-2">Sedang Diedit</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($is_image)
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ $file_url }}" target="_blank" title="Lihat Gambar">
                                                <img src="{{ $file_url }}" alt="Preview" class="rounded border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                                            </a>
                                            <span class="badge bg-success"><i class="bx bxs-image me-1"></i>Gambar ({{ strtoupper($ext) }})</span>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-danger"><i class="bx bxs-file-pdf me-1"></i>PDF Dokumen</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted"><i class="bx bx-time-five me-1"></i>{{ date('d M Y, H:i', strtotime($row->created_at)) }}</small>
                                </td>
                                <td class="text-center">
                                    <!-- Lihat / Download -->
                                    <a href="{{ $file_url }}"
                                       target="_blank"
                                       class="btn btn-sm btn-info text-white me-1"
                                       title="Lihat / Download">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <!-- Edit -->
                                    <a href="{{ url('sdm/dokumen?edit=' . $row->id) }}"
                                       class="btn btn-sm btn-warning me-1"
                                       title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <!-- Hapus -->
                                    <a href="{{ url('sdm/dokumen/hapus/' . $row->id) }}"
                                       class="btn btn-sm btn-danger"
                                       title="Hapus"
                                       onclick="return confirm('Yakin ingin menghapus dokumen \'{{ addslashes($row->nama_dokumen) }}\'?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        @if ($data->count() == 0)
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bx bx-file-blank fs-2 d-block mb-1"></i>
                                    Belum ada data dokumen.
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.jQuery && $.fn.DataTable && $('#tabelDokumen tbody tr').length > 0 && !$('#tabelDokumen tbody td').hasClass('text-center')) {
        $('#tabelDokumen').DataTable({
            paging: true,
            pageLength: 10,
            language: {
                search: "Cari Dokumen:",
                lengthMenu: "Tampilkan _MENU_ data",
                zeroRecords: "Tidak ada dokumen yang sesuai",
                info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ dokumen",
                infoEmpty: "Dokumen kosong",
                infoFiltered: "(disaring dari _MAX_ total dokumen)"
            }
        });
    }
});
</script>
@endsection

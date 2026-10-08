@php
    use Illuminate\Support\Facades\DB;

    $id        = (int) request()->route('id');
    $edit_data = DB::table('peserta')->where('id', $id)->first();

    $list_bidang  = DB::table('bidang')->orderBy('bidang')->get();
    $list_sekolah = DB::table('peserta')->where('asal_sekolah', '!=', '')->distinct()->orderBy('asal_sekolah')->pluck('asal_sekolah');
    $list_jurusan = DB::table('peserta')->where('jurusan', '!=', '')->distinct()->orderBy('jurusan')->pluck('jurusan');
@endphp

@if ($edit_data)
<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ url('sdm/peserta') }}">Daftar Peserta</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Datalist autocomplete -->
<datalist id="listSekolah">
    @foreach ($list_sekolah as $s)
        <option value="{{ $s }}">
    @endforeach
</datalist>
<datalist id="listJurusan">
    @foreach ($list_jurusan as $j)
        <option value="{{ $j }}">
    @endforeach
</datalist>

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">
        <div class="card border-top border-0 border-4 border-warning">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bx bxs-edit-alt fs-4 text-warning"></i>
                    <h5 class="mb-0">Edit Peserta</h5>
                    <span class="badge bg-light text-dark border ms-auto">
                        <i class="bx bx-id-card me-1"></i>{{ $edit_data->username }}
                    </span>
                </div>
                <hr class="mt-0">

                <form action="{{ url('sdm/peserta/edit/' . $id) }}" method="POST" id="formEdit">
                    @csrf

                    <!-- SEKSI: Akun -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">
                            <i class="bx bx-lock-alt me-1"></i> Informasi Akun
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control bg-light"
                                       value="{{ $edit_data->username }}" readonly>
                                <div class="form-text">Username tidak dapat diubah.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="text" class="form-control bg-light" value="••••••••" readonly>
                                <div class="form-text">Password dikelola oleh peserta masing-masing.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI: Data Pribadi -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">
                            <i class="bx bx-user me-1"></i> Data Pribadi
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="nama" class="form-control"
                                       value="{{ old('nama', $edit_data->nama) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Peserta <span class="text-danger">*</span></label>
                                <select name="status_peserta" class="form-select" required>
                                    @foreach (['Siswa', 'Mahasiswa'] as $sp)
                                        <option value="{{ $sp }}" {{ old('status_peserta', $edit_data->status_peserta) == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Asal Sekolah / Universitas <span class="text-danger">*</span></label>
                                <input type="text" name="asal_sekolah" class="form-control"
                                       list="listSekolah"
                                       placeholder="Ketik atau pilih dari daftar..."
                                       value="{{ old('asal_sekolah', $edit_data->asal_sekolah) }}"
                                       autocomplete="off"
                                       required>
                                <div class="form-text"><i class="bx bx-bulb"></i> Pilih dari daftar atau ketik manual jika belum ada.</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Jurusan <span class="text-danger">*</span></label>
                                <input type="text" name="jurusan" class="form-control"
                                       list="listJurusan"
                                       placeholder="Ketik atau pilih dari daftar..."
                                       value="{{ old('jurusan', $edit_data->jurusan) }}"
                                       autocomplete="off"
                                       required>
                                <div class="form-text"><i class="bx bx-bulb"></i> Pilih dari daftar atau ketik manual jika belum ada.</div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI: Data Magang -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase small fw-bold mb-3">
                            <i class="bx bx-briefcase me-1"></i> Data Magang
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" name="tgl_masuk" id="tglMasuk" class="form-control"
                                       value="{{ old('tgl_masuk', $edit_data->tgl_masuk) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Keluar <span class="text-danger">*</span></label>
                                <input type="date" name="tgl_keluar" id="tglKeluar" class="form-control"
                                       value="{{ old('tgl_keluar', $edit_data->tgl_keluar) }}" required>
                            </div>
                            <div class="col-12">
                                <div id="selisihHari" class="alert alert-info py-2 d-none">
                                    <i class="bx bx-calendar-check me-1"></i>
                                    Durasi magang: <strong id="jumlahHari">-</strong>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bidang</label>
                                <select name="bidang_id" class="form-select">
                                    <option value="">-- Pilih Bidang --</option>
                                    @foreach ($list_bidang as $b)
                                        <option value="{{ $b->id }}" {{ old('bidang_id', $edit_data->bidang_id) == $b->id ? 'selected' : '' }}>
                                            {{ $b->bidang }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit" class="form-select" required>
                                    @foreach (['Unit 1-2', 'Unit 9'] as $u)
                                        <option value="{{ $u }}" {{ old('unit', $edit_data->unit) == $u ? 'selected' : '' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Magang <span class="text-danger">*</span></label>
                                <select name="status_magang" class="form-select" required>
                                    @foreach (['Aktif', 'Menunggu', 'Selesai'] as $st)
                                        <option value="{{ $st }}" {{ old('status_magang', $edit_data->status_magang) == $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $edit_data->keterangan) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex gap-2">
                        <button type="submit" name="submit_edit" class="btn btn-warning px-5">
                            <i class="bx bx-save me-1"></i> Simpan Perubahan
                        </button>
                        <a href="{{ url('sdm/peserta') }}" class="btn btn-outline-secondary px-4">
                            <i class="bx bx-arrow-back me-1"></i> Kembali
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
@endif

@section('scripts')
@if ($edit_data)
<script>
function hitungSelisih() {
    var masuk  = document.getElementById('tglMasuk').value;
    var keluar = document.getElementById('tglKeluar').value;
    var box    = document.getElementById('selisihHari');
    var label  = document.getElementById('jumlahHari');

    if (masuk && keluar) {
        var d1   = new Date(masuk);
        var d2   = new Date(keluar);
        var diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));

        box.classList.remove('d-none', 'alert-info', 'alert-danger');

        if (diff > 0) {
            var bulan = Math.floor(diff / 30);
            var sisa  = diff % 30;
            var teks  = diff + ' hari';
            if (bulan > 0) teks += ' (' + bulan + ' bulan' + (sisa > 0 ? ' ' + sisa + ' hari' : '') + ')';
            label.textContent = teks;
            box.classList.add('alert-info');
        } else {
            label.textContent = 'Tanggal keluar harus setelah tanggal masuk!';
            box.classList.add('alert-danger');
        }
    } else {
        box.classList.add('d-none');
    }
}
document.getElementById('tglMasuk').addEventListener('change', hitungSelisih);
document.getElementById('tglKeluar').addEventListener('change', hitungSelisih);
hitungSelisih(); // Langsung hitung saat halaman load
</script>
@endif
@endsection

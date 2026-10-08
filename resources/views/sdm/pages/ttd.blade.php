@php
    use Illuminate\Support\Facades\DB;

    $cfg = DB::table('pengaturan_ttd')->where('id', 1)->first();
    $nama_val = $cfg->nama_ttd ?? 'Sukarno';
    $jabatan_val = $cfg->jabatan_ttd ?? 'Manager Business Support';
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Tanda Tangan Sertifikat</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-success">
            <div class="card-body p-4">
                <div class="card-title d-flex align-items-center gap-2">
                    <i class="bx bxs-certification text-success font-24"></i>
                    <h5 class="mb-0">Atur Nama & Jabatan Penandatangan</h5>
                </div>
                <hr />
                <form action="{{ url('sdm/pengaturan/ttd') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_ttd" class="form-control" value="{{ $nama_val }}" required>
                        <div class="form-text">Nama pejabat yang menandatangani sertifikat.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jabatan</label>
                        <input type="text" name="jabatan_ttd" class="form-control" value="{{ $jabatan_val }}" required>
                        <div class="form-text">Contoh: Manager Business Support, Kepala Unit, dll.</div>
                    </div>

                    <button type="submit" name="save_ttd" class="btn btn-success px-4">
                        <i class="bx bx-save me-1"></i>Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-info">
            <div class="card-body p-4">
                <h5 class="card-title mb-3 text-info"><i class="bx bx-info-circle me-1"></i> Preview Tanda Tangan</h5>
                <div class="text-center p-4 border rounded-3 bg-light">
                    <div style="font-family: 'Brush Script MT', cursive; font-size: 28px; color: #1a1a2e;">
                        {{ $nama_val }}
                    </div>
                    <div class="text-muted mt-2" style="font-size: 13px;">
                        {{ $jabatan_val }}
                    </div>
                </div>
                <hr>
                <div class="alert alert-info mb-0 small">
                    <i class="bx bx-printer me-1"></i>
                    Data ini akan digunakan saat mencetak sertifikat peserta melalui menu
                    <strong>Cetak Sertifikat</strong>.
                </div>
            </div>
        </div>
    </div>
</div>

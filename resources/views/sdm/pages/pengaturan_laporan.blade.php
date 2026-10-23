@php
    use Illuminate\Support\Facades\DB;

    $cfg = DB::table('pengaturan_laporan')->where('id', 1)->first();
    $max_val = $cfg->max_ukuran_mb ?? 5;
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3"><span class="sdm-title-ico"><i class="bx bx-file"></i></span>Ukuran File Laporan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}">Beranda</a></li>
                <li class="breadcrumb-item">Sistem PKL</li>
                <li class="breadcrumb-item active" aria-current="page">Ukuran File Laporan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-success">
            <div class="card-body p-4">
                <div class="card-title d-flex align-items-center gap-2">
                    <i class="bx bx-file text-success font-24"></i>
                    <h5 class="mb-0">Atur Ukuran Maksimal File Laporan</h5>
                </div>
                <hr />
                <form action="{{ url('sdm/pengaturan/laporan') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ukuran Maksimal File Laporan (MB)</label>
                        <input type="number" min="1" name="max_ukuran_mb" class="form-control" value="{{ $max_val }}" required>
                        <div class="form-text">Batas ukuran file PDF laporan yang boleh diunggah peserta. Default 5 MB.</div>
                    </div>

                    <button type="submit" name="save_laporan" class="btn btn-success px-4">
                        <i class="bx bx-save me-1"></i>Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-info">
            <div class="card-body p-4">
                <h5 class="card-title mb-3 text-info"><i class="bx bx-info-circle me-1"></i> Keterangan</h5>
                <ul class="small text-muted ps-3">
                    <li class="mb-2">Pengaturan ini berlaku untuk semua peserta saat mengunggah laporan magang.</li>
                    <li class="mb-2">File yang melebihi batas akan otomatis ditolak oleh sistem.</li>
                    <li class="mb-2">Saat ini batas maksimal: <strong>{{ $max_val }} MB</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

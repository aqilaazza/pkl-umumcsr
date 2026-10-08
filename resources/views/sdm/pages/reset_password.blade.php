@php
    $search_result = $search_result ?? null;
    $keyword = $keyword ?? '';
    $search_performed = $search_performed ?? false;
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Admin</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Reset Password Peserta</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-3"><i class="bx bx-search-alt me-2"></i>Cari Peserta</h5>
                <form method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-10">
                        <input type="text" name="keyword" class="form-control"
                               placeholder="Masukkan Nama Lengkap atau Username Peserta..."
                               value="{{ $keyword }}" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" name="search" class="btn btn-primary">
                            <i class="bx bx-search"></i> Cari Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="row">
    @if ($search_result && $search_result->count() > 0)
        @foreach ($search_result as $row)
            <div class="col-md-6 col-lg-4">
                <div class="card border-bottom border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="user-avatar bg-light-info text-info p-3 rounded-circle me-3">
                                <i class="bx bxs-user-detail fs-3"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $row->nama }}</h6>
                                <small class="text-muted">Username: <strong>{{ $row->username }}</strong></small>
                            </div>
                        </div>

                        <table class="table table-sm table-borderless small mb-3">
                            <tr>
                                <td width="100">Instansi/Sekolah</td>
                                <td>: {{ $row->asal_sekolah }}</td>
                            </tr>
                            <tr>
                                <td>Jurusan</td>
                                <td>: {{ $row->jurusan }}</td>
                            </tr>
                            <tr>
                                <td>Bidang</td>
                                <td>: <span class="badge bg-light-primary text-primary">{{ $row->nama_bidang ?? 'Belum Ditentukan' }}</span></td>
                            </tr>
                            <tr>
                                <td>Unit</td>
                                <td>: {{ $row->unit }}</td>
                            </tr>
                            <tr>
                                <td>Periode</td>
                                <td>: {{ date('d M Y', strtotime($row->tgl_masuk)) }} s/d {{ date('d M Y', strtotime($row->tgl_keluar)) }}</td>
                            </tr>
                            <tr>
                                <td>Status Magang</td>
                                <td>:
                                    @php
                                        $color = ($row->status_magang == 'Aktif') ? 'success' : (($row->status_magang == 'Menunggu') ? 'warning' : 'secondary');
                                    @endphp
                                    <span class='badge bg-{{ $color }}'>{{ $row->status_magang }}</span>
                                </td>
                            </tr>
                        </table>

                        <div class="d-grid mt-2">
                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mereset password akun ini?')">
                                @csrf
                                <input type="hidden" name="username" value="{{ $row->username }}">
                                <button type="submit" name="confirm_reset" class="btn btn-danger btn-sm w-100">
                                    <i class="bx bx-refresh"></i> Reset Password ke "123456"
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @elseif ($search_performed)
        <div class="col-12 text-center py-5">
            <i class="bx bx-user-x text-muted" style="font-size: 5rem;"></i>
            <h5 class="mt-3 text-muted">Peserta tidak ditemukan</h5>
            <p>Pastikan nama atau username yang Anda masukkan sudah benar.</p>
        </div>
    @endif
</div>

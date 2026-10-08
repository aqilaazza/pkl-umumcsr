@php
    use Illuminate\Support\Facades\DB;

    $libur_pekan_db = DB::table('libur_pekan')->pluck('hari_index')->map(fn ($i) => (int) $i)->all();

    $filter_tahun = request()->has('tahun') ? (int) request('tahun') : (int) date('Y');
    $data_libur = DB::table('hari_libur')
        ->whereRaw('YEAR(tanggal) = ?', [$filter_tahun])
        ->orderBy('tanggal', 'ASC')
        ->get();

    $bulan_names = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $hari_names = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

    $hari_names_indo = [
        0 => 'Minggu (Ahad)',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu'
    ];
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active">Hari Libur & Pekan</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Kolom Kiri: Pengaturan Libur Pekan & Form Tambah -->
    <div class="col-lg-4">
        <!-- Default Libur Pekan -->
        <div class="card border-top border-0 border-4 border-primary mb-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-3"><i class="bx bx-time me-1 text-primary"></i> Default Libur Pekan</h5>
                <form method="POST" action="{{ url('sdm/pengaturan/hari-libur/libur-pekan') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label d-block fw-bold text-muted small">Pilih Hari Libur Pekan:</label>
                        @foreach ($hari_names_indo as $idx => $nama)
                            @php $checked = in_array($idx, $libur_pekan_db) ? 'checked' : ''; @endphp
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hari_libur_pekan[]" value="{{ $idx }}" id="hari_{{ $idx }}" {{ $checked }}>
                            <label class="form-check-label small" for="hari_{{ $idx }}">
                                {{ $nama }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <button type="submit" name="update_libur_pekan" class="btn btn-primary w-100 btn-sm">
                        <i class="bx bx-save me-1"></i>Simpan Libur Pekan
                    </button>
                </form>
            </div>
        </div>

        <!-- Tambah Hari Libur Khusus -->
        <div class="card border-top border-0 border-4 border-danger">
            <div class="card-body p-4">
                <h5 class="card-title mb-3"><i class="bx bx-calendar-plus me-1 text-danger"></i> Tambah Libur Khusus</h5>
                <form method="POST" action="{{ url('sdm/pengaturan/hari-libur') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small">Tanggal Libur</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Keterangan / Nama Libur</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm" placeholder="Misal: Tahun Baru" required>
                    </div>
                    <button type="submit" name="tambah_libur" class="btn btn-danger w-100 btn-sm">
                        <i class="bx bx-plus me-1"></i>Tambah Libur Khusus
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar Hari Libur Khusus -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bx bx-calendar-x me-1"></i> Daftar Libur Khusus {{ $filter_tahun }}</h5>
                    <div>
                        <a href="{{ url('sdm/pengaturan/hari-libur') }}?tahun={{ $filter_tahun - 1 }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-chevron-left"></i></a>
                        <span class="mx-2 fw-bold small">{{ $filter_tahun }}</span>
                        <a href="{{ url('sdm/pengaturan/hari-libur') }}?tahun={{ $filter_tahun + 1 }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-chevron-right"></i></a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Keterangan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($data_libur as $r)
                            @php
                                $day = $hari_names[date('w', strtotime($r->tanggal))];
                                $tgl = date('d', strtotime($r->tanggal)) . ' ' . $bulan_names[(int) date('m', strtotime($r->tanggal))] . ' ' . date('Y', strtotime($r->tanggal));
                            @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $tgl }}</td>
                            <td><span class="badge bg-info text-dark">{{ $day }}</span></td>
                            <td>{{ $r->keterangan }}</td>
                            <td class="text-center">
                                <form method="POST" action="{{ url('sdm/pengaturan/hari-libur/hapus/' . $r->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus hari libur ini?')">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        @if ($data_libur->isEmpty())
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bx bx-calendar-x fs-3 d-block mb-1"></i>
                                Belum ada hari libur khusus di tahun {{ $filter_tahun }}
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

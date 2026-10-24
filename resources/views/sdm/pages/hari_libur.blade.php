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

<div class="pkl-set">
    <div class="pkl-mission">
        <div class="pkl-mission__eyebrow">
            <nav class="pkl-crumb" aria-label="breadcrumb">
                <a href="{{ url('sdm') }}">Beranda</a>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span>Pengaturan</span>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span class="pkl-crumb__current">Hari Libur</span>
            </nav>
            <span class="pkl-chip pkl-chip--primary">
                <i class='bx bx-calendar'></i>
                Tahun {{ $filter_tahun }}
            </span>
        </div>
        <div class="pkl-mission__text">
            <h1 class="pkl-mission__title">Hari Libur &amp; Pekan</h1>
            <p class="pkl-mission__desc">
                Kelola libur pekan rutin dan tanggal libur khusus yang dipakai dalam
                perhitungan absensi serta hari kerja peserta.
            </p>
        </div>
<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3"><span class="sdm-title-ico"><i class="bx bx-calendar-x"></i></span>Hari Libur &amp; Pekan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}">Beranda</a></li>
                <li class="breadcrumb-item">Sistem PKL</li>
                <li class="breadcrumb-item active">Hari Libur & Pekan</li>
            </ol>
        </nav>
    </div>

    <div class="set-grid set-grid--holiday">
        <div class="set-col">
            <div class="pkl-panel pkl-panel--accent">
                <div class="pkl-panel__head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="pkl-panel__icon"><i class='bx bx-time'></i></span>
                        <div>
                            <h2 class="pkl-panel__title">Libur Pekan Rutin</h2>
                            <span class="pkl-panel__sub">Hari Mingguan</span>
                        </div>
                    </div>
                    <span class="pkl-pill pkl-pill--muted">{{ count($libur_pekan_db) }} hari</span>
                </div>

                <form class="pkl-panel__body" method="POST" action="{{ url('sdm/pengaturan/hari-libur/libur-pekan') }}">
                    @csrf

                    <div class="set-days">
                        @foreach ($hari_names_indo as $idx => $nama)
                            @php $checked = in_array($idx, $libur_pekan_db) ? 'checked' : ''; @endphp
                            <label class="set-day" for="hari_{{ $idx }}">
                                <input class="form-check-input" type="checkbox" name="hari_libur_pekan[]" value="{{ $idx }}" id="hari_{{ $idx }}" {{ $checked }}>
                                <span class="set-day__tick"><i class='bx bx-check'></i></span>
                                <span>{{ $nama }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="set-info">
                        <i class='bx bx-info-circle'></i>
                        <span>Hari yang dipilih akan dianggap sebagai libur pekan pada semua perhitungan absensi.</span>
                    </div>

                    <div class="set-actions">
                        <button type="submit" name="update_libur_pekan" class="pkl-btn pkl-btn--primary">
                            <i class='bx bx-save'></i> Simpan Libur Pekan
                        </button>
                    </div>
                </form>
            </div>

            <div class="pkl-panel pkl-panel--accent">
                <div class="pkl-panel__head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="pkl-panel__icon"><i class='bx bx-calendar-plus'></i></span>
                        <div>
                            <h2 class="pkl-panel__title">Tambah Libur Khusus</h2>
                            <span class="pkl-panel__sub">Tanggal Tertentu</span>
                        </div>
                    </div>
                    <span class="pkl-pill pkl-pill--primary">Sekali</span>
                </div>

                <form class="pkl-panel__body" method="POST" action="{{ url('sdm/pengaturan/hari-libur') }}">
                    @csrf

                    <div class="pkl-field">
                        <div class="pkl-field__label">
                            <span>Tanggal Libur</span>
                            <small class="pkl-mono">Date</small>
                        </div>
                        <div class="pkl-field__control">
                            <span class="pkl-field__icon"><i class='bx bx-calendar'></i></span>
                            <input type="date" name="tanggal" required>
                        </div>
                    </div>

                    <div class="pkl-field">
                        <div class="pkl-field__label">
                            <span>Keterangan / Nama Libur</span>
                            <small class="pkl-mono">Text</small>
                        </div>
                        <div class="pkl-field__control">
                            <span class="pkl-field__icon"><i class='bx bx-text'></i></span>
                            <input type="text" name="keterangan" placeholder="Misal: Tahun Baru" required>
                        </div>
                    </div>

                    <div class="set-actions">
                        <button type="submit" name="tambah_libur" class="pkl-btn pkl-btn--primary">
                            <i class='bx bx-plus'></i> Tambah Libur Khusus
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bx-calendar-x'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Daftar Libur Khusus</h2>
                        <span class="pkl-panel__sub">{{ $data_libur->count() }} tanggal terdaftar</span>
                    </div>
                </div>
                <div class="set-yearnav" aria-label="Pilih tahun">
                    <a href="{{ url('sdm/pengaturan/hari-libur') }}?tahun={{ $filter_tahun - 1 }}" class="set-yearnav__btn" title="Tahun sebelumnya" aria-label="Tahun sebelumnya">
                        <i class='bx bx-chevron-left'></i>
                    </a>
                    <span class="set-yearnav__value">{{ $filter_tahun }}</span>
                    <a href="{{ url('sdm/pengaturan/hari-libur') }}?tahun={{ $filter_tahun + 1 }}" class="set-yearnav__btn" title="Tahun berikutnya" aria-label="Tahun berikutnya">
                        <i class='bx bx-chevron-right'></i>
                    </a>
                </div>
            </div>

            <div class="set-toolbar">
                <div class="set-search">
                    <i class='bx bx-search'></i>
                    <input type="text" class="form-control form-control-sm" placeholder="Cari tanggal / keterangan..."
                           data-ui-filter="#tabelLibur" aria-label="Cari hari libur">
                </div>
                <button type="button" class="pkl-btn pkl-btn--primary" data-ui-filter-apply="#tabelLibur">
                    <i class='bx bx-search'></i> Cari
                </button>
            </div>

            <div class="set-table-wrap">
                <table class="set-table" id="tabelLibur">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Keterangan</th>
                            <th class="is-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($data_libur as $r)
                        @php
                            $day = $hari_names[date('w', strtotime($r->tanggal))];
                            $tgl = date('d', strtotime($r->tanggal)) . ' ' . $bulan_names[(int) date('m', strtotime($r->tanggal))] . ' ' . date('Y', strtotime($r->tanggal));
                        @endphp
                        <tr>
                            <td class="pkl-mono">{{ $loop->iteration }}</td>
                            <td class="set-name">{{ $tgl }}</td>
                            <td><span class="set-badge">{{ $day }}</span></td>
                            <td>{{ $r->keterangan }}</td>
                            <td class="is-center">
                                <form method="POST" action="{{ url('sdm/pengaturan/hari-libur/hapus/' . $r->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="set-icon-btn"
                                            title="Hapus hari libur"
                                            data-ui-confirm="Hapus hari libur ini?"
                                            data-confirm-title="Hapus Hari Libur?"
                                            data-confirm-ok="Hapus">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if ($data_libur->isEmpty())
                        <tr class="pkl-empty">
                            <td colspan="5">
                                <i class='bx bx-calendar-x'></i>
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

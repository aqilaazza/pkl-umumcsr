@php
    use Illuminate\Support\Facades\DB;

    $tgl_indo = function ($tanggal) {
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
                  'Juli','Agustus','September','Oktober','November','Desember'];
        $p = explode('-', $tanggal);
        return $p[2] . ' ' . $bulan[(int)$p[1]] . ' ' . $p[0];
    };
    $sisa_hari = function ($tgl_keluar) {
        return round((strtotime($tgl_keluar) - time()) / 86400);
    };

    $today = date('Y-m-d');

    $total_peserta    = (int) DB::table('peserta')->count();
    $peserta_aktif    = (int) DB::table('peserta')->where('status_magang', 'Aktif')->count();
    $peserta_menunggu = (int) DB::table('peserta')->where('status_magang', 'Menunggu')->count();
    $peserta_selesai  = (int) DB::table('peserta')->where('status_magang', 'Selesai')->count();
    $siswa_count      = (int) DB::table('peserta')->where('status_peserta', 'Siswa')->count();
    $mahasiswa_count  = (int) DB::table('peserta')->where('status_peserta', 'Mahasiswa')->count();
    $unit1_count      = (int) DB::table('peserta')->where('unit', 'Unit 1-2')->count();
    $unit9_count      = (int) DB::table('peserta')->where('unit', 'Unit 9')->count();

    $absensi_hari_ini = DB::table('absensi_peserta')
        ->where('tanggal', $today)
        ->selectRaw("SUM(CASE WHEN status='Hadir' AND jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir")
        ->selectRaw("SUM(CASE WHEN status='Izin' THEN 1 ELSE 0 END) as izin")
        ->selectRaw("SUM(CASE WHEN status='Sakit' THEN 1 ELSE 0 END) as sakit")
        ->selectRaw("SUM(CASE WHEN status='Alpha' THEN 1 ELSE 0 END) as alpha")
        ->selectRaw("COUNT(*) as total_absen")
        ->first();
    $absen_hadir = (int) ($absensi_hari_ini->hadir ?? 0);
    $absen_izin  = (int) ($absensi_hari_ini->izin ?? 0);
    $absen_sakit = (int) ($absensi_hari_ini->sakit ?? 0);
    $absen_total = (int) ($absensi_hari_ini->total_absen ?? 0);
    $belum_absen = max(0, $peserta_aktif - $absen_total);

    $pending_izin = (int) DB::table('absensi_peserta')
        ->whereIn('status', ['Izin', 'Sakit'])
        ->where('approval_status', 'Pending')
        ->count();

    $laporan_stat = [
        'total'            => (int) DB::table('laporan_magang')->count(),
        'menunggu'         => (int) DB::table('laporan_magang')->where('status', 'Menunggu')->count(),
        'menunggu_manager' => (int) DB::table('laporan_magang')->where('sdm_status', 'Disetujui')->where('manager_status', 'Menunggu')->count(),
        'disetujui'        => (int) DB::table('laporan_magang')->where('status', 'Disetujui')->count(),
        'ditolak'          => (int) DB::table('laporan_magang')->where('status', 'Ditolak')->count(),
    ];

    $sertif_belum   = (int) DB::table('sertifikat_magang')->where('status', 'Belum Upload')->count();
    $sertif_upload  = (int) DB::table('sertifikat_magang')->where('status', 'Sudah Upload')->count();
    $sertif_digital = (int) DB::table('sertifikat_magang')->where('status', 'Digital')->count();

    $q_akan_selesai = DB::table('peserta as p')
        ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
        ->where('p.status_magang', 'Aktif')
        ->whereNotNull('p.tgl_keluar')
        ->whereRaw("DATEDIFF(p.tgl_keluar, ?) BETWEEN 0 AND 30", [$today])
        ->orderBy('p.tgl_keluar')
        ->limit(6)
        ->get(['p.nama', 'p.username', 'p.asal_sekolah', 'p.tgl_keluar', 'b.bidang as bidang_peserta']);

    $bidang_data = DB::table('bidang as b')
        ->leftJoin('peserta as p', 'b.id', '=', 'p.bidang_id')
        ->groupBy('b.id', 'b.bidang')
        ->orderByRaw('COUNT(p.id) DESC')
        ->get([DB::raw('b.bidang as bidang'), DB::raw('COUNT(p.id) as jumlah')]);

    $instansi_raw = DB::table('peserta')
        ->selectRaw("TRIM(asal_sekolah) as instansi, status_peserta, status_magang, unit, COUNT(*) as jumlah")
        ->whereRaw("TRIM(asal_sekolah) <> ''")
        ->groupByRaw("TRIM(asal_sekolah), status_peserta, status_magang, unit")
        ->get();
@endphp

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark"><i class='bx bxs-dashboard me-2 text-primary'></i>Dashboard SDM</h4>
        <p class="text-muted mb-0 small">{{ $tgl_indo($today) }} &middot; Overview Sistem Magang</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="d-flex flex-wrap gap-2 justify-content-md-end">
            @if ($pending_izin > 0)
            <a href="{{ url('sdm/approval-absensi') }}" class="btn btn-sm btn-light border-0 shadow-sm px-3">
                <i class="bx bxs-calendar-check text-warning me-1"></i> {{ $pending_izin }} Izin
            </a>
            @endif
            @if ($laporan_stat['menunggu'] > 0)
            <a href="{{ url('sdm/approval-laporan') }}" class="btn btn-sm btn-light border-0 shadow-sm px-3">
                <i class="bx bxs-bell-ring text-primary me-1"></i> {{ $laporan_stat['menunggu'] }} Review
            </a>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-primary text-primary me-3">
                        <i class="bx bx-group fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Total Peserta</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0">{{ number_format($total_peserta,0,',','.') }}</h3>
                    <small class="text-muted">Siswa: {{ $siswa_count }} &middot; Mhs: {{ $mahasiswa_count }}</small>
                </div>
            </div>
            <div style="height:4px; background: #0d6efd; width: 100%;"></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-success text-success me-3">
                        <i class="bx bx-user-check fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Peserta Aktif</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0">{{ number_format($peserta_aktif,0,',','.') }}</h3>
                    <small class="text-muted">U1: {{ $unit1_count }} &middot; U9: {{ $unit9_count }}</small>
                </div>
            </div>
            <div style="height:4px; background: #198754; width: 100%;"></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-warning text-warning me-3">
                        <i class="bx bx-time-five fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Menunggu</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0">{{ number_format($peserta_menunggu,0,',','.') }}</h3>
                    <small class="text-muted">Konfirmasi Akun</small>
                </div>
            </div>
            <div style="height:4px; background: #ffc107; width: 100%;"></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-3 bg-light-info text-info me-3">
                        <i class="bx bx-check-double fs-4"></i>
                    </div>
                    <span class="text-muted small fw-semibold">Selesai Magang</span>
                </div>
                <div class="d-flex align-items-end justify-content-between">
                    <h3 class="fw-bold mb-0">{{ number_format($peserta_selesai,0,',','.') }}</h3>
                    <small class="text-muted">Total Alumni</small>
                </div>
            </div>
            <div style="height:4px; background: #0dcaf0; width: 100%;"></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bx-bar-chart-alt-2 me-2 text-primary"></i>Peserta per Bidang</h6>
                <div id="chart-bidang" style="min-height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <h6 class="fw-bold mb-4 text-start"><i class="bx bx-pie-chart-alt me-2 text-primary"></i>Status Magang</h6>
                <div id="chart-status" style="min-height: 250px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <h6 class="fw-bold mb-4 text-start"><i class="bx bx-doughnut-chart me-2 text-primary"></i>Absensi Hari Ini</h6>
                <div id="chart-absen" style="min-height: 250px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION: PESERTA PER INSTANSI -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0"><i class="bx bx-buildings me-2 text-primary"></i>Peserta per Instansi</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <input type="text" id="ins-q" class="form-control form-control-sm" style="width:170px" placeholder="Cari instansi...">
                        <select id="ins-status" class="form-select form-select-sm w-auto">
                            <option value="">Semua Status</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                        <select id="ins-jenis" class="form-select form-select-sm w-auto">
                            <option value="">Siswa &amp; Mhs</option>
                            <option value="Siswa">Siswa</option>
                            <option value="Mahasiswa">Mahasiswa</option>
                        </select>
                        <select id="ins-top" class="form-select form-select-sm w-auto">
                            <option value="10">Top 10</option>
                            <option value="20">Top 20</option>
                            <option value="0">Semua</option>
                        </select>
                    </div>
                </div>
                <div id="ins-empty" class="text-muted small py-5 text-center d-none">Tidak ada instansi yang cocok.</div>
                <div id="ins-scroll" style="max-height: 340px; overflow-y: auto; overflow-x: hidden;">
                    <div id="chart-instansi"></div>
                </div>
                <div class="text-muted mt-2" style="font-size: 11px;"><span id="ins-info"></span> &middot; Klik batang untuk melihat komposisinya.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 text-center">
                <h6 class="fw-bold mb-1 text-start"><i class="bx bx-doughnut-chart me-2 text-primary"></i>Status Peserta</h6>
                <div class="text-start mb-3 d-flex justify-content-between align-items-center" style="min-height: 22px;">
                    <span id="ins-sub" class="text-muted small text-truncate">Semua instansi</span>
                    <a href="#" id="ins-clear" class="small text-decoration-none d-none ms-2 flex-shrink-0">Reset</a>
                </div>
                <div id="chart-instansi-status" style="min-height: 250px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0"><i class="bx bx-calendar-event me-2 text-primary"></i>Absensi Hari Ini</h6>
                    <span class="badge bg-light text-dark fw-normal border">Total Magang Aktif: {{ $peserta_aktif }}</span>
                </div>
                <div class="row g-4 text-center">
                    <div class="col-3">
                        <div class="p-3 rounded-4 bg-light-success-subtle">
                            <h4 class="fw-bold text-success mb-1">{{ $absen_hadir }}</h4>
                            <div class="text-muted small">Hadir</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 rounded-4 bg-light-warning-subtle">
                            <h4 class="fw-bold text-warning mb-1">{{ $absen_izin }}</h4>
                            <div class="text-muted small">Izin</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 rounded-4 bg-light-danger-subtle">
                            <h4 class="fw-bold text-danger mb-1">{{ $absen_sakit }}</h4>
                            <div class="text-muted small">Sakit</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-3 rounded-4 bg-light-secondary-subtle">
                            <h4 class="fw-bold text-secondary mb-1">{{ $belum_absen }}</h4>
                            <div class="text-muted small">Belum</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-2">
                    @php $pct_absen = $peserta_aktif > 0 ? round(($absen_total / $peserta_aktif) * 100) : 0; @endphp
                    <div class="d-flex justify-content-between small mb-2 text-muted">
                        <span>Persentase Kehadiran</span>
                        <span class="fw-bold">{{ $pct_absen }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct_absen }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bx-task me-2 text-primary"></i>Quick Status</h6>
                <div class="d-grid gap-3">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-light border-0">
                        <div class="d-flex align-items-center">
                            <i class="bx bxs-file-find text-primary fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold mb-0">Laporan Baru</div>
                                <div class="text-muted small">Menunggu Review</div>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-0">{{ $laporan_stat['menunggu'] }}</h4>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-light border-0">
                        <div class="d-flex align-items-center">
                            <i class="bx bxs-award text-info fs-3 me-3"></i>
                            <div>
                                <div class="fw-bold mb-0">Sertifikat Digital</div>
                                <div class="text-muted small">Telah Terbit</div>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-0">{{ $sertif_digital }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-bar-chart-alt-2 me-2 text-primary"></i>Status Laporan</h6>
                <div class="d-grid gap-3">
                    @php
                    $total_display = $laporan_stat['total'] ?: 1;
                    $bars = [
                        ['label'=>'Review SDM',   'val'=>$laporan_stat['menunggu'], 'cls'=>'bg-warning'],
                        ['label'=>'Ke Manager',   'val'=>$laporan_stat['menunggu_manager'], 'cls'=>'bg-info'],
                        ['label'=>'Disetujui',    'val'=>$laporan_stat['disetujui'], 'cls'=>'bg-success'],
                        ['label'=>'Ditolak',      'val'=>$laporan_stat['ditolak'], 'cls'=>'bg-danger'],
                    ];
                    @endphp
                    @foreach ($bars as $b)
                        @php $pct = round($b['val'] / $total_display * 100); @endphp
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">{{ $b['label'] }}</span>
                            <span class="fw-bold">{{ $b['val'] }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar {{ $b['cls'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-2 text-center border-top pt-3">
                    <span class="text-muted small">Total: <strong>{{ $laporan_stat['total'] }}</strong> Laporan</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-pie-chart-alt-2 me-2 text-primary"></i>Distribusi Bidang</h6>
                @if ($bidang_data->isEmpty())
                    <div class="text-muted small py-4 text-center">Belum ada data bidang.</div>
                @else
                    @php $max_bidang = $bidang_data->max('jumlah') ?: 1; @endphp
                    @foreach ($bidang_data->take(5) as $row)
                        @php $pct = round(($row->jumlah / $max_bidang) * 100); @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted text-truncate me-2">{{ $row->bidang ?: 'Lainnya' }}</span>
                        <span class="fw-bold">{{ $row->jumlah }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary opacity-75" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-certification me-2 text-primary"></i>Sertifikat</h6>
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Belum Upload Scan</span>
                            <h5 class="fw-bold mb-0 text-dark">{{ $sertif_belum }}</h5>
                        </div>
                    </div>
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Sudah Upload Scan</span>
                            <h5 class="fw-bold mb-0 text-dark">{{ $sertif_upload }}</h5>
                        </div>
                    </div>
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Digital (QR Code)</span>
                            <h5 class="fw-bold mb-0 text-dark">{{ $sertif_digital }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-4"><i class="bx bx-calendar-exclamation me-2 text-danger"></i>Akan Selesai Magang (30 Hari Ke Depan)</h6>
        @if ($q_akan_selesai->isEmpty())
            <div class="text-muted small py-2 text-center">Tidak ada peserta yang akan selesai dalam 30 hari.</div>
        @else
            <div class="row g-2">
                @foreach ($q_akan_selesai as $row)
                    @php
                        $sisa = $sisa_hari($row->tgl_keluar);
                        $badge_cls = $sisa <= 7 ? 'bg-light-danger text-danger' : ($sisa <= 14 ? 'bg-light-warning text-warning' : 'bg-light-info text-info');
                    @endphp
                <div class="col-md-4">
                    <div class="d-flex align-items-center p-3 rounded-4 bg-light border-0 h-100">
                        <div class="p-2 rounded-circle {{ $badge_cls }} me-3">
                            <i class="bx bx-user fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold small mb-0">{{ $row->nama }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $sisa }} hari lagi &middot; {{ $row->bidang_peserta ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
.bg-light-primary { background-color: #e7f1ff; }
.bg-light-success { background-color: #e1f2e9; }
.bg-light-warning { background-color: #fff8e1; }
.bg-light-info    { background-color: #e0f7fa; }
.bg-light-danger  { background-color: #fce4ec; }

.bg-light-success-subtle { background-color: rgba(25, 135, 84, 0.08); }
.bg-light-warning-subtle { background-color: rgba(255, 193, 7, 0.08); }
.bg-light-danger-subtle  { background-color: rgba(220, 53, 69, 0.08); }
.bg-light-secondary-subtle { background-color: rgba(108, 117, 125, 0.08); }

.card { transition: transform 0.2s ease; }
.card:hover { transform: translateY(-3px); }
</style>

@section('scripts')
<script>
window.addEventListener('load', function() {
    var optionsBidang = {
        series: [{ name: 'Jumlah Peserta', data: [@foreach ($bidang_data as $b){{ $b->jumlah }},@endforeach] }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true } },
        dataLabels: { enabled: false },
        colors: ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#d63384', '#6610f2', '#6f42c1'],
        xaxis: { categories: [@foreach ($bidang_data as $b)'{{ addslashes($b->bidang) }}',@endforeach] },
        legend: { show: false }
    };
    new ApexCharts(document.querySelector("#chart-bidang"), optionsBidang).render();

    var optionsStatus = {
        series: [{{ $peserta_aktif }}, {{ $peserta_menunggu }}, {{ $peserta_selesai }}],
        chart: { type: 'pie', height: 250 },
        labels: ['Aktif', 'Menunggu', 'Selesai'],
        colors: ['#198754', '#ffc107', '#0dcaf0'],
        legend: { position: 'bottom' },
        responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: 'bottom' } } }]
    };
    new ApexCharts(document.querySelector("#chart-status"), optionsStatus).render();

    var optionsAbsen = {
        series: [{{ $absen_hadir }}, {{ $absen_izin }}, {{ $absen_sakit }}, {{ $belum_absen }}],
        chart: { type: 'donut', height: 250 },
        labels: ['Hadir', 'Izin', 'Sakit', 'Belum'],
        colors: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: function (w) { return {{ $peserta_aktif }} } } } } } }
    };
    new ApexCharts(document.querySelector("#chart-absen"), optionsAbsen).render();

    (function () {
        var RAW = @json($instansi_raw).map(function (r) {
            return { n: r.instansi, j: r.status_peserta, s: r.status_magang, c: Number(r.jumlah) };
        });
        var $ = function (id) { return document.getElementById(id); };
        if (!$('chart-instansi')) return;

        var PALETTE = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#d63384', '#6610f2', '#6f42c1'];
        var STATUS = ['Aktif', 'Menunggu', 'Selesai'];
        var shown = [], selected = null, barChart = null, donut = null;
        var cut = function (s, n) { return s.length > n ? s.slice(0, n - 1) + '…' : s; };
        var fade = function (hex) {
            var n = parseInt(hex.slice(1), 16);
            return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',0.25)';
        };

        function listInstansi() {
            var q = $('ins-q').value.trim().toLowerCase(), st = $('ins-status').value, jn = $('ins-jenis').value, map = {};
            RAW.forEach(function (r) {
                if (st && r.s !== st) return;
                if (jn && r.j !== jn) return;
                if (q && r.n.toLowerCase().indexOf(q) === -1) return;
                map[r.n] = (map[r.n] || 0) + r.c;
            });
            return Object.keys(map).map(function (k) { return { n: k, total: map[k] }; })
                .sort(function (a, b) { return b.total - a.total || a.n.localeCompare(b.n); });
        }

        function statusTotals() {
            var q = $('ins-q').value.trim().toLowerCase(), jn = $('ins-jenis').value, t = { Aktif: 0, Menunggu: 0, Selesai: 0 };
            RAW.forEach(function (r) {
                if (jn && r.j !== jn) return;
                if (selected ? r.n !== selected : (q && r.n.toLowerCase().indexOf(q) === -1)) return;
                t[r.s] += r.c;
            });
            return STATUS.map(function (k) { return t[k]; });
        }

        function drawDonut() {
            var s = statusTotals(), total = s.reduce(function (a, b) { return a + b; }, 0);
            $('ins-sub').textContent = selected || 'Semua instansi';
            $('ins-sub').title = selected || '';
            $('ins-clear').classList.toggle('d-none', !selected);
            if (donut) { donut.updateSeries(total ? s : [0, 0, 0]); return; }
            donut = new ApexCharts($('chart-instansi-status'), {
                series: s,
                chart: { type: 'donut', height: 250 },
                labels: STATUS,
                colors: ['#198754', '#ffc107', '#0dcaf0'],
                legend: { position: 'bottom' },
                noData: { text: 'Tidak ada data' },
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } } } } } }
            });
            donut.render();
        }

        function drawBars() {
            var colors = [function (o) {
                var base = PALETTE[o.dataPointIndex % PALETTE.length];
                return selected && shown[o.dataPointIndex] && shown[o.dataPointIndex].n !== selected ? fade(base) : base;
            }];
            var opts = {
                series: [{ name: 'Jumlah Peserta', data: shown.map(function (o) { return o.total; }) }],
                xaxis: { categories: shown.map(function (o) { return o.n; }), labels: { formatter: function (v) { return Math.round(v); } } },
                chart: { height: Math.max(220, shown.length * 30 + 50) },
                colors: colors
            };
            if (barChart) { barChart.updateOptions(opts, false, false); return; }
            barChart = new ApexCharts($('chart-instansi'), {
                series: opts.series,
                chart: {
                    type: 'bar', height: opts.chart.height, toolbar: { show: false },
                    events: {
                        dataPointSelection: function (e, ctx, cfg) {
                            var o = shown[cfg.dataPointIndex]; if (!o) return;
                            selected = selected === o.n ? null : o.n;
                            drawBars(); drawDonut();
                        }
                    }
                },
                plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true } },
                dataLabels: { enabled: false },
                colors: colors,
                xaxis: opts.xaxis,
                yaxis: { labels: { maxWidth: 260, formatter: function (v) { return typeof v === 'string' ? cut(v, 26) : v; } } },
                tooltip: { y: { formatter: function (v) { return v + ' peserta'; } } },
                states: { active: { filter: { type: 'none' } } },
                legend: { show: false }
            });
            barChart.render();
        }

        function render() {
            var list = listInstansi(), top = parseInt($('ins-top').value, 10);
            shown = top > 0 ? list.slice(0, top) : list;
            if (selected && !shown.some(function (o) { return o.n === selected; })) selected = null;
            var total = list.reduce(function (a, o) { return a + o.total; }, 0);
            $('ins-info').textContent = list.length + ' instansi · ' + total.toLocaleString('id-ID') + ' peserta';
            $('ins-empty').classList.toggle('d-none', shown.length > 0);
            $('ins-scroll').classList.toggle('d-none', shown.length === 0);
            if (shown.length) drawBars();
            drawDonut();
        }

        var timer;
        $('ins-q').addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(render, 180); });
        ['ins-status', 'ins-jenis', 'ins-top'].forEach(function (id) { $(id).addEventListener('change', render); });
        $('ins-clear').addEventListener('click', function (e) { e.preventDefault(); selected = null; drawBars(); drawDonut(); });
        render();
    })();
});
</script>
@endsection
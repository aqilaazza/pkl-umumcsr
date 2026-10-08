@php
    use Illuminate\Support\Facades\DB;

    $tgl_indo = function ($t) {
        $b = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $p = explode('-', $t);
        return $p[2] . ' ' . $b[(int)$p[1]] . ' ' . $p[0];
    };

    $today = date('Y-m-d');

    $laporan_stat = DB::table('laporan_magang')
        ->selectRaw("COUNT(*) as total")
        ->selectRaw("SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu")
        ->selectRaw("SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui")
        ->selectRaw("SUM(manager_status='Ditolak' OR (sdm_status='Ditolak' AND manager_status='Menunggu')) as ditolak")
        ->selectRaw("SUM(sdm_status='Menunggu') as menunggu_sdm")
        ->first();

    $total_peserta    = (int) DB::table('peserta')->count();
    $peserta_aktif    = (int) DB::table('peserta')->where('status_magang', 'Aktif')->count();
    $peserta_menunggu = (int) DB::table('peserta')->where('status_magang', 'Menunggu')->count();
    $peserta_selesai  = (int) DB::table('peserta')->where('status_magang', 'Selesai')->count();
    $unit1_count      = (int) DB::table('peserta')->where('unit', 'Unit 1-2')->count();
    $unit9_count      = (int) DB::table('peserta')->where('unit', 'Unit 9')->count();

    $sertif_digital = (int) DB::table('sertifikat_magang')->where('status', 'Digital')->count();

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

    $bidang_data = DB::table('bidang as b')
        ->leftJoin('peserta as p', 'b.id', '=', 'p.bidang_id')
        ->groupBy('b.id', 'b.bidang')
        ->orderByRaw('COUNT(p.id) DESC')
        ->get([DB::raw('b.bidang as bidang'), DB::raw('COUNT(p.id) as jumlah')]);

    $menunggu  = (int) ($laporan_stat->menunggu ?? 0);
    $disetujui = (int) ($laporan_stat->disetujui ?? 0);
    $ditolak   = (int) ($laporan_stat->ditolak ?? 0);
    $total     = (int) ($laporan_stat->total ?? 0);
@endphp

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h4 class="fw-bold mb-0 text-dark"><i class='bx bxs-dashboard me-2 text-primary'></i>Dashboard Manager</h4>
        <p class="text-muted mb-0 small">{{ $tgl_indo($today) }} &middot; Overview Persetujuan Laporan</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="d-flex flex-wrap gap-2 justify-content-md-end">
            @if ($menunggu > 0)
            <a href="{{ url('manager/approval-laporan') }}" class="btn btn-sm btn-light border-0 shadow-sm px-3">
                <i class="bx bxs-bell-ring text-warning me-1"></i> {{ $menunggu }} Perlu Approval
            </a>
            @endif
        </div>
    </div>
</div>

<!-- SECTION 1: STATISTIK UTAMA -->
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
                </div>
            </div>
            <div style="height:4px; background: #0dcaf0; width: 100%;"></div>
        </div>
    </div>
</div>

<!-- SECTION: ANALYTICS VISUALIZATION -->
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
                <h6 class="fw-bold mb-4 text-start"><i class="bx bx-pie-chart-alt me-2 text-primary"></i>Status Laporan</h6>
                <div id="chart-laporan" style="min-height: 250px;"></div>
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

<!-- SECTION 2: STATUS APPROVAL -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0"><i class="bx bx-task me-2 text-primary"></i>Statistik Persetujuan Laporan</h6>
                    <span class="badge bg-light text-dark fw-normal border">Total Laporan: {{ $total }}</span>
                </div>
                <div class="row g-4 text-center">
                    <div class="col-4">
                        <div class="p-3 rounded-4 bg-light-warning-subtle">
                            <h4 class="fw-bold text-warning mb-1">{{ $menunggu }}</h4>
                            <div class="text-muted small">Menunggu Review</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-4 bg-light-success-subtle">
                            <h4 class="fw-bold text-success mb-1">{{ $disetujui }}</h4>
                            <div class="text-muted small">Telah Disetujui</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-4 bg-light-danger-subtle">
                            <h4 class="fw-bold text-danger mb-1">{{ $ditolak }}</h4>
                            <div class="text-muted small">Telah Ditolak</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-2">
                    @php
                    $div_rep = max(1, $total);
                    $pct_selesai = round((($disetujui + $ditolak) / $div_rep) * 100);
                    @endphp
                    <div class="d-flex justify-content-between small mb-2 text-muted">
                        <span>Penyelesaian Review Laporan</span>
                        <span class="fw-bold">{{ $pct_selesai }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct_selesai }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-award me-2 text-primary"></i>Sertifikasi</h6>
                <div class="d-grid gap-3">
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="d-flex align-items-center">
                            <i class="bx bxs-certification text-info fs-1 me-3"></i>
                            <div>
                                <h3 class="fw-bold mb-0">{{ $sertif_digital }}</h3>
                                <div class="text-muted small">Sertifikat Digital Terbit</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 rounded-4 bg-light border-0">
                        <div class="small text-muted mb-2">Alur Saat Ini:</div>
                        <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size: 11px;">
                            <span class="badge bg-white text-dark border fw-normal">SDM Review</span>
                            <i class='bx bx-right-arrow-alt text-muted'></i>
                            <span class="badge bg-primary text-white border-0 fw-normal">Manager</span>
                            <i class='bx bx-right-arrow-alt text-muted'></i>
                            <span class="badge bg-white text-dark border fw-normal">Digital</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: ANALYTICS -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-4"><i class="bx bxs-bar-chart-alt-2 me-2 text-primary"></i>Persentase Laporan</h6>
                <div class="d-grid gap-3">
                    @php
                    $div = max(1, $total);
                    $bars = [
                        ['label'=>'Menunggu Approval', 'val'=>$menunggu, 'cls'=>'bg-warning'],
                        ['label'=>'Telah Disetujui',    'val'=>$disetujui, 'cls'=>'bg-success'],
                        ['label'=>'Telah Ditolak',      'val'=>$ditolak, 'cls'=>'bg-danger'],
                    ];
                    @endphp
                    @foreach ($bars as $b)
                        @php $pct = round($b['val'] / $div * 100); @endphp
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
            </div>
        </div>
    </div>
    <div class="col-md-6">
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

    var optionsLaporan = {
        series: [{{ $disetujui }}, {{ $menunggu }}, {{ $ditolak }}],
        chart: { type: 'pie', height: 250 },
        labels: ['Disetujui', 'Menunggu', 'Ditolak'],
        colors: ['#198754', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#chart-laporan"), optionsLaporan).render();

    var optionsAbsen = {
        series: [{{ $absen_hadir }}, {{ $absen_izin }}, {{ $absen_sakit }}, {{ $belum_absen }}],
        chart: { type: 'donut', height: 250 },
        labels: ['Hadir', 'Izin', 'Sakit', 'Belum'],
        colors: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: function (w) { return {{ $peserta_aktif }} } } } } } }
    };
    new ApexCharts(document.querySelector("#chart-absen"), optionsAbsen).render();
});
</script>
@endsection
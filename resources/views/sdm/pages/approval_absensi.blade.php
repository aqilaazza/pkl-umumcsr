@php
    use Illuminate\Support\Facades\DB;

    // Query Ringkasan
    $sum_row = DB::table('absensi_peserta')
        ->whereIn('status', ['Izin', 'Sakit'])
        ->selectRaw('COUNT(*) as total')
        ->selectRaw("SUM(approval_status='Pending') as pending")
        ->selectRaw("SUM(approval_status='Disetujui') as disetujui")
        ->selectRaw("SUM(approval_status='Ditolak') as ditolak")
        ->first();

    $sum_absensi = [
        'total'     => (int) ($sum_row->total ?? 0),
        'pending'   => (int) ($sum_row->pending ?? 0),
        'disetujui' => (int) ($sum_row->disetujui ?? 0),
        'ditolak'   => (int) ($sum_row->ditolak ?? 0),
    ];

    // Query Data Pending untuk Tabel
    $data_pending = DB::table('absensi_peserta as ap')
        ->join('peserta as p', 'ap.username', '=', 'p.username')
        ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
        ->whereIn('ap.status', ['Izin', 'Sakit'])
        ->where('ap.approval_status', 'Pending')
        ->orderBy('ap.tanggal', 'desc')
        ->get([
            'ap.*', 'p.nama', 'p.asal_sekolah', 'p.unit',
            DB::raw('b.bidang as nama_bidang'),
        ]);

    $count_pending = $sum_absensi['pending'];
    $bulan_names = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3"><span class="sdm-title-ico"><i class="bx bx-calendar-edit"></i></span>Approval Izin &amp; Sakit</div>
</div>

<!-- SUMMARY CARDS -->
@php $scTotal = (int) ($sum_absensi['total'] ?? 0); @endphp
<style>
    /* KPI card - desain sama dengan menu Rekap Absensi (override tampilan partials.stat_card_v2) */
    .kpi-rk {
        --rk-ink: #0f172a;
        --rk-panel: #ffffff;
        --rk-line: #e2e8f0;
        --rk-text: #1e293b;
        --rk-muted: #64748b;
        --rk-accent: #0891b2;
        --rk-hadir: #059669;
        --rk-izin: #2563eb;
        --rk-sakit: #e11d48;
        --rk-alpha: #64748b;
        --rk-warn: #d97706;
        display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; margin-bottom: 1.5rem;
    }
    .kpi-rk--c3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .kpi-rk--c6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .kpi-rk__item { min-width: 0; display: flex; }
    .kpi-rk .ui-stat-card { --tone: var(--rk-izin); position: relative; overflow: hidden; width: 100%; min-height: 128px; padding: 1rem 1.1rem; border-radius: 12px; border: 1px solid var(--rk-line); background: var(--rk-panel); box-shadow: 0 1px 2px rgba(15, 23, 42, .04); display: flex; flex-direction: column; color: var(--rk-text); }
    .kpi-rk .ui-stat-card::before { content: ''; position: absolute; top: -50px; right: -50px; width: 120px; height: 120px; border-radius: 50%; background: var(--tone); opacity: .08; }
    .kpi-rk .ui-stat-card[data-stat-color="primary"]   { --tone: var(--rk-accent); }
    .kpi-rk .ui-stat-card[data-stat-color="success"]   { --tone: var(--rk-hadir); }
    .kpi-rk .ui-stat-card[data-stat-color="info"]      { --tone: var(--rk-izin); }
    .kpi-rk .ui-stat-card[data-stat-color="warning"]   { --tone: var(--rk-warn); }
    .kpi-rk .ui-stat-card[data-stat-color="danger"]    { --tone: var(--rk-sakit); }
    .kpi-rk .ui-stat-card[data-stat-color="secondary"],
    .kpi-rk .ui-stat-card[data-stat-color="dark"]      { --tone: var(--rk-alpha); }
    .kpi-rk .ui-stat-card__head { position: absolute; top: 1rem; right: 1.1rem; margin: 0; padding: 0; }
    .kpi-rk .ui-stat-card__badge { display: inline-flex; width: 30px; height: 30px; align-items: center; justify-content: center; border-radius: 8px; background: rgba(15, 23, 42, .05); color: var(--tone); font-size: 16px; }
    .kpi-rk .ui-stat-card__body { position: relative; padding: 0; flex: 1; display: flex; }
    .kpi-rk .ui-stat-card__meta { display: flex; flex-direction: column; width: 100%; padding-right: 2rem; }
    .kpi-rk .ui-stat-card__label { order: -1; margin-bottom: .5rem; font-family: var(--pkl-font-body); font-size: 12px; font-weight: 700; letter-spacing: 0; text-transform: none; color: var(--tone); }
    .kpi-rk .ui-stat-card__value { margin: 0 0 .5rem; font-family: var(--pkl-font-body); font-size: 24px; font-weight: 800; line-height: 1.1; color: var(--rk-ink); font-variant-numeric: tabular-nums; }
    .kpi-rk .ui-stat-card[data-stat-color="success"] .ui-stat-card__value { color: var(--rk-hadir); }
    .kpi-rk .ui-stat-card__sub { margin-top: auto; font-family: var(--pkl-font-body); font-size: 12.5px; font-weight: 400; line-height: 1.45; color: var(--rk-muted); white-space: normal; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

    @media (max-width: 1199px) {
        .kpi-rk, .kpi-rk--c6 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .kpi-rk:not(.kpi-rk--c3):not(.kpi-rk--c6) { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575px) {
        .kpi-rk, .kpi-rk--c6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .kpi-rk--c3 { grid-template-columns: 1fr; }
    }
</style>
<div class="kpi-rk">
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'total', 'value' => number_format($scTotal, 0, ',', '.'), 'label' => 'Total Pengajuan', 'icon' => 'bx bx-list-ul', 'color' => 'primary', 'sub' => 'Izin & sakit masuk'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'pending', 'value' => number_format((int) ($sum_absensi['pending'] ?? 0), 0, ',', '.'), 'label' => 'Menunggu Review', 'icon' => 'bx bx-time-five', 'color' => 'warning', 'sub' => 'Perlu ditinjau SDM'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'disetujui', 'value' => number_format((int) ($sum_absensi['disetujui'] ?? 0), 0, ',', '.'), 'label' => 'Disetujui', 'icon' => 'bx bx-check-circle', 'color' => 'success', 'sub' => 'Sudah disetujui'])
    @include('partials.stat_card_v2', ['colClass' => 'kpi-rk__item', 'counterKey' => 'ditolak', 'value' => number_format((int) ($sum_absensi['ditolak'] ?? 0), 0, ',', '.'), 'label' => 'Ditolak', 'icon' => 'bx bx-x-circle', 'color' => 'danger', 'sub' => 'Sudah ditolak'])
</div>

<!-- List Table -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="bx bx-check-shield me-1"></i> Approval Izin & Sakit</h5>
            @if ($count_pending > 0)
                <form method="POST" action="{{ url('sdm/approval-absensi/setujui-semua') }}"
                      class="js-approval-form"
                      data-action="approve-all"
                      data-title="Pengajuan Disetujui!"
                      data-confirm-title="Setujui Semua?"
                      data-confirm-message="Setujui {{ $count_pending }} permintaan izin/sakit sekaligus?"
                      data-confirm-ok="Setujui">
                    @csrf
                    <button type="submit" name="approve_all" id="btn-approve-all" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                        <i class="bx bx-check-double fs-6"></i>
                        <span>Setujui Semua (<span id="text-pending-count">{{ $count_pending }}</span>)</span>
                    </button>
                </form>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0 ui-table" id="tabelApprovalIzin">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 180px;">Peserta</th>
                        <th>Asal Sekolah</th>
                        <th style="width: 105px;" class="text-center">Tanggal</th>
                        <th style="width: 80px;" class="text-center">Jenis</th>
                        <th style="width: 250px;">Keterangan</th>
                        <th style="width: 100px;" class="text-center">Bukti</th>
                        <th style="width: 180px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody data-empty-colspan="8" data-empty-text="Tidak ada pengajuan izin/sakit yang perlu ditinjau.">
                @php $no = 1; @endphp
                @foreach ($data_pending as $r)
                    @php
                        $tgl_parts = explode('-', $r->tanggal);
                        $tgl_formatted = $tgl_parts[2] . ' ' . ($bulan_names[(int) $tgl_parts[1]] ?? '') . ' ' . $tgl_parts[0];

                        $badge = $r->status === 'Izin' ? 'warning' : 'danger';
                    @endphp
                <tr data-lg-row data-id="{{ $r->id }}">
                    <td class="text-center">{{ $no++ }}</td>
                    <td class="text-nowrap">
                        <strong>{{ $r->nama }}</strong>
                        <small class="text-muted d-block"><code>{{ '@' . $r->username }}</code></small>
                    </td>
                    <td>
                        <small class="d-block text-truncate" style="max-width: 220px;" title="{{ $r->asal_sekolah }}">{{ $r->asal_sekolah }}</small>
                        <small class="text-muted">{{ $r->unit ?? '-' }} · {{ $r->nama_bidang ?? '-' }}</small>
                    </td>
                    <td class="text-center fw-bold text-nowrap">{{ $tgl_formatted }}</td>
                    <td class="text-center text-nowrap">
                        <span class="badge bg-{{ $badge }}">{{ $r->status }}</span>
                    </td>
                    <td style="max-width: 250px; word-wrap: break-word; white-space: normal;">
                        {{ $r->keterangan ?? '-' }}
                    </td>
                    <td class="text-center" style="width: 100px;">
                        @if ($r->file_surat)
                            <a href="{{ asset('uploads/surat_absensi/' . $r->file_surat) }}" target="_blank"
                               class="btn btn-sm btn-info text-white d-inline-flex align-items-center justify-content-center gap-1 text-nowrap"
                               title="Lihat Surat" data-ui-preview data-preview-title="Surat {{ $r->nama }}">
                                <i class="bx bx-file fs-6"></i> <span>Lihat</span>
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center" style="width: 180px;">
                        <div class="d-inline-flex align-items-center justify-content-center gap-2 flex-nowrap">
                        <form method="POST" action="{{ url('sdm/approval-absensi/setujui') }}" class="d-inline js-approval-form"
                              data-action="approve" data-nama="{{ $r->nama }}" data-title="Pengajuan Disetujui!"
                              data-counters='{"pending":-1,"disetujui":1}'>
                            @csrf
                            <input type="hidden" name="absen_id" value="{{ $r->id }}">
                            <button type="submit" name="action_approve" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 text-nowrap" title="Setujui">
                                <i class="bx bx-check fs-6"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ url('sdm/approval-absensi/tolak') }}" class="d-inline js-approval-form"
                              data-action="reject-direct" data-nama="{{ $r->nama }}" data-title="Pengajuan Ditolak!"
                              data-counters='{"pending":-1,"ditolak":1}'>
                            @csrf
                            <input type="hidden" name="absen_id" value="{{ $r->id }}">
                            <button type="submit" name="action_reject" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 text-nowrap" title="Tolak">
                                <i class="bx bx-x fs-6"></i> Tolak
                            </button>
                        </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                @if ($no === 1)
                <tr class="pkl-empty">
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bx bx-check-double fs-3 d-block mb-1 text-success"></i>
                        Tidak ada pengajuan izin/sakit yang perlu ditinjau.
                    </td>
                </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    body .page-content #tabelApprovalIzin td .btn-sm,
    body .page-content #tabelApprovalIzin td .btn {
        padding: .3rem .55rem;
        font-size: 11px;
        gap: .25rem !important;
    }
    body .page-content #tabelApprovalIzin td .btn i { font-size: 14px; }
    body .page-content #tabelApprovalIzin .badge { font-size: 10px; padding: .32em .6em; }

    /* Pengajuan yang dipilih dari tombol "Tinjau" di dashboard */
    body .page-content #tabelApprovalIzin tbody tr.pkl-fokus > td { background-color: #fff4cc !important; }
    body .page-content #tabelApprovalIzin tbody tr.pkl-fokus > td:first-child { box-shadow: inset 4px 0 0 #ffc400; }
</style>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var table = $('#tabelApprovalIzin').DataTable({
        paging: true,
        pageLength: 15,
        language: { search: "Cari:", lengthMenu: "Tampilkan _MENU_ data" }
    });

    // Dari tombol "Tinjau" di dashboard: ?fokus=<id>,<id> -> pindah ke halaman tabel yang benar,
    // sorot barisnya, lalu gulir ke sana.
    var param = new URLSearchParams(window.location.search).get('fokus');
    if (!param) return;

    var ids  = param.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
    var target = table.rows(function (idx, data, node) {
        return ids.indexOf(node.getAttribute('data-id')) !== -1;
    });
    var idxs = target.indexes().toArray();

    if (!idxs.length) return;

    // pindah ke halaman tabel yang memuat baris pertama
    var urutan = table.rows({ order: 'current', search: 'applied' }).indexes().toArray();
    var pos = urutan.indexOf(idxs[0]);
    table.page(Math.floor(pos / table.page.len())).draw('page');

    $(target.nodes()).addClass('pkl-fokus');

    setTimeout(function () {
        var el = target.nodes()[0];
        if (el && el.scrollIntoView) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 250);
});
</script>
@endsection
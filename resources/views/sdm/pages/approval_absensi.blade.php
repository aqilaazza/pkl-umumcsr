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

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Absensi</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Izin & Sakit</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary" data-counter="total">{{ number_format($sum_absensi['total'], 0, ',', '.') }}</div>
                <div class="small text-muted">Total Pengajuan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-warning border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-warning" data-counter="pending">{{ number_format($sum_absensi['pending'], 0, ',', '.') }}</div>
                <div class="small text-muted">Menunggu Review</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-success border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success" data-counter="disetujui">{{ number_format($sum_absensi['disetujui'], 0, ',', '.') }}</div>
                <div class="small text-muted">Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger" data-counter="ditolak">{{ number_format($sum_absensi['ditolak'], 0, ',', '.') }}</div>
                <div class="small text-muted">Ditolak</div>
            </div>
        </div>
    </div>
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
                    <button type="submit" name="approve_all" class="btn btn-sm btn-success">
                        <i class="bx bx-check-double me-1"></i> Setujui Semua ({{ $count_pending }})
                    </button>
                </form>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0" id="tabelApprovalIzin">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="width: 180px;">Peserta</th>
                        <th>Asal Sekolah</th>
                        <th style="width: 105px;" class="text-center">Tanggal</th>
                        <th style="width: 80px;" class="text-center">Jenis</th>
                        <th style="width: 260px;">Keterangan</th>
                        <th style="width: 75px;" class="text-center">Bukti</th>
                        <th style="width: 175px;" class="text-center">Aksi</th>
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
                <tr data-lg-row>
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
                    <td class="align-top">
                        <div style="max-width: 260px; white-space: normal; overflow-wrap: anywhere; word-break: break-word;">{{ $r->keterangan ?? '-' }}</div>
                    </td>
                    <td class="text-center text-nowrap">
                        @if ($r->file_surat)
                            <a href="{{ asset('uploads/surat_absensi/' . $r->file_surat) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat Surat"><i class="bx bx-file"></i></a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center text-nowrap">
                        <form method="POST" action="{{ url('sdm/approval-absensi/setujui') }}" class="d-inline js-approval-form"
                              data-action="approve" data-nama="{{ $r->nama }}" data-title="Pengajuan Disetujui!"
                              data-counters='{"pending":-1,"disetujui":1}'>
                            @csrf
                            <input type="hidden" name="absen_id" value="{{ $r->id }}">
                            <button type="submit" name="action_approve" class="btn btn-sm btn-success me-1" title="Setujui">
                                <i class="bx bx-check"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ url('sdm/approval-absensi/tolak') }}" class="d-inline js-approval-form"
                              data-action="reject-direct" data-nama="{{ $r->nama }}" data-title="Pengajuan Ditolak!"
                              data-counters='{"pending":-1,"ditolak":1}'>
                            @csrf
                            <input type="hidden" name="absen_id" value="{{ $r->id }}">
                            <button type="submit" name="action_reject" class="btn btn-sm btn-danger" title="Tolak">
                                <i class="bx bx-x"></i> Tolak
                            </button>
                        </form>
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#tabelApprovalIzin').DataTable({
        paging: true,
        pageLength: 15,
        language: { search: "Cari:", lengthMenu: "Tampilkan _MENU_ data" }
    });
});
</script>
@endsection

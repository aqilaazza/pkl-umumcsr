@php
    use Illuminate\Support\Facades\DB;

    // --- FILTER & PAGINATION ---
    $search   = trim((string) request('q'));
    $filter   = (string) request('filter', 'semua');
    $per_page = 15;
    $page     = max(1, (int) request('p', 1));
    $offset   = ($page - 1) * $per_page;

    $baseWhere = function ($q) {
        $q->where('lm.status', '=', 'Disetujui')
            ->orWhere(function ($q2) {
                $q2->where('lm.sdm_status', '=', 'Disetujui')
                    ->whereIn('lm.manager_status', ['Menunggu', 'Disetujui']);
            });
    };

    $applyFilters = function ($query) use ($baseWhere, $search, $filter) {
        $query->where($baseWhere);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'like', '%' . $search . '%')
                    ->orWhere('lm.username', 'like', '%' . $search . '%')
                    ->orWhere('p.asal_sekolah', 'like', '%' . $search . '%');
            });
        }

        if ($filter === 'belum') {
            $query->where(function ($q) {
                $q->whereNull('sm.id')->orWhere('sm.status', '=', 'Belum Upload');
            });
        }

        if ($filter === 'sudah') {
            $query->where('sm.status', '=', 'Sudah Upload');
        }

        return $query;
    };

    $countQuery = $applyFilters(
        DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('sertifikat_magang as sm', 'lm.username', '=', 'sm.username')
    );

    $total      = (int) $countQuery->count();
    $total_page = ceil($total / $per_page);

    $data = $applyFilters(
        DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->leftJoin('sertifikat_magang as sm', 'lm.username', '=', 'sm.username')
    )
        ->orderByDesc('lm.sdm_tgl_review')
        ->limit($per_page)
        ->offset($offset)
        ->get([
            'lm.id as laporan_id',
            'lm.username',
            'lm.sdm_tgl_review',
            'lm.manager_status',
            'lm.status as laporan_status',
            'p.nama',
            'p.asal_sekolah',
            'p.jurusan',
            'p.status_peserta',
            'p.tgl_masuk',
            'p.tgl_keluar',
            'p.unit',
            'b.bidang as nama_bidang',
            'sm.id as sertif_id',
            'sm.nomor_surat',
            'sm.status as status_sertif',
            'sm.file_sertifikat',
            'sm.nama_file_scan',
            'sm.ukuran_file_scan',
            'sm.tgl_cetak',
            'sm.tgl_upload_scan',
            'sm.digital_file',
            'sm.generated_at',
            DB::raw("(SELECT COUNT(*) FROM absensi_peserta ap
                      WHERE ap.username = lm.username
                        AND ap.status = 'Hadir'
                        AND ap.tanggal BETWEEN p.tgl_masuk AND p.tgl_keluar) as total_hadir"),
        ]);

    // Ringkasan
    $sum = DB::table('laporan_magang as lm')
        ->leftJoin('sertifikat_magang as sm', 'lm.username', '=', 'sm.username')
        ->where($baseWhere)
        ->selectRaw("COUNT(DISTINCT lm.username) as total_disetujui")
        ->selectRaw("SUM(CASE WHEN sm.status='Sudah Upload' OR sm.status='Digital' THEN 1 ELSE 0 END) as sudah_upload")
        ->selectRaw("SUM(CASE WHEN sm.id IS NULL OR sm.status='Belum Upload' THEN 1 ELSE 0 END) as belum_upload")
        ->first();

    // ── Data libur untuk hitung hari kerja ──
    $libur_pekan = DB::table('libur_pekan')->pluck('hari_index')->map(fn ($v) => (int) $v)->all();
    $hari_libur  = DB::table('hari_libur')->pluck('tanggal')->all();

    /**
     * Hitung jumlah hari kerja (exclude libur pekan & tanggal merah)
     */
    $hitungHariKerja = function ($tgl_masuk, $tgl_keluar, $libur_pekan, $hari_libur) {
        if (empty($tgl_masuk) || empty($tgl_keluar) || $tgl_masuk == '0000-00-00' || $tgl_keluar == '0000-00-00') {
            return 0;
        }
        $start = new DateTime($tgl_masuk);
        $end   = new DateTime($tgl_keluar);
        $end->modify('+1 day');
        $total = 0;
        $interval = new DateInterval('P1D');
        $period   = new DatePeriod($start, $interval, $end);
        foreach ($period as $date) {
            $hari_idx = (int) $date->format('w');
            if (in_array($hari_idx, $libur_pekan)) continue;
            if (in_array($date->format('Y-m-d'), $hari_libur)) continue;
            $total++;
        }
        return $total;
    };

    $no = $offset + 1;
@endphp

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Sertifikat</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('sdm') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Cetak & Kelola Sertifikat</li>
            </ol>
        </nav>
    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-primary">{{ $sum->total_disetujui }}</div>
                <div class="small text-muted">Laporan Disetujui</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center border-0 shadow-sm border border-danger border-2">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-danger">{{ $sum->belum_upload }}</div>
                <div class="small text-muted">Belum Upload Scan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="fs-2 fw-bold text-success">{{ $sum->sudah_upload }}</div>
                <div class="small text-muted">Sudah Upload Scan</div>
            </div>
        </div>
    </div>
</div>

<!-- ALUR INFO - TIDAK DITAMPILKAN (DIHAPUS SESUAI PERMINTAAN) -->
<!-- Bagian alur info telah dihapus -->

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="card-title mb-0"><i class="bx bxs-award me-1"></i> Daftar Peserta – Laporan Disetujui</h5>
        </div>

        <!-- SEARCH & FILTER -->
        <form method="GET" action="{{ url('sdm/cetak-sertifikat') }}" class="row g-2 mb-3">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="q" class="form-control"
                           placeholder="Cari nama / username / asal sekolah..."
                           value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <select name="filter" class="form-select form-select-sm">
                    <option value="semua" {{ $filter==='semua' ? 'selected' : '' }}>Semua</option>
                    <option value="belum" {{ $filter==='belum' ? 'selected' : '' }}>Belum Upload Scan</option>
                    <option value="sudah" {{ $filter==='sudah' ? 'selected' : '' }}>Sudah Upload Scan</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Cari</button>
                <a href="{{ url('sdm/cetak-sertifikat') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bx bx-reset"></i></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Bidang / Unit</th>
                        <th>Periode Magang</th>
                        <th>Kehadiran</th>
                        <th>Status Sertifikat</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($data as $row)
                    @php
                        $has_sertif    = !empty($row->sertif_id);
                        $sudah_upload  = $has_sertif && ($row->status_sertif === 'Sudah Upload' || $row->status_sertif === 'Digital');
                        $is_digital    = $has_sertif && $row->status_sertif === 'Digital';
                        $size_label    = '';
                        if ($sudah_upload && $row->ukuran_file_scan) {
                            $size_label = $row->ukuran_file_scan > 1024*1024
                                ? round($row->ukuran_file_scan/1024/1024, 2) . ' MB'
                                : round($row->ukuran_file_scan/1024, 1) . ' KB';
                        }
                        $nama_bidang = $row->nama_bidang ?? '';
                        $unit        = $row->unit ?? '';

                        // Hitung kehadiran
                        $total_hari_kerja = $hitungHariKerja($row->tgl_masuk, $row->tgl_keluar, $libur_pekan, $hari_libur);
                        $total_hadir      = (int) ($row->total_hadir ?? 0);
                        $pct              = $total_hari_kerja > 0 ? min(100, round(($total_hadir / $total_hari_kerja) * 100)) : 0;
                        $pct_display      = $total_hari_kerja > 0 ? $pct . '%' : '-';
                        if ($pct >= 80)      $pct_color = 'bg-success';
                        elseif ($pct >= 60)  $pct_color = 'bg-warning';
                        else                 $pct_color = 'bg-danger';
                    @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            <strong>{{ $row->nama }}</strong>
                            <small class="text-muted d-block"><code>{{ $row->username }}</code></small>
                            <span class="badge bg-{{ $row->status_peserta=='Siswa' ? 'info' : 'primary' }} mt-1">
                                {{ $row->status_peserta }}
                            </span>
                        </td>
                        <td>
                            <small>{{ $row->asal_sekolah }}</small><br>
                            <small class="text-muted">{{ $row->jurusan }}</small>
                        </td>
                        <td>
                            {!! $nama_bidang ? '<span class="badge bg-light text-dark border">' . e($nama_bidang) . '</span>' : '<em class="text-muted">-</em>' !!}
                            <small class="text-muted d-block">{{ $unit }}</small>
                        </td>
                        <td class="text-nowrap">
                            <small>
                                {{ date('d/m/Y', strtotime($row->tgl_masuk)) }}<br>
                                <span class="text-muted">s/d</span>
                                {{ date('d/m/Y', strtotime($row->tgl_keluar)) }}
                            </small>
                        </td>
                        <td class="text-nowrap" style="min-width:120px;">
                            @if ($total_hari_kerja > 0)
                                <div class="d-flex align-items-center gap-2">
                                    <span style="font-weight:600; font-size:14px;">{{ $pct_display }}</span>
                                    <div class="progress" style="width:60px; height:6px; margin:0;">
                                        <div class="progress-bar {{ $pct_color }}" role="progressbar"
                                             style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}"
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <small class="text-muted">Hadir: {{ $total_hadir }}/{{ $total_hari_kerja }} hari</small>
                            @else
                                <small class="text-muted"><em>Tidak ada data</em></small>
                            @endif
                        </td>
                        <td>
                            @if ($is_digital)
                                <span class="badge bg-success"><i class="bx bx-qr me-1"></i>Digital</span>
                                <small class="text-muted d-block mt-1">
                                    Terbit: {{ date('d/m/Y', strtotime($row->generated_at)) }}
                                </small>
                                @if ($row->nomor_surat)
                                    <small class="text-muted d-block">No: {{ $row->nomor_surat }}</small>
                                @endif
                            @elseif ($sudah_upload)
                                <span class="badge bg-success"><i class="bx bxs-check-circle me-1"></i>Sudah Upload</span>
                                <small class="text-muted d-block mt-1">
                                    {{ date('d/m/Y', strtotime($row->tgl_upload_scan)) }}
                                    {!! $size_label ? '&mdash; ' . $size_label : '' !!}
                                </small>
                                @if ($row->nomor_surat)
                                    <small class="text-muted d-block">No: {{ $row->nomor_surat }}</small>
                                @endif
                            @elseif ($has_sertif)
                                <span class="badge bg-warning text-dark"><i class="bx bx-time-five me-1"></i>Menunggu Scan</span>
                                <small class="text-muted d-block mt-1">Dicetak: {{ date('d/m/Y', strtotime($row->tgl_cetak)) }}</small>
                                @if ($row->nomor_surat)
                                    <small class="text-muted d-block">No: {{ $row->nomor_surat }}</small>
                                @endif
                            @else
                                <span class="badge bg-secondary"><i class="bx bx-printer me-1"></i>Belum Cetak</span>
                            @endif
                        </td>
                        <td class="text-center text-nowrap">
                            <!-- Tombol Cetak -->
                            <a href="{{ url('sdm/print-sertifikat') }}?laporan_id={{ $row->laporan_id }}&username={{ urlencode($row->username) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-primary me-1"
                               onclick="return confirmCetak('{{ addslashes($row->nama) }}', {{ $has_sertif ? 'true' : 'false' }})">
                                <i class="bx bx-printer me-1"></i>Cetak
                            </a>

                            <!-- Digital Certificate Download -->
                            @if ($is_digital && $row->digital_file)
                                <a href="{{ asset('uploads/sertifikat/' . $row->digital_file) }}"
                                   target="_blank" class="btn btn-sm btn-success me-1" title="Download Digital">
                                    <i class="bx bx-download"></i> Digital
                                </a>
                            @endif

                            <!-- Tombol Upload Scan / Ganti -->
                            @if ($has_sertif && !$is_digital)
                                <button type="button"
                                        class="btn btn-sm {{ $sudah_upload ? 'btn-outline-success' : 'btn-warning' }} btnUploadScan"
                                        data-id="{{ $row->sertif_id }}"
                                        data-laporan="{{ $row->laporan_id }}"
                                        data-nama="{{ $row->nama }}"
                                        data-file="{{ $sudah_upload ? $row->nama_file_scan : '' }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalUploadScan">
                                    <i class="bx {{ $sudah_upload ? 'bx-refresh' : 'bx-upload' }} me-1"></i>
                                    {{ $sudah_upload ? 'Ganti' : 'Upload Scan' }}
                                </button>
                                @if ($sudah_upload)
                                    <a href="{{ asset('uploads/sertifikat/' . $row->file_sertifikat) }}"
                                       target="_blank" class="btn btn-sm btn-success ms-1" title="Lihat">
                                        <i class="bx bx-show"></i>
                                    </a>
                                @endif
                            @elseif (!$has_sertif)
                                <button class="btn btn-sm btn-secondary" disabled title="Cetak sertifikat dulu">
                                    <i class="bx bx-upload me-1"></i>Upload Scan
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                @if ($total == 0)
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bx bx-file-blank fs-3 d-block mb-1"></i>
                            Tidak ada data yang sesuai.
                        </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if ($total_page > 1)
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <small class="text-muted">
                Menampilkan {{ $offset + 1 }}–{{ min($offset + $per_page, $total) }} dari {{ $total }} peserta
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/cetak-sertifikat') }}?p={{ $page-1 }}&q={{ urlencode($search) }}&filter={{ $filter }}">
                            <i class="bx bx-chevron-left"></i></a>
                    </li>
                    @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                        <li class="page-item {{ $i==$page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('sdm/cetak-sertifikat') }}?p={{ $i }}&q={{ urlencode($search) }}&filter={{ $filter }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $total_page ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('sdm/cetak-sertifikat') }}?p={{ $page+1 }}&q={{ urlencode($search) }}&filter={{ $filter }}">
                            <i class="bx bx-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>

<!-- MODAL UPLOAD SCAN -->
<div class="modal fade" id="modalUploadScan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- PENTING: enctype multipart/form-data wajib ada untuk upload file -->
            <form method="POST" action="{{ url('sdm/cetak-sertifikat/upload-scan') }}" enctype="multipart/form-data" id="formUploadScan">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bx bx-upload me-2"></i>Upload Sertifikat Scan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sertifikat_id" id="modalSertifId">
                    <input type="hidden" name="laporan_id"    id="modalLaporanId">

                    <p class="mb-3">Upload sertifikat untuk: <strong id="modalNamaPeserta"></strong></p>

                    <div id="infoSertifLama" class="alert alert-warning py-2 small d-none mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        File saat ini: <strong id="namaFileLama"></strong>.
                        Upload baru akan <strong>menggantikan</strong> file lama.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Sertifikat yang Sudah Ditandatangani <span class="text-danger">*</span></label>
                        <input type="file"
                               name="file_sertifikat"
                               id="fileSertifikat"
                               class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png"
                               required>
                        <div class="form-text">
                            <i class="bx bx-info-circle"></i>
                            Format: <strong>PDF, JPG, atau PNG</strong> &mdash; Maksimal: <strong>10 MB</strong>
                        </div>
                    </div>

                    <div id="scanFileInfo" class="alert alert-light border py-2 d-none">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bx bxs-file-pdf text-danger fs-4" id="scanFileIcon"></i>
                            <div>
                                <div id="scanFileName" class="fw-semibold small"></div>
                                <div id="scanFileSize" class="text-muted small"></div>
                            </div>
                        </div>
                        <div id="scanSizeWarn" class="text-danger small mt-1 d-none">
                            <i class="bx bxs-error-circle"></i> Ukuran melebihi 10 MB!
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="submit_upload_scan" id="btnSubmitScan" class="btn btn-primary">
                        <i class="bx bx-cloud-upload me-1"></i> Upload Sertifikat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================================
// KONFIRMASI CETAK
// ============================================================
function confirmCetak(nama, sudahCetak) {
    if (sudahCetak) {
        return confirm('Sertifikat ' + nama + ' sudah pernah dicetak.\nMencetak ulang TIDAK akan mengubah nomor surat.\n\nLanjutkan?');
    }
    return true;
}

// ============================================================
// SETUP MODAL UPLOAD SCAN
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Event listener untuk tombol upload scan - menggunakan data-bs-toggle yang sudah ada
    var uploadButtons = document.querySelectorAll('.btnUploadScan');
    uploadButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var sertifId = this.getAttribute('data-id');
            var laporanId = this.getAttribute('data-laporan');
            var nama = this.getAttribute('data-nama');
            var namaFile = this.getAttribute('data-file');

            document.getElementById('modalSertifId').value = sertifId;
            document.getElementById('modalLaporanId').value = laporanId;
            document.getElementById('modalNamaPeserta').textContent = nama;

            var infoLama = document.getElementById('infoSertifLama');
            if (namaFile && namaFile !== '') {
                document.getElementById('namaFileLama').textContent = namaFile;
                infoLama.classList.remove('d-none');
            } else {
                infoLama.classList.add('d-none');
            }

            // Reset form
            document.getElementById('fileSertifikat').value = '';
            document.getElementById('scanFileInfo').classList.add('d-none');
            document.getElementById('btnSubmitScan').disabled = false;
        });
    });

    // ============================================================
    // PREVIEW FILE SCAN
    // ============================================================
    document.getElementById('fileSertifikat').addEventListener('change', function(e) {
        var file    = e.target.files[0];
        var info    = document.getElementById('scanFileInfo');
        var icon    = document.getElementById('scanFileIcon');
        var name    = document.getElementById('scanFileName');
        var size    = document.getElementById('scanFileSize');
        var warn    = document.getElementById('scanSizeWarn');
        var btn     = document.getElementById('btnSubmitScan');
        var maxSize = 10 * 1024 * 1024;

        if (file) {
            info.classList.remove('d-none');
            name.textContent = file.name;
            size.textContent = file.size > 1024*1024
                ? (file.size/1024/1024).toFixed(2) + ' MB'
                : (file.size/1024).toFixed(1) + ' KB';
            var ext = file.name.split('.').pop().toLowerCase();
            icon.className = (ext === 'pdf') ? 'bx bxs-file-pdf text-danger fs-4' : 'bx bxs-image text-primary fs-4';
            if (file.size > maxSize) {
                warn.classList.remove('d-none');
                info.classList.add('border-danger');
                btn.disabled = true;
            } else {
                warn.classList.add('d-none');
                info.classList.remove('border-danger');
                btn.disabled = false;
            }
        } else {
            info.classList.add('d-none');
        }
    });

    // ============================================================
    // AUTO REFRESH setelah cetak - pakai sessionStorage
    // ============================================================
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href*="print-sertifikat"]');
        if (link) {
            sessionStorage.setItem('cetakDiklik', '1');
        }
    });

    window.addEventListener('focus', function() {
        if (sessionStorage.getItem('cetakDiklik') === '1') {
            sessionStorage.removeItem('cetakDiklik');
            setTimeout(function() {
                window.location.reload();
            }, 1200);
        }
    });
});
</script>

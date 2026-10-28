<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Magang - PT PLN Nusantara Power UP Paiton</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brand: #0d6efd; --brand-dark: #0a58ca; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #eef3f9;
            color: #22303f;
            min-height: 100vh;
        }
        .site-header {
            background: linear-gradient(135deg, #0a4d8c 0%, #0d6efd 55%, #3d8bfd 100%);
            color: #fff;
            padding: 46px 16px 70px;
            position: relative;
            overflow: hidden;
        }
        .site-header::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 85% 20%, rgba(255,255,255,.18), transparent 45%),
                        radial-gradient(circle at 10% 90%, rgba(255,255,255,.12), transparent 40%);
            pointer-events: none;
        }
        .site-header .inner { position: relative; z-index: 1; max-width: 1080px; margin: 0 auto; }
        .brand-logo {
            width: 54px; height: 54px; object-fit: contain;
            background: #fff; border-radius: 14px; padding: 6px;
            box-shadow: 0 8px 22px rgba(0,0,0,.18);
        }
        .site-header h1 { font-weight: 800; font-size: 1.7rem; margin: 0; }
        .site-header p { margin: 6px 0 0; opacity: .92; font-size: .95rem; }

        .wrap { max-width: 1080px; margin: -44px auto 60px; padding: 0 16px; position: relative; z-index: 2; }

        .stat-bar {
            display: flex; flex-wrap: wrap; gap: 10px 26px;
            background: #fff; border-radius: 16px; padding: 16px 22px;
            box-shadow: 0 12px 30px rgba(13,110,253,.12); margin-bottom: 22px;
        }
        .stat-bar .stat { display: flex; align-items: center; gap: 10px; }
        .stat-bar .stat i { font-size: 1.6rem; color: var(--brand); }
        .stat-bar .stat .num { font-weight: 800; font-size: 1.25rem; line-height: 1; }
        .stat-bar .stat .lbl { font-size: .74rem; color: #6c7a89; text-transform: uppercase; letter-spacing: .04em; }

        .filter-card {
            background: #fff; border-radius: 16px; padding: 16px;
            box-shadow: 0 8px 22px rgba(20,50,90,.07); margin-bottom: 22px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand); box-shadow: 0 0 0 .2rem rgba(13,110,253,.12);
        }

        .report-card {
            background: #fff; border-radius: 16px; height: 100%;
            padding: 20px; border: 1px solid #e7eef7;
            display: flex; flex-direction: column; gap: 12px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .report-card:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(20,50,90,.12); }
        .report-card .name { font-weight: 700; font-size: 1.02rem; line-height: 1.3; }
        .report-card .uname { font-size: .76rem; color: #8a97a6; }
        .report-card .file-title {
            font-size: .82rem; color: #4a5a6a; background: #f4f8fd;
            border-radius: 10px; padding: 8px 10px; line-height: 1.35;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; min-height: 46px;
        }
        .meta-row { display: flex; align-items: flex-start; gap: 8px; font-size: .8rem; color: #5a6b7c; }
        .meta-row i { color: var(--brand); font-size: 1rem; margin-top: 1px; }
        .badge-soft {
            display: inline-block; font-size: .7rem; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
            background: #e7f0ff; color: #0a58ca;
        }
        .badge-approved {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: .7rem; font-weight: 700; padding: 3px 10px;
            border-radius: 20px; background: #d1e7dd; color: #0f5132;
        }
        .card-actions { margin-top: auto; display: flex; gap: 8px; }
        .card-actions .btn { border-radius: 10px; font-size: .82rem; font-weight: 600; flex: 1; }

        .empty-state { text-align: center; padding: 60px 20px; color: #8593a3; }
        .empty-state i { font-size: 3.2rem; opacity: .5; }

        .pagination .page-link { border-radius: 10px !important; margin: 0 3px; border: none; color: #405366; }
        .pagination .page-item.active .page-link { background: var(--brand); color: #fff; }

        .site-footer { text-align: center; color: #8593a3; font-size: .82rem; padding: 0 16px 40px; }
    </style>
</head>
<body>

@php
    // Helper format tanggal Indonesia, ukuran file, dan periode
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    $tglIndo = function ($t) use ($bulan) {
        if (empty($t) || $t === '0000-00-00') return '-';
        try {
            $d = \Carbon\Carbon::parse($t);
        } catch (\Throwable $e) {
            return '-';
        }
        return $d->day . ' ' . $bulan[$d->month] . ' ' . $d->year;
    };

    $periodeIndo = function ($mulai, $selesai) use ($tglIndo) {
        if (empty($mulai) || empty($selesai)) return '-';
        return $tglIndo($mulai) . ' s/d ' . $tglIndo($selesai);
    };

    $ukuran = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    };
@endphp

<header class="site-header">
    <div class="inner d-flex align-items-center gap-3 flex-wrap">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
        <div>
            <h1>Katalog Laporan Magang</h1>
            <p>PT PLN Nusantara Power UP Paiton &mdash; Laporan yang telah disetujui Manager</p>
        </div>
    </div>
</header>

<div class="wrap">

    <div class="stat-bar">
        <div class="stat">
            <i class='bx bx-folder-open'></i>
            <div>
                <div class="num">{{ number_format($laporan->total(), 0, ',', '.') }}</div>
                <div class="lbl">Laporan Disetujui</div>
            </div>
        </div>
        <div class="stat">
            <i class='bx bx-check-shield'></i>
            <div>
                <div class="num">Terbuka</div>
                <div class="lbl">Akses Publik</div>
            </div>
        </div>
    </div>

    <form class="filter-card" method="GET" action="{{ route('laporan.index') }}">
        <div class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class='bx bx-search'></i></span>
                    <input type="text" name="q" class="form-control border-start-0"
                           placeholder="Cari nama, sekolah, jurusan, bidang, atau judul laporan..."
                           value="{{ $q }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="bidang" class="form-select">
                    <option value="">Semua Bidang</option>
                    @foreach ($bidangList as $bid => $bn)
                        <option value="{{ $bid }}" {{ (int) $bidang === (int) $bid ? 'selected' : '' }}>
                            {{ $bn }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class='bx bx-filter-alt'></i> Cari</button>
                @if ($q !== '' || $bidang)
                    <a href="{{ route('laporan.index') }}" class="btn btn-outline-secondary" title="Reset"><i class='bx bx-reset'></i></a>
                @endif
            </div>
        </div>
    </form>

    @if ($laporan->total() === 0)
        <div class="empty-state">
            <i class='bx bx-file-blank d-block mb-3'></i>
            <h5 class="mb-1">Tidak ada laporan ditemukan</h5>
            <p class="mb-0">Belum ada laporan yang disetujui Manager{{ $q !== '' ? ' sesuai pencarian Anda' : '' }}.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach ($laporan as $row)
                <div class="col-lg-4 col-md-6">
                    <div class="report-card">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="name">{{ $row->nama }}</div>
                                <div class="uname"><code>{{ $row->username }}</code></div>
                            </div>
                            <span class="badge-approved"><i class='bx bx-check'></i> Disetujui</span>
                        </div>

                        <div class="file-title" title="{{ $row->nama_file }}">
                            <i class='bx bxs-file-pdf text-danger me-1'></i>
                            {{ $row->nama_file }}
                        </div>

                        <div class="meta-row"><i class='bx bx-school'></i><span>{{ $row->asal_sekolah }}</span></div>
                        <div class="meta-row"><i class='bx bx-book'></i><span>{{ $row->jurusan }}</span></div>
                        <div class="meta-row">
                            <i class='bx bx-buildings'></i>
                            <span>{{ $row->nama_bidang ?? '-' }}{{ !empty($row->unit) ? ' (' . $row->unit . ')' : '' }}</span>
                        </div>
                        <div class="meta-row"><i class='bx bx-calendar'></i><span>{{ $periodeIndo($row->tgl_masuk, $row->tgl_keluar) }}</span></div>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge-soft">{{ $row->status_peserta }}</span>
                            @if (!empty($row->nomor_surat))
                                <span class="badge-soft">No: {{ $row->nomor_surat }}</span>
                            @endif
                            <span class="badge-soft">{{ $ukuran($row->ukuran_file) }}</span>
                        </div>

                        <div class="card-actions">
                            <a href="{{ route('laporan.file', ['id' => $row->id, 'mode' => 'view']) }}" target="_blank"
                               class="btn btn-outline-primary">
                                <i class='bx bx-show'></i> Lihat
                            </a>
                            <a href="{{ route('laporan.file', ['id' => $row->id, 'mode' => 'download']) }}"
                               class="btn btn-primary">
                                <i class='bx bx-download'></i> Unduh
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($laporan->hasPages())
            <nav class="mt-4 d-flex justify-content-center">
                {{ $laporan->onEachSide(2)->links('pagination::bootstrap-5') }}
            </nav>
        @endif

        <p class="text-center text-muted small mb-0 mt-2">
            Menampilkan {{ $laporan->firstItem() }}&ndash;{{ $laporan->lastItem() }}
            dari {{ number_format($laporan->total(), 0, ',', '.') }} laporan
        </p>
    @endif

</div>

<footer class="site-footer">
    <i class='bx bx-lock-alt'></i> Halaman ini hanya menampilkan laporan yang telah disetujui oleh Manager &mdash; PKL System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
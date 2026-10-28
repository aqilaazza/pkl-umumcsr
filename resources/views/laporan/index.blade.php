<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b4fb3">
    <meta name="description" content="Katalog Laporan Magang - PT PLN Nusantara Power UP Paiton">
    <title>Katalog Laporan - Website PKL</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #0b4fb3;
            --ink: #16233b;
            --muted: #6b7a90;
            --line: #e1e8f3;
            --soft: #f4f7fc;
            --yellow: #ffc400;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { min-height: 100%; }
        body {
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
            background-color: #f1f5fb;
            background-image:
                radial-gradient(1100px 520px at 50% -12%, rgba(47, 124, 240, .12), transparent 70%),
                radial-gradient(#d3dcec 1px, transparent 1px);
            background-size: auto, 18px 18px;
        }

        .wrap {
            width: 100%; max-width: 1200px; margin: 0 auto;
            padding: calc(24px + env(safe-area-inset-top)) clamp(16px, 4vw, 40px) calc(32px + env(safe-area-inset-bottom));
        }

        /* ── Hero ── */
        .hero { max-width: 780px; margin: 0 auto 22px; }
        .brand {
            display: flex; align-items: center; justify-content: center;
            width: 168px; height: 64px; margin: 0 auto 16px; padding: 8px 14px;
            border-radius: 18px; background: var(--soft); border: 1px solid var(--line);
        }
        .brand img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .brand i { font-size: 30px; color: var(--blue); display: none; }
        .tag {
            display: table; margin: 0 auto 12px; padding: 5px 12px; border-radius: 8px;
            background: #e8f0ff; color: var(--blue); font-size: 10.5px; font-weight: 800; letter-spacing: .5px;
        }
        h1 { text-align: center; font-size: clamp(26px, 4vw, 42px); font-weight: 800; line-height: 1.15; letter-spacing: -.3px; }
        .sub { text-align: center; font-size: clamp(12.5px, 1.5vw, 15px); color: var(--muted); line-height: 1.6; margin: 10px auto 0; max-width: 620px; }

        /* ── Statistik ── */
        .stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px; margin: 24px 0 18px;
        }
        .stat {
            display: flex; align-items: center; gap: 12px;
            background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 15px 17px;
            box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
        }
        .stat-ico {
            width: 46px; height: 46px; flex-shrink: 0; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 23px; background: #e8f0ff; color: var(--blue);
        }
        .stat-ico.green { background: #e1f5ea; color: #157347; }
        .stat-ico.cyan { background: #e2f8fd; color: #0891b2; }
        .stat-val { font-size: 20px; font-weight: 800; line-height: 1.1; color: var(--ink); font-variant-numeric: tabular-nums; }
        .stat-lbl { margin-top: 3px; font-size: 11px; font-weight: 700; color: var(--muted); }

        /* ── Pencarian ── */
        .search-card {
            display: flex; flex-wrap: wrap; align-items: center; gap: 10px;
            background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 12px;
            box-shadow: 0 8px 24px rgba(24, 60, 120, .07); margin-bottom: 20px;
        }
        .search-input { position: relative; flex: 1 1 300px; min-width: 0; }
        .search-input > i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 19px; color: var(--muted); pointer-events: none;
        }
        .search-input input, .field select {
            width: 100%; height: 46px; border-radius: 13px; border: 1.5px solid var(--line);
            font-family: inherit; font-size: 13px; color: var(--ink); outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .search-input input { background: var(--soft); padding: 0 14px 0 43px; }
        .search-input input::placeholder { color: #9aa8bb; }
        .search-input input:focus { border-color: #2f7cf0; background: #fff; box-shadow: 0 0 0 4px rgba(47, 124, 240, .15); }
        .field { flex: 0 1 220px; min-width: 0; }
        .field select {
            background-color: #fff; padding: 0 38px 0 14px; cursor: pointer;
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7a90' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 14px center;
        }
        .field select:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .15); }
        .search-actions { display: flex; gap: 8px; flex-shrink: 0; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            height: 46px; padding: 0 18px; border-radius: 13px; border: 0; cursor: pointer;
            font-family: inherit; font-size: 13px; font-weight: 800; text-decoration: none; white-space: nowrap;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .btn:active { transform: scale(.97); }
        .btn i { font-size: 17px; }
        .btn-primary { background: linear-gradient(135deg, #2f7cf0, var(--blue)); color: #fff; box-shadow: 0 8px 18px rgba(11, 79, 179, .25); }
        .btn-primary:hover { color: #fff; box-shadow: 0 10px 22px rgba(11, 79, 179, .34); }
        .btn-ghost { background: var(--soft); color: #3d4c63; border: 1px solid var(--line); }
        .btn-ghost:hover { background: #fff; color: var(--ink); }

        /* ── Judul hasil ── */
        .result-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin: 2px 2px 12px; }
        .result-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 800; color: var(--ink); }
        .result-title i { font-size: 22px; color: var(--blue); }
        .result-count { font-size: 11px; font-weight: 700; color: var(--blue); background: #e8f0ff; padding: 5px 12px; border-radius: 999px; }

        /* ── Grid kartu ── */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
        .card {
            display: flex; flex-direction: column; gap: 12px; height: 100%;
            background: #fff; border: 1px solid rgba(230, 236, 245, .95); border-radius: 18px; padding: 16px;
            box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px rgba(24, 60, 120, .12); border-color: #cfdcf0; }

        .card-top { display: flex; align-items: flex-start; gap: 11px; }
        .card-ico {
            width: 44px; height: 44px; flex-shrink: 0; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: #e8f0ff; color: var(--blue); font-size: 23px;
        }
        .card-id { flex: 1; min-width: 0; }
        .card-name { font-size: 14.5px; font-weight: 800; line-height: 1.25; color: var(--ink); word-break: break-word; }
        .card-user { display: flex; align-items: center; gap: 4px; margin-top: 3px; font-size: 11.5px; font-weight: 600; color: var(--muted); }
        .card-user i { font-size: 14px; }
        .badge-ok {
            flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px;
            padding: 4px 9px; border-radius: 999px; font-size: 10px; font-weight: 800;
            background: #e1f5ea; color: #157347; white-space: nowrap;
        }

        .card-file {
            display: flex; align-items: flex-start; gap: 7px;
            padding: 9px 11px; border-radius: 11px; background: var(--soft); border: 1px solid var(--line);
            font-size: 11.5px; font-weight: 600; line-height: 1.4; color: #4a5a72; word-break: break-word;
        }
        .card-file i { flex-shrink: 0; margin-top: 1px; font-size: 15px; color: #dc3545; }
        .card-file span { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .card-meta { list-style: none; display: flex; flex-direction: column; gap: 6px; }
        .card-meta li { display: flex; align-items: flex-start; gap: 7px; font-size: 12px; line-height: 1.4; color: #5a6b7c; }
        .card-meta li i { flex-shrink: 0; margin-top: 1px; font-size: 15px; color: #2f7cf0; }
        .card-meta li span { word-break: break-word; }

        .card-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .chip {
            display: inline-flex; align-items: center; gap: 4px; max-width: 100%;
            padding: 4px 10px; border-radius: 999px; font-size: 10.5px; font-weight: 700;
            background: var(--soft); color: #4a5a72; border: 1px solid var(--line);
        }
        .chip i { font-size: 12px; }
        .chip-status { background: #e1f5ea; color: #157347; border-color: #c9ebd8; }

        .card-actions { margin-top: auto; display: flex; gap: 8px; padding-top: 12px; border-top: 1px dashed var(--line); }
        .card-actions .btn { flex: 1; height: 38px; padding: 0 12px; border-radius: 12px; font-size: 12.5px; }
        .btn-outline { background: #e8f0ff; color: var(--blue); }
        .btn-outline:hover { background: #dbe8ff; color: var(--blue); }
        .btn-solid { background: linear-gradient(135deg, #2f7cf0, var(--blue)); color: #fff; box-shadow: 0 6px 14px rgba(11, 79, 179, .25); }
        .btn-solid:hover { color: #fff; box-shadow: 0 8px 18px rgba(11, 79, 179, .34); }

        /* ── Kosong ── */
        .empty {
            text-align: center; padding: 48px 18px; background: #fff;
            border-radius: 18px; border: 1px solid var(--line); color: var(--muted);
        }
        .empty i { font-size: 52px; color: #b8c4d6; }
        .empty h5 { margin: 8px 0 4px; font-size: 15px; font-weight: 800; color: var(--ink); }
        .empty p { font-size: 12.5px; }

        /* ── Paginasi ── */
        .pager { display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; margin: 22px 0 0; }
        .pg {
            min-width: 38px; height: 38px; padding: 0 11px; border-radius: 11px;
            display: inline-flex; align-items: center; justify-content: center;
            background: #fff; border: 1px solid var(--line); color: #405366;
            text-decoration: none; font-size: 12.5px; font-weight: 700;
            box-shadow: 0 3px 10px rgba(24, 60, 120, .06);
        }
        .pg i { font-size: 18px; }
        a.pg:hover { color: var(--blue); border-color: #cfdcf0; }
        .pg.active { background: linear-gradient(135deg, #2f7cf0, var(--blue)); border-color: transparent; color: #fff; }
        .pg.disabled { background: var(--soft); color: #a3afc0; box-shadow: none; }
        .pg.dots { background: transparent; border: 0; box-shadow: none; color: var(--muted); }
        .count { text-align: center; margin-top: 12px; font-size: 12px; color: var(--muted); }

        /* ── Footer ── */
        .back {
            display: flex; align-items: center; justify-content: center; gap: 6px; width: fit-content;
            margin: 26px auto 0; padding: 9px 16px; border-radius: 999px; text-decoration: none;
            background: var(--soft); border: 1px solid var(--line); color: #3d4c63; font-size: 12.5px; font-weight: 700;
        }
        .back i { font-size: 18px; color: var(--blue); }
        .back:hover { background: #fff; }
        .foot { text-align: center; font-size: 11px; color: var(--muted); margin-top: 18px; }

        .btn:focus-visible, .pg:focus-visible, .back:focus-visible, .search-input input:focus-visible, .field select:focus-visible {
            outline: 3px solid rgba(47, 124, 240, .5); outline-offset: 2px;
        }

        @media (max-width: 560px) {
            .search-actions { flex: 1 1 100%; }
            .search-actions .btn { flex: 1; }
            .field { flex: 1 1 100%; }
            .stat-val { font-size: 18px; }
        }
        @media (min-width: 992px) {
            .brand { width: 190px; height: 72px; margin-bottom: 18px; }
            .grid { gap: 16px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .card, .btn, .search-input input, .field select { transition: none; }
        }
    </style>
</head>
<body>

@php
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

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

<div class="wrap">

    <header class="hero">
        <div class="brand">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <i class='bx bxs-briefcase-alt-2'></i>
        </div>
        <span class="tag">PORTAL PKL &amp; MAGANG INDUSTRI</span>
        <h1>Katalog Laporan Magang</h1>
        <p class="sub">Kumpulan laporan peserta magang PT PLN Nusantara Power UP Paiton yang telah disetujui Manager.</p>
    </header>

    <div class="stats">
        <div class="stat">
            <div class="stat-ico"><i class='bx bx-folder-open'></i></div>
            <div>
                <div class="stat-val">{{ number_format($laporan->total(), 0, ',', '.') }}</div>
                <div class="stat-lbl">Laporan Disetujui</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-ico cyan"><i class='bx bx-buildings'></i></div>
            <div>
                <div class="stat-val">{{ number_format($bidangList->count(), 0, ',', '.') }}</div>
                <div class="stat-lbl">Bidang</div>
            </div>
        </div>
        <div class="stat">
            <div class="stat-ico green"><i class='bx bx-globe'></i></div>
            <div>
                <div class="stat-val">Terbuka</div>
                <div class="stat-lbl">Akses Publik</div>
            </div>
        </div>
    </div>

    <form class="search-card" method="GET" action="{{ route('laporan.index') }}">
        <div class="search-input">
            <i class='bx bx-search'></i>
            <input type="text" name="q" aria-label="Cari laporan"
                   placeholder="Cari nama, sekolah, jurusan, bidang, atau judul laporan..."
                   value="{{ $q }}">
        </div>
        <div class="field">
            <select name="bidang" aria-label="Filter bidang">
                <option value="">Semua Bidang</option>
                @foreach ($bidangList as $bid => $bn)
                    <option value="{{ $bid }}" {{ (int) $bidang === (int) $bid ? 'selected' : '' }}>{{ $bn }}</option>
                @endforeach
            </select>
        </div>
        <div class="search-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bx-filter-alt'></i> Cari</button>
            @if ($q !== '' || $bidang)
                <a href="{{ route('laporan.index') }}" class="btn btn-ghost" title="Reset pencarian"><i class='bx bx-reset'></i> Reset</a>
            @endif
        </div>
    </form>

    <div class="result-head">
        <div class="result-title"><i class='bx bx-book-open'></i> Daftar Laporan</div>
        <span class="result-count">{{ number_format($laporan->total(), 0, ',', '.') }} laporan ditemukan</span>
    </div>

    @if ($laporan->total() === 0)
        <div class="empty">
            <i class='bx bx-file-blank'></i>
            <h5>Tidak ada laporan ditemukan</h5>
            <p>Belum ada laporan yang disetujui Manager{{ $q !== '' ? ' sesuai pencarian Anda' : '' }}.</p>
        </div>
    @else
        <div class="grid">
            @foreach ($laporan as $row)
                <article class="card">
                    <div class="card-top">
                        <div class="card-ico"><i class='bx bxs-file-pdf'></i></div>
                        <div class="card-id">
                            <div class="card-name">{{ $row->nama }}</div>
                            <div class="card-user"><i class='bx bx-id-card'></i> {{ $row->username }}</div>
                        </div>
                        <span class="badge-ok"><i class='bx bx-check'></i> Disetujui</span>
                    </div>

                    <div class="card-file" title="{{ $row->nama_file }}">
                        <i class='bx bx-file-blank'></i>
                        <span>{{ $row->nama_file }}</span>
                    </div>

                    <ul class="card-meta">
                        <li><i class='bx bx-school'></i><span>{{ $row->asal_sekolah }}</span></li>
                        <li><i class='bx bx-book'></i><span>{{ $row->jurusan }}</span></li>
                        <li><i class='bx bx-buildings'></i><span>{{ $row->nama_bidang ?? '-' }}{{ !empty($row->unit) ? ' (' . $row->unit . ')' : '' }}</span></li>
                        <li><i class='bx bx-calendar'></i><span>{{ $periodeIndo($row->tgl_masuk, $row->tgl_keluar) }}</span></li>
                    </ul>

                    <div class="card-chips">
                        <span class="chip chip-status"><i class='bx bx-check-circle'></i> {{ $row->status_peserta }}</span>
                        @if (!empty($row->nomor_surat))
                            <span class="chip"><i class='bx bx-hash'></i> {{ $row->nomor_surat }}</span>
                        @endif
                        <span class="chip"><i class='bx bx-hdd'></i> {{ $ukuran($row->ukuran_file) }}</span>
                    </div>

                    <div class="card-actions">
                        <a href="{{ route('laporan.file', ['id' => $row->id, 'mode' => 'view']) }}" target="_blank" class="btn btn-outline">
                            <i class='bx bx-show'></i> Lihat
                        </a>
                        <a href="{{ route('laporan.file', ['id' => $row->id, 'mode' => 'download']) }}" class="btn btn-solid">
                            <i class='bx bx-download'></i> Unduh
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($laporan->hasPages())
            @php
                $start = max(1, $laporan->currentPage() - 2);
                $end   = min($laporan->lastPage(), $laporan->currentPage() + 2);
            @endphp
            <nav class="pager" aria-label="Navigasi halaman">
                @if ($laporan->onFirstPage())
                    <span class="pg disabled" aria-hidden="true"><i class='bx bx-chevron-left'></i></span>
                @else
                    <a class="pg" href="{{ $laporan->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><i class='bx bx-chevron-left'></i></a>
                @endif

                @if ($start > 1)
                    <a class="pg" href="{{ $laporan->url(1) }}">1</a>
                    @if ($start > 2)
                        <span class="pg dots" aria-hidden="true">&hellip;</span>
                    @endif
                @endif

                @foreach ($laporan->getUrlRange($start, $end) as $page => $url)
                    @if ($page == $laporan->currentPage())
                        <span class="pg active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pg" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($end < $laporan->lastPage())
                    @if ($end < $laporan->lastPage() - 1)
                        <span class="pg dots" aria-hidden="true">&hellip;</span>
                    @endif
                    <a class="pg" href="{{ $laporan->url($laporan->lastPage()) }}">{{ $laporan->lastPage() }}</a>
                @endif

                @if ($laporan->hasMorePages())
                    <a class="pg" href="{{ $laporan->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><i class='bx bx-chevron-right'></i></a>
                @else
                    <span class="pg disabled" aria-hidden="true"><i class='bx bx-chevron-right'></i></span>
                @endif
            </nav>
        @endif

        <p class="count">
            Menampilkan {{ $laporan->firstItem() }}&ndash;{{ $laporan->lastItem() }}
            dari {{ number_format($laporan->total(), 0, ',', '.') }} laporan
        </p>
    @endif

    <a href="{{ route('login') }}" class="back"><i class='bx bx-left-arrow-alt'></i> Kembali ke Login</a>
    <div class="foot">SINFO PKL &copy; {{ date('Y') }}</div>

</div>

</body>
</html>

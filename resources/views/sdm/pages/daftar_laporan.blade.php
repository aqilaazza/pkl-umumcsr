@php
    $cur  = $laporan->currentPage();
    $last = $laporan->lastPage();
    $from = max(1, $cur - 1);
    $to   = min($last, $cur + 1);
@endphp

<div class="kq dl">

    {{-- Judul --}}
    <div class="kq-head">
        <div class="kq-head-ico"><i class="bx bx-list-ul"></i></div>
        <div style="min-width:0;">
            <h1 class="kq-title">Daftar Laporan</h1>
            <p class="kq-sub-title">Laporan yang sudah disetujui SDM dan Manager. Laporan Aktif tampil sebagai referensi peserta, laporan Nonaktif disembunyikan dari halaman peserta dan publik.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="dl-alert ok" role="alert"><i class="bx bx-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="dl-alert err" role="alert"><i class="bx bx-error-circle"></i> {{ session('error') }}</div>
    @endif

    {{-- Cari --}}
    <form method="GET" action="{{ route('sdm.pengaturan.daftar_laporan') }}" class="kq-card kq-filter">
        <div class="kq-card-t"><i class="bx bx-filter-alt"></i> Cari Laporan</div>
        <div class="dl-search-grid">
            <div class="kq-field">
                <label for="dl-q" class="kq-label">Nama peserta atau nama laporan</label>
                <div class="kq-select">
                    <i class="bx bx-search"></i>
                    <input type="search" name="q" id="dl-q" value="{{ $search }}" class="dl-input" placeholder="Cari nama peserta atau nama laporan" autocomplete="off">
                </div>
            </div>
            <div class="kq-actions">
                <button type="submit" class="kq-btn primary"><i class="bx bx-search"></i> Cari</button>
                <a href="{{ route('sdm.pengaturan.daftar_laporan') }}" class="kq-btn ghost" title="Reset pencarian" aria-label="Reset pencarian"><i class="bx bx-sync"></i></a>
            </div>
        </div>
        @if ($search !== '')
            <div class="kq-active">
                <span class="kq-active-l">Pencarian aktif:</span>
                <span class="kq-tag"><i class="bx bx-search"></i> {{ $search }}</span>
            </div>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="kq-card kq-list">
        <div class="kq-list-head">
            <div class="kq-list-ttl">
                <div class="kq-list-ico"><i class="bx bx-folder-open"></i></div>
                <div style="min-width:0;">
                    <h2 class="kq-list-t">Daftar Laporan Disetujui</h2>
                    <p class="kq-card-s">Klik toggle untuk mengaktifkan atau menonaktifkan laporan dari halaman peserta. Diurutkan berdasarkan periode magang terbaru.</p>
                </div>
            </div>
            <span class="kq-pill">{{ number_format($laporan->total()) }} laporan</span>
        </div>

        @if ($laporan->count() === 0)
            <div class="kq-empty">
                <div class="kq-empty-ico"><i class="bx bx-file-blank"></i></div>
                <div class="kq-empty-t">Tidak ada laporan</div>
                <div class="kq-empty-s">{{ $search !== '' ? 'Tidak ada laporan yang cocok dengan pencarian.' : 'Belum ada laporan yang disetujui.' }}</div>
            </div>
        @else
            <div class="kq-table-wrap">
                <table class="kq-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Peserta</th>
                            <th class="dl-c">Nama Laporan</th>
                            <th class="dl-c">Status</th>
                            <th class="dl-c">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($laporan as $index => $row)
                            @php
                                $aktif = (bool) $row->is_active;
                                $nama  = trim((string) $row->nama);
                                $pal   = [['#e7f1ff','#0b4fb3'],['#d9f0e3','#0f5132'],['#fff1c9','#7a5800'],['#efe6ff','#5b2fa8'],['#d5f1f8','#055160'],['#fde0e0','#a01c1c']];
                                $pi    = crc32($nama) % count($pal);
                            @endphp
                            <tr class="{{ $aktif ? '' : 'dl-off' }}">
                                <td data-label="#">{{ $laporan->firstItem() + $index }}</td>
                                <td>
                                    <div class="kq-person">
                                        <span class="kq-ava" style="--abg: {{ $pal[$pi][0] }}; --ac: {{ $pal[$pi][1] }};">{{ strtoupper(mb_substr($nama ?: '?', 0, 1)) }}</span>
                                        <div style="min-width:0;">
                                            <div class="kq-name">{{ $nama }}</div>
                                            @if (!empty($row->username))<span class="kq-sub">{{ $row->username }}</span>@endif
                                            @if (!empty($row->tgl_masuk) && !empty($row->tgl_keluar))
                                                <span class="kq-sub">Periode {{ \Carbon\Carbon::parse($row->tgl_masuk)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($row->tgl_keluar)->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Nama Laporan">
                                    <div class="dl-file" title="{{ $row->nama_file }}">
                                        <span class="dl-pdf"><i class="bx bxs-file-pdf"></i></span>
                                        <span class="dl-file-name">{{ $row->nama_file }}</span>
                                    </div>
                                </td>
                                <td class="dl-c" data-label="Status">
                                    <span class="kq-badge {{ $aktif ? 'aktif' : 'nonaktif' }}">{{ $aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="dl-c" data-label="Aksi">
                                    <div class="dl-actions">
                                        <a href="{{ asset('uploads/laporan/' . $row->file_laporan) }}" target="_blank" rel="noopener" class="kq-btn ghost dl-view"
                                           data-ui-preview data-preview-title="Laporan {{ $nama }}">
                                            <i class="bx bx-show"></i> Lihat
                                        </a>
                                        <form method="POST" action="{{ route('sdm.pengaturan.daftar_laporan.toggle', ['id' => $row->id]) }}">
                                            @csrf
                                            <input type="hidden" name="is_active" value="{{ $aktif ? 0 : 1 }}">
                                            <input type="hidden" name="q" value="{{ $search }}">
                                            <input type="hidden" name="page" value="{{ $cur }}">
                                            <button type="submit" class="dl-toggle {{ $aktif ? 'on' : '' }}"
                                                    role="switch" aria-checked="{{ $aktif ? 'true' : 'false' }}"
                                                    title="{{ $aktif ? 'Klik untuk menonaktifkan laporan' : 'Klik untuk mengaktifkan laporan' }}"
                                                    aria-label="{{ $aktif ? 'Nonaktifkan laporan' : 'Aktifkan laporan' }}">
                                                <span class="dl-switch"></span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="kq-pager">
                <div class="kq-pager-info">Menampilkan {{ $laporan->firstItem() }} s/d {{ $laporan->lastItem() }} dari {{ $laporan->total() }} laporan</div>
                @if ($laporan->hasPages())
                    <div class="kq-pager-btns" role="navigation" aria-label="Halaman daftar laporan">
                        @if ($laporan->onFirstPage())
                            <span class="kq-pg is-off" aria-label="Halaman sebelumnya"><i class="bx bx-chevron-left"></i></span>
                        @else
                            <a class="kq-pg" href="{{ $laporan->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><i class="bx bx-chevron-left"></i></a>
                        @endif

                        @if ($from > 1)
                            <a class="kq-pg" href="{{ $laporan->url(1) }}">1</a>
                            @if ($from > 2)<span class="kq-dots">…</span>@endif
                        @endif

                        @for ($i = $from; $i <= $to; $i++)
                            @if ($i === $cur)
                                <span class="kq-pg is-cur" aria-current="page">{{ $i }}</span>
                            @else
                                <a class="kq-pg" href="{{ $laporan->url($i) }}">{{ $i }}</a>
                            @endif
                        @endfor

                        @if ($to < $last)
                            @if ($to < $last - 1)<span class="kq-dots">…</span>@endif
                            <a class="kq-pg" href="{{ $laporan->url($last) }}">{{ $last }}</a>
                        @endif

                        @if ($laporan->hasMorePages())
                            <a class="kq-pg" href="{{ $laporan->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><i class="bx bx-chevron-right"></i></a>
                        @else
                            <span class="kq-pg is-off" aria-label="Halaman berikutnya"><i class="bx bx-chevron-right"></i></span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
    .kq { --kq-blue: var(--pln-blue, #0b6bcb); --kq-ink: #16233b; --kq-muted: #6b7a90; --kq-line: #e6ecf5; --kq-soft: #f4f7fc; color: var(--kq-ink); }
    .kq * { box-sizing: border-box; }

    /* Judul */
    .kq-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .kq-head-ico { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; font-size: 21px; display: flex; align-items: center; justify-content: center; background: #e7f1ff; color: var(--kq-blue); }
    .kq-title { font-size: 20px; font-weight: 800; line-height: 1.2; margin: 0; }
    .kq-sub-title { font-size: 12px; color: var(--kq-muted); margin: 2px 0 0; }

    /* Alert */
    .dl-alert { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; font-size: 12.5px; font-weight: 700; margin-bottom: 12px; }
    .dl-alert i { font-size: 18px; }
    .dl-alert.ok  { background: #d9f0e3; color: #0f5132; }
    .dl-alert.err { background: #fde0e0; color: #a01c1c; }

    /* Kartu */
    .kq-card { background: #fff; border-radius: 16px; border: 1px solid var(--kq-line); box-shadow: 0 4px 16px rgba(24, 60, 120, .05); padding: 14px; margin-bottom: 12px; }
    .kq-card-t { font-size: 13.5px; font-weight: 800; display: flex; align-items: center; gap: 7px; margin-bottom: 10px; }
    .kq-card-t i { color: var(--kq-blue); font-size: 18px; }
    .kq-card-s { font-size: 11px; color: var(--kq-muted); margin: 3px 0 0; }
    .kq-pill { font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 999px; background: #e7f1ff; color: var(--kq-blue); white-space: nowrap; }

    /* Pencarian */
    .dl-search-grid { display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end; }
    .kq-label { display: block; font-size: 11px; font-weight: 700; color: var(--kq-muted); margin-bottom: 5px; }
    .kq-select { position: relative; }
    .kq-select > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: 17px; color: var(--kq-muted); pointer-events: none; }
    .dl-input { width: 100%; height: 40px; border-radius: 10px; border: 1px solid var(--kq-line); background: #fff; color: var(--kq-ink); padding: 0 12px 0 36px; font-size: 12.5px; font-weight: 600; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .dl-input:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .14); }
    .kq-actions { display: flex; gap: 7px; }
    .kq-btn { display: inline-flex; align-items: center; justify-content: center; gap: 5px; height: 40px; padding: 0 14px; border-radius: 10px; border: 1px solid transparent; font-size: 12.5px; font-weight: 800; text-decoration: none; cursor: pointer; transition: transform .15s ease, border-color .15s ease, color .15s ease; }
    .kq-btn:active { transform: scale(.97); }
    .kq-btn i { font-size: 17px; }
    .kq-btn.primary { background: linear-gradient(135deg, #2f7cf0, #0b4fb3); color: #fff; box-shadow: 0 6px 14px rgba(11, 79, 179, .26); }
    .kq-btn.ghost { background: #fff; color: var(--kq-muted); border-color: var(--kq-line); }
    .kq-actions .kq-btn.ghost { width: 40px; padding: 0; }
    .kq-btn.ghost:hover { border-color: #9cc2f5; color: var(--kq-blue); }
    .kq-active { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--kq-line); }
    .kq-active-l { font-size: 11px; font-weight: 700; color: var(--kq-muted); }
    .kq-tag { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; background: #e7f1ff; color: var(--kq-blue); font-size: 11px; font-weight: 700; }
    .kq-tag i { font-size: 14px; }

    /* Kosong */
    .kq-empty { text-align: center; padding: 28px 16px; }
    .kq-empty-ico { width: 52px; height: 52px; border-radius: 16px; margin: 0 auto 10px; font-size: 26px; display: flex; align-items: center; justify-content: center; background: #e9edf3; color: #8a98ad; }
    .kq-empty-t { font-size: 14px; font-weight: 800; }
    .kq-empty-s { font-size: 11.5px; color: var(--kq-muted); margin-top: 4px; }

    /* Daftar */
    .kq-list-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    .kq-list-ttl { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .kq-list-ico { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; font-size: 19px; display: flex; align-items: center; justify-content: center; background: #e7f1ff; color: var(--kq-blue); }
    .kq-list-t { font-size: 15px; font-weight: 800; margin: 0; line-height: 1.25; }

    .kq-table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid var(--kq-line); }
    .kq-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; min-width: 700px; margin: 0; }
    .kq-table thead th { background: var(--kq-soft); padding: 9px 10px; font-size: 9.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--kq-muted); text-align: left; white-space: nowrap; border-bottom: 1px solid var(--kq-line); }
    .kq-table td { padding: 9px 10px; vertical-align: middle; border-bottom: 1px dashed var(--kq-line); font-size: 12px; }
    .kq-table tbody tr:hover { background: #fafcff; }
    .kq-table tbody tr:last-child td { border-bottom: 0; }
    .kq-table td:first-child { color: var(--kq-muted); font-weight: 700; width: 38px; }

    /* Kolom rata tengah (desktop): judul Nama Laporan, Status, Aksi; isi Status & Aksi */
    .kq-table th.dl-c, .kq-table td.dl-c { text-align: center; }
    .kq-table td.dl-c .dl-actions { justify-content: center; }

    .kq-person { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .kq-ava { width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: var(--ac); background: var(--abg); }
    .kq-name { font-weight: 800; line-height: 1.25; }
    .kq-sub { display: block; font-size: 10.5px; color: var(--kq-muted); margin-top: 2px; font-weight: 500; }
    .kq-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; white-space: nowrap; }
    .kq-badge.aktif { background: #d9f0e3; color: #0f5132; }
    .kq-badge.nonaktif { background: #fde0e0; color: #a01c1c; }

    .dl-file { display: flex; align-items: center; gap: 9px; max-width: 380px; min-width: 0; }
    .dl-pdf { width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0; background: #fde0e0; color: #a01c1c; display: flex; align-items: center; justify-content: center; font-size: 17px; }
    .dl-file-name { font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dl-off .dl-person, .dl-off .dl-file, .dl-off .kq-person { opacity: .55; }

    .dl-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .dl-actions form { margin: 0; }
    .dl-view { height: 32px; padding: 0 12px; font-size: 12px; }
    .dl-toggle { position: relative; width: 44px; height: 24px; border: 0; padding: 0; border-radius: 999px; background: #cbd5e1; cursor: pointer; transition: background .2s ease; display: block; }
    .dl-toggle:focus-visible { outline: 3px solid var(--kq-blue); outline-offset: 2px; }
    .dl-switch { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.28); transition: left .2s ease; }
    .dl-toggle.on { background: #059669; }
    .dl-toggle.on .dl-switch { left: 23px; }

    /* Paginasi */
    .kq-pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--kq-line); }
    .kq-pager-info { font-size: 11px; color: var(--kq-muted); font-weight: 600; }
    .kq-pager-btns { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .kq-pg { min-width: 30px; height: 30px; padding: 0 8px; border-radius: 9px; border: 1px solid var(--kq-line); background: #fff; color: var(--kq-ink); font-size: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: all .15s ease; }
    a.kq-pg:hover { border-color: #9cc2f5; color: var(--kq-blue); }
    .kq-pg:focus-visible { outline: 3px solid var(--kq-blue); outline-offset: 1px; }
    .kq-pg.is-cur { background: linear-gradient(135deg, #2f7cf0, #0b4fb3); border-color: transparent; color: #fff; box-shadow: 0 5px 12px rgba(11, 79, 179, .26); }
    .kq-pg.is-off { opacity: .4; cursor: not-allowed; }
    .kq-pg i { font-size: 18px; line-height: 1; }
    .kq-dots { color: var(--kq-muted); padding: 0 2px; font-weight: 800; }

    @@media (max-width: 767.98px) {
        .dl-search-grid { grid-template-columns: 1fr; }
        .kq-actions .kq-btn.primary { flex: 1; }

        /* Tabel menjadi kartu (rata kiri) */
        .kq-table-wrap { border: 0; overflow: visible; }
        .kq-table { min-width: 0; }
        .kq-table thead { display: none; }
        .kq-table, .kq-table tbody, .kq-table tr, .kq-table td { display: block; width: 100%; }
        .kq-table tbody tr { position: relative; margin-top: 10px; padding: 12px 14px; border: 1px solid var(--kq-line); border-radius: 16px; background: #fff; }
        .kq-table td { padding: 5px 0; border: 0; }
        .kq-table td[data-label]::before { content: attr(data-label); display: block; font-size: 9.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--kq-muted); margin-bottom: 1px; }
        .kq-table td:first-child { position: absolute; top: 14px; right: 14px; width: auto; padding: 0; }
        .kq-table td:first-child::before { display: none; }
        .kq-table td:nth-child(2) { padding: 0 34px 8px 0; margin-bottom: 4px; border-bottom: 1px dashed var(--kq-line); }
        .dl-file { max-width: 100%; }
        .kq-table td.dl-c { text-align: left; }
        .kq-table td.dl-c .dl-actions { justify-content: flex-start; }

        .kq-pager { justify-content: center; text-align: center; }
        .kq-pager-info { width: 100%; }
        .kq-pager-btns { justify-content: center; }
    }
    @@media (prefers-reduced-motion: reduce) {
        .kq-btn, .dl-toggle, .dl-switch, .kq-pg { transition: none; }
    }
</style>
@php
    // Helper tampilan saja
    $ap_bln = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $ap_tgl = function ($t, $jam = false) use ($ap_bln) {
        $ts = $t ? strtotime($t) : false;
        if (! $ts) return '-';
        $s = date('j', $ts) . ' ' . $ap_bln[(int) date('n', $ts)] . ' ' . date('Y', $ts);
        return $jam ? $s . ', ' . date('H:i', $ts) : $s;
    };
@endphp

<style>
    .ap { --ap-blue: #0b4fb3; --ap-ink: #16233b; --ap-muted: #6b7a90; --ap-line: #e6ecf5; --ap-soft: #f4f7fc; color: var(--ap-ink); }
    .ap * { box-sizing: border-box; }

    /* ── Judul ── */
    .ap-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .ap-title { font-size: 22px; font-weight: 800; line-height: 1.2; margin: 0; }
    .ap-sub { font-size: 12.5px; color: var(--ap-muted); margin: 4px 0 0; }
    .ap-tag {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px;
        background: #e8f0ff; color: var(--ap-blue); font-size: 11px; font-weight: 800;
    }
    .ap-tag i { font-size: 15px; }

    /* ── Ringkasan ── */
    .ap-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .ap-stat {
        display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 18px; background: #fff;
        border: 1px solid var(--ap-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
        border-left: 5px solid var(--c); min-width: 0;
    }
    .ap-stat-ico {
        width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0; font-size: 23px;
        display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--c);
    }
    .ap-stat-n { font-size: 24px; font-weight: 800; line-height: 1.1; color: var(--ap-ink); }
    .ap-stat-l { font-size: 11.5px; font-weight: 600; color: var(--ap-muted); margin-top: 3px; }
    .ap-blue  { --c: #0b4fb3; --bg: #e8f0ff; }
    .ap-amber { --c: #e0a800; --bg: #fff3cd; background: linear-gradient(180deg, #fffaf0, #fff); }
    .ap-green { --c: #198754; --bg: #d9f0e3; }
    .ap-red   { --c: #dc3545; --bg: #fbdcdf; }

    /* ── Kartu utama ── */
    .ap-card { background: #fff; border-radius: 20px; border: 1px solid var(--ap-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06); overflow: hidden; }
    .ap-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 18px 0; }
    .ap-card-title { font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin: 0; }
    .ap-card-title i { color: var(--ap-blue); font-size: 20px; }
    .ap-pill { font-size: 11px; font-weight: 800; padding: 5px 11px; border-radius: 999px; background: #e8f0ff; color: var(--ap-blue); white-space: nowrap; }

    .ap-search { display: flex; gap: 8px; padding: 14px 18px 16px; flex-wrap: wrap; }
    .ap-field { position: relative; flex: 1 1 260px; min-width: 0; max-width: 420px; }
    .ap-field i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 19px; color: var(--ap-muted); pointer-events: none; }
    .ap-input {
        width: 100%; height: 42px; border-radius: 13px; border: 1px solid var(--ap-line); background: #fff;
        padding: 0 14px 0 40px; font-size: 13px; color: var(--ap-ink); outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ap-input:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .14); }
    .ap-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; padding: 0 16px;
        border-radius: 13px; border: 1px solid transparent; font-size: 13px; font-weight: 800; text-decoration: none; cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ap-btn:active { transform: scale(.97); }
    .ap-btn i { font-size: 18px; }
    .ap-btn.primary { background: linear-gradient(135deg, #2f7cf0, var(--ap-blue)); color: #fff; box-shadow: 0 6px 14px rgba(11, 79, 179, .26); }
    .ap-btn.ghost { background: #fff; color: var(--ap-muted); border-color: var(--ap-line); width: 42px; padding: 0; }

    /* ── Tabel (desktop) ── */
    .ap-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; font-size: 12.5px; }
    .ap-table thead th {
        padding: 11px 12px; background: var(--ap-soft); border-top: 1px solid var(--ap-line); border-bottom: 1px solid var(--ap-line);
        font-size: 10.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--ap-muted); text-align: left;
    }
    .ap-table td { padding: 14px 12px; vertical-align: middle; border-bottom: 1px dashed var(--ap-line); overflow-wrap: anywhere; }
    .ap-table tbody tr:last-child td { border-bottom: 0; }
    .ap-table tbody tr[data-laporan-row]:hover { background: #fafcff; }
    .ap-w-no { width: 4%; } .ap-w-p { width: 24%; } .ap-w-s { width: 21%; } .ap-w-t { width: 16%; } .ap-w-l { width: 16%; } .ap-w-a { width: 19%; }

    .ap-no { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 8px; background: var(--ap-soft); color: var(--ap-muted); font-size: 11px; font-weight: 800; }
    .ap-person { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .ap-avatar {
        width: 38px; height: 38px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        font-size: 15px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #2f7cf0, var(--ap-blue));
    }
    .ap-name { font-weight: 800; font-size: 13px; line-height: 1.3; }
    .ap-user { display: inline-block; margin-top: 3px; padding: 1px 7px; border-radius: 6px; background: var(--ap-soft); color: #4a5a72; font-size: 11px; font-weight: 700; }
    .ap-strong { font-weight: 700; line-height: 1.35; }
    .ap-mut { color: var(--ap-muted); font-size: 11.5px; margin-top: 2px; line-height: 1.35; }
    .ap-badge { display: inline-block; margin-top: 6px; padding: 3px 9px; border-radius: 999px; background: #e8f0ff; color: var(--ap-blue); font-size: 10.5px; font-weight: 800; }
    .ap-dash { color: var(--ap-muted); }
    .ap-doc {
        display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 12px; border-radius: 11px;
        background: #fdecee; color: #c62828; font-size: 12px; font-weight: 800; text-decoration: none;
    }
    .ap-doc i { font-size: 18px; }
    .ap-doc:hover { background: #fadadd; color: #a11d28; }

    /* ── Aksi ── */
    .ap-act { display: flex; gap: 8px; }
    .ap-form { margin: 0; }
    .ap-act-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 5px; height: 38px; padding: 0 14px;
        border-radius: 12px; border: 1px solid transparent; cursor: pointer; font-size: 12.5px; font-weight: 800;
        white-space: nowrap; transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .ap-act-btn i { font-size: 18px; }
    .ap-act-btn:active { transform: scale(.96); }
    .ap-act-btn.ok { background: #198754; color: #fff; box-shadow: 0 5px 12px rgba(25, 135, 84, .28); }
    .ap-act-btn.ok:hover { background: #157347; }
    .ap-act-btn.no { background: #fff; color: #dc3545; border-color: #f1b4ba; }
    .ap-act-btn.no:hover { background: #fdecee; }
    .ap-act-btn:disabled { opacity: .6; cursor: not-allowed; }
    .ap-act-btn:focus-visible, .ap-doc:focus-visible, .ap-btn:focus-visible { outline: 3px solid rgba(47, 124, 240, .45); outline-offset: 2px; }

    .ap-empty-td { text-align: center; color: var(--ap-muted); padding: 40px 16px !important; font-size: 13px; font-weight: 600; }
    .ap-empty-td i { display: block; font-size: 38px; color: #b8c4d6; margin-bottom: 6px; }

    /* ── Footer / pagination ── */
    .ap-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 14px 18px; border-top: 1px solid var(--ap-line); }
    .ap-foot small { font-size: 12px; color: var(--ap-muted); font-weight: 600; }
    .ap-pg { display: flex; gap: 6px; list-style: none; margin: 0; padding: 0; }
    .ap-pg a, .ap-pg span {
        min-width: 36px; height: 36px; padding: 0 8px; border-radius: 11px; display: flex; align-items: center; justify-content: center;
        font-size: 12.5px; font-weight: 800; text-decoration: none; background: #fff; color: var(--ap-blue); border: 1px solid var(--ap-line);
    }
    .ap-pg i { font-size: 19px; }
    .ap-pg .on { background: linear-gradient(135deg, #2f7cf0, var(--ap-blue)); color: #fff; border-color: transparent; box-shadow: 0 5px 12px rgba(11, 79, 179, .25); }
    .ap-pg .off { color: #c3cddb; background: var(--ap-soft); }

    /* ── Tablet & HP: tabel berubah jadi kartu, tanpa scroll samping ── */
    @media (max-width: 1199.98px) {
        .ap-table thead { display: none; }
        .ap-table, .ap-table tbody, .ap-table tr, .ap-table td { display: block; width: 100%; }
        .ap-table tbody { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px; padding: 0 18px 18px; }
        .ap-table tbody tr:not([data-laporan-row]) { grid-column: 1 / -1; }
        .ap-table td { padding: 0; border: 0; }

        .ap-table tr[data-laporan-row] {
            position: relative; display: grid; grid-template-columns: 1fr 1fr; gap: 12px 14px; padding: 14px;
            border: 1px solid var(--ap-line); border-left: 5px solid #ffc107; border-radius: 18px; background: #fff;
            box-shadow: 0 6px 18px rgba(24, 60, 120, .06);
        }
        .ap-table tr[data-laporan-row]:hover { background: #fff; }
        .ap-table td[data-label]::before {
            content: attr(data-label); display: block; margin-bottom: 4px;
            font-size: 9.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--ap-muted);
        }
        .ap-table td.ap-c-no { position: absolute; top: 14px; right: 14px; width: auto; }
        .ap-table td.ap-c-p, .ap-table td.ap-c-l, .ap-table td.ap-c-a { grid-column: 1 / -1; }
        .ap-table td.ap-c-p { padding-right: 40px; }
        .ap-table td.ap-c-p::before, .ap-table td.ap-c-a::before { display: none; }
        .ap-table td.ap-c-l { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 10px 12px; border-radius: 14px; background: var(--ap-soft); border: 1px solid var(--ap-line); }
        .ap-table td.ap-c-l::before { width: 100%; margin: 0; }
        .ap-table td.ap-c-a { padding-top: 12px; border-top: 1px dashed var(--ap-line); }
        .ap-act { width: 100%; }
        .ap-act .ap-form { flex: 1; }
        .ap-act-btn { width: 100%; height: 44px; font-size: 13.5px; }
    }
    @media (max-width: 991.98px) {
        .ap-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .ap-title { font-size: 19px; }
        .ap-stat { padding: 12px; gap: 10px; }
        .ap-stat-ico { width: 38px; height: 38px; font-size: 20px; }
        .ap-stat-n { font-size: 21px; }
        .ap-card-head, .ap-search, .ap-foot { padding-left: 14px; padding-right: 14px; }
        .ap-table tbody { padding: 0 14px 14px; grid-template-columns: 1fr; }
        .ap-table tr[data-laporan-row] { grid-template-columns: 1fr; }
        .ap-field { max-width: none; flex-basis: 100%; }
        .ap-search .ap-btn.primary { flex: 1; }
        .ap-foot { justify-content: center; }
    }
    @media (prefers-reduced-motion: reduce) {
        .ap-btn, .ap-act-btn, .ap-input { transition: none; }
    }
</style>

<div class="ap">

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Approval</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url('manager') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Approval Laporan Manager</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Judul -->
<div class="ap-head">
    <div>
        <h1 class="ap-title">Approval Laporan Manager</h1>
        <p class="ap-sub">Tinjau laporan magang yang sudah disetujui SDM, lalu setujui atau tolak.</p>
    </div>
    <span class="ap-tag"><i class="bx bx-check-shield"></i> Review Tingkat Manager</span>
</div>

<!-- SUMMARY CARDS -->
<div class="ap-stats">
    <div class="ap-stat ap-blue">
        <div class="ap-stat-ico"><i class="bx bx-file"></i></div>
        <div>
            <div class="ap-stat-n" data-counter="total">{{ number_format($sum->total ?? 0, 0, ',', '.') }}</div>
            <div class="ap-stat-l">Total Laporan</div>
        </div>
    </div>
    <div class="ap-stat ap-amber">
        <div class="ap-stat-ico"><i class="bx bx-time-five"></i></div>
        <div>
            <div class="ap-stat-n" data-counter="menunggu_manager">{{ number_format($sum->menunggu ?? 0, 0, ',', '.') }}</div>
            <div class="ap-stat-l">Menunggu Review</div>
        </div>
    </div>
    <div class="ap-stat ap-green">
        <div class="ap-stat-ico"><i class="bx bx-check-circle"></i></div>
        <div>
            <div class="ap-stat-n" data-counter="disetujui">{{ number_format($sum->disetujui ?? 0, 0, ',', '.') }}</div>
            <div class="ap-stat-l">Disetujui</div>
        </div>
    </div>
    <div class="ap-stat ap-red">
        <div class="ap-stat-ico"><i class="bx bx-x-circle"></i></div>
        <div>
            <div class="ap-stat-n" data-counter="ditolak">{{ number_format($sum->ditolak ?? 0, 0, ',', '.') }}</div>
            <div class="ap-stat-l">Ditolak</div>
        </div>
    </div>
</div>

<div class="ap-card">
    <div class="ap-card-head">
        <h5 class="ap-card-title"><i class="bx bx-list-check"></i> Daftar Menunggu Persetujuan</h5>
        <span class="ap-pill">{{ $total }} antrean</span>
    </div>

    <form method="GET" action="{{ url('manager/approval-laporan') }}" class="ap-search" role="search">
        <div class="ap-field">
            <i class="bx bx-search"></i>
            <input type="text" name="q" class="ap-input" placeholder="Cari nama / username..." value="{{ $search }}" aria-label="Cari nama atau username">
        </div>
        <button type="submit" class="ap-btn primary"><i class="bx bx-filter-alt"></i> Cari</button>
        <a href="{{ url('manager/approval-laporan') }}" class="ap-btn ghost" title="Reset pencarian" aria-label="Reset pencarian"><i class="bx bx-reset"></i></a>
    </form>

    <table class="ap-table">
        <thead>
            <tr>
                <th class="ap-w-no">#</th>
                <th class="ap-w-p">Peserta</th>
                <th class="ap-w-s">Asal Sekolah</th>
                <th class="ap-w-t">Periode &amp; Bidang</th>
                <th class="ap-w-l">Laporan</th>
                <th class="ap-w-a">Aksi</th>
            </tr>
        </thead>
        <tbody data-empty-colspan="6" data-empty-text="Tidak ada laporan yang menunggu approval manager.">
        @php
        $no = $offset + 1;
        @endphp
        @foreach ($data as $row)
            <tr data-laporan-row data-laporan-id="{{ $row->id }}">
                <td class="ap-c-no"><span class="ap-no">{{ $no++ }}</span></td>

                <td class="ap-c-p" data-label="Peserta">
                    <div class="ap-person">
                        <div class="ap-avatar">{{ mb_strtoupper(mb_substr($row->nama ?? '?', 0, 1)) }}</div>
                        <div style="min-width:0;">
                            <div class="ap-name">{{ $row->nama }}</div>
                            <span class="ap-user">{{ $row->username }}</span>
                        </div>
                    </div>
                </td>

                <td class="ap-c-s" data-label="Asal Sekolah">
                    <div class="ap-strong">{{ $row->asal_sekolah }}</div>
                    <div class="ap-mut">{{ $row->jurusan }}</div>
                </td>

                <td class="ap-c-t" data-label="Periode &amp; Bidang">
                    <div class="ap-strong">{{ $ap_tgl($row->tgl_masuk) }}</div>
                    <div class="ap-mut">s/d {{ $ap_tgl($row->tgl_keluar) }}</div>
                    @if ($row->nama_bidang)
                        <span class="ap-badge">{{ $row->nama_bidang }}</span>
                    @else
                        <div class="ap-dash">-</div>
                    @endif
                </td>

                <td class="ap-c-l" data-label="Laporan">
                    <div>
                        <a href="{{ asset('uploads/laporan/' . $row->file_laporan) }}" target="_blank" class="ap-doc" title="Buka laporan">
                            <i class="bx bxs-file-pdf"></i> Lihat
                        </a>
                    </div>
                    <div class="ap-mut" style="margin:0;">
                        Review SDM: {{ $ap_tgl($row->sdm_tgl_review, true) }}<br>
                        oleh {{ $row->sdm_reviewer_nama ?? '-' }}
                    </div>
                </td>

                <td class="ap-c-a">
                    <div class="ap-act">
                        <form method="POST" action="{{ url('manager/approval-laporan/approve') }}" class="ap-form js-approval-form"
                              data-action="approve" data-nama="{{ $row->nama }}"
                              data-counters='{"menunggu_manager":-1,"disetujui":1}'>
                            @csrf
                            <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                            <input type="hidden" name="username" value="{{ $row->username }}">
                            <button type="submit" class="ap-act-btn ok" title="Setujui & Terbitkan Sertifikat">
                                <i class="bx bx-check"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ url('manager/approval-laporan/reject') }}" class="ap-form js-approval-form"
                              data-action="reject" data-nama="{{ $row->nama }}"
                              data-counters='{"menunggu_manager":-1,"ditolak":1}'>
                            @csrf
                            <input type="hidden" name="laporan_id" value="{{ $row->id }}">
                            <button type="submit" class="ap-act-btn no" title="Tolak">
                                <i class="bx bx-x"></i> Tolak
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        @if ($total == 0)
            <tr>
                <td colspan="6" class="ap-empty-td">
                    <i class="bx bx-file-blank"></i>
                    Tidak ada laporan yang menunggu approval manager.
                </td>
            </tr>
        @endif
        </tbody>
    </table>

    @if ($total_page > 1)
    <div class="ap-foot">
        <small>Menampilkan {{ $offset + 1 }}-{{ min($offset + $per_page, $total) }} dari {{ $total }}</small>
        <nav aria-label="Halaman">
            <ul class="ap-pg">
                <li>
                    @if ($page <= 1)
                        <span class="off"><i class="bx bx-chevron-left"></i></span>
                    @else
                        <a href="{{ url('manager/approval-laporan') }}?p={{ $page-1 }}&q={{ urlencode($search) }}" aria-label="Sebelumnya"><i class="bx bx-chevron-left"></i></a>
                    @endif
                </li>
                @for ($i = max(1, $page-2); $i <= min($total_page, $page+2); $i++)
                    <li>
                        <a class="{{ $i==$page ? 'on' : '' }}" href="{{ url('manager/approval-laporan') }}?p={{ $i }}&q={{ urlencode($search) }}">{{ $i }}</a>
                    </li>
                @endfor
                <li>
                    @if ($page >= $total_page)
                        <span class="off"><i class="bx bx-chevron-right"></i></span>
                    @else
                        <a href="{{ url('manager/approval-laporan') }}?p={{ $page+1 }}&q={{ urlencode($search) }}" aria-label="Berikutnya"><i class="bx bx-chevron-right"></i></a>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
    @endif
</div>

</div>
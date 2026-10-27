@php
    // Turunan untuk tampilan saja (dari data $kq yang sudah ada)
    $kq_total  = max(1, (int) $kq['total']);
    $kq_p_akt  = round($kq['aktif'] / $kq_total * 100);
    $kq_p_tgu  = round($kq['menunggu'] / $kq_total * 100);

    // Nama filter yang sedang aktif
    $kq_unit_aktif   = $kq['unit'] ?: null;
    $kq_bidang_aktif = null;
    if ($kq['bidangId']) {
        foreach ($kq['bidang'] as $bb) {
            if ((int) $bb->id === (int) $kq['bidangId']) { $kq_bidang_aktif = $bb->bidang; break; }
        }
    }
    $kq_ada_filter = $kq_unit_aktif || $kq_bidang_aktif;
@endphp

<div class="kq">

    {{-- Judul --}}
    <div class="kq-head">
        <div class="kq-head-ico"><i class="bx bx-bar-chart-square"></i></div>
        <div style="min-width:0;">
            <h1 class="kq-title">Kuota &amp; Peserta Magang</h1>
            <p class="kq-sub-title">Data mulai Juli 2026; breakdown bulanan menghitung peserta Aktif dan Menunggu yang masih dalam periode magang pada bulan tersebut.</p>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="kq-stats">
        <div class="kq-stat" style="--c:#198754; --bg:#e1f2e9;">
            <div class="kq-stat-ico"><i class="bx bx-user-check"></i></div>
            <div class="kq-stat-b">
                <div class="kq-stat-label">Total Aktif</div>
                <div class="kq-stat-num">{{ number_format($kq['aktif']) }}</div>
                <span class="kq-chip">{{ $kq_p_akt }}% dari total</span>
            </div>
        </div>
        <div class="kq-stat" style="--c:#e0a800; --bg:#fff3cd;">
            <div class="kq-stat-ico"><i class="bx bx-time-five"></i></div>
            <div class="kq-stat-b">
                <div class="kq-stat-label">Total Menunggu</div>
                <div class="kq-stat-num">{{ number_format($kq['menunggu']) }}</div>
                <span class="kq-chip">{{ $kq_p_tgu }}% dari total</span>
            </div>
        </div>
        <div class="kq-stat" style="--c:#0b6bcb; --bg:#e7f1ff;">
            <div class="kq-stat-ico"><i class="bx bx-group"></i></div>
            <div class="kq-stat-b">
                <div class="kq-stat-label">Total Keseluruhan</div>
                <div class="kq-stat-num">{{ number_format($kq['total']) }}</div>
                <span class="kq-chip">Aktif + Menunggu</span>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ url()->current() }}" class="kq-card kq-filter">
        <div class="kq-card-t"><i class="bx bx-filter-alt"></i> Filter Data</div>
        <div class="kq-filter-grid">
            <div class="kq-field">
                <label for="kq-unit" class="kq-label">Unit</label>
                <div class="kq-select">
                    <i class="bx bx-buildings"></i>
                    <select name="unit" id="kq-unit">
                        <option value="">Semua Unit</option>
                        @foreach ($kq['units'] as $u)
                            <option value="{{ $u }}" {{ $kq['unit'] === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="kq-field">
                <label for="kq-bidang" class="kq-label">Bidang</label>
                <div class="kq-select">
                    <i class="bx bx-briefcase"></i>
                    <select name="bidang_id" id="kq-bidang">
                        <option value="">Semua Bidang</option>
                        @foreach ($kq['bidang'] as $b)
                            <option value="{{ $b->id }}" {{ $kq['bidangId'] === (int) $b->id ? 'selected' : '' }}>{{ $b->bidang }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="kq-actions">
                <button type="submit" class="kq-btn primary"><i class="bx bx-search"></i> Terapkan</button>
                <a href="{{ url()->current() }}" class="kq-btn ghost" title="Reset filter" aria-label="Reset filter"><i class="bx bx-sync pkl-sync pkl-sync--spin"></i></a>
            </div>
        </div>

        @if ($kq_ada_filter)
        <div class="kq-active">
            <span class="kq-active-l">Filter aktif:</span>
            @if ($kq_unit_aktif)<span class="kq-tag"><i class="bx bx-buildings"></i> {{ $kq_unit_aktif }}</span>@endif
            @if ($kq_bidang_aktif)<span class="kq-tag"><i class="bx bx-briefcase"></i> {{ $kq_bidang_aktif }}</span>@endif
        </div>
        @endif
    </form>

    {{-- Breakdown per bulan --}}
    <div class="kq-card">
        <div class="kq-card-h">
            <div>
                <div class="kq-card-t" style="margin:0;"><i class="bx bx-calendar"></i> Breakdown Peserta Per Bulan</div>
                <p class="kq-card-s">Peserta Aktif dan Menunggu dihitung selama periode masuk–selesai magang. Pilih salah satu bulan untuk melihat daftarnya.</p>
            </div>
            @if (count($kq['bulan']) > 0)
                <span class="kq-pill">{{ count($kq['bulan']) }} bulan</span>
            @endif
        </div>

        @if (count($kq['bulan']) === 0)
            <div class="kq-empty">
                <div class="kq-empty-ico"><i class="bx bx-calendar-x"></i></div>
                <div class="kq-empty-t">Tidak ada data</div>
                <div class="kq-empty-s">Tidak ada peserta dengan periode magang untuk filter ini.</div>
            </div>
        @else
            <div class="kq-grid">
                @foreach ($kq['bulan'] as $b)
                    <button type="button" class="kq-month {{ (int) $b['jumlah'] === (int) $kq['maks'] ? 'is-top' : '' }}"
                            data-ym="{{ $b['ym'] }}" data-label="{{ $b['label'] }}" aria-pressed="false">
                        <span class="kq-month-head">
                            <span class="kq-month-name">{{ $b['label'] }}</span>
                            <span class="kq-tags">
                                <span class="kq-sel"><i class="bx bx-check-circle"></i> Dipilih</span>
                                @if ((int) $b['jumlah'] === (int) $kq['maks'])
                                    <span class="kq-top"><i class="bx bxs-up-arrow"></i> Tertinggi</span>
                                @endif
                            </span>
                        </span>
                        <span class="kq-month-num">{{ $b['jumlah'] }}<small>peserta</small></span>
                        <span class="kq-bar" role="progressbar" aria-label="Peserta {{ $b['label'] }}"
                              aria-valuenow="{{ $b['jumlah'] }}" aria-valuemin="0" aria-valuemax="{{ $kq['maks'] }}">
                            <span style="width: {{ $b['persen'] }}%"></span>
                        </span>
                        <span class="kq-month-foot">
                            <span>{{ $b['persen'] }}% dari bulan tertinggi</span>
                            <i class="bx bx-chevron-down"></i>
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Daftar peserta bulan terpilih (di bawah grid bulan) --}}
    @if (count($kq['bulan']) > 0)
    <div class="kq-card kq-list" id="kq-list">
        <div class="kq-list-head">
            <div class="kq-list-ttl">
                <div class="kq-list-ico"><i class="bx bx-calendar-check"></i></div>
                <div style="min-width:0;">
                    <h2 class="kq-list-t" id="kq-list-title">Daftar Peserta</h2>
                    <p class="kq-card-s" id="kq-list-sub"></p>
                </div>
            </div>
            <div class="kq-list-tools">
                <span class="kq-pill" id="kq-list-count"></span>
                <div class="kq-search">
                    <i class="bx bx-search"></i>
                    <input type="search" id="kq-search" placeholder="Cari nama, sekolah, bidang..." autocomplete="off" aria-label="Cari peserta">
                </div>
            </div>
        </div>

        <div class="kq-table-wrap">
            <table class="kq-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Bidang &amp; Unit</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="kq-rows"></tbody>
            </table>
        </div>

        <div class="kq-empty" id="kq-list-empty" hidden>
            <div class="kq-empty-ico"><i class="bx bx-user-x"></i></div>
            <div class="kq-empty-t">Tidak ada peserta</div>
            <div class="kq-empty-s" id="kq-list-empty-s">Tidak ada peserta yang cocok dengan pencarian.</div>
        </div>

        <div class="kq-pager">
            <div class="kq-pager-info" id="kq-pager-info"></div>
            <div class="kq-pager-btns" id="kq-pager" role="navigation" aria-label="Halaman daftar peserta"></div>
        </div>
    </div>
    @endif
</div>

<style>
    .kq { --kq-blue: var(--pln-blue, #0b6bcb); --kq-ink: #16233b; --kq-muted: #6b7a90; --kq-line: #e6ecf5; --kq-soft: #f4f7fc; color: var(--kq-ink); }
    .kq * { box-sizing: border-box; }

    /* ── Judul ── */
    .kq-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .kq-head-ico { width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; font-size: 21px; display: flex; align-items: center; justify-content: center; background: #e7f1ff; color: var(--kq-blue); }
    .kq-title { font-size: 20px; font-weight: 800; line-height: 1.2; margin: 0; }
    .kq-sub-title { font-size: 12px; color: var(--kq-muted); margin: 2px 0 0; }

    /* ── Ringkasan ── */
    .kq-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
    .kq-stat {
        position: relative; overflow: hidden; display: flex; align-items: center; gap: 9px; padding: 9px; border-radius: 12px;
        background: #fff; border: 1px solid var(--kq-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .kq-stat::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: var(--c); }
    .kq-stat-ico { width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0; font-size: 18px; display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--c); }
    .kq-stat-b { min-width: 0; }
    .kq-stat-label { font-size: 10.5px; font-weight: 700; color: var(--kq-muted); }
    .kq-stat-num { font-size: 24px; font-weight: 800; line-height: 1.1; margin: 1px 0 2px; color: var(--c); }
    .kq-chip { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9.5px; font-weight: 800; background: var(--bg); color: var(--c); }

    /* ── Kartu umum ── */
    .kq-card { background: #fff; border-radius: 16px; border: 1px solid var(--kq-line); box-shadow: 0 4px 16px rgba(24, 60, 120, .05); padding: 14px; margin-bottom: 12px; }
    .kq-card-h { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 12px; }
    .kq-card-t { font-size: 13.5px; font-weight: 800; display: flex; align-items: center; gap: 7px; margin-bottom: 10px; }
    .kq-card-t i { color: var(--kq-blue); font-size: 18px; }
    .kq-card-s { font-size: 11px; color: var(--kq-muted); margin: 3px 0 0; }
    .kq-pill { font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 999px; background: #e7f1ff; color: var(--kq-blue); white-space: nowrap; }

    /* ── Filter ── */
    .kq-filter-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end; }
    .kq-label { display: block; font-size: 11px; font-weight: 700; color: var(--kq-muted); margin-bottom: 5px; }
    .kq-select { position: relative; }
    .kq-select > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: 17px; color: var(--kq-muted); pointer-events: none; }
    .kq-select select {
        width: 100%; height: 40px; border-radius: 10px; border: 1px solid var(--kq-line); background: #fff; color: var(--kq-ink);
        padding: 0 12px 0 36px; font-size: 12.5px; font-weight: 600; outline: none; appearance: auto;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .kq-select select:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .14); }
    .kq-actions { display: flex; gap: 7px; }
    .kq-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 5px; height: 40px; padding: 0 14px; border-radius: 10px;
        border: 1px solid transparent; font-size: 12.5px; font-weight: 800; text-decoration: none; cursor: pointer; transition: transform .15s ease;
    }
    .kq-btn:active { transform: scale(.97); }
    .kq-btn i { font-size: 17px; }
    .kq-btn.primary { background: linear-gradient(135deg, #2f7cf0, #0b4fb3); color: #fff; box-shadow: 0 6px 14px rgba(11, 79, 179, .26); }
    .kq-btn.ghost { background: #fff; color: var(--kq-muted); border-color: var(--kq-line); width: 40px; padding: 0; }
    .kq-active { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--kq-line); }
    .kq-active-l { font-size: 11px; font-weight: 700; color: var(--kq-muted); }
    .kq-tag { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; background: #e7f1ff; color: var(--kq-blue); font-size: 11px; font-weight: 700; }
    .kq-tag i { font-size: 14px; }

    /* ── Tile bulan ── */
    .kq-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 10px; }
    .kq-month {
        display: flex; flex-direction: column; gap: 8px; width: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer;
        border: 1px solid var(--kq-line); border-radius: 15px; padding: 12px; background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .kq-month:hover { border-color: #9cc2f5; box-shadow: 0 8px 20px rgba(11, 107, 203, .14); transform: translateY(-2px); }
    .kq-month:active { transform: scale(.98); }
    .kq-month:focus-visible { outline: 3px solid var(--kq-blue); outline-offset: 2px; }
    .kq-month.is-top { border-color: #cfe0f7; background: linear-gradient(180deg, #f7faff, #fff); }
    .kq-month.is-active {
        border-color: var(--kq-blue); background: linear-gradient(180deg, #e6f0ff, #fff);
        box-shadow: 0 0 0 3px rgba(11, 107, 203, .16), 0 8px 20px rgba(11, 107, 203, .14);
    }
    .kq-month-head { display: flex; align-items: center; justify-content: space-between; gap: 5px; min-height: 20px; flex-wrap: wrap; }
    .kq-month-name { font-size: 12px; font-weight: 800; color: #41566e; }
    .kq-month.is-active .kq-month-name { color: var(--kq-blue); }
    .kq-tags { display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap; }
    .kq-sel { display: none; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 999px; background: var(--kq-blue); color: #fff; font-size: 9.5px; font-weight: 800; white-space: nowrap; }
    .kq-sel i { font-size: 11px; }
    .kq-month.is-active .kq-sel { display: inline-flex; }
    .kq-top { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; border-radius: 999px; background: #d9f0e3; color: #0f5132; font-size: 9.5px; font-weight: 800; white-space: nowrap; }
    .kq-top i { font-size: 10px; }
    .kq-month-num { font-size: 24px; font-weight: 800; line-height: 1; color: var(--kq-blue); }
    .kq-month-num small { font-size: 10.5px; font-weight: 600; color: var(--kq-muted); margin-left: 5px; }
    .kq-bar { display: block; height: 7px; border-radius: 999px; background: #e6edf5; overflow: hidden; }
    .kq-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #4d94ff, var(--kq-blue)); transition: width .5s cubic-bezier(.4, 0, .2, 1); }
    .kq-month-foot { display: flex; align-items: center; justify-content: space-between; gap: 5px; font-size: 10px; color: var(--kq-muted); font-weight: 600; }
    .kq-month-foot i { font-size: 16px; color: var(--kq-blue); transition: transform .2s ease; }
    .kq-month.is-active .kq-month-foot i { transform: rotate(180deg); }

    /* ── Kosong ── */
    .kq-empty { text-align: center; padding: 28px 16px; }
    .kq-empty[hidden] { display: none; }
    .kq-empty-ico { width: 52px; height: 52px; border-radius: 16px; margin: 0 auto 10px; font-size: 26px; display: flex; align-items: center; justify-content: center; background: #e9edf3; color: #8a98ad; }
    .kq-empty-t { font-size: 14px; font-weight: 800; }
    .kq-empty-s { font-size: 11.5px; color: var(--kq-muted); margin-top: 4px; }

    /* ── Daftar peserta ── */
    .kq-list { scroll-margin-top: 80px; }
    .kq-list-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
    .kq-list-ttl { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .kq-list-ico { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; font-size: 19px; display: flex; align-items: center; justify-content: center; background: #e7f1ff; color: var(--kq-blue); }
    .kq-list-t { font-size: 15px; font-weight: 800; margin: 0; line-height: 1.25; }
    .kq-list-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .kq-search { position: relative; }
    .kq-search > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: 16px; color: var(--kq-muted); pointer-events: none; }
    .kq-search input {
        width: 220px; max-width: 100%; height: 36px; border-radius: 10px; border: 1px solid var(--kq-line); background: var(--kq-soft);
        padding: 0 10px 0 34px; font-size: 12.5px; color: var(--kq-ink); outline: none; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .kq-search input:focus { background: #fff; border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .14); }

    .kq-table-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid var(--kq-line); }
    .kq-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12px; min-width: 700px; }
    .kq-table thead th { background: var(--kq-soft); padding: 9px 10px; font-size: 9.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; color: var(--kq-muted); text-align: left; white-space: nowrap; border-bottom: 1px solid var(--kq-line); }
    .kq-table td { padding: 9px 10px; vertical-align: middle; border-bottom: 1px dashed var(--kq-line); font-size: 12px; }
    .kq-table tbody tr:hover { background: #fafcff; }
    .kq-table tbody tr:last-child td { border-bottom: 0; }
    .kq-table td:first-child { color: var(--kq-muted); font-weight: 700; width: 38px; }

    .kq-person { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .kq-ava { width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: var(--ac); background: var(--abg); }
    .kq-name { font-weight: 800; line-height: 1.25; }
    .kq-sub { display: block; font-size: 10.5px; color: var(--kq-muted); margin-top: 2px; font-weight: 500; }
    .kq-strong { font-weight: 700; line-height: 1.3; }
    .kq-date { white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }
    .kq-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; white-space: nowrap; }
    .kq-badge.aktif { background: #d9f0e3; color: #0f5132; }
    .kq-badge.menunggu { background: #fff1c9; color: #7a5800; }

    /* ── Paginasi ── */
    .kq-pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--kq-line); }
    .kq-pager-info { font-size: 11px; color: var(--kq-muted); font-weight: 600; }
    .kq-pager-btns { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .kq-pg {
        min-width: 30px; height: 30px; padding: 0 8px; border-radius: 9px; border: 1px solid var(--kq-line); background: #fff; color: var(--kq-ink);
        font-size: 12px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all .15s ease;
    }
    .kq-pg:hover:not(:disabled):not(.is-cur) { border-color: #9cc2f5; color: var(--kq-blue); }
    .kq-pg:focus-visible { outline: 3px solid var(--kq-blue); outline-offset: 1px; }
    .kq-pg.is-cur { background: linear-gradient(135deg, #2f7cf0, #0b4fb3); border-color: transparent; color: #fff; box-shadow: 0 5px 12px rgba(11, 79, 179, .26); cursor: default; }
    .kq-pg:disabled { opacity: .4; cursor: not-allowed; }
    .kq-pg i { font-size: 16px; }
    .kq-dots { color: var(--kq-muted); padding: 0 2px; font-weight: 800; }

    @@media (max-width: 991.98px) {
        .kq-filter-grid { grid-template-columns: 1fr 1fr; }
        .kq-actions { grid-column: 1 / -1; }
        .kq-actions .kq-btn.primary { flex: 1; }
    }
    @@media (max-width: 767.98px) {
        .kq-stats { grid-template-columns: 1fr; }
        .kq-stat-num { font-size: 21px; }
        .kq-filter-grid { grid-template-columns: 1fr; }
        .kq-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .kq-month { padding: 12px; }
        .kq-month-num { font-size: 22px; }
        .kq-title { font-size: 20px; }
        .kq-card { padding: 14px; }

        .kq-list-tools { width: 100%; }
        .kq-list-tools .kq-search { flex: 1; }
        .kq-search input { width: 100%; }

        /* Tabel menjadi kartu */
        .kq-table-wrap { border: 0; overflow: visible; }
        .kq-table { min-width: 0; }
        .kq-table thead { display: none; }
        .kq-table, .kq-table tbody, .kq-table tr, .kq-table td { display: block; width: 100%; }
        .kq-table tbody tr { position: relative; margin-top: 10px; padding: 12px 14px; border: 1px solid var(--kq-line); border-radius: 16px; background: #fff; }
        .kq-table td { padding: 5px 0; border: 0; }
        .kq-table td[data-label]::before { content: attr(data-label); display: block; font-size: 9.5px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; color: var(--kq-muted); margin-bottom: 1px; }
        .kq-table td:first-child { position: absolute; top: 14px; right: 14px; width: auto; padding: 0; }
        .kq-table td:first-child::before { display: none; }
        .kq-table td:nth-child(2)::before { display: none; }
        .kq-table td:nth-child(2) { padding: 0 34px 8px 0; margin-bottom: 4px; border-bottom: 1px dashed var(--kq-line); }

        .kq-pager { justify-content: center; text-align: center; }
        .kq-pager-info { width: 100%; }
        .kq-pager-btns { justify-content: center; }
    }
    @@media (max-width: 380px) {
        .kq-grid { grid-template-columns: 1fr; }
        .kq-pg { min-width: 30px; height: 30px; padding: 0 7px; }
    }
    @@media (prefers-reduced-motion: reduce) {
        .kq-month, .kq-btn, .kq-bar span, .kq-pg, .kq-month-foot i { transition: none; }
    }
</style>

<script>
(function () {
    var data = {{ \Illuminate\Support\Js::from($kq['detail']) }};
    var tiles = document.querySelectorAll('.kq-month');
    var listEl = document.getElementById('kq-list');
    if (!tiles.length || !listEl) return;

    var PER_PAGE = 6; // jumlah baris per halaman

    var rowsEl  = document.getElementById('kq-rows');
    var titleEl = document.getElementById('kq-list-title');
    var subEl   = document.getElementById('kq-list-sub');
    var countEl = document.getElementById('kq-list-count');
    var infoEl  = document.getElementById('kq-pager-info');
    var pagerEl = document.getElementById('kq-pager');
    var emptyEl = document.getElementById('kq-list-empty');
    var emptyS  = document.getElementById('kq-list-empty-s');
    var searchEl = document.getElementById('kq-search');
    var tableWrap = listEl.querySelector('.kq-table-wrap');

    var state = { ym: null, label: '', page: 1, q: '' };

    // Warna avatar (latar, teks) berdasarkan nama
    var PALETTE = [
        ['#e7f1ff', '#0b4fb3'], ['#d9f0e3', '#0f5132'], ['#fff1c9', '#7a5800'],
        ['#efe6ff', '#5b2fa8'], ['#d5f1f8', '#055160'], ['#fde0e0', '#a01c1c']
    ];
    function palette(name) {
        var h = 0;
        for (var i = 0; i < name.length; i++) h = (h * 31 + name.charCodeAt(i)) % 997;
        return PALETTE[h % PALETTE.length];
    }

    function cell(tr, text, label, cls) {
        var td = document.createElement('td');
        td.textContent = text;
        if (label) td.setAttribute('data-label', label);
        if (cls) td.className = cls;
        tr.appendChild(td);
        return td;
    }

    function matches(p, q) {
        if (!q) return true;
        var hay = [p.nama, p.sekolah, p.jurusan, p.bidang, p.unit].join(' ').toLowerCase();
        return hay.indexOf(q) !== -1;
    }

    function pageList(cur, total) {
        if (total <= 7) {
            var all = [];
            for (var i = 1; i <= total; i++) all.push(i);
            return all;
        }
        var out = [1];
        var from = Math.max(2, cur - 1), to = Math.min(total - 1, cur + 1);
        if (from > 2) out.push('…');
        for (var j = from; j <= to; j++) out.push(j);
        if (to < total - 1) out.push('…');
        out.push(total);
        return out;
    }

    function pageBtn(content, page, opts) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'kq-pg' + (opts && opts.current ? ' is-cur' : '');
        if (opts && opts.html) b.innerHTML = content; else b.textContent = content;
        if (opts && opts.label) b.setAttribute('aria-label', opts.label);
        if (opts && opts.current) b.setAttribute('aria-current', 'page');
        if (opts && opts.disabled) b.disabled = true;
        else if (!(opts && opts.current)) b.addEventListener('click', function () { state.page = page; render(true); });
        return b;
    }

    function render(keepScroll) {
        var all  = data[state.ym] || [];
        var q    = state.q;
        var list = all.filter(function (p) { return matches(p, q); });

        var totalPages = Math.max(1, Math.ceil(list.length / PER_PAGE));
        if (state.page > totalPages) state.page = totalPages;
        if (state.page < 1) state.page = 1;

        var start = (state.page - 1) * PER_PAGE;
        var slice = list.slice(start, start + PER_PAGE);

        var aktif = 0, menunggu = 0;
        all.forEach(function (p) { if (p.status === 'Aktif') aktif++; else menunggu++; });

        titleEl.textContent = 'Daftar Peserta — ' + state.label;
        subEl.textContent = all.length + ' peserta · ' + aktif + ' Aktif · ' + menunggu + ' Menunggu';
        countEl.textContent = all.length + ' peserta';

        // Baris tabel
        rowsEl.textContent = '';
        slice.forEach(function (p, i) {
            var tr = document.createElement('tr');
            cell(tr, start + i + 1, '#');

            var tdP = document.createElement('td');
            var wrap = document.createElement('div');
            wrap.className = 'kq-person';
            var nama = (p.nama || '').trim();
            var pal = palette(nama);
            var ava = document.createElement('span');
            ava.className = 'kq-ava';
            ava.style.setProperty('--abg', pal[0]);
            ava.style.setProperty('--ac', pal[1]);
            ava.textContent = (nama.charAt(0) || '?').toUpperCase();
            var info = document.createElement('div');
            info.style.minWidth = '0';
            var nm = document.createElement('div');
            nm.className = 'kq-name';
            nm.textContent = nama;
            info.appendChild(nm);
            if (p.jurusan) {
                var js = document.createElement('span');
                js.className = 'kq-sub';
                js.textContent = p.jurusan;
                info.appendChild(js);
            }
            wrap.appendChild(ava);
            wrap.appendChild(info);
            tdP.appendChild(wrap);
            tr.appendChild(tdP);

            cell(tr, p.sekolah, 'Asal Sekolah', 'kq-strong');

            var tdB = cell(tr, p.bidang, 'Bidang & Unit', 'kq-strong');
            if (p.unit) {
                var us = document.createElement('span');
                us.className = 'kq-sub';
                us.textContent = p.unit;
                tdB.appendChild(us);
            }

            cell(tr, p.masuk, 'Tanggal Mulai', 'kq-date');
            cell(tr, p.keluar, 'Tanggal Selesai', 'kq-date');

            var tdS = document.createElement('td');
            tdS.setAttribute('data-label', 'Status');
            var badge = document.createElement('span');
            badge.className = 'kq-badge ' + (p.status === 'Aktif' ? 'aktif' : 'menunggu');
            badge.textContent = p.status;
            tdS.appendChild(badge);
            tr.appendChild(tdS);

            rowsEl.appendChild(tr);
        });

        // Kosong / ada data
        var kosong = list.length === 0;
        tableWrap.hidden = kosong;
        emptyEl.hidden = !kosong;
        if (kosong) {
            emptyS.textContent = q ? 'Tidak ada peserta yang cocok dengan pencarian.' : 'Belum ada peserta pada bulan ini.';
        }

        // Info & paginasi
        pagerEl.textContent = '';
        if (kosong) {
            infoEl.textContent = '';
        } else {
            infoEl.textContent = 'Menampilkan baris ' + (start + 1) + ' s/d ' + (start + slice.length) +
                ' dari ' + list.length + ' peserta' + (q ? ' (hasil pencarian)' : ' pada ' + state.label);
        }
        if (totalPages > 1) {
            pagerEl.appendChild(pageBtn('<i class="bx bx-chevron-left"></i>', state.page - 1, { html: true, label: 'Halaman sebelumnya', disabled: state.page === 1 }));
            pageList(state.page, totalPages).forEach(function (n) {
                if (n === '…') {
                    var d = document.createElement('span');
                    d.className = 'kq-dots';
                    d.textContent = '…';
                    pagerEl.appendChild(d);
                } else {
                    pagerEl.appendChild(pageBtn(String(n), n, { current: n === state.page, label: 'Halaman ' + n }));
                }
            });
            pagerEl.appendChild(pageBtn('<i class="bx bx-chevron-right"></i>', state.page + 1, { html: true, label: 'Halaman berikutnya', disabled: state.page === totalPages }));
        }

        if (keepScroll === true && list.length > PER_PAGE) {
            // tetap di area daftar saat pindah halaman
            var top = listEl.getBoundingClientRect().top;
            if (top < 0) listEl.scrollIntoView({ block: 'start' });
        }
    }

    function selectMonth(btn, scroll) {
        state.ym = btn.dataset.ym;
        state.label = btn.dataset.label;
        state.page = 1;
        state.q = '';
        searchEl.value = '';
        tiles.forEach(function (t) {
            var on = t === btn;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        render();
        if (scroll) {
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            listEl.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        }
    }

    tiles.forEach(function (btn) {
        btn.addEventListener('click', function () { selectMonth(btn, true); });
    });

    var timer = null;
    searchEl.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            state.q = searchEl.value.trim().toLowerCase();
            state.page = 1;
            render();
        }, 150);
    });

    // Bulan pertama langsung terpilih
    selectMonth(tiles[0], false);
})();
</script>
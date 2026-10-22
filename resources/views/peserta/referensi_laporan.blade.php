@extends('layouts.peserta')

@section('content')
<style>
    :root {
        --rf-blue: #0b4fb3;
        --rf-ink: #16233b;
        --rf-muted: #6b7a90;
        --rf-line: #e6ecf5;
        --rf-soft: #f4f7fc;
    }

    /* ── Tabs ── */
    .rf-tabs {
        display: flex; gap: 4px; padding: 5px; margin-bottom: 14px;
        background: #fff; border-radius: 18px; border: 1px solid var(--rf-line);
        box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .rf-tab {
        flex: 1; border: 0; background: transparent; cursor: pointer; font-family: inherit;
        padding: 10px 6px; border-radius: 14px; font-size: 13px; font-weight: 700; color: var(--rf-muted);
        transition: background .2s ease, color .2s ease;
    }
    .rf-tab.active {
        background: linear-gradient(135deg, #2f7cf0, var(--rf-blue)); color: #fff;
        box-shadow: 0 6px 14px rgba(11, 79, 179, .30);
    }

    /* ── Judul & pencarian ── */
    .rf-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 2px 2px 12px; flex-wrap: wrap; }
    .rf-title { font-size: 16px; font-weight: 800; color: var(--rf-ink); display: flex; align-items: center; gap: 8px; }
    .rf-title i { font-size: 22px; color: var(--rf-blue); }
    .rf-count { font-size: 11px; font-weight: 700; color: var(--rf-blue); background: #e8f0ff; padding: 5px 12px; border-radius: 999px; }

    .rf-search {
        display: flex; align-items: center; gap: 8px; padding: 6px 6px 6px 14px; margin-bottom: 14px;
        background: #fff; border-radius: 18px; border: 1px solid var(--rf-line);
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
    }
    .rf-search > i { font-size: 20px; color: var(--rf-muted); }
    .rf-search input {
        flex: 1; min-width: 0; border: 0; outline: 0; background: transparent;
        font-family: inherit; font-size: 13px; color: var(--rf-ink); padding: 10px 0;
    }
    .rf-search input::placeholder { color: #9aa8bb; }
    .rf-clear {
        width: 34px; height: 34px; border-radius: 50%; background: var(--rf-soft); color: var(--rf-muted);
        display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 18px; flex-shrink: 0;
    }
    .rf-go {
        border: 0; cursor: pointer; width: 42px; height: 42px; border-radius: 14px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 20px; color: #fff;
        background: linear-gradient(135deg, #2f7cf0, var(--rf-blue)); box-shadow: 0 6px 14px rgba(11, 79, 179, .28);
    }

    /* ── Daftar kartu ── */
    .rf-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px; }
    .rf-card {
        background: #fff; border-radius: 20px; padding: 14px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07); border: 1px solid rgba(230, 236, 245, .9);
        display: flex; flex-direction: column; gap: 12px;
    }
    .rf-card-top { display: flex; gap: 12px; align-items: flex-start; }
    .rf-ico {
        width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
        background: #e8f0ff; color: var(--rf-blue); font-size: 24px;
        display: flex; align-items: center; justify-content: center;
    }
    .rf-body { flex: 1; min-width: 0; }
    .rf-name { font-size: 14px; font-weight: 800; color: var(--rf-ink); line-height: 1.25; word-break: break-word; }
    .rf-school { font-size: 12px; color: var(--rf-muted); margin-top: 3px; font-weight: 600; }
    .rf-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .rf-chip {
        display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px;
        font-size: 10.5px; font-weight: 700; max-width: 100%;
    }
    .rf-chip i { font-size: 13px; }
    .rf-chip.bidang { background: #e8f0ff; color: var(--rf-blue); }
    .rf-chip.jurusan { background: var(--rf-soft); color: #4a5a72; border: 1px solid var(--rf-line); }
    .rf-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        padding-top: 12px; border-top: 1px dashed var(--rf-line);
    }
    .rf-meta { font-size: 11px; color: var(--rf-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rf-meta i { font-size: 14px; }
    .rf-actions { display: flex; gap: 8px; }
    .rf-act {
        height: 36px; padding: 0 12px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none; font-size: 12px; font-weight: 700; transition: transform .15s ease;
    }
    .rf-act:active { transform: scale(.96); }
    .rf-act i { font-size: 17px; }
    .rf-act.view { background: #e8f0ff; color: var(--rf-blue); }
    .rf-act.dl { background: linear-gradient(135deg, #2f7cf0, var(--rf-blue)); color: #fff; box-shadow: 0 5px 12px rgba(11, 79, 179, .25); }

    /* ── Kosong ── */
    .rf-empty { text-align: center; padding: 40px 16px; background: #fff; border-radius: 20px; border: 1px solid var(--rf-line); color: var(--rf-muted); }
    .rf-empty i { font-size: 44px; color: #b8c4d6; }
    .rf-empty-title { font-size: 14px; font-weight: 800; color: var(--rf-ink); margin-top: 6px; }
    .rf-empty-sub { font-size: 12px; margin-top: 4px; }

    /* ── Paginasi ── */
    .rf-pager { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 18px 0 14px; }
    .rf-pg {
        width: 40px; height: 40px; border-radius: 14px; background: #fff; color: var(--rf-blue);
        border: 1px solid var(--rf-line); display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: 22px; box-shadow: 0 4px 12px rgba(24, 60, 120, .08);
    }
    .rf-pg-info { font-size: 12px; font-weight: 700; color: var(--rf-ink); background: #fff; padding: 10px 16px; border-radius: 14px; border: 1px solid var(--rf-line); }

    .rf-back {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;
        padding: 14px 16px; border-radius: 16px; margin-bottom: 16px; text-decoration: none;
        background: #fff; color: var(--rf-blue); border: 1px solid var(--rf-line);
        font-size: 13px; font-weight: 800; box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .rf-back i { font-size: 18px; }

    /* ── Responsif ── */
    @@media (max-width: 360px) {
        .rf-tab { font-size: 12px; }
        .rf-list { grid-template-columns: 1fr; }
        .rf-act span { display: none; }
        .rf-act { padding: 0 10px; }
    }
    @@media (min-width: 768px) {
        .rf-back { max-width: 320px; margin-left: auto; margin-right: auto; }
        .rf-card { transition: transform .15s ease, box-shadow .15s ease; }
        .rf-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(24, 60, 120, .12); }
    }
</style>

<!-- ═══ Tabs ═══ -->
<div class="rf-tabs w-100">
    <button class="rf-tab" onclick="location.href='{{ route('peserta.laporan') }}'">Upload</button>
    <button class="rf-tab" onclick="location.href='{{ route('peserta.laporan', ['tab' => 'riwayat']) }}'">Riwayat</button>
    <button class="rf-tab active" onclick="location.href='{{ route('peserta.laporan.referensi') }}'">Referensi</button>
</div>

<!-- ═══ Judul & hasil ═══ -->
<div class="rf-head">
    <div class="rf-title"><i class='bx bx-book'></i> Referensi Laporan</div>
    <div class="rf-count">{{ $total }} laporan ditemukan</div>
</div>

<!-- ═══ Pencarian ═══ -->
<form method="GET" action="{{ route('peserta.laporan.referensi') }}" class="rf-search w-100">
    <i class='bx bx-search'></i>
    <input type="text" name="q" placeholder="Cari nama, sekolah, jurusan, bidang, nama file..." value="{{ $search }}">
    @if ($search)
    <a href="{{ route('peserta.laporan.referensi') }}" class="rf-clear" title="Hapus pencarian"><i class='bx bx-x'></i></a>
    @endif
    <button type="submit" class="rf-go" title="Cari"><i class='bx bx-search'></i></button>
</form>

<!-- ═══ Daftar Referensi ═══ -->
@php $has = false; @endphp
<div class="rf-list w-100">
    @foreach ($data as $row)
        @php
            $has = true;
            $sz = $row->ukuran_file > 1024*1024 ? round($row->ukuran_file/1024/1024,2).' MB' : round($row->ukuran_file/1024,1).' KB';
        @endphp
    <div class="rf-card">
        <div class="rf-card-top">
            <div class="rf-ico"><i class='bx bxs-file-pdf'></i></div>
            <div class="rf-body">
                <div class="rf-name">{{ $row->nama }}</div>
                <div class="rf-school">{{ $row->asal_sekolah }}</div>
            </div>
        </div>

        <div class="rf-chips">
            <span class="rf-chip bidang"><i class='bx bx-briefcase-alt-2'></i> {{ $row->nama_bidang ?? '-' }}</span>
            <span class="rf-chip jurusan"><i class='bx bx-book-open'></i> {{ $row->jurusan ?? '-' }}</span>
        </div>

        <div class="rf-foot">
            <div class="rf-meta">
                <i class='bx bx-hdd'></i> {{ $sz }}
                <span>·</span>
                <i class='bx bx-calendar'></i> {{ date('d/m/Y', strtotime($row->tgl_upload)) }}
            </div>
            <div class="rf-actions">
                <a href="/uploads/laporan/{{ $row->file_laporan }}" target="_blank" class="rf-act view" title="Lihat">
                    <i class='bx bx-show-alt'></i><span>Lihat</span>
                </a>
                <a href="/uploads/laporan/{{ $row->file_laporan }}" download="{{ 'Laporan_'.$row->nama.'.pdf' }}" class="rf-act dl" title="Download">
                    <i class='bx bx-download'></i><span>Unduh</span>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if (! $has)
<div class="rf-empty">
    <i class='bx bx-book'></i>
    @if ($search)
        <div class="rf-empty-title">Tidak ada hasil</div>
        <div class="rf-empty-sub">Tidak ada laporan yang cocok dengan "{{ $search }}". Coba kata kunci lain.</div>
    @else
        <div class="rf-empty-title">Belum ada referensi laporan</div>
    @endif
</div>
@endif

<!-- ═══ Paginasi ═══ -->
@if ($total_page > 1)
<div class="rf-pager">
    @if ($page_num > 1)
    <a href="{{ route('peserta.laporan.referensi', ['p' => $page_num-1, 'q' => $search]) }}" class="rf-pg" title="Sebelumnya">
        <i class='bx bx-chevron-left'></i>
    </a>
    @endif
    <span class="rf-pg-info">{{ $page_num }} / {{ $total_page }}</span>
    @if ($page_num < $total_page)
    <a href="{{ route('peserta.laporan.referensi', ['p' => $page_num+1, 'q' => $search]) }}" class="rf-pg" title="Berikutnya">
        <i class='bx bx-chevron-right'></i>
    </a>
    @endif
</div>
@endif

<a href="{{ route('peserta.laporan') }}" class="rf-back">
    <i class='bx bx-arrow-back'></i> Kembali ke Laporan
</a>
@endsection
@extends('layouts.peserta')

@section('content')
@php
    // Helper tampilan saja (tidak menyentuh backend)
    $dk_bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $dk_tgl = function ($t) use ($dk_bulan) {
        $ts = $t ? strtotime($t) : false;
        if (! $ts) return ['-', ''];
        return [date('j', $ts) . ' ' . $dk_bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts), date('H:i', $ts)];
    };

    // Jenis berkas: [kelas warna, ikon, nama jenis]
    $dk_kind = function ($ext) {
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) return ['img',   'bxs-image',       'Gambar ' . strtoupper($ext)];
        if ($ext === 'pdf')                                          return ['pdf',   'bxs-file-pdf',    'PDF'];
        if (in_array($ext, ['doc', 'docx', 'dot', 'dotx', 'rtf']))   return ['word',  'bxs-file-doc',    'Word'];
        if (in_array($ext, ['xls', 'xlsx', 'csv']))                  return ['excel', 'bxs-spreadsheet', 'Excel'];
        if (in_array($ext, ['ppt', 'pptx']))                         return ['ppt',   'bxs-slideshow',   'PowerPoint'];
        if (in_array($ext, ['txt']))                                 return ['other', 'bxs-file-txt',    'Teks'];
        return ['other', 'bxs-file', $ext ? strtoupper($ext) : 'Berkas'];
    };
@endphp

<style>
    :root {
        --dk-blue: #0b4fb3;
        --dk-ink: #16233b;
        --dk-muted: #6b7a90;
        --dk-line: #e6ecf5;
        --dk-soft: #f4f7fc;
    }

    .dk-card {
        background: #fff; border-radius: 20px; padding: 14px; margin-bottom: 12px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
        border: 1px solid rgba(230, 236, 245, .9);
    }

    /* ── Header + pencarian ── */
    .dk-hero {
        padding: 18px 16px 16px;
        background: linear-gradient(180deg, #e8f0ff 0%, #fff 75%);
    }
    .dk-hero-top { display: flex; align-items: center; gap: 12px; }
    .dk-hero-ico {
        width: 48px; height: 48px; border-radius: 16px; flex-shrink: 0; font-size: 26px;
        display: flex; align-items: center; justify-content: center;
        background: #fff; color: var(--dk-blue);
        box-shadow: 0 0 0 5px rgba(255, 255, 255, .7), 0 8px 18px rgba(24, 60, 120, .12);
    }
    .dk-hero-ttl { flex: 1; min-width: 0; }
    .dk-title { font-size: 17px; font-weight: 800; color: var(--dk-ink); line-height: 1.2; }
    .dk-sub { font-size: 11.5px; color: var(--dk-muted); margin-top: 3px; line-height: 1.4; }
    .dk-count {
        flex-shrink: 0; font-size: 11px; font-weight: 800; padding: 5px 11px; border-radius: 999px;
        background: #fff; color: var(--dk-blue); border: 1px solid rgba(11, 79, 179, .12); white-space: nowrap;
    }

    .dk-search { display: flex; gap: 8px; margin-top: 14px; }
    .dk-field { position: relative; flex: 1; min-width: 0; }
    .dk-field > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 19px; color: var(--dk-muted); pointer-events: none; }
    .dk-input {
        width: 100%; height: 46px; border-radius: 14px; border: 1px solid var(--dk-line); background: #fff;
        padding: 0 14px 0 40px; font-family: inherit; font-size: 13px; color: var(--dk-ink); outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .dk-input::placeholder { color: #9aa8bb; }
    .dk-input:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .14); }
    .dk-go {
        width: 46px; height: 46px; border: 0; border-radius: 14px; flex-shrink: 0; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 21px; color: #fff;
        background: linear-gradient(135deg, #2f7cf0, var(--dk-blue)); box-shadow: 0 6px 14px rgba(11, 79, 179, .28);
        transition: transform .15s ease;
    }
    .dk-go:active { transform: scale(.95); }

    .dk-result { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 2px 4px 10px; font-size: 12px; color: var(--dk-muted); }
    .dk-result b { color: var(--dk-ink); font-weight: 800; }
    .dk-reset { display: inline-flex; align-items: center; gap: 3px; font-weight: 700; color: var(--dk-blue); text-decoration: none; white-space: nowrap; }
    .dk-reset i { font-size: 15px; }

    /* ── Item dokumen ── */
    .dk-item { padding: 12px; }
    .dk-item-main { display: flex; align-items: center; gap: 12px; }
    .dk-thumb {
        position: relative; width: 56px; height: 64px; border-radius: 12px; flex-shrink: 0; overflow: hidden;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
    }
    .dk-thumb i { font-size: 26px; line-height: 1; }
    .dk-thumb span { font-size: 9px; font-weight: 800; letter-spacing: .5px; }
    /* Warna per jenis berkas: --k-bg latar, --k-fg ikon/teks, --k-fold lipatan sudut */
    .dk-thumb.pdf   { --k-bg: #fdecee; --k-fg: #c62828; --k-fold: #f6c9ce; }
    .dk-thumb.word  { --k-bg: #e4eefb; --k-fg: #1f5bb5; --k-fold: #bcd3f3; }
    .dk-thumb.excel { --k-bg: #e2f4ea; --k-fg: #1b7a47; --k-fold: #b6e0c6; }
    .dk-thumb.ppt   { --k-bg: #fdeee6; --k-fg: #c4421a; --k-fold: #f6cdb9; }
    .dk-thumb.other { --k-bg: #eceff4; --k-fg: #5b6b82; --k-fold: #d0d7e2; }
    .dk-thumb.file { background: var(--k-bg); color: var(--k-fg); }
    .dk-thumb.file::after {
        content: ''; position: absolute; top: 0; right: 0; width: 14px; height: 14px;
        background: linear-gradient(225deg, #fff 50%, var(--k-fold) 50%); border-bottom-left-radius: 5px;
    }
    .dk-thumb.img { background: var(--dk-soft); border: 1px solid var(--dk-line); }
    .dk-thumb.img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }

    .dk-body { flex: 1; min-width: 0; }
    .dk-name {
        font-size: 13.5px; font-weight: 800; color: var(--dk-ink); line-height: 1.3; word-break: break-word;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .dk-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; margin-top: 7px; }
    .dk-type {
        font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px; white-space: nowrap;
    }
    .dk-type.pdf   { background: #fdecee; color: #a11d28; }
    .dk-type.word  { background: #e4eefb; color: #174a99; }
    .dk-type.excel { background: #e2f4ea; color: #14633a; }
    .dk-type.ppt   { background: #fdeee6; color: #a3330f; }
    .dk-type.img   { background: #d9f0e3; color: #0f5132; }
    .dk-type.other { background: #eceff4; color: #4a5a72; }
    .dk-date { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--dk-muted); font-weight: 600; }
    .dk-date i { font-size: 14px; color: #8a98ad; }

    .dk-acts { display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--dk-line); }
    .dk-btn {
        flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; height: 42px;
        border-radius: 13px; text-decoration: none; font-family: inherit; font-size: 12.5px; font-weight: 800;
        transition: transform .15s ease;
    }
    .dk-btn:active { transform: scale(.97); }
    .dk-btn i { font-size: 19px; }
    .dk-btn.primary { background: linear-gradient(135deg, #2f7cf0, var(--dk-blue)); color: #fff; box-shadow: 0 6px 14px rgba(11, 79, 179, .25); }
    .dk-btn.outline { background: #fff; color: var(--dk-blue); border: 1px solid var(--dk-line); }
    .dk-btn:focus-visible, .dk-reset:focus-visible, .dk-pg a:focus-visible, .dk-back:focus-visible { outline: 3px solid rgba(47, 124, 240, .45); outline-offset: 2px; }

    /* ── Kosong ── */
    .dk-empty { text-align: center; padding: 34px 20px 30px; }
    .dk-empty-ico {
        width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 14px; font-size: 32px;
        display: flex; align-items: center; justify-content: center; background: #e9edf3; color: #8a98ad;
    }
    .dk-empty-ttl { font-size: 15px; font-weight: 800; color: var(--dk-ink); }
    .dk-empty-txt { font-size: 12px; color: var(--dk-muted); margin: 6px auto 0; max-width: 280px; line-height: 1.55; word-break: break-word; }
    .dk-empty .dk-btn { display: inline-flex; flex: none; width: auto; padding: 0 20px; margin-top: 16px; }

    /* ── Pagination ── */
    .dk-pg { display: flex; align-items: center; justify-content: center; gap: 10px; margin: 4px 0 14px; }
    .dk-pg a, .dk-pg .dk-pg-off {
        width: 42px; height: 42px; border-radius: 13px; display: flex; align-items: center; justify-content: center;
        font-size: 22px; text-decoration: none; background: #fff; color: var(--dk-blue); border: 1px solid var(--dk-line);
        box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .dk-pg .dk-pg-off { color: #c3cddb; box-shadow: none; background: var(--dk-soft); }
    .dk-pg-info { font-size: 12px; font-weight: 700; color: var(--dk-muted); min-width: 96px; text-align: center; }
    .dk-pg-info b { color: var(--dk-ink); }

    .dk-back {
        display: flex; align-items: center; justify-content: center; gap: 8px; height: 48px; margin: 4px 0 16px;
        border-radius: 16px; text-decoration: none; font-size: 13.5px; font-weight: 800; color: var(--dk-blue);
        background: #fff; border: 1px solid var(--dk-line); box-shadow: 0 6px 22px rgba(24, 60, 120, .06);
    }
    .dk-back i { font-size: 20px; }

    @@media (max-width: 360px) {
        .dk-hero { padding: 16px 12px 14px; }
        .dk-title { font-size: 15.5px; }
        .dk-thumb { width: 50px; height: 58px; }
    }
    @@media (min-width: 992px) {
        .dk-wrap {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px; align-items: start;
        }
        .dk-wrap > .dk-hero,
        .dk-wrap > .dk-result,
        .dk-wrap > .dk-empty,
        .dk-wrap > .dk-pg,
        .dk-wrap > .dk-back { grid-column: 1 / -1; }
        .dk-wrap > .dk-item { margin-bottom: 0; }
        .dk-result { margin: 0 2px 2px; }
    }
    @@media (min-width: 1400px) {
        .dk-wrap { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @@media (prefers-reduced-motion: reduce) {
        .dk-btn, .dk-go, .dk-input { transition: none; }
    }
</style>

<div class="dk-wrap w-100">

    <!-- Header + Pencarian -->
    <div class="dk-card dk-hero">
        <div class="dk-hero-top">
            <div class="dk-hero-ico"><i class='bx bx-folder-open'></i></div>
            <div class="dk-hero-ttl">
                <div class="dk-title">Dokumen SDM</div>
                <div class="dk-sub">Berkas resmi dari SDM untuk peserta magang</div>
            </div>
            <span class="dk-count">{{ $total }} berkas</span>
        </div>

        <form method="GET" action="{{ route('peserta.dokumen') }}" class="dk-search" role="search">
            <div class="dk-field">
                <i class='bx bx-search'></i>
                <input type="text" name="q" class="dk-input" placeholder="Cari nama dokumen" value="{{ $search }}" aria-label="Cari nama dokumen">
            </div>
            <button type="submit" class="dk-go" aria-label="Cari"><i class='bx bx-search'></i></button>
        </form>
    </div>

    @if (! empty($search))
    <div class="dk-result">
        <span><b>{{ $total }}</b> hasil untuk “{{ $search }}”</span>
        <a href="{{ route('peserta.dokumen') }}" class="dk-reset"><i class='bx bx-x-circle'></i> Hapus</a>
    </div>
    @endif

    <!-- Daftar Dokumen -->
    @php $has = false; @endphp
    @foreach ($data as $row)
        @php
            $has = true;
            $ext = strtolower(pathinfo($row->file_dokumen, PATHINFO_EXTENSION));
            [$kind, $icon, $kind_label] = $dk_kind($ext);
            $is_image = $kind === 'img';
            $file_path = '/uploads/dokumen/' . $row->file_dokumen;
            [$tgl, $jam] = $dk_tgl($row->created_at);
        @endphp
    <div class="dk-card dk-item">
        <div class="dk-item-main">
            @if ($is_image)
            <div class="dk-thumb img">
                <img src="{{ $file_path }}" alt="" loading="lazy">
            </div>
            @else
            <div class="dk-thumb file {{ $kind }}">
                <i class='bx {{ $icon }}'></i>
                <span>{{ strtoupper($ext) }}</span>
            </div>
            @endif

            <div class="dk-body">
                <div class="dk-name">{{ $row->nama_dokumen }}</div>
                <div class="dk-meta">
                    <span class="dk-type {{ $kind }}">{{ $kind_label }}</span>
                    <span class="dk-date"><i class='bx bx-calendar'></i> {{ $tgl }}{{ $jam ? ', ' . $jam : '' }}</span>
                </div>
            </div>
        </div>

        <div class="dk-acts">
            <a href="{{ $file_path }}" target="_blank" class="dk-btn primary">
                <i class='bx bx-show'></i> Lihat
            </a>
            <a href="{{ $file_path }}" download="{{ $row->nama_dokumen . '.' . $ext }}" class="dk-btn outline">
                <i class='bx bx-download'></i> Unduh
            </a>
        </div>
    </div>
    @endforeach

    @if (! $has)
    <div class="dk-card dk-empty">
        @if (! empty($search))
            <div class="dk-empty-ico"><i class='bx bx-search-alt'></i></div>
            <div class="dk-empty-ttl">Dokumen tidak ditemukan</div>
            <div class="dk-empty-txt">Tidak ada dokumen dengan nama “{{ $search }}”. Coba kata kunci lain.</div>
            <a href="{{ route('peserta.dokumen') }}" class="dk-btn outline"><i class='bx bx-x-circle'></i> Hapus pencarian</a>
        @else
            <div class="dk-empty-ico"><i class='bx bx-folder-open'></i></div>
            <div class="dk-empty-ttl">Belum ada dokumen</div>
            <div class="dk-empty-txt">Dokumen dari SDM akan muncul di sini setelah diunggah.</div>
        @endif
    </div>
    @endif

    <!-- Pagination -->
    @if ($total_page > 1)
    <nav class="dk-pg" aria-label="Halaman dokumen">
        @if ($page_num > 1)
        <a href="{{ route('peserta.dokumen', ['p' => $page_num-1, 'q' => $search]) }}" aria-label="Halaman sebelumnya"><i class='bx bx-chevron-left'></i></a>
        @else
        <span class="dk-pg-off"><i class='bx bx-chevron-left'></i></span>
        @endif

        <span class="dk-pg-info">Halaman <b>{{ $page_num }}</b> dari {{ $total_page }}</span>

        @if ($page_num < $total_page)
        <a href="{{ route('peserta.dokumen', ['p' => $page_num+1, 'q' => $search]) }}" aria-label="Halaman berikutnya"><i class='bx bx-chevron-right'></i></a>
        @else
        <span class="dk-pg-off"><i class='bx bx-chevron-right'></i></span>
        @endif
    </nav>
    @endif

    <a href="{{ route('peserta.home') }}" class="dk-back">
        <i class='bx bx-arrow-back'></i> Kembali ke Home
    </a>
</div>
@endsection
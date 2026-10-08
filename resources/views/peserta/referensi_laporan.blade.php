@extends('layouts.peserta')

@section('content')
<!-- ═══ Tabs ═══ -->
<div class="m-tabs" style="margin-top:4px; margin-bottom:12px;">
    <button class="m-tab" onclick="location.href='{{ route('peserta.laporan') }}'">Upload</button>
    <button class="m-tab" onclick="location.href='{{ route('peserta.laporan', ['tab' => 'riwayat']) }}'">Riwayat</button>
    <button class="m-tab active" onclick="location.href='{{ route('peserta.laporan.referensi') }}'">Referensi</button>
</div>

<div class="m-section-title" style="margin-top:4px;"><i class='bx bx-book'></i> Referensi Laporan</div>

<div class="m-card" style="padding:8px 12px; margin-bottom:12px;">
    <form method="GET" action="{{ route('peserta.laporan.referensi') }}" style="display:flex; gap:8px;">
        <input type="text" name="q" class="m-form-input" placeholder="Cari nama / sekolah..." value="{{ $search }}" style="flex:1; padding:8px 12px; font-size:13px;">
        <button type="submit" class="action-btn action-btn-primary" style="padding:8px 12px;"><i class='bx bx-search'></i></button>
    </form>
</div>

<div style="font-size:11px; color:var(--text-secondary); margin-bottom:8px;">{{ $total }} laporan ditemukan</div>

<div class="m-card" style="padding:4px 16px;">
    @php $has = false; @endphp
    @foreach ($data as $row)
        @php
            $has = true;
            $sz = $row->ukuran_file > 1024*1024 ? round($row->ukuran_file/1024/1024,2).' MB' : round($row->ukuran_file/1024,1).' KB';
        @endphp
    <div class="m-list-item" style="padding:14px 0;">
        <div class="m-list-icon" style="background:#d1e7dd; color:#0f5132;">
            <i class='bx bxs-file-pdf'></i>
        </div>
        <div class="m-list-content">
            <div class="m-list-title" style="font-weight:600;">{{ $row->nama }}</div>
            <div class="m-list-subtitle" style="margin-top:4px; font-size:11px; line-height:1.4;">
                <span style="font-weight:500; color:var(--text-primary);">{{ $row->asal_sekolah }}</span><br>
                Bidang: <span style="color:var(--primary); font-weight:600;">{{ $row->nama_bidang ?? '-' }}</span> · Jurusan: {{ $row->jurusan ?? '-' }}<br>
                <small class="text-muted">{{ $sz }} · {{ date('d/m/Y', strtotime($row->tgl_upload)) }}</small>
            </div>
        </div>
        <div class="m-list-action" style="display:flex; gap:6px; align-items:center;">
            <a href="/uploads/laporan/{{ $row->file_laporan }}" target="_blank" style="color:var(--primary); font-size:20px; display:inline-flex;" title="Lihat">
                <i class='bx bx-show-alt'></i>
            </a>
            <a href="/uploads/laporan/{{ $row->file_laporan }}" download="{{ 'Laporan_'.$row->nama.'.pdf' }}" style="color:#198754; font-size:20px; display:inline-flex;" title="Download">
                <i class='bx bx-download'></i>
            </a>
        </div>
    </div>
    @endforeach
    @if (! $has)
    <div class="m-empty">
        <i class='bx bx-book'></i>
        <div class="m-empty-text">Belum ada referensi laporan</div>
    </div>
    @endif
</div>

@if ($total_page > 1)
<div style="display:flex; justify-content:center; gap:4px; margin-top:12px; margin-bottom:16px;">
    @if ($page_num > 1)
    <a href="{{ route('peserta.laporan.referensi', ['p' => $page_num-1, 'q' => $search]) }}" class="action-btn action-btn-outline" style="padding:6px 12px; font-size:12px;">
        <i class='bx bx-chevron-left'></i>
    </a>
    @endif
    <span style="padding:6px 12px; font-size:12px; color:var(--text-secondary);">{{ $page_num }} / {{ $total_page }}</span>
    @if ($page_num < $total_page)
    <a href="{{ route('peserta.laporan.referensi', ['p' => $page_num+1, 'q' => $search]) }}" class="action-btn action-btn-outline" style="padding:6px 12px; font-size:12px;">
        <i class='bx bx-chevron-right'></i>
    </a>
    @endif
</div>
@endif

<a href="{{ route('peserta.laporan') }}" class="action-btn action-btn-outline action-btn-block" style="margin-bottom:16px;">
    <i class='bx bx-arrow-back'></i> Kembali ke Laporan
</a>
@endsection

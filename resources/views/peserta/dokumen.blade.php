@extends('layouts.peserta')

@section('content')
<div class="m-section-title" style="margin-top:4px;">
    <i class='bx bx-folder-open'></i> Dokumen SDM
</div>

<!-- Form Pencarian -->
<div class="m-card" style="padding:8px 12px; margin-bottom:12px;">
    <form method="GET" action="{{ route('peserta.dokumen') }}" style="display:flex; gap:8px;">
        <input type="text" 
               name="q" 
               class="m-form-input" 
               placeholder="Cari nama dokumen..." 
               value="{{ $search }}" 
               style="flex:1; padding:8px 12px; font-size:13px;">
        <button type="submit" class="action-btn action-btn-primary" style="padding:8px 12px;">
            <i class='bx bx-search'></i>
        </button>
    </form>
</div>

<div style="font-size:11px; color:var(--text-secondary); margin-bottom:8px;">
    {{ $total }} dokumen ditemukan
</div>

<!-- List Dokumen -->
<div class="m-card" style="padding:4px 16px;">
    @php $has = false; @endphp
    @foreach ($data as $row)
        @php
            $has = true;
            $ext = strtolower(pathinfo($row->file_dokumen, PATHINFO_EXTENSION));
            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            $file_path = '/uploads/dokumen/' . $row->file_dokumen;
        @endphp
    <div class="m-list-item" style="padding:14px 0;">
        @if ($is_image)
            <div class="m-list-icon" style="background:#e8f5e9; color:#2e7d32;">
                <i class='bx bxs-image'></i>
            </div>
        @else
            <div class="m-list-icon" style="background:#ffebee; color:#c62828;">
                <i class='bx bxs-file-pdf'></i>
            </div>
        @endif

        <div class="m-list-content">
            <div class="m-list-title" style="font-weight:600;">
                {{ $row->nama_dokumen }}
            </div>
            <div class="m-list-subtitle" style="margin-top:4px; font-size:11px; line-height:1.4;">
                <span class="m-badge {{ $is_image ? 'm-badge-disetujui' : 'm-badge-ditolak' }}" style="font-size:9px; padding:2px 6px;">
                    {{ $is_image ? 'Gambar (' . strtoupper($ext) . ')' : 'PDF Dokumen' }}
                </span>
                <span style="margin-left:6px; color:var(--text-secondary);">
                    <i class='bx bx-time-five' style="font-size:11px;"></i> {{ date('d/m/Y H:i', strtotime($row->created_at)) }}
                </span>
            </div>
        </div>

        <div class="m-list-action" style="display:flex; gap:10px; align-items:center;">
            <!-- Button Lihat/Preview -->
            <a href="{{ $file_path }}" 
               target="_blank" 
               style="color:var(--primary); font-size:22px; display:inline-flex;" 
               title="Lihat Dokumen">
                <i class='bx bx-show-alt'></i>
            </a>
            <!-- Button Download -->
            <a href="{{ $file_path }}" 
               download="{{ $row->nama_dokumen . '.' . $ext }}" 
               style="color:#198754; font-size:22px; display:inline-flex;" 
               title="Download Dokumen">
                <i class='bx bx-download'></i>
            </a>
        </div>
    </div>
    @endforeach

    @if (! $has)
    <div class="m-empty" style="padding:24px 0;">
        <i class='bx bx-folder-open' style="font-size:48px; color:var(--text-secondary); opacity:0.5;"></i>
        <div class="m-empty-text" style="margin-top:8px; color:var(--text-secondary);">Belum ada dokumen dari SDM</div>
    </div>
    @endif
</div>

<!-- Pagination -->
@if ($total_page > 1)
<div style="display:flex; justify-content:center; gap:4px; margin-top:12px; margin-bottom:16px;">
    @if ($page_num > 1)
    <a href="{{ route('peserta.dokumen', ['p' => $page_num-1, 'q' => $search]) }}" class="action-btn action-btn-outline" style="padding:6px 12px; font-size:12px;">
        <i class='bx bx-chevron-left'></i>
    </a>
    @endif
    <span style="padding:6px 12px; font-size:12px; color:var(--text-secondary);">{{ $page_num }} / {{ $total_page }}</span>
    @if ($page_num < $total_page)
    <a href="{{ route('peserta.dokumen', ['p' => $page_num+1, 'q' => $search]) }}" class="action-btn action-btn-outline" style="padding:6px 12px; font-size:12px;">
        <i class='bx bx-chevron-right'></i>
    </a>
    @endif
</div>
@endif

<a href="{{ route('peserta.home') }}" class="action-btn action-btn-outline action-btn-block" style="margin-top:12px; margin-bottom:16px; text-decoration:none;">
    <i class='bx bx-arrow-back'></i> Kembali ke Home
</a>
@endsection

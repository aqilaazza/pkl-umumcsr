@extends('layouts.peserta')

@section('content')
@php
    $message = session('message');
@endphp

<!-- Toast dari server -->
@if ($message)
    @php
        $parts = explode(':', $message, 2);
        $mtype = $parts[0]; $mmsg = $parts[1] ?? '';
    @endphp
<script>document.addEventListener('DOMContentLoaded', () => showToast('{{ addslashes($mmsg) }}', '{{ $mtype }}'));</script>
@endif

<!-- ═══ Tabs ═══ -->
<div class="m-tabs" style="margin-top:4px;">
    <button class="m-tab {{ $init_tab === 'upload' ? 'active' : '' }}" onclick="showTab('upload')">Upload</button>
    <button class="m-tab {{ $init_tab === 'riwayat' ? 'active' : '' }}" onclick="showTab('riwayat')">Riwayat</button>
    <button class="m-tab" onclick="location.href='{{ route('peserta.laporan.referensi') }}'">Referensi</button>
</div>

<!-- ═══ Tab Upload ═══ -->
<div id="tab-upload" style="display: {{ $init_tab === 'upload' ? 'block' : 'none' }};">
    @if ($laporan_aktif)
    <!-- Status Card -->
    <div class="m-card" style="border-left: 5px solid {{ match($laporan_aktif->status) { 'Menunggu' => '#ffc107', 'Menunggu Manager' => '#0dcaf0', 'Disetujui' => '#198754', 'Ditolak' => '#dc3545', default => '#6c757d' } }}; padding: 16px; margin-bottom: 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div style="flex:1; min-width:0;">
                <div style="font-size:10px; color:var(--text-secondary); font-weight:600; letter-spacing:0.5px;">STATUS LAPORAN TERAKHIR</div>
                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                    <span class="m-badge m-badge-{{ strtolower($laporan_aktif->status) }}" style="font-size:12px; padding:4px 8px;">
                        {{ $laporan_aktif->status }}
                    </span>
                    <span style="font-size:11px; color:var(--text-muted);">{{ date('d/m/Y H:i', strtotime($laporan_aktif->tgl_upload)) }}</span>
                </div>
                <div style="font-size:12px; color:var(--text-primary); margin-top:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:500;">
                    <i class='bx bxs-file-pdf' style="vertical-align:middle; color:#dc3545; font-size:16px; margin-right:4px;"></i> {{ $laporan_aktif->nama_file }}
                </div>
            </div>
            <a href="/uploads/laporan/{{ $laporan_aktif->file_laporan }}" target="_blank" class="action-btn action-btn-outline" style="padding:8px 12px; font-size:12px; height:36px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center;">
                <i class='bx bx-show-alt' style="font-size:18px;"></i>
            </a>
        </div>
        @if ($laporan_aktif->status === 'Ditolak' && $laporan_aktif->keterangan_tolak)
        <div style="margin-top:12px; padding:10px; background:#f8d7da; border-radius:8px; font-size:11px; color:#842029; border:1px solid #f5c2c7;">
            <strong>Catatan Koreksi:</strong><br>
            <div style="margin-top:2px;">{{ $laporan_aktif->keterangan_tolak }}</div>
        </div>
        @endif
    </div>
    @endif

    @if ($boleh_upload)
    <!-- Upload Form Card -->
    <div class="m-card" style="padding:20px;">
        <div style="text-align:center; margin-bottom:16px;">
            <div style="width:48px; height:48px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:inline-flex; align-items:center; justify-content:center; font-size:24px; margin-bottom:8px;">
                <i class='bx bx-upload'></i>
            </div>
            <h3 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">
                {{ $laporan_aktif && $laporan_aktif->status === 'Ditolak' ? 'Upload Laporan Koreksi' : 'Unggah Laporan Magang' }}
            </h3>
            <p style="font-size:11px; color:var(--text-secondary); margin:4px 0 0 0;">Laporan akhir kegiatan magang PKL</p>
        </div>

        <form action="{{ route('peserta.laporan.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="m-upload-zone" id="uploadZone" for="fileLaporan" style="display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed var(--primary); border-radius:12px; padding:28px 16px; background:#f8fdf9; cursor:pointer; transition:all 0.3s ease;">
                <i class='bx bxs-file-pdf' style="font-size:44px; color:var(--primary); margin-bottom:8px;"></i>
                <div class="upload-text" id="uploadText" style="font-size:13px; font-weight:600; color:#1a1a2e;">Pilih file PDF laporan</div>
                <div class="upload-hint" style="font-size:10px; color:#6c757d; margin-top:2px;">Maksimal 5 MB</div>
            </label>
            <input type="file" name="file_laporan" id="fileLaporan" accept=".pdf,application/pdf" required style="display:none;">

            <!-- Preview File Terpilih -->
            <div id="filePreview" style="display:none; margin-top:12px; padding:12px; background:#e8f5e9; border:1px solid #c8e6c9; border-radius:8px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class='bx bxs-check-circle' style="font-size:22px; color:#2e7d32;"></i>
                    <div style="flex:1; min-width:0;">
                        <div id="fpName" style="font-size:12px; font-weight:600; color:#1b5e20; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
                        <div id="fpSize" style="font-size:11px; color:#2e7d32;"></div>
                    </div>
                </div>
            </div>

            <button type="submit" name="submit_upload" class="action-btn action-btn-primary action-btn-block" style="margin-top:16px; border-radius:8px;" id="btnUpload">
                <i class='bx bx-cloud-upload'></i> Mulai Upload Laporan
            </button>
        </form>
    </div>
    @else
    <!-- Blocked Card -->
    <div class="m-card" style="text-align:center; padding:32px 16px; border-top: 4px solid var(--primary);">
        @if ($laporan_aktif->status === 'Menunggu')
            <div style="font-size:48px; margin-bottom:12px;">⏳</div>
            <div style="font-size:15px; font-weight:700; color:var(--text-primary);">Laporan Menunggu Review SDM</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:6px; padding: 0 12px; line-height:1.4;">
                Laporan Anda sedang diperiksa oleh admin SDM. Anda dapat mengunggah ulang jika laporan ditolak.
            </div>
        @elseif ($laporan_aktif->status === 'Menunggu Manager')
            <div style="font-size:48px; margin-bottom:12px;">⏳</div>
            <div style="font-size:15px; font-weight:700; color:#0dcaf0;">Laporan Disetujui SDM</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:6px; padding: 0 12px; line-height:1.4;">
                Laporan Anda sudah disetujui SDM, menunggu approval Manager. Cek menu **Sertifikat** untuk memantau progres.
            </div>
        @else
            <div style="font-size:48px; margin-bottom:12px;">✅</div>
            <div style="font-size:15px; font-weight:700; color:#198754;">Laporan Telah Disetujui!</div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:6px; padding: 0 12px; line-height:1.4;">
                Selamat! Laporan magang Anda telah disetujui. Silakan cek menu **Sertifikat** untuk memantau proses penerbitan sertifikat digital Anda.
            </div>
        @endif
    </div>
    @endif
</div>

<!-- ═══ Tab Riwayat ═══ -->
<div id="tab-riwayat" style="display: {{ $init_tab === 'riwayat' ? 'block' : 'none' }};">
    <div class="m-card" style="padding:4px 16px;">
        @php
            $no = 1;
            $has = false;
        @endphp
        @foreach ($riwayat as $r)
            @php
                $has = true;
                $badge = strtolower($r->status);
                $sz = $r->ukuran_file > 1024*1024 ? round($r->ukuran_file/1024/1024,2).' MB' : round($r->ukuran_file/1024,1).' KB';
            @endphp
        <div class="m-list-item" style="padding:14px 0;">
            <div class="m-list-icon" style="background:{{ match($r->status){'Menunggu'=>'#fff3cd','Disetujui'=>'#d1e7dd','Ditolak'=>'#f8d7da',default=>'#e2e3e5'} }}; color:{{ match($r->status){'Menunggu'=>'#856404','Disetujui'=>'#0f5132','Ditolak'=>'#842029',default=>'#41464b'} }};">
                <i class='bx bxs-file-pdf'></i>
            </div>
            <div class="m-list-content">
                <div class="m-list-title" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:600;">{{ $r->nama_file }}</div>
                <div class="m-list-subtitle" style="margin-top:2px; font-size:11px;">{{ $sz }} · {{ date('d/m/Y H:i', strtotime($r->tgl_upload)) }}</div>
            </div>
            <div class="m-list-action" style="display:flex; gap:6px; align-items:center;">
                <span class="m-badge m-badge-{{ $badge }}" style="font-size:10px; padding:3px 6px;">{{ $r->status }}</span>
                <a href="/uploads/laporan/{{ $r->file_laporan }}" target="_blank" style="color:var(--primary); font-size:20px; display:inline-flex;">
                    <i class='bx bx-show-alt'></i>
                </a>
            </div>
        </div>
        @endforeach
        @if (! $has)
        <div class="m-empty">
            <i class='bx bx-file'></i>
            <div class="m-empty-text">Belum ada riwayat laporan</div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
// Tab switching
function showTab(tab) {
    document.getElementById('tab-upload').style.display = tab === 'upload' ? 'block' : 'none';
    document.getElementById('tab-riwayat').style.display = tab === 'riwayat' ? 'block' : 'none';
    document.querySelectorAll('.m-tab').forEach((t, i) => {
        t.classList.toggle('active', (i === 0 && tab === 'upload') || (i === 1 && tab === 'riwayat'));
    });
}

// File input
const fileInput = document.getElementById('fileLaporan');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const f = this.files[0];
        if (f) {
            document.getElementById('filePreview').style.display = 'block';
            document.getElementById('fpName').textContent = f.name;
            document.getElementById('fpSize').textContent = f.size > 1024*1024 ? (f.size/1024/1024).toFixed(2)+' MB' : (f.size/1024).toFixed(1)+' KB';
            document.getElementById('uploadText').textContent = 'File dipilih ✓';
            document.getElementById('uploadZone').style.borderColor = 'var(--primary)';
            document.getElementById('uploadZone').style.background = '#e8f5e9';
            document.getElementById('btnUpload').disabled = f.size > 5*1024*1024;
        }
    });
}
</script>
@endsection

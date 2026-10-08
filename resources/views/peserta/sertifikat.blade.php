@extends('layouts.peserta')

@section('content')
@php
    $tgl_indo_sert = function ($t) {
        if (empty($t) || $t == '0000-00-00') return '-';
        $b = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $p = explode('-', $t);
        return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
    };
@endphp

@if ($is_digital)
<!-- ═══ Sertifikat Digital ═══ -->
<div class="m-card" style="border-left:4px solid #198754; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">✅</div>
    <div style="font-size:16px; font-weight:700; color:#198754;">Sertifikat Digital!</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Sertifikat digital Anda sudah diterbitkan dengan QR Code</div>
</div>

<div class="m-card">
    <div class="m-section-title" style="margin-bottom:8px;"><i class='bx bx-info-circle'></i> Detail Sertifikat</div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-qr'></i></div>
        <div>
            <div class="profile-info-label">QR Code</div>
            <div class="profile-info-value">Terdapat QR Code untuk verifikasi</div>
        </div>
    </div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#cfe2ff; color:#084298;"><i class='bx bx-calendar'></i></div>
        <div>
            <div class="profile-info-label">Diterbitkan</div>
            <div class="profile-info-value">{{ $tgl_indo_sert(date('Y-m-d', strtotime($sertifikat->generated_at ?? ''))) }}</div>
        </div>
    </div>
</div>

<a href="/uploads/sertifikat/{{ $sertifikat->digital_file }}" target="_blank" class="action-btn action-btn-success action-btn-block" style="margin-bottom:8px;">
    <i class='bx bx-show'></i> Lihat Sertifikat
</a>
<a href="/uploads/sertifikat/{{ $sertifikat->digital_file }}" download="{{ 'Sertifikat_' . ($peserta->nama ?? 'Magang') }}" class="action-btn action-btn-outline action-btn-block" style="margin-bottom:16px;">
    <i class='bx bx-download'></i> Download Sertifikat Digital
</a>

@elseif ($is_uploaded)
<!-- ═══ Sertifikat Upload (scanned) ═══ -->
<div class="m-card" style="border-left:4px solid #198754; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">✅</div>
    <div style="font-size:16px; font-weight:700; color:#198754;">Sertifikat Tersedia!</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Sertifikat Anda sudah ditandatangani dan siap diunduh</div>
</div>
<div class="m-card">
    <div class="m-section-title" style="margin-bottom:8px;"><i class='bx bx-info-circle'></i> Detail Sertifikat</div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-hash'></i></div>
        <div>
            <div class="profile-info-label">Nomor Surat</div>
            <div class="profile-info-value">{{ $sertifikat->nomor_surat ?? '-' }}</div>
        </div>
    </div>
    <div class="profile-info-item">
        <div class="profile-info-icon" style="background:#cfe2ff; color:#084298;"><i class='bx bx-calendar'></i></div>
        <div>
            <div class="profile-info-label">Tersedia Sejak</div>
            <div class="profile-info-value">{{ $tgl_indo_sert(date('Y-m-d', strtotime($sertifikat->tgl_upload_scan ?? ''))) }}</div>
        </div>
    </div>
</div>
<a href="/uploads/sertifikat/{{ $sertifikat->file_sertifikat }}" target="_blank" class="action-btn action-btn-success action-btn-block" style="margin-bottom:8px;">
    <i class='bx bx-show'></i> Lihat Sertifikat
</a>
<a href="/uploads/sertifikat/{{ $sertifikat->file_sertifikat }}" download="{{ 'Sertifikat_' . ($peserta->nama ?? 'Magang') }}" class="action-btn action-btn-outline action-btn-block" style="margin-bottom:16px;">
    <i class='bx bx-download'></i> Download Sertifikat
</a>

@elseif ($manager_ok)
<!-- ═══ Laporan Disetujui Manager - Generate digital cert ═══ -->
<div class="m-card" style="border-left:4px solid #198754; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">🎉</div>
    <div style="font-size:16px; font-weight:700; color:#198754;">Laporan Disetujui!</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Sertifikat digital sedang diproses</div>
</div>
<div class="m-card">
    <div class="m-section-title" style="margin-bottom:12px;"><i class='bx bx-git-merge'></i> Progress</div>
    <div class="m-timeline">
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Laporan Diupload</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Disetujui SDM</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Disetujui Manager</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot warning"></div>
            <div class="m-timeline-label muted">📄 Terbit Sertifikat Digital</div>
        </div>
    </div>
</div>

@elseif ($sdm_ok && $laporan_aktif->manager_status === 'Menunggu')
<!-- ═══ Laporan Disetujui SDM - Menunggu Manager ═══ -->
<div class="m-card" style="border-left:4px solid #ffc107; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">⏳</div>
    <div style="font-size:16px; font-weight:700; color:#856404;">Menunggu Approval Manager</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Laporan sudah disetujui SDM, menunggu approval Manager</div>
</div>
<div class="m-card">
    <div class="m-section-title" style="margin-bottom:12px;"><i class='bx bx-git-merge'></i> Progress</div>
    <div class="m-timeline">
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Laporan Diupload</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Disetujui SDM</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot warning"></div>
            <div class="m-timeline-label">⏳ Menunggu Manager</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot"></div>
            <div class="m-timeline-label muted">📄 Sertifikat Digital</div>
        </div>
    </div>
</div>

@elseif ($laporan_aktif && $laporan_aktif->status === 'Disetujui')
<!-- ═══ Laporan Disetujui (legacy) ═══ -->
<div class="m-card" style="border-left:4px solid #0dcaf0; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">ℹ️</div>
    <div style="font-size:16px; font-weight:700; color:#084298;">Dalam Antrian</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Sertifikat dalam antrian pembuatan oleh petugas</div>
</div>
<div class="m-card">
    <div class="m-section-title" style="margin-bottom:12px;"><i class='bx bx-git-merge'></i> Progress</div>
    <div class="m-timeline">
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Laporan Disetujui</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot"></div>
            <div class="m-timeline-label muted">🖨️ Cetak Sertifikat</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot"></div>
            <div class="m-timeline-label muted">✍️ Proses Tanda Tangan</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot"></div>
            <div class="m-timeline-label muted">📥 Siap Diunduh</div>
        </div>
    </div>
</div>

@elseif ($laporan_aktif && $laporan_aktif->status === 'Menunggu Manager')
<!-- ═══ Menunggu Manager ═══ -->
<div class="m-card" style="border-left:4px solid #ffc107; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">⏳</div>
    <div style="font-size:16px; font-weight:700; color:#856404;">Menunggu Persetujuan Manager</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Laporan Anda sudah direview SDM dan menunggu approval Manager</div>
</div>
<div class="m-card">
    <div class="m-section-title" style="margin-bottom:12px;"><i class='bx bx-git-merge'></i> Progress</div>
    <div class="m-timeline">
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Laporan Diupload</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot done"></div>
            <div class="m-timeline-label">✅ Disetujui SDM</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot warning"></div>
            <div class="m-timeline-label">⏳ Approval Manager</div>
        </div>
        <div class="m-timeline-item">
            <div class="m-timeline-dot"></div>
            <div class="m-timeline-label muted">📄 Sertifikat Digital</div>
        </div>
    </div>
</div>

@elseif ($laporan_aktif && $laporan_aktif->status === 'Ditolak')
<!-- ═══ Laporan Ditolak ═══ -->
<div class="m-card" style="border-left:4px solid #dc3545; text-align:center; padding:20px;">
    <div style="font-size:36px; margin-bottom:8px;">❌</div>
    <div style="font-size:16px; font-weight:700; color:#dc3545;">Laporan Ditolak</div>
    <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
        @php
            $alasan = $laporan_aktif->manager_keterangan_tolak ?: $laporan_aktif->sdm_keterangan_tolak ?: 'Silakan hubungi petugas';
        @endphp
        Alasan: {{ $alasan }}
    </div>
    <a href="{{ route('peserta.laporan') }}" class="action-btn action-btn-primary" style="margin-top:12px;">
        <i class='bx bx-upload'></i> Upload Ulang
    </a>
</div>

@else
<!-- ═══ Belum Ada ═══ -->
<div class="m-card" style="text-align:center; padding:32px;">
    <div style="font-size:48px; opacity:0.3; margin-bottom:8px;">🔒</div>
    <div style="font-size:16px; font-weight:700; color:var(--text-secondary);">Sertifikat Belum Tersedia</div>
    <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
        Sertifikat akan tersedia setelah laporan magang Anda disetujui oleh SDM dan Manager
    </div>
    <a href="{{ route('peserta.laporan') }}" class="action-btn action-btn-primary" style="margin-top:16px;">
        <i class='bx bx-upload'></i> Upload Laporan
    </a>
</div>
@endif
@endsection

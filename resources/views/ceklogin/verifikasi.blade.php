<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Verifikasi Sertifikat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verify-card {
            max-width: 500px;
            margin: 40px auto;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .verify-header {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .verify-header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .verify-header h3 {
            margin: 0;
            font-weight: 700;
        }
        .verify-header p {
            opacity: 0.9;
            margin: 5px 0 0;
        }
        .verify-body {
            padding: 24px;
            background: white;
        }
        .verify-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .verify-item:last-child { border-bottom: none; }
        .verify-item .label {
            font-size: 12px;
            color: #6c757d;
            min-width: 100px;
        }
        .verify-item .value {
            font-size: 14px;
            font-weight: 600;
            color: #212529;
        }
        .verify-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #d1e7dd;
            color: #0f5132;
        }
        .verify-footer {
            background: #f8f9fa;
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #6c757d;
        }
        .status-verified {
            color: #198754;
        }
        @media (max-width: 576px) {
            .verify-card { margin: 20px 16px; }
        }
    </style>
</head>
<body>
    @php
        $tgl_indo = function ($t) {
            if (empty($t) || $t == '0000-00-00') return '-';
            $b = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            $p = explode('-', $t);
            return (int)$p[2] . ' ' . $b[(int)$p[1]] . ' ' . $p[0];
        };
        $ttd_nama = $ttd->nama_ttd ?? '';
        $ttd_jabatan = $ttd->jabatan_ttd ?? '';
    @endphp
    <div class="verify-card">
        <div class="verify-header">
            <div class="icon"><i class='bx bx-check-shield'></i></div>
            <h3>Sertifikat Terverifikasi</h3>
            <p>Sertifikat ini diterbitkan secara digital dan sah</p>
        </div>
        <div class="verify-body">
            <div style="text-align:center; margin-bottom:16px;">
                <span class="verify-badge"><i class='bx bx-check-circle'></i> ASLI & VALID</span>
            </div>

            <div class="verify-item">
                <div class="label">Nama</div>
                <div class="value">{{ $sertifikat->nama }}</div>
            </div>
            <div class="verify-item">
                <div class="label">Asal Sekolah</div>
                <div class="value">{{ $sertifikat->asal_sekolah }}</div>
            </div>
            <div class="verify-item">
                <div class="label">Jurusan</div>
                <div class="value">{{ $sertifikat->jurusan }}</div>
            </div>
            <div class="verify-item">
                <div class="label">Bidang</div>
                <div class="value">{{ $sertifikat->nama_bidang ?? '-' }}</div>
            </div>
            <div class="verify-item">
                <div class="label">Status</div>
                <div class="value">{{ $sertifikat->status_peserta }}</div>
            </div>
            <div class="verify-item">
                <div class="label">Periode Magang</div>
                <div class="value">{{ $tgl_indo($sertifikat->tgl_masuk) }} &mdash; {{ $tgl_indo($sertifikat->tgl_keluar) }}</div>
            </div>
            <div class="verify-item">
                <div class="label">Diterbitkan</div>
                <div class="value">{{ $tgl_indo(date('Y-m-d', strtotime($sertifikat->generated_at ?? $sertifikat->manager_approved_at ?? ''))) }}</div>
            </div>
            @if ($ttd_nama)
            <div class="verify-item">
                <div class="label">Disetujui Oleh</div>
                <div class="value">{{ $ttd_nama }}{{ $ttd_jabatan ? ' (' . $ttd_jabatan . ')' : '' }}</div>
            </div>
            @endif
        </div>
        <div class="verify-footer">
            <i class='bx bx-lock-alt'></i> Dokumen ini diverifikasi secara digital &mdash; PKL System
        </div>
    </div>
</body>
</html>

@extends('layouts.peserta')

@section('content')
@php
    $tgl_indo_profile = function ($t) {
        if (empty($t) || $t == '0000-00-00') return '-';
        $b = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $p = explode('-', $t);
        return $p[2].' '.$b[(int)$p[1]].' '.$p[0];
    };
    $msg = session('message');
@endphp

@if ($msg)
    @php $parts = explode(':', $msg, 2); @endphp
<script>document.addEventListener('DOMContentLoaded', () => showToast('{{ addslashes($parts[1] ?? '') }}', '{{ $parts[0] }}'));</script>
@endif

@php
    // ── Turunan untuk tampilan saja (dihitung dari data yang sudah ada, tanpa query baru) ──
    $pf_nama  = ucwords($peserta->nama ?? $data_profile->nama ?? '');
    $pf_kata  = preg_split('/\s+/', trim($pf_nama));
    $pf_ini   = mb_strtoupper(mb_substr($pf_kata[0] ?? '', 0, 1)) . (count($pf_kata) > 1 ? mb_strtoupper(mb_substr($pf_kata[1], 0, 1)) : '');

    $pf_ok = false; $pf_status = ''; $pf_tone = 'grey'; $pf_total = 0; $pf_sisa = 0; $pf_jalan = 0; $pf_persen = 0;
    $pf_m = $peserta->tgl_masuk ?? '';
    $pf_k = $peserta->tgl_keluar ?? '';
    if (! empty($pf_m) && ! empty($pf_k) && $pf_m != '0000-00-00' && $pf_k != '0000-00-00'
        && strtotime($pf_m) && strtotime($pf_k) && strtotime($pf_k) >= strtotime($pf_m)) {
        $pf_ok  = true;
        $d_m    = strtotime($pf_m);
        $d_k    = strtotime($pf_k);
        $d_now  = strtotime(date('Y-m-d'));
        $pf_total = (int) round(($d_k - $d_m) / 86400) + 1;
        if ($d_now < $d_m) {
            $pf_status = 'Belum Dimulai'; $pf_tone = 'amber'; $pf_sisa = $pf_total; $pf_jalan = 0;
        } elseif ($d_now > $d_k) {
            $pf_status = 'Selesai'; $pf_tone = 'grey'; $pf_sisa = 0; $pf_jalan = $pf_total;
        } else {
            $pf_status = 'Peserta Aktif'; $pf_tone = 'green';
            $pf_sisa  = (int) round(($d_k - $d_now) / 86400);
            $pf_jalan = (int) round(($d_now - $d_m) / 86400) + 1;
        }
        $pf_persen = (int) round($pf_jalan / max(1, $pf_total) * 100);
    }
@endphp

<style>
    :root {
        --pf-blue: #0b4fb3;
        --pf-ink: #16233b;
        --pf-muted: #6b7a90;
        --pf-line: #e6ecf5;
        --pf-soft: #f4f7fc;
    }

    .pf-card {
        background: #fff; border-radius: 20px; padding: 16px; margin-bottom: 12px;
        box-shadow: 0 6px 22px rgba(24, 60, 120, .07);
        border: 1px solid rgba(230, 236, 245, .9);
    }
    .pf-head { font-size: 14px; font-weight: 800; color: var(--pf-ink); display: flex; align-items: center; gap: 8px; margin: 18px 4px 10px; }
    .pf-head i { color: var(--pf-blue); font-size: 19px; }

    /* ── Kartu identitas ── */
    .pf-hero { padding: 18px 16px 16px; background: linear-gradient(180deg, #e8f0ff 0%, #fff 70%); }
    .pf-id { display: flex; align-items: center; gap: 14px; }
    .pf-avatar {
        width: 72px; height: 72px; border-radius: 22px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; font-weight: 800; letter-spacing: .5px; color: #fff;
        background: linear-gradient(135deg, #2f7cf0, var(--pf-blue));
        box-shadow: 0 0 0 5px rgba(255, 255, 255, .75), 0 10px 22px rgba(11, 79, 179, .25);
    }
    .pf-id-body { flex: 1; min-width: 0; }
    .pf-name-row { display: flex; align-items: flex-start; gap: 6px; }
    .pf-name { flex: 1; min-width: 0; font-size: 18px; font-weight: 800; color: var(--pf-ink); line-height: 1.25; word-break: break-word; }
    .pf-user { font-size: 12px; color: var(--pf-muted); margin-top: 3px; font-weight: 600; word-break: break-all; }
    .pf-chip {
        display: inline-flex; align-items: center; gap: 5px; margin-top: 9px; padding: 4px 11px; border-radius: 999px;
        font-size: 11px; font-weight: 800; background: #d9f0e3; color: #0f5132;
    }
    .pf-chip::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .pf-chip.amber { background: #fff1c9; color: #7a5800; }
    .pf-chip.grey  { background: #e9edf3; color: #5b6b82; }

    .pf-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 16px; }
    .pf-stat { background: #fff; border: 1px solid var(--pf-line); border-radius: 16px; padding: 11px 6px; text-align: center; }
    .pf-stat-v { font-size: 20px; font-weight: 800; color: var(--pf-ink); line-height: 1.1; }
    .pf-stat-v small { font-size: 11px; font-weight: 700; color: var(--pf-muted); margin-left: 2px; }
    .pf-stat-l { font-size: 10.5px; font-weight: 700; color: var(--pf-muted); margin-top: 4px; }

    .pf-bar { height: 8px; border-radius: 999px; background: #dfe8f6; overflow: hidden; margin-top: 14px; }
    .pf-bar > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #2f7cf0, var(--pf-blue)); }
    .pf-bar-lbl { display: flex; justify-content: space-between; gap: 8px; font-size: 10.5px; color: var(--pf-muted); font-weight: 600; margin-top: 6px; }

    /* ── Baris informasi ── */
    .pf-info { padding: 6px 16px; }
    .pf-row { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-top: 1px dashed var(--pf-line); }
    .pf-row:first-child { border-top: 0; }
    .pf-row-ico { width: 40px; height: 40px; border-radius: 13px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .pf-row-body { flex: 1; min-width: 0; }
    .pf-row-l { font-size: 11px; font-weight: 700; color: var(--pf-muted); }
    .pf-row-v { font-size: 13.5px; font-weight: 700; color: var(--pf-ink); margin-top: 2px; line-height: 1.35; word-break: break-word; }
    .pf-edit, .pf-lock {
        width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0; border: 0; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: 19px;
    }
    .pf-edit { background: #e8f0ff; color: var(--pf-blue); transition: transform .15s ease; }
    .pf-edit:active { transform: scale(.93); }
    .pf-lock { background: transparent; color: #aab6c8; cursor: default; }
    .pf-edit.sm { width: 32px; height: 32px; border-radius: 10px; font-size: 16px; margin-top: -2px; }

    /* ── Form password ── */
    .pf-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--pf-ink); margin-bottom: 7px; }
    .pf-field { position: relative; }
    .pf-field > i.pf-lead { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 19px; color: var(--pf-muted); pointer-events: none; }
    .pf-input {
        width: 100%; height: 48px; border-radius: 14px; border: 1px solid var(--pf-line); background: #fff;
        padding: 0 46px 0 42px; font-family: inherit; font-size: 13.5px; color: var(--pf-ink); outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pf-input::placeholder { color: #9aa8bb; }
    .pf-input:focus { border-color: #2f7cf0; box-shadow: 0 0 0 4px rgba(47, 124, 240, .14); }
    .pf-eye { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); width: 38px; height: 38px; border: 0; background: transparent; color: var(--pf-muted); font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .pf-hint { font-size: 11px; color: var(--pf-muted); margin-top: 6px; }
    .pf-group { margin-bottom: 14px; }

    .pf-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 50px;
        border: 0; border-radius: 16px; text-decoration: none; cursor: pointer; font-family: inherit;
        font-size: 14px; font-weight: 800; transition: transform .15s ease;
    }
    .pf-btn:active { transform: scale(.98); }
    .pf-btn i { font-size: 20px; }
    .pf-btn.primary { background: linear-gradient(135deg, #2f7cf0, var(--pf-blue)); color: #fff; box-shadow: 0 8px 18px rgba(11, 79, 179, .30); }
    .pf-btn.danger { background: #fdecee; color: #c62828; border: 1px solid #f6c9ce; margin-bottom: 16px; }

    /* ── Admin ── */
    .pf-admin { display: flex; align-items: center; gap: 12px; }
    .pf-wa { width: 46px; height: 46px; border-radius: 15px; background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 25px; flex-shrink: 0; }
    .pf-admin-b { flex: 1; min-width: 0; }
    .pf-admin-n { font-size: 14px; font-weight: 800; color: var(--pf-ink); }
    .pf-admin-t { font-size: 12px; color: var(--pf-muted); margin-top: 2px; }
    .pf-chat {
        display: inline-flex; align-items: center; gap: 5px; height: 40px; padding: 0 16px; border-radius: 13px;
        background: #25d366; color: #fff; text-decoration: none; font-size: 12.5px; font-weight: 800; white-space: nowrap;
    }
    .pf-chat i { font-size: 18px; }

    .pf-foot { text-align: center; font-size: 10.5px; color: var(--pf-muted); padding-bottom: 10px; }

    .pf-btn:focus-visible, .pf-edit:focus-visible, .pf-eye:focus-visible, .pf-chat:focus-visible { outline: 3px solid rgba(47, 124, 240, .45); outline-offset: 2px; }

    @@media (max-width: 360px) {
        .pf-hero { padding: 16px 12px 14px; }
        .pf-avatar { width: 60px; height: 60px; font-size: 22px; border-radius: 18px; }
        .pf-name { font-size: 16px; }
        .pf-stat-v { font-size: 17px; }
    }
    @@media (min-width: 768px) {
        .pf-wrap { max-width: 640px; margin: 0 auto; }
    }
    @@media (prefers-reduced-motion: reduce) {
        .pf-btn, .pf-edit, .pf-input { transition: none; }
    }
</style>

<div class="pf-wrap">

@if ($peserta)
<!-- ═══ Identitas ═══ -->
<div class="pf-card pf-hero">
    <div class="pf-id">
        <div class="pf-avatar">{{ $pf_ini ?: '?' }}</div>
        <div class="pf-id-body">
            <div class="pf-name-row">
                <div class="pf-name">{{ $pf_nama }}</div>
                <button type="button" class="pf-edit sm" onclick="openEditProfilModal('nama')" aria-label="Ubah nama"><i class='bx bx-edit-alt'></i></button>
            </div>
            @if (! empty($data_profile->username))
            <div class="pf-user">{{ '@' . $data_profile->username }}</div>
            @endif
            @if ($pf_ok)
            <span class="pf-chip {{ $pf_tone }}">{{ $pf_status }}</span>
            @endif
        </div>
    </div>

    @if ($pf_ok)
    <div class="pf-stats">
        <div class="pf-stat">
            <div class="pf-stat-v">{{ $pf_sisa }}<small>hari</small></div>
            <div class="pf-stat-l">Sisa waktu</div>
        </div>
        <div class="pf-stat">
            <div class="pf-stat-v">{{ $pf_total }}<small>hari</small></div>
            <div class="pf-stat-l">Total durasi</div>
        </div>
        <div class="pf-stat">
            <div class="pf-stat-v">{{ $pf_persen }}<small>%</small></div>
            <div class="pf-stat-l">Berjalan</div>
        </div>
    </div>
    <div class="pf-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pf_persen }}"><span style="width: {{ $pf_persen }}%;"></span></div>
    <div class="pf-bar-lbl">
        <span>{{ $tgl_indo_profile($pf_m) }}</span>
        <span>{{ $tgl_indo_profile($pf_k) }}</span>
    </div>
    @endif
</div>

<!-- ═══ Informasi Peserta ═══ -->
<div class="pf-head"><i class='bx bx-id-card'></i> Informasi Peserta</div>
<div class="pf-card pf-info">
    <div class="pf-row">
        <div class="pf-row-ico" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-building-house'></i></div>
        <div class="pf-row-body">
            <div class="pf-row-l">Sekolah / Universitas</div>
            <div class="pf-row-v">{{ $peserta->asal_sekolah ?? '-' }}</div>
        </div>
        <button type="button" class="pf-edit" onclick="openEditProfilModal('sekolah')" aria-label="Ubah sekolah"><i class='bx bx-edit-alt'></i></button>
    </div>

    <div class="pf-row">
        <div class="pf-row-ico" style="background:#cfe2ff; color:#084298;"><i class='bx bx-book-reader'></i></div>
        <div class="pf-row-body">
            <div class="pf-row-l">Jurusan</div>
            <div class="pf-row-v">{{ $peserta->jurusan ?? '-' }}</div>
        </div>
        <button type="button" class="pf-edit" onclick="openEditProfilModal('jurusan')" aria-label="Ubah jurusan"><i class='bx bx-edit-alt'></i></button>
    </div>

    <div class="pf-row">
        <div class="pf-row-ico" style="background:#fff3cd; color:#856404;"><i class='bx bx-briefcase'></i></div>
        <div class="pf-row-body">
            <div class="pf-row-l">Bidang</div>
            <div class="pf-row-v">{{ $peserta->bidang->bidang ?? '-' }}</div>
        </div>
        <span class="pf-lock" title="Diatur oleh admin"><i class='bx bx-lock-alt'></i></span>
    </div>

    @if ($peserta->unit)
    <div class="pf-row">
        <div class="pf-row-ico" style="background:#e2d9f3; color:#6f42c1;"><i class='bx bx-buildings'></i></div>
        <div class="pf-row-body">
            <div class="pf-row-l">Unit</div>
            <div class="pf-row-v">{{ $peserta->unit }}</div>
        </div>
        <span class="pf-lock" title="Diatur oleh admin"><i class='bx bx-lock-alt'></i></span>
    </div>
    @endif

    <div class="pf-row">
        <div class="pf-row-ico" style="background:#d1ecf1; color:#0c5460;"><i class='bx bx-calendar'></i></div>
        <div class="pf-row-body">
            <div class="pf-row-l">Periode Magang</div>
            <div class="pf-row-v">{{ $tgl_indo_profile($peserta->tgl_masuk ?? '') }} — {{ $tgl_indo_profile($peserta->tgl_keluar ?? '') }}</div>
        </div>
        <span class="pf-lock" title="Diatur oleh admin"><i class='bx bx-lock-alt'></i></span>
    </div>
</div>
@endif

<!-- ═══ Modal Edit Profil ═══ -->
<div class="m-modal-overlay" id="modalEditProfil">
    <div class="m-bottom-sheet">
        <div class="m-bottom-sheet-handle"></div>
        <div class="m-bottom-sheet-title">Edit Profil</div>
        <form action="{{ route('peserta.profile.update') }}" method="post">
            @csrf
            <div class="m-form-group">
                <label class="m-form-label">Nama Lengkap</label>
                <input type="text" name="nama" id="editNama" class="m-form-input" value="{{ $peserta->nama ?? $data_profile->nama ?? '' }}" required>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Sekolah / Universitas</label>
                <input type="text" name="asal_sekolah" id="editSekolah" class="m-form-input" value="{{ $peserta->asal_sekolah ?? '' }}" required>
            </div>
            <div class="m-form-group">
                <label class="m-form-label">Jurusan</label>
                <input type="text" name="jurusan" id="editJurusan" class="m-form-input" value="{{ $peserta->jurusan ?? '' }}" required>
            </div>
            <button type="submit" name="submit_profile" class="action-btn action-btn-primary action-btn-block">
                <i class='bx bx-save'></i> Simpan Perubahan
            </button>
            <button type="button" class="action-btn action-btn-outline action-btn-block" style="margin-top:8px;" onclick="closeEditProfilModal()">
                Batal
            </button>
        </form>
    </div>
</div>

<!-- ═══ Ubah Password ═══ -->
<div class="pf-head"><i class='bx bx-lock-alt'></i> Ubah Password</div>
<div class="pf-card">
    <form action="{{ route('peserta.profile.update') }}" method="post">
        @csrf
        <div class="pf-group">
            <label class="pf-lbl" for="pw1">Password baru</label>
            <div class="pf-field">
                <i class='bx bx-key pf-lead'></i>
                <input type="password" name="password_baru" id="pw1" class="pf-input" placeholder="Minimal 5 karakter" minlength="5" required>
                <button type="button" class="pf-eye" onclick="togglePw('pw1', this)" aria-label="Tampilkan password">
                    <i class='bx bx-hide'></i>
                </button>
            </div>
            <div class="pf-hint">Minimal 5 karakter</div>
        </div>
        <div class="pf-group">
            <label class="pf-lbl" for="pw2">Konfirmasi password</label>
            <div class="pf-field">
                <i class='bx bx-lock-open-alt pf-lead'></i>
                <input type="password" name="konfirmasi_password" id="pw2" class="pf-input" placeholder="Ketik ulang password" required>
                <button type="button" class="pf-eye" onclick="togglePw('pw2', this)" aria-label="Tampilkan password">
                    <i class='bx bx-hide'></i>
                </button>
            </div>
        </div>
        <button type="submit" name="submit" class="pf-btn primary">
            <i class='bx bx-check-circle'></i> Ubah Password
        </button>
    </form>
</div>

<!-- ═══ Kontak Admin ═══ -->
<div class="pf-head"><i class='bx bx-headphone'></i> Hubungi Admin</div>
<div class="pf-card">
    <div class="pf-admin">
        <div class="pf-wa"><i class='bx bxl-whatsapp'></i></div>
        <div class="pf-admin-b">
            <div class="pf-admin-n">Admin Ubaid</div>
            <div class="pf-admin-t">0852 3322 3872</div>
        </div>
        <a href="https://wa.me/6285233223872?text=Halo%20Admin%2C%20saya%20peserta%20magang%20({{ urlencode(auth()->user()->nama ?? '') }})%20ingin%20bertanya"
           target="_blank" class="pf-chat">
            <i class='bx bxl-whatsapp'></i> Chat
        </a>
    </div>
</div>

<!-- ═══ Logout ═══ -->
<a href="{{ route('logout') }}" class="pf-btn danger">
    <i class='bx bx-log-out'></i> Keluar dari Akun
</a>

<div class="pf-foot">PKL Magang v2.0 · © 2026</div>

</div>
@endsection

@section('scripts')
<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bx bx-show';
    } else {
        input.type = 'password';
        icon.className = 'bx bx-hide';
    }
}

function openEditProfilModal(focusField) {
    const modal = document.getElementById('modalEditProfil');
    modal.classList.add('show');
    
    if (focusField === 'nama') {
        document.getElementById('editNama').focus();
    } else if (focusField === 'sekolah') {
        document.getElementById('editSekolah').focus();
    } else if (focusField === 'jurusan') {
        document.getElementById('editJurusan').focus();
    }
}

function closeEditProfilModal() {
    document.getElementById('modalEditProfil').classList.remove('show');
}

document.getElementById('modalEditProfil').addEventListener('click', function(e) {
    if (e.target === this) closeEditProfilModal();
});
</script>
@endsection
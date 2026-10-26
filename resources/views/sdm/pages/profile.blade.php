@php
    $namaBulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $bulanPendek = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    $user = $user ?? auth()->user();

    // Tanggal Bahasa Indonesia
    $tglIndo = function ($date) use ($namaBulan) {
        if (! $date) {
            return '-';
        }
        return $date->format('d') . ' ' . $namaBulan[(int) $date->format('n')] . ' ' . $date->format('Y');
    };

    // Stempel waktu untuk tabel log (tanggal + jam, WIB)
    $stempel = function ($date) use ($bulanPendek) {
        if (! $date) {
            return ['-', ''];
        }
        return [
            $date->format('d') . ' ' . $bulanPendek[(int) $date->format('n')] . ' ' . $date->format('Y'),
            $date->format('H:i:s') . ' WIB',
        ];
    };

    // Kelengkapan profil (data nyata)
    $cek = [
        (bool) $user->nama,
        (bool) $user->username,
        (bool) $user->role,
    ];
    $persen = (int) round(count(array_filter($cek)) / count($cek) * 100);

    // Deteksi browser & OS dari user agent (untuk sesi saat ini)
    $ua = request()->userAgent() ?? '';
    $browser = 'Browser tidak dikenal';
    foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $kunci => $nama) {
        if (stripos($ua, $kunci) !== false) { $browser = $nama; break; }
    }
    $os = 'OS tidak dikenal';
    foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $kunci => $nama) {
        if (stripos($ua, $kunci) !== false) { $os = $nama; break; }
    }
    $perangkatSaatIni = $browser . ' di ' . $os;

    // LOG: pakai $logs dari controller bila ada, jika tidak susun dari data akun.
    // Format tiap baris: ['icon','aktivitas','sub','perangkat','ip','waktu' (Carbon),'metode','status','tone']
    //   tone: ok | live | warn | muted
    if (! isset($logs)) {
        $logs = [];

        $loginTerakhir = $user->last_login_at ?? null;
        if ($loginTerakhir) {
            $logs[] = [
                'icon' => 'bx-log-in-circle', 'aktivitas' => 'Login Terakhir', 'sub' => 'Autentikasi berhasil',
                'perangkat' => $user->last_login_device ?? 'Perangkat tercatat', 'ip' => $user->last_login_ip ?? '-',
                'waktu' => $loginTerakhir, 'metode' => 'Username + password',
                'status' => 'Terverifikasi', 'tone' => 'ok',
            ];
        }

        $logs[] = [
            'icon' => 'bx-desktop', 'aktivitas' => 'Sesi Aktif Saat Ini', 'sub' => 'Sedang digunakan',
            'perangkat' => $perangkatSaatIni, 'ip' => request()->ip(),
            'waktu' => now(), 'metode' => 'Cookie sesi terenkripsi',
            'status' => 'Aktif', 'tone' => 'live',
        ];
    }
@endphp

{{-- ================= MISSION HEADER ================= --}}
<div class="pkl-mission">
    <div class="pkl-mission__eyebrow">
        <nav class="pkl-crumb" aria-label="breadcrumb">
            <a href="{{ url('sdm') }}">Beranda</a>
            <span class="pkl-crumb__sep"><i class="bx bx-chevron-right"></i></span>
            <span>Sistem PKL</span>
            <span class="pkl-crumb__sep"><i class="bx bx-chevron-right"></i></span>
            <span class="pkl-crumb__current">Profil</span>
        </nav>
        <span class="pkl-chip pkl-chip--ok">
            <span class="pkl-chip__dot pkl-chip__dot--live"></span>
            Akun {{ strtoupper($user->role) }} Aktif
        </span>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
        <div class="pkl-mission__text">
            <div class="sdm-title-row">
                <span class="sdm-title-ico"><i class="bx bx-user-circle"></i></span>
                <h1 class="pkl-mission__title">Profil Administrator {{ strtoupper($user->role) }}</h1>
            </div>
            <p class="pkl-mission__desc">
                Kelola identitas akun operasional, kredensial akses, dan integritas keamanan sistem PKL PLN Nusantara Power.
            </p>
        </div>
        <span class="pkl-chip pkl-chip--primary align-self-start align-self-md-auto">
            <i class="bx bx-shield-quarter"></i>
            Akses Terverifikasi
        </span>
    </div>
</div>

<div class="row g-4 align-items-start">

    {{-- ================= KARTU KIRI: PROFIL PENGGUNA ================= --}}
    <div class="col-12 col-lg-5">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class="bx bx-user-pin"></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Profil Pengguna</h2>
                        <span class="pkl-panel__sub">Kredensial {{ strtoupper($user->role) }} Terdaftar</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--muted pkl-mono">ID: USR-{{ $user->id }}</span>
            </div>

            <div class="pkl-panel__body">

                {{-- Avatar + aksi --}}
                <div class="d-flex flex-column align-items-center">
                    <div class="pkl-avatar">
                        <span class="pkl-avatar__glow"></span>
                        <div class="pkl-avatar__frame">
                            <img src="{{ asset('assets/images/team.png') }}" alt="Foto profil {{ $user->nama }}" class="pkl-avatar__img">
                            <span class="pkl-avatar__status">Aktif</span>
                        </div>
                    </div>
                </div>

                {{-- Identitas --}}
                <div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Nama Lengkap</span>
                        <span class="pkl-kv__value">{{ ucwords($user->nama ?: $user->username) }}</span>
                        <span class="pkl-kv__hint">Display: {{ strtoupper($user->role) }}</span>
                    </div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Username Akun</span>
                        <span class="pkl-kv__value pkl-kv__value--mono">{{ '@' . $user->username }}</span>
                    </div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Role / Divisi</span>
                        <span class="pkl-kv__value">{{ ucfirst($user->role) }}</span>
                    </div>
                    <div class="pkl-kv">
                        <span class="pkl-kv__label">Akun Terdaftar</span>
                        <span class="pkl-kv__value">{{ $tglIndo($user->created_at) }}</span>
                    </div>
                </div>

                {{-- Kelengkapan profil --}}
                <div class="pkl-meter">
                    <div class="pkl-meter__head">
                        <span>Kelengkapan Profil</span>
                        <span class="pkl-meter__value">{{ $persen }}%</span>
                    </div>
                    <div class="pkl-meter__track">
                        <div class="pkl-meter__bar" style="width: {{ $persen }}%"></div>
                    </div>
                    <div class="pkl-meter__foot">
                        <span>Nama, username, role</span>
                        <span class="pkl-mono">Lengkap</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ================= KARTU KANAN: PEMBARUAN KATA SANDI ================= --}}
    <div class="col-12 col-lg-7">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class="bx bx-lock-alt"></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Pembaruan Kata Sandi</h2>
                        <span class="pkl-panel__sub">Password Dienkripsi // Bcrypt</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--primary">Min. 6 Karakter</span>
            </div>

            <form class="pkl-panel__body" action="{{ route('sdm.profile.update') }}" method="post" id="passwordForm">
                @csrf

                {{-- Status password saat ini --}}
                <div class="pkl-field">
                    <div class="pkl-field__label">
                        <span>Status Password Saat Ini</span>
                        <small class="pkl-mono">Hash bcrypt</small>
                    </div>
                    <div class="pkl-stored">
                        <span class="pkl-stored__dots">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span>
                        <span class="pkl-stored__state"><i class="bx bx-check-circle"></i> Tersimpan Aman</span>
                    </div>
                </div>

                {{-- Password baru --}}
                <div class="pkl-field">
                    <div class="pkl-field__label">
                        <label for="password_baru">Password Baru</label>
                        <small style="color:#dc2626">* Wajib diisi</small>
                    </div>
                    <div class="pkl-field__control">
                        <span class="pkl-field__icon"><i class="bx bx-key"></i></span>
                        <input type="password" name="password_baru" id="password_baru" placeholder="Masukkan password baru" minlength="6" maxlength="100" autocomplete="new-password" required>
                        <button type="button" class="pkl-field__toggle" data-toggle-password="password_baru" title="Tampilkan password" aria-label="Tampilkan password baru">
                            <i class="bx bx-hide"></i>
                        </button>
                    </div>

                    <div class="pkl-strength">
                        <div class="pkl-strength__head">
                            <span>Kekuatan Password Baru</span>
                            <span class="pkl-strength__label" id="strengthLabel" data-level="0">Belum diisi</span>
                        </div>
                        <div class="pkl-strength__track">
                            <span class="pkl-strength__seg" data-seg="1"></span>
                            <span class="pkl-strength__seg" data-seg="2"></span>
                            <span class="pkl-strength__seg" data-seg="3"></span>
                            <span class="pkl-strength__seg" data-seg="4"></span>
                        </div>
                        <p class="pkl-field__help">
                            <i class="bx bx-info-circle"></i>
                            Minimal 6 karakter (disarankan kombinasi huruf kapital, angka, dan simbol).
                        </p>
                    </div>
                </div>

                {{-- Konfirmasi password --}}
                <div class="pkl-field">
                    <div class="pkl-field__label">
                        <label for="konfirmasi_password">Konfirmasi Password</label>
                        <span class="pkl-pill pkl-pill--muted" id="matchPill">Menunggu input</span>
                    </div>
                    <div class="pkl-field__control">
                        <span class="pkl-field__icon"><i class="bx bx-lock-open-alt"></i></span>
                        <input type="password" name="konfirmasi_password" id="konfirmasi_password" placeholder="Ketik ulang password baru" maxlength="100" autocomplete="new-password" required>
                        <button type="button" class="pkl-field__toggle" data-toggle-password="konfirmasi_password" title="Tampilkan password" aria-label="Tampilkan konfirmasi password">
                            <i class="bx bx-hide"></i>
                        </button>
                    </div>
                </div>

                {{-- Kebijakan keamanan --}}
                <div class="pkl-note">
                    <i class="bx bx-info-circle"></i>
                    <div>
                        <strong>Kebijakan Keamanan PLN Nusantara Power:</strong>
                        Kata sandi baru minimal 6 karakter. Sesi login tetap aktif setelah konfirmasi, namun hak akses
                        pada perangkat lain perlu melakukan login ulang.
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="d-flex flex-wrap gap-3 pt-1">
                    <button type="submit" class="pkl-btn pkl-btn--primary flex-grow-1" style="min-width:220px">
                        <i class="bx bx-lock"></i>
                        Ubah Password
                    </button>
                    <button type="reset" class="pkl-btn pkl-btn--ghost">
                        <i class="bx bx-sync pkl-icon-spin"></i>
                        Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ================= LOG INTEGRITAS & SESI TERAKHIR ================= --}}
<div class="pkl-panel pkl-panel--accent pkl-log">
    <div class="pkl-panel__head">
        <div class="d-flex align-items-center gap-2">
            <span class="pkl-panel__icon"><i class="bx bx-shield-alt-2"></i></span>
            <div>
                <h2 class="pkl-panel__title">Log Integritas &amp; Sesi Terakhir</h2>
                <span class="pkl-panel__sub">Audit trail otentikasi admin</span>
            </div>
        </div>
        <span class="pkl-log__sync">
            <span class="pkl-chip__dot pkl-chip__dot--live"></span>
            Tercatat otomatis
        </span>
    </div>

    @if (count($logs))
    <div class="pkl-log__wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Aktivitas Keamanan</th>
                    <th scope="col">Perangkat &amp; Alamat IP</th>
                    <th scope="col">Waktu Stempel</th>
                    <th scope="col">Metode Verifikasi</th>
                    <th scope="col">Status Hasil</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                @php [$tglLog, $jamLog] = $stempel($log['waktu'] ?? null); @endphp
                <tr>
                    <td>
                        <div class="pkl-log__act">
                            <i class="bx {{ $log['icon'] ?? 'bx-shield' }}"></i>
                            <div>
                                <b>{{ $log['aktivitas'] }}</b>
                                @if (! empty($log['sub']))<small>{{ $log['sub'] }}</small>@endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="pkl-log__mono">{{ $log['perangkat'] }}</span>
                        <span class="pkl-log__ip">IP {{ $log['ip'] }}</span>
                    </td>
                    <td>
                        <span class="pkl-log__mono">{{ $tglLog }}</span>
                        <span class="pkl-log__sub pkl-mono">{{ $jamLog }}</span>
                    </td>
                    <td><span class="pkl-log__method">{{ $log['metode'] }}</span></td>
                    <td><span class="pkl-badge pkl-badge--{{ $log['tone'] ?? 'muted' }}">{{ strtoupper($log['status']) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="pkl-log__empty">Belum ada aktivitas keamanan yang tercatat untuk akun ini.</div>
    @endif
</div>

@section('scripts')
<script>
$(document).ready(function () {

    // --- Toggle tampilan password ---
    $("[data-toggle-password]").on('click', function () {
        var input = $('#' + $(this).data('toggle-password'));
        var icon = $(this).find('i');
        var isText = input.attr('type') === 'text';

        input.attr('type', isText ? 'password' : 'text');
        icon.removeClass(isText ? 'bx-show' : 'bx-hide').addClass(isText ? 'bx-hide' : 'bx-show');
    });

    // --- Meter kekuatan password ---
    var passBaru = $('#password_baru');
    var konfirmasi = $('#konfirmasi_password');
    var labelStrength = $('#strengthLabel');
    var matchPill = $('#matchPill');

    function strengthLevel(value) {
        if (!value) return 0;

        var skor = 0;
        if (value.length >= 6) skor++;
        if (value.length >= 12) skor++;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value)) skor++;
        if (/[0-9]/.test(value)) skor++;
        if (/[^A-Za-z0-9]/.test(value)) skor++;

        if (skor <= 1) return 1;   // Lemah
        if (skor === 2) return 2;  // Cukup
        if (skor === 3) return 3;  // Baik
        return 4;                  // Kuat
    }

    var labelText = { 0: 'Belum diisi', 1: 'Lemah', 2: 'Cukup', 3: 'Baik', 4: 'Kuat' };

    function renderStrength() {
        var value = passBaru.val();
        var level = strengthLevel(value);

        labelStrength.text(labelText[level]).attr('data-level', level);

        $('.pkl-strength__seg').each(function (i) {
            var seg = i + 1;
            $(this).removeClass('is-on-1 is-on-2 is-on-3 is-on-4');
            if (seg <= level) {
                $(this).addClass('is-on-' + level);
            }
        });
    }

    function renderMatch() {
        var a = passBaru.val();
        var b = konfirmasi.val();

        if (!a && !b) {
            matchPill.attr('class', 'pkl-pill pkl-pill--muted').text('Menunggu input');
            return;
        }

        if (a && b && a === b) {
            matchPill.attr('class', 'pkl-pill pkl-pill--ok').html('<i class="bx bx-check-double"></i> Cocok');
        } else {
            matchPill.attr('class', 'pkl-pill pkl-pill--warn').html('<i class="bx bx-x"></i> Tidak cocok');
        }
    }

    passBaru.on('input', function () {
        renderStrength();
        renderMatch();
    });

    konfirmasi.on('input', renderMatch);

    $('#passwordForm').on('reset', function () {
        setTimeout(function () {
            renderStrength();
            renderMatch();
        }, 0);
    });

    renderStrength();
    renderMatch();
});
</script>
@endsection
@php
    use Illuminate\Support\Facades\DB;

    $cfg = DB::table('pengaturan_absensi')->where('id', 1)->first();
    $lat_val = $cfg->office_lat ?? -6.175392;
    $lng_val = $cfg->office_lng ?? 106.827153;
    $radius_val = $cfg->radius_meter ?? 100;
    $min_val = $cfg->min_durasi_keluar ?? 180;
@endphp

<div class="pkl-set">
    <div class="pkl-mission">
        <div class="pkl-mission__eyebrow">
            <nav class="pkl-crumb" aria-label="breadcrumb">
                <a href="{{ url('sdm') }}">Beranda</a>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span>Pengaturan</span>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span class="pkl-crumb__current">Lokasi &amp; Radius</span>
            </nav>
            <span class="pkl-chip pkl-chip--ok">
                <span class="pkl-chip__dot pkl-chip__dot--live"></span>
                Konfigurasi Aktif
            </span>
        </div>
        <div class="pkl-mission__text">
            <h1 class="pkl-mission__title">Lokasi &amp; Radius Absensi</h1>
            <p class="pkl-mission__desc">
                Tentukan titik pusat kantor, radius toleransi, dan jeda minimal absen pulang
                yang dipakai sistem untuk memvalidasi lokasi peserta.
            </p>
        </div>
    </div>

    <div class="set-grid">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bx-map-pin'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Koordinat &amp; Validasi</h2>
                        <span class="pkl-panel__sub">Konfigurasi GPS</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--primary">Wajib</span>
            </div>

            <form class="pkl-panel__body" action="{{ url('sdm/pengaturan/absensi') }}" method="POST">
                @csrf

                <div class="set-section">
                    <div class="set-section__head">
                        <h3 class="set-section__title"><i class='bx bx-map'></i> Titik Kantor</h3>
                        <span class="set-section__hint">WGS 84</span>
                    </div>
                    <div class="set-grid-2">
                        <div class="pkl-field">
                            <div class="pkl-field__label">
                                <span>Latitude</span>
                                <small class="pkl-mono">Garis lintang</small>
                            </div>
                            <div class="pkl-field__control">
                                <span class="pkl-field__icon"><i class='bx bx-map-pin'></i></span>
                                <input type="number" step="any" name="office_lat" id="office_lat" value="{{ $lat_val }}" required>
                            </div>
                            <p class="pkl-field__help"><i class='bx bx-info-circle'></i> Contoh: -6.175392</p>
                        </div>

                        <div class="pkl-field">
                            <div class="pkl-field__label">
                                <span>Longitude</span>
                                <small class="pkl-mono">Garis bujur</small>
                            </div>
                            <div class="pkl-field__control">
                                <span class="pkl-field__icon"><i class='bx bx-map-alt'></i></span>
                                <input type="number" step="any" name="office_lng" id="office_lng" value="{{ $lng_val }}" required>
                            </div>
                            <p class="pkl-field__help"><i class='bx bx-info-circle'></i> Contoh: 106.827153</p>
                        </div>
                    </div>
                </div>

                <div class="set-section">
                    <div class="set-section__head">
                        <h3 class="set-section__title"><i class='bx bx-target-lock'></i> Toleransi Absensi</h3>
                        <span class="set-section__hint">Validasi radius</span>
                    </div>
                    <div class="set-grid-2">
                        <div class="pkl-field">
                            <div class="pkl-field__label">
                                <span>Radius Toleransi</span>
                                <small class="pkl-mono">Min. 5</small>
                            </div>
                            <div class="pkl-field__control has-suffix">
                                <span class="pkl-field__icon"><i class='bx bx-current-location'></i></span>
                                <input type="number" min="5" name="radius_meter" value="{{ $radius_val }}" required>
                                <span class="set-input-suffix">Meter</span>
                            </div>
                            <p class="pkl-field__help">
                                <i class='bx bx-info-circle'></i>
                                Jarak maksimal peserta diizinkan absen dari titik koordinat.
                            </p>
                        </div>

                        <div class="pkl-field">
                            <div class="pkl-field__label">
                                <span>Jeda Absen Pulang</span>
                                <small class="pkl-mono">Menit</small>
                            </div>
                            <div class="pkl-field__control has-suffix">
                                <span class="pkl-field__icon"><i class='bx bx-time-five'></i></span>
                                <input type="number" min="0" name="min_durasi_keluar" value="{{ $min_val }}" required>
                                <span class="set-input-suffix">Menit</span>
                            </div>
                            <p class="pkl-field__help">
                                <i class='bx bx-info-circle'></i>
                                Default 180 menit. Isi 0 untuk menonaktifkan jeda.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="set-actions">
                    <button type="submit" name="save_settings" class="pkl-btn pkl-btn--primary">
                        <i class='bx bx-save'></i> Simpan Pengaturan
                    </button>
                    <button type="button" onclick="getCurrentLocationAdmin()" class="pkl-btn pkl-btn--ghost">
                        <i class='bx bx-current-location'></i> Gunakan Lokasi Saya Saat Ini
                    </button>
                </div>
            </form>
        </div>

        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bx-info-circle'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Panduan Koordinat</h2>
                        <span class="pkl-panel__sub">Langkah Operasional</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--muted">Google Maps</span>
            </div>

            <div class="pkl-panel__body">
                <ol class="set-steps">
                    <li class="set-step">
                        <span class="set-step__num">01</span>
                        <div class="set-step__text">Buka aplikasi <strong>Google Maps</strong> di browser Anda.</div>
                    </li>
                    <li class="set-step">
                        <span class="set-step__num">02</span>
                        <div class="set-step__text">Cari lokasi kantor atau instansi Anda pada peta.</div>
                    </li>
                    <li class="set-step">
                        <span class="set-step__num">03</span>
                        <div class="set-step__text"><strong>Klik kanan</strong> tepat pada titik lokasi kantor Anda di peta.</div>
                    </li>
                    <li class="set-step">
                        <span class="set-step__num">04</span>
                        <div class="set-step__text">
                            Pop-up kecil akan menampilkan koordinat di baris paling atas
                            (contoh: <code>-6.175392, 106.827153</code>).
                        </div>
                    </li>
                    <li class="set-step">
                        <span class="set-step__num">05</span>
                        <div class="set-step__text"><strong>Klik koordinat tersebut</strong> untuk menyalinnya otomatis.</div>
                    </li>
                    <li class="set-step">
                        <span class="set-step__num">06</span>
                        <div class="set-step__text">
                            Tempelkan angka pertama ke kolom <strong>Latitude</strong> dan angka kedua ke kolom
                            <strong>Longitude</strong> di halaman ini.
                        </div>
                    </li>
                </ol>

                <div class="pkl-note pkl-note--warn">
                    <i class='bx bx-error'></i>
                    <div>
                        <strong>Catatan</strong>
                        <p>
                            Jika peserta melakukan absensi di luar batas radius yang ditentukan,
                            absensi mereka otomatis <strong>diblokir</strong> oleh sistem dengan
                            notifikasi peringatan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function getCurrentLocationAdmin() {
    if (navigator.geolocation) {
        // Tampilkan loading di button
        const btn = event.target.closest('button');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Mencari GPS...';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('office_lat').value = position.coords.latitude;
                document.getElementById('office_lng').value = position.coords.longitude;
                btn.disabled = false;
                btn.innerHTML = origText;
                alert('Lokasi berhasil diambil dari GPS perangkat Anda!');
            },
            function(error) {
                btn.disabled = false;
                btn.innerHTML = origText;
                let msg = 'Gagal mendapatkan lokasi';
                if (error.code === 1) msg = 'Akses lokasi ditolak oleh browser. Mohon berikan izin lokasi.';
                else if (error.code === 2) msg = 'Posisi tidak tersedia.';
                else if (error.code === 3) msg = 'Waktu pengambilan lokasi habis.';
                alert(msg);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    } else {
        alert('Browser Anda tidak mendukung Geolocation.');
    }
}
</script>

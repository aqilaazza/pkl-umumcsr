@php
    use Illuminate\Support\Facades\DB;

    $cfg = DB::table('pengaturan_laporan')->where('id', 1)->first();
    $max_val = $cfg->max_ukuran_mb ?? 5;
@endphp

<div class="pkl-set">
    <div class="pkl-mission">
        <div class="pkl-mission__eyebrow">
            <nav class="pkl-crumb" aria-label="breadcrumb">
                <a href="{{ url('sdm') }}">Beranda</a>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span>Pengaturan</span>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span class="pkl-crumb__current">Laporan</span>
            </nav>
            <span class="pkl-chip pkl-chip--ok">
                <span class="pkl-chip__dot pkl-chip__dot--live"></span>
                Konfigurasi Aktif
            </span>
        </div>
        <div class="pkl-mission__text">
            <h1 class="pkl-mission__title">Pengaturan Laporan</h1>
            <p class="pkl-mission__desc">
                Batasi ukuran maksimal file laporan yang boleh diunggah peserta agar proses
                penyimpanan dan peninjauan tetap optimal.
            </p>
        </div>
    </div>

    <div class="set-grid">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bx-file'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Batas Ukuran File</h2>
                        <span class="pkl-panel__sub">Unggah Laporan</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--primary">PDF</span>
            </div>

            <form class="pkl-panel__body" action="{{ url('sdm/pengaturan/laporan') }}" method="POST">
                @csrf

                <div class="set-section">
                    <div class="set-section__head">
                        <h3 class="set-section__title"><i class='bx bx-cloud-upload'></i> Ukuran Maksimal</h3>
                        <span class="set-section__hint">Berlaku untuk semua peserta</span>
                    </div>

                    <div class="pkl-field">
                        <div class="pkl-field__label">
                            <span>Ukuran Maksimal File Laporan</span>
                            <small class="pkl-mono">Min. 1 MB</small>
                        </div>
                        <div class="pkl-field__control has-suffix">
                            <span class="pkl-field__icon"><i class='bx bx-data'></i></span>
                            <input type="number" min="1" name="max_ukuran_mb" value="{{ $max_val }}" required>
                            <span class="set-input-suffix">MB</span>
                        </div>
                        <p class="pkl-field__help">
                            <i class='bx bx-info-circle'></i>
                            Batas ukuran file PDF laporan yang boleh diunggah peserta. Default 5 MB.
                        </p>
                    </div>
                </div>

                <div class="set-actions">
                    <button type="submit" name="save_laporan" class="pkl-btn pkl-btn--primary">
                        <i class='bx bx-save'></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bx-info-circle'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Ringkasan Ketentuan</h2>
                        <span class="pkl-panel__sub">Kebijakan Sistem</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--muted">Otomatis</span>
            </div>

            <div class="pkl-panel__body">
                <div class="set-limit">
                    <span class="set-limit__icon"><i class='bx bx-file-blank'></i></span>
                    <div>
                        <span class="set-limit__label">Batas saat ini</span>
                        <span class="set-limit__value">{{ $max_val }}<small>MB</small></span>
                    </div>
                </div>

                <ul class="set-list">
                    <li>
                        <i class='bx bx-check-circle'></i>
                        <span>Pengaturan ini berlaku untuk semua peserta saat mengunggah laporan magang.</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle'></i>
                        <span>File yang melebihi batas akan otomatis ditolak oleh sistem.</span>
                    </li>
                    <li>
                        <i class='bx bx-check-circle'></i>
                        <span>Format file yang diterima adalah PDF sesuai ketentuan unggah laporan.</span>
                    </li>
                </ul>

                <div class="pkl-note pkl-note--warn">
                    <i class='bx bx-error'></i>
                    <div>
                        <strong>Catatan</strong>
                        <p>
                            Pastikan batas ukuran tidak terlalu kecil agar peserta tetap dapat
                            mengunggah laporan dengan lampiran yang lengkap.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

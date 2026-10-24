@php
    use Illuminate\Support\Facades\DB;

    $cfg = DB::table('pengaturan_ttd')->where('id', 1)->first();
    $nama_val = $cfg->nama_ttd ?? 'Sukarno';
    $jabatan_val = $cfg->jabatan_ttd ?? 'Manager Business Support';
@endphp

<div class="pkl-set">
    <div class="pkl-mission">
        <div class="pkl-mission__eyebrow">
            <nav class="pkl-crumb" aria-label="breadcrumb">
                <a href="{{ url('sdm') }}">Beranda</a>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span>Pengaturan</span>
                <span class="pkl-crumb__sep"><i class='bx bx-chevron-right'></i></span>
                <span class="pkl-crumb__current">Tanda Tangan Sertifikat</span>
            </nav>
            <span class="pkl-chip pkl-chip--ok">
                <span class="pkl-chip__dot pkl-chip__dot--live"></span>
                Konfigurasi Aktif
            </span>
        </div>
        <div class="pkl-mission__text">
            <h1 class="pkl-mission__title">Tanda Tangan Sertifikat</h1>
            <p class="pkl-mission__desc">
                Atur nama dan jabatan penandatangan yang akan dicetak pada sertifikat peserta magang.
            </p>
        </div>
    </div>

    <div class="set-grid">
        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bxs-certification'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Data Penandatangan</h2>
                        <span class="pkl-panel__sub">Sertifikat Peserta</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--primary">Wajib</span>
            </div>

            <form class="pkl-panel__body" action="{{ url('sdm/pengaturan/ttd') }}" method="POST">
                @csrf

                <div class="set-section">
                    <div class="set-section__head">
                        <h3 class="set-section__title"><i class='bx bx-user'></i> Identitas Pejabat</h3>
                        <span class="set-section__hint">Tampil di sertifikat</span>
                    </div>

                    <div class="pkl-field">
                        <div class="pkl-field__label">
                            <span>Nama Lengkap</span>
                            <small class="pkl-mono">Text</small>
                        </div>
                        <div class="pkl-field__control">
                            <span class="pkl-field__icon"><i class='bx bx-user-pin'></i></span>
                            <input type="text" name="nama_ttd" value="{{ $nama_val }}" required>
                        </div>
                        <p class="pkl-field__help">
                            <i class='bx bx-info-circle'></i>
                            Nama pejabat yang menandatangani sertifikat.
                        </p>
                    </div>

                    <div class="pkl-field">
                        <div class="pkl-field__label">
                            <span>Jabatan</span>
                            <small class="pkl-mono">Text</small>
                        </div>
                        <div class="pkl-field__control">
                            <span class="pkl-field__icon"><i class='bx bx-briefcase'></i></span>
                            <input type="text" name="jabatan_ttd" value="{{ $jabatan_val }}" required>
                        </div>
                        <p class="pkl-field__help">
                            <i class='bx bx-info-circle'></i>
                            Contoh: Manager Business Support, Kepala Unit, dll.
                        </p>
                    </div>
                </div>

                <div class="set-actions">
                    <button type="submit" name="save_ttd" class="pkl-btn pkl-btn--primary">
                        <i class='bx bx-save'></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        <div class="pkl-panel pkl-panel--accent">
            <div class="pkl-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="pkl-panel__icon"><i class='bx bx-pen'></i></span>
                    <div>
                        <h2 class="pkl-panel__title">Pratinjau Tanda Tangan</h2>
                        <span class="pkl-panel__sub">Hasil Cetak</span>
                    </div>
                </div>
                <span class="pkl-pill pkl-pill--muted">Live Preview</span>
            </div>

            <div class="pkl-panel__body">
                <div class="set-signature">
                    <span class="set-signature__label"><i class='bx bx-certification'></i> Penandatangan</span>
                    <div class="set-signature__name">{{ $nama_val }}</div>
                    <div class="set-signature__role">{{ $jabatan_val }}</div>
                    <div class="set-signature__line"></div>
                    <span class="set-signature__hint">Tanda tangan resmi</span>
                </div>

                <div class="pkl-note">
                    <i class='bx bx-printer'></i>
                    <div>
                        <strong>Catatan</strong>
                        <p>
                            Data ini akan digunakan saat mencetak sertifikat peserta melalui menu
                            <strong>Cetak Sertifikat</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

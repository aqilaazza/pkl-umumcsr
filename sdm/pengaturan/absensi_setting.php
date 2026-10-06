<?php
// ============================================================
// PUSAT - Pengaturan Lokasi & Radius Absensi (Geofencing)
// ============================================================
include "../conn/conn.php";

$message = '';

if (isset($_POST['save_settings'])) {
    $lat = floatval($_POST['office_lat'] ?? 0);
    $lng = floatval($_POST['office_lng'] ?? 0);
    $radius = intval($_POST['radius_meter'] ?? 100);

    // Cek apakah ada data di baris id=1
    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM pengaturan_absensi WHERE id = 1"));
    if ($cek) {
        $q = mysqli_query($conn, "UPDATE pengaturan_absensi SET office_lat = $lat, office_lng = $lng, radius_meter = $radius WHERE id = 1");
    } else {
        $q = mysqli_query($conn, "INSERT INTO pengaturan_absensi (id, office_lat, office_lng, radius_meter) VALUES (1, $lat, $lng, $radius)");
    }

    if ($q) {
        $message = '<div class="alert alert-success alert-dismissible fade show py-2">
            <i class="bx bxs-check-circle me-2"></i> Pengaturan lokasi & radius absensi berhasil disimpan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show py-2">
            <i class="bx bxs-error-circle me-2"></i> Gagal menyimpan pengaturan: ' . mysqli_error($conn) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// Ambil data pengaturan
$cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_absensi WHERE id = 1"));
$lat_val = $cfg['office_lat'] ?? -6.175392;
$lng_val = $cfg['office_lng'] ?? 106.827153;
$radius_val = $cfg['radius_meter'] ?? 100;
?>

<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pengaturan</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Lokasi & Radius Absensi</li>
            </ol>
        </nav>
    </div>
</div>

<?= $message ?>

<div class="row">
    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-success">
            <div class="card-body p-4">
                <div class="card-title d-flex align-items-center gap-2">
                    <i class="bx bx-map-pin text-success font-24"></i>
                    <h5 class="mb-0">Atur Koordinat Kantor & Radius</h5>
                </div>
                <hr />
                <form action="index.php?page=pengaturan_absensi" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Latitude (Garis Lintang)</label>
                        <input type="number" step="any" name="office_lat" id="office_lat" class="form-control" value="<?= $lat_val ?>" required>
                        <div class="form-text">Contoh: -6.175392</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Longitude (Garis Bujur)</label>
                        <input type="number" step="any" name="office_lng" id="office_lng" class="form-control" value="<?= $lng_val ?>" required>
                        <div class="form-text">Contoh: 106.827153</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Radius Toleransi (Meter)</label>
                        <input type="number" min="5" name="radius_meter" class="form-control" value="<?= $radius_val ?>" required>
                        <div class="form-text">Jarak maksimal peserta diizinkan absen dari titik koordinat (dalam satuan meter).</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="save_settings" class="btn btn-success px-4">
                            <i class="bx bx-save me-1"></i>Simpan Pengaturan
                        </button>
                        <button type="button" onclick="getCurrentLocationAdmin()" class="btn btn-outline-primary">
                            <i class="bx bx-current-location me-1"></i>Gunakan Lokasi Saya Saat Ini
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Panduan Mendapatkan Koordinat -->
    <div class="col-xl-6">
        <div class="card border-top border-0 border-4 border-info">
            <div class="card-body p-4">
                <h5 class="card-title mb-3 text-info"><i class="bx bx-info-circle me-1"></i> Cara Mendapatkan Koordinat Kantor</h5>
                <ol class="small text-muted ps-3">
                    <li class="mb-2">Buka aplikasi <strong>Google Maps</strong> di browser Anda.</li>
                    <li class="mb-2">Cari lokasi kantor atau instansi Anda pada peta.</li>
                    <li class="mb-2"><strong>Klik kanan</strong> tepat pada titik lokasi kantor Anda di peta.</li>
                    <li class="mb-2">Sebuah pop-up kecil akan muncul yang menampilkan koordinat di baris paling atas (contoh: <code>-6.175392, 106.827153</code>).</li>
                    <li class="mb-2"><strong>Klik koordinat tersebut</strong> untuk menyalinnya secara otomatis.</li>
                    <li class="mb-2">Tempelkan (paste) angka pertama ke kolom <strong>Latitude</strong> dan angka kedua ke kolom <strong>Longitude</strong> di halaman ini.</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="bx bx-error me-1 text-warning"></i> <strong>Catatan:</strong> Jika peserta melakukan absensi di luar batas radius yang ditentukan di atas, absensi mereka otomatis akan <strong>diblokir</strong> oleh sistem dengan notifikasi peringatan.
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

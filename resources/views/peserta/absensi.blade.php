@extends('layouts.peserta')

@section('content')
@php
    $hari_arr = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan_arr = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $today_label = $hari_arr[date('w')] . ', ' . date('d') . ' ' . $bulan_arr[(int)date('m')] . ' ' . date('Y');
@endphp

<!-- ═══ Clock Section ═══ -->
<div class="m-card" style="text-align:center; padding:24px 16px; margin-top:4px;">
    <div class="clock-time-display" id="liveClock">{{ date('H:i:s') }}</div>
    <div class="clock-date-display">{{ $today_label }}</div>

    @if (! $is_libur && (! $absensi_today || ($absensi_today->status === 'Hadir' && ! $absensi_today->jam_keluar)))
        <!-- Map Container -->
        <div id="map-container" style="margin: 16px 0; text-align: left;">
            <div id="map"></div>
            <div style="margin-top: 8px; font-size: 11px; color: var(--text-secondary); padding: 0 4px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <i class='bx bx-current-location' style="color: var(--primary); font-size: 13px; vertical-align: middle;"></i> 
                        <span id="userCoords" style="font-variant-numeric: tabular-nums;">Mencari GPS...</span>
                    </div>
                    <div id="distanceStatus" style="font-weight: 600;"></div>
                </div>
            </div>
        </div>
    @endif

    @if ($is_libur)
        <!-- Hari Libur -->
        <div class="clock-btn clock-btn-disabled" style="margin:0 auto;">
            <i class='bx bx-calendar-x'></i>
            <span>Libur</span>
        </div>
        <div style="margin-top:12px; padding:8px 16px; background:#cfe2ff; border-radius:8px; font-size:12px; color:#084298; display:inline-block;">
            📅 {{ $is_libur }}
        </div>

    @elseif (! $absensi_today || (! $absensi_today->jam_masuk && $absensi_today->status === 'Hadir'))
        <!-- Belum Absen Masuk -->
        <button class="clock-btn clock-btn-in" id="btnClockIn" onclick="doClockIn()">
            <i class='bx bx-log-in'></i>
            <span>Absen Masuk</span>
        </button>
        <div style="margin-top:12px; font-size:12px; color:var(--text-secondary);">
            <i class='bx bx-current-location'></i> Tekan untuk absen masuk (GPS aktif)
        </div>

    @elseif ($absensi_today && $absensi_today->jam_masuk && ! $absensi_today->jam_keluar && $absensi_today->status === 'Hadir')
        <!-- Sudah Absen Masuk, belum Pulang -->
        <button class="clock-btn clock-btn-out" id="btnClockOut" onclick="doClockOut()">
            <i class='bx bx-log-out'></i>
            <span>Absen Pulang</span>
        </button>
        <div style="margin-top:12px;">
            <span class="m-badge m-badge-hadir"><i class='bx bx-check'></i> Masuk: {{ substr($absensi_today->jam_masuk,0,5) }}</span>
        </div>

    @elseif ($absensi_today && $absensi_today->status === 'Izin')
        <div class="clock-btn clock-btn-disabled" style="margin:0 auto;">
            <i class='bx bx-envelope'></i>
            <span>Izin</span>
        </div>
        <div style="margin-top:8px; font-size:12px; color:#856404;">📝 Anda izin hari ini</div>

    @elseif ($absensi_today && $absensi_today->status === 'Sakit')
        <div class="clock-btn clock-btn-disabled" style="margin:0 auto;">
            <i class='bx bx-plus-medical'></i>
            <span>Sakit</span>
        </div>
        <div style="margin-top:8px; font-size:12px; color:#842029;">🏥 Anda sakit hari ini</div>

    @else
        <!-- Sudah Selesai -->
        <div class="clock-btn clock-btn-disabled" style="margin:0 auto;">
            <i class='bx bx-check-double'></i>
            <span>Selesai</span>
        </div>
        <div style="margin-top:12px; display:flex; justify-content:center; gap:12px;">
            <span class="m-badge m-badge-hadir"><i class='bx bx-log-in'></i> {{ substr($absensi_today->jam_masuk,0,5) }}</span>
            <span class="m-badge m-badge-ditolak" style="background:#d1e7dd; color:#0f5132;"><i class='bx bx-log-out'></i> {{ substr($absensi_today->jam_keluar,0,5) }}</span>
        </div>
    @endif
</div>

<!-- ═══ Tombol Izin / Sakit ═══ -->
@if (! $absensi_today || (! $absensi_today->jam_masuk && ! in_array($absensi_today->status ?? '', ['Izin','Sakit'])))
<div style="display:flex; gap:8px; margin-bottom:12px;">
    <button class="action-btn action-btn-warning" style="flex:1;" onclick="openIzinModal('Izin')">
        <i class='bx bx-envelope'></i> Izin
    </button>
    <button class="action-btn action-btn-danger" style="flex:1;" onclick="openIzinModal('Sakit')">
        <i class='bx bx-plus-medical'></i> Sakit
    </button>
</div>
@endif

<!-- ═══ Statistik Bulan Ini ═══ -->
<div class="m-section-title"><i class='bx bx-bar-chart-alt-2'></i> Statistik {{ $bulan_arr[(int)date('m')] }}</div>
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-value" style="color:#198754;">{{ $stat['Hadir'] ?? 0 }}</div>
        <div class="stat-label">Hadir</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#ffc107;">{{ $stat['Izin'] ?? 0 }}</div>
        <div class="stat-label">Izin</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#dc3545;">{{ $stat['Sakit'] ?? 0 }}</div>
        <div class="stat-label">Sakit</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#6c757d;">{{ $stat['Alpha'] ?? 0 }}</div>
        <div class="stat-label">Alpha</div>
    </div>
</div>

<!-- ═══ Kalender Mini ═══ -->
<div class="mini-calendar" style="margin-bottom:12px;">
    <div class="mini-calendar-header">
        <div class="mini-calendar-title" id="calTitle">{{ $bulan_arr[(int)date('m')] . ' ' . date('Y') }}</div>
        <div class="mini-calendar-nav">
            <button onclick="changeMonth(-1)"><i class='bx bx-chevron-left'></i></button>
            <button onclick="changeMonth(1)"><i class='bx bx-chevron-right'></i></button>
        </div>
    </div>
    <div class="mini-calendar-grid" id="calendarGrid">
        <!-- Day labels -->
        <div class="mini-calendar-day-label">Min</div>
        <div class="mini-calendar-day-label">Sen</div>
        <div class="mini-calendar-day-label">Sel</div>
        <div class="mini-calendar-day-label">Rab</div>
        <div class="mini-calendar-day-label">Kam</div>
        <div class="mini-calendar-day-label">Jum</div>
        <div class="mini-calendar-day-label">Sab</div>
    </div>
    <div class="calendar-legend">
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#d1e7dd;"></div> Hadir</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#fff3cd;"></div> Izin</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#f8d7da;"></div> Sakit</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#e2e3e5;"></div> Alpha</div>
        <div class="calendar-legend-item"><div class="calendar-legend-dot" style="background:#cfe2ff;"></div> Libur</div>
    </div>
</div>

<!-- ═══ Riwayat Absensi ═══ -->
<div class="m-section-title"><i class='bx bx-history'></i> Riwayat Terbaru</div>
<div class="m-card" style="padding:4px 16px;">
    @php $has_data = false; @endphp
    @foreach ($riwayat as $r)
        @php
            $has_data = true;
            $d = date('d/m', strtotime($r->tanggal));
            $day_name = $hari_arr[date('w', strtotime($r->tanggal))];
            $badge_class = strtolower($r->status);
            $icons_map = ['Hadir'=>'bx-check-circle','Izin'=>'bx-envelope','Sakit'=>'bx-plus-medical','Alpha'=>'bx-x-circle'];
        @endphp
    <div class="m-list-item">
        <div class="m-list-icon" style="background:{{ match($r->status) { 'Hadir'=>'#d1e7dd','Izin'=>'#fff3cd','Sakit'=>'#f8d7da', default=>'#e2e3e5' } }}; color:{{ match($r->status) { 'Hadir'=>'#0f5132','Izin'=>'#856404','Sakit'=>'#842029', default=>'#41464b' } }};">
            <i class='bx {{ $icons_map[$r->status] ?? 'bx-circle' }}'></i>
        </div>
        <div class="m-list-content">
            <div class="m-list-title">{{ $day_name }}, {{ $d }}</div>
            <div class="m-list-subtitle">
                @if ($r->jam_masuk)
                    Masuk: {{ substr($r->jam_masuk,0,5) }}
                    {{ $r->jam_keluar ? ' — Keluar: '.substr($r->jam_keluar,0,5) : '' }}
                @else
                    {{ $r->keterangan ? mb_strimwidth($r->keterangan,0,40,'...') : $r->status }}
                @endif
            </div>
        </div>
        <div class="m-list-action" style="display:flex; flex-direction:column; align-items:flex-end; gap:2px;">
            <span class="m-badge m-badge-{{ $badge_class }}">{{ $r->status }}</span>
            @if (in_array($r->status, ['Izin', 'Sakit']))
                @if ($r->approval_status === 'Pending')
                    <span style="font-size:9px; color:#ffc107; font-weight:600;"><i class='bx bx-time' style="vertical-align:middle;"></i> Pending</span>
                @elseif ($r->approval_status === 'Ditolak')
                    <span style="font-size:9px; color:#dc3545; font-weight:600;"><i class='bx bx-x-circle' style="vertical-align:middle;"></i> Ditolak</span>
                @else
                    <span style="font-size:9px; color:#198754; font-weight:600;"><i class='bx bx-check-circle' style="vertical-align:middle;"></i> Disetujui</span>
                @endif
            @endif
        </div>
    </div>
    @endforeach
    @if (! $has_data)
    <div class="m-empty">
        <i class='bx bx-calendar'></i>
        <div class="m-empty-text">Belum ada riwayat absensi</div>
    </div>
    @endif
</div>

<!-- ═══ Modal Izin/Sakit ═══ -->
<div class="m-modal-overlay" id="modalIzin">
    <div class="m-bottom-sheet">
        <div class="m-bottom-sheet-handle"></div>
        <div class="m-bottom-sheet-title" id="modalIzinTitle">Pengajuan Izin</div>
        <form id="formIzin" enctype="multipart/form-data">
            <input type="hidden" name="action" value="izin_sakit">
            <input type="hidden" name="status" id="izinStatus" value="Izin">

            <div style="display:flex; gap:10px; margin-bottom:12px;">
                <div style="flex:1;">
                    <label class="m-form-label">Mulai Tanggal</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="m-form-input" required value="{{ date('Y-m-d') }}">
                </div>
                <div style="flex:1;">
                    <label class="m-form-label">Selesai Tanggal</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="m-form-input" required value="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div class="m-form-group">
                <label class="m-form-label">Keterangan</label>
                <textarea name="keterangan" class="m-form-input" rows="3" placeholder="Tulis alasan izin/sakit..." required style="resize:none;"></textarea>
            </div>

            <div class="m-form-group">
                <label class="m-form-label">Upload Surat <span style="color:var(--text-muted); font-weight:400;">(opsional)</span></label>
                <input type="file" name="file_surat" class="m-form-input" accept=".jpg,.jpeg,.png,.pdf" style="padding:8px;">
                <div class="m-form-hint">Format: JPG, PNG, PDF — Maks 2MB</div>
            </div>

            <button type="submit" class="action-btn action-btn-primary action-btn-block" id="btnSubmitIzin">
                <i class='bx bx-send'></i> Kirim Pengajuan
            </button>
            <button type="button" class="action-btn action-btn-outline action-btn-block" style="margin-top:8px;" onclick="closeIzinModal()">
                Batal
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Live Clock ──
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    document.getElementById('liveClock').textContent = h + ':' + m + ':' + s;
}
setInterval(updateClock, 1000);

// ── Calendar ──
let calYear = {{ date('Y') }};
let calMonth = {{ date('n') }};

const bulanNames = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function changeMonth(dir) {
    calMonth += dir;
    if (calMonth > 12) { calMonth = 1; calYear++; }
    if (calMonth < 1) { calMonth = 12; calYear--; }
    loadCalendar();
}

function loadCalendar() {
    document.getElementById('calTitle').textContent = bulanNames[calMonth] + ' ' + calYear;
    const ym = calYear + '-' + String(calMonth).padStart(2,'0');

    fetch('{{ route('peserta.absensi.proses') }}?action=get_calendar&bulan=' + ym)
    .then(r => r.json())
    .then(data => {
        renderCalendar(data);
    });
}

function renderCalendar(data) {
    const grid = document.getElementById('calendarGrid');
    // Keep day labels
    const labels = grid.querySelectorAll('.mini-calendar-day-label');
    grid.innerHTML = '';
    labels.forEach(l => grid.appendChild(l));

    const firstDay = new Date(calYear, calMonth - 1, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth, 0).getDate();
    const todayStr = new Date().toISOString().split('T')[0];

    // Empty cells
    for (let i = 0; i < firstDay; i++) {
        const el = document.createElement('div');
        el.className = 'mini-calendar-day empty';
        grid.appendChild(el);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = calYear + '-' + String(calMonth).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const el = document.createElement('div');
        el.className = 'mini-calendar-day';
        el.textContent = d;

        const dateObj = new Date(calYear, calMonth - 1, d);
        const dayOfWeek = dateObj.getDay(); // 0 = Minggu, 6 = Sabtu

        if (dateStr === todayStr) el.classList.add('today');
        if (data.absensi && data.absensi[dateStr]) {
            el.classList.add(data.absensi[dateStr].toLowerCase());
        }
        if (data.libur && data.libur.includes(dateStr)) {
            el.classList.add('libur');
        }
        if (data.libur_pekan && data.libur_pekan.includes(dayOfWeek)) {
            el.classList.add('libur');
        }

        grid.appendChild(el);
    }
}

// Load initial calendar
loadCalendar();

// ── GPS Helper ──
function getLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('GPS tidak tersedia di browser ini');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            pos => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            err => {
                let msg = 'Gagal mendapatkan lokasi';
                if (err.code === 1) msg = 'Izin lokasi ditolak. Mohon aktifkan GPS dan izinkan akses lokasi.';
                else if (err.code === 2) msg = 'Lokasi tidak tersedia';
                else if (err.code === 3) msg = 'Waktu mendapatkan lokasi habis';
                reject(msg);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

// ── Geofencing Map Logic ──
let map = null;
let userMarker = null;
let officeMarker = null;
let geofenceCircle = null;

const officeLat = {{ $office_lat }};
const officeLng = {{ $office_lng }};
const geofenceRadius = {{ $radius_limit }};

function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000; // in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function initAttendanceMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl) return;

    // Initialize Leaflet Map
    map = L.map('map', {
        zoomControl: false,
        attributionControl: false
    }).setView([officeLat, officeLng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Marker Icon untuk Kantor (Warna Hijau)
    const officeIcon = L.divIcon({
        className: 'custom-div-icon',
        html: "<div style='background-color:#198754; width:12px; height:12px; border-radius:50%; border:2px solid white; box-shadow:0 0 4px rgba(0,0,0,0.5);'></div>",
        iconSize: [12, 12],
        iconAnchor: [6, 6]
    });

    officeMarker = L.marker([officeLat, officeLng], { icon: officeIcon })
        .addTo(map)
        .bindPopup('<b>Titik Absensi (Kantor)</b>');

    // Lingkaran Radius Geofence (Default: Kuning sebelum GPS terdeteksi)
    geofenceCircle = L.circle([officeLat, officeLng], {
        radius: geofenceRadius,
        color: '#ffc107',
        fillColor: '#fff3cd',
        fillOpacity: 0.15,
        weight: 1.5
    }).addTo(map);

    // Ambil Lokasi User
    getLocation().then(loc => {
        const userLat = loc.lat;
        const userLng = loc.lng;

        document.getElementById('userCoords').textContent = userLat.toFixed(6) + ', ' + userLng.toFixed(6);

        const dist = getDistance(userLat, userLng, officeLat, officeLng);
        const isInside = dist <= geofenceRadius;
        
        const statusEl = document.getElementById('distanceStatus');
        if (isInside) {
            statusEl.innerHTML = '<span style="color:#198754;"><i class="bx bxs-check-circle"></i> Dalam Radius (' + Math.round(dist) + 'm)</span>';
            geofenceCircle.setStyle({
                color: '#198754',
                fillColor: '#d1e7dd'
            });
        } else {
            statusEl.innerHTML = '<span style="color:#dc3545;"><i class="bx bxs-x-circle"></i> Luar Radius (' + Math.round(dist) + 'm)</span>';
            geofenceCircle.setStyle({
                color: '#dc3545',
                fillColor: '#f8d7da'
            });
        }

        // Marker Icon untuk User (Lingkaran biru pulsing)
        const userIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color:#0d6efd; width:14px; height:14px; border-radius:50%; border:2px solid white; box-shadow:0 0 6px rgba(13,110,253,0.8); animation: mapPulse 1.8s infinite;'></div>",
            iconSize: [14, 14],
            iconAnchor: [7, 7]
        });

        userMarker = L.marker([userLat, userLng], { icon: userIcon })
            .addTo(map)
            .bindPopup('<b>Posisi Anda</b>');

        // Zoom otomatis agar muat marker Kantor & User
        const group = new L.featureGroup([officeMarker, userMarker]);
        map.fitBounds(group.getBounds().pad(0.35));

    }).catch(err => {
        document.getElementById('userCoords').textContent = 'Gagal mengakses GPS';
        document.getElementById('distanceStatus').innerHTML = '<span style="color:#dc3545;">GPS Tidak Aktif</span>';
        console.warn(err);
    });
}

// Jalankan Inisialisasi Peta
initAttendanceMap();

// ── Absen Masuk ──
async function doClockIn() {
    const btn = document.getElementById('btnClockIn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>Mencari GPS...</span>';

    try {
        const loc = await getLocation();
        const fd = new FormData();
        fd.append('action', 'clock_in');
        fd.append('lat', loc.lat);
        fd.append('lng', loc.lng);
        fd.append('_token', '{{ csrf_token() }}');

        const resp = await fetch('{{ route('peserta.absensi.proses') }}', { method: 'POST', body: fd });
        const result = await resp.json();

        if (result.success) {
            showToast('Absen Masuk berhasil! ' + result.jam, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-log-in"></i><span>Absen Masuk</span>';
        }
    } catch (e) {
        showToast(e, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-log-in"></i><span>Absen Masuk</span>';
    }
}

// ── Absen Pulang ──
async function doClockOut() {
    const btn = document.getElementById('btnClockOut');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>Mencari GPS...</span>';

    try {
        const loc = await getLocation();
        const fd = new FormData();
        fd.append('action', 'clock_out');
        fd.append('lat', loc.lat);
        fd.append('lng', loc.lng);
        fd.append('_token', '{{ csrf_token() }}');

        const resp = await fetch('{{ route('peserta.absensi.proses') }}', { method: 'POST', body: fd });
        const result = await resp.json();

        if (result.success) {
            showToast('Absen Pulang berhasil! ' + result.jam, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bx bx-log-out"></i><span>Absen Pulang</span>';
        }
    } catch (e) {
        showToast(e, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bx bx-log-out"></i><span>Absen Pulang</span>';
    }
}

// ── Modal Izin/Sakit ──
function openIzinModal(status) {
    document.getElementById('izinStatus').value = status;
    document.getElementById('modalIzinTitle').textContent = 'Pengajuan ' + status;
    document.getElementById('modalIzin').classList.add('show');
}

function closeIzinModal() {
    document.getElementById('modalIzin').classList.remove('show');
}

// Close modal on overlay click
document.getElementById('modalIzin').addEventListener('click', function(e) {
    if (e.target === this) closeIzinModal();
});

// ── Submit Izin/Sakit ──
document.getElementById('formIzin').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitIzin');
    btn.disabled = true;
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Mengirim...';

    const fd = new FormData(this);
    fd.append('_token', '{{ csrf_token() }}');

    try {
        const resp = await fetch('{{ route('peserta.absensi.proses') }}', { method: 'POST', body: fd });
        const result = await resp.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeIzinModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bx bx-send"></i> Kirim Pengajuan';
});
</script>
@endsection

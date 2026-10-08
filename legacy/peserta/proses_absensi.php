<?php
// ============================================================
// PESERTA - Backend AJAX Handler Absensi
// ============================================================
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

include "../conn/conn.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'peserta') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$username = $_SESSION['username'];
$esc_user = mysqli_real_escape_string($conn, $username);
$today = date('Y-m-d');

// Determine action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function get_distance_meters($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

switch ($action) {

    // ═══════════════════════════════════════════════════
    // CLOCK IN
    // ═══════════════════════════════════════════════════
    case 'clock_in':
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);

        // Geofencing Check
        $cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_absensi WHERE id = 1"));
        if ($cfg) {
            $office_lat = floatval($cfg['office_lat']);
            $office_lng = floatval($cfg['office_lng']);
            $radius_limit = intval($cfg['radius_meter']);
            
            $distance = get_distance_meters($lat, $lng, $office_lat, $office_lng);
            if ($distance > $radius_limit) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal: Lokasi Anda terlalu jauh (' . round($distance) . ' meter dari kantor). Batas radius: ' . $radius_limit . ' meter.'
                ]);
                exit;
            }
        }

        // Cek hari libur
        $libur = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT * FROM hari_libur WHERE tanggal='$today'"));
        if (!$libur) {
            $day_idx = date('w');
            $libur_pekan = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT * FROM libur_pekan WHERE hari_index='$day_idx'"));
            if ($libur_pekan) {
                $libur = ['keterangan' => 'Libur Pekan (' . $libur_pekan['nama_hari'] . ')'];
            }
        }
        if ($libur) {
            echo json_encode(['success' => false, 'message' => 'Hari ini libur: ' . $libur['keterangan']]);
            exit;
        }

        // Cek sudah absen hari ini
        $existing = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM absensi_peserta WHERE username='$esc_user' AND tanggal='$today'"));
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah absen hari ini']);
            exit;
        }

        $jam = date('H:i:s');
        $stmt = $conn->prepare("INSERT INTO absensi_peserta (username, tanggal, jam_masuk, status, lat_masuk, lng_masuk) VALUES (?, ?, ?, 'Hadir', ?, ?)");
        $stmt->bind_param("sssdd", $username, $today, $jam, $lat, $lng);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Absen Masuk berhasil', 'jam' => substr($jam, 0, 5)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
        }
        break;

    // ═══════════════════════════════════════════════════
    // ABSEN PULANG
    // ═══════════════════════════════════════════════════
    case 'clock_out':
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);

        // Geofencing Check
        $cfg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan_absensi WHERE id = 1"));
        if ($cfg) {
            $office_lat = floatval($cfg['office_lat']);
            $office_lng = floatval($cfg['office_lng']);
            $radius_limit = intval($cfg['radius_meter']);
            
            $distance = get_distance_meters($lat, $lng, $office_lat, $office_lng);
            if ($distance > $radius_limit) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Gagal: Lokasi Anda terlalu jauh (' . round($distance) . ' meter dari kantor). Batas radius: ' . $radius_limit . ' meter.'
                ]);
                exit;
            }
        }

        // Cek sudah absen masuk
        $existing = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM absensi_peserta WHERE username='$esc_user' AND tanggal='$today' AND status='Hadir'"));
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Anda belum absen masuk hari ini']);
            exit;
        }
        if ($existing['jam_keluar']) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah absen pulang hari ini']);
            exit;
        }

        $jam = date('H:i:s');
        $stmt = $conn->prepare("UPDATE absensi_peserta SET jam_keluar=?, lat_keluar=?, lng_keluar=? WHERE username=? AND tanggal=?");
        $stmt->bind_param("sddss", $jam, $lat, $lng, $username, $today);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Absen Pulang berhasil', 'jam' => substr($jam, 0, 5)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
        }
        break;

    // ═══════════════════════════════════════════════════
    // IZIN / SAKIT
    // ═══════════════════════════════════════════════════
    case 'izin_sakit':
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['Izin', 'Sakit'])) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            exit;
        }

        $keterangan = trim($_POST['keterangan'] ?? '');
        if (empty($keterangan)) {
            echo json_encode(['success' => false, 'message' => 'Keterangan wajib diisi']);
            exit;
        }

        $tanggal_mulai = $_POST['tanggal_mulai'] ?? $today;
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? $today;

        // Validasi format tanggal (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_selesai)) {
            echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid']);
            exit;
        }

        $start = new DateTime($tanggal_mulai);
        $end = new DateTime($tanggal_selesai);

        if ($start > $end) {
            echo json_encode(['success' => false, 'message' => 'Tanggal mulai tidak boleh melebihi tanggal selesai']);
            exit;
        }

        // Hitung jarak hari
        $diff = $start->diff($end)->days;
        if ($diff > 31) {
            echo json_encode(['success' => false, 'message' => 'Maksimal durasi pengajuan izin/sakit adalah 31 hari']);
            exit;
        }

        // Handle file upload (opsional, upload sekali untuk seluruh rentang tanggal)
        $file_surat = null;
        if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file_surat'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($ext, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Format file tidak didukung (JPG, PNG, PDF)']);
                exit;
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
                exit;
            }

            $upload_dir = '../uploads/surat_absensi/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $saved_name = 'surat_' . $esc_user . '_' . date('Ymd_His') . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $saved_name)) {
                $file_surat = $saved_name;
            }
        }

        // Perulangan untuk tiap tanggal dalam rentang
        $end->modify('+1 day'); // inclusive
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        $success_count = 0;
        $fail_count = 0;

        foreach ($period as $date) {
            $tgl = $date->format('Y-m-d');
            
            // Simpan atau update jika duplikat
            $stmt = $conn->prepare("INSERT INTO absensi_peserta (username, tanggal, status, keterangan, file_surat, approval_status) 
                VALUES (?, ?, ?, ?, ?, 'Pending') 
                ON DUPLICATE KEY UPDATE status=?, keterangan=?, file_surat=?, approval_status='Pending'");
            
            $stmt->bind_param("ssssssss", $username, $tgl, $status, $keterangan, $file_surat, $status, $keterangan, $file_surat);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }

        if ($success_count > 0) {
            $msg = "Pengajuan " . $status . " berhasil disimpan untuk " . $success_count . " hari.";
            if ($fail_count > 0) $msg .= " Namun " . $fail_count . " hari gagal.";
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pengajuan']);
        }
        break;

    // ═══════════════════════════════════════════════════
    // GET STATUS HARI INI
    // ═══════════════════════════════════════════════════
    case 'get_status':
        $data = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM absensi_peserta WHERE username='$esc_user' AND tanggal='$today'"));
        $libur = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT * FROM hari_libur WHERE tanggal='$today'"));
        if (!$libur) {
            $day_idx = date('w');
            $libur_pekan = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT * FROM libur_pekan WHERE hari_index='$day_idx'"));
            if ($libur_pekan) {
                $libur = ['keterangan' => 'Libur Pekan (' . $libur_pekan['nama_hari'] . ')'];
            }
        }

        echo json_encode([
            'success' => true,
            'absensi' => $data,
            'libur' => $libur
        ]);
        break;

    // ═══════════════════════════════════════════════════
    // GET CALENDAR DATA
    // ═══════════════════════════════════════════════════
    case 'get_calendar':
        $bulan = $_GET['bulan'] ?? date('Y-m');
        $bulan = preg_replace('/[^0-9\-]/', '', $bulan);

        // Absensi bulan ini
        $absensi = [];
        $q = mysqli_query($conn, "SELECT tanggal, status, approval_status FROM absensi_peserta 
            WHERE username='$esc_user' AND DATE_FORMAT(tanggal,'%Y-%m')='$bulan'");
        while ($r = mysqli_fetch_assoc($q)) {
            if ($r['approval_status'] === 'Pending') {
                $absensi[$r['tanggal']] = $r['status'] . '_pending';
            } elseif ($r['approval_status'] === 'Ditolak') {
                $absensi[$r['tanggal']] = $r['status'] . '_ditolak';
            } else {
                $absensi[$r['tanggal']] = $r['status'];
            }
        }

        // Hari libur bulan ini
        $libur = [];
        $q2 = mysqli_query($conn, "SELECT tanggal FROM hari_libur 
            WHERE DATE_FORMAT(tanggal,'%Y-%m')='$bulan'");
        while ($r2 = mysqli_fetch_assoc($q2)) {
            $libur[] = $r2['tanggal'];
        }

        // Libur pekan terdaftar
        $libur_pekan = [];
        $q3 = mysqli_query($conn, "SELECT hari_index FROM libur_pekan");
        while ($r3 = mysqli_fetch_assoc($q3)) {
            $libur_pekan[] = intval($r3['hari_index']);
        }

        echo json_encode([
            'success' => true,
            'absensi' => $absensi,
            'libur' => $libur,
            'libur_pekan' => $libur_pekan
        ]);
        break;

    // ═══════════════════════════════════════════════════
    // GET RIWAYAT
    // ═══════════════════════════════════════════════════
    case 'get_riwayat':
        $limit = intval($_GET['limit'] ?? 10);
        $data = [];
        $q = mysqli_query($conn, "SELECT * FROM absensi_peserta 
            WHERE username='$esc_user' ORDER BY tanggal DESC LIMIT $limit");
        while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}
?>

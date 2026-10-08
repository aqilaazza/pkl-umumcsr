<?php
session_start();
if (!isset($_SESSION['id'])) {
	header("Location: ../index.php");
	exit();
}
if ($_SESSION['role'] !== 'peserta') {
    header("Location: ../index.php");
    exit();
}

date_default_timezone_set('Asia/Jakarta');
include "../conn/conn.php";

// Determine active page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Map pages to nav items for active state
$nav_map = [
    'dashboard' => 'home',
    'absensi' => 'absensi',
    'upload_laporan' => 'laporan',
    'referensi_laporan' => 'laporan',
    'dokumen' => 'dokumen',
    'sertifikat' => 'sertifikat',
    'profile' => 'profil',
    'kontak' => 'profil',
];
$active_nav = isset($nav_map[$page]) ? $nav_map[$page] : 'home';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#198754">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="Portal Peserta PKL - Absensi, Laporan & Sertifikat Magang">
    <title>PKL Magang</title>

    <!-- Favicon -->
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="../assets/images/logo.png">
    <link rel="manifest" href="../manifest.json">

    <!-- CSS -->
    <link href="../assets/css/mobile-app.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Page body reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            overscroll-behavior: none;
        }

        /* Hide scrollbar but allow scroll */
        body::-webkit-scrollbar { width: 0; height: 0; }

        /* Prevent iOS bounce */
        html { overflow: hidden; height: 100%; }
        body { overflow-y: auto; height: 100%; -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="mobile-app">

    <!-- ═══ Top App Bar ═══ -->
    <div class="app-topbar">
        <div class="topbar-avatar">
            <i class='bx bxs-user'></i>
        </div>
        <div class="topbar-info" style="flex: 1; min-width: 0; padding-right: 8px;">
            <div class="topbar-greeting" style="font-size: 10px; opacity: 0.85; line-height: 1.1;">Selamat datang,</div>
            <div class="topbar-name" style="font-size: 14px; font-weight: 700; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars(ucwords($_SESSION['nama'] ?? 'Peserta')) ?></div>
            <?php
            $username_esc = mysqli_real_escape_string($conn, $_SESSION['username'] ?? '');
            $topbar_peserta = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT p.asal_sekolah, p.unit, b.bidang as nama_bidang 
                FROM peserta p LEFT JOIN bidang b ON p.bidang_id = b.id 
                WHERE p.username = '$username_esc'
            "));
            if ($topbar_peserta):
                $sub_parts = [];
                if ($topbar_peserta['asal_sekolah']) $sub_parts[] = $topbar_peserta['asal_sekolah'];
                if ($topbar_peserta['nama_bidang']) $sub_parts[] = $topbar_peserta['nama_bidang'];
                if ($topbar_peserta['unit']) $sub_parts[] = $topbar_peserta['unit'];
                $sub_text = implode(' · ', $sub_parts);
            ?>
                <div class="topbar-sub" style="font-size: 9px; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.1; margin-top: 1px; color: #fff; font-weight: 400;"><?= htmlspecialchars($sub_text) ?></div>
            <?php endif; ?>
        </div>
        <div class="topbar-actions">
            <a href="index.php?page=profile" class="topbar-btn" title="Profil">
                <i class='bx bx-cog'></i>
            </a>
        </div>
    </div>

    <!-- ═══ Main Content ═══ -->
    <div class="app-content">
        <?php
        switch ($page) {
            case 'dashboard':
                include "home.php";
                break;
            case 'absensi':
                include "absensi.php";
                break;
            case 'upload_laporan':
                include "upload_laporan.php";
                break;
            case 'referensi_laporan':
                include "referensi_laporan.php";
                break;
            case 'sertifikat':
                include "sertifikat.php";
                break;
            case 'dokumen':
                include "dokumen.php";
                break;
            case 'profile':
                include "profile.php";
                break;
            case 'kontak':
                include "kontak.php";
                break;
            default:
                include "home.php";
                break;
        }
        ?>
    </div>

    <!-- ═══ Bottom Navigation ═══ -->
    <nav class="bottom-nav">
        <a href="index.php?page=dashboard" class="bottom-nav-item <?= $active_nav === 'home' ? 'active' : '' ?>">
            <i class='bx <?= $active_nav === 'home' ? 'bxs-home' : 'bx-home' ?>'></i>
            <span>Home</span>
        </a>
        <a href="index.php?page=absensi" class="bottom-nav-item <?= $active_nav === 'absensi' ? 'active' : '' ?>">
            <i class='bx <?= $active_nav === 'absensi' ? 'bxs-calendar-check' : 'bx-calendar-check' ?>'></i>
            <span>Absensi</span>
        </a>
        <a href="index.php?page=upload_laporan" class="bottom-nav-item <?= $active_nav === 'laporan' ? 'active' : '' ?>">
            <i class='bx <?= $active_nav === 'laporan' ? 'bxs-file' : 'bx-file' ?>'></i>
            <span>Laporan</span>
        </a>
        <a href="index.php?page=dokumen" class="bottom-nav-item <?= $active_nav === 'dokumen' ? 'active' : '' ?>">
            <i class='bx <?= $active_nav === 'dokumen' ? 'bxs-folder-open' : 'bx-folder-open' ?>'></i>
            <span>Dokumen</span>
        </a>
        <a href="index.php?page=sertifikat" class="bottom-nav-item <?= $active_nav === 'sertifikat' ? 'active' : '' ?>">
            <i class='bx <?= $active_nav === 'sertifikat' ? 'bxs-award' : 'bx-award' ?>'></i>
            <span>Sertifikat</span>
        </a>
        <a href="index.php?page=profile" class="bottom-nav-item <?= $active_nav === 'profil' ? 'active' : '' ?>">
            <i class='bx <?= $active_nav === 'profil' ? 'bxs-user' : 'bx-user' ?>'></i>
            <span>Profil</span>
        </a>
    </nav>

    <!-- ═══ Toast Container ═══ -->
    <div id="toast-container"></div>

    <!-- ═══ Scripts ═══ -->
    <script>
    // Ripple effect
    document.querySelectorAll('.ripple, .action-btn, .clock-btn').forEach(el => {
        el.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // Toast notification using SweetAlert2
    function showToast(message, type = 'success', duration = 3000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    // Confirmation Alert
    function showAlert(title, text, icon = 'info', confirmButtonText = 'OK') {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonText: confirmButtonText,
            customClass: {
                confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
        });
    }
    </script>
</body>
</html>
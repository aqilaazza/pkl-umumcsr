<?php
session_start();
include __DIR__ . "/../conn/conn.php";

$bidang_list = [];
$result_bidang = mysqli_query($conn, "SELECT id, bidang FROM bidang ORDER BY bidang");
if ($result_bidang) {
    while ($row = mysqli_fetch_assoc($result_bidang)) {
        $bidang_list[$row['id']] = $row['bidang'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Akun Peserta - Website PKL</title>
    <!--favicon-->
	<link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --bs-primary: #198754; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f0f5f1;
            color: #333;
            min-height: 100vh;
            padding: 40px 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container-box {
            width: 100%;
            max-width: 600px;
        }

        /* Header */
        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-section h4 {
            font-weight: 700;
            color: #212529;
            margin-bottom: 5px;
        }

        .header-section p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Card */
        .card-custom {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: none;
            overflow: hidden;
        }

        .card-body-custom { padding: 35px; }

        /* Input */
        .form-label-custom {
            font-size: 0.8rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            display: block;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
        }

        .input-custom {
            width: 100%;
            padding: 12px 18px 12px 45px;
            border: 2px solid #eef1f6;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s;
            outline: none;
            background: #f8fafc;
        }

        .input-custom:focus {
            border-color: #198754;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 1.2rem;
        }

        /* Dropdown */
        .dropdown-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0; right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: 1px solid #eef1f6;
            max-height: 250px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .result-item {
            padding: 12px 18px;
            border-bottom: 1px solid #f8f9fa;
            cursor: pointer;
            transition: background 0.15s;
        }

        .result-item:last-child { border-bottom: none; }
        .result-item:hover { background: #e8f5e9; }
        .result-item .name { font-weight: 600; font-size: 0.9rem; color: #212529; }
        .result-item .school { font-size: 0.75rem; color: #6c757d; }

        /* Info Card */
        .info-highlight {
            background: #198754;
            border-radius: 15px;
            padding: 20px;
            color: white;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .info-highlight::after {
            content: "\eb71";
            font-family: 'boxicons';
            position: absolute;
            right: -10px;
            bottom: -15px;
            font-size: 80px;
            opacity: 0.1;
        }

        .label-top {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .cred-box {
            display: flex;
            gap: 15px;
        }

        .cred-item {
            flex: 1;
            background: rgba(255,255,255,0.15);
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            cursor: pointer;
            transition: 0.2s;
        }

        .cred-item:hover { background: rgba(255,255,255,0.25); }
        .cred-label { font-size: 0.65rem; opacity: 0.7; margin-bottom: 2px; text-transform: uppercase; }
        .cred-val { font-size: 1.1rem; font-weight: 700; }

        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .profile-card {
            background: #f8fafc;
            border: 1px solid #eef1f6;
            padding: 12px 15px;
            border-radius: 12px;
        }

        .p-label { font-size: 0.65rem; color: #6c757d; text-transform: uppercase; font-weight: 700; margin-bottom: 3px; }
        .p-val { font-weight: 600; font-size: 0.85rem; color: #333; }

        .badge-status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .status-aktif { background: #e1f2e9; color: #198754; }
        .status-menunggu { background: #fff8e1; color: #f59e0b; }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #198754;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.2s;
        }
        .back-link a:hover { color: #0f5132; }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #198754;
            color: #fff;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 0.85rem;
            z-index: 9999;
            display: none;
        }

        @media (max-width: 500px) {
            .profile-grid { grid-template-columns: 1fr; }
            .cred-box { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container-box">
    <div class="header-section">
        <img src="../assets/images/logo.png" width="120" class="mb-3" alt="Logo">
        <h4>Cek Akun Peserta</h4>
        <p>Lupa username? Cari data Anda di bawah ini.</p>
    </div>

    <div class="card-custom">
        <div class="card-body-custom">
            <span class="form-label-custom">Cari Nama Peserta</span>
            <div class="input-group-custom">
                <i class='bx bx-search input-icon'></i>
                <input type="text" id="inputCari" class="input-custom" placeholder="Ketik nama Anda..." autocomplete="off">
                <div id="dropdown" class="dropdown-results"></div>
            </div>

            <div id="hasilContainer">
                <div class="text-center py-4 opacity-50">
                    <i class='bx bx-user-circle' style="font-size: 50px;"></i>
                    <p class="small mt-2">Gunakan kolom pencarian di atas untuk<br>menemukan detail akun Anda.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="back-link">
        <a href="../index.php"><i class='bx bx-left-arrow-alt me-1'></i>Kembali ke Login</a>
    </div>
</div>

<div id="toast">Berhasil disalin!</div>

<script src="../assets/js/jquery.min.js"></script>
<script>
    const bidangData = <?php echo json_encode($bidang_list); ?>;
    const inputCari = document.getElementById('inputCari');
    const dropdown = document.getElementById('dropdown');
    const hasilContainer = document.getElementById('hasilContainer');

    function showToast(msg) {
        $("#toast").text(msg).fadeIn().delay(2000).fadeOut();
    }

    function copyText(text, label) {
        navigator.clipboard.writeText(text);
        showToast(label + ' berhasil disalin');
    }

    let timeout = null;
    inputCari.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        timeout = setTimeout(async () => {
            dropdown.innerHTML = '<div class="p-3 text-center small text-muted">Mencari...</div>';
            dropdown.style.display = 'block';

            try {
                const res = await fetch(`search_peserta.php?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                
                if (data.length === 0) {
                    dropdown.innerHTML = '<div class="p-3 text-center small text-danger">Data tidak ditemukan</div>';
                } else {
                    dropdown.innerHTML = data.map((item) => `
                        <div class="result-item" onclick="pilihPeserta(${item.id})">
                            <div class="name">${item.nama}</div>
                            <div class="school">${item.asal_sekolah}</div>
                        </div>
                    `).join('');
                }
            } catch (err) {
                dropdown.innerHTML = '<div class="p-3 text-center small text-danger">Terjadi kesalahan</div>';
            }
        }, 300);
    });

    async function pilihPeserta(id) {
        dropdown.style.display = 'none';
        inputCari.value = '';
        hasilContainer.innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1 text-primary"></i></div>';
        
        try {
            const res = await fetch(`get_peserta.php?id=${id}`);
            const p = await res.json();
            
            const bidang = bidangData[p.bidang_id] || '-';
            const badgeCls = p.status_magang.toLowerCase() === 'aktif' ? 'status-aktif' : 'status-menunggu';
            const tgl = p.tgl_masuk ? new Date(p.tgl_masuk).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) : '-';

            const html = `
                <div class="info-highlight">
                    <div class="label-top">Informasi Login</div>
                    <div class="cred-box">
                        <div class="cred-item" onclick="copyText('${p.username}', 'Username')">
                            <div class="cred-label">Username</div>
                            <div class="cred-val">${p.username}</div>
                        </div>
                        <div class="cred-item" onclick="copyText('12345', 'Password')">
                            <div class="cred-label">Password Default</div>
                            <div class="cred-val">12345</div>
                        </div>
                    </div>
                </div>

                <div class="form-label-custom mb-3">Detail Profil</div>
                <div class="profile-grid">
                    <div class="profile-card">
                        <div class="p-label">Nama Lengkap</div>
                        <div class="p-val">${p.nama}</div>
                    </div>
                    <div class="profile-card">
                        <div class="p-label">Status Magang</div>
                        <div class="p-val"><span class="badge-status ${badgeCls}">${p.status_magang}</span></div>
                    </div>
                    <div class="profile-card">
                        <div class="p-label">Asal Instansi</div>
                        <div class="p-val">${p.asal_sekolah}</div>
                    </div>
                    <div class="profile-card">
                        <div class="p-label">Bidang</div>
                        <div class="p-val">${bidang}</div>
                    </div>
                    <div class="profile-card">
                        <div class="p-label">Tanggal Masuk</div>
                        <div class="p-val">${tgl}</div>
                    </div>
                    <div class="profile-card">
                        <div class="p-label">Unit Kerja</div>
                        <div class="p-val">${p.unit}</div>
                    </div>
                </div>
            `;
            hasilContainer.innerHTML = html;
        } catch (err) {
            hasilContainer.innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
        }
    }

    document.addEventListener('click', (e) => {
        if (!inputCari.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>

</body>
</html>
<?php
// index.php
session_start();
include "../conn/conn.php";

// Ambil data bidang dan unit untuk filter
$bidang_list = [];
$result_bidang = mysqli_query($conn, "SELECT id, bidang FROM bidang ORDER BY bidang");
if ($result_bidang) {
    while ($row = mysqli_fetch_assoc($result_bidang)) {
        $bidang_list[$row['id']] = $row['bidang'];
    }
}

$unit_list = ['Unit 1-2', 'Unit 9'];

// Proses ubah status via AJAX
if (isset($_POST['action']) && $_POST['action'] == 'ubah_status') {
    $id = (int)$_POST['id'];
    $status_sekarang = mysqli_real_escape_string($conn, $_POST['status_sekarang']);
    
    // Tentukan status baru
    if ($status_sekarang == 'Menunggu') {
        $status_baru = 'Aktif';
    } else if ($status_sekarang == 'Aktif') {
        $status_baru = 'Selesai';
    } else {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }
    
    $query = "UPDATE peserta SET status_magang = '$status_baru' WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'status_baru' => $status_baru]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update: ' . mysqli_error($conn)]);
    }
    exit;
}

// Ambil parameter filter
$filter_unit = isset($_GET['unit']) ? $_GET['unit'] : '';
$filter_bidang = isset($_GET['bidang']) ? (int)$_GET['bidang'] : 0;

// Bangun query
$query = "SELECT p.*, b.bidang as nama_bidang 
          FROM peserta p 
          LEFT JOIN bidang b ON p.bidang_id = b.id 
          WHERE p.status_magang IN ('Aktif', 'Menunggu')";

if (!empty($filter_unit)) {
    $filter_unit = mysqli_real_escape_string($conn, $filter_unit);
    $query .= " AND p.unit = '$filter_unit'";
}

if ($filter_bidang > 0) {
    $query .= " AND p.bidang_id = $filter_bidang";
}

$query .= " ORDER BY p.tgl_masuk ASC";

$result = mysqli_query($conn, $query);

// Hitung total per status
$query_count = "SELECT 
                    SUM(CASE WHEN status_magang = 'Aktif' THEN 1 ELSE 0 END) as total_aktif,
                    SUM(CASE WHEN status_magang = 'Menunggu' THEN 1 ELSE 0 END) as total_menunggu,
                    COUNT(*) as total_all
                FROM peserta 
                WHERE status_magang IN ('Aktif', 'Menunggu')";
$count_result = mysqli_query($conn, $query_count);
$count_data = mysqli_fetch_assoc($count_result);

// Ambil range tanggal min dan max
$query_range = "SELECT 
                    MIN(tgl_masuk) as tgl_min,
                    MAX(tgl_keluar) as tgl_max
                FROM peserta 
                WHERE status_magang IN ('Aktif', 'Menunggu')";

if (!empty($filter_unit)) {
    $query_range .= " AND unit = '$filter_unit'";
}

if ($filter_bidang > 0) {
    $query_range .= " AND bidang_id = $filter_bidang";
}

$range_result = mysqli_query($conn, $query_range);
$range_data = mysqli_fetch_assoc($range_result);

$monthly_data = [];
$max_monthly = 0;

if ($range_data['tgl_min'] && $range_data['tgl_max']) {
    $start_date = new DateTime($range_data['tgl_min']);
    $end_date = new DateTime($range_data['tgl_max']);
    
    // Generate semua bulan dalam rentang
    $current = clone $start_date;
    $current->modify('first day of this month');
    
    while ($current <= $end_date) {
        $bulan_str = $current->format('Y-m');
        $label = $current->format('F Y');
        
        // Hitung peserta yang aktif di bulan ini (tgl_masuk <= akhir bulan AND tgl_keluar >= awal bulan)
        $bulan_pertama = $current->format('Y-m-01');
        $bulan_terakhir = $current->format('Y-m-t');
        
        $query_count_month = "SELECT COUNT(*) as jumlah
                              FROM peserta 
                              WHERE status_magang IN ('Aktif', 'Menunggu')
                              AND tgl_masuk <= '$bulan_terakhir'
                              AND tgl_keluar >= '$bulan_pertama'";
        
        if (!empty($filter_unit)) {
            $query_count_month .= " AND unit = '$filter_unit'";
        }
        
        if ($filter_bidang > 0) {
            $query_count_month .= " AND bidang_id = $filter_bidang";
        }
        
        $count_month_result = mysqli_query($conn, $query_count_month);
        $count_month = mysqli_fetch_assoc($count_month_result);
        $jumlah = $count_month['jumlah'];
        
        $monthly_data[] = [
            'bulan' => $bulan_str,
            'label' => $label,
            'jumlah' => $jumlah
        ];
        
        if ($jumlah > $max_monthly) {
            $max_monthly = $jumlah;
        }
        
        // Lanjut ke bulan berikutnya
        $current->modify('first day of next month');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Peserta Aktif & Menunggu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .box-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
        }

        .box-header h1 {
            font-size: 1.5rem;
            font-weight: 500;
            color: #222;
        }

        .box-header p {
            color: #666;
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .box-body {
            padding: 20px;
        }

        /* Info box */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-card {
            background: #f8f8f8;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }

        .info-card .label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }

        .info-card .value {
            font-size: 2rem;
            font-weight: 500;
            color: #222;
        }

        .filter-box {
            background: #f8f8f8;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-item {
            flex: 1 1 200px;
        }

        .filter-item label {
            display: block;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 4px;
        }

        .filter-item select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            background: #e6e6e6;
            color: #333;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #4a6fa5;
            color: white;
        }

        .btn-primary:hover {
            background: #3a5a87;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        table th {
            text-align: left;
            padding: 12px 10px;
            background: #f0f0f0;
            font-weight: 500;
            color: #444;
        }

        table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        table tr:hover {
            background: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
            background: #f0f0f0;
        }

        .status-badge.aktif {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.menunggu {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.selesai {
            background: #e2e3e5;
            color: #383d41;
        }

        .btn-status {
            padding: 5px 12px;
            border: none;
            border-radius: 4px;
            background: #e6e6e6;
            color: #333;
            cursor: pointer;
            font-size: 0.85rem;
            min-width: 100px;
        }

        .btn-status:hover {
            opacity: 0.8;
        }

        .btn-status.menunggu {
            background: #fff3cd;
            color: #856404;
        }

        .btn-status.aktif {
            background: #d4edda;
            color: #155724;
        }

        .footer {
            text-align: center;
            padding: 16px;
            color: #777;
            font-size: 0.8rem;
            border-top: 1px solid #eee;
        }

        .notif {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .notif.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .notif.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        /* Breakdown per bulan */
        .breakdown-section {
            margin-bottom: 20px;
        }

        .breakdown-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 12px;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .breakdown-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            transition: all 0.2s ease;
        }

        .breakdown-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-color: #4a6fa5;
        }

        .breakdown-card.empty {
            opacity: 0.6;
        }

        .breakdown-card.empty:hover {
            opacity: 1;
        }

        .breakdown-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .breakdown-month {
            font-size: 0.85rem;
            color: #666;
            font-weight: 500;
        }

        .breakdown-count {
            font-size: 1.4rem;
            font-weight: 600;
            color: #4a6fa5;
        }

        .breakdown-bar {
            width: 100%;
            height: 6px;
            background: #e8e8e8;
            border-radius: 3px;
            overflow: hidden;
        }

        .breakdown-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a6fa5 0%, #6a9fd8 100%);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Notifikasi floating -->
        <div id="notif" class="notif"></div>

        <div class="box">
            <div class="box-header">
                <h1>Daftar Peserta Magang</h1>
                <p>Peserta status Aktif dan Menunggu - Urut berdasarkan tanggal masuk</p>
            </div>
            
            <div class="box-body">
                <!-- Info box -->
                <div class="info-grid">
                    <div class="info-card">
                        <div class="label">Total Aktif</div>
                        <div class="value" id="totalAktif"><?php echo $count_data['total_aktif']; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="label">Total Menunggu</div>
                        <div class="value" id="totalMenunggu"><?php echo $count_data['total_menunggu']; ?></div>
                    </div>
                    <div class="info-card">
                        <div class="label">Total Keseluruhan</div>
                        <div class="value" id="totalAll"><?php echo $count_data['total_all']; ?></div>
                    </div>
                </div>

                <!-- Filter -->
                <form method="GET" class="filter-box">
                    <div class="filter-item">
                        <label>Unit</label>
                        <select name="unit">
                            <option value="">Semua Unit</option>
                            <?php foreach ($unit_list as $unit): ?>
                            <option value="<?php echo $unit; ?>" <?php echo $filter_unit == $unit ? 'selected' : ''; ?>>
                                <?php echo $unit; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Bidang</label>
                        <select name="bidang">
                            <option value="0">Semua Bidang</option>
                            <?php foreach ($bidang_list as $id => $bidang): ?>
                            <option value="<?php echo $id; ?>" <?php echo $filter_bidang == $id ? 'selected' : ''; ?>>
                                <?php echo $bidang; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="index.php" class="btn">Reset</a>
                    </div>
                </form>

                <!-- Breakdown per bulan -->
                <?php if (count($monthly_data) > 0): ?>
                <div class="breakdown-section">
                    <div class="breakdown-title">Breakdown Peserta Magang Per Bulan</div>
                    <div class="breakdown-grid">
                        <?php foreach ($monthly_data as $data): ?>
                        <div class="breakdown-card <?php echo $data['jumlah'] == 0 ? 'empty' : ''; ?>">
                            <div class="breakdown-header">
                                <span class="breakdown-month"><?php echo $data['label']; ?></span>
                                <span class="breakdown-count"><?php echo $data['jumlah']; ?></span>
                            </div>
                            <div class="breakdown-bar">
                                <div class="breakdown-bar-fill" style="width: <?php echo ($data['jumlah'] / max($max_monthly, 1)) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabel data -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Asal Sekolah</th>
                                <th>Jurusan</th>
                                <th>Bidang</th>
                                <th>Unit</th>
                                <th>Tgl Masuk</th>
                                <th>Tgl Keluar</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php $no = 1; ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr id="row-<?php echo $row['id']; ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['status_magang']); ?>" id="status-<?php echo $row['id']; ?>">
                                            <?php echo $row['status_magang']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['asal_sekolah']); ?></td>
                                    <td><?php echo htmlspecialchars($row['jurusan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_bidang'] ?: '-'); ?></td>
                                    <td><?php echo $row['unit']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_masuk'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tgl_keluar'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                                    <td>
                                        <button class="btn-status <?php echo strtolower($row['status_magang']); ?>" 
                                                onclick="ubahStatus(<?php echo $row['id']; ?>, '<?php echo $row['status_magang']; ?>', this)">
                                            <?php 
                                            if ($row['status_magang'] == 'Menunggu') {
                                                echo 'Jadikan Aktif';
                                            } else if ($row['status_magang'] == 'Aktif') {
                                                echo 'Selesaikan';
                                            }
                                            ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 30px; color: #777;">
                                        Tidak ada data peserta dengan status Aktif/Menunggu
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footer">
                Sistem Informasi Magang - <?php echo date('Y'); ?>
            </div>
        </div>
    </div>

    <script>
        function ubahStatus(id, statusSekarang, button) {
            if (!confirm('Yakin mau ubah status peserta ini?')) {
                return;
            }

            // Disable button biar ga diklik 2x
            button.disabled = true;
            button.textContent = 'Proses...';

            const formData = new FormData();
            formData.append('action', 'ubah_status');
            formData.append('id', id);
            formData.append('status_sekarang', statusSekarang);

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update tampilan status
                    const statusSpan = document.getElementById('status-' + id);
                    statusSpan.className = 'status-badge ' + data.status_baru.toLowerCase();
                    statusSpan.textContent = data.status_baru;
                    
                    // Update tombol
                    if (data.status_baru == 'Aktif') {
                        button.className = 'btn-status aktif';
                        button.textContent = 'Selesaikan';
                        button.onclick = function() { ubahStatus(id, 'Aktif', this); };
                    } else if (data.status_baru == 'Selesai') {
                        // Kalau jadi selesai, hapus baris
                        const row = document.getElementById('row-' + id);
                        if (row) {
                            row.remove();
                        }
                    }
                    
                    // Update total di info box
                    updateTotal();
                    
                    // Tampilkan notifikasi sukses
                    tampilNotif('Status berhasil diubah!', 'success');
                    
                } else {
                    // Kembalikan tombol ke keadaan semula
                    button.disabled = false;
                    if (statusSekarang == 'Menunggu') {
                        button.className = 'btn-status menunggu';
                        button.textContent = 'Jadikan Aktif';
                    } else {
                        button.className = 'btn-status aktif';
                        button.textContent = 'Selesaikan';
                    }
                    
                    tampilNotif('Gagal: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                tampilNotif('Terjadi kesalahan koneksi', 'error');
            });
        }

        function tampilNotif(pesan, tipe) {
            const notif = document.getElementById('notif');
            notif.className = 'notif ' + tipe;
            notif.textContent = pesan;
            
            setTimeout(() => {
                notif.style.display = 'none';
            }, 3000);
        }

        function updateTotal() {
            // Hitung ulang total dari tabel yang masih ada
            const rows = document.querySelectorAll('#tableBody tr');
            let totalAktif = 0;
            let totalMenunggu = 0;
            
            rows.forEach(row => {
                const statusSpan = row.querySelector('.status-badge');
                if (statusSpan) {
                    const status = statusSpan.textContent;
                    if (status == 'Aktif') totalAktif++;
                    if (status == 'Menunggu') totalMenunggu++;
                }
            });
            
            document.getElementById('totalAktif').textContent = totalAktif;
            document.getElementById('totalMenunggu').textContent = totalMenunggu;
            document.getElementById('totalAll').textContent = totalAktif + totalMenunggu;
        }
    </script>
</body>
</html>
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
                        <div class="value" id="totalAktif">{{ $count_data->total_aktif }}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Total Menunggu</div>
                        <div class="value" id="totalMenunggu">{{ $count_data->total_menunggu }}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Total Keseluruhan</div>
                        <div class="value" id="totalAll">{{ $count_data->total_all }}</div>
                    </div>
                </div>

                <!-- Filter -->
                <form method="GET" class="filter-box">
                    <div class="filter-item">
                        <label>Unit</label>
                        <select name="unit">
                            <option value="">Semua Unit</option>
                            @foreach ($unit_list as $unit)
                            <option value="{{ $unit }}" {{ $filter_unit == $unit ? 'selected' : '' }}>
                                {{ $unit }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Bidang</label>
                        <select name="bidang">
                            <option value="0">Semua Bidang</option>
                            @foreach ($bidang_list as $id => $bidang)
                            <option value="{{ $id }}" {{ $filter_bidang == $id ? 'selected' : '' }}>
                                {{ $bidang }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('cekpeserta.index') }}" class="btn">Reset</a>
                    </div>
                </form>

                <!-- Breakdown per bulan -->
                @if (count($monthly_data) > 0)
                <div class="breakdown-section">
                    <div class="breakdown-title">Breakdown Peserta Magang Per Bulan</div>
                    <div class="breakdown-grid">
                        @foreach ($monthly_data as $data)
                        <div class="breakdown-card {{ $data['jumlah'] == 0 ? 'empty' : '' }}">
                            <div class="breakdown-header">
                                <span class="breakdown-month">{{ $data['label'] }}</span>
                                <span class="breakdown-count">{{ $data['jumlah'] }}</span>
                            </div>
                            <div class="breakdown-bar">
                                <div class="breakdown-bar-fill" style="width: {{ ($data['jumlah'] / max($max_monthly, 1)) * 100 }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

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
                            @if ($peserta->count() > 0)
                                @php $no = 1; @endphp
                                @foreach ($peserta as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $row->nama }}</td>
                                    <td>
                                        <span class="status-badge {{ strtolower($row->status_magang) }}" id="status-{{ $row->id }}">
                                            {{ $row->status_magang }}
                                        </span>
                                    </td>
                                    <td>{{ $row->asal_sekolah }}</td>
                                    <td>{{ $row->jurusan }}</td>
                                    <td>{{ $row->nama_bidang ?: '-' }}</td>
                                    <td>{{ $row->unit }}</td>
                                    <td>{{ date('d/m/Y', strtotime($row->tgl_masuk)) }}</td>
                                    <td>{{ date('d/m/Y', strtotime($row->tgl_keluar)) }}</td>
                                    <td>{{ $row->keterangan ?: '-' }}</td>
                                    <td>
                                        <button class="btn-status {{ strtolower($row->status_magang) }}" 
                                                onclick="ubahStatus({{ $row->id }}, '{{ $row->status_magang }}', this)">
                                            @if ($row->status_magang == 'Menunggu')
                                                Jadikan Aktif
                                            @elseif ($row->status_magang == 'Aktif')
                                                Selesaikan
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 30px; color: #777;">
                                        Tidak ada data peserta dengan status Aktif/Menunggu
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="footer">
                Sistem Informasi Magang - {{ date('Y') }}
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
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route('cekpeserta.index') }}', {
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

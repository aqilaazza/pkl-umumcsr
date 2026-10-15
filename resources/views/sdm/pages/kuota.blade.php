<!-- Breadcrumb -->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Data</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ url(auth()->user()->role) }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Lihat Kuota</li>
            </ol>
        </nav>
    </div>
</div>

<div class="kq">
    <div class="card">
        <div class="card-body">
            <h5 class="mb-1">Daftar Peserta Magang</h5>
            <p class="text-muted mb-4">Peserta status Aktif dan Menunggu · Dikelompokkan berdasarkan bulan tanggal masuk</p>

            {{-- Ringkasan --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="kq-stat">
                        <div class="kq-stat-label">Total Aktif</div>
                        <div class="kq-stat-num">{{ number_format($kq['aktif']) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kq-stat">
                        <div class="kq-stat-label">Total Menunggu</div>
                        <div class="kq-stat-num">{{ number_format($kq['menunggu']) }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="kq-stat">
                        <div class="kq-stat-label">Total Keseluruhan</div>
                        <div class="kq-stat-num">{{ number_format($kq['total']) }}</div>
                    </div>
                </div>
            </div>

            {{-- Filter --}}
            <form method="GET" action="{{ url()->current() }}" class="kq-filter row g-3 align-items-end mb-4">
                <div class="col-12 col-md-5">
                    <label for="kq-unit" class="form-label small text-muted mb-1">Unit</label>
                    <select name="unit" id="kq-unit" class="form-select">
                        <option value="">Semua Unit</option>
                        @foreach ($kq['units'] as $u)
                            <option value="{{ $u }}" {{ $kq['unit'] === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label for="kq-bidang" class="form-label small text-muted mb-1">Bidang</label>
                    <select name="bidang_id" id="kq-bidang" class="form-select">
                        <option value="">Semua Bidang</option>
                        @foreach ($kq['bidang'] as $b)
                            <option value="{{ $b->id }}" {{ $kq['bidangId'] === (int) $b->id ? 'selected' : '' }}>{{ $b->bidang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary flex-fill">Reset</a>
                </div>
            </form>

            {{-- Breakdown per bulan --}}
            <h6 class="mb-1">Breakdown Peserta Magang Per Bulan</h6>
            <p class="small text-muted mb-3">Klik salah satu bulan untuk melihat daftar pesertanya.</p>

            @if (count($kq['bulan']) === 0)
                <div class="text-center text-muted py-5">
                    <i class="bx bx-calendar-x fs-1 d-block mb-1"></i>
                    Tidak ada peserta Aktif atau Menunggu untuk filter ini.
                </div>
            @else
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5 g-3">
                    @foreach ($kq['bulan'] as $b)
                        <div class="col">
                            <button type="button" class="kq-month" data-ym="{{ $b['ym'] }}" data-label="{{ $b['label'] }}" aria-haspopup="dialog">
                                <span class="kq-month-head">
                                    <span class="kq-month-name">{{ $b['label'] }}</span>
                                    <span class="kq-month-num">{{ $b['jumlah'] }}</span>
                                </span>
                                <span class="kq-bar" role="progressbar" aria-label="Peserta {{ $b['label'] }}"
                                      aria-valuenow="{{ $b['jumlah'] }}" aria-valuemin="0" aria-valuemax="{{ $kq['maks'] }}">
                                    <span style="width: {{ $b['persen'] }}%"></span>
                                </span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Pop up detail --}}
    <dialog class="kq-modal" id="kq-modal" aria-labelledby="kq-modal-title">
        <div class="kq-modal-head">
            <div>
                <h6 class="mb-0" id="kq-modal-title"></h6>
                <small class="text-muted" id="kq-modal-sub"></small>
            </div>
            <button type="button" class="kq-close" id="kq-close" aria-label="Tutup">&times;</button>
        </div>
        <div class="kq-modal-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Asal Sekolah</th>
                            <th>Bidang</th>
                            <th>Unit</th>
                            <th>Periode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="kq-modal-rows"></tbody>
                </table>
            </div>
        </div>
    </dialog>
</div>

<style>
    .kq { --kq-blue: var(--pln-blue, #0b6bcb); --kq-line: #e1e8ef; --kq-track: #e6edf5; }
    .kq-stat { background: #f4f7fa; border-radius: 10px; padding: 20px 16px; text-align: center; }
    .kq-stat-label { font-size: .875rem; color: #5b6f86; margin-bottom: 4px; }
    .kq-stat-num { font-size: 2.25rem; font-weight: 600; line-height: 1.15; }
    .kq-filter { background: #f4f7fa; border-radius: 10px; padding: 16px; margin-left: 0; margin-right: 0; }

    .kq-month { display: block; width: 100%; height: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer;
        border: 1px solid var(--kq-line); border-radius: 10px; padding: 12px 14px; background: #fff;
        transition: border-color .15s, box-shadow .15s; }
    .kq-month:hover { border-color: var(--kq-blue); box-shadow: 0 2px 8px rgba(11, 107, 203, .15); }
    .kq-month:focus-visible { outline: 3px solid var(--kq-blue); outline-offset: 2px; }
    .kq-month-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
    .kq-month-name { font-size: .875rem; color: #41566e; }
    .kq-month-num { font-size: 1.25rem; font-weight: 700; color: var(--kq-blue); }
    .kq-bar { display: block; height: 8px; border-radius: 999px; background: var(--kq-track); overflow: hidden; }
    .kq-bar span { display: block; height: 100%; border-radius: 999px; background: var(--kq-blue); }

    .kq-modal { border: 0; border-radius: 12px; padding: 0; width: min(960px, calc(100% - 24px));
        max-height: calc(100dvh - 48px); box-shadow: 0 12px 40px rgba(10, 30, 60, .3); overflow: hidden; }
    .kq-modal[open] { display: flex; flex-direction: column; }
    .kq-modal::backdrop { background: rgba(15, 36, 51, .5); }
    .kq-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
        padding: 16px 20px; border-bottom: 1px solid var(--kq-line); }
    .kq-close { flex: none; width: 36px; height: 36px; border: 0; border-radius: 8px; background: transparent;
        font-size: 1.5rem; line-height: 1; color: #5b6f86; cursor: pointer; }
    .kq-close:hover { background: #eef3f8; color: #0f2433; }
    .kq-close:focus-visible { outline: 2px solid var(--kq-blue); }
    .kq-modal-body { padding: 8px 20px 20px; overflow: auto; }
    .kq-modal-body thead th { position: sticky; top: 0; background: #fff; z-index: 1; font-size: .8125rem; color: #5b6f86; white-space: nowrap; }
    .kq-modal-body td { font-size: .875rem; }
    .kq-modal-body .kq-sub { display: block; font-size: .75rem; color: #6b7d92; }
</style>

<script>
(function () {
    var data  = {{ \Illuminate\Support\Js::from($kq['detail']) }};
    var modal = document.getElementById('kq-modal');
    if (!modal) return;

    var rows  = document.getElementById('kq-modal-rows');
    var title = document.getElementById('kq-modal-title');
    var sub   = document.getElementById('kq-modal-sub');

    function cell(tr, text, nowrap) {
        var td = document.createElement('td');
        td.textContent = text;
        if (nowrap) td.style.whiteSpace = 'nowrap';
        tr.appendChild(td);
        return td;
    }

    function open(ym, label) {
        var list = data[ym] || [];
        var aktif = 0, menunggu = 0;
        rows.textContent = '';

        list.forEach(function (p, i) {
            var tr = document.createElement('tr');
            cell(tr, i + 1);

            var tdNama = cell(tr, p.nama);
            if (p.jurusan) {
                var s = document.createElement('span');
                s.className = 'kq-sub';
                s.textContent = p.jurusan;
                tdNama.appendChild(s);
            }

            cell(tr, p.sekolah);
            cell(tr, p.bidang);
            cell(tr, p.unit);
            cell(tr, p.masuk + ' – ' + p.keluar, true);

            var tdStatus = document.createElement('td');
            var badge = document.createElement('span');
            badge.className = 'badge ' + (p.status === 'Aktif' ? 'bg-success' : 'bg-warning text-dark');
            badge.textContent = p.status;
            tdStatus.appendChild(badge);
            tr.appendChild(tdStatus);

            rows.appendChild(tr);
            if (p.status === 'Aktif') aktif++; else menunggu++;
        });

        title.textContent = 'Peserta masuk ' + label;
        sub.textContent = list.length + ' peserta · ' + aktif + ' Aktif · ' + menunggu + ' Menunggu';
        modal.showModal();
        modal.querySelector('.kq-modal-body').scrollTop = 0;
    }

    document.querySelectorAll('.kq-month').forEach(function (btn) {
        btn.addEventListener('click', function () { open(btn.dataset.ym, btn.dataset.label); });
    });
    document.getElementById('kq-close').addEventListener('click', function () { modal.close(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });
})();
</script>
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
            <h6 class="mb-3">Breakdown Peserta Magang Per Bulan</h6>

            @if (count($kq['bulan']) === 0)
                <div class="text-center text-muted py-5">
                    <i class="bx bx-calendar-x fs-1 d-block mb-1"></i>
                    Tidak ada peserta Aktif atau Menunggu untuk filter ini.
                </div>
            @else
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5 g-3">
                    @foreach ($kq['bulan'] as $b)
                        <div class="col">
                            <div class="kq-month">
                                <div class="kq-month-head">
                                    <span class="kq-month-name">{{ $b['label'] }}</span>
                                    <span class="kq-month-num">{{ $b['jumlah'] }}</span>
                                </div>
                                <div class="kq-bar" role="progressbar" aria-label="Peserta {{ $b['label'] }}"
                                     aria-valuenow="{{ $b['jumlah'] }}" aria-valuemin="0" aria-valuemax="{{ $kq['maks'] }}">
                                    <span style="width: {{ $b['persen'] }}%"></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .kq { --kq-blue: var(--pln-blue, #0b6bcb); --kq-line: #e1e8ef; --kq-track: #e6edf5; }
    .kq-stat { background: #f4f7fa; border-radius: 10px; padding: 20px 16px; text-align: center; }
    .kq-stat-label { font-size: .875rem; color: #5b6f86; margin-bottom: 4px; }
    .kq-stat-num { font-size: 2.25rem; font-weight: 600; line-height: 1.15; }
    .kq-filter { background: #f4f7fa; border-radius: 10px; padding: 16px; margin-left: 0; margin-right: 0; }
    .kq-month { border: 1px solid var(--kq-line); border-radius: 10px; padding: 12px 14px; background: #fff; height: 100%; }
    .kq-month-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px; }
    .kq-month-name { font-size: .875rem; color: #41566e; }
    .kq-month-num { font-size: 1.25rem; font-weight: 700; color: var(--kq-blue); }
    .kq-bar { height: 8px; border-radius: 999px; background: var(--kq-track); overflow: hidden; }
    .kq-bar span { display: block; height: 100%; border-radius: 999px; background: var(--kq-blue); }
</style>
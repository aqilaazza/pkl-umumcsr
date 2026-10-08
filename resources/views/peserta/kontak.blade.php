@extends('layouts.peserta')

@section('content')
@php
    $tgl_indo = function ($tanggal) {
        if (empty($tanggal) || $tanggal == '0000-00-00') return '-';
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni',
                  'Juli','Agustus','September','Oktober','November','Desember'];
        $p = explode('-', $tanggal);
        return $p[2] . ' ' . $bulan[(int)$p[1]] . ' ' . $p[0];
    };
@endphp

<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Kontak</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('peserta.home') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Kontak Admin</li>
            </ol>
        </nav>
    </div>

    <div class="ms-auto">
        <span class="text-muted small"><i class="bx bx-calendar me-1"></i>{{ $tgl_indo(date('Y-m-d')) }}</span>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     HEADER KONTAK
══════════════════════════════════════════════════════════════ -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-gradient-primary text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2 class="fw-bold mb-2 text-white">Kontak Admin</h2>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <i class='bx bx-headphone fs-1 text-white opacity-50' style="font-size: 5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     KARTU KONTAK ADMIN
══════════════════════════════════════════════════════════════ -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="row g-0">
                <!-- Foto/Ilustrasi Admin -->
                <div class="col-md-4 bg-primary text-white d-flex flex-column align-items-center justify-content-center p-4">
                    <div class="bg-white bg-opacity-20 rounded-circle p-4 mb-3">
                        <i class='bx bxs-user-circle fs-1 text-white'></i>
                    </div>
                    <h5 class="fw-bold text-center mb-0">Admin</h5>
                </div>
                
                <!-- Detail Kontak -->
                <div class="col-md-8">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3 text-primary">Ubaid</h4>
                        
                        <div class="mb-4">
                            <p class="text-muted mb-2">Silahkan hubungi admin melalui kontak di bawah ini untuk pertanyaan seputar:</p>
                            <ul class="text-muted small">
                                <li>Status laporan magang</li>
                                <li>Kendala upload file</li>
                                <li>Informasi sertifikat</li>
                                <li>Pertanyaan lainnya</li>
                            </ul>
                        </div>
                        
                        <!-- Kontak WhatsApp -->
                        <div class="d-flex align-items-center mb-4 p-3 bg-success bg-opacity-10 rounded-3">
                            <div class="bg-success rounded-circle p-3 me-3">
                                <i class='bx bxl-whatsapp fs-3 text-white'></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small text-muted">WhatsApp</div>
                                <div class="fw-bold fs-5">0852 3322 3872</div>
                            </div>
                            <a href="https://wa.me/6285233223872?text=Halo%20Admin%2C%20saya%20peserta%20magang%20ingin%20bertanya" 
                               target="_blank" 
                               class="btn btn-success">
                                <i class="bx bxl-whatsapp me-1"></i>Chat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
}
.text-white-50 {
    color: rgba(255,255,255,0.5) !important;
}
.bg-opacity-20 {
    --bs-bg-opacity: 0.2;
}
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.1) !important;
}
</style>
@endsection

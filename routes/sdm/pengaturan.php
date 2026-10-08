<?php

use App\Http\Controllers\Sdm\HariLiburController;
use App\Http\Controllers\Sdm\PengaturanAbsensiController;
use App\Http\Controllers\Sdm\PengaturanTtdController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/pengaturan/hari-libur', [HariLiburController::class, 'index'])->name('pengaturan.hari_libur');
    Route::post('/pengaturan/hari-libur', [HariLiburController::class, 'store'])->name('pengaturan.hari_libur.store');
    Route::post('/pengaturan/hari-libur/libur-pekan', [HariLiburController::class, 'updateLiburPekan'])->name('pengaturan.hari_libur.libur_pekan');
    Route::post('/pengaturan/hari-libur/hapus/{id}', [HariLiburController::class, 'destroy'])->name('pengaturan.hari_libur.hapus');

    Route::get('/pengaturan/absensi', [PengaturanAbsensiController::class, 'index'])->name('pengaturan.absensi');
    Route::post('/pengaturan/absensi', [PengaturanAbsensiController::class, 'update'])->name('pengaturan.absensi.update');

    Route::get('/pengaturan/ttd', [PengaturanTtdController::class, 'index'])->name('pengaturan.ttd');
    Route::post('/pengaturan/ttd', [PengaturanTtdController::class, 'update'])->name('pengaturan.ttd.update');
});

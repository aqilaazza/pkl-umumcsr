<?php

use App\Http\Controllers\Sdm\RekapAbsensiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/rekap-absensi', [RekapAbsensiController::class, 'index'])->name('rekap_absensi');
    Route::get('/rekap-absensi/ajax-detail', [RekapAbsensiController::class, 'ajaxDetail'])->name('rekap_absensi.detail');
});

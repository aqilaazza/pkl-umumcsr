<?php

use App\Http\Controllers\Sdm\DokumenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/dokumen', [DokumenController::class, 'index'])->name('dokumen');
    Route::post('/dokumen/store', [DokumenController::class, 'store'])->name('dokumen.store');
    Route::post('/dokumen/update/{id}', [DokumenController::class, 'update'])->name('dokumen.update');
    Route::get('/dokumen/hapus/{id}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');
});

<?php

use App\Http\Controllers\Sdm\PesertaController;
use App\Http\Controllers\Sdm\PesertaExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/peserta', [PesertaController::class, 'index'])->name('peserta.daftar');
    Route::get('/peserta/tambah', [PesertaController::class, 'create'])->name('peserta.tambah');
    Route::post('/peserta/tambah', [PesertaController::class, 'store'])->name('peserta.store');
    Route::get('/peserta/edit/{id}', [PesertaController::class, 'edit'])->name('peserta.edit');
    Route::post('/peserta/edit/{id}', [PesertaController::class, 'update'])->name('peserta.update');
    Route::get('/peserta/cek_username.php', [PesertaController::class, 'cekUsername'])->name('peserta.cek_username');
    Route::get('/peserta/export', [PesertaExportController::class, 'export'])->name('peserta.export');
});

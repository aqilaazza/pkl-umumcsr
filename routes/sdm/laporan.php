<?php

use App\Http\Controllers\Sdm\BackupLaporanController;
use App\Http\Controllers\Sdm\HapusLaporanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/backup-laporan', [BackupLaporanController::class, 'index'])->name('backup_laporan');
    Route::get('/backup-laporan/download', [BackupLaporanController::class, 'download'])->name('backup_laporan.download');
    Route::get('/backup-laporan/export', [BackupLaporanController::class, 'export'])->name('backup_laporan.export');

    Route::get('/hapus-laporan', [HapusLaporanController::class, 'index'])->name('hapus_laporan');
    Route::post('/hapus-laporan/hapus', [HapusLaporanController::class, 'destroy'])->name('hapus_laporan.destroy');
    Route::post('/hapus-laporan/hapus-massal', [HapusLaporanController::class, 'destroyBulk'])->name('hapus_laporan.destroy_bulk');
});

<?php

use App\Http\Controllers\Sdm\AjaxCekSertifikatController;
use App\Http\Controllers\Sdm\CetakSertifikatController;
use App\Http\Controllers\Sdm\PrintSertifikatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/cetak-sertifikat', [CetakSertifikatController::class, 'index'])->name('cetak_sertifikat');
    Route::post('/cetak-sertifikat/upload-scan', [CetakSertifikatController::class, 'uploadScan'])->name('cetak_sertifikat.upload_scan');
    Route::get('/print-sertifikat', [PrintSertifikatController::class, 'cetak'])->name('print_sertifikat');
    Route::get('/ajax_cek_sertifikat.php', [AjaxCekSertifikatController::class, 'check'])->name('ajax_cek_sertifikat');
});

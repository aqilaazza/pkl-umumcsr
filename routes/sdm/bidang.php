<?php

use App\Http\Controllers\Sdm\BidangController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/bidang', [BidangController::class, 'index'])->name('bidang');
    Route::post('/bidang/store', [BidangController::class, 'store'])->name('bidang.store');
    Route::post('/bidang/update/{id}', [BidangController::class, 'update'])->name('bidang.update');
    Route::get('/bidang/hapus/{id}', [BidangController::class, 'destroy'])->name('bidang.destroy');
});

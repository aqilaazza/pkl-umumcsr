<?php

use App\Http\Controllers\Sdm\ApprovalAbsensiController;
use App\Http\Controllers\Sdm\ApprovalLaporanController;
use App\Http\Controllers\Sdm\ApprovalManagerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/approval-laporan', [ApprovalLaporanController::class, 'index'])->name('approval_laporan');
    Route::post('/approval-laporan/setujui', [ApprovalLaporanController::class, 'approve'])->name('approval_laporan.approve');
    Route::post('/approval-laporan/tolak', [ApprovalLaporanController::class, 'reject'])->name('approval_laporan.reject');

    Route::get('/approval-manager', [ApprovalManagerController::class, 'index'])->name('approval_laporan_manager');

    Route::get('/approval-absensi', [ApprovalAbsensiController::class, 'index'])->name('approval_absensi');
    Route::post('/approval-absensi/setujui', [ApprovalAbsensiController::class, 'approve'])->name('approval_absensi.approve');
    Route::post('/approval-absensi/tolak', [ApprovalAbsensiController::class, 'reject'])->name('approval_absensi.reject');
});

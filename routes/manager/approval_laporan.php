<?php

use App\Http\Controllers\Manager\ApprovalLaporanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('approval-laporan', [ApprovalLaporanController::class, 'index'])->name('approval_laporan');
    Route::post('approval-laporan/approve', [ApprovalLaporanController::class, 'approve'])->name('approval_laporan.approve');
    Route::post('approval-laporan/reject', [ApprovalLaporanController::class, 'reject'])->name('approval_laporan.reject');
});

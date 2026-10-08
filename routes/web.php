<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CekLoginController;
use App\Http\Controllers\CekPesertaController;
use App\Http\Controllers\PesertaController;
use App\Http\Controllers\VerifikasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/ceklogin', [CekLoginController::class, 'index'])->name('ceklogin.index');
Route::get('/ceklogin/search_peserta.php', [CekLoginController::class, 'search'])->name('ceklogin.search');
Route::get('/ceklogin/get_peserta.php', [CekLoginController::class, 'getPeserta'])->name('ceklogin.get');
Route::get('/ceklogin/verifikasi.php', [VerifikasiController::class, 'show'])->name('ceklogin.verifikasi');

Route::get('/cekpeserta', [CekPesertaController::class, 'index'])->name('cekpeserta.index');
Route::post('/cekpeserta', [CekPesertaController::class, 'ubahStatus'])->name('cekpeserta.ubahStatus');

Route::middleware('role:peserta')->prefix('peserta')->group(function () {
    Route::get('/', [PesertaController::class, 'home'])->name('peserta.home');
    Route::get('/absensi', [PesertaController::class, 'absensi'])->name('peserta.absensi');
    Route::match(['get', 'post'], '/absensi/proses', [PesertaController::class, 'absensiProses'])->name('peserta.absensi.proses');
    Route::get('/laporan', [PesertaController::class, 'laporan'])->name('peserta.laporan');
    Route::post('/laporan', [PesertaController::class, 'laporanStore'])->name('peserta.laporan.store');
    Route::get('/laporan/referensi', [PesertaController::class, 'referensi'])->name('peserta.laporan.referensi');
    Route::get('/sertifikat', [PesertaController::class, 'sertifikat'])->name('peserta.sertifikat');
    Route::get('/dokumen', [PesertaController::class, 'dokumen'])->name('peserta.dokumen');
    Route::get('/profile', [PesertaController::class, 'profile'])->name('peserta.profile');
    Route::post('/profile', [PesertaController::class, 'profileUpdate'])->name('peserta.profile.update');
    Route::get('/kontak', [PesertaController::class, 'kontak'])->name('peserta.kontak');
});

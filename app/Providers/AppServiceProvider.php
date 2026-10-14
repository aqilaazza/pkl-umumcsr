<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan angka badge (topbar & sidebar) ke seluruh halaman modul SDM.
        View::composer('layouts.sdm', function ($view) {
            $pendingIzin   = 0;
            $reviewLaporan = 0;

            try {
                $pendingIzin = (int) DB::table('absensi_peserta')
                    ->whereIn('status', ['Izin', 'Sakit'])
                    ->where('approval_status', 'Pending')
                    ->count();

                $reviewLaporan = (int) DB::table('laporan_magang')
                    ->where('sdm_status', 'Menunggu')
                    ->count();
            } catch (\Throwable $e) {
                // Abaikan bila tabel belum tersedia (mis. saat proses instalasi).
            }

            $view->with([
                'badge_izin_pending' => $pendingIzin,
                'badge_laporan'      => $reviewLaporan,
            ]);
        });
    }
}

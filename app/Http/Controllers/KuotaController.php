<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Halaman "Lihat Kuota" — dipakai bersama oleh role SDM dan Manager.
 * Menghitung peserta berstatus Aktif/Menunggu, dikelompokkan per bulan tanggal masuk.
 */
class KuotaController extends Controller
{
    public function index(): View
    {
        $unit     = trim((string) request('unit'));
        $bidangId = (int) request('bidang_id');

        $base = DB::table('peserta')->whereIn('status_magang', ['Aktif', 'Menunggu']);

        if ($unit !== '') {
            $base->where('unit', $unit);
        }
        if ($bidangId > 0) {
            $base->where('bidang_id', $bidangId);
        }

        // Kartu ringkasan
        $sum = (clone $base)
            ->selectRaw("COUNT(*) AS total, SUM(status_magang = 'Aktif') AS aktif, SUM(status_magang = 'Menunggu') AS menunggu")
            ->first();

        // Breakdown per bulan tanggal masuk
        $rows = (clone $base)
            ->selectRaw("DATE_FORMAT(tgl_masuk, '%Y-%m') AS ym, COUNT(*) AS jumlah")
            ->groupByRaw("DATE_FORMAT(tgl_masuk, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(tgl_masuk, '%Y-%m')")
            ->get();

        $maks  = max(1, (int) $rows->max('jumlah'));
        $bulan = $rows->map(fn ($r) => [
            'label'  => Carbon::createFromFormat('!Y-m', $r->ym)->locale('id')->translatedFormat('F Y'),
            'jumlah' => (int) $r->jumlah,
            'persen' => (int) round($r->jumlah / $maks * 100),
        ])->all();

        // Pilihan filter
        $units = DB::table('peserta')
            ->whereNotNull('unit')->where('unit', '<>', '')
            ->distinct()->pluck('unit')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        $daftarBidang = DB::table('bidang')->orderBy('bidang')->get(['id', 'bidang']);

        // Layout mengikuti role yang sedang login
        $layout = request()->routeIs('manager.*') ? 'layouts.manager' : 'layouts.sdm';

        return view($layout, [
            'pageView' => 'sdm.pages.kuota',
            'kq'       => [
                'aktif'    => (int) ($sum->aktif ?? 0),
                'menunggu' => (int) ($sum->menunggu ?? 0),
                'total'    => (int) ($sum->total ?? 0),
                'bulan'    => $bulan,
                'maks'     => $maks,
                'units'    => $units,
                'bidang'   => $daftarBidang,
                'unit'     => $unit,
                'bidangId' => $bidangId,
            ],
        ]);
    }
}
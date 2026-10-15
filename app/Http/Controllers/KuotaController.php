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

        $base = DB::table('peserta')->whereIn('peserta.status_magang', ['Aktif', 'Menunggu']);

        if ($unit !== '') {
            $base->where('peserta.unit', $unit);
        }
        if ($bidangId > 0) {
            $base->where('peserta.bidang_id', $bidangId);
        }

        // Kartu ringkasan
        $sum = (clone $base)
            ->selectRaw("COUNT(*) AS total, SUM(peserta.status_magang = 'Aktif') AS aktif, SUM(peserta.status_magang = 'Menunggu') AS menunggu")
            ->first();

        // Breakdown per bulan tanggal masuk
        $rows = (clone $base)
            ->selectRaw("DATE_FORMAT(peserta.tgl_masuk, '%Y-%m') AS ym, COUNT(*) AS jumlah")
            ->groupByRaw("DATE_FORMAT(peserta.tgl_masuk, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(peserta.tgl_masuk, '%Y-%m')")
            ->get();

        $maks  = max(1, (int) $rows->max('jumlah'));
        $bulan = $rows->map(fn ($r) => [
            'ym'     => $r->ym,
            'label'  => Carbon::createFromFormat('!Y-m', $r->ym)->locale('id')->translatedFormat('F Y'),
            'jumlah' => (int) $r->jumlah,
            'persen' => (int) round($r->jumlah / $maks * 100),
        ])->all();

        // Detail peserta per bulan (untuk pop up)
        $detail = (clone $base)
            ->leftJoin('bidang as b', 'peserta.bidang_id', '=', 'b.id')
            ->orderBy('peserta.tgl_masuk')
            ->orderBy('peserta.nama')
            ->get([
                'peserta.nama', 'peserta.jurusan', 'peserta.asal_sekolah', 'peserta.unit',
                'peserta.tgl_masuk', 'peserta.tgl_keluar', 'peserta.status_magang',
                'b.bidang as nama_bidang',
            ])
            ->groupBy(fn ($r) => substr($r->tgl_masuk, 0, 7))
            ->map(fn ($items) => $items->map(fn ($r) => [
                'nama'    => $r->nama,
                'jurusan' => $r->jurusan,
                'sekolah' => $r->asal_sekolah,
                'bidang'  => $r->nama_bidang ?: '-',
                'unit'    => $r->unit ?: '-',
                'masuk'   => Carbon::parse($r->tgl_masuk)->format('d/m/Y'),
                'keluar'  => Carbon::parse($r->tgl_keluar)->format('d/m/Y'),
                'status'  => $r->status_magang,
            ])->values()->all())
            ->all();

        // Pilihan filter
        $units = DB::table('peserta')
            ->whereNotNull('unit')->where('unit', '<>', '')
            ->distinct()->pluck('unit')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();

        $daftarBidang = DB::table('bidang')->orderBy('bidang')->get(['id', 'bidang']);

        // Layout mengikuti route yang dibuka (manager.kuota atau sdm.kuota)
        $layout = request()->routeIs('manager.*') ? 'layouts.manager' : 'layouts.sdm';

        return view($layout, [
            'pageView' => 'sdm.pages.kuota',
            'kq'       => [
                'aktif'    => (int) ($sum->aktif ?? 0),
                'menunggu' => (int) ($sum->menunggu ?? 0),
                'total'    => (int) ($sum->total ?? 0),
                'bulan'    => $bulan,
                'detail'   => $detail,
                'maks'     => $maks,
                'units'    => $units,
                'bidang'   => $daftarBidang,
                'unit'     => $unit,
                'bidangId' => $bidangId,
            ],
        ]);
    }
}
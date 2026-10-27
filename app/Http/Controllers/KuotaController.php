<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Halaman "Lihat Kuota" — dipakai bersama oleh role SDM dan Manager.
 * Ringkasan memakai status saat ini; breakdown bulanan menghitung peserta
 * yang periodenya berjalan pada bulan tersebut.
 */
class KuotaController extends Controller
{
    public function index(): View
    {
        $unit = trim((string) request('unit'));
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

        $peserta = (clone $base)
            ->leftJoin('bidang as b', 'peserta.bidang_id', '=', 'b.id')
            ->orderBy('peserta.tgl_masuk')
            ->orderBy('peserta.nama')
            ->get([
                'peserta.nama', 'peserta.jurusan', 'peserta.asal_sekolah', 'peserta.unit',
                'peserta.tgl_masuk', 'peserta.tgl_keluar', 'peserta.status_magang',
                'b.bidang as nama_bidang',
            ]);

        $records = $peserta->map(function ($r) {
            $masukDate = $this->dateOf($r->tgl_masuk);
            if (! $masukDate) {
                return null;
            }

            $keluarDate = $this->dateOf($r->tgl_keluar);
            $masuk = $masukDate->copy()->startOfMonth();
            $keluar = $keluarDate ? $keluarDate->copy()->startOfMonth() : Carbon::now()->startOfMonth();

            if ($keluar->lt($masuk)) {
                $keluar = $masuk->copy();
            }

            return [
                'row' => $r,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'masuk_date' => $masukDate,
                'keluar_date' => $keluarDate,
            ];
        })->filter()->values();

        $dataStart = Carbon::create(2026, 7, 1)->startOfMonth();
        $start = $records->min(fn ($r) => $r['masuk']);
        $end = $records->max(fn ($r) => $r['keluar']);

        if ($start && $start->lt($dataStart)) {
            $start = $dataStart->copy();
        }

        $bulan = [];
        $detail = [];

        if ($start && $end) {
            for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
                $items = $records->filter(fn ($r) => $r['masuk']->lte($month) && $r['keluar']->gte($month));
                if ($items->isEmpty()) {
                    continue;
                }

                $ym = $month->format('Y-m');

                $bulan[] = [
                    'ym' => $ym,
                    'label' => $month->copy()->locale('id')->translatedFormat('F Y'),
                    'jumlah' => $items->count(),
                ];

                $detail[$ym] = $items->map(function ($r) {
                    $row = $r['row'];

                    return [
                        'nama' => $row->nama,
                        'jurusan' => $row->jurusan,
                        'sekolah' => $row->asal_sekolah,
                        'bidang' => $row->nama_bidang ?: '-',
                        'unit' => $row->unit ?: '-',
                        'masuk' => $r['masuk_date']->format('d/m/Y'),
                        'keluar' => $r['keluar_date'] ? $r['keluar_date']->format('d/m/Y') : '-',
                        'status' => $row->status_magang,
                    ];
                })->values()->all();
            }
        }

        $maks = max(1, (int) collect($bulan)->max('jumlah'));
        foreach ($bulan as $i => $b) {
            $bulan[$i]['persen'] = (int) round($b['jumlah'] / $maks * 100);
        }

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
            'kq' => [
                'aktif' => (int) ($sum->aktif ?? 0),
                'menunggu' => (int) ($sum->menunggu ?? 0),
                'total' => (int) ($sum->total ?? 0),
                'bulan' => $bulan,
                'detail' => $detail,
                'maks' => $maks,
                'units' => $units,
                'bidang' => $daftarBidang,
                'unit' => $unit,
                'bidangId' => $bidangId,
            ],
        ]);
    }

    private function dateOf(?string $date): ?Carbon
    {
        if (trim((string) $date) === '') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

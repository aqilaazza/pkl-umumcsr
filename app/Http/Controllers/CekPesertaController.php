<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Peserta;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CekPesertaController extends Controller
{
    protected array $unitList = ['Unit 1-2', 'Unit 9'];

    public function index(Request $request): View
    {
        $bidang_list = Bidang::orderBy('bidang')->pluck('bidang', 'id')->all();

        $filter_unit = (string) $request->query('unit', '');
        $filter_bidang = (int) $request->query('bidang', 0);

        $base = function () use ($filter_unit, $filter_bidang) {
            $q = Peserta::query()->whereIn('status_magang', ['Aktif', 'Menunggu']);

            if ($filter_unit !== '') {
                $q->where('unit', $filter_unit);
            }

            if ($filter_bidang > 0) {
                $q->where('bidang_id', $filter_bidang);
            }

            return $q;
        };

        $peserta = $base()
            ->leftJoin('bidang as b', 'peserta.bidang_id', '=', 'b.id')
            ->select('peserta.*', 'b.bidang as nama_bidang')
            ->orderBy('peserta.tgl_masuk')
            ->get();

        $count_data = Peserta::query()
            ->whereIn('status_magang', ['Aktif', 'Menunggu'])
            ->selectRaw("SUM(CASE WHEN status_magang = 'Aktif' THEN 1 ELSE 0 END) as total_aktif")
            ->selectRaw("SUM(CASE WHEN status_magang = 'Menunggu' THEN 1 ELSE 0 END) as total_menunggu")
            ->selectRaw('COUNT(*) as total_all')
            ->first();

        $range_data = $base()
            ->selectRaw('MIN(tgl_masuk) as tgl_min')
            ->selectRaw('MAX(tgl_keluar) as tgl_max')
            ->first();

        $monthly_data = [];
        $max_monthly = 0;

        if ($range_data->tgl_min && $range_data->tgl_max) {
            $start_date = new DateTime($range_data->tgl_min);
            $end_date = new DateTime($range_data->tgl_max);

            $current = clone $start_date;
            $current->modify('first day of this month');

            while ($current <= $end_date) {
                $label = $current->format('F Y');
                $bulan_pertama = $current->format('Y-m-01');
                $bulan_terakhir = $current->format('Y-m-t');

                $jumlah = $base()
                    ->where('tgl_masuk', '<=', $bulan_terakhir)
                    ->where('tgl_keluar', '>=', $bulan_pertama)
                    ->count();

                $monthly_data[] = [
                    'bulan' => $current->format('Y-m'),
                    'label' => $label,
                    'jumlah' => $jumlah,
                ];

                if ($jumlah > $max_monthly) {
                    $max_monthly = $jumlah;
                }

                $current->modify('first day of next month');
            }
        }

        $unit_list = $this->unitList;

        return view('cekpeserta.index', compact(
            'bidang_list',
            'unit_list',
            'filter_unit',
            'filter_bidang',
            'peserta',
            'count_data',
            'monthly_data',
            'max_monthly'
        ));
    }

    public function ubahStatus(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $status_sekarang = $request->input('status_sekarang');

        if ($status_sekarang === 'Menunggu') {
            $status_baru = 'Aktif';
        } elseif ($status_sekarang === 'Aktif') {
            $status_baru = 'Selesai';
        } else {
            return response()->json(['success' => false, 'message' => 'Status tidak valid']);
        }

        $updated = Peserta::where('id', $id)->update(['status_magang' => $status_baru]);

        if ($updated === false) {
            return response()->json(['success' => false, 'message' => 'Gagal update']);
        }

        return response()->json(['success' => true, 'status_baru' => $status_baru]);
    }
}

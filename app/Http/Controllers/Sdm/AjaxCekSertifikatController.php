<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjaxCekSertifikatController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $laporan_id = (int) $request->query('laporan_id', 0);

        if (!$laporan_id) {
            return response()->json(['found' => false]);
        }

        $row = DB::table('sertifikat_magang as sm')
            ->join('laporan_magang as lm', 'sm.username', '=', 'lm.username')
            ->join('peserta as p', 'sm.username', '=', 'p.username')
            ->where('lm.id', $laporan_id)
            ->limit(1)
            ->get([
                'sm.id as sertif_id',
                'sm.nomor_surat',
                'sm.tgl_cetak',
                'sm.status',
                'p.nama',
            ])
            ->first();

        if ($row) {
            return response()->json([
                'found'       => true,
                'sertif_id'   => $row->sertif_id,
                'nomor_surat' => $row->nomor_surat,
                'tgl_cetak'   => $row->tgl_cetak,
                'nama'        => $row->nama,
                'status'      => $row->status,
            ]);
        }

        return response()->json(['found' => false]);
    }
}

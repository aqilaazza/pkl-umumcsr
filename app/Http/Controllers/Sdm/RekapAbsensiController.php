<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapAbsensiController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.rekap_absensi',
            'need_datatables' => true,
        ]);
    }

    public function ajaxDetail(Request $request)
    {
        $user  = (string) $request->query('user', '');
        $bulan = preg_replace('/[^0-9\-]/', '', (string) $request->query('bulan', ''));

        $awal  = $bulan . '-01';
        $akhir = date('Y-m-d', strtotime($awal . ' +1 month'));

        $rows = DB::table('absensi_peserta')
            ->where('username', $user)
            ->where('tanggal', '>=', $awal)
            ->where('tanggal', '<', $akhir)
            ->orderBy('tanggal', 'asc')
            ->get();

        return response()->json($rows);
    }
}

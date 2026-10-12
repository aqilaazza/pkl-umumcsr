<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $pesertaPerInstansi = DB::table('peserta')
            ->select('asal_sekolah as instansi', DB::raw('COUNT(*) as total'))
            ->groupBy('asal_sekolah')
            ->orderByDesc('total')
            ->get();

        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.home',
            'need_apexcharts' => true,
            'pesertaPerInstansi' => $pesertaPerInstansi,
        ]);
    }
}
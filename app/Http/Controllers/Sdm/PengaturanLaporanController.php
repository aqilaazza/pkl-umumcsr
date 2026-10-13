<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\PengaturanLaporan;
use Illuminate\Http\Request;

class PengaturanLaporanController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.pengaturan_laporan',
        ]);
    }

    public function update(Request $request)
    {
        $max_ukuran = max(1, (int) $request->input('max_ukuran_mb', 5));

        try {
            $cek = PengaturanLaporan::where('id', 1)->first();

            if ($cek) {
                $cek->update([
                    'max_ukuran_mb' => $max_ukuran,
                ]);
            } else {
                PengaturanLaporan::create([
                    'id' => 1,
                    'max_ukuran_mb' => $max_ukuran,
                ]);
            }

            return redirect()->back()->with('success', 'Pengaturan ukuran file laporan berhasil disimpan!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }
}

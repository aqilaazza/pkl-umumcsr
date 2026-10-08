<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\PengaturanAbsensi;
use Illuminate\Http\Request;

class PengaturanAbsensiController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.absensi',
        ]);
    }

    public function update(Request $request)
    {
        $lat = (float) $request->input('office_lat', 0);
        $lng = (float) $request->input('office_lng', 0);
        $radius = (int) $request->input('radius_meter', 100);

        try {
            $cek = PengaturanAbsensi::where('id', 1)->first();

            if ($cek) {
                $cek->update([
                    'office_lat' => $lat,
                    'office_lng' => $lng,
                    'radius_meter' => $radius,
                ]);
            } else {
                PengaturanAbsensi::create([
                    'id' => 1,
                    'office_lat' => $lat,
                    'office_lng' => $lng,
                    'radius_meter' => $radius,
                ]);
            }

            return redirect()->back()->with('success', 'Pengaturan lokasi & radius absensi berhasil disimpan!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\PengaturanTtd;
use Illuminate\Http\Request;

class PengaturanTtdController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.ttd',
        ]);
    }

    public function update(Request $request)
    {
        $nama = (string) $request->input('nama_ttd', '');
        $jabatan = (string) $request->input('jabatan_ttd', '');

        try {
            $cek = PengaturanTtd::where('id', 1)->first();

            if ($cek) {
                $cek->update([
                    'nama_ttd' => $nama,
                    'jabatan_ttd' => $jabatan,
                ]);
            } else {
                PengaturanTtd::create([
                    'id' => 1,
                    'nama_ttd' => $nama,
                    'jabatan_ttd' => $jabatan,
                ]);
            }

            return redirect()->back()->with('success', 'Pengaturan tanda tangan berhasil disimpan!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}

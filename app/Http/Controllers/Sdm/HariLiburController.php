<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\LiburPekan;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.hari_libur',
        ]);
    }

    public function store(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $keterangan = trim((string) $request->input('keterangan', ''));

        if (empty($tanggal) || $keterangan === '') {
            return redirect()->back()->with('error', 'Tanggal dan keterangan wajib diisi!');
        }

        if (HariLibur::where('tanggal', $tanggal)->exists()) {
            return redirect()->back()->with('error', 'Tanggal ini sudah terdaftar sebagai hari libur.');
        }

        try {
            HariLibur::create([
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
            ]);

            return redirect()->back()->with('success', 'Hari libur khusus berhasil ditambahkan!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan.');
        }
    }

    public function updateLiburPekan(Request $request)
    {
        $libur_pekan_input = $request->input('hari_libur_pekan', []);

        $hari_names = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        try {
            LiburPekan::query()->delete();

            $success = true;
            foreach ($libur_pekan_input as $idx) {
                $idx = (int) $idx;
                $nama_hari = $hari_names[$idx] ?? '';
                if ($nama_hari !== '') {
                    $created = LiburPekan::create([
                        'hari_index' => $idx,
                        'nama_hari' => $nama_hari,
                    ]);
                    if (!$created) {
                        $success = false;
                    }
                }
            }

            if ($success) {
                return redirect()->back()->with('success', 'Konfigurasi libur pekan berhasil disimpan!');
            }

            return redirect()->back()->with('error', 'Gagal menyimpan konfigurasi libur pekan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan konfigurasi libur pekan.');
        }
    }

    public function destroy($id)
    {
        HariLibur::where('id', (int) $id)->delete();

        return redirect()->back()->with('success', 'Hari libur berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BidangController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.bidang',
        ]);
    }

    public function store(Request $request)
    {
        $bidang = trim((string) $request->input('bidang'));

        if ($bidang === '') {
            return redirect()->back()->with('error', 'Nama bidang tidak boleh kosong.');
        }

        if (DB::table('bidang')->where('bidang', $bidang)->exists()) {
            return redirect()->back()->with('error', 'Bidang <strong>' . e($bidang) . '</strong> sudah terdaftar.');
        }

        DB::table('bidang')->insert(['bidang' => $bidang]);

        return redirect('sdm/bidang')->with('success', 'Bidang <strong>' . e($bidang) . '</strong> berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $id     = (int) $id;
        $bidang = trim((string) $request->input('bidang'));

        if ($bidang === '') {
            return redirect()->back()->withInput()->with('error', 'Nama bidang tidak boleh kosong.');
        }

        $duplicate = DB::table('bidang')
            ->where('bidang', $bidang)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', 'Bidang <strong>' . e($bidang) . '</strong> sudah terdaftar.');
        }

        DB::table('bidang')->where('id', $id)->update(['bidang' => $bidang]);

        return redirect('sdm/bidang')->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('bidang')->where('id', (int) $id)->delete();

        return redirect('sdm/bidang')->with('success', 'Data bidang berhasil dihapus.');
    }
}

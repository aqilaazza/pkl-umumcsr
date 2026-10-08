<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalLaporanController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.approval_laporan',
            'need_datatables' => true,
        ]);
    }

    public function approve(Request $request)
    {
        $laporan_id = (int) $request->input('laporan_id');

        DB::table('laporan_magang')->where('id', $laporan_id)->update([
            'sdm_status'           => 'Disetujui',
            'sdm_reviewed_by'      => auth()->user()->username,
            'sdm_tgl_review'       => now(),
            'sdm_keterangan_tolak' => null,
            'status'               => 'Menunggu Manager',
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil <strong>Disetujui</strong> (menunggu approval Manager).');
    }

    public function reject(Request $request)
    {
        $laporan_id       = (int) $request->input('laporan_id');
        $keterangan_tolak = trim((string) $request->input('keterangan_tolak'));

        if (empty($keterangan_tolak)) {
            return redirect()->back()->with('error', 'Keterangan penolakan <strong>wajib diisi</strong>.');
        }

        DB::table('laporan_magang')->where('id', $laporan_id)->update([
            'sdm_status'           => 'Ditolak',
            'sdm_keterangan_tolak' => $keterangan_tolak,
            'sdm_tgl_review'       => now(),
            'sdm_reviewed_by'      => auth()->user()->username,
            'status'               => 'Ditolak',
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil <strong>Ditolak</strong>. Peserta akan melihat keterangan penolakan.');
    }
}

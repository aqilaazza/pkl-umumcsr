<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApprovalAbsensiController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.approval_absensi',
            'need_datatables' => true,
        ]);
    }

    public function approve(Request $request)
    {
        $absen_id = (int) $request->input('absen_id');

        try {
            DB::table('absensi_peserta')->where('id', $absen_id)->update([
                'approval_status' => 'Disetujui',
            ]);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyetujui pengajuan.');
        }

        return redirect()->back()->with('success', 'Pengajuan berhasil <strong>Disetujui</strong>.');
    }

    public function reject(Request $request)
    {
        $absen_id = (int) $request->input('absen_id');

        try {
            DB::table('absensi_peserta')->where('id', $absen_id)->update([
                'approval_status' => 'Ditolak',
            ]);
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menolak pengajuan.');
        }

        return redirect()->back()->with('success', 'Pengajuan berhasil <strong>Ditolak</strong>.');
    }
}

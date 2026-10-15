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

    private function namaPeserta($laporan_id)
    {
        return DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->where('lm.id', $laporan_id)
            ->value('p.nama');
    }

    public function approve(Request $request)
    {
        $laporan_id = (int) $request->input('laporan_id');
        $nama = $this->namaPeserta($laporan_id);

        DB::table('laporan_magang')->where('id', $laporan_id)->update([
            'sdm_status'           => 'Disetujui',
            'sdm_reviewed_by'      => auth()->user()->username,
            'sdm_tgl_review'       => now(),
            'sdm_keterangan_tolak' => null,
            'status'               => 'Menunggu Manager',
        ]);

        $msg = $nama ? "Laporan {$nama} berhasil disetujui." : 'Laporan berhasil disetujui.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'title'   => 'Laporan Disetujui',
                'nama'    => $nama,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg)
            ->with('approval_result', 'approved')
            ->with('approval_nama', $nama);
    }

    public function reject(Request $request)
    {
        $laporan_id       = (int) $request->input('laporan_id');
        $keterangan_tolak = trim((string) $request->input('keterangan_tolak'));

        if (empty($keterangan_tolak)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keterangan penolakan wajib diisi.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Keterangan penolakan wajib diisi.')
                ->with('approval_result', 'error')
                ->with('approval_message', 'Keterangan penolakan wajib diisi.');
        }

        $nama = $this->namaPeserta($laporan_id);

        DB::table('laporan_magang')->where('id', $laporan_id)->update([
            'sdm_status'           => 'Ditolak',
            'sdm_keterangan_tolak' => $keterangan_tolak,
            'sdm_tgl_review'       => now(),
            'sdm_reviewed_by'      => auth()->user()->username,
            'status'               => 'Ditolak',
        ]);

        $msg = $nama ? "Laporan {$nama} berhasil ditolak." : 'Laporan berhasil ditolak.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'title'   => 'Laporan Ditolak',
                'nama'    => $nama,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg)
            ->with('approval_result', 'rejected')
            ->with('approval_nama', $nama);
    }
}

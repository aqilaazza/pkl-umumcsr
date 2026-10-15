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

        $nama = DB::table('absensi_peserta as ap')
            ->leftJoin('peserta as p', 'ap.username', '=', 'p.username')
            ->where('ap.id', $absen_id)
            ->value('p.nama');

        try {
            DB::table('absensi_peserta')->where('id', $absen_id)->update([
                'approval_status' => 'Disetujui',
            ]);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyetujui pengajuan.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal menyetujui pengajuan.');
        }

        $msg = $nama
            ? 'Pengajuan izin/sakit ' . $nama . ' berhasil disetujui.'
            : 'Pengajuan izin/sakit berhasil disetujui.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'title'   => 'Pengajuan Disetujui!',
                'nama'    => $nama,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', 'Pengajuan berhasil <strong>Disetujui</strong>.');
    }

    public function reject(Request $request)
    {
        $absen_id = (int) $request->input('absen_id');

        $nama = DB::table('absensi_peserta as ap')
            ->leftJoin('peserta as p', 'ap.username', '=', 'p.username')
            ->where('ap.id', $absen_id)
            ->value('p.nama');

        try {
            DB::table('absensi_peserta')->where('id', $absen_id)->update([
                'approval_status' => 'Ditolak',
            ]);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menolak pengajuan.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal menolak pengajuan.');
        }

        $msg = $nama
            ? 'Pengajuan izin/sakit ' . $nama . ' berhasil ditolak.'
            : 'Pengajuan izin/sakit berhasil ditolak.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'title'   => 'Pengajuan Ditolak!',
                'nama'    => $nama,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', 'Pengajuan berhasil <strong>Ditolak</strong>.');
    }

    public function approveAll(Request $request)
    {
        try {
            $jml = DB::table('absensi_peserta')
                ->whereIn('status', ['Izin', 'Sakit'])
                ->where('approval_status', 'Pending')
                ->update([
                    'approval_status' => 'Disetujui',
                ]);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyetujui semua pengajuan.'], 500);
            }
            return redirect()->back()->with('error', 'Gagal menyetujui semua pengajuan.');
        }

        if ($jml === 0) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada pengajuan izin/sakit yang menunggu.'], 422);
            }
            return redirect()->back()->with('error', 'Tidak ada pengajuan izin/sakit yang menunggu.');
        }

        $msg = $jml . ' pengajuan izin/sakit berhasil disetujui.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'title'   => 'Pengajuan Disetujui!',
                'count'   => $jml,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', '<strong>' . $jml . '</strong> pengajuan izin/sakit berhasil <strong>Disetujui</strong>.');
    }
}

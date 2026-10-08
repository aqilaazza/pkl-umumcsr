<?php

namespace App\Http\Controllers;

use App\Models\PengaturanTtd;
use App\Models\SertifikatMagang;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VerifikasiController extends Controller
{
    public function show(Request $request): View|Response
    {
        $token = trim((string) $request->query('token', ''));

        if ($token === '') {
            return response('Token tidak valid.', 400);
        }

        $sertifikat = SertifikatMagang::query()
            ->from('sertifikat_magang as sm')
            ->select('sm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', 'p.status_peserta', 'p.tgl_masuk', 'p.tgl_keluar', 'b.bidang as nama_bidang')
            ->join('peserta as p', 'sm.username', '=', 'p.username')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->where('sm.qr_token', $token)
            ->first();

        if (! $sertifikat) {
            return response('Sertifikat tidak ditemukan atau token tidak valid.', 404);
        }

        $ttd = PengaturanTtd::where('id', 1)->first();

        return view('ceklogin.verifikasi', compact('sertifikat', 'ttd'));
    }
}

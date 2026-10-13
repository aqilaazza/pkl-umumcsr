<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapAbsensiController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.rekap_absensi',
            'need_datatables' => true,
        ]);
    }

    public function ajaxDetail(Request $request)
    {
        $user  = (string) $request->query('user', '');
        $bulan = preg_replace('/[^0-9\-]/', '', (string) $request->query('bulan', ''));

        $awal  = $bulan . '-01';
        $akhir = date('Y-m-d', strtotime($awal . ' +1 month'));

        $rows = DB::table('absensi_peserta')
            ->where('username', $user)
            ->where('tanggal', '>=', $awal)
            ->where('tanggal', '<', $akhir)
            ->orderBy('tanggal', 'asc')
            ->get();

        return response()->json($rows);
    }

    public function hapusAbsen(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:50',
            'tanggal'  => 'required|date_format:Y-m-d',
            'tipe'     => 'required|in:masuk,keluar',
        ]);

        $query = DB::table('absensi_peserta')
            ->where('username', $data['username'])
            ->where('tanggal', $data['tanggal']);

        if (!$query->exists()) {
            return response()->json(['success' => false, 'message' => 'Data absensi tidak ditemukan.'], 404);
        }

        // Kosongkan jam + koordinat GPS sesuai tipe yang dipilih
        $kolom = $data['tipe'] === 'masuk'
            ? ['jam_masuk' => null, 'lat_masuk' => null, 'lng_masuk' => null]
            : ['jam_keluar' => null, 'lat_keluar' => null, 'lng_keluar' => null];

        $query->update($kolom);

        // Kalau jam masuk kosong pada baris berstatus Hadir, hapus seluruh barisnya.
        // Alasannya: absen keluar tanpa absen masuk tidak valid, dan baris yang
        // tersisa akan mengunci peserta (clock_in ditolak karena dianggap "sudah absen").
        // Dengan baris terhapus, hari itu tidak terhitung Hadir dan peserta bisa
        // absen masuk lagi.
        $row = DB::table('absensi_peserta')
            ->where('username', $data['username'])
            ->where('tanggal', $data['tanggal'])
            ->first();

        $rowDihapus = false;
        if ($row && $row->status === 'Hadir' && empty($row->jam_masuk)) {
            DB::table('absensi_peserta')
                ->where('username', $data['username'])
                ->where('tanggal', $data['tanggal'])
                ->delete();
            $rowDihapus = true;
        }

        return response()->json(['success' => true, 'row_deleted' => $rowDihapus]);
    }
}
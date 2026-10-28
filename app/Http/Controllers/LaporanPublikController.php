<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaporanPublikController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim($request->query('q', ''));
        $bidang = (int) $request->query('bidang', 0);

        $query = DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->leftJoin('sertifikat_magang as sm', 'sm.laporan_id', '=', 'lm.id')
            ->where('lm.manager_status', 'Disetujui')
            ->where('lm.sdm_status', 'Disetujui');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where('p.nama', 'like', $like)
                  ->orWhere('lm.username', 'like', $like)
                  ->orWhere('p.asal_sekolah', 'like', $like)
                  ->orWhere('p.jurusan', 'like', $like)
                  ->orWhere('b.bidang', 'like', $like)
                  ->orWhere('lm.nama_file', 'like', $like);
            });
        }

        if ($bidang > 0) {
            $query->where('p.bidang_id', $bidang);
        }

        $laporan = $query->select(
                'lm.id', 'lm.username', 'lm.file_laporan', 'lm.nama_file', 'lm.ukuran_file',
                'lm.manager_tgl_review',
                'p.nama', 'p.asal_sekolah', 'p.jurusan', 'p.status_peserta', 'p.unit',
                'p.tgl_masuk', 'p.tgl_keluar',
                'b.bidang as nama_bidang', 'sm.nomor_surat'
            )
            ->orderByDesc('lm.manager_tgl_review')
            ->paginate(12)
            ->withQueryString();

        $bidangList = DB::table('bidang')->orderBy('bidang')->pluck('bidang', 'id');

        return view('laporan.index', compact('laporan', 'bidangList', 'q', 'bidang'));
    }

    public function file(Request $request, $id)
    {
        $row = DB::table('laporan_magang')
            ->where('id', $id)
            ->where('manager_status', 'Disetujui')
            ->where('sdm_status', 'Disetujui')
            ->first(['file_laporan', 'nama_file']);

        if (!$row || empty($row->file_laporan)) {
            abort(404, 'Laporan tidak ditemukan atau belum disetujui Manager.');
        }

        $stored = $row->file_laporan;
        $base   = basename($stored);

        // Cegah path traversal pada nilai dari DB
        if (str_contains($stored, '..')) {
            abort(400, 'Permintaan tidak valid.');
        }

        // Cek beberapa lokasi yang umum dipakai Laravel
        $candidates = [
            storage_path('app/public/' . ltrim($stored, '/')),   // store('laporan','public') -> "laporan/xxx.pdf"
            storage_path('app/public/laporan/' . $base),
            storage_path('app/private/' . ltrim($stored, '/')),  // Laravel 11+ disk "local"
            storage_path('app/private/laporan/' . $base),
            storage_path('app/' . ltrim($stored, '/')),
            storage_path('app/laporan/' . $base),
            public_path('uploads/laporan/' . $base),             // lokasi lama (PHP native)
            public_path('uploads/' . ltrim($stored, '/')),
        ];

        $path = null;
        foreach ($candidates as $c) {
            if (is_file($c)) { $path = $c; break; }
        }

        if ($path === null) {
            Log::warning('File laporan tidak ditemukan', [
                'id' => $id, 'file_laporan' => $stored, 'dicek' => $candidates,
            ]);
            abort(404, 'File laporan tidak tersedia.');
        }

        $name = str_replace(['"', "\r", "\n"], '', $row->nama_file ?: $base);

        if ($request->query('mode') === 'download') {
            return response()->download($path, $name);
        }

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }
}
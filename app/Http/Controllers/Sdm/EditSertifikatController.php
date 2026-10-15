<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use App\Services\SertifikatDigitalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EditSertifikatController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $per_page = 15;
        $page = max(1, (int) $request->query('p', 1));
        $offset = ($page - 1) * $per_page;

        $baseQuery = function () use ($search) {
            $query = DB::table('sertifikat_magang as sm')
                ->join('peserta as p', 'sm.username', '=', 'p.username')
                ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('p.nama', 'like', '%'.$search.'%')
                        ->orWhere('sm.username', 'like', '%'.$search.'%')
                        ->orWhere('p.asal_sekolah', 'like', '%'.$search.'%');
                });
            }

            return $query;
        };

        $total = $baseQuery()->count();
        $total_page = $total > 0 ? (int) ceil($total / $per_page) : 1;

        $data = $baseQuery()
            ->orderByDesc('sm.id')
            ->limit($per_page)
            ->offset($offset)
            ->get([
                'sm.id as sertif_id',
                'sm.username',
                'sm.nomor_surat',
                'sm.status as status_sertif',
                'p.id as peserta_id',
                'p.nama',
                'p.asal_sekolah',
                'p.jurusan',
                'p.bidang_id',
                'p.unit',
                'p.tgl_masuk',
                'p.tgl_keluar',
                'p.status_peserta',
                'b.bidang as nama_bidang',
            ]);

        $bidang_list = DB::table('bidang')->orderBy('bidang')->get();

        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.edit_sertifikat',
            'data' => $data,
            'bidang_list' => $bidang_list,
            'search' => $search,
            'total' => $total,
            'total_page' => $total_page,
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $username = trim((string) $request->input('username', ''));

        $sertif = DB::table('sertifikat_magang')->where('username', $username)->first();
        $peserta = DB::table('peserta')->where('username', $username)->first();

        if (! $sertif || ! $peserta) {
            return redirect()->back()->with('error', 'Data sertifikat tidak ditemukan.');
        }

        $nama = trim((string) $request->input('nama'));
        $asal_sekolah = trim((string) $request->input('asal_sekolah'));
        $jurusan = trim((string) $request->input('jurusan'));
        $tgl_masuk = (string) $request->input('tgl_masuk');
        $tgl_keluar = (string) $request->input('tgl_keluar');
        $bidang_id = $request->filled('bidang_id') ? (int) $request->input('bidang_id') : null;
        $unit = (string) $request->input('unit');

        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama lengkap tidak boleh kosong.';
        }
        if ($asal_sekolah === '') {
            $errors[] = 'Kampus/Asal sekolah tidak boleh kosong.';
        }
        if ($jurusan === '') {
            $errors[] = 'Jurusan tidak boleh kosong.';
        }
        if ($tgl_masuk === '') {
            $errors[] = 'Tanggal masuk tidak boleh kosong.';
        }
        if ($tgl_keluar === '') {
            $errors[] = 'Tanggal keluar tidak boleh kosong.';
        }
        if ($tgl_masuk !== '' && $tgl_keluar !== '' && $tgl_keluar <= $tgl_masuk) {
            $errors[] = 'Tanggal keluar harus setelah tanggal masuk.';
        }
        if (! in_array($unit, ['Unit 1-2', 'Unit 9'], true)) {
            $errors[] = 'Unit tidak valid.';
        }

        if (! empty($errors)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $errors));
        }

        DB::table('peserta')->where('username', $username)->update([
            'nama' => $nama,
            'asal_sekolah' => $asal_sekolah,
            'jurusan' => $jurusan,
            'tgl_masuk' => $tgl_masuk,
            'tgl_keluar' => $tgl_keluar,
            'bidang_id' => $bidang_id,
            'unit' => $unit,
        ]);

        DB::table('users')
            ->where('username', $username)
            ->where('role', 'peserta')
            ->update(['nama' => $nama]);

        $regenerated = false;
        if ($sertif->status === 'Digital' && ! empty($sertif->qr_token) && ! empty($sertif->nomor_surat)) {
            $svc = new SertifikatDigitalService();
            $newFile = $svc->generate($username, (int) $sertif->laporan_id, $sertif->qr_token, $sertif->nomor_surat);

            if ($newFile) {
                DB::table('sertifikat_magang')->where('id', $sertif->id)->update([
                    'digital_file' => $newFile,
                    'generated_at' => DB::raw('NOW()'),
                ]);

                $oldFile = (string) $sertif->digital_file;
                if ($oldFile !== '' && $oldFile !== $newFile) {
                    $oldPath = public_path('uploads/sertifikat/'.$oldFile);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $regenerated = true;
            }
        }

        $message = 'Data sertifikat '.e($nama).' berhasil diperbarui.';
        $message .= $regenerated
            ? ' Sertifikat digital telah diperbarui secara otomatis.'
            : '';

        return redirect()->back()->with('success', $message);
    }
}

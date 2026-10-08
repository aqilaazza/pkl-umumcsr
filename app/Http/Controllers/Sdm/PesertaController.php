<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PesertaController extends Controller
{
    /**
     * Hash password default yang dipakai legacy (bcrypt dari "12345").
     * Dipertahankan apa adanya agar hash yang tersimpan identik.
     */
    private const DEFAULT_PASSWORD_HASH = '$2y$10$NEw7BdY0IR9GGEuiJjCOIOT/gxSFOH8j54MnRLk9G3yOuCASPeFre';

    public function index(): View|RedirectResponse
    {
        // Legacy: hapus via GET index.php?page=daftar_peserta&hapus=<id>
        if (request()->filled('hapus')) {
            $id  = (int) request('hapus');
            $row = DB::table('peserta')->where('id', $id)->first(['username', 'nama']);

            if ($row) {
                DB::table('peserta')->where('id', $id)->delete();
                DB::table('users')->where('username', $row->username)->where('role', 'peserta')->delete();

                return redirect('sdm/peserta')->with('success', 'Peserta <strong>' . e($row->nama) . '</strong> berhasil dihapus.');
            }

            return redirect('sdm/peserta');
        }

        return view('layouts.sdm', [
            'pageView'       => 'sdm.pages.daftar_peserta',
            'need_datatables' => true,
        ]);
    }

    public function create(): View
    {
        return view('layouts.sdm', [
            'pageView'        => 'sdm.pages.tambah_peserta',
            'need_datatables' => true,
            'need_fancyupload' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $username       = $this->generateUsername();
        $nama           = trim((string) $request->input('nama'));
        $status_peserta = (string) $request->input('status_peserta');
        $asal_sekolah   = trim((string) $request->input('asal_sekolah'));
        $jurusan        = trim((string) $request->input('jurusan'));
        $tgl_masuk      = (string) $request->input('tgl_masuk');
        $tgl_keluar     = (string) $request->input('tgl_keluar');
        $bidang_id      = $request->filled('bidang_id') ? (int) $request->input('bidang_id') : null;
        $unit           = (string) $request->input('unit');
        $status_magang  = (string) $request->input('status_magang');
        $keterangan     = trim((string) $request->input('keterangan'));

        $errors = [];

        if ($nama === '')          $errors[] = 'Nama lengkap tidak boleh kosong.';
        if ($asal_sekolah === '')  $errors[] = 'Asal sekolah tidak boleh kosong.';
        if ($jurusan === '')       $errors[] = 'Jurusan tidak boleh kosong.';
        if ($tgl_masuk === '')     $errors[] = 'Tanggal masuk tidak boleh kosong.';
        if ($tgl_keluar === '')    $errors[] = 'Tanggal keluar tidak boleh kosong.';
        if ($tgl_masuk !== '' && $tgl_keluar !== '' && $tgl_keluar <= $tgl_masuk) {
            $errors[] = 'Tanggal keluar harus setelah tanggal masuk.';
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('error', $this->errorList($errors));
        }

        DB::table('peserta')->insert([
            'username'       => $username,
            'nama'           => $nama,
            'status_peserta' => $status_peserta,
            'asal_sekolah'   => $asal_sekolah,
            'jurusan'        => $jurusan,
            'tgl_masuk'      => $tgl_masuk,
            'tgl_keluar'     => $tgl_keluar,
            'bidang_id'      => $bidang_id,
            'unit'           => $unit,
            'status_magang'  => $status_magang,
            'keterangan'     => $keterangan,
        ]);

        DB::table('users')->insert([
            'username' => $username,
            'nama'     => $nama,
            'password' => self::DEFAULT_PASSWORD_HASH,
            'role'     => 'peserta',
        ]);

        return redirect('sdm/peserta')->with('success', 'Peserta <strong>' . e($nama) . '</strong> berhasil ditambahkan. Username login: <code>' . e($username) . '</code>.');
    }

    public function edit($id): View|RedirectResponse
    {
        $id = (int) $id;

        if (! DB::table('peserta')->where('id', $id)->exists()) {
            return redirect('sdm/peserta');
        }

        return view('layouts.sdm', [
            'pageView'        => 'sdm.pages.edit_peserta',
            'need_datatables' => true,
            'need_fancyupload' => true,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $id        = (int) $id;
        $edit_data = DB::table('peserta')->where('id', $id)->first();

        if (! $edit_data) {
            return redirect('sdm/peserta');
        }

        $nama           = trim((string) $request->input('nama'));
        $status_peserta = (string) $request->input('status_peserta');
        $asal_sekolah   = trim((string) $request->input('asal_sekolah'));
        $jurusan        = trim((string) $request->input('jurusan'));
        $tgl_masuk      = (string) $request->input('tgl_masuk');
        $tgl_keluar     = (string) $request->input('tgl_keluar');
        $bidang_id      = $request->filled('bidang_id') ? (int) $request->input('bidang_id') : null;
        $unit           = (string) $request->input('unit');
        $status_magang  = (string) $request->input('status_magang');
        $keterangan     = trim((string) $request->input('keterangan'));

        $errors = [];

        if ($nama === '')          $errors[] = 'Nama lengkap tidak boleh kosong.';
        if ($asal_sekolah === '')  $errors[] = 'Asal sekolah tidak boleh kosong.';
        if ($jurusan === '')       $errors[] = 'Jurusan tidak boleh kosong.';
        if ($tgl_masuk === '')     $errors[] = 'Tanggal masuk tidak boleh kosong.';
        if ($tgl_keluar === '')    $errors[] = 'Tanggal keluar tidak boleh kosong.';
        if ($tgl_masuk !== '' && $tgl_keluar !== '' && $tgl_keluar <= $tgl_masuk) {
            $errors[] = 'Tanggal keluar harus setelah tanggal masuk.';
        }

        if ($errors) {
            return redirect()->back()->withInput()->with('error', $this->errorList($errors));
        }

        DB::table('peserta')->where('id', $id)->update([
            'nama'           => $nama,
            'status_peserta' => $status_peserta,
            'asal_sekolah'   => $asal_sekolah,
            'jurusan'        => $jurusan,
            'tgl_masuk'      => $tgl_masuk,
            'tgl_keluar'     => $tgl_keluar,
            'bidang_id'      => $bidang_id,
            'unit'           => $unit,
            'status_magang'  => $status_magang,
            'keterangan'     => $keterangan,
        ]);

        // Sinkron nama ke users (legacy: UPDATE users SET nama = ... WHERE username = ... AND role = 'peserta')
        DB::table('users')
            ->where('username', $edit_data->username)
            ->where('role', 'peserta')
            ->update(['nama' => $nama]);

        return redirect('sdm/peserta')->with('success', 'Data peserta <strong>' . e($nama) . '</strong> berhasil diperbarui.');
    }

    public function cekUsername(Request $request): JsonResponse
    {
        $username = trim((string) $request->query('username'));

        if ($username === '') {
            return response()->json(['tersedia' => false, 'pesan' => 'Username kosong.']);
        }

        $cek_user = DB::table('users')->where('username', $username)->count();
        $cek_pest = DB::table('peserta')->where('username', $username)->count();

        if ($cek_user > 0 || $cek_pest > 0) {
            return response()->json(['tersedia' => false, 'pesan' => 'Username sudah digunakan.']);
        }

        return response()->json(['tersedia' => true, 'pesan' => 'Username tersedia.']);
    }

    /**
     * Generate username otomatis — logika identik dengan legacy tambah_peserta.php.
     */
    private function generateUsername(): string
    {
        $last = DB::table('peserta')->orderBy('id', 'desc')->value('username');

        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = (int) $m[1] + 1;
        } else {
            $num = 1;
        }

        do {
            $username = str_pad($num, 4, '0', STR_PAD_LEFT);
            $cek_u    = DB::table('users')->where('username', $username)->count();
            $cek_p    = DB::table('peserta')->where('username', $username)->count();

            if ($cek_u > 0 || $cek_p > 0) {
                $num++;
            }
        } while ($cek_u > 0 || $cek_p > 0);

        return $username;
    }

    /**
     * Format daftar error persis seperti alert legacy (list <li>).
     */
    private function errorList(array $errors): string
    {
        $items = '';

        foreach ($errors as $err) {
            $items .= '<li>' . e($err) . '</li>';
        }

        return '<ul class="mb-0 ps-3 text-start">' . $items . '</ul>';
    }
}

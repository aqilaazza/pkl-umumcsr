<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $data_profile = DB::selectOne("SELECT * FROM users WHERE username = ?", [$user->username]);
        $pending_count = (int) DB::selectOne("
            SELECT COUNT(*) n FROM laporan_magang WHERE sdm_status='Disetujui' AND manager_status='Menunggu'
        ")->n;
        return view('layouts.manager', [
            'pageView' => 'manager.pages.profile',
            'need_datatables' => false,
            'pending_count' => $pending_count,
            'data_profile' => $data_profile,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $data_profile = DB::selectOne("SELECT * FROM users WHERE username = ?", [$user->username]);
        if (!$data_profile) {
            return redirect()->back()->with('error', 'Data user tidak ditemukan.');
        }
        $nama = trim($request->input('nama', $data_profile->nama));
        $password_baru = $request->input('password_baru');
        $konfirmasi_password = $request->input('konfirmasi_password');
        if ($password_baru || $konfirmasi_password) {
            if (empty($password_baru) || empty($konfirmasi_password)) {
                return redirect()->back()->with('error', 'Password tidak boleh kosong!');
            }
            if ($password_baru !== $konfirmasi_password) {
                return redirect()->back()->with('error', 'Konfirmasi password tidak sesuai!');
            }
            if (strlen($password_baru) < 6) {
                return redirect()->back()->with('error', 'Password minimal 6 karakter!');
            }
            $hashed_password = Hash::make($password_baru);
            DB::update("UPDATE users SET password = ?, updated_at = NOW() WHERE username = ?", [$hashed_password, $user->username]);
            return redirect()->back()->with('success', 'Berhasil Merubah Password Baru');
        } else {
            DB::update("UPDATE users SET nama = ?, updated_at = NOW() WHERE username = ?", [$nama, $user->username]);
            return redirect()->back()->with('success', 'Profil berhasil diperbarui');
        }
    }
}

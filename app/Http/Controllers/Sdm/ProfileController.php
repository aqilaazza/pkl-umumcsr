<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.profile',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->changePassword($request);
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $username = auth()->user()->username;

        $password_baru = $request->input('password_baru');
        $konfirmasi_password = $request->input('konfirmasi_password');

        // Validasi password tidak kosong
        if (empty($password_baru) || empty($konfirmasi_password)) {
            return redirect()->back()->with('error', 'Password tidak boleh kosong!');
        }

        // Validasi konfirmasi password
        if ($password_baru !== $konfirmasi_password) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak sesuai!');
        }

        // Validasi panjang password minimal 6 karakter
        if (strlen($password_baru) < 6) {
            return redirect()->back()->with('error', 'Password minimal 6 karakter!');
        }

        // Hash password baru
        $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);

        // Update ke database
        $update = DB::table('users')->where('username', $username)->update([
            'password' => $hashed_password,
        ]);

        if ($update) {
            return redirect()->back()->with('success', 'Berhasil Merubah Password Baru');
        }

        return redirect()->back()->with('error', 'Gagal merubah password!');
    }
}

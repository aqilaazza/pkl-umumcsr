<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Folder penyimpanan avatar (relatif terhadap folder public) */
    private const AVATAR_DIR = 'uploads/avatar';

    /** Maksimal ukuran file avatar: 2 MB */
    private const AVATAR_MAX_SIZE = 2 * 1024 * 1024;

    /** Format yang boleh diunggah */
    private const AVATAR_ALLOWED = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    public function index(): View
    {
        return view('layouts.sdm', [
            'pageView'        => 'sdm.pages.profile',
            'need_profile_css' => true,
            'user'            => auth()->user(),
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
            'password'            => $hashed_password,
            'password_updated_at' => now(),
        ]);

        if ($update) {
            return redirect()->back()->with('success', 'Berhasil Merubah Password Baru');
        }

        return redirect()->back()->with('error', 'Gagal merubah password!');
    }

    /**
     * Upload / ganti foto avatar akun yang sedang login.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        if (! $request->hasFile('avatar') || ! $request->file('avatar')->isValid()) {
            return redirect()->back()->with('error', 'Silakan pilih file foto untuk diunggah.');
        }

        $file = $request->file('avatar');
        $ext  = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if (! isset(self::AVATAR_ALLOWED[$ext]) || self::AVATAR_ALLOWED[$ext] !== $mime) {
            return redirect()->back()->with('error', 'Format file tidak didukung. Gunakan file <strong>JPG, PNG, atau WEBP</strong>.');
        }

        if ($file->getSize() > self::AVATAR_MAX_SIZE) {
            return redirect()->back()->with('error', 'Ukuran file terlalu besar. Maksimal 2 MB.');
        }

        $dir = public_path(self::AVATAR_DIR);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $username = (string) auth()->user()->username;
        $filename = 'avatar_' . preg_replace('/[^A-Za-z0-9_-]/', '', $username) . '_' . time() . '.' . $ext;

        try {
            $file->move($dir, $filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan foto avatar ke server.');
        }

        // Hapus file lama (bila ada) supaya tidak menumpuk
        $this->deleteAvatarFile((string) DB::table('users')->where('username', $username)->value('avatar'));

        DB::table('users')->where('username', $username)->update([
            'avatar' => $filename,
        ]);

        if (auth()->user()) {
            auth()->user()->avatar = $filename;
        }

        return redirect('sdm/profile')->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Hapus foto avatar dan kembalikan ke gambar bawaan.
     */
    public function deleteAvatar(): RedirectResponse
    {
        $username = (string) auth()->user()->username;
        $avatar   = (string) DB::table('users')->where('username', $username)->value('avatar');

        if ($avatar !== '') {
            $this->deleteAvatarFile($avatar);
        }

        DB::table('users')->where('username', $username)->update([
            'avatar' => null,
        ]);

        if (auth()->user()) {
            auth()->user()->avatar = null;
        }

        return redirect('sdm/profile')->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Hapus berkas avatar dari disk (diabaikan bila tidak ada).
     */
    private function deleteAvatarFile(string $filename): void
    {
        if ($filename === '') {
            return;
        }

        $path = public_path(self::AVATAR_DIR . '/' . basename($filename));

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

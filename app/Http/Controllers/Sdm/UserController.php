<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.users',
        ]);
    }

    // --- SIMPAN (INSERT) ---
    public function store(Request $request): RedirectResponse
    {
        $nid      = (string) $request->input('nid');
        $nama     = (string) $request->input('nama');
        $password = (string) $request->input('password');
        $role     = (string) $request->input('role');

        $cek = DB::table('users')->where('username', $nid)->count();
        if ($cek > 0) {
            return redirect()->back()->with('error', 'NID <strong>' . e($nid) . '</strong> sudah digunakan.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        DB::table('users')->insert([
            'username' => $nid,
            'nama'     => $nama,
            'password' => $hash,
            'role'     => $role,
        ]);

        return redirect()->back()->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id): View|RedirectResponse
    {
        $id = (int) $id;

        if (! $id) {
            return redirect('sdm/users');
        }

        $data = DB::table('users')->where('id', $id)->first();

        if (! $data) {
            return redirect('sdm/users');
        }

        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.edit_users',
            'data' => $data,
        ]);
    }

    // --- UPDATE ---
    public function update(Request $request, $id): RedirectResponse
    {
        $id   = (int) $id;
        $nid  = (string) $request->input('nid');
        $nama = (string) $request->input('nama');
        $role = (string) $request->input('role');

        $cek = DB::table('users')->where('username', $nid)->where('id', '!=', $id)->count();
        if ($cek > 0) {
            return redirect()->back()->with('error', 'NID sudah digunakan user lain.');
        }

        $update = [
            'username' => $nid,
            'nama'     => $nama,
            'role'     => $role,
        ];

        if (! empty($request->input('password'))) {
            $update['password'] = password_hash((string) $request->input('password'), PASSWORD_DEFAULT);
        }

        DB::table('users')->where('id', $id)->update($update);

        return redirect('sdm/users')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $id = (int) $id;

        if ($id > 0) {
            DB::table('users')->where('id', $id)->delete();
        }

        return redirect('sdm/users')->with('success', 'User berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'NRP' => 'required',
            'pass' => 'required',
        ]);

        $nid = $request->input('NRP');
        $pass = $request->input('pass');

        $user = User::where('username', $nid)->first();

        if (! $user) {
            return back()->with('error', 'User tidak ditemukan');
        }

        if (! Hash::check($pass, $user->password)) {
            return back()->with('error', 'Password salah');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user->role);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    protected function redirectByRole(?string $role): RedirectResponse
    {
        return match ($role) {
            'peserta' => redirect('/peserta'),
            'sdm' => redirect('/sdm'),
            'manager' => redirect('/manager'),
            default => redirect('/login'),
        };
    }
}

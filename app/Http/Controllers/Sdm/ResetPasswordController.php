<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function index(): View
    {
        return $this->renderPage(null, '', false);
    }

    public function store(Request $request)
    {
        // --- PROSES PENCARIAN ---
        if ($request->has('search')) {
            $keyword = trim((string) $request->input('keyword'));

            // Query lengkap dengan JOIN ke tabel bidang
            $search_result = DB::table('peserta as p')
                ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
                ->join('users as u', 'p.username', '=', 'u.username')
                ->where(function ($query) use ($keyword) {
                    $query->where('p.nama', 'like', '%' . $keyword . '%')
                        ->orWhere('p.username', $keyword);
                })
                ->get(['p.*', DB::raw('b.bidang as nama_bidang')]);

            return $this->renderPage($search_result, $keyword, true);
        }

        // --- PROSES RESET PASSWORD ---
        if ($request->has('confirm_reset')) {
            $username = (string) $request->input('username');

            // Hash password default: 123456
            $new_password = password_hash('123456', PASSWORD_BCRYPT);

            DB::table('users')->where('username', $username)->update([
                'password' => $new_password,
            ]);

            return redirect()->back()->with(
                'success',
                'Password untuk <strong>' . e($username) . '</strong> telah direset menjadi: <strong>123456</strong>'
            );
        }

        return redirect()->back();
    }

    protected function renderPage($search_result, string $keyword, bool $search_performed): View
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.reset_password',
            'search_result' => $search_result,
            'keyword' => $keyword,
            'search_performed' => $search_performed,
        ]);
    }
}

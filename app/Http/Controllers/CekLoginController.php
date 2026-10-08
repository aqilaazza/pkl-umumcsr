<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Peserta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CekLoginController extends Controller
{
    public function index(): View
    {
        $bidang_list = Bidang::orderBy('bidang')->pluck('bidang', 'id')->all();

        return view('ceklogin.index', compact('bidang_list'));
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $data = Peserta::query()
            ->select('id', 'nama', 'asal_sekolah', 'status_magang')
            ->where('nama', 'like', '%'.$search.'%')
            ->orderByRaw(
                'CASE WHEN nama LIKE ? THEN 1 WHEN nama LIKE ? THEN 2 ELSE 3 END',
                [$search.'%', '%'.$search.'%']
            )
            ->orderBy('nama')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function getPeserta(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (! is_numeric($id)) {
            return response()->json(['error' => 'ID tidak valid'], 400);
        }

        $row = Peserta::where('id', (int) $id)->first();

        if (! $row) {
            return response()->json(['error' => 'Peserta tidak ditemukan'], 404);
        }

        return response()->json($row);
    }
}

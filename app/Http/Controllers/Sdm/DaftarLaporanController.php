<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DaftarLaporanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $query = DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->where('lm.sdm_status', 'Disetujui')
            ->where('lm.manager_status', 'Disetujui');

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('p.nama', 'like', '%'.$search.'%')
                    ->orWhere('lm.nama_file', 'like', '%'.$search.'%');
            });
        }

        $laporan = $query
            ->select(
                'lm.id', 'lm.username', 'lm.file_laporan', 'lm.nama_file', 'lm.is_active',
                'p.nama', 'p.tgl_masuk', 'p.tgl_keluar'
            )
            // Urut berdasarkan periode magang terbaru, bukan tanggal review/unggah
            ->orderByDesc('p.tgl_keluar')
            ->orderByDesc('p.tgl_masuk')
            ->orderByDesc('lm.manager_tgl_review')
            ->orderByDesc('lm.id') // tie-breaker unik: urutan stabil antar halaman
            ->paginate(25)
            ->withQueryString();

        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.daftar_laporan',
            'laporan' => $laporan,
            'search' => $search,
        ]);
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = DB::table('laporan_magang')
            ->where('id', $id)
            ->where('sdm_status', 'Disetujui')
            ->where('manager_status', 'Disetujui');

        if (! $query->exists()) {
            return $this->kembali($validated)->with('error', 'Laporan tidak ditemukan atau belum disetujui.');
        }

        $isActive = (bool) $validated['is_active'];
        $query->update(['is_active' => $isActive ? 1 : 0]);

        return $this->kembali($validated)->with(
            'success',
            $isActive
                ? 'Laporan berhasil diaktifkan.'
                : 'Laporan berhasil dinonaktifkan dan disembunyikan dari peserta.'
        );
    }

    private function kembali(array $input): RedirectResponse
    {
        return redirect()->route('sdm.pengaturan.daftar_laporan', array_filter([
            'q' => $input['q'] ?? null,
            'page' => $input['page'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
    }
}
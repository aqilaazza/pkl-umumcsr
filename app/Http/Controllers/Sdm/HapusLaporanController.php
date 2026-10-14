<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HapusLaporanController extends Controller
{
    private function uploadDir(): string
    {
        return public_path('uploads/laporan');
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id');

        $search = trim((string) $request->query('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'like', '%'.$search.'%')
                    ->orWhere('lm.username', 'like', '%'.$search.'%')
                    ->orWhere('p.asal_sekolah', 'like', '%'.$search.'%')
                    ->orWhere('lm.nama_file', 'like', '%'.$search.'%');
            });
        }

        $approve = (string) $request->query('approve');
        if ($approve !== '') {
            $query->where('lm.status', $approve);
        }

        [$start, $end] = $this->periodeRange($request);
        if ($start !== '') {
            $query->whereDate('lm.tgl_upload', '>=', $start);
        }
        if ($end !== '') {
            $query->whereDate('lm.tgl_upload', '<=', $end);
        }

        return $query;
    }

    /**
     * Ambil rentang tanggal upload dari parameter tgl_dari & tgl_sampai (format Y-m-d).
     * Salah satu boleh kosong. Jika dari > sampai, otomatis ditukar.
     *
     * @return array{0: string, 1: string}
     */
    private function periodeRange(Request $request): array
    {
        $start = $this->parseTanggal($request->query('tgl_dari'));
        $end = $this->parseTanggal($request->query('tgl_sampai'));

        if ($start !== '' && $end !== '' && $start > $end) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function parseTanggal($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $value);

        return ($dt && $dt->format('Y-m-d') === $value) ? $value : '';
    }

    public function index(Request $request)
    {
        $per_page = 15;
        $page = max(1, (int) $request->query('p', 1));
        $offset = ($page - 1) * $per_page;

        $base = $this->baseQuery($request);
        $total = (clone $base)->count();
        $total_page = (int) ceil($total / $per_page);

        $data = (clone $base)
            ->select('lm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', DB::raw('b.bidang AS nama_bidang'))
            ->orderByDesc('lm.tgl_upload')
            ->limit($per_page)
            ->offset($offset)
            ->get();

        // Statistik dihitung dari seluruh hasil filter (bukan hanya halaman aktif).
        $stats = (clone $base)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(lm.status='Menunggu'), 0) as menunggu")
            ->selectRaw("COALESCE(SUM(lm.status='Disetujui'), 0) as disetujui")
            ->selectRaw("COALESCE(SUM(lm.status='Ditolak'), 0) as ditolak")
            ->selectRaw('COALESCE(SUM(lm.ukuran_file), 0) as total_size')
            ->first();

        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.hapus_laporan',
            'data' => $data,
            'total' => $total,
            'total_page' => $total_page,
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset,
            'search' => trim((string) $request->query('q')),
            'approve_filter' => (string) $request->query('approve'),
            'stats' => $stats,
        ]);
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('laporan_id');
        $row = DB::table('laporan_magang')->where('id', $id)->first();

        if (! $row) {
            return redirect()->back()->with('error', 'Data laporan tidak ditemukan.');
        }

        $this->deleteFile($row->file_laporan);
        DB::table('laporan_magang')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Laporan berhasil dihapus.');
    }

    public function destroyBulk(Request $request)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Pilih minimal satu laporan untuk dihapus.');
        }

        $rows = DB::table('laporan_magang')->whereIn('id', $ids)->get();

        foreach ($rows as $row) {
            $this->deleteFile($row->file_laporan);
        }

        DB::table('laporan_magang')->whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', $rows->count().' laporan berhasil dihapus.');
    }

    private function deleteFile(?string $file): void
    {
        if (! $file) {
            return;
        }

        $path = $this->uploadDir().DIRECTORY_SEPARATOR.$file;

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
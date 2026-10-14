<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupLaporanController extends Controller
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
            'pageView' => 'sdm.pages.backup_laporan',
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

    public function download(Request $request): BinaryFileResponse
    {
        $rows = $this->baseQuery($request)
            ->select('lm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', DB::raw('b.bidang AS nama_bidang'))
            ->orderBy('lm.id')
            ->get();

        $zip_path = tempnam(sys_get_temp_dir(), 'backup_laporan_').'.zip';

        $zip = new \ZipArchive;
        if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Gagal membuat file backup.');
        }

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, [
            'ID', 'Username', 'Nama', 'Asal Sekolah', 'Jurusan', 'Bidang',
            'Nama File', 'File Laporan', 'Ukuran (byte)',
            'Status', 'Status SDM', 'Status Manager',
            'Tgl Upload', 'Tgl Review SDM', 'Reviewer SDM', 'Tgl Review Manager', 'Reviewer Manager',
        ]);

        $upload_dir = $this->uploadDir();
        $added = [];

        foreach ($rows as $r) {
            fputcsv($csv, [
                $r->id, $r->username, $r->nama, $r->asal_sekolah, $r->jurusan, $r->nama_bidang,
                $r->nama_file, $r->file_laporan, $r->ukuran_file,
                $r->status, $r->sdm_status, $r->manager_status,
                $r->tgl_upload, $r->sdm_tgl_review, $r->sdm_reviewed_by, $r->manager_tgl_review, $r->manager_reviewed_by,
            ]);

            $file = (string) $r->file_laporan;
            $path = $upload_dir.DIRECTORY_SEPARATOR.$file;

            if ($file !== '' && ! isset($added[$file]) && is_file($path)) {
                $zip->addFile($path, 'laporan/'.$file);
                $added[$file] = true;
            }
        }

        rewind($csv);
        $zip->addFromString('data_laporan.csv', stream_get_contents($csv));
        fclose($csv);

        $zip->addFromString('README.txt', implode("\n", [
            'BACKUP LAPORAN MAGANG',
            'Dibuat       : '.date('d/m/Y H:i:s'),
            'Jumlah data  : '.count($rows),
            'Jumlah file  : '.count($added),
            '',
            'Isi arsip:',
            '- data_laporan.csv : seluruh data laporan',
            '- laporan/         : file PDF laporan',
        ]));

        $zip->close();

        $filename = 'backup_laporan_'.date('Ymd_His').'.zip';

        return response()->download($zip_path, $filename)->deleteFileAfterSend(true);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->baseQuery($request)
            ->select('lm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', DB::raw('b.bidang AS nama_bidang'))
            ->orderBy('lm.id')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan');

        $headers = [
            'No', 'Username', 'Nama', 'Asal Sekolah', 'Jurusan', 'Bidang',
            'Nama File', 'Ukuran (KB)', 'Status', 'Status SDM', 'Status Manager', 'Tgl Upload',
        ];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

        foreach ($cols as $i => $col) {
            $sheet->setCellValue($col.'1', $headers[$i]);
        }

        $rowNum = 2;
        $no = 1;

        foreach ($rows as $r) {
            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValue('B'.$rowNum, $r->username);
            $sheet->setCellValue('C'.$rowNum, $r->nama);
            $sheet->setCellValue('D'.$rowNum, $r->asal_sekolah);
            $sheet->setCellValue('E'.$rowNum, $r->jurusan);
            $sheet->setCellValue('F'.$rowNum, $r->nama_bidang);
            $sheet->setCellValue('G'.$rowNum, $r->nama_file);
            $sheet->setCellValue('H'.$rowNum, round(((int) $r->ukuran_file) / 1024, 1));
            $sheet->setCellValue('I'.$rowNum, $r->status);
            $sheet->setCellValue('J'.$rowNum, $r->sdm_status);
            $sheet->setCellValue('K'.$rowNum, $r->manager_status);
            $sheet->setCellValue('L'.$rowNum, $r->tgl_upload ? date('Y-m-d H:i', strtotime($r->tgl_upload)) : '');

            $rowNum++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'data_laporan_'.date('Ymd_His').'.xlsx';

        return new StreamedResponse(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
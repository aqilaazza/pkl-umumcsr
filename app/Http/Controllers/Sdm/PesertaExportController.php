<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PesertaExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $search        = trim((string) $request->query('q'));
        $status_filter = (string) $request->query('status');

        // Terima dari params terpisah (bulan_dari + tahun_dari) atau format gabungan YYYY-MM
        $b_dari   = $request->query('bulan_dari') ? str_pad((string) $request->query('bulan_dari'), 2, '0', STR_PAD_LEFT) : '';
        $t_dari   = $request->query('tahun_dari') ? (int) $request->query('tahun_dari') : 0;
        $b_sampai = $request->query('bulan_sampai') ? str_pad((string) $request->query('bulan_sampai'), 2, '0', STR_PAD_LEFT) : '';
        $t_sampai = $request->query('tahun_sampai') ? (int) $request->query('tahun_sampai') : 0;

        $bulan_dari   = ($b_dari && $t_dari) ? $t_dari . '-' . $b_dari : '';
        $bulan_sampai = ($b_sampai && $t_sampai) ? $t_sampai . '-' . $b_sampai : '';

        $query = DB::table('peserta as p')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id');

        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('p.nama', 'like', '%' . $search . '%')
                    ->orWhere('p.username', 'like', '%' . $search . '%')
                    ->orWhere('p.asal_sekolah', 'like', '%' . $search . '%');
            });
        }

        if ($status_filter !== '') {
            $query->where('p.status_magang', $status_filter);
        }

        [$start, $end] = $this->dateRange($bulan_dari, $bulan_sampai);

        if ($start && $end) {
            $query->whereRaw("NOT (p.tgl_keluar < ? OR p.tgl_masuk > ?)", [$start, $end]);
        }

        $rows = $query->orderBy('p.id', 'desc')
            ->get(['p.*', DB::raw('b.bidang AS nama_bidang')]);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Peserta');

        $headers = ['No', 'Username', 'Nama', 'Status Peserta', 'Status Magang', 'Asal Sekolah', 'Bidang', 'Unit', 'Tgl Masuk', 'Tgl Keluar', 'Durasi(hari)', 'Keterangan'];
        $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];

        foreach ($cols as $i => $col) {
            $sheet->setCellValue($col . '1', $headers[$i]);
        }

        $rowNum = 2;
        $no     = 1;

        foreach ($rows as $r) {
            $masuk  = $r->tgl_masuk;
            $keluar = $r->tgl_keluar;
            $durasi = '';

            if ($masuk && $keluar) {
                $d1    = new \DateTime($masuk);
                $d2    = new \DateTime($keluar);
                $durasi = $d1->diff($d2)->days;
            }

            $sheet->setCellValue('A' . $rowNum, $no++);
            $sheet->setCellValue('B' . $rowNum, $r->username);
            $sheet->setCellValue('C' . $rowNum, $r->nama);
            $sheet->setCellValue('D' . $rowNum, $r->status_peserta);
            $sheet->setCellValue('E' . $rowNum, $r->status_magang);
            $sheet->setCellValue('F' . $rowNum, $r->asal_sekolah);
            $sheet->setCellValue('G' . $rowNum, $r->nama_bidang);
            $sheet->setCellValue('H' . $rowNum, $r->unit);
            $sheet->setCellValue('I' . $rowNum, $masuk ? date('Y-m-d', strtotime($masuk)) : '');
            $sheet->setCellValue('J' . $rowNum, $keluar ? date('Y-m-d', strtotime($keluar)) : '');
            $sheet->setCellValue('K' . $rowNum, $durasi);
            $sheet->setCellValue('L' . $rowNum, $r->keterangan);

            $rowNum++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $filename = 'peserta_export_' . date('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);

        return $response;
    }

    /**
     * Range tanggal filter — logika identik dengan legacy export_excel.php / daftar_peserta.php.
     *
     * @return array{0: string, 1: string}
     */
    private function dateRange(string $from, string $to): array
    {
        try {
            if ($from && $to) {
                $start = new \DateTime($from . '-01');
                $end   = new \DateTime($to . '-01');
                $end->modify('last day of this month');
            } elseif ($from) {
                $start = new \DateTime($from . '-01');
                $end   = clone $start;
                $end->modify('last day of this month');
            } elseif ($to) {
                $start = new \DateTime($to . '-01');
                $end   = clone $start;
                $end->modify('last day of this month');
            } else {
                return ['', ''];
            }
        } catch (\Exception $e) {
            return ['', ''];
        }

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }
}

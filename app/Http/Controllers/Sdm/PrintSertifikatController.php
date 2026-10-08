<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

require_once base_path('legacy/sdm/fpdf/fpdf.php');

if (!class_exists(__NAMESPACE__ . '\PDF', false)) {
    // Kelas sertifikat - identik dengan legacy print_sertifikat.php (extends FPDF)
    class PDF extends \FPDF
    {
        public $bgImage = '';

        function Header()
        {
            if ($this->bgImage && file_exists($this->bgImage)) {
                $this->Image($this->bgImage, 0, 0, 297, 210);
            }
        }

        function BuatSertifikat($nomorSurat, $nama, $sekolah, $bidang, $unit, $tglMulai, $tglSelesai, $namaTTD, $jabatanTTD)
        {
            $this->Cell(0, 20, '', 0, 1);
            $this->SetFont('Arial', '', 13);
            $this->Cell(0, 15, "No : $nomorSurat", 0, 1, 'C');
            $this->SetFont('Arial', '', 15);
            $this->Cell(0, 15, "Diberikan kepada:", 0, 1, 'C');
            $this->SetFont('Arial', 'B', 30);
            $this->Cell(0, 20, $nama, 0, 1, 'C');
            $this->SetFont('Arial', 'B', 20);
            $this->Cell(0, 10, $sekolah, 0, 1, 'C');
            $this->SetFont('Arial', '', 13);

            $bagian_str = "";
            if ($bidang || $unit) {
                $parts = [];
                if ($bidang) $parts[] = $bidang;
                if ($unit)   $parts[] = $unit;
                $bagian_str = " di Bagian " . implode(' ', $parts);
            } else {
                $bagian_str = " di";
            }
            $ket = "Telah Melaksanakan Praktek Kerja Lapangan" . $bagian_str . " PT PLN Nusantara Power UP Paiton";

            $this->Cell(0, 35, $ket, 0, 1, 'C');
            $this->SetY($this->GetY() - 10);
            $this->SetFont('Arial', '', 13);

            $periode = $this->formatRangeTanggal($tglMulai, $tglSelesai);
            $this->Cell(0, 0, "Tanggal $periode", 0, 1, 'C');

            $tglSkrg = $this->getTanggalIndonesia(date('Y-m-d'));
            $this->Cell(0, 40, "Paiton, " . $tglSkrg, 0, 1, 'C');
            $this->SetFont('Arial', 'B', 13);
            $this->Cell(0, 30, $namaTTD, 0, 1, 'C');
            $this->SetY($this->GetY() - 10);
            $this->Cell(0, 10, $jabatanTTD, 0, 1, 'C');
        }

        function getTanggalIndonesia($tanggal)
        {
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',    4 => 'April',
                5 => 'Mei',     6 => 'Juni',     7 => 'Juli',      8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];
            $ts = strtotime($tanggal);
            return date('d', $ts) . ' ' . $namaBulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
        }

        function formatRangeTanggal($tglMulai, $tglSelesai)
        {
            $namaBulan = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',    4 => 'April',
                5 => 'Mei',     6 => 'Juni',     7 => 'Juli',      8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            $ts1 = strtotime($tglMulai);
            $ts2 = strtotime($tglSelesai);

            $d1 = (int) date('d', $ts1);
            $m1 = (int) date('n', $ts1);
            $y1 = (int) date('Y', $ts1);

            $d2 = (int) date('d', $ts2);
            $m2 = (int) date('n', $ts2);
            $y2 = (int) date('Y', $ts2);

            if ($y1 === $y2) {
                if ($m1 === $m2) {
                    if ($d1 === $d2) {
                        return "$d1 " . $namaBulan[$m1] . " $y1";
                    }
                    return "$d1 - $d2 " . $namaBulan[$m1] . " $y1";
                } else {
                    return "$d1 " . $namaBulan[$m1] . " - $d2 " . $namaBulan[$m2] . " $y1";
                }
            } else {
                return "$d1 " . $namaBulan[$m1] . " $y1 - $d2 " . $namaBulan[$m2] . " $y2";
            }
        }
    }
}

class PrintSertifikatController extends Controller
{
    public function cetak(Request $request): Response
    {
        $laporan_id = (int) $request->query('laporan_id', 0);
        $username   = trim((string) $request->query('username', ''));

        if (!$laporan_id || $username === '') {
            return response('Parameter tidak valid.', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $peserta = DB::table('peserta as p')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->join('laporan_magang as lm', function ($join) use ($laporan_id) {
                $join->on('lm.username', '=', 'p.username')
                    ->where('lm.id', '=', $laporan_id);
            })
            ->where('p.username', $username)
            ->limit(1)
            ->get([
                'p.*',
                'b.bidang as nama_bidang',
                'lm.id as laporan_id',
                'lm.status as status_laporan',
                'lm.sdm_status',
                'lm.manager_status',
            ])
            ->first();

        if (!$peserta) {
            return response('Data peserta tidak ditemukan.', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $bolehCetak = ($peserta->status_laporan === 'Disetujui') ||
            ($peserta->status_laporan === 'Menunggu Manager' && $peserta->sdm_status === 'Disetujui');

        if (!$bolehCetak) {
            return response(
                '<script>alert("Laporan belum Disetujui!"); window.close();</script>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        // ============================================================
        // NOMOR SURAT
        // ============================================================
        $sertifExist = DB::table('sertifikat_magang')
            ->where('username', $username)
            ->limit(1)
            ->first(['id', 'nomor_surat']);

        if ($sertifExist && $sertifExist->nomor_surat) {
            $nomorSuratBerikutnya = $this->adjustNomorSurat($sertifExist->nomor_surat);

            DB::table('sertifikat_magang')->where('username', $username)->update([
                'laporan_id'  => $laporan_id,
                'nomor_surat' => $nomorSuratBerikutnya,
                'status'      => 'Belum Upload',
                'tgl_cetak'   => DB::raw('NOW()'),
            ]);
        } else {
            $nomorSuratTerakhir   = $this->getNomorSuratTerakhir();
            $nomorSuratBerikutnya = $this->getNomorSuratBerikutnya($nomorSuratTerakhir);

            try {
                DB::table('nomor')->where('id', 1)->update(['nomor_surat' => $nomorSuratBerikutnya]);
            } catch (\Exception $e) {
                DB::table('nomor')->insert(['id' => 1, 'nomor_surat' => $nomorSuratBerikutnya]);
            }

            if ($sertifExist) {
                DB::table('sertifikat_magang')->where('username', $username)->update([
                    'laporan_id'  => $laporan_id,
                    'nomor_surat' => $nomorSuratBerikutnya,
                    'status'      => 'Belum Upload',
                    'tgl_cetak'   => DB::raw('NOW()'),
                ]);
            } else {
                DB::table('sertifikat_magang')->insert([
                    'username'    => $username,
                    'laporan_id'  => $laporan_id,
                    'nomor_surat' => $nomorSuratBerikutnya,
                    'status'      => 'Belum Upload',
                    'tgl_cetak'   => DB::raw('NOW()'),
                ]);
            }
        }

        // ============================================================
        // KONFIGURASI TTD - BACA DARI DATABASE
        // ============================================================
        $ttd_cfg           = DB::table('pengaturan_ttd')->where('id', 1)->first();
        $namaTandaTangan   = $ttd_cfg->nama_ttd ?? 'Sukarno';
        $jabatan           = $ttd_cfg->jabatan_ttd ?? 'Manager Business Support';

        // Cari background sertifikat otomatis (kandidat sama dengan legacy,
        // 'sertifikat.jpg' relative ke legacy/sdm dan root web)
        $bg_paths = [
            base_path('legacy/sdm/sertifikat.jpg'),
            public_path('sertifikat.jpg'),
            base_path('legacy/sdm/images/sertifikat.jpg'),
            base_path('legacy/images/sertifikat.jpg'),
            base_path('legacy/sdm/fpdf/sertifikat.jpg'),
        ];
        $bg_sertifikat = '';
        foreach ($bg_paths as $p) {
            if (file_exists($p)) {
                $bg_sertifikat = $p;
                break;
            }
        }

        // ============================================================
        // OUTPUT PDF
        // ============================================================
        $tglMulai   = $peserta->tgl_masuk;
        $tglSelesai = $peserta->tgl_keluar;
        $namaBidang = $peserta->nama_bidang ?? '';
        $namaUnit   = $peserta->unit ?? '';

        $pdf          = new PDF('L', 'mm', 'A4');
        $pdf->bgImage = $bg_sertifikat;
        $pdf->AddPage();
        $pdf->BuatSertifikat(
            $nomorSuratBerikutnya,
            $peserta->nama,
            $peserta->asal_sekolah,
            $namaBidang,
            $namaUnit,
            $tglMulai,
            $tglSelesai,
            $namaTandaTangan,
            $jabatan
        );

        $namaFile = 'Sertifikat_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $peserta->nama) . '.pdf';
        $pdfContent = $pdf->Output('S');

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $namaFile . '"',
        ]);
    }

    private function getNomorSuratTerakhir(): string
    {
        $row = DB::table('nomor')->where('id', 1)->first();

        if ($row) {
            return $row->nomor_surat;
        }

        $tahun = date('Y');
        $init  = '000/PKL/I/PTN/' . $tahun;
        DB::table('nomor')->insertOrIgnore(['id' => 1, 'nomor_surat' => $init]);

        return $init;
    }

    private function getNomorSuratBerikutnya(string $nomorSuratTerakhir): string
    {
        $bln_arr       = [1 => "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
        $bln           = $bln_arr[(int) date('n')];
        $tahunSekarang = (int) date('Y');
        $pecah         = explode('/', $nomorSuratTerakhir);
        $nomorTerakhir = (int) $pecah[0];
        $tahunTerakhir = isset($pecah[4]) ? (int) $pecah[4] : 0;

        if ($tahunTerakhir != $tahunSekarang) {
            return "001/PKL/{$bln}/PTN/{$tahunSekarang}";
        }

        $nomorTerakhir++;

        return sprintf("%03d", $nomorTerakhir) . "/PKL/{$bln}/PTN/{$tahunSekarang}";
    }

    private function adjustNomorSurat($nomorSurat, $tanggal = null): string
    {
        if (!$tanggal) {
            $tanggal = date('Y-m-d');
        }
        $ts = strtotime($tanggal);
        $m  = (int) date('n', $ts);
        $y  = date('Y', $ts);

        $bln_arr = [1 => "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
        $bln = $bln_arr[$m];

        $pecah = explode('/', $nomorSurat);
        if (count($pecah) >= 5) {
            $pecah[2] = $bln;
            $pecah[4] = $y;
            return implode('/', $pecah);
        }

        return $nomorSurat;
    }
}

<?php

namespace App\Services;

use App\Http\Controllers\Manager\SertifikatPDF;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\DB;

class SertifikatDigitalService
{
    public function generateToken(int $len = 40): string
    {
        return bin2hex(random_bytes($len / 2));
    }

    public function buildVerifyUrl(string $qrToken): string
    {
        $protocol = request()->secure() ? 'https' : 'http';
        $host = request()->getHost();
        $basePath = rtrim(dirname(dirname(request()->getScriptName())), '/');

        return "$protocol://$host$basePath/ceklogin/verifikasi.php?token=$qrToken";
    }

    public function generateQrImage(string $data, string $outputPath): void
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => 'M',
            'scale' => 6,
            'outputBase64' => false,
        ]);
        $qrcode = new QRCode($options);
        $qrcode->render($data, $outputPath);
    }

    /**
     * Generate the digital certificate PDF for a peserta.
     * Uses the given (already persisted) qr_token & nomor_surat so an
     * existing QR code stays valid when the certificate is regenerated.
     *
     * @return string|null saved file name (relative to uploads/sertifikat) or null on failure
     */
    public function generate(string $username, int $laporan_id, string $qrToken, string $nomorSurat): ?string
    {
        $peserta = DB::selectOne("
            SELECT p.*, b.bidang AS nama_bidang
            FROM peserta p
            LEFT JOIN bidang b ON p.bidang_id = b.id
            JOIN laporan_magang lm ON lm.username = p.username AND lm.id = ?
            WHERE p.username = ?
            LIMIT 1
        ", [$laporan_id, $username]);

        if (! $peserta) {
            return null;
        }

        $ttd_cfg = DB::selectOne("SELECT * FROM pengaturan_ttd WHERE id = 1");
        $namaTTD = $ttd_cfg->nama_ttd ?? 'Sukarno';
        $jabatan = $ttd_cfg->jabatan_ttd ?? 'Manager Business Support';

        $bg_paths = [
            public_path('sertifikat.jpg'),
            base_path('legacy/sdm/fpdf/sertifikat.jpg'),
            base_path('legacy/sertifikat.jpg'),
            public_path('assets/images/sertifikat.jpg'),
        ];
        $bg_sertifikat = '';
        foreach ($bg_paths as $p) {
            if (file_exists($p)) {
                $bg_sertifikat = $p;
                break;
            }
        }

        $fpdf_paths = [
            base_path('legacy/sdm/fpdf/fpdf.php'),
            base_path('legacy/fpdf/fpdf.php'),
            base_path('legacy/fpdf17/fpdf.php'),
        ];
        $fpdf_found = '';
        foreach ($fpdf_paths as $p) {
            if (file_exists($p)) {
                $fpdf_found = $p;
                break;
            }
        }
        if (! $fpdf_found) {
            return null;
        }

        $qrDir = public_path('uploads/temp/');
        if (! is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        $qrFile = $qrDir . 'qr_' . $qrToken . '.png';
        $this->generateQrImage($this->buildVerifyUrl($qrToken), $qrFile);

        require_once $fpdf_found;
        require_once app_path('Http/Controllers/Manager/SertifikatPDF.php');

        $pdf = new SertifikatPDF('L', 'mm', 'A4');
        $pdf->bgImage = $bg_sertifikat;
        $pdf->AddPage();
        $pdf->BuatSertifikat(
            $nomorSurat,
            $peserta->nama,
            $peserta->asal_sekolah,
            $peserta->nama_bidang ?? '',
            $peserta->unit ?? '',
            $peserta->tgl_masuk,
            $peserta->tgl_keluar,
            $namaTTD,
            $jabatan,
            $qrFile
        );

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $peserta->nama);
        $fileName = 'digital_' . $safeName . '_' . time() . '.pdf';
        $dir = public_path('uploads/sertifikat');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $pdf->Output('F', $dir . '/' . $fileName);

        if (file_exists($qrFile)) {
            @unlink($qrFile);
        }

        return $fileName;
    }
}

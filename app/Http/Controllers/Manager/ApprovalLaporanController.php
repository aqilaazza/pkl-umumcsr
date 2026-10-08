<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalLaporanController extends Controller
{
    private function generateToken($len = 40)
    {
        return bin2hex(random_bytes($len / 2));
    }

    private function generateQrImage($data, $outputPath)
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

    private function getNomorSuratTerakhir()
    {
        $res = DB::selectOne("SELECT nomor_surat FROM nomor WHERE id=1");
        if ($res && !empty($res->nomor_surat)) {
            return $res->nomor_surat;
        }
        $tahun = date('Y');
        $init = '000/PKL/I/PTN/' . $tahun;
        DB::statement("INSERT INTO nomor (id, nomor_surat) VALUES (1, ?) ON DUPLICATE KEY UPDATE id=id", [$init]);
        return $init;
    }

    private function getNomorSuratBerikutnya($nomorSuratTerakhir)
    {
        $bln_arr = [1 => "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
        $bln = $bln_arr[(int) date('n')];
        $tahunSekarang = (int) date('Y');
        $pecah = explode('/', $nomorSuratTerakhir);
        $nomorTerakhir = isset($pecah[0]) ? (int) $pecah[0] : 0;
        $tahunTerakhir = isset($pecah[4]) ? (int) $pecah[4] : 0;
        if ($tahunTerakhir != $tahunSekarang) {
            return "001/PKL/{$bln}/PTN/{$tahunSekarang}";
        } else {
            $nomorTerakhir++;
            return sprintf("%03d", $nomorTerakhir) . "/PKL/{$bln}/PTN/{$tahunSekarang}";
        }
    }

    private function adjustNomorSurat($nomorSurat, $tanggal = null)
    {
        if (!$tanggal) {
            $tanggal = date('Y-m-d');
        }
        $ts = strtotime($tanggal);
        $m = (int) date('n', $ts);
        $y = date('Y', $ts);
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

    private function generateDigitalCert($username, $laporan_id, $qrImagePath, $nomorSurat)
    {
        $peserta = DB::selectOne("
            SELECT p.*, b.bidang AS nama_bidang
            FROM peserta p
            LEFT JOIN bidang b ON p.bidang_id = b.id
            JOIN laporan_magang lm ON lm.username = p.username AND lm.id = ?
            WHERE p.username = ?
            LIMIT 1
        ", [$laporan_id, $username]);
        if (!$peserta) {
            return null;
        }
        $ttd_cfg = DB::selectOne("SELECT * FROM pengaturan_ttd WHERE id = 1");
        $namaTTD = $ttd_cfg->nama_ttd ?? 'Sukarno';
        $jabatan = $ttd_cfg->jabatan_ttd ?? 'Manager Business Support';
        $bg_paths = [public_path('sertifikat.jpg'), base_path('legacy/sdm/fpdf/sertifikat.jpg'), base_path('legacy/sertifikat.jpg'), public_path('assets/images/sertifikat.jpg')];
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
        if (!$fpdf_found) {
            return null;
        }
        require $fpdf_found;
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
            $qrImagePath
        );
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $peserta->nama);
        $fileName = 'digital_' . $safeName . '_' . time() . '.pdf';
        $dir = public_path('uploads/sertifikat');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filePath = $dir . '/' . $fileName;
        $pdf->Output('F', $filePath);
        return $fileName;
    }

    public function index(Request $request)
    {
        $search = $request->input('q', '');
        $per_page = 15;
        $page = $request->input('p') ? max(1, (int) $request->input('p')) : 1;
        $offset = ($page - 1) * $per_page;
        $where = "WHERE lm.sdm_status = 'Disetujui' AND lm.manager_status = 'Menunggu'";
        $params = [];
        if ($search) {
            $where .= " AND (p.nama LIKE ? OR lm.username LIKE ? OR p.asal_sekolah LIKE ?)";
            $searchLike = '%' . $search . '%';
            $params = array_merge($params, [$searchLike, $searchLike, $searchLike]);
        }
        $total_row = DB::selectOne("
            SELECT COUNT(*) as total FROM laporan_magang lm
            JOIN peserta p ON lm.username = p.username $where
        ", $params);
        $total = (int) ($total_row->total ?? 0);
        $total_page = $total > 0 ? ceil($total / $per_page) : 1;
        $data = DB::select("
            SELECT lm.*, p.nama, p.asal_sekolah, p.jurusan, p.status_peserta,
                   p.tgl_masuk, p.tgl_keluar,
                   b.bidang AS nama_bidang,
                   u.nama AS sdm_reviewer_nama
            FROM laporan_magang lm
            JOIN peserta p ON lm.username = p.username
            LEFT JOIN bidang b ON p.bidang_id = b.id
            LEFT JOIN users u ON lm.sdm_reviewed_by = u.username
            $where
            ORDER BY lm.sdm_tgl_review ASC
            LIMIT $per_page OFFSET $offset
        ", $params);
        $sum = DB::selectOne("
            SELECT
                COUNT(*) as total,
                SUM(sdm_status='Disetujui' AND manager_status='Menunggu') as menunggu,
                SUM(sdm_status='Disetujui' AND manager_status='Disetujui') as disetujui,
                SUM(manager_status='Ditolak') as ditolak
            FROM laporan_magang
        ");
        $pending_count = (int) DB::selectOne("
            SELECT COUNT(*) n FROM laporan_magang WHERE sdm_status='Disetujui' AND manager_status='Menunggu'
        ")->n;
        return view('layouts.manager', [
            'pageView' => 'manager.pages.approval_laporan',
            'need_datatables' => true,
            'pending_count' => $pending_count,
            'data' => $data,
            'sum' => $sum,
            'total' => $total,
            'total_page' => $total_page,
            'page' => $page,
            'per_page' => $per_page,
            'offset' => $offset,
            'search' => $search,
        ]);
    }

    public function approve(Request $request)
    {
        $laporan_id = (int) $request->input('laporan_id');
        $username = trim($request->input('username', ''));
        $reviewer = auth()->user()->username ?? null;
        $cek = DB::selectOne("
            SELECT id, sdm_status, manager_status FROM laporan_magang WHERE id = ? AND username = ?
        ", [$laporan_id, $username]);
        if (!$cek || $cek->sdm_status !== 'Disetujui' || $cek->manager_status !== 'Menunggu') {
            return redirect()->back()->with('error', 'Laporan tidak valid atau sudah diproses.');
        }
        $qrToken = $this->generateToken();
        $protocol = request()->secure() ? 'https' : 'http';
        $host = request()->getHost();
        $basePath = rtrim(dirname(dirname(request()->getScriptName())), '/');
        $verifyUrl = "$protocol://$host$basePath/ceklogin/verifikasi.php?token=$qrToken";
        $qrDir = public_path('uploads/temp/');
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        $qrFile = $qrDir . 'qr_' . $qrToken . '.png';
        $this->generateQrImage($verifyUrl, $qrFile);
        $sertifNo = DB::selectOne("
            SELECT id, nomor_surat FROM sertifikat_magang WHERE username = ? AND laporan_id = ? LIMIT 1
        ", [$username, $laporan_id]);
        if ($sertifNo && !empty($sertifNo->nomor_surat)) {
            $nomorSurat = $this->adjustNomorSurat($sertifNo->nomor_surat);
        } else {
            $lastNo = $this->getNomorSuratTerakhir();
            $nomorSurat = $this->getNomorSuratBerikutnya($lastNo);
            DB::update("UPDATE nomor SET nomor_surat = ? WHERE id = 1", [$nomorSurat]);
        }
        $digitalFile = $this->generateDigitalCert($username, $laporan_id, $qrFile, $nomorSurat);
        if (file_exists($qrFile)) {
            @unlink($qrFile);
        }
        if (!$digitalFile) {
            return redirect()->back()->with('error', 'Gagal generate sertifikat digital.');
        }
        DB::update("
            UPDATE laporan_magang SET
                manager_status     = 'Disetujui',
                manager_reviewed_by = ?,
                manager_tgl_review  = NOW(),
                status             = 'Disetujui'
            WHERE id = ?
        ", [$reviewer, $laporan_id]);
        $exist = DB::selectOne("
            SELECT id FROM sertifikat_magang WHERE username = ? AND laporan_id = ? LIMIT 1
        ", [$username, $laporan_id]);
        if ($exist) {
            DB::update("
                UPDATE sertifikat_magang SET
                    qr_token          = ?,
                    digital_file      = ?,
                    nomor_surat       = ?,
                    status            = 'Digital',
                    generated_at      = NOW(),
                    manager_approved_at = NOW()
                WHERE id = ?
            ", [$qrToken, $digitalFile, $nomorSurat, $exist->id]);
        } else {
            DB::insert("
                INSERT INTO sertifikat_magang
                    (laporan_id, username, nomor_surat, qr_token, digital_file, status, generated_at, manager_approved_at)
                VALUES (?, ?, ?, ?, ?, 'Digital', NOW(), NOW())
            ", [$laporan_id, $username, $nomorSurat, $qrToken, $digitalFile]);
        }
        return redirect()->back()->with('success', 'Laporan disetujui. Sertifikat digital dengan QR Code berhasil diterbitkan.');
    }

    public function reject(Request $request)
    {
        $laporan_id = (int) $request->input('laporan_id');
        $keterangan_tolak = trim($request->input('keterangan_tolak', ''));
        $reviewer = auth()->user()->username ?? null;
        if (empty($keterangan_tolak)) {
            return redirect()->back()->with('error', 'Keterangan penolakan wajib diisi.');
        }
        DB::update("
            UPDATE laporan_magang SET
                manager_status        = 'Ditolak',
                manager_reviewed_by   = ?,
                manager_tgl_review    = NOW(),
                manager_keterangan_tolak = ?,
                status                = 'Ditolak'
            WHERE id = ?
        ", [$reviewer, $keterangan_tolak, $laporan_id]);
        return redirect()->back()->with('success', 'Laporan berhasil ditolak.');
    }
}

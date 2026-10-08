<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CetakSertifikatController extends Controller
{
    public function index(): View
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.cetak_sertifikat',
            'need_datatables' => true,
            'need_tinymce' => true,
            'need_fancyupload' => true,
        ]);
    }

    public function uploadScan(Request $request): RedirectResponse
    {
        $sertif_id = (int) $request->input('sertifikat_id');
        $uploader   = auth()->user()->username ?? '';

        $errors = [];

        if (!$request->hasFile('file_sertifikat') || !$request->file('file_sertifikat')->isValid()) {
            $errors[] = 'Pilih file sertifikat terlebih dahulu.';
        } else {
            $file      = $request->file('file_sertifikat');
            $file_name = $file->getClientOriginalName();
            $file_size = $file->getSize();
            $file_ext  = strtolower($file->getClientOriginalExtension());

            $mime_type = $file->getMimeType();

            $allowed_ext  = ['pdf', 'jpg', 'jpeg', 'png'];
            $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];

            if (!in_array($file_ext, $allowed_ext) || !in_array($mime_type, $allowed_mime)) {
                $errors[] = 'File harus berformat <strong>PDF, JPG, atau PNG</strong>.';
            }

            if ($file_size > 10 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal <strong>10 MB</strong>.';
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->with('error', implode('<br>', $errors));
        }

        $sertif_row = DB::table('sertifikat_magang')->where('id', $sertif_id)->first();

        if (!$sertif_row) {
            return redirect()->back()->with('error', 'Data sertifikat tidak ditemukan. Pastikan sudah mencetak sertifikat terlebih dahulu.');
        }

        $dir = public_path('uploads/sertifikat');

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $saved_name = 'sertif_' . $sertif_row->username . '_' . time() . '.' . $file_ext;

        try {
            $file->move($dir, $saved_name);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan file. Periksa permission folder uploads/sertifikat/.');
        }

        try {
            DB::table('sertifikat_magang')->where('id', $sertif_id)->update([
                'file_sertifikat'  => $saved_name,
                'nama_file_scan'   => $file_name,
                'ukuran_file_scan' => $file_size,
                'status'           => 'Sudah Upload',
                'tgl_upload_scan'  => DB::raw('NOW()'),
                'uploaded_by'      => $uploader,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update database: ' . $e->getMessage());
        }

        return redirect('sdm/cetak-sertifikat')->with(
            'success',
            'Sertifikat scan berhasil <strong>diupload</strong>. Peserta dapat mengunduhnya sekarang.'
        );
    }
}

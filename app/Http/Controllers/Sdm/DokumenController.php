<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DokumenController extends Controller
{
    private const UPLOAD_DIR = 'uploads/dokumen';

    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    private const MAX_SIZE = 15 * 1024 * 1024;

    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.dokumen',
            'need_datatables' => true,
        ]);
    }

    public function store(Request $request)
    {
        $nama = trim((string) $request->input('nama_dokumen'));

        if ($nama === '') {
            return redirect()->back()->with('error', 'Nama dokumen tidak boleh kosong.');
        }

        if (!$request->hasFile('file_dokumen') || !$request->file('file_dokumen')->isValid()) {
            return redirect()->back()->with('error', 'Silakan pilih file dokumen untuk diupload.');
        }

        $file = $request->file('file_dokumen');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, self::ALLOWED_EXT)) {
            return redirect()->back()->with('error', 'Format file tidak didukung. Hany file <strong>PDF, JPG, JPEG, PNG, WEBP</strong> yang diperbolehkan.');
        }

        if ($file->getSize() > self::MAX_SIZE) {
            return redirect()->back()->with('error', 'Ukuran file terlalu besar. Maksimal 15 MB.');
        }

        $dir = public_path(self::UPLOAD_DIR);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = 'doc_' . date('YmdHis') . '_' . rand(100, 999) . '.' . $ext;

        try {
            $file->move($dir, $filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengupload file dokumen ke server.');
        }

        DB::table('dokumen')->insert([
            'nama_dokumen' => $nama,
            'file_dokumen' => $filename,
            'tipe_file'    => $ext,
        ]);

        return redirect('sdm/dokumen')->with('success', 'Dokumen <strong>' . e($nama) . '</strong> berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $id       = (int) $id;
        $nama     = trim((string) $request->input('nama_dokumen'));
        $old_file = (string) $request->input('old_file');

        if ($nama === '') {
            return redirect()->back()->withInput()->with('error', 'Nama dokumen tidak boleh kosong.');
        }

        if ($request->hasFile('file_dokumen') && $request->file('file_dokumen')->isValid()) {
            $file = $request->file('file_dokumen');
            $ext  = strtolower($file->getClientOriginalExtension());

            if (!in_array($ext, self::ALLOWED_EXT)) {
                return redirect()->back()->withInput()->with('error', 'Format file tidak didukung. Hanya file <strong>PDF, JPG, JPEG, PNG, WEBP</strong> yang diperbolehkan.');
            }

            if ($file->getSize() > self::MAX_SIZE) {
                return redirect()->back()->withInput()->with('error', 'Ukuran file terlalu besar. Maksimal 15 MB.');
            }

            $dir = public_path(self::UPLOAD_DIR);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $filename = 'doc_' . date('YmdHis') . '_' . rand(100, 999) . '.' . $ext;

            try {
                $file->move($dir, $filename);
            } catch (\Exception $e) {
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload file dokumen baru.');
            }

            if ($old_file && file_exists($dir . '/' . $old_file)) {
                @unlink($dir . '/' . $old_file);
            }

            DB::table('dokumen')->where('id', $id)->update([
                'nama_dokumen' => $nama,
                'file_dokumen' => $filename,
                'tipe_file'    => $ext,
            ]);

            return redirect('sdm/dokumen')->with('success', 'Dokumen dan file berhasil diperbarui.');
        }

        DB::table('dokumen')->where('id', $id)->update(['nama_dokumen' => $nama]);

        return redirect('sdm/dokumen')->with('success', 'Nama dokumen berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $id  = (int) $id;
        $row = DB::table('dokumen')->find($id);

        if ($row) {
            $file_path = public_path(self::UPLOAD_DIR . '/' . $row->file_dokumen);
            if (file_exists($file_path)) {
                @unlink($file_path);
            }

            DB::table('dokumen')->where('id', $id)->delete();

            return redirect('sdm/dokumen')->with('success', 'Dokumen <strong>' . e($row->nama_dokumen) . '</strong> berhasil dihapus.');
        }

        return redirect('sdm/dokumen');
    }
}

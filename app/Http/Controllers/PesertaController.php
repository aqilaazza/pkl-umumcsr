<?php

namespace App\Http\Controllers;

use App\Models\AbsensiPeserta;
use App\Models\Dokumen;
use App\Models\HariLibur;
use App\Models\LaporanMagang;
use App\Models\LiburPekan;
use App\Models\PengaturanAbsensi;
use App\Models\Peserta;
use App\Models\SertifikatMagang;
use App\Models\User;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PesertaController extends Controller
{
    protected function username(): string
    {
        return auth()->user()->username;
    }

    protected function liburKeterangan(string $tanggal): ?string
    {
        $libur = HariLibur::where('tanggal', $tanggal)->first();
        if ($libur) {
            return $libur->keterangan;
        }

        $day_idx = (int) date('w', strtotime($tanggal));
        $libur_pekan = LiburPekan::where('hari_index', $day_idx)->first();
        if ($libur_pekan) {
            return 'Libur Pekan ('.$libur_pekan->nama_hari.')';
        }

        return null;
    }

    protected function getDistanceMeters($lat1, $lon1, $lat2, $lon2): float
    {
        $earth_radius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earth_radius * $c;
    }

    public function home(): View
    {
        $u = $this->username();
        $peserta = Peserta::with('bidang')->where('username', $u)->first();

        $today = date('Y-m-d');
        $absensi_today = AbsensiPeserta::where('username', $u)->where('tanggal', $today)->first();
        $is_libur = $this->liburKeterangan($today);

        $bulan_ini = date('Y-m');
        $stat_hadir = AbsensiPeserta::where('username', $u)->whereRaw("DATE_FORMAT(tanggal,'%Y-%m') = ?", [$bulan_ini])->where('status', 'Hadir')->count();
        $stat_izin = AbsensiPeserta::where('username', $u)->whereRaw("DATE_FORMAT(tanggal,'%Y-%m') = ?", [$bulan_ini])->where('status', 'Izin')->count();
        $stat_sakit = AbsensiPeserta::where('username', $u)->whereRaw("DATE_FORMAT(tanggal,'%Y-%m') = ?", [$bulan_ini])->where('status', 'Sakit')->count();

        $laporan_aktif = LaporanMagang::where('username', $u)->orderByDesc('id')->first();
        $jumlah_laporan = LaporanMagang::where('username', $u)->count();
        $sertifikat = SertifikatMagang::where('username', $u)->orderByDesc('id')->first();

        return view('peserta.home', compact(
            'peserta',
            'absensi_today',
            'is_libur',
            'stat_hadir',
            'stat_izin',
            'stat_sakit',
            'laporan_aktif',
            'jumlah_laporan',
            'sertifikat'
        ))->with('active_nav', 'home');
    }

    public function absensi(Request $request): View
    {
        $u = $this->username();
        $today = date('Y-m-d');

        $absensi_today = AbsensiPeserta::where('username', $u)->where('tanggal', $today)->first();
        $cfg = PengaturanAbsensi::where('id', 1)->first();
        $office_lat = floatval($cfg->office_lat ?? 0);
        $office_lng = floatval($cfg->office_lng ?? 0);
        $radius_limit = intval($cfg->radius_meter ?? 100);

        $is_libur = $this->liburKeterangan($today);

        $bulan = $request->query('bln', date('Y-m'));
        $stat = AbsensiPeserta::where('username', $u)
            ->whereRaw("DATE_FORMAT(tanggal,'%Y-%m') = ?", [$bulan])
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $riwayat = AbsensiPeserta::where('username', $u)->orderByDesc('tanggal')->limit(10)->get();

        return view('peserta.absensi', compact(
            'absensi_today',
            'office_lat',
            'office_lng',
            'radius_limit',
            'is_libur',
            'stat',
            'riwayat',
            'bulan'
        ))->with('active_nav', 'absensi');
    }

    public function absensiProses(Request $request): JsonResponse
    {
        $u = $this->username();
        $today = date('Y-m-d');
        $action = $request->input('action', $request->query('action', ''));

        switch ($action) {
            case 'clock_in':
                $lat = floatval($request->input('lat', 0));
                $lng = floatval($request->input('lng', 0));

                $cfg = PengaturanAbsensi::where('id', 1)->first();
                if ($cfg) {
                    $distance = $this->getDistanceMeters($lat, $lng, floatval($cfg->office_lat), floatval($cfg->office_lng));
                    $radius_limit = intval($cfg->radius_meter);
                    if ($distance > $radius_limit) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal: Lokasi Anda terlalu jauh ('.round($distance).' meter dari kantor). Batas radius: '.$radius_limit.' meter.',
                        ]);
                    }
                }

                $libur = $this->liburKeterangan($today);
                if ($libur) {
                    return response()->json(['success' => false, 'message' => 'Hari ini libur: '.$libur]);
                }

                $existing = AbsensiPeserta::where('username', $u)->where('tanggal', $today)->first();
                if ($existing) {
                    return response()->json(['success' => false, 'message' => 'Anda sudah absen hari ini']);
                }

                $jam = date('H:i:s');
                AbsensiPeserta::create([
                    'username' => $u,
                    'tanggal' => $today,
                    'jam_masuk' => $jam,
                    'status' => 'Hadir',
                    'lat_masuk' => $lat,
                    'lng_masuk' => $lng,
                ]);

                return response()->json(['success' => true, 'message' => 'Absen Masuk berhasil', 'jam' => substr($jam, 0, 5)]);

            case 'clock_out':
                $lat = floatval($request->input('lat', 0));
                $lng = floatval($request->input('lng', 0));

                $cfg = PengaturanAbsensi::where('id', 1)->first();
                if ($cfg) {
                    $distance = $this->getDistanceMeters($lat, $lng, floatval($cfg->office_lat), floatval($cfg->office_lng));
                    $radius_limit = intval($cfg->radius_meter);
                    if ($distance > $radius_limit) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal: Lokasi Anda terlalu jauh ('.round($distance).' meter dari kantor). Batas radius: '.$radius_limit.' meter.',
                        ]);
                    }
                }

                $existing = AbsensiPeserta::where('username', $u)->where('tanggal', $today)->where('status', 'Hadir')->first();
                if (! $existing) {
                    return response()->json(['success' => false, 'message' => 'Anda belum absen masuk hari ini']);
                }
                if ($existing->jam_keluar) {
                    return response()->json(['success' => false, 'message' => 'Anda sudah absen pulang hari ini']);
                }

                $jam = date('H:i:s');
                $existing->update(['jam_keluar' => $jam, 'lat_keluar' => $lat, 'lng_keluar' => $lng]);

                return response()->json(['success' => true, 'message' => 'Absen Pulang berhasil', 'jam' => substr($jam, 0, 5)]);

            case 'izin_sakit':
                $status = $request->input('status', '');
                if (! in_array($status, ['Izin', 'Sakit'])) {
                    return response()->json(['success' => false, 'message' => 'Status tidak valid']);
                }

                $keterangan = trim($request->input('keterangan', ''));
                if ($keterangan === '') {
                    return response()->json(['success' => false, 'message' => 'Keterangan wajib diisi']);
                }

                $tanggal_mulai = $request->input('tanggal_mulai', $today);
                $tanggal_selesai = $request->input('tanggal_selesai', $today);

                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_selesai)) {
                    return response()->json(['success' => false, 'message' => 'Format tanggal tidak valid']);
                }

                $start = new DateTime($tanggal_mulai);
                $end = new DateTime($tanggal_selesai);

                if ($start > $end) {
                    return response()->json(['success' => false, 'message' => 'Tanggal mulai tidak boleh melebihi tanggal selesai']);
                }

                if ($start->diff($end)->days > 31) {
                    return response()->json(['success' => false, 'message' => 'Maksimal durasi pengajuan izin/sakit adalah 31 hari']);
                }

                $file_surat = null;
                if ($request->hasFile('file_surat') && $request->file('file_surat')->isValid()) {
                    $file = $request->file('file_surat');
                    $ext = strtolower($file->getClientOriginalExtension());
                    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

                    if (! in_array($ext, $allowed)) {
                        return response()->json(['success' => false, 'message' => 'Format file tidak didukung (JPG, PNG, PDF)']);
                    }
                    if ($file->getSize() > 2 * 1024 * 1024) {
                        return response()->json(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
                    }

                    $upload_dir = public_path('uploads/surat_absensi');
                    if (! is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $saved_name = 'surat_'.$u.'_'.date('Ymd_His').'.'.$ext;
                    $file->move($upload_dir, $saved_name);
                    $file_surat = $saved_name;
                }

                $end->modify('+1 day');
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, $end);

                $success_count = 0;
                $fail_count = 0;

                foreach ($period as $date) {
                    $tgl = $date->format('Y-m-d');
                    try {
                        AbsensiPeserta::updateOrCreate(
                            ['username' => $u, 'tanggal' => $tgl],
                            [
                                'status' => $status,
                                'keterangan' => $keterangan,
                                'file_surat' => $file_surat,
                                'approval_status' => 'Pending',
                            ]
                        );
                        $success_count++;
                    } catch (\Throwable $e) {
                        $fail_count++;
                    }
                }

                if ($success_count > 0) {
                    $msg = 'Pengajuan '.$status.' berhasil disimpan untuk '.$success_count.' hari.';
                    if ($fail_count > 0) {
                        $msg .= ' Namun '.$fail_count.' hari gagal.';
                    }

                    return response()->json(['success' => true, 'message' => $msg]);
                }

                return response()->json(['success' => false, 'message' => 'Gagal menyimpan data pengajuan']);

            case 'get_status':
                $data = AbsensiPeserta::where('username', $u)->where('tanggal', $today)->first();
                $libur = $this->liburKeterangan($today);

                return response()->json(['success' => true, 'absensi' => $data, 'libur' => $libur]);

            case 'get_calendar':
                $bulan = $request->query('bulan', date('Y-m'));
                $bulan = preg_replace('/[^0-9\-]/', '', $bulan);

                $absensi = [];
                $rows = AbsensiPeserta::where('username', $u)
                    ->whereRaw("DATE_FORMAT(tanggal,'%Y-%m') = ?", [$bulan])
                    ->get(['tanggal', 'status', 'approval_status']);
                foreach ($rows as $r) {
                    $key = date('Y-m-d', strtotime($r->tanggal));
                    if ($r->approval_status === 'Pending') {
                        $absensi[$key] = $r->status.'_pending';
                    } elseif ($r->approval_status === 'Ditolak') {
                        $absensi[$key] = $r->status.'_ditolak';
                    } else {
                        $absensi[$key] = $r->status;
                    }
                }

                $libur = HariLibur::whereRaw("DATE_FORMAT(tanggal,'%Y-%m') = ?", [$bulan])
                    ->pluck('tanggal')
                    ->map(fn ($t) => date('Y-m-d', strtotime($t)))
                    ->values()
                    ->all();

                $libur_pekan = LiburPekan::pluck('hari_index')->map(fn ($i) => intval($i))->all();

                return response()->json(['success' => true, 'absensi' => $absensi, 'libur' => $libur, 'libur_pekan' => $libur_pekan]);

            case 'get_riwayat':
                $limit = intval($request->query('limit', 10));
                $data = AbsensiPeserta::where('username', $u)->orderByDesc('tanggal')->limit($limit)->get();

                return response()->json(['success' => true, 'data' => $data]);

            default:
                return response()->json(['success' => false, 'message' => 'Action tidak valid']);
        }
    }

    public function laporan(Request $request): View|RedirectResponse
    {
        $u = $this->username();
        $peserta = Peserta::with('bidang')->where('username', $u)->first();

        if (! $peserta) {
            return redirect()->route('peserta.home');
        }

        $riwayat = LaporanMagang::where('username', $u)->orderBy('tgl_upload', 'DESC')->get();
        $laporan_aktif = LaporanMagang::where('username', $u)->orderByDesc('id')->first();
        $boleh_upload = ! $laporan_aktif || $laporan_aktif->status === 'Ditolak';
        $init_tab = $request->query('tab') === 'riwayat' ? 'riwayat' : 'upload';

        return view('peserta.upload_laporan', compact('peserta', 'riwayat', 'laporan_aktif', 'boleh_upload', 'init_tab'))
            ->with('active_nav', 'laporan');
    }

    public function laporanStore(Request $request): RedirectResponse
    {
        $u = $this->username();

        $upload_dir = public_path('uploads/laporan');
        if (! is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $errors = [];

        if (! $request->hasFile('file_laporan') || ! $request->file('file_laporan')->isValid()) {
            $errors[] = 'Pilih file PDF terlebih dahulu.';
        } else {
            $file = $request->file('file_laporan');
            $file_ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType();

            if ($file_ext !== 'pdf' || $mime !== 'application/pdf') {
                $errors[] = 'File harus format PDF.';
            }
            if ($file->getSize() > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 5 MB.';
            }
        }

        if (! empty($errors)) {
            return redirect()->route('peserta.laporan')->with('message', 'error:'.implode(' ', $errors));
        }

        $file = $request->file('file_laporan');

        $cek = LaporanMagang::where('username', $u)
            ->whereIn('status', ['Menunggu', 'Disetujui', 'Menunggu Manager'])
            ->orderByDesc('id')
            ->first();

        if ($cek && ($cek->status === 'Disetujui' || ($cek->sdm_status === 'Disetujui' && $cek->manager_status === 'Disetujui'))) {
            return redirect()->route('peserta.laporan')->with('message', 'warning:Laporan sudah Disetujui. Tidak perlu upload ulang.');
        } elseif ($cek && ($cek->status === 'Menunggu' || $cek->status === 'Menunggu Manager')) {
            return redirect()->route('peserta.laporan')->with('message', 'warning:Laporan sedang dalam proses review.');
        }

        $saved_name = 'laporan_'.$u.'_'.time().'.pdf';
        $file->move($upload_dir, $saved_name);

        LaporanMagang::create([
            'username' => $u,
            'file_laporan' => $saved_name,
            'nama_file' => $file->getClientOriginalName(),
            'ukuran_file' => $file->getSize(),
            'status' => 'Menunggu',
        ]);

        return redirect()->route('peserta.laporan')->with('message', 'success:Laporan berhasil diupload!');
    }

    public function referensi(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $per_page = 10;
        $page_num = max(1, (int) $request->query('p', 1));
        $offset = ($page_num - 1) * $per_page;

        $query = LaporanMagang::query()
            ->from('laporan_magang as lm')
            ->join('peserta as p', 'lm.username', '=', 'p.username')
            ->leftJoin('bidang as b', 'p.bidang_id', '=', 'b.id')
            ->where(function ($q) {
                $q->where('lm.status', 'Disetujui')
                    ->orWhere(function ($q2) {
                        $q2->where('lm.sdm_status', 'Disetujui')->where('lm.manager_status', 'Disetujui');
                    });
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'like', '%'.$search.'%')
                    ->orWhere('p.asal_sekolah', 'like', '%'.$search.'%');
            });
        }

        $total = $query->count();
        $total_page = (int) ceil($total / $per_page);

        $data = $query->select('lm.*', 'p.nama', 'p.asal_sekolah', 'p.jurusan', 'b.bidang as nama_bidang')
            ->orderBy('lm.tgl_upload', 'DESC')
            ->limit($per_page)
            ->offset($offset)
            ->get();

        return view('peserta.referensi_laporan', compact('search', 'total', 'total_page', 'page_num', 'data'))
            ->with('active_nav', 'laporan');
    }

    public function sertifikat(): View
    {
        $u = $this->username();

        $peserta = Peserta::with('bidang')->where('username', $u)->first();
        $laporan_aktif = LaporanMagang::where('username', $u)->orderByDesc('id')->first();
        $sertifikat = SertifikatMagang::where('username', $u)->orderByDesc('id')->first();

        $sdm_ok = $laporan_aktif && $laporan_aktif->sdm_status === 'Disetujui';
        $manager_ok = $laporan_aktif && $laporan_aktif->manager_status === 'Disetujui';
        $is_digital = $sertifikat && $sertifikat->status === 'Digital';
        $is_uploaded = $sertifikat && $sertifikat->status === 'Sudah Upload';

        return view('peserta.sertifikat', compact(
            'peserta',
            'laporan_aktif',
            'sertifikat',
            'sdm_ok',
            'manager_ok',
            'is_digital',
            'is_uploaded'
        ))->with('active_nav', 'sertifikat');
    }

    public function dokumen(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $per_page = 10;
        $page_num = max(1, (int) $request->query('p', 1));
        $offset = ($page_num - 1) * $per_page;

        $query = Dokumen::query();
        if ($search !== '') {
            $query->where('nama_dokumen', 'like', '%'.$search.'%');
        }

        $total = $query->count();
        $total_page = (int) ceil($total / $per_page);

        $data = $query->orderByDesc('id')->limit($per_page)->offset($offset)->get();

        return view('peserta.dokumen', compact('search', 'total', 'total_page', 'page_num', 'data'))
            ->with('active_nav', 'dokumen');
    }

    public function profile(): View
    {
        $u = $this->username();
        $data_profile = User::where('username', $u)->first();
        $peserta = Peserta::with('bidang')->where('username', $u)->first();

        return view('peserta.profile', compact('data_profile', 'peserta'))
            ->with('active_nav', 'profil');
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $u = $this->username();

        if ($request->has('submit_profile')) {
            $nama = trim((string) $request->input('nama'));
            $asal_sekolah = trim((string) $request->input('asal_sekolah'));
            $jurusan = trim((string) $request->input('jurusan'));

            if ($nama === '' || $asal_sekolah === '' || $jurusan === '') {
                return redirect()->route('peserta.profile')->with('message', 'error:Nama, Sekolah/Universitas, dan Jurusan tidak boleh kosong!');
            }

            User::where('username', $u)->update(['nama' => $nama]);
            Peserta::where('username', $u)->update([
                'nama' => $nama,
                'asal_sekolah' => $asal_sekolah,
                'jurusan' => $jurusan,
            ]);

            $user = User::where('username', $u)->first();
            auth()->setUser($user);

            return redirect()->route('peserta.profile')->with('message', 'success:Profil berhasil diperbarui!');
        }

        if ($request->has('submit')) {
            $pw = (string) $request->input('password_baru');
            $cpw = (string) $request->input('konfirmasi_password');

            if ($pw === '' || $cpw === '') {
                return redirect()->route('peserta.profile')->with('message', 'error:Password tidak boleh kosong!');
            }
            if ($pw !== $cpw) {
                return redirect()->route('peserta.profile')->with('message', 'error:Konfirmasi password tidak sesuai!');
            }
            if (strlen($pw) < 5) {
                return redirect()->route('peserta.profile')->with('message', 'error:Password minimal 5 karakter!');
            }

            User::where('username', $u)->update(['password' => Hash::make($pw)]);

            return redirect()->route('peserta.profile')->with('message', 'success:Password berhasil diubah!');
        }

        return redirect()->route('peserta.profile');
    }

    public function kontak(): View
    {
        return view('peserta.kontak')->with('active_nav', 'profil');
    }
}

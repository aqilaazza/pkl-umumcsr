<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerbaikanFiturTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->increments('id');
            $t->string('username', 50)->nullable();
            $t->string('nama', 100)->nullable();
            $t->string('password')->nullable();
            $t->string('role', 20)->default('peserta');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('bidang', function (Blueprint $t) {
            $t->increments('id');
            $t->string('bidang', 50);
        });

        Schema::create('peserta', function (Blueprint $t) {
            $t->increments('id');
            $t->string('username', 50);
            $t->string('nama');
            $t->string('status_peserta')->default('Mahasiswa');
            $t->string('asal_sekolah');
            $t->string('jurusan');
            $t->date('tgl_masuk');
            $t->date('tgl_keluar');
            $t->integer('bidang_id')->nullable();
            $t->string('unit')->default('Unit 9');
            $t->string('status_magang')->default('Aktif');
            $t->text('keterangan')->nullable();
        });

        Schema::create('laporan_magang', function (Blueprint $t) {
            $t->increments('id');
            $t->string('username', 100);
            $t->string('file_laporan');
            $t->string('nama_file');
            $t->integer('ukuran_file');
            $t->string('sdm_status')->default('Menunggu');
            $t->string('sdm_reviewed_by')->nullable();
            $t->dateTime('sdm_tgl_review')->nullable();
            $t->text('sdm_keterangan_tolak')->nullable();
            $t->string('manager_status')->default('Menunggu');
            $t->string('manager_reviewed_by')->nullable();
            $t->dateTime('manager_tgl_review')->nullable();
            $t->text('manager_keterangan_tolak')->nullable();
            $t->string('status')->default('Menunggu');
            $t->text('keterangan_tolak')->nullable();
            $t->dateTime('tgl_upload')->nullable();
            $t->dateTime('tgl_review')->nullable();
            $t->string('reviewed_by')->nullable();
        });

        Schema::create('dokumen', function (Blueprint $t) {
            $t->increments('id');
            $t->string('nama_dokumen');
            $t->string('file_dokumen');
            $t->string('tipe_file', 50)->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    private function makeUser(string $username, string $role): User
    {
        return User::create([
            'username' => $username,
            'nama' => ucfirst($username),
            'password' => bcrypt('secret'),
            'role' => $role,
        ]);
    }

    private function makePeserta(string $username, array $attrs = []): void
    {
        DB::table('peserta')->insert(array_merge([
            'username' => $username,
            'nama' => 'Nama '.$username,
            'asal_sekolah' => 'Sekolah Umum',
            'jurusan' => 'Teknik',
            'tgl_masuk' => '2026-01-01',
            'tgl_keluar' => '2026-03-01',
            'bidang_id' => null,
            'unit' => 'Unit 9',
            'status_magang' => 'Aktif',
            'status_peserta' => 'Mahasiswa',
            'keterangan' => '-',
        ], $attrs));
        $this->makeUser($username, 'peserta');
    }

    public function test_approval_manager_defaults_to_menunggu(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_menunggu');
        $this->makePeserta('p_disetujui');

        DB::table('laporan_magang')->insert([
            'username' => 'p_menunggu', 'file_laporan' => 'a.pdf', 'nama_file' => 'a.pdf', 'ukuran_file' => 1,
            'sdm_status' => 'Disetujui', 'manager_status' => 'Menunggu', 'status' => 'Menunggu Manager',
            'sdm_tgl_review' => now(), 'tgl_upload' => now(),
        ]);
        DB::table('laporan_magang')->insert([
            'username' => 'p_disetujui', 'file_laporan' => 'b.pdf', 'nama_file' => 'b.pdf', 'ukuran_file' => 1,
            'sdm_status' => 'Disetujui', 'manager_status' => 'Disetujui', 'status' => 'Disetujui',
            'sdm_tgl_review' => now(), 'manager_tgl_review' => now(), 'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->get('/sdm/approval-manager');
        $res->assertOk();
        $res->assertSee('Nama p_menunggu');
        $res->assertDontSee('Nama p_disetujui');
        $res->assertSee('value="menunggu" selected', false);
    }

    public function test_approval_manager_other_filters_still_work(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_menunggu');
        $this->makePeserta('p_disetujui');

        DB::table('laporan_magang')->insert([
            'username' => 'p_menunggu', 'file_laporan' => 'a.pdf', 'nama_file' => 'a.pdf', 'ukuran_file' => 1,
            'sdm_status' => 'Disetujui', 'manager_status' => 'Menunggu', 'status' => 'Menunggu Manager',
            'sdm_tgl_review' => now(), 'tgl_upload' => now(),
        ]);
        DB::table('laporan_magang')->insert([
            'username' => 'p_disetujui', 'file_laporan' => 'b.pdf', 'nama_file' => 'b.pdf', 'ukuran_file' => 1,
            'sdm_status' => 'Disetujui', 'manager_status' => 'Disetujui', 'status' => 'Disetujui',
            'sdm_tgl_review' => now(), 'manager_tgl_review' => now(), 'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->get('/sdm/approval-manager?filter=disetujui');
        $res->assertOk();
        $res->assertSee('Nama p_disetujui');
        $res->assertDontSee('Nama p_menunggu');
    }

    public function test_dokumen_store_accepts_docx_and_doc(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');

        foreach (['laporan.docx', 'laporan.doc'] as $name) {
            $res = $this->actingAs($sdm)->post('/sdm/dokumen/store', [
                'nama_dokumen' => 'Dokumen '.$name,
                'file_dokumen' => UploadedFile::fake()->create($name, 50),
            ]);
            $res->assertRedirect();

            $row = DB::table('dokumen')->where('nama_dokumen', 'Dokumen '.$name)->first();
            $this->assertNotNull($row, "Dokumen $name tidak tersimpan");
            $this->assertSame(pathinfo($name, PATHINFO_EXTENSION), strtolower($row->tipe_file));

            $path = public_path('uploads/dokumen/'.$row->file_dokumen);
            $this->assertFileExists($path);
            @unlink($path);
            DB::table('dokumen')->where('id', $row->id)->delete();
        }
    }

    public function test_dokumen_store_rejects_unsupported_extension(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');

        $res = $this->actingAs($sdm)->post('/sdm/dokumen/store', [
            'nama_dokumen' => 'Berbahaya',
            'file_dokumen' => UploadedFile::fake()->create('virus.exe', 10),
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertSame(0, DB::table('dokumen')->count());
    }

    public function test_peserta_search_covers_all_fields(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');

        $bidangId = DB::table('bidang')->insertGetId(['bidang' => 'Har Listrik']);
        $this->makePeserta('10001', [
            'nama' => 'Budi Santoso',
            'asal_sekolah' => 'Universitas Brawijaya',
            'jurusan' => 'Teknik Nuklir',
            'unit' => 'Unit 1-2',
            'status_peserta' => 'Siswa',
            'status_magang' => 'Selesai',
            'keterangan' => 'Peserta teladan',
            'bidang_id' => $bidangId,
        ]);

        $cases = [
            'q=nama' => 'Budi',
            'q=username' => '10001',
            'q=sekolah' => 'Brawijaya',
            'q=jurusan' => 'Nuklir',
            'q=unit' => 'Unit 1-2',
            'q=status_peserta' => 'Siswa',
            'q=status_magang' => 'Selesai',
            'q=keterangan' => 'teladan',
            'q=bidang' => 'Har Listrik',
        ];

        foreach ($cases as $label => $query) {
            $res = $this->actingAs($sdm)->get('/sdm/peserta?'.$query);
            $res->assertOk();
            $res->assertSee('Budi Santoso', false);
        }
    }

    public function test_referensi_search_covers_all_fields(): void
    {
        $bidangId = DB::table('bidang')->insertGetId(['bidang' => 'Rendal Operasi']);
        $this->makePeserta('20001', [
            'nama' => 'Citra Dewi',
            'asal_sekolah' => 'Politeknik Negeri Malang',
            'jurusan' => 'Teknik Instrumentasi',
            'bidang_id' => $bidangId,
        ]);

        $peserta = User::where('username', '20001')->first();

        DB::table('laporan_magang')->insert([
            'username' => '20001',
            'file_laporan' => 'laporan_20001.pdf',
            'nama_file' => 'Laporan Akhir Magang Citra.pdf',
            'ukuran_file' => 2048,
            'sdm_status' => 'Disetujui',
            'manager_status' => 'Disetujui',
            'status' => 'Disetujui',
            'tgl_upload' => now(),
        ]);

        $cases = ['Citra', '20001', 'Negeri Malang', 'Instrumentasi', 'Rendal Operasi', 'Akhir Magang'];

        foreach ($cases as $query) {
            $res = $this->actingAs($peserta)->get('/peserta/laporan/referensi?q='.urlencode($query));
            $res->assertOk();
            $res->assertSee('Citra Dewi', false);
        }
    }

    public function test_peserta_export_handles_extended_search(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('10001', ['jurusan' => 'Teknik Nuklir', 'bidang_id' => null]);

        $res = $this->actingAs($sdm)->get('/sdm/peserta/export?q=Nuklir');
        $res->assertOk();
        $res->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_backup_laporan_page_renders(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_backup');

        DB::table('laporan_magang')->insert([
            'username' => 'p_backup', 'file_laporan' => 'b.pdf', 'nama_file' => 'b.pdf', 'ukuran_file' => 2048,
            'sdm_status' => 'Disetujui', 'manager_status' => 'Disetujui', 'status' => 'Disetujui',
            'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->get('/sdm/backup-laporan');
        $res->assertOk();
        $res->assertSee('Backup Laporan');
        $res->assertSee('Nama p_backup');
    }

    public function test_backup_laporan_download_returns_zip(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_backup');

        DB::table('laporan_magang')->insert([
            'username' => 'p_backup', 'file_laporan' => 'b.pdf', 'nama_file' => 'b.pdf', 'ukuran_file' => 2048,
            'sdm_status' => 'Disetujui', 'manager_status' => 'Disetujui', 'status' => 'Disetujui',
            'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->get('/sdm/backup-laporan/download');
        $res->assertOk();
        $this->assertStringContainsString('application/zip', (string) $res->headers->get('content-type'));
    }

    public function test_hapus_laporan_page_renders(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_hapus');

        DB::table('laporan_magang')->insert([
            'username' => 'p_hapus', 'file_laporan' => 'h.pdf', 'nama_file' => 'h.pdf', 'ukuran_file' => 1024,
            'sdm_status' => 'Menunggu', 'manager_status' => 'Menunggu', 'status' => 'Menunggu',
            'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->get('/sdm/hapus-laporan');
        $res->assertOk();
        $res->assertSee('Hapus Laporan');
        $res->assertSee('Nama p_hapus');
    }

    public function test_hapus_laporan_destroy_deletes_record_and_file(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_hapus');

        $dir = public_path('uploads/laporan');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/hapus_test.pdf', 'dummy');

        $id = DB::table('laporan_magang')->insertGetId([
            'username' => 'p_hapus', 'file_laporan' => 'hapus_test.pdf', 'nama_file' => 'hapus_test.pdf',
            'ukuran_file' => 5, 'sdm_status' => 'Menunggu', 'manager_status' => 'Menunggu',
            'status' => 'Menunggu', 'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->post('/sdm/hapus-laporan/hapus', ['laporan_id' => $id]);
        $res->assertRedirect();
        $this->assertSame(0, DB::table('laporan_magang')->where('id', $id)->count());
        $this->assertFileDoesNotExist($dir.'/hapus_test.pdf');
    }

    public function test_hapus_laporan_bulk_deletes_records(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_hapus');

        $id1 = DB::table('laporan_magang')->insertGetId([
            'username' => 'p_hapus', 'file_laporan' => 'x1.pdf', 'nama_file' => 'x1.pdf', 'ukuran_file' => 5,
            'sdm_status' => 'Menunggu', 'manager_status' => 'Menunggu', 'status' => 'Menunggu', 'tgl_upload' => now(),
        ]);
        $id2 = DB::table('laporan_magang')->insertGetId([
            'username' => 'p_hapus', 'file_laporan' => 'x2.pdf', 'nama_file' => 'x2.pdf', 'ukuran_file' => 5,
            'sdm_status' => 'Menunggu', 'manager_status' => 'Menunggu', 'status' => 'Menunggu', 'tgl_upload' => now(),
        ]);

        $res = $this->actingAs($sdm)->post('/sdm/hapus-laporan/hapus-massal', ['ids' => [$id1, $id2]]);
        $res->assertRedirect();
        $this->assertSame(0, DB::table('laporan_magang')->whereIn('id', [$id1, $id2])->count());
    }

    private function makeLaporan(string $username, array $attrs = []): void
    {
        DB::table('laporan_magang')->insert(array_merge([
            'username' => $username,
            'file_laporan' => 'laporan_'.$username.'.pdf',
            'nama_file' => 'laporan_'.$username.'.pdf',
            'ukuran_file' => 1024,
            'sdm_status' => 'Disetujui',
            'manager_status' => 'Disetujui',
            'status' => 'Disetujui',
            'tgl_upload' => now(),
        ], $attrs));
    }

    public function test_backup_laporan_filter_by_approve_updates_stats(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_filter');

        $this->makeLaporan('p_filter', ['ukuran_file' => 1024, 'status' => 'Disetujui']);
        $this->makeLaporan('p_filter', ['ukuran_file' => 2048, 'status' => 'Disetujui']);
        $this->makeLaporan('p_filter', ['ukuran_file' => 4096, 'status' => 'Menunggu', 'sdm_status' => 'Menunggu', 'manager_status' => 'Menunggu']);

        $res = $this->actingAs($sdm)->get('/sdm/backup-laporan?approve=Disetujui');
        $res->assertOk();
        // 2 file disetujui dengan total 1024 + 2048 = 3072 byte = 3 KB
        $res->assertSee('Jumlah File');
        $res->assertSee('3 KB');
        // Laporan Menunggu (4 KB) tidak boleh ikut terhitung
        $res->assertDontSee('4 KB');
    }

    public function test_backup_laporan_filter_by_periode_updates_stats(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_filter');

        $this->makeLaporan('p_filter', ['ukuran_file' => 1024, 'tgl_upload' => '2026-01-15 10:00:00']);
        $this->makeLaporan('p_filter', ['ukuran_file' => 2048, 'tgl_upload' => '2026-02-15 10:00:00']);

        $res = $this->actingAs($sdm)->get('/sdm/backup-laporan?bulan_dari=2&tahun_dari=2026&bulan_sampai=2&tahun_sampai=2026');
        $res->assertOk();
        // Hanya laporan Februari (2048 byte = 2 KB)
        $res->assertSee('2 KB');
        $res->assertDontSee('1 KB');
    }

    public function test_backup_laporan_total_size_covers_all_filtered_pages(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_filter');

        for ($i = 0; $i < 16; $i++) {
            $this->makeLaporan('p_filter', ['ukuran_file' => 1024]);
        }

        $res = $this->actingAs($sdm)->get('/sdm/backup-laporan?approve=Disetujui');
        $res->assertOk();
        // 16 file x 1024 byte = 16 KB, dihitung dari seluruh hasil filter (bukan hanya 15 di halaman ini)
        $res->assertSee('16 KB');
    }

    public function test_hapus_laporan_filter_by_approve_updates_stats(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_filter');

        $this->makeLaporan('p_filter', ['ukuran_file' => 1536, 'status' => 'Disetujui']);
        $this->makeLaporan('p_filter', ['ukuran_file' => 1024, 'status' => 'Menunggu', 'sdm_status' => 'Menunggu', 'manager_status' => 'Menunggu']);

        $res = $this->actingAs($sdm)->get('/sdm/hapus-laporan?approve=Disetujui');
        $res->assertOk();
        // Hanya 1 file disetujui: 1536 byte = 1.5 KB
        $res->assertSee('1.5 KB');
        $res->assertDontSee('2.5 KB');
    }

    public function test_laporan_filter_without_match_shows_zero_size(): void
    {
        $sdm = $this->makeUser('sdm123', 'sdm');
        $this->makePeserta('p_filter');
        $this->makeLaporan('p_filter', ['ukuran_file' => 1024, 'status' => 'Disetujui']);

        $res = $this->actingAs($sdm)->get('/sdm/backup-laporan?approve=Ditolak');
        $res->assertOk();
        $res->assertSee('0 B');
    }
}

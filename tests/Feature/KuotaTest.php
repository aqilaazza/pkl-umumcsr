<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KuotaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-26 10:00:00');

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
            $t->date('tgl_keluar')->nullable();
            $t->integer('bidang_id')->nullable();
            $t->string('unit')->default('Unit 9');
            $t->string('status_magang')->default('Aktif');
            $t->text('keterangan')->nullable();
        });

        Schema::create('absensi_peserta', function (Blueprint $t) {
            $t->increments('id');
            $t->string('username', 50);
            $t->date('tanggal');
            $t->string('status')->default('Hadir');
            $t->string('approval_status')->default('Disetujui');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_breakdown_bulanan_menghitung_peserta_aktif_dan_menunggu_selama_periode_magang(): void
    {
        $bidangId = DB::table('bidang')->insertGetId(['bidang' => 'Teknik']);

        $this->makePeserta('p_juni', '2026-06-01', '2026-06-30', 'Selesai', $bidangId);
        $this->makePeserta('p_juli', '2026-07-01', '2026-09-30', 'Aktif', $bidangId);
        $this->makePeserta('p_agustus', '2026-08-01', '2026-08-31', 'Menunggu', $bidangId);
        $this->makePeserta('p_september', '2026-09-01', null, 'Aktif', $bidangId);
        $this->makePeserta('p_selesai_juli', '2026-07-01', '2026-09-30', 'Selesai', $bidangId);
        $this->makePeserta('p_januari', '2027-01-01', '2027-03-31', 'Menunggu', $bidangId);

        $sdm = User::create([
            'username' => 'sdm',
            'nama' => 'SDM',
            'password' => bcrypt('secret'),
            'role' => 'sdm',
        ]);

        $res = $this->actingAs($sdm)->get('/sdm/kuota');

        $res->assertOk();

        $kq = $res->viewData('kq');
        $jumlah = collect($kq['bulan'])->pluck('jumlah', 'ym');

        $this->assertFalse($jumlah->has('2026-06'));
        $this->assertSame(1, $jumlah->get('2026-07'));
        $this->assertSame(2, $jumlah->get('2026-08'));
        $this->assertSame(2, $jumlah->get('2026-09'));
        $this->assertSame(1, $jumlah->get('2026-10'));
        $this->assertSame(1, $jumlah->get('2027-01'));
        $this->assertSame(1, $jumlah->get('2027-02'));
        $this->assertSame(1, $jumlah->get('2027-03'));

        $this->assertSame(2, $kq['aktif']);
        $this->assertSame(2, $kq['menunggu']);
        $this->assertSame(4, $kq['total']);

        $juli = collect($kq['detail']['2026-07'])->pluck('nama')->all();
        $this->assertContains('Nama p_juli', $juli);
        $this->assertNotContains('Nama p_selesai_juli', $juli);
        $this->assertNotContains('Nama p_juni', $juli);

        $agustus = collect($kq['detail']['2026-08'])->pluck('nama')->all();
        $this->assertContains('Nama p_juli', $agustus);
        $this->assertContains('Nama p_agustus', $agustus);
        $this->assertNotContains('Nama p_september', $agustus);
    }

    private function makePeserta(string $username, string $masuk, ?string $keluar, string $status, int $bidangId): void
    {
        DB::table('peserta')->insert([
            'username' => $username,
            'nama' => 'Nama '.$username,
            'status_peserta' => 'Mahasiswa',
            'asal_sekolah' => 'Sekolah Umum',
            'jurusan' => 'Teknik',
            'tgl_masuk' => $masuk,
            'tgl_keluar' => $keluar,
            'bidang_id' => $bidangId,
            'unit' => 'Unit 9',
            'status_magang' => $status,
            'keterangan' => '-',
        ]);
    }
}

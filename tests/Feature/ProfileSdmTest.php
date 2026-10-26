<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileSdmTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('absensi_peserta');

        Schema::create('users', function (Blueprint $t) {
            $t->increments('id');
            $t->string('username', 50)->nullable();
            $t->string('nama', 100)->nullable();
            $t->string('password')->nullable();
            $t->string('role', 20)->default('peserta');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('absensi_peserta', function (Blueprint $t) {
            $t->increments('id');
            $t->string('username', 50);
            $t->date('tanggal');
            $t->string('status')->default('Hadir');
            $t->string('approval_status')->default('Disetujui');
        });
    }

    private function makeSdm(): User
    {
        return User::create([
            'username' => 'sdm123',
            'nama'     => 'Sdm Tester',
            'password' => bcrypt('secret'),
            'role'     => 'sdm',
        ]);
    }

    public function test_halaman_profile_tampil_dengan_data_asli(): void
    {
        $sdm = $this->makeSdm();

        $res = $this->actingAs($sdm)->get('/sdm/profile');

        $res->assertOk();
        $res->assertSee('Profil Pengguna', false);
        $res->assertSee('Pembaruan Kata Sandi', false);
        $res->assertSee('Sdm Tester');
        $res->assertSee('@sdm123');
        // Data fiktif dari referensi harus tidak muncul
        $res->assertDontSee('SDM-NP-882');
        $res->assertDontSee('Pegawai Tetap PLN NP');
        $res->assertDontSee('Argon2id');
    }

    public function test_halaman_profile_tidak_memuat_form_upload_avatar(): void
    {
        $sdm = $this->makeSdm();

        $res = $this->actingAs($sdm)->get('/sdm/profile');

        $res->assertOk();
        $res->assertSee('assets/images/team.png', false);
        $res->assertDontSee('Ganti Foto Avatar');
        $res->assertDontSee('/sdm/profile/avatar', false);
    }

    public function test_route_upload_avatar_sudah_dihapus(): void
    {
        $sdm = $this->makeSdm();

        $this->actingAs($sdm)->post('/sdm/profile/avatar')->assertNotFound();
        $this->actingAs($sdm)->post('/sdm/profile/avatar/hapus')->assertNotFound();
    }

    public function test_ubah_password_berhasil(): void
    {
        $sdm = $this->makeSdm();

        $res = $this->actingAs($sdm)->post('/sdm/profile', [
            'password_baru'        => 'rahasia123',
            'konfirmasi_password'  => 'rahasia123',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');
        $this->assertTrue(password_verify('rahasia123', DB::table('users')->where('username', 'sdm123')->value('password')));
    }

    public function test_ubah_password_ditolak_jika_kurang_dari_enam_karakter(): void
    {
        $sdm = $this->makeSdm();
        $hashAwal = DB::table('users')->where('username', 'sdm123')->value('password');

        $res = $this->actingAs($sdm)->post('/sdm/profile', [
            'password_baru'       => 'abc',
            'konfirmasi_password' => 'abc',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertSame($hashAwal, DB::table('users')->where('username', 'sdm123')->value('password'));
    }
}

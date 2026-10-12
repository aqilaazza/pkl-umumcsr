<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_absensi', function (Blueprint $table) {
            $table->integer('min_durasi_keluar')->default(180)->after('radius_meter');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_absensi', function (Blueprint $table) {
            $table->dropColumn('min_durasi_keluar');
        });
    }
};

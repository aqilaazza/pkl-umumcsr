<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_laporan', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('max_ukuran_mb')->default(5);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_laporan');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peserta extends Model
{
    protected $table = 'peserta';

    public $timestamps = false;

    protected $guarded = [];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id', 'id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'username', 'username');
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(LaporanMagang::class, 'username', 'username');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(AbsensiPeserta::class, 'username', 'username');
    }

    public function sertifikat(): HasOne
    {
        return $this->hasOne(SertifikatMagang::class, 'username', 'username');
    }
}

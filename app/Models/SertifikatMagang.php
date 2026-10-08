<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SertifikatMagang extends Model
{
    protected $table = 'sertifikat_magang';

    public $timestamps = false;

    protected $guarded = [];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'username', 'username');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMagang::class, 'laporan_id', 'id');
    }
}

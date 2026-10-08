<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanMagang extends Model
{
    protected $table = 'laporan_magang';

    public $timestamps = false;

    protected $guarded = [];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'username', 'username');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id', 'id');
    }
}

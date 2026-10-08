<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiPeserta extends Model
{
    protected $table = 'absensi_peserta';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $guarded = [];

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(Peserta::class, 'username', 'username');
    }
}

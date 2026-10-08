<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bidang extends Model
{
    protected $table = 'bidang';

    public $timestamps = false;

    protected $guarded = [];

    public function peserta(): HasMany
    {
        return $this->hasMany(Peserta::class, 'bidang_id', 'id');
    }
}

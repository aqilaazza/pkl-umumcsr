<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $table = 'hari_libur';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $guarded = [];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    protected $table = 'dokumen';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $guarded = [];
}

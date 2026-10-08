<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomor extends Model
{
    protected $table = 'nomor';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
}

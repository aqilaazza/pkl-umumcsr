<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'nama',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function peserta(): HasOne
    {
        return $this->hasOne(Peserta::class, 'username', 'username');
    }
}

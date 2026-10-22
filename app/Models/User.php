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
        'avatar',
        'password_updated_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password_updated_at' => 'datetime',
    ];

    /**
     * URL avatar pengguna. Falls back ke gambar default bila belum ada upload.
     */
    public function getAvatarUrlAttribute(): string
    {
        if (! empty($this->avatar)) {
            return asset('uploads/avatar/' . $this->avatar);
        }

        return asset('assets/images/team.png');
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function peserta(): HasOne
    {
        return $this->hasOne(Peserta::class, 'username', 'username');
    }
}

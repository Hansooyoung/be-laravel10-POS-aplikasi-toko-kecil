<?php

namespace App\Models;

use Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $table = 'user';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'email',
        'nama',
        'password',
        'role',
        'status',
    ];

    public $timestamps = true;

    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role'=> $this->role];
    }
    public function barang()
    {
        return $this->hasMany(Barang::class, 'user_id');
    }
    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'user_id');
    }
    public function member()
    {
        return $this->hasOne(Member::class, 'user_id');
    }
    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'user_id');
    }

}

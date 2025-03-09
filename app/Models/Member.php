<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'member';

    protected $fillable = [
        'nama_member',
        'email',
        'no_hp',
        'password',
        'total_point',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function penjualan()
    {
        return $this->hasMany(penjualan::class, 'member_id', 'member_id');
    }
}

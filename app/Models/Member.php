<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'member'; // Nama tabel di database

    protected $fillable = [
        'user_id',
        'nama_member',
        'email',
        'alamat',
        'no_hp',
        'total_point',
    ];

    /**
     * Relasi ke tabel users (User yang terhubung dengan Member)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // Di dalam Model Member.php
    public function historyVouchers()
    {
        return $this->hasMany(HistoryVoucher::class, 'member_id');
    }
    /**
     * Relasi ke tabel pengajuan_barang (Pengajuan Barang oleh Member)
     */
    public function pengajuanBarang()
    {
        return $this->hasMany(PengajuanBarang::class, 'member_id');
    }

}

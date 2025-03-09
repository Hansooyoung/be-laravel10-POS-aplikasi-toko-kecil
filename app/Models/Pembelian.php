<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelian';

    protected $fillable = [
        'tanggal_pembelian',
        'tanggal_masuk',
        'user_id',
        'vendor_id',
    ];

    protected $appends = ['total'];

    /**
     * Relasi ke User (Pembelian dilakukan oleh user).
     */
    public function user()
{
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke Vendor (Pembelian dari vendor tertentu).
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Relasi ke DetailPembelian (1 pembelian bisa memiliki banyak detail pembelian).
     */
    public function detailPembelian()
    {
        return $this->hasMany(DetailPembelian::class, 'pembelian_id');
    }

    /**
     * Hitung total harga jual dari detail pembelian.
     */
    public function getTotalAttribute()
    {
        return $this->detailPembelian->sum('sub_total');
    }
}

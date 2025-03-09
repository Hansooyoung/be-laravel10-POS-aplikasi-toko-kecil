<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';

    protected $fillable = [
        'member_id',
        'voucher_id',
        'user_id',
        'tanggal_penjualan'
    ];

    protected $appends = ['total_penjualan', 'total_keuntungan'];

    // 🔹 Relasi ke Member (jika ada)
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    // 🔹 Relasi ke Voucher (jika ada)
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    // 🔹 Relasi ke User (kasir yang melakukan transaksi)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🔹 Relasi ke Detail Penjualan
    public function detailPenjualan()
    {
        return $this->hasMany(DetailPenjualan::class, 'penjualan_id');
    }

    // 🔹 Total harga dari semua barang dalam transaksi
    public function getTotalPenjualanAttribute()
    {
        return $this->detailPenjualan->sum(fn($detail) => $detail->harga_jual * $detail->jumlah);
    }

    // 🔹 Total keuntungan (harga jual - harga beli) * jumlah barang
    public function getTotalKeuntunganAttribute()
    {
        return $this->detailPenjualan->sum(fn($detail) => ($detail->harga_jual - $detail->harga_beli) * $detail->jumlah);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan';

    protected $fillable = [
        'penjualan_id','kode_barang',
        'harga_jual', 'harga_beli', 'jumlah'
    ];

    protected $appends = ['sub_total', 'keuntungan', 'diskon'];

    // 🔹 Relasi ke tabel Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang')->withTrashed();
    }

    public function penjualan()
{
    return $this->belongsTo(Penjualan::class, 'penjualan_id');
}
    // 🔹 Subtotal = Harga Jual Final x Jumlah
    public function getSubTotalAttribute()
    {
        return $this->harga_jual * $this->jumlah;
    }

    // 🔹 Keuntungan = (Harga Jual - Harga Beli) x Jumlah
    public function getKeuntunganAttribute()
    {
        return (    $this->harga_jual * $this->jumlah) - ($this->harga_beli * $this->jumlah); ;
    }

    // 🔹 Diskon = Harga Jual Asli - Harga Jual Setelah Diskon
    public function getDiskonAttribute()
    {
        if (!$this->barang || !$this->barang->harga_jual_diskon) {
            return 0;
        }

        return $this->barang->harga_jual - $this->barang->harga_jual_diskon;
    }
}
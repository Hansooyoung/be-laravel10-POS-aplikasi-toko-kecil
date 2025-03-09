<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPembelian extends Model
{
    use HasFactory;

    protected $table = 'detail_pembelian';

    protected $fillable = [
        'pembelian_id',
        
        'kode_barang',
        'harga_beli',
        'jumlah',
    ];
    protected $appends = ['sub_total'];

    /**
     * Relasi ke Pembelian (Detail ini milik satu pembelian).
     */
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    /**
     * Relasi ke Barang (Detail pembelian terkait dengan barang tertentu).
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    /**
     * Menghitung subtotal (harga_beli * jumlah).
     */
    public function getSubTotalAttribute()
    {
        return $this->harga_beli * $this->jumlah;
    }
}

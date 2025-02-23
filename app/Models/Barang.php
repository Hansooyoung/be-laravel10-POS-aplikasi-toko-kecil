<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang';
    protected $primaryKey = 'kode_barang';
    public $incrementing = false; // Karena primary key adalah string
    protected $keyType = 'string';

    protected $fillable = [
        'kode_barang', 'kategori_id', 'user_id', 'vendor_id','barcode',
        'nama_barang','profit_persen', 'status',// Hapus 'satuan' karena tidak ada di tabel
        'harga_beli', 'gambar', 'stok','satuan_id'
    ];

    protected $appends = ['harga_jual'];

    // Relasi ke kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Relasi ke user (admin yang input barang)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    // Relasi ke detail pembelian
    public function detailPembelian()
    {
        return $this->hasMany(DetailPembelian::class, 'kode_barang', 'kode_barang');
    }

    // Harga jual otomatis berdasarkan harga beli dan profit kategori
    public function getHargaJualAttribute()
    {
        $hargaBeli = floatval($this->harga_beli);
        $profitPersen = floatval($this->profit_persen) / 100;

        return $hargaBeli + ($hargaBeli * $profitPersen);
    }


}

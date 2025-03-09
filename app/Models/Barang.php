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
        'kode_barang', 'kategori_id', 'user_id', 'satuan_id', 'diskon_id',
        'barcode', 'nama_barang', 'status', 'profit_persen',
        'harga_beli', 'gambar', 'stok'
    ];

    protected $appends = ['harga_jual', 'harga_jual_diskon'];

    // 🔹 Relasi ke kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // 🔹 Relasi ke user (admin yang input barang)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🔹 Relasi ke satuan
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    // 🔹 Relasi ke diskon
    public function diskon()
    {
        return $this->belongsTo(Diskon::class, 'diskon_id');
    }

    // 🔹 Relasi ke detail pembelian
    public function detailPembelian()
    {
        return $this->hasMany(DetailPembelian::class, 'kode_barang', 'kode_barang');
    }

    // 🔹 Harga jual normal tanpa diskon
    public function getHargaJualAttribute()
    {
        $hargaBeli = floatval($this->harga_beli);
        $profitPersen = floatval($this->profit_persen) / 100;

        return $hargaBeli + ($hargaBeli * $profitPersen);
    }

    // 🔹 Harga jual setelah diskon (opsional)
    public function getHargaJualDiskonAttribute()
    {
        if (!$this->diskon) {
            return null;
        }

        $hargaJual = $this->getHargaJualAttribute();

        if ($this->diskon->jenis_diskon == 'persen') {
            return $hargaJual - ($hargaJual * ($this->diskon->nilai_diskon / 100));
        } else {
            return max(0, $hargaJual - $this->diskon->nilai_diskon);
        }
    }
}

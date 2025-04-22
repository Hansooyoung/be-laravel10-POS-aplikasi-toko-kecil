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

    protected $appends = ['total_penjualan', 'total_penjualan_setelah_diskon', 'total_keuntungan', 'tunai', 'kembalian', 'total_diskon', 'kode_penjualan']; // Menambahkan kode_penjualan ke appends

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detailPenjualan()
    {
        return $this->hasMany(DetailPenjualan::class, 'penjualan_id');
    }

    // 🔹 Total harga sebelum diskon
    public function getTotalPenjualanAttribute()
    {
        return $this->detailPenjualan->sum(fn($detail) => $detail->harga_jual * $detail->jumlah);
    }

    // 🔹 Total harga setelah diskon (mengurangi voucher jika ada)
    public function getTotalDiskonAttribute()
    {
        if (!$this->voucher) {
            return 0;
        }

        $total = $this->total_penjualan;
        $voucher = $this->voucher;

        // Pastikan minimal pembelian terpenuhi
        if ($total < $voucher->min_pembelian) {
            return 0;
        }

        if ($voucher->jenis_voucher === 'persen') {
            return ($voucher->nilai_voucher / 100) * $total;
        } else {
            return min($total, $voucher->nilai_voucher); // Maksimal diskon adalah total belanja
        }
    }

    /**
     * Menghitung total penjualan setelah diskon
     */
    public function getTotalPenjualanSetelahDiskonAttribute()
    {
        return max($this->total_penjualan - $this->total_diskon, 0);
    }

    // 🔹 Total keuntungan (harga jual - harga beli) * jumlah barang
    public function getTotalKeuntunganAttribute()
    {
        return $this->detailPenjualan->sum(fn($detail) => ($detail->harga_jual - $detail->harga_beli) * $detail->jumlah);
    }

    private $tunaiInput; // Menyimpan tunai sementara

    public function setTunaiAttribute($value)
    {
        $this->tunaiInput = $value;
    }

    public function getTunaiAttribute()
    {
        return $this->tunaiInput ?? 0;
    }

    public function getKembalianAttribute()
    {
        return max(($this->tunaiInput ?? 0) - $this->total_penjualan_setelah_diskon, 0);
    }

    public function getKodePenjualanAttribute()
    {
        return 'INV-' . date('Ymd') . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT); // Menggunakan ID penjualan
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Tidak perlu membuat kode_penjualan di sini karena kode_penjualan sudah dihitung menggunakan getKodePenjualan
        });
    }
}

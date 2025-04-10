<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_barang'; // Nama tabel di database

    protected $fillable = [
        'user_id',
        'member_id',
        'nama_barang',
        'jumlah',
        'status',
        'pesan',
        'tanggal_pengajuan',
        'keterangan'
    ];

    /**
     * Relasi ke tabel users (yang menyetujui/menolak pengajuan)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke tabel members (yang mengajukan barang)
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}

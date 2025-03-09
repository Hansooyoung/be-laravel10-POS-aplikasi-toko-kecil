<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'voucher';

    protected $fillable = [
        'nama_voucher',
        'harga_point',
        'jenis_voucher',
        'status',
        'nilai_voucher',
        'min_pembelian',
    ];
}

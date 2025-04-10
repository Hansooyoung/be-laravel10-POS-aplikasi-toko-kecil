<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryVoucher extends Model
{
    use HasFactory;

    protected $table = 'history_voucher';

    protected $fillable = [
        'member_id',
        'voucher_id',
        'tanggal_penukaran',
        'tanggal_digunakan',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }
}
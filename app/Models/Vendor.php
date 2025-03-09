<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor';

    protected $fillable = ['nama_vendor', 'alamat', 'no_hp'];

    protected $dates = ['deleted_at'];

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'vendor_id');
    }
}

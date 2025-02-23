<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendor';

    protected $fillable = ['nama_vendor', 'alamat', 'no_hp'];

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'vendor_id');
    }
}

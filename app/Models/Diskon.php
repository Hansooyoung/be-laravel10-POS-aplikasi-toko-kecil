<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diskon extends Model
{
    use HasFactory;

    protected $table = 'diskon';

    protected $fillable = [
        'nama_diskon',
        'jenis_diskon',
        'nilai_diskon',
        'tanggal_mulai',
        'tanggal_berakhir',
    ];
    public function barang()
    {
        return $this->hasMany(Barang::class, 'diskon_id');
    }
}

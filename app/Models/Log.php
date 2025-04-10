<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = ['activity', 'user_id', 'description', 'tanggal_aktifitas']; // Tambahkan 'tanggal_aktifitas'

    // Fungsi untuk mencatat aktivitas
    public static function createLog($activity, $userId, $description = null)
    {
        return self::create([
            'activity' => $activity,
            'user_id' => $userId,
            'description' => $description,
            'tanggal_aktifitas' => now(), // Menambahkan waktu saat ini
        ]);
    }
}

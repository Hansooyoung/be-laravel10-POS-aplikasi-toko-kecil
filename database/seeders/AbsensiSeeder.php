<?php

namespace Database\Seeders;

use App\Models\Absensi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbsensiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil id user secara acak
        $userIds = DB::table('user')->pluck('id')->toArray();

        // Menyisipkan data absensi
        Absensi::insert([
            [
                'user_id' => $userIds[array_rand($userIds)],  // Mengambil user_id acak
                'tanggal' => now()->format('Y-m-d'),
                'jam_masuk' => now()->format('H:i:s'),
                'jam_keluar' => now()->addHours(8)->format('H:i:s'),
                'status' => 'masuk',
                'keterangan' => 'Masuk kerja',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],  // Mengambil user_id acak
                'tanggal' => now()->format('Y-m-d'),
                'jam_masuk' => now()->format('H:i:s'),
                'jam_keluar' => now()->addHours(8)->format('H:i:s'),
                'status' => 'izin',
                'keterangan' => 'Izin sakit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],  // Mengambil user_id acak
                'tanggal' => now()->format('Y-m-d'),
                'jam_masuk' => now()->format('H:i:s'),
                'jam_keluar' => now()->addHours(8)->format('H:i:s'),
                'status' => 'sakit',
                'keterangan' => 'Sakit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $userIds[array_rand($userIds)],  // Mengambil user_id acak
                'tanggal' => now()->format('Y-m-d'),
                'jam_masuk' => now()->format('H:i:s'),
                'jam_keluar' => now()->addHours(8)->format('H:i:s'),
                'status' => 'cuti',
                'keterangan' => 'Cuti tahunan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

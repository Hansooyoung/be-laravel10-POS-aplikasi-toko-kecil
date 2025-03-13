<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Satuan::insert([
        ['nama_satuan' => 'Pcs',  'created_at' => now(), 'updated_at' => now()],
        ['nama_satuan' => 'Dus',  'created_at' => now(), 'updated_at' => now()],
        ['nama_satuan' => 'Lusin',  'created_at' => now(), 'updated_at' => now()],
        ['nama_satuan' => 'Kodi', 'created_at' => now(), 'updated_at' => now()],
                ]);
    }
}

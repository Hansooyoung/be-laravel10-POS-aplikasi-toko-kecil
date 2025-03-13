<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kategori::insert([
            ['nama_kategori' => 'Makanan',  'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Minuman',  'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Alat Tulis',  'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Elektronik', 'created_at' => now(), 'updated_at' => now()],
        ]);

    }
}

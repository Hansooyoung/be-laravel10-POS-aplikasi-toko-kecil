<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

    // }

    // public function run(): void
    // {


    //     // Membaca file JSON
    //     if (File::exists($jsonPath)) {
    //         $jsonData = File::get('database/data/kategori.json');
    //         $kategoriData = json_decode($jsonData, true);

    //         // Insert data ke tabel kategori menggunakan model Kategori
    //         foreach ($kategoriData as $kategori) {
    //             Kategori::create([
    //                 'id' => $kategori -> id,
    //                 'nama_kategori' => $kategori -> nama_kategori,
    //             ]);
    //         }
    //     }
    // }
}
}
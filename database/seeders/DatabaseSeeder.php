<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Vendor; // Pastikan sudah import model Vendor
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan database seeds.
     */
    public function run(): void
    {
        // Seed User
        User::insert([
            [
                'nama' => 'Super Admin',
                'email' => 'super@gmail.com',
                'password' => Hash::make('super123'),
                'role' => 'super',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Regular User',
                'email' => 'user@gmail.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Seed Kategori
        Kategori::insert([
            ['nama_kategori' => 'Makanan',  'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Minuman',  'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Alat Tulis',  'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Elektronik', 'created_at' => now(), 'updated_at' => now()],
        ]);
        Satuan::insert([
            ['nama_satuan' => 'Pcs',  'created_at' => now(), 'updated_at' => now()],
            ['nama_satuan' => 'Dus',  'created_at' => now(), 'updated_at' => now()],
            ['nama_satuan' => 'Lusin',  'created_at' => now(), 'updated_at' => now()],
            ['nama_satuan' => 'Kodi', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed Vendor
        Vendor::insert([
            [
                'nama_vendor' => 'Vendor A',
                'alamat' => 'Jl. Contoh Alamat A, No. 123',
                'no_hp' => '081234567890',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_vendor' => 'Vendor B',
                'alamat' => 'Jl. Contoh Alamat B, No. 456',
                'no_hp' => '082345678901',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_vendor' => 'Vendor C',
                'alamat' => 'Jl. Contoh Alamat C, No. 789',
                'no_hp' => '083456789012',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

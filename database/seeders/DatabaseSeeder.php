<?php

namespace Database\Seeders;

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
                'password' => Hash::make('password'),
                'role' => 'super',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Regular User',
                'email' => 'user@gmail.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Seed Kategori
        Kategori::insert([
            ['nama_kategori' => 'Makanan', 'profit_persen' => 10.0, 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Minuman', 'profit_persen' => 12.5, 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Alat Tulis', 'profit_persen' => 8.0, 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Elektronik', 'profit_persen' => 15.0, 'created_at' => now(), 'updated_at' => now()],
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

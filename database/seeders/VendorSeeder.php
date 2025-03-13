<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\Vendor;
use App\Models\Diskon;
use App\Models\Member;
use Carbon\Carbon;

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

        // Seed Satuan
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

        // Seed Diskon
        Diskon::insert([
            [
                'nama_diskon' => 'diskon 10 persen',
                'jenis_diskon' => 'persen',
                'nilai_diskon' => 10.00,
                'tanggal_mulai' => Carbon::now()->toDateString(),
                'tanggal_berakhir' => Carbon::now()->addDays(30)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_diskon' => 'potongan harga goceng',
                'jenis_diskon' => 'nominal',
                'nilai_diskon' => 5000.00,
                'tanggal_mulai' => Carbon::now()->toDateString(),
                'tanggal_berakhir' => Carbon::now()->addDays(15)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_diskon' => 'diskon 5 persen',
                'jenis_diskon' => 'persen',
                'nilai_diskon' => 5.00,
                'tanggal_mulai' => Carbon::now()->subDays(10)->toDateString(),
                'tanggal_berakhir' => Carbon::now()->addDays(20)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Voucher::insert([
            [
                'nama_voucher' => 'Diskon 10%',
                'harga_point' => 5000,
                'jenis_voucher' => 'persen',
                'status' => 'aktif',
                'nilai_voucher' => 10.00,
                'min_pembelian' => 50000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_voucher' => 'Potongan Rp 10.000',
                'harga_point' => 10000,
                'jenis_voucher' => 'nominal',
                'status' => 'aktif',
                'nilai_voucher' => 10000.00,
                'min_pembelian' => 100000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_voucher' => 'Diskon 5%',
                'harga_point' => 3000,
                'jenis_voucher' => 'persen',
                'status' => 'kadaluarsa',
                'nilai_voucher' => 5.00,
                'min_pembelian' => 30000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Member::insert([
            [
                'nama_member' => 'John Doe',
                'email' => 'johndoe@example.com',
                'no_hp' => '081234567891',
                'password' => Hash::make('password123'),
                'total_point' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_member' => 'Jane Smith',
                'email' => 'janesmith@example.com',
                'no_hp' => '081234567892',
                'password' => Hash::make('password123'),
                'total_point' => 2000,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}

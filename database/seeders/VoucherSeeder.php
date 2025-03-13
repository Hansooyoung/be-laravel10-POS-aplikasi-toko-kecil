<?php

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
    }
}

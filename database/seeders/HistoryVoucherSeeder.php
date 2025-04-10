<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HistoryVoucher;

class HistoryVoucherSeeder extends Seeder
{
    public function run()
    {
        HistoryVoucher::insert([
            [
                'member_id' => 1, // Sesuai dengan member_id dari MemberSeeder
                'voucher_id' => 1, // Sesuai dengan voucher_id dari VoucherSeeder
                'tanggal_penukaran' => now()->subDays(2),
                'tanggal_digunakan' => null, // Belum digunakan
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 2,
                'voucher_id' => 2,
                'tanggal_penukaran' => now()->subDays(5),
                'tanggal_digunakan' => null, // Sudah digunakan
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 1, // Sesuai dengan member_id dari MemberSeeder
                'voucher_id' => 1, // Sesuai dengan voucher_id dari VoucherSeeder
                'tanggal_penukaran' => now()->subDays(2),
                'tanggal_digunakan' => null, // Belum digunakan
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'member_id' => 1, // Sesuai dengan member_id dari MemberSeeder
                'voucher_id' => 1, // Sesuai dengan voucher_id dari VoucherSeeder
                'tanggal_penukaran' => now()->subDays(2),
                'tanggal_digunakan' => null, // Belum digunakan
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

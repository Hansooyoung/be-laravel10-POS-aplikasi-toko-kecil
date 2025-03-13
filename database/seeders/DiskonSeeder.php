<?php

namespace Database\Seeders;

use App\Models\Diskon;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiskonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
    }
}

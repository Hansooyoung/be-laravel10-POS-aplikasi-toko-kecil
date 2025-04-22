<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Vendor;
use App\Models\Barang;
use App\Models\Member;
use App\Models\User;
use App\Models\Voucher;

class TransaksiSeeder extends Seeder
{
    public function run()
    {
        $this->seedPembelian();
        $this->seedPenjualan();
    }

    private function seedPembelian()
    {
        $user_ids = [5, 6]; // Kasir atau admin
        $vendor = Vendor::inRandomOrder()->first();

        if (!$vendor) {
            throw new \Exception('Tidak ada vendor yang tersedia.');
        }

        // Generate array of last 7 days
        $last7Days = [];
        for ($i = 0; $i < 7; $i++) {
            $last7Days[] = Carbon::today()->subDays($i);
        }

        for ($i = 0; $i < 10; $i++) { // 10 transaksi pembelian
            $barangList = Barang::inRandomOrder()->limit(5)->get();
            if ($barangList->isEmpty()) {
                throw new \Exception('Tidak ada barang yang tersedia.');
            }

            // Get random date from last 7 days
            $tanggal_pembelian = $last7Days[array_rand($last7Days)]->copy();

            $pembelian = Pembelian::create([
                'vendor_id' => $vendor->id,
                'tanggal_pembelian' => $tanggal_pembelian,
                'tanggal_masuk' => (rand(0, 1) ? $tanggal_pembelian->copy()->addHours(rand(1, 12)) : null),
                'user_id' => $user_ids[array_rand($user_ids)],
            ]);

            foreach ($barangList as $barang) {
                $harga_beli = max(rand(10000, 50000), 10000);
                $jumlah = max(rand(10, 50), 10);

                DetailPembelian::create([
                    'pembelian_id' => $pembelian->id,
                    'kode_barang' => $barang->kode_barang,
                    'harga_beli' => $harga_beli,
                    'jumlah' => $jumlah,
                ]);

                $barang->harga_beli = $harga_beli;

                if (!is_null($pembelian->tanggal_masuk)) {
                    $barang->stok += $jumlah;
                }

                $barang->save();
            }
        }

        echo "Seeder Pembelian selesai! (10 transaksi dibuat)\n";
    }

    private function seedPenjualan()
    {
        $user_ids = [3, 4];

        // Generate array of last 7 days
        $last7Days = [];
        for ($i = 0; $i < 7; $i++) {
            $last7Days[] = Carbon::today()->subDays($i);
        }

        for ($i = 0; $i < 10; $i++) { // 10 transaksi penjualan
            $member = Member::inRandomOrder()->first();
            $member_id = $member ? $member->id : null;

            $barangList = Barang::where('stok', '>', 0)->inRandomOrder()->limit(5)->get();
            if ($barangList->isEmpty()) {
                throw new \Exception('Tidak ada barang yang tersedia untuk penjualan.');
            }

            // Get random date from last 7 days
            $tanggal_penjualan = $last7Days[array_rand($last7Days)]->copy();

            $penjualan = Penjualan::create([
                'member_id' => $member_id,
                'voucher_id' => null,
                'user_id' => $user_ids[array_rand($user_ids)],
                'tanggal_penjualan' => $tanggal_penjualan,
            ]);

            foreach ($barangList as $barang) {
                $jumlah = max(rand(1, 5), 1);

                if ($barang->stok < $jumlah) {
                    continue;
                }

                DB::table('detail_penjualan')->insert([
                    'penjualan_id' => $penjualan->id,
                    'kode_barang' => $barang->kode_barang,
                    'harga_jual' => $barang->harga_jual,
                    'harga_beli' => $barang->harga_beli,
                    'jumlah' => $jumlah,
                    'created_at' => $tanggal_penjualan,
                    'updated_at' => $tanggal_penjualan,
                ]);

                $barang->stok -= $jumlah;
                $barang->save();
            }

            if ($member_id) {
                $voucher = Voucher::where('min_pembelian', '<=', $penjualan->total_penjualan)->first();
                if ($voucher) {
                    $penjualan->voucher_id = $voucher->id;
                    $penjualan->save();
                }
            }
        }

        echo "Seeder Penjualan selesai! (10 transaksi dibuat)\n";
    }
}
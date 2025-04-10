<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Vendor;
use App\Models\Barang;
use App\Models\User;

class PembelianSeeder extends Seeder
{
    public function run()
    {
        // Ambil user_id hanya 3 dan 4
        $user_ids = [3, 4];

        // Ambil daftar vendor
        $vendor = Vendor::inRandomOrder()->first();
        if (!$vendor) {
            throw new \Exception('Tidak ada vendor yang tersedia.');
        }

        for ($i = 0; $i < 11; $i++) { // Loop untuk 3 transaksi
            // Ambil barang yang tersedia
            $barangList = Barang::inRandomOrder()->limit(5)->get();
            if ($barangList->isEmpty()) {
                throw new \Exception('Tidak ada barang yang tersedia.');
            }
            $tanggal_pembelian = Carbon::today()->subDays(rand(0, 30));
            // Buat transaksi pembelian baru
            $pembelian = Pembelian::create([
                'vendor_id' => $vendor->id,
                'tanggal_pembelian' => $tanggal_pembelian,
                'tanggal_masuk' => (rand(0, 1) ? now() : null), // Acak antara null atau tanggal masuk
                'user_id' => $user_ids[array_rand($user_ids)],
            ]);

            // Tambahkan barang ke detail pembelian
            foreach ($barangList as $barang) {
                $harga_beli = max(rand(10000, 50000), 10000); // Harga beli minimal 10.000
                $jumlah = max(rand(10, 50), 10); // Stok minimal 10

                DetailPembelian::create([
                    'pembelian_id' => $pembelian->id,
                    'kode_barang' => $barang->kode_barang,
                    'harga_beli' => $harga_beli,
                    'jumlah' => $jumlah,
                ]);

                // Update harga beli barang
                $barang->harga_beli = $harga_beli;

                // Tambahkan stok jika tanggal_masuk tidak null
                if (!is_null($pembelian->tanggal_masuk)) {
                    $barang->stok += $jumlah;
                }

                $barang->save();
            }
        }

        echo "Seeder Pembelian berhasil dijalankan (10 transaksi dibuat)!\n";
    }
}
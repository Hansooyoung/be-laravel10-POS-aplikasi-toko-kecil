<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Barang;
use App\Models\Member;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;

class PenjualanSeeder extends Seeder
{
    public function run()
    {
        // Ambil user_id hanya dari kasir (misalnya user role 3 atau 4)
        $user_ids = [3, 4];

        for ($i = 0; $i < 3; $i++) { // Loop untuk membuat 3 transaksi
            // Pilih member secara acak atau null
            $member = Member::inRandomOrder()->first();
            $member_id = $member ? $member->id : null;

            // Pilih barang secara acak untuk transaksi
            $barangList = Barang::where('stok', '>', 0)->inRandomOrder()->limit(5)->get();
            if ($barangList->isEmpty()) {
                throw new \Exception('Tidak ada barang yang tersedia untuk penjualan.');
            }

            // Generate tanggal penjualan acak dalam 30 hari terakhir
            $tanggal_penjualan = Carbon::today()->subDays(rand(0, 30));

            // Buat transaksi penjualan
            $penjualan = Penjualan::create([
                'member_id' => $member_id,
                'voucher_id' => null, // Akan diatur nanti jika tersedia
                'user_id' => $user_ids[array_rand($user_ids)],
                'tanggal_penjualan' => $tanggal_penjualan,
            ]);

            // Tambahkan barang ke detail penjualan dan kurangi stok
            foreach ($barangList as $barang) {
                $jumlah = max(rand(1, 5), 1); // Minimal beli 1 barang

                if ($barang->stok < $jumlah) {
                    continue; // Lewati barang jika stok tidak cukup
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

                // Kurangi stok barang
                $barang->stok -= $jumlah;
                $barang->save();
            }

            // Cek apakah member memiliki voucher yang bisa digunakan
            if ($member_id) {
                $voucher = Voucher::where('min_pembelian', '<=', $penjualan->total_penjualan)->first();
                if ($voucher) {
                    $penjualan->voucher_id = $voucher->id;
                    $penjualan->save();
                }
            }
        }

        echo "Seeder Penjualan berhasil dijalankan (3 transaksi dibuat)!\n";
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kategori;
use App\Models\Satuan;
use App\Models\Diskon;
use App\Models\Barang;

class BarangSeeder extends Seeder
{
    public function run()
    {
        // Ambil ID kategori, satuan, dan diskon yang ada
        $kategori_ids = Kategori::pluck('id')->toArray();
        $satuan_ids = Satuan::pluck('id')->toArray();
        $diskon_ids = Diskon::pluck('id')->toArray();
        $user_ids = [3, 4]; // Hanya user_id 3 atau 4

        // Data barang dalam bentuk JSON (10 barang)
        $barangData = [
            ['barcode' => '1234567890123', 'nama_barang' => 'Beras Premium 5kg', 'profit_persen' => 15.00],
            ['barcode' => '2345678901234', 'nama_barang' => 'Minyak Goreng 2L', 'profit_persen' => 10.00],
            ['barcode' => '3456789012345', 'nama_barang' => 'Gula Pasir 1kg', 'profit_persen' => 12.00],
            ['barcode' => '4567890123456', 'nama_barang' => 'Kopi Bubuk 200gr', 'profit_persen' => 20.00],
            ['barcode' => '5678901234567', 'nama_barang' => 'Teh Celup 25pcs', 'profit_persen' => 18.00],
            ['barcode' => '6789012345678', 'nama_barang' => 'Susu UHT 1L', 'profit_persen' => 15.00],
            ['barcode' => '7890123456789', 'nama_barang' => 'Mie Instan 5pcs', 'profit_persen' => 8.00],
            ['barcode' => '8901234567890', 'nama_barang' => 'Sabun Mandi Cair 500ml', 'profit_persen' => 25.00],
            ['barcode' => '9012345678901', 'nama_barang' => 'Shampoo 200ml', 'profit_persen' => 22.00],
            ['barcode' => '0123456789012', 'nama_barang' => 'Pasta Gigi 100gr', 'profit_persen' => 12.00],
        ];

        // Array untuk melacak nomor terakhir yang sudah dibuat per kategori
        $lastNumbers = [];

        // Loop untuk insert barang ke database
        foreach ($barangData as &$barang) {
            $barang['kategori_id'] = $kategori_ids[array_rand($kategori_ids)] ?? null;
            $barang['satuan_id'] = $satuan_ids[array_rand($satuan_ids)] ?? null;
            $barang['diskon_id'] = $diskon_ids[array_rand($diskon_ids)] ?? null;
            $barang['user_id'] = $user_ids[array_rand($user_ids)];
            $barang['status'] = 'Aktif';
            $barang['harga_beli'] = 10000; // Harga beli selalu 0
            $barang['gambar'] = null; // Gambar null
            $barang['stok'] = 10; // Stok awal 0
            $barang['kode_barang'] = $this->generateKodeBarang($barang['kategori_id'], $lastNumbers);
            $barang['created_at'] = now();
            $barang['updated_at'] = now();
        }

        // Insert data ke tabel barang
        DB::table('barang')->insert($barangData);
    }

    private function generateKodeBarang($kategori_id, &$lastNumbers)
    {
        $tahun = date('Y');

        // Jika kategori ini belum ada dalam array tracking, ambil nomor terakhir dari database
        if (!isset($lastNumbers[$kategori_id])) {
            $lastBarang = Barang::withTrashed()
                ->where('kategori_id', $kategori_id)
                ->whereYear('created_at', $tahun)
                ->orderBy('kode_barang', 'desc')
                ->first();

            // Ambil nomor urut terakhir, jika tidak ada mulai dari 0
            $lastNumbers[$kategori_id] = $lastBarang ? (int)substr($lastBarang->kode_barang, -4) : 0;
        }

        // Tambahkan 1 untuk nomor urut baru
        $lastNumbers[$kategori_id]++;
        $newNumber = str_pad($lastNumbers[$kategori_id], 4, '0', STR_PAD_LEFT);

        return "BRG{$tahun}{$kategori_id}{$newNumber}";
    }
}
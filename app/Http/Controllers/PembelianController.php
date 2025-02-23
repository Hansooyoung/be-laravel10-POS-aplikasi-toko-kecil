<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    /**
     * Tampilkan daftar pembelian dengan pagination.
     */
    public function index()
    {
        $pembelian = Pembelian::with([
            'vendor:id,nama_vendor',
            'user:id,nama',
            'detailPembelian.barang:kode_barang,barcode,nama_barang'
        ])->paginate(10);

        return response()->json([
            'data' => $pembelian->items(), // Data pembelian dengan detail
            'pagination' => [
                'total' => $pembelian->total(),
                'per_page' => $pembelian->perPage(),
                'current_page' => $pembelian->currentPage(),
                'last_page' => $pembelian->lastPage(),
                'from' => $pembelian->firstItem(),
                'to' => $pembelian->lastItem(),
            ]
        ]);
    }


    public function show($id)
    {
        $pembelian = Pembelian::with('detailPembelian.barang')->findOrFail($id);
        return response()->json($pembelian);
    }
    /**
     * Simpan data pembelian beserta detailnya.
     */
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'vendor_id' => 'required|exists:vendor,id',
                'barang' => 'required|array',
                'barang.*.barcode' => 'required|string', // Gunakan barcode untuk pencarian
                'barang.*.harga_beli' => 'required|numeric|min:1',
                'barang.*.jumlah' => 'required|integer|min:1',
            ]);

            // Ambil user yang sedang login
            $user_id = auth()->id();
            $tanggal_pembelian = now();

            // Buat data pembelian
            $pembelian = Pembelian::create([
                'tanggal_pembelian' => $tanggal_pembelian,
                'user_id' => $user_id,
                'vendor_id' => $validated['vendor_id'],
            ]);

            // Proses detail pembelian
            foreach ($validated['barang'] as $barangData) {
                // Cari barang berdasarkan barcode
                $barang = Barang::where('barcode', $barangData['barcode'])
                                ->where('vendor_id', $validated['vendor_id']) // Pastikan barang dari vendor yang dipilih
                                ->firstOrFail();

                // Buat detail pembelian
                $pembelian->detailPembelian()->create([
                    'kode_barang' => $barang->kode_barang, // Simpan kode_barang, bukan barcode
                    'harga_beli' => $barangData['harga_beli'],
                    'jumlah' => $barangData['jumlah'],
                ]);

                // Update stok barang dan harga_beli dengan harga terbaru
                $barang->stok += $barangData['jumlah'];
                $barang->harga_beli = $barangData['harga_beli']; // Update harga beli

                // Tidak perlu menyimpan harga_jual karena sudah dihitung otomatis di model
                $barang->save(); // Simpan perubahan stok dan harga beli
            }

            return response()->json([
                'message' => 'Pembelian berhasil ditambahkan',
                'pembelian' => $pembelian
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal menambahkan pembelian: ' . $e->getMessage()
            ], 500);
        }
    }


}

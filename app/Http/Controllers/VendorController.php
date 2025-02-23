<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Tampilkan daftar vendor dengan pagination (10 per halaman).
     */
    public function index()
    {
        $vendors = Vendor::paginate(10);

        return response()->json([
            'message' => 'Data vendor berhasil diambil',
            'data' => $vendors
        ]);
    }

    /**
     * Simpan vendor baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255|unique:vendor,nama_vendor',
            'alamat' => 'nullable|string|max:500',
            'no_hp' => 'nullable|string|max:15',
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'message' => 'Vendor berhasil ditambahkan',
            'data' => $vendor
        ], 201);
    }

    /**
     * Perbarui data vendor.
     */
    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255|unique:vendor,nama_vendor,' . $vendor->id,
            'alamat' => 'nullable|string|max:500',
            'no_hp' => 'nullable|string|max:15',
        ]);

        $vendor->update($validated);

        return response()->json([
            'message' => 'Vendor berhasil diperbarui',
            'data' => $vendor
        ]);
    }

    /**
     * Hapus vendor dengan pengecekan relasi ke pembelian (restrict on delete).
     */
    public function destroy(Vendor $vendor)
    {
        // Cek apakah vendor masih digunakan dalam tabel pembelian
        if ($vendor->pembelian()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus vendor, karena masih digunakan dalam transaksi pembelian.'
            ], 400);
        }

        $vendor->delete();

        return response()->json([
            'message' => 'Vendor berhasil dihapus'
        ]);
    }
}

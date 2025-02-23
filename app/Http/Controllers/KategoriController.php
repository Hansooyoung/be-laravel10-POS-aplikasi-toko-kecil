<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    /**
     * Tampilkan daftar kategori dengan pagination (10 per halaman).
     */
    public function index()
    {
        $kategori = Kategori::paginate(10);

        return response()->json([
            'message' => 'Data kategori berhasil diambil',
            'data' => $kategori
        ]);
    }

    /**
     * Simpan kategori baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        $kategori = Kategori::create($validated);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan',
            'data' => $kategori
        ], 201);
    }

    /**
     * Perbarui data kategori.
     */
    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $kategori->id,
        ]);

        $kategori->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'data' => $kategori
        ]);
    }

    /**
     * Hapus kategori dengan pengecekan relasi ke barang (restrict on delete).
     */
    public function destroy(Kategori $kategori)
    {
        // Cek apakah kategori masih digunakan dalam tabel barang
        if ($kategori->barang()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus kategori, karena masih digunakan dalam barang.'
            ], 400);
        }

        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    // Ambil semua kategori
    public function index()
    {
        return response()->json(Kategori::all());
    }

    // Simpan kategori baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
            'profit_persen' => 'required|numeric|min:0|max:100', // Tambah validasi profit
        ]);

        $kategori = Kategori::create($validated);

        return response()->json(['message' => 'Kategori berhasil ditambahkan', 'kategori' => $kategori], 201);
    }

    // Tampilkan kategori berdasarkan ID
    public function show($id)
    {
        $kategori = Kategori::with('barang')->find($id);

        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        return response()->json($kategori);
    }

    // Update kategori berdasarkan ID
    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $validated = $request->validate([
            'nama_kategori' => 'sometimes|required|string|max:255|unique:kategori,nama_kategori,' . $kategori->id,
            'profit_persen' => 'sometimes|required|numeric|min:0|max:100', // Bisa diupdate
        ]);

        $kategori->update($validated);

        return response()->json(['message' => 'Kategori berhasil diperbarui', 'kategori' => $kategori]);
    }

    // Hapus kategori berdasarkan ID
    public function destroy($id)
    {
        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json(['message' => 'Kategori tidak ditemukan'], 404);
        }

        $kategori->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}

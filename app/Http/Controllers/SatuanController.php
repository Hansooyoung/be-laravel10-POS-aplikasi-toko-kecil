<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class SatuanController extends Controller
{
    /**
     * Tampilkan daftar satuan dengan pagination (10 per halaman).
     */
    public function index()
    {
        $satuan = Satuan::paginate(10);

        return response()->json([
            'message' => 'Data satuan berhasil diambil',
            'data' => $satuan
        ]);
    }

    /**
     * Simpan satuan baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_satuan' => 'required|string|max:20|unique:satuan,nama_satuan',
        ]);

        $satuan = Satuan::create($validated);

        return response()->json([
            'message' => 'Satuan berhasil ditambahkan',
            'data' => $satuan
        ], 201);
    }

    /**
     * Perbarui data satuan.
     */
    public function update(Request $request, Satuan $satuan)
    {
        $validated = $request->validate([
            'nama_satuan' => 'required|string|max:20|unique:satuan,nama_satuan,' . $satuan->id,
        ]);

        $satuan->update($validated);

        return response()->json([
            'message' => 'Satuan berhasil diperbarui',
            'data' => $satuan
        ]);
    }

    /**
     * Hapus satuan dengan pengecekan relasi ke barang (restrict on delete).
     */
    public function destroy(Satuan $satuan)
    {
        // Cek apakah satuan masih digunakan dalam tabel barang
        if ($satuan->barang()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus satuan, karena masih digunakan dalam barang.'
            ], 400);
        }

        $satuan->delete();

        return response()->json([
            'message' => 'Satuan berhasil dihapus'
        ]);
    }
}

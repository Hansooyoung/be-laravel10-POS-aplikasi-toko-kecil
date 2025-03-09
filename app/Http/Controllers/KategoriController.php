<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $query = Kategori::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama_kategori', 'LIKE', '%' . $request->search . '%');
        }

        $kategori = $query->paginate(10);

        return response()->json([
            'message' => 'Data kategori berhasil diambil',
            'data' => $kategori
        ]);
    }



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

    public function show($id)
    {
        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Kategori berhasil ditemukan',
            'data' => $kategori
        ], Response::HTTP_OK);
    }
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
    public function destroy($id)
    {
        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json(['message' => 'kategori tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }
        // Cek apakah ada barang_inventaris yang terkait
        if ($kategori->barang()->exists()) {
            return response()->json([
                'message' => 'kategori tidak dapat dihapus karena masih digunakan .'
            ], Response::HTTP_BAD_REQUEST);
        }
        $kategori->delete();

        return response()->json(['message' => 'kategori berhasil dihapus.'], Response::HTTP_OK);
    }
}

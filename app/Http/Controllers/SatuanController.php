<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;

class SatuanController extends Controller
{
    /**
     * Tampilkan daftar satuan dengan pagination (10 per halaman).
     */
    public function index(Request $request)
    {
        $satuan = Satuan::query();

        if ($request->has('search') && !empty($request->search)) {
            $satuan->where('nama_satuan', 'LIKE', '%' . $request->search . '%');
        }

        $satuan = $satuan->paginate(10);
        return response()->json([
            'message' => 'Data satuan berhasil diambil',
            'data' => $satuan
        ]);
    }

    public function show($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor){
            return response()->json([
                'message' => 'vendor tidak ditemukan'
            ],Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'message' => 'vendor berhasil ditemukan',
            'data' => $vendor
        ], Response::HTTP_OK);
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

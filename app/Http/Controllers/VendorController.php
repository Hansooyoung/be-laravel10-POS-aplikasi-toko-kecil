<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    // Ambil semua vendor
    public function index()
    {
        return response()->json(Vendor::all());
    }

    // Simpan vendor baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255|unique:vendor,nama_vendor,',
            'alamat' => 'required|string',
            'no_hp' => 'required|string|max:15', // Validasi nomor HP
        ]);

        $vendor = Vendor::create($validated);

        return response()->json(['message' => 'Vendor berhasil ditambahkan', 'vendor' => $vendor], 201);
    }

    // Tampilkan vendor berdasarkan ID
    public function show($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor tidak ditemukan'], 404);
        }

        return response()->json($vendor);
    }

    // Update vendor berdasarkan ID
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $request->validate([
            'nama_vendor' => 'required|string|unique:vendor,nama_vendor,' . $vendor->id,
            'alamat' => 'required|string',
            'no_hp' => 'required|string',
        ]);

        $vendor->update($request->all());

        return response()->json(['message' => 'Vendor berhasil diperbarui', 'vendor' => $vendor]);
    }


    // Hapus vendor berdasarkan ID
    public function destroy($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor tidak ditemukan'], 404);
        }

        $vendor->delete();

        return response()->json(['message' => 'Vendor berhasil dihapus']);
    }
}

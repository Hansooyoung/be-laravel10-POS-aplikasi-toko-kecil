<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::query();

        if ($request->has('search') && !empty($request->search)) {
            $vendors->where('nama_vendor', 'LIKE', '%' . $request->search . '%');
        }

        $vendors = $vendors->paginate(10);

        return response()->json([
            'message' => 'Data vendor berhasil diambil',
            'data' => $vendors
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_vendor' => 'required|string|max:255|unique:vendor,nama_vendor,NULL,id,deleted_at,NULL',
            'alamat' => 'nullable|string|max:500',
            'no_hp' => 'nullable|string|max:15',
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'message' => 'Vendor berhasil ditambahkan',
            'data' => $vendor
        ], 201);
    }

    public function show($id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor){
            return response()->json([
                'message' => 'Vendor tidak ditemukan'
            ],Response::HTTP_NOT_FOUND);
        }
        return response()->json([
            'message' => 'Vendor berhasil ditemukan',
            'data' => $vendor
        ], Response::HTTP_OK);
    }
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

    public function destroy($id)
    {
        $vendor = Vendor::where('id', $id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }

        $vendor->delete();

        return response()->json(['message' => 'Vendor berhasil dihapus.'], Response::HTTP_OK);
    }

}

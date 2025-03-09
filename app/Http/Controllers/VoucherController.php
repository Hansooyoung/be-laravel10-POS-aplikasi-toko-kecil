<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama_voucher', 'LIKE', '%' . $request->search . '%');
        }

        $vouchers = $query->paginate(10);

        return response()->json([
            'message' => 'Data voucher berhasil diambil',
            'data' => $vouchers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_voucher' => 'required|string|max:255|unique:voucher,nama_voucher',
            'harga_point' => 'required|integer|min:0',
            'jenis_voucher' => 'required|string|max:50',
            'status' => 'required|in:aktif,tidak_aktif',
            'nilai_voucher' => 'required|numeric|min:0',
            'min_pembelian' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::create($validated);

        return response()->json([
            'message' => 'Voucher berhasil ditambahkan',
            'data' => $voucher
        ], Response::HTTP_CREATED);
    }

    public function show($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json([
                'message' => 'Voucher tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Voucher berhasil ditemukan',
            'data' => $voucher
        ]);
    }

    public function update(Request $request, Voucher $voucher)
    {
        $validated = $request->validate([
            'nama_voucher' => 'required|string|max:255|unique:voucher,nama_voucher,' . $voucher->id,
            'harga_point' => 'required|integer|min:0',
            'jenis_voucher' => 'required|string|max:50',
            'status' => 'required|in:aktif,tidak_aktif',
            'nilai_voucher' => 'required|numeric|min:0',
            'min_pembelian' => 'required|numeric|min:0',
        ]);

        $voucher->update($validated);

        return response()->json([
            'message' => 'Voucher berhasil diperbarui',
            'data' => $voucher
        ]);
    }

    public function destroy($id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher berhasil dihapus.'], Response::HTTP_OK);
    }
}

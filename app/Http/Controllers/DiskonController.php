<?php

namespace App\Http\Controllers;

use App\Models\Diskon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiskonController extends Controller
{
    public function index(Request $request)
    {
        $query = Diskon::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama_diskon', 'LIKE', '%' . $request->search . '%');
        }

        $diskon = $query->paginate(10);

        return response()->json([
            'message' => 'Data diskon berhasil diambil',
            'data' => $diskon
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_diskon' => 'required|string|max:255|unique:diskon,nama_diskon',
            'jenis_diskon' => 'required|string|max:255',
            'nilai_diskon' => 'required|numeric|min:0',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $diskon = Diskon::create($validated);

        return response()->json([
            'message' => 'Diskon berhasil ditambahkan',
            'data' => $diskon
        ], 201);
    }

    public function show($id)
    {
        $diskon = Diskon::find($id);

        if (!$diskon) {
            return response()->json([
                'message' => 'Diskon tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Diskon berhasil ditemukan',
            'data' => $diskon
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Diskon $diskon)
    {
        $validated = $request->validate([
            'nama_diskon' => 'required|string|max:255|unique:diskon,nama_diskon,' . $diskon->id,
            'jenis_diskon' => 'required|string|max:255',
            'nilai_diskon' => 'required|numeric|min:0',
            'tanggal_mulai' => 'required|date',
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $diskon->update($validated);

        return response()->json([
            'message' => 'Diskon berhasil diperbarui',
            'data' => $diskon
        ]);
    }

    public function destroy($id)
    {
        $diskon = Diskon::find($id);

        if (!$diskon) {
            return response()->json(['message' => 'Diskon tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }

        // Cek status kadaluarsa berdasarkan tanggal_berakhir
        $status = now()->greaterThan($diskon->tanggal_berakhir) ? 'Kadaluarsa' : 'Aktif';

        if ($status === 'Aktif' && $diskon->barang()->exists()) {
            return response()->json([
                'message' => 'Diskon tidak dapat dihapus karena masih aktif dan terikat pada barang.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $diskon->delete();

        return response()->json(['message' => 'Diskon berhasil dihapus.'], Response::HTTP_OK);
    }
}

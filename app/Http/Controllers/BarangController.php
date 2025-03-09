<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{

    public function index(Request $request)
    {
        $query = Barang::with([
            'kategori',
            'user',
            'diskon'
        ]);

        // 🔹 Filter berdasarkan nama kategori
        if ($request->has('nama_kategori') && !empty($request->nama_kategori)) {
            $query->whereHas('kategori', function ($q) use ($request) {
                $q->where('nama_kategori', $request->nama_kategori);
            });
        }

        // 🔹 Filter pencarian nama_barang atau barcode
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_barang', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('barcode', 'LIKE', '%' . $request->search . '%');
            });
        }

        $barang = $query->paginate(10);

        $barangData = $barang->map(function ($item) {
            return [
                'kode_barang' => $item->kode_barang,
                'barcode' => $item->barcode,
                'nama_barang' => $item->nama_barang,
                'gambar' => $item->gambar ? url('storage/' . $item->gambar) : null,
                'status' => $item->status,
                'satuan' => $item->satuan->nama_satuan,
                'profit_persen' => $item->profit_persen,
                'harga_jual' => $item->harga_jual,
                'harga_jual_diskon' => $item->harga_jual_diskon,
                'harga_beli' => $item->harga_beli,
                'stok' => $item->stok,
                'kategori' => $item->kategori->nama_kategori,
                'user' => $item->user->nama,
            ];
        });

        return response()->json([
            'data' => $barangData,
            'pagination' => [
                'total' => $barang->total(),
                'per_page' => $barang->perPage(),
                'current_page' => $barang->currentPage(),
                'last_page' => $barang->lastPage(),
                'from' => $barang->firstItem(),
                'to' => $barang->lastItem(),
            ]
        ]);
    }


    public function show($kode_barang)
    {
        $barang = Barang::with([
            'kategori',
            'user',
            'diskon' // 🔹 Tambahkan relasi diskon
        ])->where('kode_barang', $kode_barang)->firstOrFail();

        // Siapkan respons
        $response = [
            'kode_barang' => $barang->kode_barang,
            'nama_barang' => $barang->nama_barang,
            'barcode' => $barang->barcode,
            'gambar' => $barang->gambar ? 'http://localhost:8000/storage/' . $barang->gambar : null, // 🔹 Mengubah path gambar menjadi URL
            'status' => $barang->status,
            'satuan' => $barang->satuan->nama_satuan,
            'harga_beli' => $barang->harga_beli,
            'harga_jual' => $barang->harga_jual,
            'harga_jual_diskon' => $barang->harga_jual_diskon,
            'profit_persen' => $barang->profit_persen,
            'stok' => $barang->stok,
            'kategori' => $barang->kategori->nama_kategori,
            'user' => $barang->user->nama,

        ];

        // 🔹 Tambahkan `nama_diskon` jika barang memiliki diskon
        if ($barang->diskon_id) {
            $response['nama_diskon'] = $barang->diskon->nama_diskon;
        }

        return response()->json($response);
    }




    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategori,id',
                'satuan_id' => 'required|exists:satuan,id',
                'diskon_id' => 'nullable|exists:diskon,id',
                'nama_barang' => 'required|string|max:255|unique:barang,nama_barang',
                'barcode' => 'required|string|max:50|unique:barang,barcode',
                'status' => 'required|in:Aktif,Tidak',
                'profit_persen' => 'nullable|numeric|min:0|max:100',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user_id = auth()->id();

            // Generate kode barang
            $kode_barang = $this->generateKodeBarang($validated['kategori_id']);

            // Upload gambar jika ada
            $gambarPath = null;
            if ($request->hasFile('gambar') && $request->file('gambar')->isValid()) {
                $gambarPath = $request->file('gambar')->store('barang_images', 'public');
            }

            $profit_persen = $validated['profit_persen'] ?? 10;

            // Simpan barang dengan diskon jika ada
            $barang = Barang::create([
                'kode_barang' => $kode_barang,
                'kategori_id' => $validated['kategori_id'],
                'user_id' => $user_id,
                'satuan_id' => $validated['satuan_id'],
                'diskon_id' => $validated['diskon_id'] ?? null,
                'barcode' => $validated['barcode'],
                'nama_barang' => $validated['nama_barang'],
                'status' => $validated['status'],
                'profit_persen' => $profit_persen,
                'harga_beli' => 0,
                'stok' => 0,
                'gambar' => $gambarPath,
            ]);

            return response()->json([
                'message' => 'Barang berhasil ditambahkan',
                'barang' => [
                    'kode_barang' => $barang->kode_barang,
                    'barcode' => $barang->barcode,
                    'nama_barang' => $barang->nama_barang,
                    'satuan' => $barang->satuan->nama_satuan ?? null,
                    'status' => $barang->status,
                    'profit_persen' => $barang->profit_persen,
                    'harga_jual' => $barang->harga_jual,
                    'harga_jual_diskon' => $barang->harga_jual_diskon,
                    'harga_beli' => $barang->harga_beli,
                    'stok' => $barang->stok,
                    'kategori' => $barang->kategori->nama_kategori ?? null,
                    'user' => $barang->user->nama ?? null,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menambahkan barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }









    public function update(Request $request, $kode_barang)
    {
        $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();

        // Validasi data termasuk gambar
        $validated = $request->validate([
            'kategori_id' => 'sometimes|required|exists:kategori,id',
            'satuan_id' => 'sometimes|required|exists:satuan,id',
            'diskon_id' => 'sometimes|nullable|exists:diskon,id',
            'nama_barang' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('barang', 'nama_barang')->ignore($barang->kode_barang, 'kode_barang')
            ],
            'barcode' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('barang', 'barcode')->ignore($barang->kode_barang, 'kode_barang')
            ],
            'status' => 'sometimes|required|in:Aktif,Tidak',
            'profit_persen' => 'sometimes|nullable|numeric|min:0|max:100',
            'gambar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Cek apakah gambar baru diunggah
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($barang->gambar && Storage::disk('public')->exists($barang->gambar)) {
                Storage::disk('public')->delete($barang->gambar);
            }

            // Simpan gambar baru
            $gambarPath = $request->file('gambar')->store('barang_images', 'public');
            $validated['gambar'] = $gambarPath;
        } else {
            // Jika tidak ada gambar baru, tetap gunakan gambar lama
            $validated['gambar'] = $barang->gambar;
        }

        // Debug: Cek apakah data valid sebelum update
        if (empty($validated)) {
            return response()->json([
                'message' => 'Tidak ada data yang dikirim untuk update'
            ], 400);
        }

        // Update barang
        $barang->update($validated);

        return response()->json([
            'message' => 'Barang berhasil diperbarui',
            'barang' => $barang->fresh()
        ]);
    }






    // ✅ DELETE: Soft delete barang
    public function destroy($kode_barang)
    {
        $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();
        $barang->delete();

        return response()->json(['message' => 'Barang berhasil dihapus']);
    }

    // ✅ Generate kode barang (BRG+Tahun+id kategori+no urut)
    private function generateKodeBarang($kategori_id)
    {
        $tahun = date('Y');

        // Ambil nomor urut terakhir termasuk yang sudah soft delete
        $lastBarang = Barang::withTrashed()
            ->where('kategori_id', $kategori_id)
            ->whereYear('created_at', $tahun)
            ->orderBy('kode_barang', 'desc')
            ->first();

        // Ambil no urut terakhir, jika tidak ada mulai dari 1
        $lastNumber = $lastBarang ? (int)substr($lastBarang->kode_barang, -4) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return "BRG{$tahun}{$kategori_id}{$newNumber}";
    }
}

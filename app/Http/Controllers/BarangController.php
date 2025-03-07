<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
// ✅ INDEX: Ambil daftar barang (paginate 10)
public function index()
{
    // Ambil data barang dengan kategori, user, dan vendor menggunakan pagination
    $barang = Barang::with(['kategori', 'user', 'vendor'])->paginate(10);

    // Ambil hanya data yang diperlukan dan ubah menjadi array
    $barangData = $barang->map(function ($item) {
        return [
            'kode_barang' => $item->kode_barang,
            'nama_barang' => $item->nama_barang,
            'status' => $item->status,
            'satuan' => $item->satuan->nama_satuan,
            'harga_jual' => $item->harga_jual,
            'profit_persen' => $item->profit_persen,
            'harga_beli' => $item->harga_beli,
            'stok' => $item->stok,
            'kategori' => $item->kategori->nama_kategori,
            'user' => $item->user->nama,
            'vendor' => $item->vendor->nama_vendor,
        ];
    });

    return response()->json([
        'data' => $barangData, // Mengembalikan data barang yang sudah diproses
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


    // ✅ SHOW: Tampilkan detail barang
    public function show($kode_barang)
    {
        $barang = Barang::with(['kategori', 'user', 'vendor'])
            ->where('kode_barang', $kode_barang)
            ->firstOrFail();

        return response()->json([
            'kode_barang' => $barang->kode_barang,
            'nama_barang' => $barang->nama_barang,
            'status' => $barang->status,
            'satuan' => $barang->satuan->nama_satuan,
            'harga_jual' => $barang->harga_jual,
            'profit_persen' => $barang->profit_persen,
            'harga_beli' => $barang->harga_beli,
            'stok' => $barang->stok,
            'kategori' => $barang->kategori->nama_kategori,
            'user' => $barang->user->nama,
            'vendor' => $barang->vendor->nama_vendor,
        ]);
    }

    // ✅ STORE: Tambah barang baru

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategori,id',
                'vendor_id' => 'required|exists:vendor,id',
                'satuan_id' => 'required|exists:satuan,id',
                'nama_barang' => [
                    'required', 'string', 'max:255',
                    Rule::unique('barang')->where(function ($query) use ($request) {
                        return $query->where('vendor_id', $request->vendor_id);
                    })
                ],
                'barcode' => 'required|string|max:50|unique:barang,barcode',
                'status' => 'required|in:aktif,tidak_aktif',
                'profit_persen' => 'nullable|numeric|min:0|max:100', // Bisa nullable (jika tidak diisi, default 10%)
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Ambil user yang sedang login
            $user_id = auth()->id();

            // Generate kode barang
            $kode_barang = $this->generateKodeBarang($validated['kategori_id']);

            // Handle file upload
            $gambarPath = null;
            if ($request->hasFile('gambar') && $request->file('gambar')->isValid()) {
                $gambarPath = $request->file('gambar')->store('barang_images', 'public');
            }

            // Set default profit_persen jika tidak dikirim
            $profit_persen = $validated['profit_persen'] ?? 10; // Default 10%

            // Create barang baru
            $barang = Barang::create([
                'kode_barang' => $kode_barang,
                'kategori_id' => $validated['kategori_id'],
                'user_id' => $user_id,
                'vendor_id' => $validated['vendor_id'],
                'satuan_id' => $validated['satuan_id'],
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
                    'harga_beli' => $barang->harga_beli,
                    'stok' => $barang->stok,
                    'kategori' => $barang->kategori->nama_kategori ?? null,
                    'user' => $barang->user->nama ?? null,
                    'vendor' => $barang->vendor->nama_vendor ?? null,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal menambahkan barang: ' . $e->getMessage()
            ], 500);
        }
    }




    // ✅ UPDATE: Edit data barang
    public function update(Request $request, $kode_barang)
    {
        $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();

        // Validasi input
        $validated = $request->validate([
            'nama_barang' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:aktif,tidak_aktif',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar
        ]);

        // Jika ada gambar baru, hapus gambar lama dan simpan gambar baru
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($barang->gambar) {
                Storage::disk('public')->delete($barang->gambar);
            }

            // Simpan gambar baru
            $gambarPath = $request->file('gambar')->store('barang_images', 'public');
            $validated['gambar'] = $gambarPath;  // Update gambar dengan path baru
        }

        // Update barang
        $barang->update($validated);

        return response()->json(['message' => 'Barang berhasil diperbarui', 'barang' => $barang]);
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

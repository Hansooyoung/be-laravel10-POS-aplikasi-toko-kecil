<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
    /**
     * Get paginated list of items with filters
     *
     * @param Request $request The HTTP request containing filters
     * @return \Illuminate\Http\JsonResponse Paginated list of items
     */
    public function index(Request $request)
    {
        // Base query with eager loading
        $query = Barang::with(['kategori', 'user', 'diskon']);

        // 🔹 Filter by category name if provided
        if ($request->has('nama_kategori') && !empty($request->nama_kategori)) {
            $query->whereHas('kategori', function ($q) use ($request) {
                $q->where('nama_kategori', $request->nama_kategori);
            });
        }

        // 🔹 Search by item name or barcode if search term provided
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('nama_barang', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('barcode', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Paginate results (10 items per page)
        $barang = $query->paginate(10);

        // Transform each item in the collection
        $barangData = $barang->map(function ($item) {
            return [
                'kode_barang' => $item->kode_barang,
                'barcode' => $item->barcode,
                'nama_barang' => $item->nama_barang,
                'gambar' => $item->gambar ? 'http://localhost:8000/storage/' . $item->gambar : null, // 🔹 Convert image path to URL
                'status' => $item->status,
                'satuan' => $item->satuan->nama_satuan,
                'profit_persen' => $item->profit_persen,
                'harga_jual' => $item->harga_jual,
                'harga_jual_diskon' => $item->harga_jual_diskon ?? 'Tidak Ada Diskon',
                'harga_beli' => 'Rp. ' . number_format($item->harga_beli, 0, ',', '.'), // Format price
                'stok' => $item->stok,
                'kategori' => $item->kategori->nama_kategori,
                'user' => $item->user->nama,
            ];
        });

        // Return response with pagination metadata
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

    /**
     * Get single item details
     *
     * @param string $kode_barang Item code to lookup
     * @return \Illuminate\Http\JsonResponse Item details
     */
    public function show($kode_barang)
    {
        // Find item with eager loaded relationships or fail
        $barang = Barang::with(['kategori', 'user', 'diskon'])
                      ->where('kode_barang', $kode_barang)
                      ->firstOrFail();

        // Prepare response data
        $response = [
            'kode_barang' => $barang->kode_barang,
            'nama_barang' => $barang->nama_barang,
            'barcode' => $barang->barcode,
            'gambar' => $barang->gambar ? 'http://localhost:8000/storage/' . $barang->gambar : null, // 🔹 Convert image path to URL
            'status' => $barang->status,
            'satuan' => $barang->satuan->nama_satuan,
            'harga_beli' => $barang->harga_beli,
            'harga_jual' => $barang->harga_jual,
            'harga_jual_diskon' => $barang->harga_jual_diskon ?? 'Tidak Ada Diskon',
            'profit_persen' => $barang->profit_persen,
            'stok' => $barang->stok,
            'kategori' => $barang->kategori->nama_kategori,
            'user' => $barang->user->nama,
        ];

        // Add discount name if discount exists
        if ($barang->diskon_id) {
            $response['nama_diskon'] = $barang->diskon->nama_diskon;
        }

        return response()->json($response);
    }

    /**
     * Create new item
     *
     * @param Request $request HTTP request with item data
     * @return \Illuminate\Http\JsonResponse Response with created item or errors
     */
    public function store(Request $request)
    {
        try {
            // Validate input data
            $validated = $request->validate([
                'kategori_id' => 'required|exists:kategori,id',
                'satuan_id' => 'required|exists:satuan,id',
                'diskon_id' => 'nullable|exists:diskon,id',
                'nama_barang' => 'required|string|max:255|unique:barang,nama_barang',
                'barcode' => 'required|string|max:50|unique:barang,barcode',
                'status' => 'required|in:Aktif,Tidak',
                'profit_persen' => 'nullable|numeric|min:0|max:100',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            ]);

            // Get authenticated user ID
            $user_id = auth()->id();

            // Generate unique item code
            $kode_barang = $this->generateKodeBarang($validated['kategori_id']);

            // Handle image upload if present
            $gambarPath = null;
            if ($request->hasFile('gambar') && $request->file('gambar')->isValid()) {
                $gambarPath = $request->file('gambar')->store('barang_images', 'public');
            }

            // Set default profit percentage if not provided
            $profit_persen = $validated['profit_persen'] ?? 10;

            // Create new item record
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
                'harga_beli' => 0, // Default values
                'stok' => 0,       // Default values
                'gambar' => $gambarPath,
            ]);

            // Return success response with created item data
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
            ], 201); // 201 Created status code

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Return generic error
            return response()->json([
                'message' => 'Gagal menambahkan barang',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Update existing item
     *
     * @param Request $request HTTP request with update data
     * @param string $kode_barang Item code to update
     * @return \Illuminate\Http\JsonResponse Response with updated item or errors
     */
    public function update(Request $request, $kode_barang)
    {
        // Find item or fail
        $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();

        // Validate input data (some fields are optional with 'sometimes')
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

        // Handle image update if new image provided
        if ($request->hasFile('gambar')) {
            // Delete old image if exists
            if ($barang->gambar && Storage::disk('public')->exists($barang->gambar)) {
                Storage::disk('public')->delete($barang->gambar);
            }

            // Store new image
            $gambarPath = $request->file('gambar')->store('barang_images', 'public');
            $validated['gambar'] = $gambarPath;
        } else {
            // Keep existing image if no new image provided
            $validated['gambar'] = $barang->gambar;
        }

        // Check if any data was provided for update
        if (empty($validated)) {
            return response()->json([
                'message' => 'Tidak ada data yang dikirim untuk update'
            ], 400); // 400 Bad Request
        }

        // Update item with validated data
        $barang->update($validated);

        // Return success response with refreshed item data
        return response()->json([
            'message' => 'Barang berhasil diperbarui',
            'barang' => $barang->fresh() // Get fresh data from database
        ]);
    }

    /**
     * Soft delete an item
     *
     * @param string $kode_barang Item code to delete
     * @return \Illuminate\Http\JsonResponse Success message
     */
    public function destroy($kode_barang)
    {
        // Find item or fail
        $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();

        // Soft delete the item
        $barang->delete();

        return response()->json(['message' => 'Barang berhasil dihapus']);
    }

    /**
     * Generate unique item code (BRG+Year+CategoryID+Sequence)
     *
     * @param int $kategori_id Category ID
     * @return string Generated item code
     */
    private function generateKodeBarang($kategori_id)
    {
        $tahun = date('Y'); // Current year

        // Get last item in this category/year (including soft deleted)
        $lastBarang = Barang::withTrashed()
            ->where('kategori_id', $kategori_id)
            ->whereYear('created_at', $tahun)
            ->orderBy('kode_barang', 'desc')
            ->first();

        // Extract sequence number or start from 1
        $lastNumber = $lastBarang ? (int)substr($lastBarang->kode_barang, -4) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT); // 4-digit sequence

        return "BRG{$tahun}{$kategori_id}{$newNumber}";
    }
}
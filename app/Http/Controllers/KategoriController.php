<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exports\ExportArray;
use App\Imports\KategoriImport;
use Maatwebsite\Excel\Facades\Excel;
class KategoriController extends Controller
{
    /**
     * Get paginated list of categories with optional search
     *
     * @param Request $request HTTP request containing optional search parameter
     * @return \Illuminate\Http\JsonResponse Paginated list of categories
     */
    public function index(Request $request)
    {
        // Initialize query builder
        $query = Kategori::query();

        // Add search filter if search term provided
        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama_kategori', 'LIKE', '%' . $request->search . '%');
        }

        // Paginate results (10 items per page)
        $kategori = $query->paginate(10);

        // Return response with paginated data
        return response()->json([
            'message' => 'Data kategori berhasil diambil', // Success message
            'data' => $kategori                          // Paginated category data
        ]);
    }

    /**
     * Create a new category
     *
     * @param Request $request HTTP request containing category data
     * @return \Illuminate\Http\JsonResponse Created category data
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori', // Unique category name
        ]);

        // Create new category record
        $kategori = Kategori::create($validated);

        // Return success response with created category
        return response()->json([
            'message' => 'Kategori berhasil ditambahkan', // Success message
            'data' => $kategori                         // Created category data
        ], Response::HTTP_CREATED); // HTTP 201 Created status
    }

    /**
     * Get specific category details
     *
     * @param int $id Category ID
     * @return \Illuminate\Http\JsonResponse Category details or error
     */
    public function show($id)
    {
        // Find category by ID
        $kategori = Kategori::find($id);

        // Return 404 if category not found
        if (!$kategori) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan' // Not found message
            ], Response::HTTP_NOT_FOUND); // HTTP 404
        }

        // Return category details
        return response()->json([
            'message' => 'Kategori berhasil ditemukan', // Success message
            'data' => $kategori                       // Category data
        ], Response::HTTP_OK); // HTTP 200
    }

    /**
     * Update existing category
     *
     * @param Request $request HTTP request with update data
     * @param int $id Category ID
     * @return \Illuminate\Http\JsonResponse Updated category data
     */
    public function update(Request $request, $id)
    {
        // Find category by ID
        $kategori = Kategori::find($id);

        // Return 404 if category not found
        if (!$kategori) {
            return response()->json([
                'message' => 'Kategori tidak ditemukan' // Not found message
            ], Response::HTTP_NOT_FOUND); // HTTP 404
        }

        // Validate incoming request data
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $kategori->id, // Unique name except current record
        ]);

        // Update category with validated data
        $kategori->update($validated);
        $kategori->refresh(); // Refresh model to get updated attributes from database

        // Return success response with updated category
        return response()->json([
            'message' => 'Kategori berhasil diperbarui', // Success message
            'data' => $kategori                        // Updated category data
        ]);
    }

    /**
     * Delete a category with relationship check
     *
     * @param int $id Category ID
     * @return \Illuminate\Http\JsonResponse Success or error message
     */
    public function destroy($id)
    {
        // Find category by ID
        $kategori = Kategori::find($id);

        // Return 404 if category not found
        if (!$kategori) {
            return response()->json(
                ['message' => 'Kategori tidak ditemukan.'], // Not found message
                Response::HTTP_NOT_FOUND                   // HTTP 404
            );
        }

        // Check if category has related items
        if ($kategori->barang()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan.'
            ], Response::HTTP_BAD_REQUEST); // HTTP 400
        }

        // Delete the category
        $kategori->delete();

        // Return success response
        return response()->json(
            ['message' => 'Kategori berhasil dihapus.'], // Success message
            Response::HTTP_OK                            // HTTP 200
        );
    }
    public function export(Request $request, $format)
    {
        // Get data from index method
        $dataRaw = json_decode($this->index($request)->getContent(), true);
        $data = $dataRaw['data']['data'] ?? [];

        // Prepare data for export
        $exportData = [
            // Header row
            ['ID Kategori', 'Nama Kategori', 'Dibuat Pada', 'Diperbarui Pada'],

            // Data rows
            ...array_map(function ($item) {
                return [
                    $item['id'] ?? 'N/A',
                    $item['nama_kategori'] ?? 'N/A',
                    $item['created_at'] ?? '-',
                    $item['updated_at'] ?? '-'
                ];
            }, $data)
        ];

        $filename = 'laporan_kategori_' . date('Ymd_His');

        if ($format === 'excel') {
            return Excel::download(
                new ExportArray($exportData),
                $filename . '.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Excel::download(
                new ExportArray($exportData),
                $filename . '.pdf',
                \Maatwebsite\Excel\Excel::DOMPDF
            );
        }

        return response()->json(['message' => 'Format tidak didukung'], 400);
    }

    /**
     * Import categories from Excel file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new KategoriImport, $request->file('file'));

            return response()->json([
                'message' => 'Data kategori berhasil diimpor'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat import data',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

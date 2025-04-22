<?php

namespace App\Http\Controllers;

use App\Models\Diskon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiskonController extends Controller
{
    /**
     * Get paginated list of discounts with optional search
     *
     * @param Request $request HTTP request containing optional search parameter
     * @return \Illuminate\Http\JsonResponse Paginated list of discounts
     */
    public function index(Request $request)
    {
        // Initialize query builder
        $query = Diskon::query();

        // Add search filter if search term provided
        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama_diskon', 'LIKE', '%' . $request->search . '%');
        }

        // Paginate results (10 items per page)
        $diskon = $query->paginate(10);

        // Return response with paginated data
        return response()->json([
            'message' => 'Data diskon berhasil diambil', // Success message
            'data' => $diskon                           // Paginated discount data
        ]);
    }

    /**
     * Create a new discount
     *
     * @param Request $request HTTP request containing discount data
     * @return \Illuminate\Http\JsonResponse Created discount data
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'nama_diskon' => 'required|string|max:255|unique:diskon,nama_diskon', // Unique discount name
            'jenis_diskon' => 'required|string|max:255',                          // Discount type
            'nilai_diskon' => 'required|numeric|min:0',                           // Positive numeric value
            'tanggal_mulai' => 'required|date',                                   // Start date
            'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',   // End date must be >= start date
        ]);

        // Create new discount record
        $diskon = Diskon::create($validated);

        // Return success response with created discount
        return response()->json([
            'message' => 'Diskon berhasil ditambahkan', // Success message
            'data' => $diskon                         // Created discount data
        ], 201); // HTTP 201 Created status
    }

    /**
     * Get specific discount details
     *
     * @param int $id Discount ID
     * @return \Illuminate\Http\JsonResponse Discount details or error
     */
    public function show($id)
    {
        // Find discount by ID
        $diskon = Diskon::find($id);

        // Return 404 if discount not found
        if (!$diskon) {
            return response()->json([
                'message' => 'Diskon tidak ditemukan' // Not found message
            ], Response::HTTP_NOT_FOUND); // HTTP 404
        }

        // Return discount details
        return response()->json([
            'message' => 'Diskon berhasil ditemukan', // Success message
            'data' => $diskon                       // Discount data
        ], Response::HTTP_OK); // HTTP 200
    }

    /**
     * Update existing discount
     *
     * @param Request $request HTTP request with update data
     * @param Diskon $diskon Discount model (route model binding)
     * @return \Illuminate\Http\JsonResponse Updated discount data
     */
    public function update(Request $request, $id)
    {
        // Cari diskon berdasarkan ID
        $diskon = Diskon::find($id);

        // Kalau tidak ditemukan, return 404
        if (!$diskon) {
            return response()->json([
                'message' => 'Diskon tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        // Validasi input
        $validated = $request->validate([
            'nama_diskon'       => 'required|string|max:255|unique:diskon,nama_diskon,' . $diskon->id,
            'jenis_diskon'      => 'required|string|max:255',
            'nilai_diskon'      => 'required|numeric|min:0',
            'tanggal_mulai'     => 'required|date',
            'tanggal_berakhir'  => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        // Update diskon
        $diskon->update($validated);
        $diskon->refresh(); // Ambil data terbaru dari DB

        // Return response
        return response()->json([
            'message' => 'Diskon berhasil diperbarui',
            'data'    => $diskon
        ]);
    }

    /**
     * Delete a discount
     *
     * @param int $id Discount ID
     * @return \Illuminate\Http\JsonResponse Success or error message
     */
    public function destroy($id)
    {
        // Find discount by ID
        $diskon = Diskon::find($id);

        // Return 404 if discount not found
        if (!$diskon) {
            return response()->json(
                ['message' => 'Diskon tidak ditemukan.'], // Not found message
                Response::HTTP_NOT_FOUND                  // HTTP 404
            );
        }

        // Check discount status based on expiration date
        $status = now()->greaterThan($diskon->tanggal_berakhir)
            ? 'Kadaluarsa'  // Expired if current date > end date
            : 'Aktif';      // Active otherwise

        // Prevent deletion if active and has related items
        if ($status === 'Aktif' && $diskon->barang()->exists()) {
            return response()->json([
                'message' => 'Diskon tidak dapat dihapus karena masih aktif dan terikat pada barang.'
            ], Response::HTTP_BAD_REQUEST); // HTTP 400
        }

        // Soft delete the discount
        $diskon->delete();

        // Return success response
        return response()->json(
            ['message' => 'Diskon berhasil dihapus.'], // Success message
            Response::HTTP_OK                          // HTTP 200
        );
    }
}
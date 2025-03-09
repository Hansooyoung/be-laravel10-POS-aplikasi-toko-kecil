<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    public function index()
{
    $pembelian = Pembelian::with([
        'vendor:id,nama_vendor',
        'user:id,nama',
        'detailPembelian.barang' => function ($query) {
            $query->select('kode_barang', 'barcode', 'nama_barang', 'harga_beli', 'profit_persen', 'diskon_id')
                  ->with('diskon:id,jenis_diskon,nilai_diskon');
        }
    ])
    ->whereHas('vendor', function ($query) {
        $query->withTrashed();
    })
    ->paginate(10);

    // Format response
    $formattedData = $pembelian->map(function ($item) {
        return [
            'id' => $item->id,
            'tanggal_pembelian' => $item->tanggal_pembelian,
            'tanggal_masuk' => $item->tanggal_masuk ?? 'Belum Masuk',
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'total' => 'Rp. ' . number_format($item->total, 0, ',', '.'),
            'nama_vendor' => $item->vendor->nama_vendor ?? null,
            'nama_user' => $item->user->nama ?? null,
            'detail_pembelian' => $item->detailPembelian->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'pembelian_id' => $detail->pembelian_id,
                    'kode_barang' => $detail->kode_barang,
                    'harga_beli' => $detail->harga_beli,
                    'jumlah' => $detail->jumlah,
                    'created_at' => $detail->created_at,
                    'updated_at' => $detail->updated_at,
                    'sub_total' => $detail->sub_total,
                    'barang' => [
                        'kode_barang' => $detail->barang->kode_barang,
                        'barcode' => $detail->barang->barcode,
                        'nama_barang' => $detail->barang->nama_barang,
                        'harga_jual' => $detail->barang->harga_jual ?? 0,
                        'harga_jual_diskon' => $detail->barang->harga_jual_diskon,
                        'diskon' => $detail->barang->diskon ? [
                            'jenis' => $detail->barang->diskon->jenis_diskon,
                            'nilai' => $detail->barang->diskon->nilai_diskon
                        ] : null,
                    ]
                ];
            }),
        ];
    });

    return response()->json([
        'data' => $formattedData,
        'pagination' => [
            'total' => $pembelian->total(),
            'per_page' => $pembelian->perPage(),
            'current_page' => $pembelian->currentPage(),
            'last_page' => $pembelian->lastPage(),
            'from' => $pembelian->firstItem(),
            'to' => $pembelian->lastItem(),
        ]
    ]);
}






    public function show($id)
    {
        $pembelian = Pembelian::with(['detailPembelian.barang'])->findOrFail($id);


        $detailPembelian = $pembelian->detailPembelian()->paginate(10);


        return response()->json([
            'id' => $pembelian->id,
            'tanggal_masuk' => $pembelian->tanggal_masuk ?? 'Belum Masuk', // Jika null, tampilkan "Belum Masuk"
            'tanggal_pembelian' => $pembelian->tanggal_pembelian,
            'user_id' => $pembelian->user_id,
            'vendor_id' => $pembelian->vendor_id,
            'created_at' => $pembelian->created_at,
            'updated_at' => $pembelian->updated_at,
            'total' => 'Rp. ' . number_format($pembelian->total, 0, ',', '.'),
            'detail_pembelian' => $detailPembelian->through(function ($detail) {
                return [
                    'id' => $detail->id,
                    'pembelian_id' => $detail->pembelian_id,
                    'kode_barang' => $detail->kode_barang,
                    'harga_beli' => 'Rp. ' . number_format($detail->harga_beli, 0, ',', '.'),
                    'jumlah' => $detail->jumlah,
                    'sub_total' => 'Rp. ' . number_format($detail->sub_total, 0, ',', '.'),
                    'nama_barang' => $detail->barang->nama_barang,
                ];
            }),
            'pagination' => [
                'total' => $detailPembelian->total(),
                'per_page' => $detailPembelian->perPage(),
                'current_page' => $detailPembelian->currentPage(),
                'last_page' => $detailPembelian->lastPage(),
                'from' => $detailPembelian->firstItem(),
                'to' => $detailPembelian->lastItem(),
            ],
        ]);
    }

    public function updateTanggalMasuk(Request $request, $id)
    {
        $request->validate([
            'tanggal_masuk' => 'required|date',
        ]);

        $pembelian = Pembelian::findOrFail($id);

        // Cek apakah tanggal_masuk masih null atau "Belum Masuk"
        if (!is_null($pembelian->tanggal_masuk) && $pembelian->tanggal_masuk !== 'Belum Masuk') {
            return response()->json([
                'error' => 'Tanggal masuk sudah ditetapkan dan tidak dapat diubah.',
            ], 400);
        }

        // Perbarui tanggal_masuk
        $pembelian->update([
            'tanggal_masuk' => $request->tanggal_masuk,
        ]);

        return response()->json([
            'message' => 'Tanggal masuk berhasil diperbarui.',
            'pembelian' => $pembelian,
        ]);
    }


    /**
     * Simpan data pembelian beserta detailnya.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'vendor_id' => 'required|exists:vendor,id',
                'barang' => [
                    'required', 'array',
                    function ($attribute, $value, $fail) {
                        if (empty($value)) {
                            $fail('Minimal satu barang harus dipilih.');
                        }

                        $barcodes = array_column($value, 'barcode');
                        if (count($barcodes) !== count(array_unique($barcodes))) {
                            $fail('Barcode tidak boleh ada yang sama dalam satu transaksi.');
                        }
                    }
                ],
                'barang.*.barcode' => 'required|string',
                'barang.*.harga_beli' => 'required|numeric|min:1',
                'barang.*.jumlah' => 'required|integer|min:1',
                'tanggal_masuk' => 'nullable|date',
            ], [
                'vendor_id.required' => 'Vendor harus dipilih.',
                'vendor_id.exists' => 'Vendor yang dipilih tidak valid.',
                'barang.required' => 'Minimal satu barang harus dipilih.',
                'barang.array' => 'Format barang tidak valid.',
                'barang.*.barcode.required' => 'Barcode barang harus diisi.',
                'barang.*.harga_beli.required' => 'Harga beli harus diisi.',
                'barang.*.harga_beli.numeric' => 'Harga beli harus berupa angka.',
                'barang.*.harga_beli.min' => 'Harga beli minimal adalah 1.',
                'barang.*.jumlah.required' => 'Jumlah barang harus diisi.',
                'barang.*.jumlah.integer' => 'Jumlah barang harus berupa angka.',
                'barang.*.jumlah.min' => 'Jumlah barang minimal adalah 1.',
            ]);

            $user_id = auth()->id();
            $tanggal_pembelian = now();
            $tanggal_masuk = $validated['tanggal_masuk'] ?? null;

            $pembelian = Pembelian::create([
                'vendor_id' => $validated['vendor_id'],
                'tanggal_pembelian' => $tanggal_pembelian,
                'tanggal_masuk' => $tanggal_masuk,
                'user_id' => $user_id,
            ]);

            // Ambil semua barang berdasarkan barcode tanpa filter vendor_id
            $barcodes = array_column($validated['barang'], 'barcode');
            $barangList = Barang::whereIn('barcode', $barcodes)->get()->keyBy('barcode');

            foreach ($validated['barang'] as $barangData) {
                if (!isset($barangList[$barangData['barcode']])) {
                    throw new \Exception("Barang dengan barcode {$barangData['barcode']} tidak ditemukan.");
                }

                $barang = $barangList[$barangData['barcode']];

                // Simpan detail pembelian
                $pembelian->detailPembelian()->create([
                    'kode_barang' => $barang->kode_barang,
                    'harga_beli' => $barangData['harga_beli'],
                    'jumlah' => $barangData['jumlah'],
                ]);

                // Update stok dan harga beli barang
                $barang->stok += $barangData['jumlah'];
                $barang->harga_beli = $barangData['harga_beli'];
                $barang->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Pembelian berhasil ditambahkan',
                'pembelian' => $pembelian,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal menambahkan pembelian: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menambahkan pembelian: ' . $e->getMessage()
            ], 500);
        }
    }






}

<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{

    public function index()
    {
        $penjualan = Penjualan::with([
            'member:id,nama_member',
            'voucher:id,nama_voucher',
            'user:id,nama',
            'detailPenjualan.barang' => function ($query) {
                $query->select('kode_barang', 'barcode', 'nama_barang');
            }
        ])->paginate(10);

        $formattedData = $penjualan->map(function ($item) {
            return [
                'id' => $item->id,
                'tanggal_penjualan' => $item->tanggal_penjualan,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'tunai' => $item->tunai,
                'kembalian' => $item->kembalian,
                'total_penjualan' => $item->total_penjualan,
                'total_keuntungan' => $item->total_keuntungan,
                // 'total_penjualan' => 'Rp. ' . number_format($item->total_penjualan, 0, ',', '.'),
                // 'total_keuntungan' => 'Rp. ' . number_format($item->total_keuntungan, 0, ',', '.'),
                'nama_member' => $item->member->nama_member ?? 'Tidak Memiliki Member',
                'nama_user' => $item->user->nama ?? null,
                'detail_penjualan' => $item->detailPenjualan->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'penjualan_id' => $detail->penjualan_id,
                        'kode_barang' => $detail->kode_barang,
                        'harga_jual' => 'Rp. ' . number_format($detail->harga_jual, 0, ',', '.'),
                        'jumlah' => $detail->jumlah,
                        'sub_total' => 'Rp. ' . number_format($detail->sub_total, 0, ',', '.'),
                        'keuntungan' => 'Rp. ' . number_format($detail->keuntungan, 0, ',', '.'),
                        'nama_barang' => $detail->barang->nama_barang,
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $formattedData,
            'pagination' => [
                'total' => $penjualan->total(),
                'per_page' => $penjualan->perPage(),
                'current_page' => $penjualan->currentPage(),
                'last_page' => $penjualan->lastPage(),
                'from' => $penjualan->firstItem(),
                'to' => $penjualan->lastItem(),
            ]
        ]);
    }


    public function show($id)
    {
        $penjualan = Penjualan::with(['detailPenjualan.barang'])->findOrFail($id);
        $detailPenjualan = $penjualan->detailPenjualan()->paginate(10);

        return response()->json([
            'id' => $penjualan->id,
            'tanggal_penjualan' => $penjualan->tanggal_penjualan,
            'total_penjualan' => $penjualan->total_penjualan,
            'total_keuntungan' => $penjualan->total_keuntungan,
            'detail_penjualan' => $detailPenjualan->through(function ($detail) {
                return [
                    'id' => $detail->id,
                    'kode_barang' => $detail->kode_barang,
                    'harga_jual' => $detail->harga_jual,
                    'jumlah' => $detail->jumlah,
                    'sub_total' => $detail->sub_total,
                    'keuntungan' => $detail->keuntungan,
                    'diskon' => $detail->diskon,
                    'nama_barang' => $detail->barang->nama_barang,
                ];
            }),
            'pagination' => [
                'total' => $detailPenjualan->total(),
                'per_page' => $detailPenjualan->perPage(),
                'current_page' => $detailPenjualan->currentPage(),
                'last_page' => $detailPenjualan->lastPage(),
                'from' => $detailPenjualan->firstItem(),
                'to' => $detailPenjualan->lastItem(),
            ],
        ]);
    }

    public function struk(Request $request, $id)
    {
        $penjualan = Penjualan::with([
            'detailPenjualan.barang',
            'member',
            'voucher',
            'user'
        ])->findOrFail($id);

        // Hitung total harga jika tidak ada di database
        $totalHarga = $penjualan->total_penjualan ?? $penjualan->detailPenjualan->sum(function ($detail) {
            return $detail->harga_jual * $detail->jumlah;
        });

        return response()->json([
            'id' => $penjualan->id,
            'tanggal_penjualan' => $penjualan->tanggal_penjualan,
            'nama_kasir' => $penjualan->user?->name,
            'total_penjualan' => $totalHarga,
            'total_keuntungan' => $penjualan->total_keuntungan,
            'nama_member' => $penjualan->member?->nama,
            'nama_voucher' => $penjualan->voucher?->nama_voucher,
            'tunai' => $request->input('tunai', 0), // Ambil dari frontend
            'kembalian' => $request->input('kembalian', 0), // Ambil dari frontend
            'detail_penjualan' => $penjualan->detailPenjualan->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'kode_barang' => $detail->kode_barang,
                    'harga_jual' => $detail->harga_jual,
                    'jumlah' => $detail->jumlah,
                    'sub_total' => $detail->sub_total,
                    'keuntungan' => $detail->keuntungan,
                    'diskon' => $detail->diskon,
                    'nama_barang' => $detail->barang->nama_barang,
                ];
            }),
        ]);
    }







    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'member_id' => 'nullable|exists:member,id',
                'voucher_id' => 'nullable|exists:voucher,id',
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
                'barang.*.jumlah' => 'required|integer|min:1',  
                'tanggal_penjualan' => 'nullable|date',
                'tunai' => 'required|numeric|min:0',
            ], [
                'tunai.required' => 'Uang tunai harus diisi.',
                'tunai.numeric' => 'Uang tunai harus berupa angka.',
                'tunai.min' => 'Uang tunai tidak boleh negatif.',
            ]);

            $userId = auth()->id();
            $tanggal_penjualan = now();
            $tanggal_masuk = $validated['tanggal_penjualan'] ?? null;

            $totalHarga = 0;
            $barcodes = array_column($validated['barang'], 'barcode');
            $barangList = Barang::whereIn('barcode', $barcodes)->get()->keyBy('barcode');

            foreach ($validated['barang'] as $barangData) {
                $barang = $barangList[$barangData['barcode']] ?? null;
                if (!$barang) {
                    throw new \Exception("Barang dengan barcode {$barangData['barcode']} tidak ditemukan.");
                }

                if ($barang->stok < $barangData['jumlah']) {
                    throw new \Exception("Stok barang {$barang->nama_barang} tidak mencukupi.");
                }

                $hargaJualFinal = $barang->harga_jual_diskon ?? $barang->harga_jual;
                $totalHarga += $hargaJualFinal * $barangData['jumlah'];
            }

            if ($validated['tunai'] < $totalHarga) {
                throw new \Exception("Uang tunai tidak cukup. Total belanja: Rp" . number_format($totalHarga, 0, ',', '.'));
            }

            $penjualan = Penjualan::create([
                'tanggal_penjualan' => $tanggal_penjualan,
                'tanggal_masuk'     => $tanggal_masuk,
                'user_id'           => $userId,
                'member_id'         => $validated['member_id'] ?? null,
                'voucher_id'        => $validated['voucher_id'] ?? null,
                'total_penjualan'   => $totalHarga,
            ]);

            foreach ($validated['barang'] as $barangData) {
                $barang = $barangList[$barangData['barcode']];
                $hargaJualFinal = $barang->harga_jual_diskon ?? $barang->harga_jual;

                $penjualan->detailPenjualan()->create([
                    'kode_barang' => $barang->kode_barang,
                    'harga_jual'  => $hargaJualFinal,
                    'harga_beli'  => $barang->harga_beli,
                    'jumlah'      => $barangData['jumlah'],
                ]);

                $barang->stok -= $barangData['jumlah'];
                $barang->save();
            }

            $penjualan->tunai = $validated['tunai'];

            DB::commit();

            return response()->json([
                'message'     => 'Penjualan berhasil ditambahkan',
                'penjualan'   => $penjualan,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal menambahkan penjualan: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal menambahkan penjualan: ' . $e->getMessage()
            ], 500);
        }
    }
}

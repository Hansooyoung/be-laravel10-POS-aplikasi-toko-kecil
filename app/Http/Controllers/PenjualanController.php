<?php

namespace App\Http\Controllers;

use App\Models\HistoryVoucher;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Barang;
use App\Models\Voucher;
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

        // Ambil total_penjualan_setelah_diskon jika ada, jika tidak gunakan total_penjualan
        $totalHarga = $penjualan->total_penjualan_setelah_diskon ?? $penjualan->total_penjualan;

        return response()->json([
            'id' => $penjualan->id,
            'tanggal_penjualan' => $penjualan->tanggal_penjualan,
            'nama_kasir' => $penjualan->user?->name,
            'total_penjualan' => $totalHarga, // Menggunakan total setelah diskon jika ada
            'total_keuntungan' => $penjualan->total_keuntungan,
            'nama_member' => $penjualan->member?->nama,
            'nama_voucher' => $penjualan->voucher?->nama_voucher,
            'tunai' => $request->input('tunai', 0),
            'kembalian' => $request->input('kembalian', 0),
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
            $voucherId = null;

            // Ambil barang berdasarkan barcode
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
            }

            // Simpan transaksi awal (sementara)
            $penjualan = Penjualan::create([
                'tanggal_penjualan' => $tanggal_penjualan,
                'tanggal_masuk'     => $tanggal_masuk,
                'user_id'           => $userId,
                'member_id'         => $validated['member_id'] ?? null,
                'voucher_id'        => null, // Voucher ditentukan nanti setelah cek total
            ]);

            // Simpan detail penjualan & kurangi stok barang
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

            // Cek jika member memiliki voucher yang tersedia
            if (!empty($validated['member_id'])) {
                $historyVoucher = HistoryVoucher::where('member_id', $validated['member_id'])
                    ->whereNull('tanggal_digunakan')
                    ->first();

                if ($historyVoucher) {
                    $voucher = Voucher::find($historyVoucher->voucher_id);

                    if ($voucher) {
                        // Cek apakah total belanja memenuhi `min_pembelian`
                        if ($penjualan->total_penjualan < $voucher->min_pembelian) {
                            throw new \Exception("Minimal pembelian untuk voucher ini adalah Rp" . number_format($voucher->min_pembelian, 0, ',', '.') . ". Total belanja Anda: Rp" . number_format($penjualan->total_penjualan, 0, ',', '.'));
                        }

                        $voucherId = $voucher->id;
                    }
                }
            }

            // Set voucher_id jika valid
            if ($voucherId) {
                $penjualan->voucher_id = $voucherId;
                $penjualan->save();
            }

            // Validasi tunai setelah diskon
            if ($validated['tunai'] < $penjualan->total_penjualan_setelah_diskon) {
                throw new \Exception("Uang tunai tidak cukup. Total belanja setelah diskon: Rp" . number_format($penjualan->total_penjualan_setelah_diskon, 0, ',', '.'));
            }

            // Simpan tunai di model
            $penjualan->tunai = $validated['tunai'];

            // Tandai voucher sebagai digunakan jika ada
            if ($voucherId) {
                $historyVoucher->update(['tanggal_digunakan' => now()]);
            }

            DB::commit();

            return response()->json([
                'message'     => 'Penjualan berhasil ditambahkan',
                'penjualan'   => $penjualan->load('voucher'),
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

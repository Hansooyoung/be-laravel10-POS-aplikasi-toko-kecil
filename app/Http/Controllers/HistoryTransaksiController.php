<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use Illuminate\Support\Facades\Auth;

class HistoryTransaksiController extends Controller
{
    public function pembelian()
    {
        // Get the currently authenticated user's ID
        $userId = Auth::id();

        // Get sorting parameters from request
        $sortField = request()->input('sort', 'tanggal_pembelian');
        $sortDirection = request()->input('direction', 'desc');

        // Validate sort field
        $validSortFields = ['tanggal_pembelian', 'tanggal_masuk', 'total', 'created_at'];
        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'tanggal_pembelian';
        }

        // Validate direction
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $pembelian = Pembelian::with([
                'vendor:id,nama_vendor',
                'user:id,nama',
                'detailPembelian.barang' => function ($query) {
                    $query->select('kode_barang', 'barcode', 'nama_barang', 'harga_beli', 'profit_persen', 'diskon_id')
                          ->with('diskon:id,jenis_diskon,nilai_diskon');
                }
            ])
            ->where('user_id', $userId)
            ->whereHas('vendor', function ($query) {
                $query->withTrashed();
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);

        // Format response
        $formattedData = $pembelian->map(function ($item) {
            return [
                'id' => $item->id,
                'tanggal_pembelian' => $item->tanggal_pembelian,
                'tanggal_masuk' => $item->tanggal_masuk,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'total' => $item->total,
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
            ],
            'sort' => [
                'field' => $sortField,
                'direction' => $sortDirection
            ]
        ]);
    }
    public function penjualan()
{
    // Get the currently authenticated user's ID
    $userId = Auth::id();

    // Ambil parameter sorting dari request
    $sortField = request()->input('sort', 'tanggal_penjualan');
    $sortDirection = request()->input('direction', 'desc');

    // Validasi field sorting
    $validSortFields = ['tanggal_penjualan', 'total_penjualan', 'total_keuntungan'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'tanggal_penjualan';
    }

    // Validasi direction
    $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

    $penjualan = Penjualan::with([
            'member:id,nama_member',
            'voucher:id,nama_voucher',
            'user:id,nama',
            'detailPenjualan.barang' => function ($query) {
                $query->select('kode_barang', 'barcode', 'nama_barang');
            }
        ])
        ->where('user_id', $userId) // Filter by logged-in user's ID
        ->orderBy($sortField, $sortDirection)
        ->paginate(10);

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
        ],
        'sort' => [
            'field' => $sortField,
            'direction' => $sortDirection
        ]
    ]);
}
}
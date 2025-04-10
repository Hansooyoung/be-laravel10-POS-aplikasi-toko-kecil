<?php

namespace App\Http\Controllers;

use App\Models\DetailPenjualan;
use App\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Penjualan;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function laporanPenjualan(Request $request)
    {
        $query = Penjualan::with(['member', 'user', 'detailPenjualan']);

        $periode = $request->periode;

        if ($periode) {
            if ($periode === 'harian' && $request->has('tanggal')) {
                $query->whereDate('tanggal_penjualan', $request->tanggal);
            } elseif ($periode === 'bulanan' && $request->has(['bulan', 'tahun'])) {
                $query->whereYear('tanggal_penjualan', $request->tahun)
                      ->whereMonth('tanggal_penjualan', $request->bulan);
            } elseif ($periode === 'tahunan' && $request->has('tahun')) {
                $query->whereYear('tanggal_penjualan', $request->tahun);
            } elseif ($periode === 'custom' && $request->has(['start_date', 'end_date'])) {
                $query->whereBetween('tanggal_penjualan', [$request->start_date, $request->end_date]);
            }
        }

        $penjualan = $query->paginate($request->input('per_page', 10));

        // Hitung total penjualan dan keuntungan
        $total_penjualan = $penjualan->getCollection()->sum('total_penjualan');
        $total_keuntungan = $penjualan->getCollection()->sum('total_keuntungan');

        // Hitung total barang yang terjual
        $total_barang = $penjualan->getCollection()->sum(function ($penjualan) {
            return $penjualan->detailPenjualan->sum('jumlah');
        });

        return response()->json([
            'success' => true,
            'data' => $penjualan->items(),
            'pagination' => [
                'current_page' => $penjualan->currentPage(),
                'per_page' => $penjualan->perPage(),
                'total' => $penjualan->total(),
                'last_page' => $penjualan->lastPage()
            ],
            'total_penjualan' => $total_penjualan,
            'total_keuntungan' => $total_keuntungan,
            'total_barang' => $total_barang
        ]);
    }

    public function laporanPembelian(Request $request)
    {
        $query = Pembelian::with(['vendor', 'user', 'detailPembelian']);

        $periode = $request->periode;

        if ($periode) {
            if ($periode === 'harian' && $request->has('tanggal')) {
                $query->whereDate('tanggal_pembelian', $request->tanggal);
            } elseif ($periode === 'bulanan' && $request->has(['bulan', 'tahun'])) {
                $query->whereYear('tanggal_pembelian', $request->tahun)
                      ->whereMonth('tanggal_pembelian', $request->bulan);
            } elseif ($periode === 'tahunan' && $request->has('tahun')) {
                $query->whereYear('tanggal_pembelian', $request->tahun);
            } elseif ($periode === 'custom' && $request->has(['start_date', 'end_date'])) {
                $query->whereBetween('tanggal_pembelian', [$request->start_date, $request->end_date]);
            }
        }

        $pembelian = $query->paginate($request->input('per_page', 10));

        // Hitung total pembelian
        $total_pembelian = $pembelian->getCollection()->sum('total');

        // Hitung total barang yang dibeli
        $total_barang = $pembelian->getCollection()->sum(function ($pembelian) {
            return $pembelian->detailPembelian->sum('jumlah');
        });

        return response()->json([
            'success' => true,
            'data' => $pembelian->items(),
            'pagination' => [
                'current_page' => $pembelian->currentPage(),
                'per_page' => $pembelian->perPage(),
                'total' => $pembelian->total(),
                'last_page' => $pembelian->lastPage()
            ],
            'total_pembelian' => $total_pembelian,
            'total_barang' => $total_barang
        ]);
    }

    public function LaporanPenjualanBarang(Request $request)
    {
        $query = DetailPenjualan::with('barang')
            ->selectRaw('
                kode_barang,
                SUM(jumlah) as total_terjual,
                SUM((harga_jual - harga_beli) * jumlah) as total_keuntungan
            ')
            ->whereHas('penjualan', function ($q) use ($request) {
                if ($request->periode === 'harian' && $request->has('tanggal')) {
                    $q->whereDate('tanggal_penjualan', $request->tanggal);
                } elseif ($request->periode === 'bulanan' && $request->has(['bulan', 'tahun'])) {
                    $q->whereYear('tanggal_penjualan', $request->tahun)
                      ->whereMonth('tanggal_penjualan', $request->bulan);
                } elseif ($request->periode === 'tahunan' && $request->has('tahun')) {
                    $q->whereYear('tanggal_penjualan', $request->tahun);
                } elseif ($request->periode === 'custom' && $request->has(['start_date', 'end_date'])) {
                    $q->whereBetween('tanggal_penjualan', [$request->start_date, $request->end_date]);
                }
            })
            ->groupBy('kode_barang');

        // 🔹 Sorting berdasarkan request (default: total_keuntungan DESC)
        $allowedSortColumns = ['total_terjual', 'total_keuntungan'];
        $sortBy = $request->get('sort_by', 'total_keuntungan'); // Default sorting by total_keuntungan
        $sortOrder = $request->get('sort_order', 'desc'); // Default descending

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('total_keuntungan'); // Default sorting jika tidak valid
        }

        // 🔹 Ambil Data dan Mapping
        $data = $query->get()->map(function ($detail) {
            return [
                'kode_barang' => $detail->kode_barang,
                'nama_barang' => $detail->barang->nama_barang ?? 'Tidak Diketahui',
                'total_terjual' => $detail->total_terjual,
                'total_keuntungan' => $detail->total_keuntungan,
            ];
        });

        // 🔹 Hitung Total Keseluruhan
        $total_keuntungan = $data->sum('total_keuntungan');
        $total_terjual = $data->sum('total_terjual');

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'total_keuntungan' => $total_keuntungan,
            'total_terjual' => $total_terjual,
        ]);
    }

    public function grafikPenjualan(Request $request)
    {
        // Ambil data 7 hari terakhir, tanpa memerlukan input tanggal_mulai atau tanggal_selesai
        $tanggalMulai = now()->subDays(6)->toDateString();  // 7 hari yang lalu
        $tanggalSelesai = now()->toDateString();  // Hari ini

        // Ambil data penjualan dalam rentang waktu
        $penjualan = Penjualan::with('detailPenjualan')
            ->whereBetween('tanggal_penjualan', [$tanggalMulai, $tanggalSelesai])
            ->get()
            ->groupBy('tanggal_penjualan');

        // Buat array untuk menyimpan data 7 hari terakhir
        $hasil = [];
        $periode = collect(range(0, 6))->map(function ($i) use ($tanggalMulai) {
            return Carbon::parse($tanggalMulai)->addDays($i)->toDateString();
        });

        // Loop setiap hari dalam periode
        foreach ($periode as $tanggal) {
            $items = $penjualan[$tanggal] ?? collect([]);  // Ambil data untuk hari tersebut

            $hasil[] = [
                'tanggal' => $tanggal,
                'jumlah_transaksi' => $items->count(),
                'total_pendapatan' => $items->sum('total_penjualan'),
                'total_keuntungan' => $items->sum('total_keuntungan'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $hasil
        ]);
    }





}

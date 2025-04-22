<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\HistoryVoucher;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\PengajuanBarang;
use App\Models\Penjualan;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboardKasir(Request $request)
    {
        // Get authenticated user
        $user = auth()->user();

        // Query today's sales for the logged in user
        $penjualan = Penjualan::with('detailPenjualan')
                    ->whereDate('tanggal_penjualan', now()->toDateString())
                    ->where('user_id', $user->id)
                    ->get();

        // Calculate required metrics
        $total_transaksi = $penjualan->count();
        $total_penjualan_setelah_diskon = $penjualan->sum('total_penjualan_setelah_diskon');
        $total_keuntungan = $penjualan->sum('total_keuntungan');

        return response()->json([
            'success' => true,
            'data' => [
                'total_transaksi' => $total_transaksi,
                'total_penjualan_setelah_diskon' => $total_penjualan_setelah_diskon,
                'total_keuntungan' => $total_keuntungan
            ],
            'tanggal' => now()->format('Y-m-d')
        ]);
    }
    public function dashboardMember(Request $request)
    {
        $user = auth()->user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found'
            ], 404);
        }

        // Total vouchers owned (both used and unused)
        $totalVoucher = HistoryVoucher::where('member_id', $member->id)->count();

        // Total points from member table
        $totalPoint = $member->total_point;

        // Total pengajuan barang
        $totalPengajuan = PengajuanBarang::where('member_id', $member->id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_voucher' => $totalVoucher,
                'total_point' => $totalPoint,
                'total_pengajuan' => $totalPengajuan,
                'nama_member' => $member->nama_member
            ]
        ]);
    }

    public function dashboardSuper()
    {
        // Get today's date
        $today = Carbon::today();

        // 1. Sales Statistics
        $todaySales = Penjualan::whereDate('tanggal_penjualan', $today)->get();
        $monthlySales = Penjualan::whereMonth('tanggal_penjualan', $today->month)
                                ->whereYear('tanggal_penjualan', $today->year)
                                ->get();

        $salesData = [
            'today' => [
                'count' => $todaySales->count(),
                'revenue' => $todaySales->sum('total_penjualan_setelah_diskon'),
                'profit' => $todaySales->sum('total_keuntungan')
            ],
            'month' => [
                'count' => $monthlySales->count(),
                'revenue' => $monthlySales->sum('total_penjualan_setelah_diskon'),
                'profit' => $monthlySales->sum('total_keuntungan')
            ]
        ];

        // 2. Purchase Statistics
        $todayPurchases = Pembelian::whereDate('tanggal_pembelian', $today)->get();
        $monthlyPurchases = Pembelian::whereMonth('tanggal_pembelian', $today->month)
                                    ->whereYear('tanggal_pembelian', $today->year)
                                    ->get();

        $purchaseData = [
            'today' => [
                'count' => $todayPurchases->count(),
                'total' => $todayPurchases->sum('total'),
                'items' => $todayPurchases->sum(function($purchase) {
                    return $purchase->detailPembelian->sum('jumlah');
                })
            ],
            'month' => [
                'count' => $monthlyPurchases->count(),
                'total' => $monthlyPurchases->sum('total'),
                'items' => $monthlyPurchases->sum(function($purchase) {
                    return $purchase->detailPembelian->sum('jumlah');
                })
            ]
        ];

        // 3. Inventory Statistics
        $inventoryData = [
            'total_items' => Barang::count(),
            'low_stock' => Barang::where('stok', '<', 10)->count(),
            'out_of_stock' => Barang::where('stok', 0)->count()
        ];

        // 4. Member Statistics
        $memberData = [
            'total_members' => Member::count(),
            'active_today' => Penjualan::whereDate('tanggal_penjualan', $today)
                                      ->whereNotNull('member_id')
                                      ->distinct('member_id')
                                      ->count('member_id'),
            'points_distributed' => Member::sum('total_point')
        ];

        // 5. Voucher Statistics
        $voucherData = [
            'total_vouchers' => Voucher::count(),
            'issued_vouchers' => HistoryVoucher::count(),
            'used_vouchers' => HistoryVoucher::whereNotNull('tanggal_digunakan')->count()
        ];

        // 6. User Statistics
        $userData = [
            'total_users' => User::count(),
            'active_today' => User::whereHas('penjualan', function($query) use ($today) {
                $query->whereDate('tanggal_penjualan', $today);
            })->count()
        ];

        // 7. Recent Transactions
        $recentSales = Penjualan::with(['member', 'user'])
                              ->orderBy('created_at', 'desc')
                              ->take(5)
                              ->get()
                              ->map(function($sale) {
                                  return [
                                      'id' => $sale->id,
                                      'date' => $sale->tanggal_penjualan,
                                      'total' => $sale->total_penjualan_setelah_diskon,
                                      'member' => $sale->member->nama_member ?? 'Non-Member',
                                      'cashier' => $sale->user->nama
                                  ];
                              });

        $recentPurchases = Pembelian::with(['vendor', 'user'])
                                  ->orderBy('created_at', 'desc')
                                  ->take(5)
                                  ->get()
                                  ->map(function($purchase) {
                                      return [
                                          'id' => $purchase->id,
                                          'date' => $purchase->tanggal_pembelian,
                                          'total' => $purchase->total,
                                          'vendor' => $purchase->vendor->nama_vendor,
                                          'operator' => $purchase->user->nama
                                      ];
                                  });

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => $salesData,
                'purchases' => $purchaseData,
                'inventory' => $inventoryData,
                'members' => $memberData,
                'vouchers' => $voucherData,
                'users' => $userData,
                'recent_transactions' => [
                    'sales' => $recentSales,
                    'purchases' => $recentPurchases
                ],
                'timestamp' => now()->toDateTimeString()
            ]
        ]);
    }

    public function dashboardOperator(Request $request)
    {
        // Get authenticated user
        $user = auth()->user();

        // Query today's purchases for the logged in operator
        $pembelian = Pembelian::with(['vendor', 'user', 'detailPembelian'])
            ->whereDate('tanggal_pembelian', now()->toDateString())
            ->where('user_id', $user->id)
            ->get();


        // Calculate totals
        $total_transaksi = $pembelian->count();
        $total_pembelian = $pembelian->sum('total');
        $total_barang = $pembelian->sum(function ($pembelian) {
            return $pembelian->detailPembelian->sum('jumlah');
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_pembelian' => $total_pembelian,
                'total_barang' => $total_barang,
                'total_transaksi' => $total_transaksi
            ],
            'tanggal' => now()->format('Y-m-d')
        ]);
    }
}

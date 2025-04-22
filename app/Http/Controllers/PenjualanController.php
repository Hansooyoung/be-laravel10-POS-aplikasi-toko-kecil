<?php

namespace App\Http\Controllers;

use Mike42\Escpos\Printer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;
use Exception;
use App\Models\HistoryVoucher;
use App\Models\Member;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Barang;
use App\Models\Voucher;
use App\Services\PrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{

    public function index()
    {
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
    public function dashboardSummary(Request $request)
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


    public function show($id)
    {
        $penjualan = Penjualan::findOrFail($id);

        $detailPenjualan = DetailPenjualan::with('barang')
            ->where('penjualan_id', $id)
            ->get();

        $formattedDetail = $detailPenjualan->map(function ($detail) {
            return [
                'id' => $detail->id,
                'penjualan_id' => $detail->penjualan_id,
                'kode_barang' => $detail->kode_barang,
                'barcode' => $detail->barang->barcode ?? null,
                'harga_jual' => $detail->harga_jual,
                'harga_beli' => $detail->harga_beli,
                'jumlah' => $detail->jumlah,
                'sub_total' => $detail->sub_total,
                'keuntungan' => $detail->harga_jual - $detail->harga_beli,
                'nama_barang' => $detail->barang->nama_barang ?? 'Barang tidak ditemukan',
            ];
        });

        return response()->json([
            'id' => $penjualan->id,
            'tanggal_penjualan' => $penjualan->tanggal_penjualan,
            'tanggal_masuk' => $penjualan->tanggal_masuk ?? 'Belum Masuk',
            'user_id' => $penjualan->user_id,
            'member_id' => $penjualan->member_id,
            'voucher_id' => $penjualan->voucher_id,
            'created_at' => $penjualan->created_at,
            'updated_at' => $penjualan->updated_at,
            'total_penjualan' => $penjualan->total_penjualan,
            'total_keuntungan' => $penjualan->total_keuntungan,
            'tunai' => $penjualan->tunai,
            'kembalian' => $penjualan->tunai - $penjualan->total_penjualan_setelah_diskon,
            'detail_penjualan' => $formattedDetail,
        ]);
    }

    public function cetakStruk($id, Request $request)
    {
        // Validasi input
        $request->validate([
            'tunai' => 'required|numeric|min:0',
            'kembalian' => 'required|numeric'
        ]);

        $penjualan = Penjualan::with(['detailPenjualan.barang', 'member', 'user'])
            ->findOrFail($id);

        // Pastikan tanggal_penjualan adalah instance Carbon
        $tanggalPenjualan = $penjualan->tanggal_penjualan;
        if (is_string($tanggalPenjualan)) {
            $tanggalPenjualan = \Carbon\Carbon::parse($tanggalPenjualan);
        }

        // Format data untuk struk
        $data = [
            'kode_penjualan' => $penjualan->kode_penjualan,
            'tanggal' => $tanggalPenjualan->format('d/m/Y'), // Gunakan yang sudah diparse
            'kasir' => $penjualan->user->nama,
            'member' => $penjualan->member ? $penjualan->member->nama_member : '-',
            'voucher' => $penjualan->voucher ? $penjualan->voucher->nama_voucher : '-',
            'items' => $penjualan->detailPenjualan->map(function ($item) {
            $barang = $item->barang;

            return [
                'nama' => $barang ? $barang->nama_barang : 'Barang tidak ditemukan',
                'qty' => $item->jumlah,
                'harga' => $item->harga_jual,
                'subtotal' => $item->sub_total
            ];
            }),

            'total' => $penjualan->total_penjualan_setelah_diskon,
            'tunai' => $request->tunai,
            'kembalian' => $request->kembalian,
            'diskon' => $penjualan->total_diskon
        ];

        // Mulai proses pencetakan
        $connector = null;
        $printer = null;

        try {
            // Koneksi ke printer
            $connector = new WindowsPrintConnector("POS50"); // Ganti dengan nama share printer
            $printer = new Printer($connector);

            /* Header Struk */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            try {
                $logo = EscposImage::load(resource_path('images/logo.png'), false);
                $printer->bitImage($logo);
            } catch (Exception $e) {
                // Skip logo jika error
            }
            $printer->setTextSize(2, 2);
            $printer->text("TOKO KECIL\n");
            $printer->setTextSize(1, 1);
            $printer->text("Jl. Contoh No. 123\n");
            $printer->text("Telp: 08123456789\n");
            $printer->feed();

            /* Informasi Transaksi */
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("No. Penjualan: " . $data['kode_penjualan'] . "\n");
            $printer->text("Tanggal    : " . $data['tanggal'] . "\n");
            $printer->text("Kasir      : " . $data['kasir'] . "\n");
            $printer->text("Member     : " . $data['member'] . "\n");
            $printer->text("Voucher     : " . $data['voucher'] . "\n");
            $printer->feed();
            $printer->text("--------------------------------\n");

        /* Daftar Barang */
        foreach ($data['items'] as $item) {
            // Ensure all values are properly set and not null
            $nama = substr($item['nama'] ?? '', 0, 20);
            $qty = str_pad($item['qty'] ?? 0, 3, ' ', STR_PAD_LEFT);
            $harga = str_pad(number_format($item['harga'] ?? 0, 0, ',', '.'), 8, ' ', STR_PAD_LEFT);
            $subtotal = str_pad(number_format($item['subtotal'] ?? 0, 0, ',', '.'), 10, ' ', STR_PAD_LEFT);

            $printer->text(sprintf("%-20s %3s x %8s = %10s\n",
                $nama,
                $qty,
                $harga,
                $subtotal
            ));
        }
            $printer->text("--------------------------------\n");

            /* Total Pembayaran */
            $printer->setJustification(Printer::JUSTIFY_RIGHT);
            if ($data['diskon'] > 0) {
                $printer->text("SUBTOTAL : " . number_format($data['total'] + $data['diskon'], 0, ',', '.') . "\n");
                $printer->text("DISKON   : -" . number_format($data['diskon'], 0, ',', '.') . "\n");
            }
            $printer->text("TOTAL    : " . number_format($data['total'], 0, ',', '.') . "\n");
            $printer->text("TUNAI    : " . number_format($data['tunai'], 0, ',', '.') . "\n");
            $printer->text("KEMBALI  : " . number_format($data['kembalian'], 0, ',', '.') . "\n");
            $printer->feed(2);

            /* Footer */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Terima kasih telah berbelanja\n");
            $printer->text("Barang yang sudah dibeli\n");
            $printer->text("tidak dapat ditukar/dikembalikan\n");
            $printer->feed();
            $printer->text("No. Penjualan: " . $data['kode_penjualan'] . "\n");
            $printer->text($data['tanggal'] . "\n");
            $printer->feed(3);
            $printer->pulse();
            $printer->cut(Printer::CUT_PARTIAL);
            $printer->close();

            return response()->json(['message' => 'Struk berhasil dicetak']);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mencetak struk',
                'error' => $e->getMessage()
            ], 500);

        }
    }
    public function destroy($id)
{
    DB::beginTransaction();
    try {
        $penjualan = Penjualan::with(['detailPenjualan', 'member'])->findOrFail($id);

        // 1. Return stock for each item in the sale
        foreach ($penjualan->detailPenjualan as $detail) {
            $barang = Barang::where('kode_barang', $detail->kode_barang)->first();
            if ($barang) {
                $barang->stok += $detail->jumlah;
                $barang->save();
            }
        }

        // 2. Handle member points if this sale had a member
        if ($penjualan->member_id) {
            $member = Member::find($penjualan->member_id);
            if ($member) {
                // Deduct points earned from this sale (1 point per Rp1000)
                $pointsToDeduct = round($penjualan->total_penjualan_setelah_diskon / 1000);
                $member->total_point = max(0, $member->total_point - $pointsToDeduct);
                $member->save();
            }
        }

        // 3. Handle voucher if this sale used one
        if ($penjualan->voucher_id && $penjualan->member_id) {
            $historyVoucher = HistoryVoucher::where('member_id', $penjualan->member_id)
                ->where('voucher_id', $penjualan->voucher_id)
                ->whereNotNull('tanggal_digunakan')
                ->first();

            if ($historyVoucher) {
                // Mark the voucher as unused again
                $historyVoucher->update(['tanggal_digunakan' => null]);
            }
        }

        // 4. Delete the sale details
        $penjualan->detailPenjualan()->delete();

        // 5. Finally, delete the sale itself
        $penjualan->delete();

        DB::commit();

        return response()->json([
            'message' => 'Penjualan berhasil dihapus'
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Gagal menghapus penjualan: ' . $e->getMessage());
        return response()->json([
            'error' => 'Gagal menghapus penjualan: ' . $e->getMessage()
        ], 500);
    }
}
    public function update(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $penjualan = Penjualan::with('detailPenjualan')->findOrFail($id);

        $validated = $request->validate([
            'member_id' => 'nullable|exists:member,id',
            'barang' => [
                'nullable', 'array',
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $barcodes = array_column($value, 'barcode');
                        if (count($barcodes) !== count(array_unique($barcodes))) {
                            $fail('Barcode tidak boleh ada yang sama dalam satu transaksi.');
                        }
                    }
                }
            ],
            'barang.*.barcode' => 'required_with:barang|string',
            'barang.*.jumlah' => 'required_with:barang|integer|min:1',
            'tunai' => 'nullable|numeric|min:0',
        ]);

        $updateData = [];
        $pointEarned = 0;
        $oldVoucherId = $penjualan->voucher_id;
        $oldMemberId = $penjualan->member_id;

        // 1. Handle perubahan member (jika ada)
        if (array_key_exists('member_id', $validated)) {
            $updateData['member_id'] = $validated['member_id'];

            // Kembalikan poin member lama jika ada
            if ($oldMemberId) {
                $oldMember = Member::find($oldMemberId);
                if ($oldMember) {
                    $oldMember->total_point -= round($penjualan->total_penjualan_setelah_diskon / 1000);
                    $oldMember->save();
                }
            }

            // Kembalikan voucher lama jika ada
            if ($oldVoucherId) {
                $historyVoucher = HistoryVoucher::where('member_id', $oldMemberId)
                    ->where('voucher_id', $oldVoucherId)
                    ->whereNotNull('tanggal_digunakan')
                    ->first();

                if ($historyVoucher) {
                    $historyVoucher->update(['tanggal_digunakan' => null]);
                }
            }

            // Cek voucher baru jika member diubah
            $voucherId = null;
            if (!empty($validated['member_id'])) {
                $historyVoucher = HistoryVoucher::where('member_id', $validated['member_id'])
                    ->whereNull('tanggal_digunakan')
                    ->first();

                if ($historyVoucher) {
                    $voucher = Voucher::find($historyVoucher->voucher_id);
                    if ($voucher && $penjualan->total_penjualan >= $voucher->min_pembelian) {
                        $voucherId = $voucher->id;
                        $historyVoucher->update(['tanggal_digunakan' => now()]);
                    }
                }
            }
            $updateData['voucher_id'] = $voucherId;
        }

        // 2. Handle perubahan barang (jika ada)
        if (!empty($validated['barang'])) {
            // Kembalikan stok barang lama
            foreach ($penjualan->detailPenjualan as $detail) {
                $barang = Barang::where('kode_barang', $detail->kode_barang)->first();
                if ($barang) {
                    $barang->stok += $detail->jumlah;
                    $barang->save();
                }
            }

            // Hapus detail lama
            $penjualan->detailPenjualan()->delete();

            // Tambahkan detail baru
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

                $penjualan->detailPenjualan()->create([
                    'kode_barang' => $barang->kode_barang,
                    'harga_jual' => $barang->harga_jual_diskon ?? $barang->harga_jual,
                    'harga_beli' => $barang->harga_beli,
                    'jumlah' => $barangData['jumlah'],
                ]);

                $barang->stok -= $barangData['jumlah'];
                $barang->save();
            }

            // Jika ada perubahan barang, perlu cek ulang voucher
            if (!empty($penjualan->member_id)) {
                $voucherId = null;
                $historyVoucher = HistoryVoucher::where('member_id', $penjualan->member_id)
                    ->whereNull('tanggal_digunakan')
                    ->first();

                if ($historyVoucher) {
                    $voucher = Voucher::find($historyVoucher->voucher_id);
                    if ($voucher && $penjualan->total_penjualan >= $voucher->min_pembelian) {
                        $voucherId = $voucher->id;
                        $historyVoucher->update(['tanggal_digunakan' => now()]);
                    }
                }
                $updateData['voucher_id'] = $voucherId;
            }
        }

        // 3. Handle perubahan tunai (jika ada)
        if (array_key_exists('tunai', $validated)) {
            if ($validated['tunai'] < $penjualan->total_penjualan_setelah_diskon) {
                throw new \Exception("Uang tunai tidak cukup. Total belanja setelah diskon: Rp" . number_format($penjualan->total_penjualan_setelah_diskon, 0, ',', '.'));
            }
            $updateData['tunai'] = $validated['tunai'];
        }

        // Update data penjualan
        $penjualan->update($updateData);

        // 4. Update poin member (jika ada member_id)
        if (!empty($penjualan->member_id)) {
            $member = Member::find($penjualan->member_id);
            if ($member) {
                $pointEarned = round($penjualan->total_penjualan_setelah_diskon / 1000);
                $member->total_point += $pointEarned;
                $member->save();
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Penjualan berhasil diperbarui',
            'penjualan' => $penjualan->fresh()->load('voucher', 'detailPenjualan'),
            'point_earned' => $pointEarned,
            'total_points' => $member->total_point ?? null,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Gagal memperbarui penjualan: ' . $e->getMessage());
        return response()->json([
            'error' => 'Gagal memperbarui penjualan: ' . $e->getMessage()
        ], 500);
    }
}
    public function store(Request $request)
    {
        // return $request->all();
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'barang' => 'required|array|min:1',
                'barang.*.barcode' => 'required|string|exists:barang,barcode',
                'barang.*.jumlah' => 'required|integer|min:1',
                'tunai' => 'required|numeric|min:0',
                'member_id' => 'nullable|exists:member,id',
            ]);

            $userId = auth()->id();
            $tanggal_penjualan = now();
            $tanggal_masuk = $validated['tanggal_penjualan'] ?? null;
            $voucherId = null;
            $pointEarned = 0; // Initialize points earned

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

            // Tambahkan point ke member (jika ada member_id)
            if (!empty($validated['member_id'])) {
                $member = Member::find($validated['member_id']);
                if ($member) {
                    // Konversi total belanja setelah diskon ke point (Rp1.000 = 1 point)
                    $pointToAdd = round($penjualan->total_penjualan_setelah_diskon / 1000);
                    $pointEarned = $pointToAdd;

                    // Update total_point member
                    $member->total_point += $pointToAdd;
                    $member->save();
                }
            }

            DB::commit();

            return response()->json([
                'message'     => 'Penjualan berhasil ditambahkan',
                'penjualan'   => $penjualan->load('voucher'),
                'point_earned' => $pointEarned, // Add points earned to response
                'total_points' => $member->total_point ?? null, // Add current total points if member exists
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

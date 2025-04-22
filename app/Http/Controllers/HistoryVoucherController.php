<?php

namespace App\Http\Controllers;

use App\Models\HistoryVoucher;
use App\Http\Requests\StoreHistoryVoucherRequest;
use App\Http\Requests\UpdateHistoryVoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\Request;

class HistoryVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = HistoryVoucher::with('voucher')->where('member_id', $request->member_id);

        // Filter hanya voucher yang belum digunakan
        if ($request->status == 'unused') {
            $query->whereNull('tanggal_digunakan');
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }

    public function myVouchers(Request $request)
{
    $user = auth()->user();
    $member = $user->member;

    if (!$member) {
        return response()->json(['message' => 'Member not found'], 404);
    }

    $query = HistoryVoucher::with('voucher')
        ->where('member_id', $member->id);

    // Opsional: filter hanya yang belum digunakan
    if ($request->status == 'unused') {
        $query->whereNull('tanggal_digunakan');
    }

    return response()->json([
        'data' => $query->get()
    ]);
}

public function redeem(Request $request)
{
    $request->validate([
        'voucher_id' => 'required|exists:voucher,id',
    ]);

    $user = auth()->user();
    $member = $user->member;

    if (!$member) {
        return response()->json(['message' => 'Member not found'], 404);
    }

    $voucher = Voucher::findOrFail($request->voucher_id);

    if ($member->total_point < $voucher->harga_point) {
        return response()->json(['message' => 'Poin tidak mencukupi'], 400);
    }

    $member->total_point -= $voucher->harga_point;
    $member->save();

    $history = HistoryVoucher::create([
        'member_id' => $member->id,
        'voucher_id' => $voucher->id,
        'tanggal_penukaran' => now(),
    ]);

    return response()->json([
        'message' => 'Voucher berhasil ditukarkan',
        'data' => $history->load('voucher')
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function riwayatPenukaran()
{
    $user = auth()->user();
    $member = $user->member;

    if (!$member) {
        return response()->json(['message' => 'Member not found'], 404);
    }

    $riwayat = HistoryVoucher::with('voucher')
        ->where('member_id', $member->id)
        ->whereNotNull('tanggal_digunakan')
        ->orderByDesc('tanggal_digunakan')
        ->get();

    return response()->json([
        'message' => 'Riwayat penukaran voucher',
        'data' => $riwayat
    ]);
}

}

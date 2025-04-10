<?php

namespace App\Http\Controllers;

use App\Models\HistoryVoucher;
use App\Http\Requests\StoreHistoryVoucherRequest;
use App\Http\Requests\UpdateHistoryVoucherRequest;
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHistoryVoucherRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(HistoryVoucher $historyVoucher)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HistoryVoucher $historyVoucher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHistoryVoucherRequest $request, HistoryVoucher $historyVoucher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HistoryVoucher $historyVoucher)
    {
        //
    }
}

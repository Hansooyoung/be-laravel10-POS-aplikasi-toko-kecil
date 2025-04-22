<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HistoryAktifitasController extends Controller
{
    public function index(Request $request)
{
    // Ambil riwayat aktivitas berdasarkan user yang sedang login
    $userId = Auth::id();  // Ambil user_id yang sedang login

    // Ambil data logs yang terkait dengan user ini, dengan pagination 10 data per halaman
    $logs = Log::where('user_id', $userId);

    // Cek jika ada query pencarian
    if ($request->has('search') && $request->search != '') {
        $logs = $logs->where('activity', 'like', '%' . $request->search . '%');
    }

    $logs = $logs->orderBy('created_at', 'desc')  // Urutkan berdasarkan tanggal terbaru
                ->paginate(10);  // 10 data per halaman

    // Kirim data logs dalam bentuk JSON, termasuk informasi pagination
    return response()->json([
        'data' => $logs->items(),  // Data logs pada halaman ini
        'current_page' => $logs->currentPage(),  // Halaman saat ini
        'total_pages' => $logs->lastPage(),  // Total halaman
        'total_records' => $logs->total(),  // Total jumlah data
    ]);
}


}

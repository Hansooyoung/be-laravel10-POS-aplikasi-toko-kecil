<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Log;
use App\Models\PengajuanBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\ExportService;
class PengajuanBarangController extends Controller
{
    // === OOP: Menggunakan Dependency Injection untuk layanan Export ===
    private $exportService;
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }
     // === OOP: Method dalam class untuk Export Data ===
    public function exportPengajuan(Request $request, $format)
    {

        // Ambil data dari function index
        $dataRaw = json_decode($this->index($request)->getContent(), true);
        $data = $dataRaw['data'] ?? [];

        // Pastikan data sesuai kebutuhan yang akan diekspor
        $formattedData = array_map(function ($item) {
            return [
                'Nama Member' => $item['nama_member'] ?? 'Tidak Diketahui',
                'Nama User' => $item['nama_user'] !== 'User Tidak Ditemukan' && !empty($item['nama_user'])
                    ? $item['nama_user']
                    : 'Belum Ditentukan',
                'Nama Barang' => $item['nama_barang'] ?? 'Tidak Diketahui',
                'Jumlah' => $item['jumlah'] ?? 0,
                'Status' => $item['status'] ?? 'Tidak Diketahui',
                'Tanggal Pengajuan' => $item['tanggal_pengajuan'] ?? 'Tidak Diketahui',
                'Pesan' => $item['pesan'] ?? '-',
                'Keterangan' => $item['keterangan'] ?? '-',
            ];
        }, $data);

        // Header sesuai dengan format yang diminta
        $headers = ['Nama Member', 'Nama User', 'Nama Barang', 'Jumlah', 'Status', 'Tanggal Pengajuan', 'Pesan', 'Keterangan'];
        $filename = 'laporan_pengajuan';
        $title = 'Laporan Pengajuan Barang';

        return $this->handleExport($formattedData, $headers, $filename, $format, $title);
    }


    // === OOP: Private Method untuk menangani Export ===
    private function handleExport($data, $headers, $filename, $format, $title)
    {
        if ($format === 'excel') {
            return $this->exportService->exportToExcel($data, $filename, $headers);
        }

        if ($format === 'pdf') {
            return $this->exportService->exportToPDF($data, $filename, $headers, $title);
        }

        return response()->json(['message' => 'Format tidak didukung'], 400);
    }

    // === OOP: Method untuk Mengambil Data Pengajuan ===
    public function index(Request $request)
    {
        $user = auth()->user()->load('member'); // Tambahkan load('member')

        $query = PengajuanBarang::with([
            'member:id,nama_member',
            'user:id,nama'
        ]);

        if ($user->member) {
            $query->where('member_id', $user->member->id);
        }

        // Fitur pencarian berdasarkan nama_member
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('nama_member', 'LIKE', "%$search%");
            });
        }

        // Pagination 10 per halaman
        $pengajuan = $query->latest()->paginate(10);

        $formattedData = $pengajuan->map(function ($item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'member_id' => $item->member_id,
                'nama_member' => $item->member->nama_member ?? 'Tidak Memiliki Member',
                'nama_user' => $item->user->nama ?? 'User Tidak Ditemukan',
                'nama_barang' => $item->nama_barang,
                'jumlah' => $item->jumlah,
                'status' => $item->status,
                'pesan' => $item->pesan,
                'tanggal_pengajuan' => $item->tanggal_pengajuan ?? $item->created_at->format('Y-m-d H:i:s'),
                'keterangan' => $item->keterangan ?? 'Tidak Ada Keterangan',
            ];
        });

        return response()->json([
            'message' => 'Data pengajuan barang berhasil diambil',
            'data' => $formattedData,
            'pagination' => [
                'total' => $pengajuan->total(),
                'per_page' => $pengajuan->perPage(),
                'current_page' => $pengajuan->currentPage(),
                'last_page' => $pengajuan->lastPage(),
                'from' => $pengajuan->firstItem(),
                'to' => $pengajuan->lastItem(),
            ]
        ]);
    }



   // === OOP: Method untuk Menampilkan Detail Pengajuan ===
    public function show($id)
    {
        $pengajuan = PengajuanBarang::with('member', 'user')->find($id);
        if (!$pengajuan) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan'], 404);
        }
        return response()->json($pengajuan);
    }

    /**
     * Operator memperbarui status pengajuan barang.
     */


    /**
     * Menghapus pengajuan barang (force delete).
     */

     // === OOP: Method untuk Menghapus Pengajuan ===
     public function destroy($id)
     {
         $pengajuan = PengajuanBarang::find($id);
         if (!$pengajuan) {
             // Log jika pengajuan tidak ditemukan
             Log::createLog('Pengajuan Dihapus', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' tidak ditemukan.');
             return response()->json(['message' => 'Pengajuan tidak ditemukan'], 404);
         }

         $pengajuan->forceDelete(); // Hapus permanen

         // Log setelah pengajuan berhasil dihapus
         Log::createLog('Pengajuan Dihapus', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' telah dihapus.');

         return response()->json(['message' => 'Pengajuan berhasil dihapus']);
     }
         // === OOP: Method untuk Menyimpan Pengajuan Baru ===
     public function store(Request $request)
     {
         $user = auth()->user(); // Ambil user yang sedang login

         // Jika user adalah member, gunakan ID member dari user yang login.
         // Jika user adalah kasir/operator, pastikan 'member_id' dikirim.
         $memberId = $user->member ? $user->member->id : $request->member_id;

         // Jika user bukan member dan tidak mengirim member_id, tolak permintaan.
         if (!$memberId) {
             Log::createLog('Pengajuan Barang Gagal', $user->id, 'User bukan member dan member_id wajib diisi.');
             return response()->json([
                 'message' => 'User bukan member dan member_id wajib diisi.',
             ], 403);
         }

         // Validasi input
         $validator = Validator::make($request->all(), [
             'nama_barang' => 'required|string|max:255|unique:pengajuan_barang,nama_barang', // Validasi unik pada pengajuan_barang
             'jumlah' => 'required|integer|min:1',
             'pesan' => 'nullable|string',
             'member_id' => 'nullable|exists:member,id', // Pastikan member_id valid jika dikirim
         ]);

         if ($validator->fails()) {
             Log::createLog('Pengajuan Barang Gagal', $user->id, 'Validasi gagal: ' . json_encode($validator->errors()));
             return response()->json(['errors' => $validator->errors()], 422);
         }

         // Pastikan nama_barang unik pada tabel barang
         $barangExist = Barang::where('nama_barang', $request->nama_barang)->exists();
         if ($barangExist) {
             Log::createLog('Pengajuan Barang Gagal', $user->id, 'Nama barang sudah ada di tabel barang.');
             return response()->json([
                 'message' => 'Nama barang sudah ada di tabel barang.',
             ], 422);
         }

         // Menyimpan pengajuan barang
         $pengajuan = PengajuanBarang::create([
             'user_id' => $user->role === 'operator' ? $user->id : null,
             'member_id' => $memberId,
             'nama_barang' => $request->nama_barang,
             'jumlah' => $request->jumlah,
             'tanggal_pengajuan' => now(),
             'status' => 'Pending',
             'pesan' => $request->pesan,
             'keterangan' => null,
         ]);

         // Log setelah pengajuan berhasil dibuat
         Log::createLog('Pengajuan Baru', $user->id, 'Pengajuan baru berhasil dibuat dengan nama barang: ' . $request->nama_barang);

         return response()->json([
             'message' => 'Pengajuan barang berhasil dibuat.',
             'data' => $pengajuan
         ], 201);
     }

     public function updateMember(Request $request, $id)
     {
         $pengajuan = PengajuanBarang::find($id);
         if (!$pengajuan) {
             // Log jika pengajuan tidak ditemukan
             Log::createLog('Pengajuan Barang Update Gagal', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' tidak ditemukan.');
             return response()->json(['message' => 'Pengajuan tidak ditemukan'], 404);
         }

         // Hanya bisa diupdate jika status masih pending
         if ($pengajuan->status !== 'Pending') {
             // Log jika status pengajuan sudah tidak bisa diubah
             Log::createLog('Pengajuan Barang Update Gagal', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' tidak dapat diubah karena sudah diproses.');
             return response()->json(['message' => 'Pengajuan tidak dapat diubah karena sudah diproses'], 403);
         }

         $validator = Validator::make($request->all(), [
             'nama_barang' => 'sometimes|string|max:255',
             'jumlah' => 'sometimes|integer|min:1',
             'pesan' => 'sometimes|nullable|string',
         ]);

         if ($validator->fails()) {
             Log::createLog('Pengajuan Barang Update Gagal', auth()->user()->id, 'Validasi gagal: ' . json_encode($validator->errors()));
             return response()->json(['errors' => $validator->errors()], 422);
         }

         $pengajuan->update($request->only(['nama_barang', 'jumlah', 'pesan']));

         // Log setelah pengajuan berhasil diperbarui
         Log::createLog('Pengajuan Barang Diperbarui', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' berhasil diperbarui.');

         return response()->json([
             'message' => 'Pengajuan barang berhasil diperbarui.',
             'data' => $pengajuan
         ]);
     }

     public function updateStatus(Request $request, $id)
     {
         $pengajuan = PengajuanBarang::find($id);
         if (!$pengajuan) {
             // Log jika pengajuan tidak ditemukan
             Log::createLog('Pengajuan Status Update Gagal', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' tidak ditemukan.');
             return response()->json(['message' => 'Pengajuan tidak ditemukan'], 404);
         }

         // Jika status sudah approved atau rejected, tidak bisa diubah lagi
         if (in_array($pengajuan->status, ['Approved', 'Rejected'])) {
             // Log jika status tidak bisa diubah lagi
             Log::createLog('Pengajuan Status Update Gagal', auth()->user()->id, 'Pengajuan dengan ID ' . $id . ' sudah diproses dan status tidak dapat diubah.');
             return response()->json(['message' => 'Status pengajuan tidak dapat diubah karena sudah diproses'], 403);
         }

         $validator = Validator::make($request->all(), [
             'status' => 'required|in:Approved,Rejected',
             'keterangan' => 'nullable|string',
         ]);

         if ($validator->fails()) {
             // Log jika validasi gagal
             Log::createLog('Pengajuan Status Update Gagal', auth()->user()->id, 'Validasi gagal: ' . json_encode($validator->errors()));
             return response()->json(['errors' => $validator->errors()], 422);
         }

         $pengajuan->update([
             'user_id' => Auth::id(), // Operator yang sedang login
             'status' => $request->status,
             'keterangan' => $request->keterangan,
         ]);

         // Log setelah status berhasil diperbarui
         Log::createLog('Pengajuan Status Diperbarui', auth()->user()->id, 'Status pengajuan dengan ID ' . $id . ' berhasil diperbarui menjadi ' . $request->status);

         return response()->json([
             'message' => 'Pengajuan barang berhasil diperbarui.',
             'data' => $pengajuan
         ]);
     }


}

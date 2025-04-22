<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Imports\AbsensiImport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use App\Services\ExportService;

/**
 * Controller untuk mengelola data absensi karyawan
 *
 * Menyediakan endpoint untuk:
 * - CRUD data absensi
 * - Update status dan jam keluar
 * - Export data ke Excel/PDF
 * - Import data dari Excel
 */
class AbsensiController extends Controller
{
    /**
     * @var ExportService Layanan untuk menangani ekspor data
     */
    private $exportService;

    /**
     * Constructor untuk dependency injection ExportService
     *
     * @param ExportService $exportService - Service untuk menangani ekspor data
     */
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Menampilkan daftar data absensi dengan pagination
     *
     * @param Request $request - Objek request yang mungkin berisi parameter pencarian
     * @return \Illuminate\Http\JsonResponse - Response JSON berisi data absensi
     */
    public function index(Request $request)
    {
        $absensi = Absensi::with('user');

        if ($request->has('search') && !empty($request->search)) {
            $absensi->whereHas('user', function ($query) use ($request) {
                $query->where('nama', 'LIKE', '%' . $request->search . '%');
            });
        }

        $absensi = $absensi->orderBy('tanggal', 'desc')->paginate(10);

        return response()->json([
            'message' => 'Data absensi berhasil diambil',
            'data' => $absensi
        ]);
    }

    /**
     * Menyimpan data absensi baru
     *
     * @param Request $request - Objek request berisi data absensi
     * @return \Illuminate\Http\JsonResponse - Response JSON hasil operasi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:user,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i:s',
            'status' => 'required|in:masuk,sakit,cuti',
            'keterangan' => 'nullable|string|max:255',
        ]);

        // Cek apakah user adalah member
        $user = User::find($validated['user_id']);
        if ($user->role === 'member') {
            return response()->json([
                'message' => 'User dengan role member tidak boleh melakukan absensi.'
            ], 422);
        }

        // Jika status bukan "masuk", jam_masuk dan jam_keluar di-set 00:00:00
        $jamMasuk = $validated['jam_masuk'] ?? null;
        $jamKeluar = null;

        if ($validated['status'] !== 'masuk') {
            $jamMasuk = '00:00:00';
            $jamKeluar = '00:00:00';
        }

        $absensi = Absensi::create([
            'user_id' => $validated['user_id'],
            'tanggal' => $validated['tanggal'],
            'jam_masuk' => $jamMasuk,
            'jam_keluar' => $jamKeluar,
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return response()->json([
            'message' => 'Absensi berhasil ditambahkan',
            'data' => $absensi
        ], Response::HTTP_CREATED);
    }



    /**
     * Menampilkan detail data absensi berdasarkan ID
     *
     * @param int $id - ID absensi yang dicari
     * @return \Illuminate\Http\JsonResponse - Response JSON data absensi
     */
    public function show($id)
    {
        $absensi = Absensi::with('user')->find($id);

        if (!$absensi) {
            return response()->json([
                'message' => 'Absensi tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Absensi berhasil ditemukan',
            'data' => $absensi
        ], Response::HTTP_OK);
    }

    /**
     * Mengupdate jam keluar untuk absensi yang dipilih
     *
     * @param Request $request - Objek request
     * @param Absensi $absensi - Model absensi yang akan diupdate
     * @return \Illuminate\Http\JsonResponse - Response JSON hasil operasi
     */
    public function updateJamKeluar(Request $request, Absensi $absensi)
    {
        // Cek apakah jam_keluar masih kosong/null
        if (is_null($absensi->jam_keluar)) {
            $absensi->jam_keluar = Carbon::now()->format('H:i:s');
            $absensi->save();

            return response()->json([
                'message' => 'Jam keluar berhasil diupdate',
                'data' => $absensi
            ]);
        }

        return response()->json([
            'message' => 'Jam keluar sudah terisi, tidak bisa diupdate lagi',
            'data' => $absensi
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Mengupdate status absensi (sakit/cuti)
     *
     * @param Request $request - Objek request
     * @param Absensi $absensi - Model absensi yang akan diupdate
     * @return \Illuminate\Http\JsonResponse - Response JSON hasil operasi
     */
    public function updateStatus(Request $request, Absensi $absensi)
    {
        $validated = $request->validate([
            'status' => 'required|in:masuk,sakit,cuti',
            'jam_masuk' => 'nullable|date_format:H:i:s',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($validated['status'] === 'masuk') {
            $absensi->update([
                'status' => 'masuk',
                'jam_masuk' => $validated['jam_masuk'] ?? $absensi->jam_masuk,
                'jam_keluar' => null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        } else {
            $absensi->update([
                'status' => $validated['status'],
                'jam_masuk' => '00:00:00',
                'jam_keluar' => '00:00:00',
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Status absensi berhasil diperbarui',
            'data' => $absensi
        ]);
    }




    /**
     * Mengupdate data absensi selain jam_keluar
     *
     * @param Request $request - Objek request
     * @param Absensi $absensi - Model absensi yang akan diupdate
     * @return \Illuminate\Http\JsonResponse - Response JSON hasil operasi
     */
    public function updateAbsensi(Request $request, Absensi $absensi)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:user,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable|date_format:H:i:s',
            'status' => 'required|in:masuk,sakit,cuti',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $updateData = [
            'user_id' => $validated['user_id'],
            'tanggal' => $validated['tanggal'],
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'] ?? null,
        ];

        if ($validated['status'] === 'masuk') {
            $updateData['jam_masuk'] = $validated['jam_masuk'] ?? now()->format('H:i:s');
            $updateData['jam_keluar'] = null;
        } else {
            $updateData['jam_masuk'] = '00:00:00';
            $updateData['jam_keluar'] = '00:00:00';
        }

        $absensi->update($updateData);

        return response()->json([
            'message' => 'Absensi berhasil diperbarui',
            'data' => $absensi
        ]);
    }


    /**
     * Menghapus data absensi
     *
     * @param int $id - ID absensi yang akan dihapus
     * @return \Illuminate\Http\JsonResponse - Response JSON hasil operasi
     */
    public function destroy($id)
    {
        $absensi = Absensi::find($id);

        if (!$absensi) {
            return response()->json([
                'message' => 'Absensi tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        $absensi->delete();

        return response()->json([
            'message' => 'Absensi berhasil dihapus'
        ], Response::HTTP_OK);
    }

    /**
     * Mengekspor data absensi ke format tertentu (Excel/PDF)
     *
     * @param Request $request - Objek request
     * @param string $format - Format ekspor (excel/pdf)
     * @return mixed - Hasil ekspor (file download)
     */
    public function exportAbsensi(Request $request, $format)
    {
        // Ambil data dari function index
        $dataRaw = json_decode($this->index($request)->getContent(), true);
        $data = $dataRaw['data']['data'] ?? []; // Adjust for pagination structure

        // Pastikan data sesuai kebutuhan yang akan diekspor
        $formattedData = array_map(function ($item) {
            return [
                'ID Absensi' => $item['id'] ?? 'N/A',
                'Nama Karyawan' => $item['user']['nama'] ?? 'N/A',
                'Tanggal' => $item['tanggal'] ?? 'N/A',
                'Jam Masuk' => $item['jam_masuk'] ?? '-',
                'Jam Keluar' => $item['jam_keluar'] ?? '-',
                'Status' => isset($item['status']) ? ucfirst($item['status']) : 'N/A',
                'Keterangan' => $item['keterangan'] ?? '-'
            ];
        }, $data);

        // Header sesuai dengan format yang diminta
        $headers = ['ID Absensi', 'Nama Karyawan', 'Tanggal', 'Jam Masuk', 'Jam Keluar', 'Status', 'Keterangan'];
        $filename = 'laporan_absensi';
        $title = 'Laporan Data Absensi';

        return $this->handleExport($formattedData, $headers, $filename, $format, $title);
    }

    /**
     * Menangani proses ekspor data
     *
     * @param array $data - Data yang akan diekspor
     * @param array $headers - Header untuk file ekspor
     * @param string $filename - Nama file output
     * @param string $format - Format ekspor (excel/pdf)
     * @param string $title - Judul untuk dokumen
     * @return mixed - Hasil ekspor (file download)
     */
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

    /**
     * Mengimpor data absensi dari file Excel
     *
     * @param Request $request - Objek request berisi file Excel
     * @return \Illuminate\Http\JsonResponse - Response JSON hasil operasi
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv', // Validasi file yang diterima
        ]);

        try {
            // Import data dari file Excel
            Excel::import(new AbsensiImport, $request->file('file'));

            return response()->json([
                'message' => 'Data absensi berhasil diimpor.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Tangani error jika ada masalah dalam proses impor
            return response()->json([
                'message' => 'Terjadi kesalahan saat import data.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
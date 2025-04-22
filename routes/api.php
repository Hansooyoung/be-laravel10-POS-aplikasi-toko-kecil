<?php


use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\DiskonController;
use App\Http\Controllers\HistoryAktifitasController;
use App\Http\Controllers\HistoryTransaksiController;
use App\Http\Controllers\HistoryVoucherController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PengajuanBarangController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\RiwayatAktifitas;
use App\Http\Controllers\RiwayatAktifitasController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorController;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\Printer;


Route::post('login', [AuthController::class, 'login']);
Route::post('/registrasi-member', [MemberController::class, 'store']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');

Route::get('/test-printer', function() {
    try {
        $connector = new FilePrintConnector("POS-50");
        $printer = new Printer($connector);
        $printer->text("Test Printer\n");
        $printer->cut();
        $printer->close();
        return "Printer test sent";
    } catch (\Exception $e) {
        return "Printer error: " . $e->getMessage();
    }
});

Route::middleware('jwt.auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/pengajuan-barang', [PengajuanBarangController::class, 'index']);
    Route::post('/pengajuan-barang', [PengajuanBarangController::class, 'store']);
    Route::delete('/pengajuan-barang/{id}', [PengajuanBarangController::class, 'destroy']);
    Route::get('/diskon', [DiskonController::class, 'index']);
    Route::get('/satuan', [SatuanController::class, 'index']);
    Route::get('/voucher', [VoucherController::class, 'index']);
    Route::get('/vendor', [VendorController::class, 'index']);
    Route::get('/kategori', [KategoriController::class, 'index']);
    Route::get('pengajuan/export/{format}', [PengajuanBarangController::class, 'exportPengajuan']);
    Route::get('/penjualan/{id}', [PenjualanController::class, 'show']); // Detail penjualan
    Route::get('/pembelian/{id}', [PembelianController::class, 'show']);
    Route::get('/riwayat-aktifitas', [HistoryAktifitasController::class, 'index']);
    Route::get('/barang', [BarangController::class, 'index']);
    Route::get('/member', [MemberController::class, 'index']);


    Route::middleware('role:admin')->group(function () {

        Route::get('/absensi', [AbsensiController::class, 'index']);
        Route::post('/absensi', [AbsensiController::class, 'store']);
        Route::get('/absensi/{id}', [AbsensiController::class, 'show']);
        Route::put('/absensi/{absensi}/updateAbsensi', [AbsensiController::class, 'updateAbsensi']);
        Route::put('/absensi/{absensi}/updateJam', [AbsensiController::class, 'updateJamKeluar']);
        Route::put('/absensi/{absensi}/updateStatus', [AbsensiController::class, 'updateStatus']);
        Route::delete('/absensi/{id}', [AbsensiController::class, 'destroy']);
        Route::get('absensi/export/{format}', [AbsensiController::class, 'exportAbsensi']);
        Route::get('laporan-penjualan-barang/export/{format}', [LaporanController::class, 'exportLaporanPenjualan']);
        Route::get('laporan-pembelian/export/{format}', [LaporanController::class, 'exportLaporanPembelian']);
        Route::get('laporan-barang/export/{format}', [LaporanController::class, 'exportLaporanPenjualanBarang']);
        Route::post('/absensi/import', [AbsensiController::class, 'importExcel']);
        Route::get('/grafik-penjualan', [LaporanController::class, 'grafikPenjualan']);
        Route::post('/vendor', [VendorController::class, 'store']);
        Route::get('/vendor/{id}', [VendorController::class, 'show']);
        Route::put('/vendor/{id}', [VendorController::class, 'update']);
        Route::delete('/vendor/{id}', [VendorController::class, 'destroy']);

        Route::get('/satuan/{id}', [SatuanController::class, 'show']);
        Route::post('/satuan', [SatuanController::class, 'store']);
        Route::put('/satuan/{id}', [SatuanController::class, 'update']);
        Route::delete('/satuan/{id}', [SatuanController::class, 'destroy']);

        Route::get('/laporan-penjualan', [LaporanController::class, 'laporanPenjualan']);
        Route::get('/laporan-penjualan-barang', [LaporanController::class, 'laporanPenjualanBarang']);
        Route::get('/laporan-pembelian', [LaporanController::class, 'laporanPembelian']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::get('/kategori/{id}', [KategoriController::class, 'show']);
        Route::put('/kategori/{id}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);
        Route::get('/kategori/export/{format}', [KategoriController::class, 'export']);
        Route::post('/kategori/import', [kategoriController::class, 'import']);

        Route::post('/diskon/', [DiskonController::class, 'store']);
        Route::get('/diskon/{id}', [DiskonController::class, 'show']);
        Route::put('/diskon/{diskon}', [DiskonController::class, 'update']);
        Route::delete('/diskon/{diskon}', [DiskonController::class, 'destroy']);

        Route::get('/voucher/{id}', [VoucherController::class, 'show']);
        Route::post('/voucher', [VoucherController::class, 'store']);
        Route::put('/voucher/{id}', [VoucherController::class, 'update']);
        Route::delete('/voucher/{id}', [VoucherController::class, 'destroy']);
    });
    Route::middleware('role:user')->group(function () {

        // Update status pengajuan oleh operator (approved/rejected)


        Route::get('/penjualan', [HistoryTransaksiController::class, 'penjualan']); // List semua penjualan
        Route::get('/dashboard/kasir', [DashboardController::class, 'dashboardKasir']);

        Route::get('/penjualan/struk/{id}', [PenjualanController::class, 'struk']);
        Route::post('/penjualan/{id}/cetak-struk', [PenjualanController::class, 'cetakStruk']);

        Route::post('/penjualan', [PenjualanController::class, 'store']); // Simpan penjualan baru


        Route::post('/member', [MemberController::class, 'store']);
        Route::get('/member/{id}', [MemberController::class, 'show']);
        Route::put('/member/{id}', [MemberController::class, 'update']);
        Route::delete('/member/{id}', [MemberController::class, 'destroy']);

        Route::get('/historyvoucher', [HistoryVoucherController::class, 'index']);


    });
    Route::middleware('role:member')->group(function () {

            // Detail pengajuan barang
            Route::get('/pengajuan-barang/{id}', [PengajuanBarangController::class, 'show']);
            Route::get('/dashboard/member', [DashboardController::class, 'dashboardMember']);
            Route::get('/history-voucher/my', [HistoryVoucherController::class, 'myVouchers']);
            Route::post('/history-voucher/redeem', [HistoryVoucherController::class, 'redeem']);
            Route::get('/history-voucher/history', [HistoryVoucherController::class, 'riwayatPenukaran']);
            // Update pengajuan barang oleh member (hanya jika masih pending)
            Route::put('/pengajuan-barang/{id}', [PengajuanBarangController::class, 'updateMember']);
    });
    Route::middleware('role:operator')->group(function () {
        Route::get('/dashboard/operator', [DashboardController::class, 'dashboardOperator']);

        Route::put('/pembelian/{id}/tanggal-masuk', [PembelianController::class, 'updateTanggalMasuk']);
        Route::get('/pembelian', [HistoryTransaksiController::class, 'pembelian']);
        Route::post('/pembelian', [PembelianController::class, 'store']);
        Route::get('/barang/{kode_barang}', [BarangController::class, 'show']);
        Route::post('/barang', [BarangController::class, 'store']);
        Route::put('/barang/{kode_barang}', [BarangController::class, 'update']);
        Route::delete('/barang/{kode_barang}', [BarangController::class, 'destroy']);
        Route::put('/pengajuan-barang/{id}/update-status', [PengajuanBarangController::class, 'updateStatus']);
    });
    Route::middleware('role:super')->group(function () {
        Route::get('/pembelian-super', [PembelianController::class, 'index']);
        Route::get('/penjualan-super', [PenjualanController::class, 'index']);
        Route::put('/pembelian/{id}', [PembelianController::class, 'update']);
        Route::put('/penjualan/{id}', [PenjualanController::class, 'update']);
        Route::delete('/pembelian/{id}', [PembelianController::class, 'destroy']);
        Route::delete('/penjualan/{id}', [PenjualanController::class, 'destroy']);
        Route::get('/dashboard/super', [DashboardController::class, 'dashboardSuper']);
    });
});

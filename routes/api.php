<?php


use App\Http\Controllers\BarangController;
use App\Http\Controllers\DiskonController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorController;


Route::post('login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');


Route::middleware('jwt.auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/diskon', [DiskonController::class, 'index']);
    Route::get('/satuan', [SatuanController::class, 'index']);
       Route::get('/voucher', [VoucherController::class, 'index']);
    Route::get('/vendor', [VendorController::class, 'index']);
    Route::get('/kategori', [KategoriController::class, 'index']);

    Route::middleware('role:admin')->group(function () {
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
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::get('/kategori/{id}', [KategoriController::class, 'show']);
        Route::put('/kategori/{id}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

        Route::post('/diskon/', [DiskonController::class, 'store']);
        Route::get('/diskon/{id}', [DiskonController::class, 'show']);
        Route::put('/diskon/{diskon}', [DiskonController::class, 'update']);
        Route::delete('/diskon/{diskon}', [DiskonController::class, 'destroy']);

    });
    Route::middleware('role:user')->group(function () {

        Route::get('/barang', [BarangController::class, 'index']);
        Route::get('/barang/{kode_barang}', [BarangController::class, 'show']);
        Route::post('/barang', [BarangController::class, 'store']);
        Route::put('/barang/{kode_barang}', [BarangController::class, 'update']);
        Route::delete('/barang/{kode_barang}', [BarangController::class, 'destroy']);
        Route::put('/pembelian/{id}/tanggal-masuk', [PembelianController::class, 'updateTanggalMasuk']);

        Route::get('/pembelian', [PembelianController::class, 'index']);
        Route::get('/pembelian/{id}', [PembelianController::class, 'show']);
        Route::post('/pembelian', [PembelianController::class, 'store']);
        Route::get('/penjualan/struk/{id}', [PenjualanController::class, 'struk']);

        Route::get('/penjualan', [PenjualanController::class, 'index']); // List semua penjualan
        Route::get('/penjualan/{id}', [PenjualanController::class, 'show']); // Detail penjualan
        Route::post('/penjualan', [PenjualanController::class, 'store']); // Simpan penjualan baru

        Route::get('/member', [MemberController::class, 'index']);
        Route::get('/member/{id}', [MemberController::class, 'show']);
        Route::post('/member', [MemberController::class, 'store']);
        Route::put('/member/{id}', [MemberController::class, 'update']);
        Route::delete('/member/{id}', [MemberController::class, 'destroy']);

        Route::get('/voucher', [VoucherController::class, 'index']);
        Route::get('/voucher/{id}', [VoucherController::class, 'show']);
        Route::post('/voucher', [VoucherController::class, 'store']);
        Route::put('/voucher/{id}', [VoucherController::class, 'update']);
        Route::delete('/voucher/{id}', [VoucherController::class, 'destroy']);
    });
});

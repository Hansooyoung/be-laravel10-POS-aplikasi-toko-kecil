<?php


use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PembelianController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VendorController;

// Endpoint untuk login & logout
Route::post('login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');

// Middleware untuk semua user yang sudah login
Route::middleware('jwt.auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    // Semua user bisa melihat daftar vendor
    Route::get('/vendor', [VendorController::class, 'index']);
    Route::get('/kategori', [KategoriController::class, 'index']);

    // Admin-only routes untuk mengelola vendor
    Route::middleware('role:admin')->group(function () {
        Route::post('/vendor', [VendorController::class, 'store']);
        Route::get('/vendor/{id}', [VendorController::class, 'show']);
        Route::put('/vendor/{id}', [VendorController::class, 'update']);
        Route::delete('/vendor/{id}', [VendorController::class, 'destroy']);


        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::get('/kategori/{id}', [KategoriController::class, 'show']);
        Route::put('/kategori/{id}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

        Route::get('/barang', [BarangController::class, 'index']); // 📌 GET semua barang (paginate 10)
        Route::get('/barang/{kode_barang}', [BarangController::class, 'show']); // 📌 GET detail barang
        Route::post('/barang', [BarangController::class, 'store']); // 📌 POST tambah barang
        Route::put('/barang/{kode_barang}', [BarangController::class, 'update']); // 📌 PUT update barang
        Route::delete('/barang/{kode_barang}', [BarangController::class, 'destroy']); // 📌 DELETE soft delete barang

        Route::get('/pembelian', [PembelianController::class, 'index']); // 📌 GET daftar pembelian
        Route::get('/pembelian/{id}', [PembelianController::class, 'show']); // 📌 GET detail pembelian
        Route::post('/pembelian', [PembelianController::class, 'store']); // 📌 POST tambah pembelian
    });
});

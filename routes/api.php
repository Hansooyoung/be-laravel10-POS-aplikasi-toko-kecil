<?php


use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\UserController;
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

        Route::get('/user', [UserController::class, 'index']);
        Route::post('/user', [UserController::class, 'store']);
        Route::put('/user/{user}', [UserController::class, 'update']);
        Route::delete('/user/{user}', [UserController::class, 'destroy']);

        Route::post('/kategori', [KategoriController::class, 'store']);
        Route::get('/kategori/{id}', [KategoriController::class, 'show']);
        Route::put('/kategori/{id}', [KategoriController::class, 'update']);
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

        Route::get('/barang', [BarangController::class, 'index']);
        Route::get('/barang/{kode_barang}', [BarangController::class, 'show']);
        Route::post('/barang', [BarangController::class, 'store']);
        Route::put('/barang/{kode_barang}', [BarangController::class, 'update']);
        Route::delete('/barang/{kode_barang}', [BarangController::class, 'destroy']);

        Route::get('/pembelian', [PembelianController::class, 'index']);
        Route::get('/pembelian/{id}', [PembelianController::class, 'show']);
        Route::post('/pembelian', [PembelianController::class, 'store']);
    });
});

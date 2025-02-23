<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->string('kode_barang',50)->primary();
            $table->unsignedBigInteger('kategori_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vendor_id'); // Tambah vendor_id
            $table->unsignedBigInteger('satuan_id',);
            $table->string('barcode',50)->unique();
            $table->string('nama_barang');
            $table->enum('status',['aktif','tidak_aktif']);
            $table->decimal('profit_persen', 5, 2)->default(10); // Default 10%
            $table->double('harga_beli')->default(0);
            $table->string('gambar')->nullable();
            $table->integer('stok');
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('user');
            $table->foreign('satuan_id')->references('id')->on('satuan');
            $table->foreign('kategori_id')->references('id')->on('kategori');
            $table->foreign('vendor_id')->references('id')->on('vendor'); // Foreign key vendor
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};

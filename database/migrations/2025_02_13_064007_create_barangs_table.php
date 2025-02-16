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
            $table->string('nama_barang');
            $table->enum('satuan',['pcs','kardus','pack']);
            $table->enum('status',['aktif','tidak_aktif']);
            $table->double('harga_jual');
            $table->double('harga_beli');
            $table->string('gambar');
            $table->integer('stok');

            $table->foreign('user_id')->references('id')->on('user');
            $table->foreign('kategori_id')->references('id')->on('kategori');
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

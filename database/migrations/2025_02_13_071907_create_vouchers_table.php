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
        Schema::create('voucher', function (Blueprint $table) {
            $table->id();
            $table->string('nama_voucher');
            $table->bigInteger('harga_point');
            $table->enum('jenis_voucher',['persen','nominal']);
            $table->enum('status',['aktif','kadaluarsa']);
            $table->double('nilai_voucher');
            $table->double('min_pembelian');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};

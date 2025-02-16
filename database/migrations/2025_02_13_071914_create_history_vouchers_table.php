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
        Schema::create('history_voucher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('voucher_id');
            $table->date('tanggal_penukaran')->nullable();
            $table->date('tanggal_digunakan')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('member');
            $table->foreign('voucher_id')->references('id')->on('voucher');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_voucher');
    }
};

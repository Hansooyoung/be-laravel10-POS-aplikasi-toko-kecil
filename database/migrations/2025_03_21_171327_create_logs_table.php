<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('activity');
            $table->unsignedBigInteger('user_id');
            $table->text('description')->nullable();
            $table->datetime('tanggal_aktifitas'); // Tidak nullable
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user'); // Pastikan tabel referensinya benar
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

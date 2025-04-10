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
        Schema::create('pengajuan_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Hanya butuh user_id
            $table->unsignedBigInteger('member_id'); // Hanya butuh member_id
            $table->string('nama_barang');
            $table->integer('jumlah');
            $table->date('tanggal_pengajuan');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('pending');
            $table->text('pesan')->nullable(); // pesan tambahan
            $table->text('keterangan')->nullable(); // Alasan jika ditolak/disetujui
            $table->timestamps();
            $table->softDeletes(); // Tambahkan soft delete

            // Foreign Key ke tabel members
            $table->foreign('member_id')->references('id')->on('member');
            $table->foreign('user_id')->references('id')->on('user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_barang');
    }
};

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
        Schema::create('penerimaan', function (Blueprint $table) {
            $table->id();
        $table->unsignedBigInteger('kode_rekening_id');
        $table->unsignedBigInteger('tahun_anggaran_id');
        $table->date('tanggal');
        $table->decimal('jumlah', 20, 2);
        $table->string('keterangan')->nullable();
        $table->timestamps();
        
        $table->foreign('kode_rekening_id')->references('id')->on('kode_rekening')->onDelete('cascade');
        $table->foreign('tahun_anggaran_id')->references('id')->on('tahun_anggaran')->onDelete('cascade');
        
        // Tambahkan indeks untuk mempercepat query
        $table->index(['tanggal']);
        $table->index(['kode_rekening_id', 'tahun_anggaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan');
    }
};

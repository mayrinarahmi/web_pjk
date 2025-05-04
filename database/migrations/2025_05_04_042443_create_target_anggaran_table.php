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
        Schema::create('target_anggaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tahun_anggaran_id');
            $table->unsignedBigInteger('kode_rekening_id');
            $table->decimal('jumlah', 20, 2); // Pagu Anggaran
            $table->timestamps();
            
            $table->foreign('tahun_anggaran_id')->references('id')->on('tahun_anggaran')->onDelete('cascade');
            $table->foreign('kode_rekening_id')->references('id')->on('kode_rekening')->onDelete('cascade');
            
            $table->unique(['tahun_anggaran_id', 'kode_rekening_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_anggaran');
    }
};

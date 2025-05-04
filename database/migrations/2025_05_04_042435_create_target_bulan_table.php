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
        Schema::create('target_bulan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tahun_anggaran_id');
            $table->string('nama_kelompok');
            $table->json('bulan'); // Menyimpan array bulan dalam kelompok
            $table->decimal('persentase', 5, 2);
            $table->timestamps();
            
            $table->foreign('tahun_anggaran_id')->references('id')->on('tahun_anggaran')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_bulan');
    }
};

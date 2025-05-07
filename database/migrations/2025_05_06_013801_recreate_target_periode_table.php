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
        Schema::dropIfExists('target_periode');
    
        Schema::create('target_periode', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tahun_anggaran_id');
            $table->string('nama_periode');
            $table->integer('bulan_awal');
            $table->integer('bulan_akhir');
            $table->decimal('persentase', 8, 2);
            $table->timestamps();
            
            $table->foreign('tahun_anggaran_id')->references('id')->on('tahun_anggaran')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

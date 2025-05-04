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
        Schema::create('kode_rekening', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50); // Panjang ditambah untuk mendukung format seperti 4.1.01.09.01.0001
            $table->string('nama');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level'); // Level 1-6 sesuai format laporan
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('kode_rekening')->onDelete('cascade');
            $table->unique('kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kode_rekening');
    }
};

<?php
// ===============================================
// 1. Migration untuk tabel skpd
// File: database/migrations/2025_01_01_000001_create_skpd_table.php
// ===============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skpd', function (Blueprint $table) {
            $table->id();
            $table->string('kode_opd', 50)->unique();
            $table->string('nama_opd');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->json('kode_rekening_access')->nullable(); // Untuk simpan array kode rekening yang bisa diakses
            $table->timestamps();
            
            $table->index('kode_opd');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skpd');
    }
};
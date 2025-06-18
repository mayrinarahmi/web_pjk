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
        Schema::table('penerimaan', function (Blueprint $table) {
            // Tambah kolom tahun
            $table->year('tahun')->after('kode_rekening_id');
            
            // Update data existing - copy tahun dari relasi tahun_anggaran
            // Ini akan dilakukan via script terpisah
        });
        
        // Update data existing
        DB::statement("
            UPDATE penerimaan p 
            JOIN tahun_anggaran ta ON p.tahun_anggaran_id = ta.id 
            SET p.tahun = ta.tahun
        ");
        
        Schema::table('penerimaan', function (Blueprint $table) {
            // Drop foreign key dan kolom tahun_anggaran_id
            $table->dropForeign(['tahun_anggaran_id']);
            $table->dropColumn('tahun_anggaran_id');
            
            // Tambah index untuk performa
            $table->index(['tahun', 'kode_rekening_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan', function (Blueprint $table) {
            // Kembalikan kolom tahun_anggaran_id
            $table->unsignedBigInteger('tahun_anggaran_id')->after('kode_rekening_id');
            
            // Drop index
            $table->dropIndex(['tahun', 'kode_rekening_id']);
            
            // Restore foreign key
            $table->foreign('tahun_anggaran_id')->references('id')->on('tahun_anggaran')->onDelete('cascade');
        });
        
        // Update data kembali (ambil tahun anggaran murni yang aktif)
        DB::statement("
            UPDATE penerimaan p 
            JOIN tahun_anggaran ta ON p.tahun = ta.tahun AND ta.jenis_anggaran = 'murni'
            SET p.tahun_anggaran_id = ta.id
        ");
        
        Schema::table('penerimaan', function (Blueprint $table) {
            // Drop kolom tahun
            $table->dropColumn('tahun');
        });
    }
};
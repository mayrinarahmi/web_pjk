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
        Schema::table('tahun_anggaran', function (Blueprint $table) {
            // Tambah kolom untuk mendukung APBD Murni dan Perubahan
            $table->enum('jenis_anggaran', ['murni', 'perubahan'])->default('murni')->after('tahun');
            $table->unsignedBigInteger('parent_tahun_anggaran_id')->nullable()->after('jenis_anggaran');
            $table->date('tanggal_penetapan')->nullable()->after('parent_tahun_anggaran_id');
            $table->text('keterangan')->nullable()->after('tanggal_penetapan');
            
            // Tambah foreign key
            $table->foreign('parent_tahun_anggaran_id')
                  ->references('id')
                  ->on('tahun_anggaran')
                  ->onDelete('cascade');
            
            // Update unique constraint - tahun bisa sama jika jenis berbeda
            $table->dropUnique(['tahun']);
            $table->unique(['tahun', 'jenis_anggaran']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tahun_anggaran', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['parent_tahun_anggaran_id']);
            
            // Drop unique constraint
            $table->dropUnique(['tahun', 'jenis_anggaran']);
            
            // Drop columns
            $table->dropColumn(['jenis_anggaran', 'parent_tahun_anggaran_id', 'tanggal_penetapan', 'keterangan']);
            
            // Restore original unique constraint
            $table->unique('tahun');
        });
    }
};
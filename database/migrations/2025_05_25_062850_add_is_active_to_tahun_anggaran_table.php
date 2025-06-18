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
            // Tambah kolom is_active jika belum ada
            if (!Schema::hasColumn('tahun_anggaran', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('keterangan');
                
                // Tambah index untuk performa
                $table->index('is_active');
                
                // Pastikan hanya satu tahun anggaran yang aktif
                $table->unique(['is_active'], 'unique_active_tahun_anggaran')
                      ->where('is_active', true);
            }
        });
        
        // Set tahun anggaran terbaru sebagai aktif jika belum ada yang aktif
        $activeCount = DB::table('tahun_anggaran')->where('is_active', true)->count();
        if ($activeCount === 0) {
            $latestTahun = DB::table('tahun_anggaran')
                ->orderBy('tahun', 'desc')
                ->orderBy('jenis_anggaran', 'asc') // murni dulu
                ->first();
                
            if ($latestTahun) {
                DB::table('tahun_anggaran')
                    ->where('id', $latestTahun->id)
                    ->update(['is_active' => true]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tahun_anggaran', function (Blueprint $table) {
            if (Schema::hasColumn('tahun_anggaran', 'is_active')) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            }
        });
    }
};
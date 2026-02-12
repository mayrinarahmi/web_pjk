<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kode_rekening', function (Blueprint $table) {
            $table->integer('berlaku_mulai')->nullable()->after('is_active');
            $table->index('berlaku_mulai');
        });

        // Set semua kode rekening existing ke berlaku_mulai = 2022
        DB::table('kode_rekening')->whereNull('berlaku_mulai')->update(['berlaku_mulai' => 2022]);

        // Drop unique constraint lama pada 'kode' saja
        Schema::table('kode_rekening', function (Blueprint $table) {
            $table->dropUnique('kode_rekening_kode_unique');
        });

        // Buat unique composite baru: kode + berlaku_mulai
        Schema::table('kode_rekening', function (Blueprint $table) {
            $table->unique(['kode', 'berlaku_mulai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kode_rekening', function (Blueprint $table) {
            $table->dropUnique(['kode', 'berlaku_mulai']);
        });

        Schema::table('kode_rekening', function (Blueprint $table) {
            $table->unique('kode');
        });

        Schema::table('kode_rekening', function (Blueprint $table) {
            $table->dropIndex(['berlaku_mulai']);
            $table->dropColumn('berlaku_mulai');
        });
    }
};

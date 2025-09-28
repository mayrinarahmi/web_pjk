<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerimaan', function (Blueprint $table) {
            $table->unsignedBigInteger('skpd_id')->nullable()->after('kode_rekening_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('skpd_id');
            
            $table->foreign('skpd_id')->references('id')->on('skpd')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index('skpd_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan', function (Blueprint $table) {
            $table->dropForeign(['skpd_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['skpd_id', 'created_by']);
        });
    }
};
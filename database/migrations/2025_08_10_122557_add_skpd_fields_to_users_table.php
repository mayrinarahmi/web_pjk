<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 20)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('skpd_id')->nullable()->after('role_id');
            $table->foreign('skpd_id')->references('id')->on('skpd')->onDelete('set null');
            
            $table->index('nip');
            $table->index('skpd_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['skpd_id']);
            $table->dropColumn(['nip', 'skpd_id']);
        });
    }
};

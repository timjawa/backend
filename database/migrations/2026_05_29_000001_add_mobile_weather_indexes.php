<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perkiraan_cuaca', function (Blueprint $table) {
            $table->index(['waktu_lokal', 'kecamatan_id'], 'idx_perkiraan_waktu_kecamatan');
        });
    }

    public function down(): void
    {
        Schema::table('perkiraan_cuaca', function (Blueprint $table) {
            $table->dropIndex('idx_perkiraan_waktu_kecamatan');
        });
    }
};

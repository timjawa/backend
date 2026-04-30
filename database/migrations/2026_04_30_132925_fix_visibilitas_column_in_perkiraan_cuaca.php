<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fixes: visibilitas DECIMAL(5,2) → integer (BMKG returns values up to 99999 meters)
     *        kecepatan_angin DECIMAL(5,2) → DECIMAL(7,2) (some values can exceed 999)
     */
    public function up(): void
    {
        Schema::table('perkiraan_cuaca', function (Blueprint $table) {
            $table->integer('visibilitas')->nullable()->change();
            $table->decimal('kecepatan_angin', 7, 2)->nullable()->change();
        });

        // Fix same columns in cuaca_realtime too
        Schema::table('cuaca_realtime', function (Blueprint $table) {
            $table->integer('visibilitas')->nullable()->change();
            $table->decimal('kecepatan_angin', 7, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perkiraan_cuaca', function (Blueprint $table) {
            $table->decimal('visibilitas', 5, 2)->nullable()->change();
            $table->decimal('kecepatan_angin', 5, 2)->nullable()->change();
        });

        Schema::table('cuaca_realtime', function (Blueprint $table) {
            $table->decimal('visibilitas', 5, 2)->nullable()->change();
            $table->decimal('kecepatan_angin', 5, 2)->nullable()->change();
        });
    }
};

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
        // =============================================
        // TABEL KECAMATAN (31 Kecamatan di Jember)
        // =============================================
        Schema::create('subdistricts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Nama kecamatan
            $table->string('code', 20)->unique();      // Kode kecamatan
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('elevation', 8, 2)->nullable();  // Elevasi wilayah (untuk prediksi)
            $table->integer('population')->nullable();        // Jumlah penduduk
            $table->decimal('area_km2', 10, 2)->nullable();   // Luas wilayah
            $table->timestamps();
        });

        // =============================================
        // TABEL KATEGORI BENCANA
        // banjir, longsor, pohon tumbang, kebakaran, dll
        // =============================================
        Schema::create('disaster_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // e.g. Banjir, Longsor, Pohon Tumbang
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();  // Hex color untuk UI
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disaster_categories');
        Schema::dropIfExists('subdistricts');
    }
};

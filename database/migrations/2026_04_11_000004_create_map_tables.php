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
        // TABEL TITIK RAWAN BANJIR
        // Lokasi-lokasi rawan bencana di peta
        // =============================================
        Schema::create('flood_prone_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->string('name');                     // Nama lokasi rawan
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('risk_level', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('subdistrict_id');
            $table->index('risk_level');
        });

        // =============================================
        // TABEL LOKASI PENGUNGSIAN
        // Tempat pengungsian resmi
        // =============================================
        Schema::create('evacuation_shelters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->string('name');                      // Nama tempat pengungsian
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('capacity')->nullable();      // Kapasitas orang
            $table->string('facilities')->nullable();     // Fasilitas tersedia
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('subdistrict_id');
        });

        // =============================================
        // TABEL RUTE JALAN AMAN
        // Jalur evakuasi & akses aman saat bencana
        // =============================================
        Schema::create('safe_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->string('name');                      // Nama rute
            $table->text('description')->nullable();
            $table->json('route_coordinates');             // Array koordinat polyline [{"lat":x,"lng":y}, ...]
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->enum('status', ['aman', 'terganggu', 'terputus'])->default('aman');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('subdistrict_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safe_routes');
        Schema::dropIfExists('evacuation_shelters');
        Schema::dropIfExists('flood_prone_areas');
    }
};

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
        // TABEL DATA CURAH HUJAN
        // Data curah hujan per kecamatan (real-time)
        // =============================================
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->decimal('temperature', 5, 2)->nullable();    // Suhu (°C)
            $table->decimal('humidity', 5, 2)->nullable();       // Kelembapan (%)
            $table->decimal('rainfall', 8, 2)->nullable();       // Curah hujan (mm)
            $table->decimal('wind_speed', 6, 2)->nullable();     // Kecepatan angin (km/h)
            $table->string('wind_direction', 10)->nullable();    // Arah angin
            $table->string('condition', 50)->nullable();          // Cerah, Berawan, Hujan, dll
            $table->string('icon', 30)->nullable();               // Icon code cuaca
            $table->enum('time_period', ['pagi', 'siang', 'sore', 'malam', 'dini_hari'])->nullable();
            $table->date('forecast_date');
            $table->string('source', 50)->default('BMKG');       // Sumber data
            $table->timestamps();

            $table->index(['subdistrict_id', 'forecast_date']);
            $table->index('forecast_date');
        });

        // =============================================
        // TABEL STATISTIK BENCANA
        // Data historis & statistik bencana
        // =============================================
        Schema::create('disaster_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('disaster_categories')->onDelete('cascade');
            $table->date('occurred_date');
            $table->integer('affected_people')->default(0);      // Korban terdampak
            $table->integer('injured')->default(0);               // Korban luka
            $table->integer('fatalities')->default(0);            // Korban meninggal
            $table->integer('evacuated')->default(0);             // Mengungsi
            $table->integer('damaged_houses')->default(0);        // Rumah rusak
            $table->decimal('estimated_loss', 15, 2)->nullable(); // Kerugian (Rupiah)
            $table->text('description')->nullable();
            $table->string('source', 100)->nullable();            // Sumber data
            $table->timestamps();

            $table->index(['subdistrict_id', 'occurred_date']);
            $table->index('category_id');
            $table->index('occurred_date');
        });

        // =============================================
        // TABEL PREDIKSI POTENSI BANJIR (AI/Analitik)
        // Hasil analisis prediksi berbasis data
        // =============================================
        Schema::create('flood_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->date('prediction_date');
            $table->enum('risk_level', ['rendah', 'sedang', 'tinggi', 'sangat_tinggi']);
            $table->decimal('probability', 5, 2)->nullable();    // Probabilitas (0-100%)
            $table->decimal('rainfall_prediction', 8, 2)->nullable(); // Prediksi curah hujan (mm)
            $table->json('factors')->nullable();                  // Faktor-faktor: {curah_hujan, riwayat, elevasi}
            $table->text('recommendation')->nullable();
            $table->string('model_version', 50)->nullable();      // Versi model AI
            $table->timestamps();

            $table->index(['subdistrict_id', 'prediction_date']);
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flood_predictions');
        Schema::dropIfExists('disaster_statistics');
        Schema::dropIfExists('weather_data');
    }
};

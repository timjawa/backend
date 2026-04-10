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
        // TABEL LAPORAN BENCANA (Smart Report System)
        // Masyarakat melaporkan bencana
        // =============================================
        Schema::create('disaster_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();  // Nomor laporan otomatis: LP-YYYYMMDD-XXXX
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('disaster_categories')->onDelete('restrict');
            $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts')->onDelete('set null');

            // Detail laporan
            $table->string('title');
            $table->text('description');                 // Kronologi kejadian
            $table->text('address')->nullable();          // Alamat detail kejadian

            // Lokasi GPS (auto detect)
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Status alur: menunggu → diverifikasi → ditindaklanjuti → selesai / ditolak
            $table->enum('status', [
                'menunggu',
                'diverifikasi',
                'ditindaklanjuti',
                'selesai',
                'ditolak'
            ])->default('menunggu');

            $table->enum('severity', ['ringan', 'sedang', 'berat'])->default('sedang');
            $table->integer('affected_people')->nullable();  // Jumlah korban terdampak
            $table->text('rejection_reason')->nullable();     // Alasan ditolak (jika ditolak)

            // Verifikasi
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();

            // Penanganan
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes untuk performa query
            $table->index('status');
            $table->index('category_id');
            $table->index('subdistrict_id');
            $table->index('created_at');
            $table->index(['latitude', 'longitude']);
        });

        // =============================================
        // TABEL MEDIA LAPORAN (Foto/Video Bukti)
        // =============================================
        Schema::create('report_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('disaster_reports')->onDelete('cascade');
            $table->enum('type', ['image', 'video']);
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable();    // dalam bytes
            $table->string('mime_type')->nullable();
            $table->text('thumbnail_path')->nullable();  // Thumbnail untuk video
            $table->timestamps();

            $table->index('report_id');
        });

        // =============================================
        // TABEL VERIFIKASI AI (Hasil Deteksi Gambar)
        // AI mendeteksi apakah gambar benar bencana
        // =============================================
        Schema::create('ai_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('disaster_reports')->onDelete('cascade');
            $table->foreignId('media_id')->nullable()->constrained('report_media')->onDelete('set null');
            $table->boolean('is_verified');               // true = valid bencana, false = bukan
            $table->decimal('confidence_score', 5, 2);    // Skor kepercayaan AI (0-100%)
            $table->string('detected_category')->nullable(); // Kategori yang terdeteksi AI
            $table->json('ai_response')->nullable();       // Raw response dari AI
            $table->timestamps();

            $table->index('report_id');
        });

        // =============================================
        // TABEL CATATAN INTERNAL (Petugas)
        // Catatan antar petugas pada laporan
        // =============================================
        Schema::create('report_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('disaster_reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('note');
            $table->timestamps();

            $table->index('report_id');
        });

        // =============================================
        // TABEL REVIEW PENGADUAN (Dari Masyarakat)
        // Masyarakat memberikan review penanganan
        // =============================================
        Schema::create('report_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('disaster_reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating');                // 1-5 bintang
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'user_id']);     // 1 user = 1 review per laporan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_reviews');
        Schema::dropIfExists('report_notes');
        Schema::dropIfExists('ai_verifications');
        Schema::dropIfExists('report_media');
        Schema::dropIfExists('disaster_reports');
    }
};

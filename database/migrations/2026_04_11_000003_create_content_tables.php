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
        // TABEL BERITA BENCANA
        // Berita/informasi dari admin
        // =============================================
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();       // Ringkasan pendek
            $table->longText('content');                // Isi berita lengkap (HTML)
            $table->string('featured_image')->nullable();
            $table->string('category', 50);             // Peringatan, Info, Berita, Cuaca, Kegiatan
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamps();

            $table->index('is_published');
            $table->index('category');
            $table->index('published_at');
        });

        // =============================================
        // TABEL KONTEN EDUKASI
        // Artikel, Video, Tips kebencanaan
        // =============================================
        Schema::create('education_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['artikel', 'video', 'tips', 'checklist']);
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('video_url')->nullable();     // URL video (YouTube, dll)
            $table->string('category', 50)->nullable();   // Banjir, Longsor, Evakuasi, dll
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('is_published');
        });

        // =============================================
        // TABEL PENGUMUMAN DARURAT
        // Broadcast pengumuman ke seluruh pengguna
        // =============================================
        Schema::create('emergency_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('level', ['info', 'warning', 'danger', 'critical'])->default('info');
            $table->enum('target', ['all', 'subdistrict'])->default('all');  // Target audience
            $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('level');
        });

        // =============================================
        // TABEL STATUS SIAGA WILAYAH
        // Update level siaga per kecamatan
        // =============================================
        Schema::create('alert_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->onDelete('cascade');
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->enum('level', ['aman', 'waspada', 'siaga', 'awas'])->default('aman');
            $table->text('description')->nullable();
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->boolean('is_current')->default(true);  // Apakah status ini yang aktif
            $table->timestamps();

            $table->index(['subdistrict_id', 'is_current']);
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_statuses');
        Schema::dropIfExists('emergency_announcements');
        Schema::dropIfExists('education_contents');
        Schema::dropIfExists('news');
    }
};
